<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ExportStep\ProductCsv;

use Orm\Zed\Merchant\Persistence\Map\SpyMerchantTableMap;
use Orm\Zed\MerchantProduct\Persistence\SpyMerchantProductAbstractQuery;
use Orm\Zed\Product\Persistence\Map\SpyProductAbstractTableMap;

class ProductCsvMerchantExportStep extends AbstractProductCsvExportStep
{
    protected const string COLUMN_MERCHANT = 'Merchant';

    protected const string ALIAS_MERCHANT_REFERENCE = 'MerchantReference';

    /**
     * {@inheritDoc}
     */
    public function exportRows(array $rows, array $columns): array
    {
        $merchantByAbstractSku = $this->buildMerchantMap($rows);

        foreach ($rows as $index => $row) {
            if (!$this->isAbstractRow($row)) {
                continue;
            }

            $abstractSku = $row[static::COLUMN_ABSTRACT_SKU];

            if (!isset($merchantByAbstractSku[$abstractSku])) {
                continue;
            }

            $row = $this->setColumn(
                $row,
                $columns,
                static::COLUMN_MERCHANT,
                $merchantByAbstractSku[$abstractSku],
            );

            $rows[$index] = $row;
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, string>> $rows
     *
     * @return array<string, string>
     */
    protected function buildMerchantMap(array $rows): array
    {
        $abstractProductIds = $this->extractAbstractProductIds($rows);

        $merchantProductAbstracts = SpyMerchantProductAbstractQuery::create()
            ->filterByFkProductAbstract($abstractProductIds, SpyMerchantProductAbstractQuery::IN)
            ->joinWithProductAbstract()
            ->joinWithMerchant()
            ->withColumn(SpyProductAbstractTableMap::COL_SKU, static::ALIAS_ABSTRACT_SKU)
            ->withColumn(SpyMerchantTableMap::COL_MERCHANT_REFERENCE, static::ALIAS_MERCHANT_REFERENCE)
            ->select([static::ALIAS_ABSTRACT_SKU, static::ALIAS_MERCHANT_REFERENCE])
            ->find()
            ->getData();

        $merchantByAbstractSku = [];

        foreach ($merchantProductAbstracts as $merchantProductAbstract) {
            $merchantByAbstractSku[$merchantProductAbstract[static::ALIAS_ABSTRACT_SKU]] = $merchantProductAbstract[static::ALIAS_MERCHANT_REFERENCE];
        }

        return $merchantByAbstractSku;
    }
}
