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
use Orm\Zed\Product\Persistence\SpyProductQuery;
use Orm\Zed\SelfServicePortal\Persistence\SpyProductShipmentTypeQuery;
use Orm\Zed\ShipmentType\Persistence\SpyShipmentTypeQuery;
use Spryker\Zed\Product\Dependency\ProductEvents;
use Spryker\Zed\Propel\Persistence\BatchProcessor\ActiveRecordBatchProcessorTrait;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportStepInterface;

class ProductCsvImportShipmentTypeStep extends AbstractProductCsvImportStep implements ImportStepInterface
{
    use ActiveRecordBatchProcessorTrait;

    protected const string COLUMN_SHIPMENT_TYPES = 'shipment_types';

    /**
     * @var non-empty-string
     */
    protected const string SHIPMENT_TYPE_KEY_SEPARATOR = ';';

    /**
     * @var array<string, int|null>
     */
    protected static array $productConcreteIdCache = [];

    /**
     * @var array<string, \Orm\Zed\ShipmentType\Persistence\SpyShipmentType|null>
     */
    protected static array $shipmentTypeCache = [];

    protected static bool $shipmentTypeCacheWarmed = false;

    /**
     * {@inheritDoc}
     *
     * @param array<int, array<string, string>> $rows
     * @param array<string, string> $propertyNamesInFile
     */
    public function executeBatch(array $rows, array $propertyNamesInFile = []): ImportStepResponseTransfer
    {
        $response = (new ImportStepResponseTransfer())->setIsSuccessful(true);

        if (!$this->canImportSelfServiceContext()) {
            return $response;
        }

        $this->warmUpShipmentTypeCache();

        $processedProductAbstractIds = [];

        foreach ($rows as $rowNumber => $row) {
            $concreteSku = $this->resolveConcreteSku($row);

            if ($concreteSku === '') {
                continue;
            }

            $shipmentTypesValue = $this->resolveShipmentTypes($row);

            if ($shipmentTypesValue === '') {
                continue;
            }

            $validationResult = $this->validateRow($row, $rowNumber, $propertyNamesInFile);

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

            $shipmentTypeKeys = $this->parseShipmentTypeKeys($shipmentTypesValue);

            foreach ($shipmentTypeKeys as $shipmentTypeKey) {
                $shipmentTypeEntity = static::$shipmentTypeCache[$shipmentTypeKey] ?? null;

                if ($shipmentTypeEntity === null) {
                    continue;
                }

                $idShipmentType = $shipmentTypeEntity->getIdShipmentType();

                $entity = SpyProductShipmentTypeQuery::create()
                    ->filterByFkProduct($idProduct)
                    ->filterByFkShipmentType($idShipmentType)
                    ->findOneOrCreate();

                if ($entity->isNew()) {
                    $this->persist($entity);
                }
            }

            $processedProductAbstractIds[] = $this->resolveProductAbstractId($row);
        }

        $this->commit();
        $this->addPublishEvents($processedProductAbstractIds, $response);

        return $response;
    }

    /**
     * @param array<string, string> $row
     * @param array<string, string> $propertyNamesInFile
     */
    protected function validateRow(array $row, int $rowNumber, array $propertyNamesInFile): ImportRowValidationCollectionTransfer
    {
        $result = new ImportRowValidationCollectionTransfer();

        $concreteSku = $this->resolveConcreteSku($row);
        $idProduct = $this->resolveProductConcreteId($concreteSku);

        if ($idProduct === null) {
            $result->addError((new ImportStepErrorTransfer())
                ->setCsvRowNumber($rowNumber)
                ->setErrorMessage(sprintf('The value \'%s\' in field \'%s\' is not valid because the concrete product does not exist.', $concreteSku, $propertyNamesInFile[static::COLUMN_CONCRETE_SKU] ?? static::COLUMN_CONCRETE_SKU)));
        }

        $shipmentTypeKeys = $this->parseShipmentTypeKeys($this->resolveShipmentTypes($row));

        foreach ($shipmentTypeKeys as $shipmentTypeKey) {
            if (!isset(static::$shipmentTypeCache[$shipmentTypeKey])) {
                $result->addError((new ImportStepErrorTransfer())
                    ->setCsvRowNumber($rowNumber)
                    ->setErrorMessage(sprintf('The value \'%s\' in field \'%s\' is not valid because the shipment type does not exist. Expected: an existing shipment type key.', $shipmentTypeKey, $propertyNamesInFile[static::COLUMN_SHIPMENT_TYPES] ?? static::COLUMN_SHIPMENT_TYPES)));
            }
        }

        return $result;
    }

    protected function warmUpShipmentTypeCache(): void
    {
        if (static::$shipmentTypeCacheWarmed) {
            return;
        }

        $entities = SpyShipmentTypeQuery::create()->find();

        foreach ($entities as $entity) {
            $shipmentTypeKey = $entity->getKey();

            if ($shipmentTypeKey === null) {
                continue;
            }

            static::$shipmentTypeCache[$shipmentTypeKey] = $entity;
        }

        static::$shipmentTypeCacheWarmed = true;
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
     * @param array<string, string> $row
     */
    protected function resolveProductAbstractId(array $row): ?int
    {
        $concreteSku = $this->resolveConcreteSku($row);
        $idProduct = static::$productConcreteIdCache[$concreteSku] ?? null;

        if ($idProduct === null) {
            return null;
        }

        $entity = SpyProductQuery::create()
            ->filterByIdProduct($idProduct)
            ->findOne();

        return $entity?->getFkProductAbstract();
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
     * @return array<string>
     */
    protected function parseShipmentTypeKeys(string $shipmentTypesValue): array
    {
        $keys = array_map('trim', explode(static::SHIPMENT_TYPE_KEY_SEPARATOR, $shipmentTypesValue));

        return array_filter($keys, static fn (string $key): bool => $key !== '');
    }

    /**
     * @param array<string, string> $row
     */
    protected function resolveShipmentTypes(array $row): string
    {
        return trim($row[static::COLUMN_SHIPMENT_TYPES] ?? '');
    }

    /**
     * Checks whether the product class Propel entities are available at runtime.
     * This allows the step to work out of the box when SelfServicePortal is installed,
     * without introducing a hard composer dependency on it. Will be refactored in future
     *
     * @return bool
     */
    protected function canImportSelfServiceContext(): bool
    {
        if (class_exists(SpyProductShipmentTypeQuery::class)) {
            return true;
        }

        return false;
    }
}
