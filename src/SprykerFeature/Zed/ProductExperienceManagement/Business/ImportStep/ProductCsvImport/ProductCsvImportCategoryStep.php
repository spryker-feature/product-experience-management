<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport;

use Generated\Shared\Transfer\ImportPublishEventTransfer;
use Generated\Shared\Transfer\ImportStepErrorTransfer;
use Generated\Shared\Transfer\ImportStepResponseTransfer;
use Orm\Zed\Category\Persistence\SpyCategoryQuery;
use Orm\Zed\Product\Persistence\SpyProductAbstractQuery;
use Orm\Zed\ProductCategory\Persistence\Map\SpyProductCategoryTableMap;
use Orm\Zed\ProductCategory\Persistence\SpyProductCategory;
use Orm\Zed\ProductCategory\Persistence\SpyProductCategoryQuery;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Spryker\Zed\Product\Dependency\ProductEvents;
use Spryker\Zed\ProductCategory\Dependency\ProductCategoryEvents;
use Spryker\Zed\Propel\Persistence\BatchProcessor\ActiveRecordBatchProcessorTrait;

class ProductCsvImportCategoryStep extends AbstractProductCsvImportStep
{
    use ActiveRecordBatchProcessorTrait;
    use TransactionTrait;

    protected const string COLUMN_CATEGORIES = 'categories';

    /**
     * @var non-empty-string
     */
    protected const string CATEGORY_KEY_SEPARATOR = ';';

    /**
     * @var array<string, int|null>
     */
    protected static array $productAbstractIdCache = [];

    /**
     * @var array<string, int|null>
     */
    protected static array $categoryIdCache = [];

    /**
     * {@inheritDoc}
     */
    public function resetCache(): void
    {
        static::$productAbstractIdCache = [];
        static::$categoryIdCache = [];
    }

    /**
     * {@inheritDoc}
     *
     * @param array<int, array<string, string>> $rows
     * @param array<string, string> $propertyNamesInFile
     */
    public function executeBatch(array $rows, array $propertyNamesInFile = []): ImportStepResponseTransfer
    {
        $response = (new ImportStepResponseTransfer())->setIsSuccessful(true);

        $requestedCategoryIdsGroupedByIdProductAbstract = $this->collectRequestedCategoryIds($rows, $propertyNamesInFile, $response);
        $existingCategoryIdsGroupedByIdProductAbstract = $this->getExistingCategoryIdsGroupedByIdProductAbstract(array_keys($requestedCategoryIdsGroupedByIdProductAbstract));

        $categoryIdsToAddGroupedByIdProductAbstract = $this->diffCategoryIds($requestedCategoryIdsGroupedByIdProductAbstract, $existingCategoryIdsGroupedByIdProductAbstract);
        $categoryIdsToRemoveGroupedByIdProductAbstract = $this->diffCategoryIds($existingCategoryIdsGroupedByIdProductAbstract, $requestedCategoryIdsGroupedByIdProductAbstract);

        $this->getTransactionHandler()->handleTransaction(function () use ($categoryIdsToAddGroupedByIdProductAbstract, $categoryIdsToRemoveGroupedByIdProductAbstract): void {
            $this->removeProductCategories($categoryIdsToRemoveGroupedByIdProductAbstract);
            $this->persistNewProductCategories($categoryIdsToAddGroupedByIdProductAbstract);
            $this->commit();
        });

        $this->addPublishEvents(array_keys($categoryIdsToAddGroupedByIdProductAbstract + $categoryIdsToRemoveGroupedByIdProductAbstract), $response);

        return $response;
    }

    /**
     * @param array<int> $productAbstractIds
     *
     * @return array<int, array<int>>
     */
    protected function getExistingCategoryIdsGroupedByIdProductAbstract(array $productAbstractIds): array
    {
        if ($productAbstractIds === []) {
            return [];
        }

        $productCategoryRows = SpyProductCategoryQuery::create()
            ->filterByFkProductAbstract_In($productAbstractIds)
            ->select([SpyProductCategoryTableMap::COL_FK_PRODUCT_ABSTRACT, SpyProductCategoryTableMap::COL_FK_CATEGORY])
            ->find();

        $existingCategoryIdsGroupedByIdProductAbstract = [];

        foreach ($productCategoryRows as $productCategoryRow) {
            $idProductAbstract = (int)$productCategoryRow[SpyProductCategoryTableMap::COL_FK_PRODUCT_ABSTRACT];
            $existingCategoryIdsGroupedByIdProductAbstract[$idProductAbstract][] = (int)$productCategoryRow[SpyProductCategoryTableMap::COL_FK_CATEGORY];
        }

        return $existingCategoryIdsGroupedByIdProductAbstract;
    }

    /**
     * @param array<int, array<int>> $categoryIdsToRemoveGroupedByIdProductAbstract
     */
    protected function removeProductCategories(array $categoryIdsToRemoveGroupedByIdProductAbstract): void
    {
        if ($categoryIdsToRemoveGroupedByIdProductAbstract === []) {
            return;
        }

        $productCategoryEntities = SpyProductCategoryQuery::create()
            ->filterByFkProductAbstract_In(array_keys($categoryIdsToRemoveGroupedByIdProductAbstract))
            ->find();

        foreach ($productCategoryEntities as $productCategoryEntity) {
            $categoryIdsToRemove = $categoryIdsToRemoveGroupedByIdProductAbstract[$productCategoryEntity->getFkProductAbstract()];

            if (in_array($productCategoryEntity->getFkCategory(), $categoryIdsToRemove, true)) {
                $this->remove($productCategoryEntity);
            }
        }
    }

    /**
     * @param array<int, array<int>> $categoryIdsToAddGroupedByIdProductAbstract
     */
    protected function persistNewProductCategories(array $categoryIdsToAddGroupedByIdProductAbstract): void
    {
        foreach ($categoryIdsToAddGroupedByIdProductAbstract as $idProductAbstract => $categoryIds) {
            foreach ($categoryIds as $idCategory) {
                $this->persist(
                    (new SpyProductCategory())
                        ->setFkProductAbstract($idProductAbstract)
                        ->setFkCategory($idCategory)
                        ->setProductOrder(0),
                );
            }
        }
    }

    /**
     * @param array<int, array<int>> $categoryIdsGroupedByIdProductAbstract
     * @param array<int, array<int>> $subtractedCategoryIdsGroupedByIdProductAbstract
     *
     * @return array<int, array<int>>
     */
    protected function diffCategoryIds(array $categoryIdsGroupedByIdProductAbstract, array $subtractedCategoryIdsGroupedByIdProductAbstract): array
    {
        $categoryIdsDifferenceGroupedByIdProductAbstract = [];

        foreach ($categoryIdsGroupedByIdProductAbstract as $idProductAbstract => $categoryIds) {
            $categoryIdsDifference = array_diff($categoryIds, $subtractedCategoryIdsGroupedByIdProductAbstract[$idProductAbstract] ?? []);

            if ($categoryIdsDifference !== []) {
                $categoryIdsDifferenceGroupedByIdProductAbstract[$idProductAbstract] = array_values($categoryIdsDifference);
            }
        }

        return $categoryIdsDifferenceGroupedByIdProductAbstract;
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @param array<string, string> $propertyNamesInFile
     *
     * @return array<int, array<int>>
     */
    protected function collectRequestedCategoryIds(array $rows, array $propertyNamesInFile, ImportStepResponseTransfer $response): array
    {
        $requestedCategoryIdsGroupedByIdProductAbstract = [];

        foreach ($rows as $rowNumber => $row) {
            if ($this->isConcreteRow($row) || !$this->hasCategoriesColumn($row)) {
                continue;
            }

            $idProductAbstract = $this->resolveProductAbstractId($this->resolveAbstractSku($row));

            if ($idProductAbstract === null) {
                continue;
            }

            $categoryIds = $this->resolveCategoryIds($row, $rowNumber, $propertyNamesInFile, $response);

            if ($categoryIds === null) {
                continue;
            }

            $requestedCategoryIdsGroupedByIdProductAbstract[$idProductAbstract] = $categoryIds;
        }

        return $requestedCategoryIdsGroupedByIdProductAbstract;
    }

    /**
     * @param array<string, string> $row
     * @param array<string, string> $propertyNamesInFile
     *
     * @return array<int>|null
     */
    protected function resolveCategoryIds(array $row, int $rowNumber, array $propertyNamesInFile, ImportStepResponseTransfer $response): ?array
    {
        $categoryIds = [];
        $hasUnknownCategory = false;

        foreach ($this->parseCategoryKeys($this->resolveCategories($row)) as $categoryKey) {
            $idCategory = $this->resolveCategoryId($categoryKey);

            if ($idCategory === null) {
                $hasUnknownCategory = true;
                $response->setIsSuccessful(false);
                $response->addError(
                    (new ImportStepErrorTransfer())->setCsvRowNumber($rowNumber)->setErrorMessage(sprintf('The value \'%s\' in field \'%s\' is not valid because the category does not exist. Expected: an existing category key. Please update the value.', $categoryKey, $propertyNamesInFile[static::COLUMN_CATEGORIES] ?? static::COLUMN_CATEGORIES)),
                );

                continue;
            }

            $categoryIds[] = $idCategory;
        }

        return $hasUnknownCategory ? null : array_values(array_unique($categoryIds));
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

    protected function resolveCategoryId(string $categoryKey): ?int
    {
        if (!array_key_exists($categoryKey, static::$categoryIdCache)) {
            static::$categoryIdCache[$categoryKey] = SpyCategoryQuery::create()
                ->filterByCategoryKey($categoryKey)
                ->findOne()
                ?->getIdCategory();
        }

        return static::$categoryIdCache[$categoryKey];
    }

    /**
     * @return array<string>
     */
    protected function parseCategoryKeys(string $categoriesValue): array
    {
        $keys = array_map('trim', explode(static::CATEGORY_KEY_SEPARATOR, $categoriesValue));

        return array_filter($keys, static fn (string $key): bool => $key !== '');
    }

    /**
     * @param array<int> $productAbstractIds
     */
    protected function addPublishEvents(array $productAbstractIds, ImportStepResponseTransfer $response): void
    {
        foreach (array_unique($productAbstractIds) as $idProductAbstract) {
            $response->addPublishEvent(
                (new ImportPublishEventTransfer())->setEventName(ProductEvents::PRODUCT_ABSTRACT_PUBLISH)->setEntityId($idProductAbstract),
            );

            $response->addPublishEvent(
                (new ImportPublishEventTransfer())->setEventName(ProductCategoryEvents::PRODUCT_CATEGORY_PUBLISH)->setEntityId($idProductAbstract),
            );
        }
    }

    /**
     * @param array<string, string> $row
     */
    protected function hasCategoriesColumn(array $row): bool
    {
        return array_key_exists(static::COLUMN_CATEGORIES, $row);
    }

    /**
     * @param array<string, string> $row
     */
    protected function resolveCategories(array $row): string
    {
        return trim($row[static::COLUMN_CATEGORIES] ?? '');
    }
}
