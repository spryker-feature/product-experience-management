<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport;

use Generated\Shared\Transfer\ImportPublishEventTransfer;
use Generated\Shared\Transfer\ImportRowValidationCollectionTransfer;
use Generated\Shared\Transfer\ImportStepErrorTransfer;
use Generated\Shared\Transfer\ImportStepResponseTransfer;
use Orm\Zed\Availability\Persistence\Map\SpyAvailabilityTableMap;
use Orm\Zed\Availability\Persistence\SpyAvailabilityAbstract;
use Orm\Zed\Availability\Persistence\SpyAvailabilityAbstractQuery;
use Orm\Zed\Availability\Persistence\SpyAvailabilityQuery;
use Orm\Zed\Oms\Persistence\SpyOmsProductReservationQuery;
use Orm\Zed\Product\Persistence\SpyProductQuery;
use Orm\Zed\Stock\Persistence\Map\SpyStockProductTableMap;
use Orm\Zed\Stock\Persistence\SpyStock;
use Orm\Zed\Stock\Persistence\SpyStockProductQuery;
use Orm\Zed\Stock\Persistence\SpyStockQuery;
use Spryker\DecimalObject\Decimal;
use Spryker\Zed\Availability\Dependency\AvailabilityEvents;
use Spryker\Zed\Product\Dependency\ProductEvents;
use Spryker\Zed\Propel\Persistence\BatchProcessor\ActiveRecordBatchProcessorTrait;
use Spryker\Zed\PropelOrm\Business\Runtime\ActiveQuery\Criteria;
use Spryker\Zed\Stock\Business\StockFacadeInterface;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportStepInterface;

/**
 * Processes per-warehouse stock columns: "Stock ({warehouse})" → system "stock.{warehouse}".
 * Value is a quantity number or NOOS (Never Out Of Stock).
 */
class ProductCsvImportStockStep extends AbstractProductCsvImportStep implements ImportStepInterface
{
    use ActiveRecordBatchProcessorTrait;

    protected const string NOOS_VALUE = 'NOOS';

    protected const string STOCK_COLUMN_PATTERN = '/^stock\.(.+)$/';

    protected const string PATTERN_WAREHOUSE_NAME = '/\((.+)\)/';

    /**
     * @var array<string, int|null>
     */
    protected static array $productConcreteIdCache = [];

    public function __construct(
        protected StoreFacadeInterface $storeFacade,
        protected StockFacadeInterface $stockFacade,
    ) {
    }

    /**
     * @var array<string, \Orm\Zed\Stock\Persistence\SpyStock|null>
     */
    protected static array $stockCache = [];

    /**
     * {@inheritDoc}
     *
     * @param array<int, array<string, string>> $rows
     * @param array<string, string> $propertyNamesInFile
     */
    public function executeBatch(array $rows, array $propertyNamesInFile = []): ImportStepResponseTransfer
    {
        $response = (new ImportStepResponseTransfer())->setIsSuccessful(true);

        $processedProductAbstractIds = [];
        $processedConcreteSkus = [];

        foreach ($rows as $rowNumber => $row) {
            $concreteSku = $this->resolveConcreteSku($row);

            if ($concreteSku === '') {
                continue;
            }

            $stockEntries = $this->extractStockEntries($row, $propertyNamesInFile);

            if ($stockEntries === []) {
                continue;
            }

            $validationResult = $this->validateRow($row, $rowNumber, $propertyNamesInFile, $stockEntries);

            if ($validationResult->getErrors()->count() > 0) {
                $response->setIsSuccessful(false);

                foreach ($validationResult->getErrors() as $error) {
                    $response->addError($error);
                }

                continue;
            }

            $idProduct = $this->resolveProductConcreteId($concreteSku);

            if ($idProduct === null) {
                continue;
            }

            foreach ($stockEntries as $entry) {
                $stockEntity = $this->resolveStockEntity($entry['warehouseName']);

                if ($stockEntity === null) {
                    continue;
                }

                $idStock = $stockEntity->getIdStock();

                $this->upsertStockProduct($idProduct, $idStock, $entry['quantity'], $entry['isNeverOutOfStock']);
            }

            $abstractSku = trim($row[static::COLUMN_ABSTRACT_SKU] ?? '');
            $isNeverOutOfStock = array_reduce($stockEntries, static fn (bool $carry, array $entry): bool => $carry || $entry['isNeverOutOfStock'], false);
            $processedConcreteSkus[$concreteSku] = [
                'abstractSku' => $abstractSku,
                'isNeverOutOfStock' => $isNeverOutOfStock,
            ];
            $processedProductAbstractIds[] = $this->resolveProductAbstractId($concreteSku);
        }

        $this->commit();
        $this->updateAvailabilityForConcreteSkus($processedConcreteSkus, $response);
        $this->addPublishEvents($processedProductAbstractIds, $response);

        return $response;
    }

    /**
     * @param array<string, string> $row
     * @param array<string, string> $propertyNamesInFile
     * @param array<array{warehouseName: string, fileHeader: string, quantity: int, isNeverOutOfStock: bool, isValid: bool, rawValue: string}> $stockEntries
     */
    protected function validateRow(array $row, int $rowNumber, array $propertyNamesInFile, array $stockEntries): ImportRowValidationCollectionTransfer
    {
        $result = new ImportRowValidationCollectionTransfer();
        $concreteSku = $this->resolveConcreteSku($row);

        if ($this->resolveProductConcreteId($concreteSku) === null) {
            $result->addError((new ImportStepErrorTransfer())
                ->setCsvRowNumber($rowNumber)
                ->setErrorMessage(sprintf(
                    'The value \'%s\' in field \'%s\' is not valid because the concrete product does not exist.',
                    $concreteSku,
                    $propertyNamesInFile[static::COLUMN_CONCRETE_SKU] ?? static::COLUMN_CONCRETE_SKU,
                )));
        }

        foreach ($stockEntries as $entry) {
            if (!$entry['isValid']) {
                $result->addError((new ImportStepErrorTransfer())
                    ->setCsvRowNumber($rowNumber)
                    ->setErrorMessage(sprintf(
                        'The value \'%s\' in field \'%s\' is not valid. Expected: a numeric quantity or NOOS (Never Out Of Stock).',
                        $entry['rawValue'],
                        $entry['fileHeader'],
                    )));

                continue;
            }

            if ($this->resolveStockEntity($entry['warehouseName']) === null) {
                $result->addError((new ImportStepErrorTransfer())
                    ->setCsvRowNumber($rowNumber)
                    ->setErrorMessage(sprintf(
                        'The value \'%s\' in field \'%s\' is not valid because the warehouse does not exist.',
                        $entry['warehouseName'],
                        $entry['fileHeader'],
                    )));
            }
        }

        return $result;
    }

    /**
     * Extracts stock entries from per-warehouse columns matching "stock.*".
     *
     * @param array<string, string> $row
     * @param array<string, string> $propertyNamesInFile
     *
     * @return array<array{warehouseName: string, fileHeader: string, quantity: int, isNeverOutOfStock: bool, isValid: bool, rawValue: string}>
     */
    protected function extractStockEntries(array $row, array $propertyNamesInFile = []): array
    {
        $entries = [];
        $reverseMapping = $propertyNamesInFile;

        foreach ($row as $key => $value) {
            if (!preg_match(static::STOCK_COLUMN_PATTERN, $key)) {
                continue;
            }

            $value = trim($value);

            if ($value === '') {
                continue;
            }

            $fileHeader = $reverseMapping[$key] ?? $key;
            $warehouseName = $this->extractWarehouseNameFromFileHeader($fileHeader);
            $isNeverOutOfStock = strtoupper($value) === static::NOOS_VALUE;
            $isValidQuantity = $isNeverOutOfStock || is_numeric($value);

            $entries[] = [
                'warehouseName' => $warehouseName,
                'fileHeader' => $fileHeader,
                'quantity' => $isNeverOutOfStock ? 0 : (int)$value,
                'isNeverOutOfStock' => $isNeverOutOfStock,
                'isValid' => $isValidQuantity,
                'rawValue' => $value,
            ];
        }

        return $entries;
    }

    /**
     * Extracts warehouse name from file header like "Stock (Warehouse 1)".
     */
    protected function extractWarehouseNameFromFileHeader(string $fileHeader): string
    {
        if (preg_match(static::PATTERN_WAREHOUSE_NAME, $fileHeader, $matches)) {
            return $matches[1];
        }

        return $fileHeader;
    }

    protected function upsertStockProduct(int $idProduct, int $idStock, int $quantity, bool $isNeverOutOfStock): void
    {
        $stockProductEntity = SpyStockProductQuery::create()
            ->filterByFkProduct($idProduct)
            ->filterByFkStock($idStock)
            ->findOneOrCreate();

        $stockProductEntity->setFkProduct($idProduct);
        $stockProductEntity->setFkStock($idStock);
        $stockProductEntity->setQuantity((string)$quantity);
        $stockProductEntity->setIsNeverOutOfStock($isNeverOutOfStock);

        if ($stockProductEntity->isNew() || $stockProductEntity->isModified()) {
            $this->persist($stockProductEntity);
        }
    }

    protected function resolveStockEntity(string $stockName): ?SpyStock
    {
        if (!array_key_exists($stockName, static::$stockCache)) {
            static::$stockCache[$stockName] = SpyStockQuery::create()
                ->filterByName($stockName)
                ->findOne();
        }

        return static::$stockCache[$stockName];
    }

    protected function resolveProductConcreteId(string $concreteSku): ?int
    {
        if (!array_key_exists($concreteSku, static::$productConcreteIdCache)) {
            static::$productConcreteIdCache[$concreteSku] = SpyProductQuery::create()
                ->filterBySku($concreteSku)
                ->findOne()
                ?->getIdProduct();
        }

        return static::$productConcreteIdCache[$concreteSku];
    }

    protected function resolveProductAbstractId(string $concreteSku): ?int
    {
        $idProduct = static::$productConcreteIdCache[$concreteSku] ?? null;

        if ($idProduct === null) {
            return null;
        }

        return SpyProductQuery::create()
            ->filterByIdProduct($idProduct)
            ->findOne()
            ?->getFkProductAbstract();
    }

    /**
     * @param array<int|null> $productAbstractIds
     */
    protected function addPublishEvents(array $productAbstractIds, ImportStepResponseTransfer $response): void
    {
        foreach (array_unique(array_filter($productAbstractIds)) as $idProductAbstract) {
            $response->addPublishEvent(
                (new ImportPublishEventTransfer())->setEventName(ProductEvents::PRODUCT_ABSTRACT_PUBLISH)->setEntityId($idProductAbstract),
            );
        }
    }

    /**
     * Calculates and persists availability records for each concrete SKU per store.
     * Matches the approach used by the standard Spryker ProductStockPropelDataSetWriter:
     * availability = stock quantity (filtered by store warehouses) minus OMS reservations.
     *
     * @param array<string, array{abstractSku: string, isNeverOutOfStock: bool}> $processedConcreteSkus concreteSku => data
     */
    protected function updateAvailabilityForConcreteSkus(array $processedConcreteSkus, ImportStepResponseTransfer $response): void
    {
        $stores = $this->storeFacade->getAllStores();
        $storeToWarehouseMapping = $this->stockFacade->getStoreToWarehouseMapping();

        foreach ($processedConcreteSkus as $concreteSku => $skuData) {
            $abstractSku = $skuData['abstractSku'];
            $isNeverOutOfStock = $skuData['isNeverOutOfStock'];

            $idProduct = $this->resolveProductConcreteId($concreteSku);

            if ($idProduct === null) {
                continue;
            }

            foreach ($stores as $storeTransfer) {
                $idStore = $storeTransfer->getIdStoreOrFail();
                $storeName = $storeTransfer->getNameOrFail();
                $warehouseNames = $storeToWarehouseMapping[$storeName] ?? [];

                $stockQuantity = $this->calculateStockQuantityForWarehouses($idProduct, $warehouseNames);
                $reservedQuantity = $this->getReservationQuantity($concreteSku, $idStore);
                $availableQuantity = $stockQuantity->subtract($reservedQuantity);

                if ($availableQuantity->lessThan(0)) {
                    $availableQuantity = new Decimal(0);
                }

                $availabilityAbstractEntity = SpyAvailabilityAbstractQuery::create()
                    ->filterByAbstractSku($abstractSku)
                    ->filterByFkStore($idStore)
                    ->findOneOrCreate();

                if ($availabilityAbstractEntity->isNew()) {
                    $availabilityAbstractEntity->setQuantity('0');
                    $availabilityAbstractEntity->save();
                }

                $availabilityEntity = SpyAvailabilityQuery::create()
                    ->filterBySku($concreteSku)
                    ->filterByFkStore($idStore)
                    ->findOneOrCreate();

                $availabilityEntity->setFkAvailabilityAbstract($availabilityAbstractEntity->getIdAvailabilityAbstract());
                $availabilityEntity->setQuantity($availableQuantity->toString());
                $availabilityEntity->setIsNeverOutOfStock($isNeverOutOfStock);

                if ($availabilityEntity->isNew() || $availabilityEntity->isModified()) {
                    $availabilityEntity->save();
                }

                $this->updateAbstractAvailabilityQuantity($availabilityAbstractEntity, $idStore);

                $response->addPublishEvent(
                    (new ImportPublishEventTransfer())
                        ->setEventName(AvailabilityEvents::AVAILABILITY_ABSTRACT_PUBLISH)
                        ->setEntityId($availabilityAbstractEntity->getIdAvailabilityAbstract()),
                );
            }
        }
    }

    /**
     * @param array<string> $warehouseNames
     */
    protected function calculateStockQuantityForWarehouses(int $idProduct, array $warehouseNames): Decimal
    {
        if ($warehouseNames === []) {
            return new Decimal(0);
        }

        $totalQuantity = SpyStockProductQuery::create()
            ->filterByFkProduct($idProduct)
            ->useStockQuery()
                ->filterByName($warehouseNames, Criteria::IN)
            ->endUse()
            ->withColumn(sprintf('SUM(%s)', SpyStockProductTableMap::COL_QUANTITY), 'totalQuantity')
            ->select(['totalQuantity'])
            ->findOne();

        return new Decimal((string)($totalQuantity ?? 0));
    }

    protected function getReservationQuantity(string $concreteSku, int $idStore): Decimal
    {
        $reservations = SpyOmsProductReservationQuery::create()
            ->filterBySku($concreteSku)
            ->filterByFkStore($idStore)
            ->find();

        $total = new Decimal(0);

        foreach ($reservations as $reservation) {
            $total = $total->add($reservation->getReservationQuantity());
        }

        return $total;
    }

    protected function updateAbstractAvailabilityQuantity(
        SpyAvailabilityAbstract $availabilityAbstractEntity,
        int $idStore,
    ): void {
        $sumQuantity = SpyAvailabilityQuery::create()
            ->filterByFkAvailabilityAbstract($availabilityAbstractEntity->getIdAvailabilityAbstract())
            ->filterByFkStore($idStore)
            ->withColumn(sprintf('SUM(%s)', SpyAvailabilityTableMap::COL_QUANTITY), 'totalQuantity')
            ->select(['totalQuantity'])
            ->findOne();

        $availabilityAbstractEntity->setQuantity((string)($sumQuantity ?? 0));

        if ($availabilityAbstractEntity->isModified()) {
            $availabilityAbstractEntity->save();
        }
    }
}
