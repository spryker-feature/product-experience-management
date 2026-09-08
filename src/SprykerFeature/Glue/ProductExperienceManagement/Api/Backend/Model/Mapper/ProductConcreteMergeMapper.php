<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Mapper;

use ArrayObject;
use Generated\Api\Backend\ProductsBackendResource;
use Generated\Shared\Transfer\PriceProductTransfer;
use Generated\Shared\Transfer\ProductBundleTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ProductImageSetTransfer;

class ProductConcreteMergeMapper extends AbstractProductConcreteResourceMapper implements ProductConcreteMergeMapperInterface
{
    public function mapResourceToProductConcreteTransfer(
        ProductsBackendResource $productsBackendResource,
        ProductConcreteTransfer $productConcreteTransfer,
    ): ProductConcreteTransfer {
        $this->mergeScalars($productsBackendResource, $productConcreteTransfer);
        $this->mergeCollections($productsBackendResource, $productConcreteTransfer);

        return $productConcreteTransfer;
    }

    protected function mergeScalars(
        ProductsBackendResource $productsBackendResource,
        ProductConcreteTransfer $productConcreteTransfer,
    ): void {
        $productConcreteTransfer->setAbstractSku(
            $productsBackendResource->getAbstractSku() ?? $productConcreteTransfer->getAbstractSku(),
        );
        $productConcreteTransfer->setIsActive(
            $productsBackendResource->getIsActive() ?? $productConcreteTransfer->getIsActive(),
        );
        $productConcreteTransfer->setValidFrom(
            $productsBackendResource->getValidFrom() ?? $productConcreteTransfer->getValidFrom(),
        );
        $productConcreteTransfer->setValidTo(
            $productsBackendResource->getValidTo() ?? $productConcreteTransfer->getValidTo(),
        );

        $productConcreteTransfer->setAttributes(array_merge(
            $productConcreteTransfer->getAttributes(),
            $productsBackendResource->getAttributes() ?? [],
        ));
    }

    protected function mergeCollections(
        ProductsBackendResource $productsBackendResource,
        ProductConcreteTransfer $productConcreteTransfer,
    ): void {
        $productConcreteTransfer->setLocalizedAttributes(
            $this->mergeLocalizedAttributes(
                $productConcreteTransfer->getLocalizedAttributes(),
                $productsBackendResource->getLocalizedAttributes() ?? [],
            ),
        );

        $productConcreteTransfer->setPrices(
            $this->mergePrices(
                $productConcreteTransfer->getPrices(),
                $productsBackendResource->getPrices() ?? [],
            ),
        );

        $productConcreteTransfer->setImageSets(
            $this->mergeImageSets(
                $productConcreteTransfer->getImageSets(),
                $productsBackendResource->getImageSets() ?? [],
            ),
        );

        $productConcreteTransfer->setStocks(
            $this->mergeStocks(
                $productConcreteTransfer->getStocks(),
                $productsBackendResource->getStocks() ?? [],
                $productConcreteTransfer->getSku(),
            ),
        );

        $productConcreteTransfer->setProductBundle(
            $this->mergeProductBundle(
                $productConcreteTransfer->getProductBundle(),
                $productsBackendResource->getProductBundle() ?? [],
            ),
        );

        $productConcreteTransfer->setProductClasses(
            $this->mergeProductClasses(
                $productConcreteTransfer->getProductClasses(),
                $productsBackendResource->getProductClass() ?? [],
            ),
        );

        $productConcreteTransfer->setShipmentTypes(
            $this->mergeShipmentTypes(
                $productConcreteTransfer->getShipmentTypes(),
                $productsBackendResource->getShipmentType() ?? [],
            ),
        );
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\LocalizedAttributesTransfer> $existingLocalizedAttributesTransfers
     * @param array<int, array<string, mixed>> $incomingLocalizedAttributesData
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\LocalizedAttributesTransfer>
     */
    protected function mergeLocalizedAttributes(
        ArrayObject $existingLocalizedAttributesTransfers,
        array $incomingLocalizedAttributesData
    ): ArrayObject {
        $incomingMappedLocalizedAttributesTransfers = $this->mapLocalizedAttributes($incomingLocalizedAttributesData);

        $existingByLocale = [];
        foreach ($existingLocalizedAttributesTransfers as $existingLocalizedAttributesTransfer) {
            $localeName = $existingLocalizedAttributesTransfer->getLocale()?->getLocaleName() ?? '';
            $existingByLocale[$localeName] = $existingLocalizedAttributesTransfer;
        }

        foreach ($incomingMappedLocalizedAttributesTransfers as $incomingLocalizedAttributesTransfer) {
            $localeName = $incomingLocalizedAttributesTransfer->getLocale()?->getLocaleName() ?? '';

            if (!isset($existingByLocale[$localeName])) {
                $existingByLocale[$localeName] = $incomingLocalizedAttributesTransfer;

                continue;
            }

            $existingLocale = $existingByLocale[$localeName]->getLocale();
            $existingByLocale[$localeName]->fromArray($incomingLocalizedAttributesTransfer->modifiedToArray(), true)
                ->setLocale($existingLocale);
        }

        return new ArrayObject(array_values($existingByLocale));
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\PriceProductTransfer> $existingPriceProductTransfers
     * @param array<int, array<string, mixed>> $incomingPriceData
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\PriceProductTransfer>
     */
    protected function mergePrices(ArrayObject $existingPriceProductTransfers, array $incomingPriceData): ArrayObject
    {
        $incomingMappedPriceProductTransfers = $this->mapPrices($incomingPriceData);

        $existingByKey = [];
        foreach ($existingPriceProductTransfers as $existingPriceProductTransfer) {
            $key = $this->buildPriceKey($existingPriceProductTransfer);
            $existingByKey[$key] = $existingPriceProductTransfer;
        }

        foreach ($incomingMappedPriceProductTransfers as $incomingPriceProductTransfer) {
            $key = $this->buildPriceKey($incomingPriceProductTransfer);

            if (!isset($existingByKey[$key])) {
                $existingByKey[$key] = $incomingPriceProductTransfer;

                continue;
            }

            $this->overlayPrice($existingByKey[$key], $incomingPriceProductTransfer);
        }

        return new ArrayObject(array_values($existingByKey));
    }

    /**
     * Overlays instead of replacing so that `idPriceProduct`, `MoneyValue.idEntity` and
     * `PriceProductDimension.idPriceProductDefault` survive — `PriceProductStoreWriter` needs them to
     * retire the superseded `spy_price_product_store` row instead of leaving it behind.
     */
    protected function overlayPrice(
        PriceProductTransfer $existingPriceProductTransfer,
        PriceProductTransfer $incomingPriceProductTransfer
    ): void {
        $incomingMoneyValueTransfer = $incomingPriceProductTransfer->getMoneyValue();

        if ($incomingMoneyValueTransfer === null) {
            return;
        }

        $existingMoneyValueTransfer = $existingPriceProductTransfer->getMoneyValue();

        if ($existingMoneyValueTransfer === null) {
            $existingPriceProductTransfer->setMoneyValue($incomingMoneyValueTransfer);

            return;
        }

        // `priceDataChecksum` is deliberately left as read from the database: `PriceProductStoreWriter`
        // matches the existing row on it before recalculating it, so clearing it would force a new row
        // even for an unchanged price.
        $incomingMoneyValueData = $incomingMoneyValueTransfer->modifiedToArray(true, true);

        $currencyTransfer = $existingMoneyValueTransfer->getCurrency();
        $storeTransfer = $existingMoneyValueTransfer->getStore();

        $existingMoneyValueTransfer
            ->fromArray($incomingMoneyValueData, true)
            ->setCurrency($currencyTransfer)
            ->setStore($storeTransfer);
    }

    protected function buildPriceKey(PriceProductTransfer $priceProductTransfer): string
    {
        $priceTypeName = $priceProductTransfer->getPriceType()?->getName()
            ?? $priceProductTransfer->getPriceTypeName()
            ?? '';
        $currencyCode = $priceProductTransfer->getMoneyValue()?->getCurrency()?->getCode() ?? '';
        $storeName = $priceProductTransfer->getMoneyValue()?->getStore()?->getName() ?? '';

        return sprintf('%s|%s|%s', $priceTypeName, $currencyCode, $storeName);
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ProductImageSetTransfer> $existingProductImageSetTransfers
     * @param array<int, array<string, mixed>> $incomingProductImageSetData
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\ProductImageSetTransfer>
     */
    protected function mergeImageSets(ArrayObject $existingProductImageSetTransfers, array $incomingProductImageSetData): ArrayObject
    {
        $incomingMappedProductImageSetTransfers = $this->mapImageSets($incomingProductImageSetData);

        $existingByUuid = [];
        foreach ($existingProductImageSetTransfers as $existingProductImageSetTransfer) {
            $uuid = $existingProductImageSetTransfer->getUuid();

            if ($uuid !== null) {
                $existingByUuid[$uuid] = $existingProductImageSetTransfer;
            }
        }

        $mergedProductImageSetTransfers = [];
        $matchedUuids = [];

        foreach ($incomingMappedProductImageSetTransfers as $incomingProductImageSetTransfer) {
            $uuid = $incomingProductImageSetTransfer->getUuid();

            if ($uuid !== null && isset($existingByUuid[$uuid])) {
                $this->applyExistingImageSetValues(
                    $incomingProductImageSetTransfer,
                    $existingByUuid[$uuid],
                );
                $matchedUuids[$uuid] = true;
            }

            $mergedProductImageSetTransfers[] = $incomingProductImageSetTransfer;
        }

        foreach ($existingProductImageSetTransfers as $existingProductImageSetTransfer) {
            $uuid = $existingProductImageSetTransfer->getUuid();

            if ($uuid === null || !isset($matchedUuids[$uuid])) {
                $mergedProductImageSetTransfers[] = $existingProductImageSetTransfer;
            }
        }

        return new ArrayObject($mergedProductImageSetTransfers);
    }

    protected function applyExistingImageSetValues(
        ProductImageSetTransfer $incomingProductImageSetTransfer,
        ProductImageSetTransfer $existingProductImageSetTransfer
    ): void {
        $incomingProductImageSetTransfer->setIdProductImageSet(
            $existingProductImageSetTransfer->getIdProductImageSet(),
        );

        if ($incomingProductImageSetTransfer->getName() === null) {
            $incomingProductImageSetTransfer->setName($existingProductImageSetTransfer->getName());
        }

        if ($incomingProductImageSetTransfer->getLocale() === null) {
            $incomingProductImageSetTransfer->setLocale($existingProductImageSetTransfer->getLocale());
        }
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\StockProductTransfer> $existingStockProductTransfers
     * @param array<int, array<string, mixed>> $incomingStockProductData
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\StockProductTransfer>
     */
    protected function mergeStocks(ArrayObject $existingStockProductTransfers, array $incomingStockProductData, ?string $sku): ArrayObject
    {
        $incomingMappedStockProductTransfers = $this->mapStocks($incomingStockProductData, $sku);

        $existingStockProductTransferByKey = [];
        foreach ($existingStockProductTransfers as $existingStockProductTransfer) {
            $key = $existingStockProductTransfer->getStockType() ?? '';
            $existingStockProductTransferByKey[$key] = $existingStockProductTransfer;
        }

        foreach ($incomingMappedStockProductTransfers as $incomingStockProductTransfer) {
            $key = $incomingStockProductTransfer->getStockType() ?? '';
            $existingStockProductTransferByKey[$key] = $incomingStockProductTransfer;
        }

        return new ArrayObject(array_values($existingStockProductTransferByKey));
    }

    /**
     * @param array<int, array<string, mixed>> $incomingProductBundleData
     */
    protected function mergeProductBundle(
        ?ProductBundleTransfer $existingProductBundleTransfer,
        array $incomingProductBundleData,
    ): ?ProductBundleTransfer {
        $incomingProductBundleTransfer = $this->mapProductBundle($incomingProductBundleData);

        if ($incomingProductBundleTransfer === null) {
            return $existingProductBundleTransfer;
        }

        if ($existingProductBundleTransfer === null) {
            return $incomingProductBundleTransfer;
        }

        $existingByKey = [];
        foreach ($existingProductBundleTransfer->getBundledProducts() as $existingTransfer) {
            $key = $existingTransfer->getSku() ?? '';
            $existingByKey[$key] = $existingTransfer;
        }

        foreach ($incomingProductBundleTransfer->getBundledProducts() as $incomingTransfer) {
            $key = $incomingTransfer->getSku() ?? '';
            $existingByKey[$key] = $incomingTransfer;
        }

        $mergedProductBundleTransfer = new ProductBundleTransfer();

        foreach (array_values($existingByKey) as $productForBundleTransfer) {
            $mergedProductBundleTransfer->addBundledProduct($productForBundleTransfer);
        }

        return $mergedProductBundleTransfer;
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ProductClassTransfer> $existingProductClassTransfers
     * @param array<int, array<string, mixed>> $incomingProductClassData
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\ProductClassTransfer>
     */
    protected function mergeProductClasses(ArrayObject $existingProductClassTransfers, array $incomingProductClassData): ArrayObject
    {
        $incomingMappedProductClassTransfers = $this->mapProductClasses($incomingProductClassData);

        $existingProductClassTransfersByKey = [];
        foreach ($existingProductClassTransfers as $existingProductClassTransfer) {
            $key = $existingProductClassTransfer->getKey();

            if ($key === null || $key === '') {
                continue;
            }

            $existingProductClassTransfersByKey[$key] = $existingProductClassTransfer;
        }

        foreach ($incomingMappedProductClassTransfers as $incomingProductClassTransfer) {
            $key = $incomingProductClassTransfer->getKey();

            if ($key === null || $key === '') {
                continue;
            }

            $existingProductClassTransfersByKey[$key] = $incomingProductClassTransfer;
        }

        return new ArrayObject(array_values($existingProductClassTransfersByKey));
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ShipmentTypeTransfer> $existingShipmentTypeTransfers
     * @param array<int, array<string, mixed>> $incomingShipmentTypeData
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\ShipmentTypeTransfer>
     */
    protected function mergeShipmentTypes(ArrayObject $existingShipmentTypeTransfers, array $incomingShipmentTypeData): ArrayObject
    {
        $incomingMappedShipmentTypeTransfers = $this->mapShipmentTypes($incomingShipmentTypeData);

        $existingShipmentTypeTransfersByKey = [];
        foreach ($existingShipmentTypeTransfers as $existingShipmentTypeTransfer) {
            $key = $existingShipmentTypeTransfer->getUuid() ?? '';
            $existingShipmentTypeTransfersByKey[$key] = $existingShipmentTypeTransfer;
        }

        foreach ($incomingMappedShipmentTypeTransfers as $incomingShipmentTypeTransfer) {
            $key = $incomingShipmentTypeTransfer->getUuid() ?? '';
            $existingShipmentTypeTransfersByKey[$key] = $incomingShipmentTypeTransfer;
        }

        return new ArrayObject(array_values($existingShipmentTypeTransfersByKey));
    }
}
