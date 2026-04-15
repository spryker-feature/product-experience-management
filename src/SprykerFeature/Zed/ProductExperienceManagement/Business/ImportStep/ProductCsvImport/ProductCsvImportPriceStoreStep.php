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
use Orm\Zed\Currency\Persistence\SpyCurrencyQuery;
use Orm\Zed\PriceProduct\Persistence\SpyPriceProduct;
use Orm\Zed\PriceProduct\Persistence\SpyPriceProductDefaultQuery;
use Orm\Zed\PriceProduct\Persistence\SpyPriceProductQuery;
use Orm\Zed\PriceProduct\Persistence\SpyPriceProductStoreQuery;
use Orm\Zed\Product\Persistence\SpyProductAbstractQuery;
use Orm\Zed\Product\Persistence\SpyProductQuery;
use Orm\Zed\Store\Persistence\SpyStoreQuery;
use Spryker\Zed\PriceProduct\Dependency\PriceProductEvents;
use Spryker\Zed\Product\Dependency\ProductEvents;
use Spryker\Zed\Propel\Persistence\BatchProcessor\ActiveRecordBatchProcessorTrait;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportStepInterface;

/**
 * Sets price amounts on spy_price_product_store and ensures spy_price_product_default.
 * Runs after ProductCsvImportPriceStep which creates the spy_price_product records.
 */
class ProductCsvImportPriceStoreStep implements ImportStepInterface
{
    use ActiveRecordBatchProcessorTrait;

    protected const string COLUMN_ABSTRACT_SKU = 'abstract_sku';

    protected const string COLUMN_CONCRETE_SKU = 'concrete_sku';

    protected const string PRICE_COLUMN_PATTERN = '/^price\.(\w+)\.(\w+)\.(\w+)\.(net|gross)$/';

    /**
     * @var array<string, \Orm\Zed\Store\Persistence\SpyStore|null>
     */
    protected static array $storeCache = [];

    /**
     * @var array<string, \Orm\Zed\Currency\Persistence\SpyCurrency|null>
     */
    protected static array $currencyCache = [];

    /**
     * @var array<string, int|null>
     */
    protected static array $productAbstractIdCache = [];

    /**
     * @var array<string, int|null>
     */
    protected static array $productConcreteIdCache = [];

    /**
     * @var array<string, \Orm\Zed\PriceProduct\Persistence\SpyPriceProduct|null>
     */
    protected static array $priceProductCache = [];

    /**
     * {@inheritDoc}
     *
     * @param array<int, array<string, string>> $rows
     * @param array<string, string> $propertyNamesInFile
     */
    public function executeBatch(array $rows, array $propertyNamesInFile = []): ImportStepResponseTransfer
    {
        $response = (new ImportStepResponseTransfer())->setIsSuccessful(true);

        $processedPriceProducts = [];

        foreach ($rows as $rowNumber => $row) {
            $priceGroups = $this->parsePriceGroups($row);

            if ($priceGroups === []) {
                continue;
            }

            $validationResult = $this->validateRow($row, $rowNumber, $propertyNamesInFile, $priceGroups);

            if ($validationResult->getErrors()->count() > 0) {
                $response->setIsSuccessful(false);

                foreach ($validationResult->getErrors() as $error) {
                    $response->addError($error);
                }

                continue;
            }

            $priceProductEntity = $this->resolvePriceProduct($row);

            if ($priceProductEntity === null) {
                continue;
            }

            foreach ($priceGroups as $group) {
                $this->upsertPriceProductStore($priceProductEntity, $group);
                $processedPriceProducts[] = $priceProductEntity;
            }
        }

        $this->commit();
        $this->addPricePublishEvents($processedPriceProducts, $response);

        return $response;
    }

    /**
     * @param array<string, string> $row
     * @param array<string, string> $propertyNamesInFile
     * @param array<string, array{type: string, store: string, currency: string, net: string, gross: string}> $priceGroups
     */
    protected function validateRow(array $row, int $rowNumber, array $propertyNamesInFile, array $priceGroups): ImportRowValidationCollectionTransfer
    {
        $result = new ImportRowValidationCollectionTransfer();

        if ($this->resolveConcreteSku($row) === null && $this->resolveAbstractSku($row) === null) {
            $result->addError((new ImportStepErrorTransfer())
                ->setCsvRowNumber($rowNumber)
                ->setErrorMessage(sprintf('The value \'\' in field \'%s\' is not valid because neither Abstract SKU nor Concrete SKU is provided. Expected: at least one SKU. Please update the value.', $propertyNamesInFile[static::COLUMN_ABSTRACT_SKU] ?? static::COLUMN_ABSTRACT_SKU)));
        }

        foreach ($priceGroups as $group) {
            $this->resolveStore($group['store']);

            if (static::$storeCache[$group['store']] === null) {
                $result->addError((new ImportStepErrorTransfer())
                    ->setCsvRowNumber($rowNumber)
                    ->setErrorMessage(sprintf('The value \'%s\' in price column is not valid because the store does not exist. Expected: an existing store name.', $group['store'])));
            }

            $this->resolveCurrency($group['currency']);

            if (static::$currencyCache[$group['currency']] === null) {
                $result->addError((new ImportStepErrorTransfer())
                    ->setCsvRowNumber($rowNumber)
                    ->setErrorMessage(sprintf('The value \'%s\' in price column is not valid because the currency does not exist. Expected: an existing currency code.', $group['currency'])));
            }
        }

        return $result;
    }

    /**
     * @param array<string, string> $row
     */
    protected function resolvePriceProduct(array $row): ?SpyPriceProduct
    {
        $concreteSku = $this->resolveConcreteSku($row);
        $productSku = $concreteSku ?? $this->resolveAbstractSku($row) ?? '';

        if (!array_key_exists($productSku, static::$priceProductCache)) {
            $query = SpyPriceProductQuery::create();

            if ($concreteSku !== null) {
                $query->filterByFkProduct($this->resolveProductConcreteId($concreteSku));
            } else {
                $query->filterByFkProductAbstract($this->resolveProductAbstractId($productSku));
            }

            static::$priceProductCache[$productSku] = $query->findOne();
        }

        return static::$priceProductCache[$productSku];
    }

    protected function resolveProductAbstractId(string $abstractSku): ?int
    {
        if (!array_key_exists($abstractSku, static::$productAbstractIdCache)) {
            static::$productAbstractIdCache[$abstractSku] = SpyProductAbstractQuery::create()
                ->filterBySku($abstractSku)
                ->findOne()
                ?->getIdProductAbstract();
        }

        return static::$productAbstractIdCache[$abstractSku];
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

    /**
     * PriceProductStore needs save() because its ID is required by PriceProductDefault.
     *
     * @param array<string, string> $priceGroup
     */
    protected function upsertPriceProductStore(SpyPriceProduct $priceProductEntity, array $priceGroup): void
    {
        $storeEntity = static::$storeCache[$priceGroup['store']] ?? null;
        $currencyEntity = static::$currencyCache[$priceGroup['currency']] ?? null;

        if ($storeEntity === null || $currencyEntity === null) {
            return;
        }

        $idPriceProduct = $priceProductEntity->getIdPriceProduct();
        $idStore = $storeEntity->getIdStore();
        $idCurrency = $currencyEntity->getIdCurrency();

        $priceProductStoreEntity = SpyPriceProductStoreQuery::create()
            ->filterByFkPriceProduct($idPriceProduct)
            ->filterByFkStore($idStore)
            ->filterByFkCurrency($idCurrency)
            ->findOneOrCreate();

        $priceProductStoreEntity->setGrossPrice($priceGroup['gross'] !== '' ? (int)$priceGroup['gross'] : null);
        $priceProductStoreEntity->setNetPrice($priceGroup['net'] !== '' ? (int)$priceGroup['net'] : null);

        if ($priceProductStoreEntity->isNew() || $priceProductStoreEntity->isModified()) {
            $priceProductStoreEntity->save();
        }

        $this->ensurePriceProductDefault((int)$priceProductStoreEntity->getIdPriceProductStore());
    }

    protected function ensurePriceProductDefault(int $idPriceProductStore): void
    {
        $priceProductDefaultEntity = SpyPriceProductDefaultQuery::create()
            ->filterByFkPriceProductStore($idPriceProductStore)
            ->findOneOrCreate();

        if ($priceProductDefaultEntity->isNew()) {
            $priceProductDefaultEntity->save();
        }
    }

    /**
     * @param array<\Orm\Zed\PriceProduct\Persistence\SpyPriceProduct> $priceProducts
     */
    protected function addPricePublishEvents(array $priceProducts, ImportStepResponseTransfer $response): void
    {
        $abstractIds = [];
        $concreteIds = [];

        foreach ($priceProducts as $priceProduct) {
            if ($priceProduct->getFkProductAbstract() !== null) {
                $abstractIds[] = $priceProduct->getFkProductAbstract();
            }

            if ($priceProduct->getFkProduct() !== null) {
                $concreteIds[] = $priceProduct->getFkProduct();
            }
        }

        foreach (array_unique($abstractIds) as $idProductAbstract) {
            $response->addPublishEvent(
                (new ImportPublishEventTransfer())->setEventName(PriceProductEvents::PRICE_ABSTRACT_PUBLISH)->setEntityId($idProductAbstract),
            );

            $response->addPublishEvent(
                (new ImportPublishEventTransfer())->setEventName(ProductEvents::PRODUCT_ABSTRACT_PUBLISH)->setEntityId($idProductAbstract),
            );
        }

        foreach (array_unique($concreteIds) as $idProduct) {
            $response->addPublishEvent(
                (new ImportPublishEventTransfer())->setEventName(PriceProductEvents::PRICE_CONCRETE_PUBLISH)->setEntityId($idProduct),
            );
        }
    }

    /**
     * @param array<string, string> $row
     *
     * @return array<string, array{type: string, store: string, currency: string, net: string, gross: string}>
     */
    protected function parsePriceGroups(array $row): array
    {
        $priceGroups = [];

        foreach ($row as $header => $value) {
            if (!preg_match(static::PRICE_COLUMN_PATTERN, $header, $matches)) {
                continue;
            }

            $priceType = strtoupper($matches[1]);
            $storeName = strtoupper($matches[2]);
            $currencyCode = strtoupper($matches[3]);
            $amountType = strtolower($matches[4]);

            $groupKey = sprintf('%s-%s-%s', $priceType, $storeName, $currencyCode);

            if (!isset($priceGroups[$groupKey])) {
                $priceGroups[$groupKey] = [
                    'type' => $priceType,
                    'store' => $storeName,
                    'currency' => $currencyCode,
                    'net' => '',
                    'gross' => '',
                ];
            }

            $priceGroups[$groupKey][$amountType] = trim($value);
        }

        /** @var array<string, array{type: string, store: string, currency: string, net: string, gross: string}> $filtered */
        $filtered = array_filter($priceGroups, static fn (array $group): bool => $group['net'] !== '' || $group['gross'] !== '');

        return $filtered;
    }

    protected function resolveStore(string $storeName): void
    {
        if (!array_key_exists($storeName, static::$storeCache)) {
            static::$storeCache[$storeName] = SpyStoreQuery::create()
                ->filterByName($storeName)
                ->findOne();
        }
    }

    protected function resolveCurrency(string $currencyCode): void
    {
        if (!array_key_exists($currencyCode, static::$currencyCache)) {
            static::$currencyCache[$currencyCode] = SpyCurrencyQuery::create()
                ->filterByCode($currencyCode)
                ->findOne();
        }
    }

    /**
     * @param array<string, string> $row
     */
    protected function resolveAbstractSku(array $row): ?string
    {
        $sku = trim($row[static::COLUMN_ABSTRACT_SKU] ?? '');

        return $sku !== '' ? $sku : null;
    }

    /**
     * @param array<string, string> $row
     */
    protected function resolveConcreteSku(array $row): ?string
    {
        $sku = trim($row[static::COLUMN_CONCRETE_SKU] ?? '');

        return $sku !== '' ? $sku : null;
    }
}
