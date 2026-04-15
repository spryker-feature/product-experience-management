<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ExportStep\ProductCsv;

use Orm\Zed\Product\Persistence\Map\SpyProductTableMap;
use Orm\Zed\Stock\Persistence\Map\SpyStockProductTableMap;
use Orm\Zed\Stock\Persistence\Map\SpyStockTableMap;
use Orm\Zed\Stock\Persistence\SpyStockProductQuery;

class ProductCsvStockExportStep extends AbstractProductCsvExportStep
{
    protected const string STOCK_COLUMN_FORMAT = 'Stock (%s)';

    protected const string STOCK_NEVER_OUT_OF_STOCK = 'NOOS';

    protected const string ALIAS_IS_NEVER_OUT_OF_STOCK = 'IsNeverOutOfStock';

    protected const string ALIAS_QUANTITY = 'Quantity';

    protected const string ALIAS_WAREHOUSE_NAME = 'WarehouseName';

    /**
     * {@inheritDoc}
     */
    public function exportRows(array $rows, array $columns): array
    {
        $stockByConcreteSku = $this->buildStockMap($rows);

        foreach ($rows as $index => $row) {
            if (!$this->isConcreteRow($row)) {
                continue;
            }

            $concreteSku = $row[static::COLUMN_CONCRETE_SKU];

            if (!isset($stockByConcreteSku[$concreteSku])) {
                continue;
            }

            foreach ($stockByConcreteSku[$concreteSku] as $warehouseName => $value) {
                $columnName = sprintf(static::STOCK_COLUMN_FORMAT, $warehouseName);

                $row = $this->setColumn($row, $columns, $columnName, $value);
            }

            $rows[$index] = $row;
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, string>> $rows
     *
     * @return array<string, array<string, string>>
     */
    protected function buildStockMap(array $rows): array
    {
        $concreteProductIds = $this->extractConcreteProductIds($rows);

        $stockProducts = SpyStockProductQuery::create()
            ->filterByFkProduct($concreteProductIds, SpyStockProductQuery::IN)
            ->joinWithSpyProduct()
            ->joinWithStock()
            ->withColumn(SpyStockProductTableMap::COL_IS_NEVER_OUT_OF_STOCK, static::ALIAS_IS_NEVER_OUT_OF_STOCK)
            ->withColumn(SpyStockProductTableMap::COL_QUANTITY, static::ALIAS_QUANTITY)
            ->withColumn(SpyProductTableMap::COL_SKU, static::ALIAS_CONCRETE_SKU)
            ->withColumn(SpyStockTableMap::COL_NAME, static::ALIAS_WAREHOUSE_NAME)
            ->select([static::ALIAS_IS_NEVER_OUT_OF_STOCK, static::ALIAS_QUANTITY, static::ALIAS_CONCRETE_SKU, static::ALIAS_WAREHOUSE_NAME])
            ->find()
            ->getData();

        $stockByConcreteSku = [];

        foreach ($stockProducts as $stockProduct) {
            $concreteSku = $stockProduct[static::ALIAS_CONCRETE_SKU];
            $warehouseName = $stockProduct[static::ALIAS_WAREHOUSE_NAME];

            if ((bool)$stockProduct[static::ALIAS_IS_NEVER_OUT_OF_STOCK]) {
                $stockByConcreteSku[$concreteSku][$warehouseName] = static::STOCK_NEVER_OUT_OF_STOCK;

                continue;
            }

            $stockByConcreteSku[$concreteSku][$warehouseName] = (string)(int)($stockProduct[static::ALIAS_QUANTITY] ?? 0);
        }

        return $stockByConcreteSku;
    }
}
