<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ExportStep\ProductCsv;

use Orm\Zed\Product\Persistence\Map\SpyProductAbstractTableMap;
use Orm\Zed\Product\Persistence\SpyProductAbstractStoreQuery;
use Orm\Zed\Store\Persistence\Map\SpyStoreTableMap;

class ProductCsvStoreExportStep extends AbstractProductCsvExportStep
{
    protected const string COLUMN_STORES = 'Stores';

    protected const string ALIAS_STORE_NAME = 'StoreName';

    /**
     * {@inheritDoc}
     */
    public function exportRows(array $rows, array $columns): array
    {
        $storesByAbstractSku = $this->buildStoreMap($rows);

        foreach ($rows as $index => $row) {
            if (!$this->isAbstractRow($row)) {
                continue;
            }

            $abstractSku = $row[static::COLUMN_ABSTRACT_SKU];

            if (!isset($storesByAbstractSku[$abstractSku])) {
                continue;
            }

            $row = $this->setColumn(
                $row,
                $columns,
                static::COLUMN_STORES,
                implode(static::SEPARATOR, $storesByAbstractSku[$abstractSku]),
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
    protected function buildStoreMap(array $rows): array
    {
        $abstractProductIds = $this->extractAbstractProductIds($rows);

        $productAbstractStores = SpyProductAbstractStoreQuery::create()
            ->filterByFkProductAbstract($abstractProductIds, SpyProductAbstractStoreQuery::IN)
            ->joinWithSpyProductAbstract()
            ->joinWithSpyStore()
            ->withColumn(SpyProductAbstractTableMap::COL_SKU, static::ALIAS_ABSTRACT_SKU)
            ->withColumn(SpyStoreTableMap::COL_NAME, static::ALIAS_STORE_NAME)
            ->select([static::ALIAS_ABSTRACT_SKU, static::ALIAS_STORE_NAME])
            ->find()
            ->getData();

        $storesByAbstractSku = [];

        foreach ($productAbstractStores as $productAbstractStore) {
            $storesByAbstractSku[$productAbstractStore[static::ALIAS_ABSTRACT_SKU]][] = $productAbstractStore[static::ALIAS_STORE_NAME];
        }

        return $storesByAbstractSku;
    }
}
