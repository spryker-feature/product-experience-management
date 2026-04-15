<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ExportStep\ProductCsv;

use Orm\Zed\Product\Persistence\Map\SpyProductAbstractTableMap;
use Orm\Zed\Product\Persistence\Map\SpyProductTableMap;
use Orm\Zed\Product\Persistence\SpyProductAbstractQuery;
use Orm\Zed\Product\Persistence\SpyProductQuery;
use Orm\Zed\Tax\Persistence\Map\SpyTaxSetTableMap;

class ProductCsvCoreExportStep extends AbstractProductCsvExportStep
{
    protected const string COLUMN_PRODUCT_STATUS = 'Product Status';

    protected const string COLUMN_TAX_SET_NAME = 'Tax Set Name';

    protected const string APPROVAL_STATUS_APPROVED = 'approved';

    protected const string CONCRETE_STATUS_ACTIVE = 'active';

    protected const string STATUS_INACTIVE = 'Inactive';

    protected const string ALIAS_SKU = 'Sku';

    protected const string ALIAS_APPROVAL_STATUS = 'ApprovalStatus';

    protected const string ALIAS_TAX_SET_NAME = 'TaxSetName';

    protected const string ALIAS_ID_PRODUCT = 'IdProduct';

    protected const string ALIAS_FK_PRODUCT_ABSTRACT = 'FkProductAbstract';

    protected const string ALIAS_IS_ACTIVE = 'IsActive';

    /**
     * {@inheritDoc}
     */
    public function exportRows(array $rows, array $columns): array
    {
        $rows = $this->enrichAbstractRows($rows, $columns);
        $rows = $this->buildConcreteRows($rows, $columns);

        return $rows;
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @param array<string> $columns
     *
     * @return array<int, array<string, string>>
     */
    protected function enrichAbstractRows(array $rows, array $columns): array
    {
        $abstractIdToSkuMap = $this->extractAbstractIdToSkuMap($rows);

        $abstractProducts = SpyProductAbstractQuery::create()
            ->filterByIdProductAbstract(array_keys($abstractIdToSkuMap), SpyProductAbstractQuery::IN)
            ->leftJoinWithSpyTaxSet()
            ->withColumn(SpyProductAbstractTableMap::COL_SKU, static::ALIAS_SKU)
            ->withColumn(SpyProductAbstractTableMap::COL_APPROVAL_STATUS, static::ALIAS_APPROVAL_STATUS)
            ->withColumn(SpyTaxSetTableMap::COL_NAME, static::ALIAS_TAX_SET_NAME)
            ->select([static::ALIAS_SKU, static::ALIAS_APPROVAL_STATUS, static::ALIAS_TAX_SET_NAME])
            ->find()
            ->getData();

        $abstractDataBySku = [];

        foreach ($abstractProducts as $abstractProduct) {
            $abstractDataBySku[$abstractProduct[static::ALIAS_SKU]] = $abstractProduct;
        }

        foreach ($rows as $index => $row) {
            if (!$this->isAbstractRow($row)) {
                continue;
            }

            $abstractSku = $row[static::COLUMN_ABSTRACT_SKU];

            if (!isset($abstractDataBySku[$abstractSku])) {
                continue;
            }

            $abstractProduct = $abstractDataBySku[$abstractSku];
            $row = $this->setColumn($row, $columns, static::COLUMN_PRODUCT_STATUS, $abstractProduct[static::ALIAS_APPROVAL_STATUS]);

            if ($abstractProduct[static::ALIAS_TAX_SET_NAME] !== null) {
                $row = $this->setColumn($row, $columns, static::COLUMN_TAX_SET_NAME, $abstractProduct[static::ALIAS_TAX_SET_NAME]);
            }

            $rows[$index] = $row;
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @param array<string> $columns
     *
     * @return array<int, array<string, string>>
     */
    protected function buildConcreteRows(array $rows, array $columns): array
    {
        $abstractIdToSkuMap = $this->extractAbstractIdToSkuMap($rows);

        $concreteProducts = SpyProductQuery::create()
            ->filterByFkProductAbstract(array_keys($abstractIdToSkuMap), SpyProductQuery::IN)
            ->withColumn(SpyProductTableMap::COL_ID_PRODUCT, static::ALIAS_ID_PRODUCT)
            ->withColumn(SpyProductTableMap::COL_FK_PRODUCT_ABSTRACT, static::ALIAS_FK_PRODUCT_ABSTRACT)
            ->withColumn(SpyProductTableMap::COL_SKU, static::ALIAS_SKU)
            ->withColumn(SpyProductTableMap::COL_IS_ACTIVE, static::ALIAS_IS_ACTIVE)
            ->select([static::ALIAS_ID_PRODUCT, static::ALIAS_FK_PRODUCT_ABSTRACT, static::ALIAS_SKU, static::ALIAS_IS_ACTIVE])
            ->find()
            ->getData();

        $emptyRow = array_fill_keys($columns, '');

        foreach ($concreteProducts as $concreteProduct) {
            $idProductAbstract = $concreteProduct[static::ALIAS_FK_PRODUCT_ABSTRACT];
            $abstractSku = $abstractIdToSkuMap[$idProductAbstract] ?? null;

            if ($abstractSku === null) {
                continue;
            }

            $row = $emptyRow;
            $row[static::INTERNAL_COLUMN_ID_PRODUCT_ABSTRACT] = (string)$idProductAbstract;
            $row[static::INTERNAL_COLUMN_ID_PRODUCT] = (string)$concreteProduct[static::ALIAS_ID_PRODUCT];
            $row = $this->setColumn($row, $columns, static::COLUMN_ABSTRACT_SKU, $abstractSku);
            $row = $this->setColumn($row, $columns, static::COLUMN_CONCRETE_SKU, $concreteProduct[static::ALIAS_SKU]);

            $status = (bool)$concreteProduct[static::ALIAS_IS_ACTIVE] ? static::CONCRETE_STATUS_ACTIVE : static::STATUS_INACTIVE;
            $row = $this->setColumn($row, $columns, static::COLUMN_PRODUCT_STATUS, $status);

            $rows[] = $row;
        }

        return $rows;
    }
}
