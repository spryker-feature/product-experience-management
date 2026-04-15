<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ExportStep\ProductCsv;

use Orm\Zed\PriceProduct\Persistence\SpyPriceProductQuery;

class ProductCsvPriceExportStep extends AbstractProductCsvExportStep
{
    protected const string PRICE_COLUMN_GROSS_FORMAT = 'Price (%s-%s-%s-Gross)';

    protected const string PRICE_COLUMN_NET_FORMAT = 'Price (%s-%s-%s-Net)';

    /**
     * {@inheritDoc}
     */
    public function exportRows(array $rows, array $columns): array
    {
        $rows = $this->populateAbstractPrices($rows, $columns);
        $rows = $this->populateConcretePrices($rows, $columns);

        return $rows;
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @param array<string> $columns
     *
     * @return array<int, array<string, string>>
     */
    protected function populateAbstractPrices(array $rows, array $columns): array
    {
        $abstractIdToSkuMap = $this->extractAbstractIdToSkuMap($rows);

        $priceProducts = SpyPriceProductQuery::create()
            ->filterByFkProductAbstract(array_keys($abstractIdToSkuMap), SpyPriceProductQuery::IN)
            ->joinWithPriceType()
            ->joinWithPriceProductStore()
            ->usePriceProductStoreQuery()
                ->joinWithStore()
                ->joinWithCurrency()
            ->endUse()
            ->find();

        $pricesByAbstractSku = [];

        foreach ($priceProducts as $priceProduct) {
            $idProductAbstract = $priceProduct->getFkProductAbstract();

            if (!isset($abstractIdToSkuMap[$idProductAbstract])) {
                continue;
            }

            $abstractSku = $abstractIdToSkuMap[$idProductAbstract];
            $pricesByAbstractSku[$abstractSku][] = $priceProduct;
        }

        foreach ($rows as $index => $row) {
            if (!$this->isAbstractRow($row)) {
                continue;
            }

            $abstractSku = $row[static::COLUMN_ABSTRACT_SKU];

            if (!isset($pricesByAbstractSku[$abstractSku])) {
                continue;
            }

            foreach ($pricesByAbstractSku[$abstractSku] as $priceProduct) {
                $row = $this->populatePriceColumns($row, $columns, $priceProduct);
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
    protected function populateConcretePrices(array $rows, array $columns): array
    {
        $concreteIdToSkuMap = $this->extractConcreteIdToSkuMap($rows);

        $priceProducts = SpyPriceProductQuery::create()
            ->filterByFkProduct(array_keys($concreteIdToSkuMap), SpyPriceProductQuery::IN)
            ->joinWithPriceType()
            ->joinWithPriceProductStore()
            ->usePriceProductStoreQuery()
                ->joinWithStore()
                ->joinWithCurrency()
            ->endUse()
            ->find();

        $pricesByConcreteSku = [];

        foreach ($priceProducts as $priceProduct) {
            $idProduct = $priceProduct->getFkProduct();

            if (!isset($concreteIdToSkuMap[$idProduct])) {
                continue;
            }

            $concreteSku = $concreteIdToSkuMap[$idProduct];
            $pricesByConcreteSku[$concreteSku][] = $priceProduct;
        }

        foreach ($rows as $index => $row) {
            if (!$this->isConcreteRow($row)) {
                continue;
            }

            $concreteSku = $row[static::COLUMN_CONCRETE_SKU];

            if (!isset($pricesByConcreteSku[$concreteSku])) {
                continue;
            }

            foreach ($pricesByConcreteSku[$concreteSku] as $priceProduct) {
                $row = $this->populatePriceColumns($row, $columns, $priceProduct);
            }

            $rows[$index] = $row;
        }

        return $rows;
    }

    /**
     * @param array<string, string> $row
     * @param array<string> $columns
     *
     * @return array<string, string>
     */
    protected function populatePriceColumns(array $row, array $columns, mixed $priceProduct): array
    {
        $priceModeName = ucfirst(strtolower($priceProduct->getPriceType()->getName()));

        foreach ($priceProduct->getPriceProductStores() as $priceStore) {
            $storeName = $priceStore->getStore()->getName();
            $currencyCode = $priceStore->getCurrency()->getCode();

            $grossColumn = sprintf(static::PRICE_COLUMN_GROSS_FORMAT, $priceModeName, $storeName, $currencyCode);
            $netColumn = sprintf(static::PRICE_COLUMN_NET_FORMAT, $priceModeName, $storeName, $currencyCode);

            if ($priceStore->getGrossPrice() !== null) {
                $row = $this->setColumn($row, $columns, $grossColumn, (string)$priceStore->getGrossPrice());
            }

            if ($priceStore->getNetPrice() !== null) {
                $row = $this->setColumn($row, $columns, $netColumn, (string)$priceStore->getNetPrice());
            }
        }

        return $row;
    }
}
