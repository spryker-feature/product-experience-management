<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ExportStep\ProductCsv;

use Orm\Zed\Category\Persistence\Map\SpyCategoryTableMap;
use Orm\Zed\Product\Persistence\Map\SpyProductAbstractTableMap;
use Orm\Zed\ProductCategory\Persistence\SpyProductCategoryQuery;

class ProductCsvCategoryExportStep extends AbstractProductCsvExportStep
{
    protected const string COLUMN_CATEGORIES = 'Categories';

    protected const string ALIAS_CATEGORY_KEY = 'CategoryKey';

    /**
     * {@inheritDoc}
     */
    public function exportRows(array $rows, array $columns): array
    {
        $categoriesByAbstractSku = $this->buildCategoryMap($rows);

        foreach ($rows as $index => $row) {
            if (!$this->isAbstractRow($row)) {
                continue;
            }

            $abstractSku = $row[static::COLUMN_ABSTRACT_SKU];

            if (!isset($categoriesByAbstractSku[$abstractSku])) {
                continue;
            }

            $row = $this->setColumn(
                $row,
                $columns,
                static::COLUMN_CATEGORIES,
                implode(static::SEPARATOR, $categoriesByAbstractSku[$abstractSku]),
            );

            $rows[$index] = $row;
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, string>> $rows
     *
     * @return array<string, array<string>>
     */
    protected function buildCategoryMap(array $rows): array
    {
        $abstractProductIds = $this->extractAbstractProductIds($rows);

        $productCategories = SpyProductCategoryQuery::create()
            ->filterByFkProductAbstract($abstractProductIds, SpyProductCategoryQuery::IN)
            ->joinWithSpyProductAbstract()
            ->joinWithSpyCategory()
            ->withColumn(SpyProductAbstractTableMap::COL_SKU, static::ALIAS_ABSTRACT_SKU)
            ->withColumn(SpyCategoryTableMap::COL_CATEGORY_KEY, static::ALIAS_CATEGORY_KEY)
            ->select([static::ALIAS_ABSTRACT_SKU, static::ALIAS_CATEGORY_KEY])
            ->find()
            ->getData();

        $categoriesByAbstractSku = [];

        foreach ($productCategories as $productCategory) {
            $categoriesByAbstractSku[$productCategory[static::ALIAS_ABSTRACT_SKU]][] = $productCategory[static::ALIAS_CATEGORY_KEY];
        }

        return $categoriesByAbstractSku;
    }
}
