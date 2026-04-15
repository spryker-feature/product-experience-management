<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport;

use Generated\Shared\Transfer\ImportRowValidationCollectionTransfer;
use Generated\Shared\Transfer\ImportStepErrorTransfer;
use Generated\Shared\Transfer\ImportStepResponseTransfer;
use Orm\Zed\Product\Persistence\SpyProductAbstractQuery;
use Orm\Zed\Product\Persistence\SpyProductAbstractStoreQuery;
use Orm\Zed\Store\Persistence\SpyStore;
use Orm\Zed\Store\Persistence\SpyStoreQuery;
use Spryker\Zed\Propel\Persistence\BatchProcessor\ActiveRecordBatchProcessorTrait;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportStepInterface;

class ProductCsvImportAbstractStoreStep implements ImportStepInterface
{
    use ActiveRecordBatchProcessorTrait;

    protected const string COLUMN_ABSTRACT_SKU = 'abstract_sku';

    protected const string COLUMN_CONCRETE_SKU = 'concrete_sku';

    protected const string COLUMN_STORES = 'stores';

    /**
     * @var non-empty-string
     */
    protected const string STORE_SEPARATOR = ';';

    /**
     * @var array<string, int|null>
     */
    protected static array $productAbstractIdCache = [];

    /**
     * @var array<string, \Orm\Zed\Store\Persistence\SpyStore|null>
     */
    protected static array $storeCache = [];

    /**
     * {@inheritDoc}
     *
     * @param array<int, array<string, string>> $rows
     * @param array<string, string> $propertyNamesInFile
     */
    public function executeBatch(array $rows, array $propertyNamesInFile = []): ImportStepResponseTransfer
    {
        $response = (new ImportStepResponseTransfer())->setIsSuccessful(true);

        foreach ($rows as $rowNumber => $row) {
            if ($this->isConcreteRow($row)) {
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

            $abstractSku = $this->resolveAbstractSku($row);
            $idProductAbstract = $this->resolveProductAbstractId($abstractSku);

            if ($idProductAbstract === null) {
                continue;
            }

            $this->upsertStoreRelations($idProductAbstract, $row);
        }

        $this->commit();

        return $response;
    }

    /**
     * @param array<string, string> $row
     * @param array<string, string> $propertyNamesInFile
     */
    protected function validateRow(array $row, int $rowNumber, array $propertyNamesInFile = []): ImportRowValidationCollectionTransfer
    {
        $result = new ImportRowValidationCollectionTransfer();

        if ($this->resolveStore($row) === null) {
            $result->addError((new ImportStepErrorTransfer())
                ->setCsvRowNumber($rowNumber)
                ->setErrorMessage(sprintf('The value \'\' in field \'%s\' is not valid because the field is empty. Expected: a comma-separated list of store names. Please update the value.', $propertyNamesInFile[static::COLUMN_STORES] ?? static::COLUMN_STORES)));
        }

        return $result;
    }

    /**
     * @param array<string, string> $row
     */
    protected function isConcreteRow(array $row): bool
    {
        return trim($row[static::COLUMN_CONCRETE_SKU] ?? '') !== '';
    }

    /**
     * @param array<string, string> $row
     */
    protected function resolveAbstractSku(array $row): string
    {
        return trim($row[static::COLUMN_ABSTRACT_SKU] ?? '');
    }

    /**
     * @param array<string, string> $row
     */
    protected function resolveStore(array $row): ?string
    {
        $store = trim($row[static::COLUMN_STORES] ?? '');

        return $store !== '' ? $store : null;
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

    /**
     * @param array<string, string> $row
     */
    protected function upsertStoreRelations(int $idProductAbstract, array $row): void
    {
        foreach (explode(static::STORE_SEPARATOR, (string)$this->resolveStore($row)) as $storeName) {
            $storeName = trim($storeName);

            if ($storeName === '') {
                continue;
            }

            $storeEntity = $this->resolveStoreEntity($storeName);

            if ($storeEntity === null) {
                continue;
            }

            $idStore = $storeEntity->getIdStore();

            $this->ensureStoreRelation($idProductAbstract, $idStore);
        }
    }

    protected function resolveStoreEntity(string $storeName): ?SpyStore
    {
        if (!array_key_exists($storeName, static::$storeCache)) {
            static::$storeCache[$storeName] = SpyStoreQuery::create()
                ->filterByName($storeName)
                ->findOne();
        }

        return static::$storeCache[$storeName];
    }

    protected function ensureStoreRelation(int $idProductAbstract, int $idStore): void
    {
        $storeRelation = SpyProductAbstractStoreQuery::create()
            ->filterByFkProductAbstract($idProductAbstract)
            ->filterByFkStore($idStore)
            ->findOneOrCreate();

        if ($storeRelation->isNew()) {
            $this->persist($storeRelation);
        }
    }
}
