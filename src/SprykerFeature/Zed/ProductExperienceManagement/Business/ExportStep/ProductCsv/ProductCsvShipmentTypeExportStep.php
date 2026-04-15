<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ExportStep\ProductCsv;

use Orm\Zed\Product\Persistence\Map\SpyProductTableMap;
use Orm\Zed\SelfServicePortal\Persistence\SpyProductShipmentTypeQuery;
use Orm\Zed\ShipmentType\Persistence\Map\SpyShipmentTypeTableMap;

class ProductCsvShipmentTypeExportStep extends AbstractProductCsvExportStep
{
    protected const string COLUMN_SHIPMENT_TYPES = 'Shipment Types';

    protected const string ALIAS_SHIPMENT_TYPE_KEY = 'ShipmentTypeKey';

    /**
     * {@inheritDoc}
     */
    public function exportRows(array $rows, array $columns): array
    {
        $shipmentTypesByConcreteSku = $this->buildShipmentTypeMap($rows);

        foreach ($rows as $index => $row) {
            if (!$this->isConcreteRow($row)) {
                continue;
            }

            $concreteSku = $row[static::COLUMN_CONCRETE_SKU];

            if (!isset($shipmentTypesByConcreteSku[$concreteSku])) {
                continue;
            }

            $row = $this->setColumn(
                $row,
                $columns,
                static::COLUMN_SHIPMENT_TYPES,
                implode(static::SEPARATOR, $shipmentTypesByConcreteSku[$concreteSku]),
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
    protected function buildShipmentTypeMap(array $rows): array
    {
        if (!$this->canExportSelfServiceContext()) {
            return [];
        }

        $concreteProductIds = $this->extractConcreteProductIds($rows);

        $productShipmentTypes = SpyProductShipmentTypeQuery::create()
            ->filterByFkProduct($concreteProductIds, SpyProductShipmentTypeQuery::IN)
            ->joinWithSpyShipmentType()
            ->joinWithSpyProduct()
            ->withColumn(SpyShipmentTypeTableMap::COL_KEY, static::ALIAS_SHIPMENT_TYPE_KEY)
            ->withColumn(SpyProductTableMap::COL_SKU, static::ALIAS_CONCRETE_SKU)
            ->select([static::ALIAS_SHIPMENT_TYPE_KEY, static::ALIAS_CONCRETE_SKU])
            ->find()
            ->getData();

        $shipmentTypesByConcreteSku = [];

        foreach ($productShipmentTypes as $productShipmentType) {
            $shipmentTypesByConcreteSku[$productShipmentType[static::ALIAS_CONCRETE_SKU]][] = $productShipmentType[static::ALIAS_SHIPMENT_TYPE_KEY];
        }

        return $shipmentTypesByConcreteSku;
    }

    /**
     * Checks whether the product class Propel entities are available at runtime.
     * This allows the step to work out of the box when SelfServicePortal is installed,
     * without introducing a hard composer dependency on it. Will be refactored in future
     *
     * @return bool
     */
    protected function canExportSelfServiceContext(): bool
    {
        if (class_exists(SpyProductShipmentTypeQuery::class)) {
            return true;
        }

        return false;
    }
}
