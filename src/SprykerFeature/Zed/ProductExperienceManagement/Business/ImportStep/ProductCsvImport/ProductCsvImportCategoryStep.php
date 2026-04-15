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
use Orm\Zed\ProductCategory\Persistence\SpyProductCategoryQuery;
use Spryker\Zed\Product\Dependency\ProductEvents;
use Spryker\Zed\ProductCategory\Dependency\ProductCategoryEvents;
use Spryker\Zed\Propel\Persistence\BatchProcessor\ActiveRecordBatchProcessorTrait;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportStepInterface;

class ProductCsvImportCategoryStep implements ImportStepInterface
{
    use ActiveRecordBatchProcessorTrait;

    protected const string COLUMN_ABSTRACT_SKU = 'abstract_sku';

    protected const string COLUMN_CONCRETE_SKU = 'concrete_sku';

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
     *
     * @param array<int, array<string, string>> $rows
     * @param array<string, string> $propertyNamesInFile
     */
    public function executeBatch(array $rows, array $propertyNamesInFile = []): ImportStepResponseTransfer
    {
        $response = (new ImportStepResponseTransfer())->setIsSuccessful(true);

        $processedProductAbstractIds = [];

        foreach ($rows as $rowNumber => $row) {
            if ($this->isConcreteRow($row)) {
                continue;
            }

            $categoriesValue = $this->resolveCategories($row);

            if ($categoriesValue === '') {
                continue;
            }

            $abstractSku = $this->resolveAbstractSku($row);
            $idProductAbstract = $this->resolveProductAbstractId($abstractSku);

            if ($idProductAbstract === null) {
                continue;
            }

            $categoryKeys = $this->parseCategoryKeys($categoriesValue);

            foreach ($categoryKeys as $categoryKey) {
                $idCategory = $this->resolveCategoryId($categoryKey);

                if ($idCategory === null) {
                    $response->setIsSuccessful(false);
                    $response->addError(
                        (new ImportStepErrorTransfer())->setCsvRowNumber($rowNumber)->setErrorMessage(sprintf('The value \'%s\' in field \'%s\' is not valid because the category does not exist. Expected: an existing category key. Please update the value.', $categoryKey, $propertyNamesInFile[static::COLUMN_CATEGORIES] ?? static::COLUMN_CATEGORIES)),
                    );

                    continue;
                }

                $this->ensureCategoryRelation($idProductAbstract, $idCategory);
            }

            $processedProductAbstractIds[] = $idProductAbstract;
        }

        $this->commit();
        $this->addPublishEvents($processedProductAbstractIds, $response);

        return $response;
    }

    protected function ensureCategoryRelation(int $idProductAbstract, int $idCategory): void
    {
        $relation = SpyProductCategoryQuery::create()
            ->filterByFkProductAbstract($idProductAbstract)
            ->filterByFkCategory($idCategory)
            ->findOneOrCreate();

        if ($relation->isNew()) {
            $relation->setProductOrder(0);
            $this->persist($relation);
        }
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
    protected function resolveCategories(array $row): string
    {
        return trim($row[static::COLUMN_CATEGORIES] ?? '');
    }
}
