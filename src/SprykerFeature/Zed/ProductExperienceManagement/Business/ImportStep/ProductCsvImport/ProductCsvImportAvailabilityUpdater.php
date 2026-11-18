<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport;

use Generated\Shared\Transfer\ImportPublishEventTransfer;
use Generated\Shared\Transfer\ImportStepResponseTransfer;
use Orm\Zed\Availability\Persistence\Map\SpyAvailabilityTableMap;
use Orm\Zed\Availability\Persistence\SpyAvailabilityAbstract;
use Orm\Zed\Availability\Persistence\SpyAvailabilityAbstractQuery;
use Orm\Zed\Availability\Persistence\SpyAvailabilityQuery;
use Orm\Zed\Oms\Persistence\SpyOmsProductReservationQuery;
use Orm\Zed\Stock\Persistence\Map\SpyStockProductTableMap;
use Orm\Zed\Stock\Persistence\SpyStockProductQuery;
use Spryker\DecimalObject\Decimal;
use Spryker\Zed\Availability\Dependency\AvailabilityEvents;
use Spryker\Zed\PropelOrm\Business\Runtime\ActiveQuery\Criteria;
use Spryker\Zed\Stock\Business\StockFacadeInterface;
use Spryker\Zed\Store\Business\StoreFacadeInterface;

class ProductCsvImportAvailabilityUpdater implements ProductCsvImportAvailabilityUpdaterInterface
{
    public function __construct(
        protected StoreFacadeInterface $storeFacade,
        protected StockFacadeInterface $stockFacade,
    ) {
    }

    /**
     * Calculates and persists availability records for each concrete SKU per store.
     * Matches the approach used by the standard Spryker ProductStockPropelDataSetWriter:
     * availability = stock quantity (filtered by store warehouses) minus OMS reservations.
     *
     * @param array<string, array{idProduct: int, abstractSku: string, isNeverOutOfStock: bool}> $processedConcreteSkus concreteSku => data
     */
    public function updateAvailabilityForConcreteSkus(array $processedConcreteSkus, ImportStepResponseTransfer $response): void
    {
        $stores = $this->storeFacade->getAllStores();
        $storeToWarehouseMapping = $this->stockFacade->getStoreToWarehouseMapping();

        foreach ($processedConcreteSkus as $concreteSku => $skuData) {
            $concreteSku = (string)$concreteSku;
            $abstractSku = $skuData['abstractSku'];
            $isNeverOutOfStock = $skuData['isNeverOutOfStock'];

            $idProduct = $skuData['idProduct'];

            foreach ($stores as $storeTransfer) {
                $idStore = $storeTransfer->getIdStoreOrFail();
                $storeName = $storeTransfer->getNameOrFail();
                $warehouseNames = $storeToWarehouseMapping[$storeName] ?? [];

                $stockQuantity = $this->calculateStockQuantityForWarehouses($idProduct, $warehouseNames);
                $reservedQuantity = $this->getReservationQuantity($concreteSku, $idStore);
                $availableQuantity = $stockQuantity->subtract($reservedQuantity);

                if ($availableQuantity->lessThan(0)) {
                    $availableQuantity = new Decimal(0);
                }

                $availabilityAbstractEntity = SpyAvailabilityAbstractQuery::create()
                    ->filterByAbstractSku($abstractSku)
                    ->filterByFkStore($idStore)
                    ->findOneOrCreate();

                if ($availabilityAbstractEntity->isNew()) {
                    $availabilityAbstractEntity->setQuantity('0');
                    $availabilityAbstractEntity->save();
                }

                $availabilityEntity = SpyAvailabilityQuery::create()
                    ->filterBySku($concreteSku)
                    ->filterByFkStore($idStore)
                    ->findOneOrCreate();

                $availabilityEntity->setFkAvailabilityAbstract($availabilityAbstractEntity->getIdAvailabilityAbstract());
                $availabilityEntity->setQuantity($availableQuantity->toString());
                $availabilityEntity->setIsNeverOutOfStock($isNeverOutOfStock);

                if ($availabilityEntity->isNew() || $availabilityEntity->isModified()) {
                    $availabilityEntity->save();
                }

                $this->updateAbstractAvailabilityQuantity($availabilityAbstractEntity, $idStore);

                $response->addPublishEvent(
                    (new ImportPublishEventTransfer())
                        ->setEventName(AvailabilityEvents::AVAILABILITY_ABSTRACT_PUBLISH)
                        ->setEntityId($availabilityAbstractEntity->getIdAvailabilityAbstract()),
                );
            }
        }
    }

    /**
     * @param array<string> $warehouseNames
     */
    protected function calculateStockQuantityForWarehouses(int $idProduct, array $warehouseNames): Decimal
    {
        if ($warehouseNames === []) {
            return new Decimal(0);
        }

        $totalQuantity = SpyStockProductQuery::create()
            ->filterByFkProduct($idProduct)
            ->useStockQuery()
                ->filterByName($warehouseNames, Criteria::IN)
            ->endUse()
            ->withColumn(sprintf('SUM(%s)', SpyStockProductTableMap::COL_QUANTITY), 'totalQuantity')
            ->select(['totalQuantity'])
            ->findOne();

        return new Decimal((string)($totalQuantity ?? 0));
    }

    protected function getReservationQuantity(string $concreteSku, int $idStore): Decimal
    {
        $reservations = SpyOmsProductReservationQuery::create()
            ->filterBySku($concreteSku)
            ->filterByFkStore($idStore)
            ->find();

        $total = new Decimal(0);

        foreach ($reservations as $reservation) {
            $total = $total->add($reservation->getReservationQuantity());
        }

        return $total;
    }

    protected function updateAbstractAvailabilityQuantity(
        SpyAvailabilityAbstract $availabilityAbstractEntity,
        int $idStore,
    ): void {
        $sumQuantity = SpyAvailabilityQuery::create()
            ->filterByFkAvailabilityAbstract($availabilityAbstractEntity->getIdAvailabilityAbstract())
            ->filterByFkStore($idStore)
            ->withColumn(sprintf('SUM(%s)', SpyAvailabilityTableMap::COL_QUANTITY), 'totalQuantity')
            ->select(['totalQuantity'])
            ->findOne();

        $availabilityAbstractEntity->setQuantity((string)($sumQuantity ?? 0));

        if ($availabilityAbstractEntity->isModified()) {
            $availabilityAbstractEntity->save();
        }
    }
}
