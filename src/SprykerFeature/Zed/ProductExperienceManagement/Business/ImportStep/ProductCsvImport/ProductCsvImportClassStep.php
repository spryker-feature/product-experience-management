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
use Orm\Zed\SelfServicePortal\Persistence\SpyProductClassQuery;
use Orm\Zed\SelfServicePortal\Persistence\SpyProductToProductClassQuery;
use Spryker\Zed\Product\Dependency\ProductEvents;
use Spryker\Zed\Propel\Persistence\BatchProcessor\ActiveRecordBatchProcessorTrait;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportStepInterface;

class ProductCsvImportClassStep implements ImportStepInterface
{
    use ActiveRecordBatchProcessorTrait;

    protected const string COLUMN_CONCRETE_SKU = 'concrete_sku';

    protected const string COLUMN_PRODUCT_CLASSES = 'product_classes';

    /**
     * @var non-empty-string
     */
    protected const string CLASS_KEY_SEPARATOR = ';';

    /**
     * @var array<string, int|null>
     */
    protected static array $productConcreteIdCache = [];

    /**
     * @var array<string, \Orm\Zed\SelfServicePortal\Persistence\SpyProductClass|null>
     */
    protected static array $productClassEntityCache = [];

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

        $processedProductIds = [];

        foreach ($rows as $rowNumber => $row) {
            $validationResult = $this->validateRow($row, $rowNumber, $propertyNamesInFile);

            if ($validationResult->getErrors()->count() > 0) {
                $response->setIsSuccessful(false);

                foreach ($validationResult->getErrors() as $error) {
                    $response->addError($error);
                }

                continue;
            }

            $processedProductIds[] = $this->assignProductClasses($row);
        }

        $this->commit();
        $this->addPublishEvents($processedProductIds, $response);

        return $response;
    }

    /**
     * @param array<string, string> $row
     * @param array<string, string> $propertyNamesInFile
     */
    protected function validateRow(array $row, int $rowNumber, array $propertyNamesInFile = []): ImportRowValidationCollectionTransfer
    {
        $result = new ImportRowValidationCollectionTransfer();
        $productClassesValue = $this->resolveProductClasses($row);

        if ($productClassesValue === '') {
            return $result;
        }

        $classKeys = $this->parseClassKeys($productClassesValue);

        foreach ($classKeys as $classKey) {
            $this->resolveProductClassEntity($classKey);

            if (static::$productClassEntityCache[$classKey] === null) {
                $result->addError((new ImportStepErrorTransfer())
                    ->setCsvRowNumber($rowNumber)
                    ->setErrorMessage(sprintf('The value \'%s\' in field \'%s\' is not valid because the product class does not exist. Expected: an existing product class key. Please update the value.', $classKey, $propertyNamesInFile[static::COLUMN_PRODUCT_CLASSES] ?? static::COLUMN_PRODUCT_CLASSES)));
            }
        }

        return $result;
    }

    /**
     * @param array<string, string> $row
     */
    protected function assignProductClasses(array $row): ?int
    {
        $productClassesValue = $this->resolveProductClasses($row);

        if ($productClassesValue === '') {
            return null;
        }

        $concreteSku = $this->resolveConcreteSku($row);

        if ($concreteSku === '') {
            return null;
        }

        $idProduct = $this->resolveProductConcreteId($concreteSku);

        if ($idProduct === null) {
            return null;
        }

        $classKeys = $this->parseClassKeys($productClassesValue);

        foreach ($classKeys as $classKey) {
            $productClassEntity = static::$productClassEntityCache[$classKey] ?? null;

            if ($productClassEntity === null) {
                continue;
            }

            $idProductClass = $productClassEntity->getIdProductClass();

            $relation = SpyProductToProductClassQuery::create()
                ->filterByFkProduct($idProduct)
                ->filterByFkProductClass($idProductClass)
                ->findOneOrCreate();

            if ($relation->isNew()) {
                $this->persist($relation);
            }
        }

        return $idProduct;
    }

    /**
     * @return array<string>
     */
    protected function parseClassKeys(string $productClassesValue): array
    {
        $classKeys = array_map('trim', explode(static::CLASS_KEY_SEPARATOR, $productClassesValue));

        return array_filter($classKeys, static fn (string $key): bool => $key !== '');
    }

    protected function resolveProductClassEntity(string $classKey): void
    {
        if (array_key_exists($classKey, static::$productClassEntityCache)) {
            return;
        }

        static::$productClassEntityCache[$classKey] = SpyProductClassQuery::create()
            ->filterByKey($classKey)
            ->findOne();
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
     * @param array<int|null> $productIds
     */
    protected function addPublishEvents(array $productIds, ImportStepResponseTransfer $response): void
    {
        foreach (array_unique(array_filter($productIds)) as $idProduct) {
            $response->addPublishEvent(
                (new ImportPublishEventTransfer())->setEventName(ProductEvents::PRODUCT_CONCRETE_PUBLISH)->setEntityId($idProduct),
            );
        }
    }

    /**
     * @param array<string, string> $row
     */
    protected function resolveProductClasses(array $row): string
    {
        return trim($row[static::COLUMN_PRODUCT_CLASSES] ?? '');
    }

    /**
     * @param array<string, string> $row
     */
    protected function resolveConcreteSku(array $row): string
    {
        return trim($row[static::COLUMN_CONCRETE_SKU] ?? '');
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
        if (class_exists(SpyProductClassQuery::class) && class_exists(SpyProductToProductClassQuery::class)) {
            return true;
        }

        return false;
    }
}
