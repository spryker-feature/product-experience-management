<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\Export\DataProvider;

use Orm\Zed\Product\Persistence\Map\SpyProductAbstractTableMap;
use Orm\Zed\Product\Persistence\SpyProductAbstractQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ExportDataProviderInterface;

class ProductCsvExportDataProvider implements ExportDataProviderInterface
{
    protected const string COLUMN_ABSTRACT_SKU = 'Abstract SKU';

    protected const string INTERNAL_COLUMN_ID_PRODUCT_ABSTRACT = '_id_product_abstract';

    protected const string ALIAS_SKU = 'AbstractSku';

    /**
     * @param array<string> $columns
     *
     * @return array<int, array<string, string>>
     */
    public function getBatch(array $columns, int $limit, int $lastId = 0): array
    {
        $abstractProducts = SpyProductAbstractQuery::create()
            ->withColumn(SpyProductAbstractTableMap::COL_ID_PRODUCT_ABSTRACT, static::INTERNAL_COLUMN_ID_PRODUCT_ABSTRACT)
            ->withColumn(SpyProductAbstractTableMap::COL_SKU, static::ALIAS_SKU)
            ->select([static::INTERNAL_COLUMN_ID_PRODUCT_ABSTRACT, static::ALIAS_SKU])
            ->filterByIdProductAbstract($lastId, Criteria::GREATER_THAN)
            ->orderByIdProductAbstract()
            ->limit($limit)
            ->find()
            ->getData();

        if ($abstractProducts === []) {
            return [];
        }

        $emptyRow = array_fill_keys($columns, '');
        $seedRows = [];

        foreach ($abstractProducts as $abstractProduct) {
            $row = $emptyRow;
            $row[static::COLUMN_ABSTRACT_SKU] = $abstractProduct[static::ALIAS_SKU];
            $row[static::INTERNAL_COLUMN_ID_PRODUCT_ABSTRACT] = (string)$abstractProduct[static::INTERNAL_COLUMN_ID_PRODUCT_ABSTRACT];
            $row[static::INTERNAL_COLUMN_CURSOR_ID] = (string)$abstractProduct[static::INTERNAL_COLUMN_ID_PRODUCT_ABSTRACT];
            $seedRows[] = $row;
        }

        return $seedRows;
    }
}
