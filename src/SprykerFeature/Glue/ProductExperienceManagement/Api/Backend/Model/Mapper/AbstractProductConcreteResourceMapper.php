<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Mapper;

use ArrayObject;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\LocaleTransfer;
use Generated\Shared\Transfer\LocalizedAttributesTransfer;
use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Generated\Shared\Transfer\PriceTypeTransfer;
use Generated\Shared\Transfer\ProductBundleTransfer;
use Generated\Shared\Transfer\ProductClassTransfer;
use Generated\Shared\Transfer\ProductForBundleTransfer;
use Generated\Shared\Transfer\ProductImageSetTransfer;
use Generated\Shared\Transfer\ProductImageTransfer;
use Generated\Shared\Transfer\ShipmentTypeTransfer;
use Generated\Shared\Transfer\StockProductTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\DecimalObject\Decimal;

abstract class AbstractProductConcreteResourceMapper
{
    protected const string KEY_NAME = 'name';

    protected const string KEY_DESCRIPTION = 'description';

    protected const string KEY_PRICE_TYPE_NAME = 'priceTypeName';

    protected const string KEY_CURRENCY_CODE = 'currencyCode';

    protected const string KEY_STORE_NAME = 'storeName';

    protected const string KEY_GROSS_AMOUNT = 'grossAmount';

    protected const string KEY_NET_AMOUNT = 'netAmount';

    protected const string KEY_LOCALE_NAME = 'localeName';

    protected const string KEY_IMAGES = 'images';

    protected const string KEY_EXTERNAL_URL_SMALL = 'externalUrlSmall';

    protected const string KEY_EXTERNAL_URL_LARGE = 'externalUrlLarge';

    protected const string KEY_SORT_ORDER = 'sortOrder';

    protected const string KEY_STOCK_NAME = 'stockName';

    protected const string KEY_QUANTITY = 'quantity';

    protected const string KEY_IS_NEVER_OUT_OF_STOCK = 'isNeverOutOfStock';

    protected const string KEY_SKU = 'sku';

    protected const string KEY_ATTRIBUTES = 'attributes';

    protected const string KEY_ALT_TEXT_SMALL = 'altTextSmall';

    protected const string KEY_ALT_TEXT_LARGE = 'altTextLarge';

    protected const string KEY_UUID = 'uuid';

    protected const string KEY_KEY = 'key';

    protected const string KEY_VOLUME_PRICES = 'volumePrices';

    protected const string KEY_VOLUME_PRICES_STORAGE = 'volume_prices';

    protected const string KEY_VOLUME_NET_PRICE = 'net_price';

    protected const string KEY_VOLUME_GROSS_PRICE = 'gross_price';

    protected const string KEY_NET_PRICE = 'netPrice';

    protected const string KEY_GROSS_PRICE = 'grossPrice';

    /**
     * @param array<int, array<string, mixed>> $localizedAttributesData
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\LocalizedAttributesTransfer>
     */
    protected function mapLocalizedAttributes(array $localizedAttributesData): ArrayObject
    {
        $localizedAttributesTransfers = new ArrayObject();

        foreach ($localizedAttributesData as $localizedAttributesItem) {
            $localizedAttributesItem = (array)$localizedAttributesItem;
            $localeName = (string)($localizedAttributesItem[static::KEY_LOCALE_NAME] ?? '');
            unset($localizedAttributesItem[static::KEY_LOCALE_NAME]);

            $filteredLocalizedAttributes = array_filter(
                $localizedAttributesItem,
                static fn ($value): bool => $value !== null,
            );

            $localizedAttributesTransfers->append(
                (new LocalizedAttributesTransfer())
                    ->fromArray($filteredLocalizedAttributes, true)
                    ->setLocale(
                        (new LocaleTransfer())->setLocaleName($localeName),
                    ),
            );
        }

        return $localizedAttributesTransfers;
    }

    /**
     * @param array<int, array<string, mixed>> $pricesData
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\PriceProductTransfer>
     */
    protected function mapPrices(array $pricesData): ArrayObject
    {
        $priceProductTransfers = new ArrayObject();

        foreach ($pricesData as $priceData) {
            $priceProductTransfers->append($this->mapSinglePrice((array)$priceData));
        }

        return $priceProductTransfers;
    }

    /**
     * @param array<string, mixed> $priceData
     */
    protected function mapSinglePrice(array $priceData): PriceProductTransfer
    {
        $moneyValueTransfer = (new MoneyValueTransfer())
            ->setGrossAmount($priceData[static::KEY_GROSS_AMOUNT] ?? null)
            ->setNetAmount($priceData[static::KEY_NET_AMOUNT] ?? null);

        if (isset($priceData[static::KEY_CURRENCY_CODE])) {
            $moneyValueTransfer->setCurrency(
                (new CurrencyTransfer())->setCode($priceData[static::KEY_CURRENCY_CODE]),
            );
        }

        if (isset($priceData[static::KEY_STORE_NAME])) {
            $moneyValueTransfer->setStore(
                (new StoreTransfer())->setName($priceData[static::KEY_STORE_NAME]),
            );
        }

        $volumePrices = $priceData[static::KEY_VOLUME_PRICES] ?? null;

        if ($volumePrices !== null) {
            $moneyValueTransfer->setPriceData($this->encodeVolumePrices((array)$volumePrices));
        }

        return (new PriceProductTransfer())
            ->setPriceType(
                (new PriceTypeTransfer())->setName($priceData[static::KEY_PRICE_TYPE_NAME] ?? null),
            )
            ->setMoneyValue($moneyValueTransfer);
    }

    /**
     * @param array<int, array<string, mixed>> $volumePrices
     */
    protected function encodeVolumePrices(array $volumePrices): ?string
    {
        if ($volumePrices === []) {
            return null;
        }

        $storageVolumePrices = [];

        foreach ($volumePrices as $volumePrice) {
            $storageVolumePrices[] = [
                static::KEY_QUANTITY => $volumePrice[static::KEY_QUANTITY] ?? null,
                static::KEY_VOLUME_NET_PRICE => $volumePrice[static::KEY_NET_PRICE] ?? null,
                static::KEY_VOLUME_GROSS_PRICE => $volumePrice[static::KEY_GROSS_PRICE] ?? null,
            ];
        }

        $encodedVolumePrices = json_encode([static::KEY_VOLUME_PRICES_STORAGE => $storageVolumePrices]);

        return $encodedVolumePrices === false ? null : $encodedVolumePrices;
    }

    /**
     * @param array<int, array<string, mixed>> $imageSetsData
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\ProductImageSetTransfer>
     */
    protected function mapImageSets(array $imageSetsData): ArrayObject
    {
        $productImageSetTransfers = new ArrayObject();

        foreach ($imageSetsData as $imageSetData) {
            $imageSetData = (array)$imageSetData;
            $imageSetTransfer = (new ProductImageSetTransfer())
                ->setName($imageSetData[static::KEY_NAME] ?? null)
                ->setUuid($imageSetData[static::KEY_UUID] ?? null);

            if (isset($imageSetData[static::KEY_LOCALE_NAME])) {
                $imageSetTransfer->setLocale(
                    (new LocaleTransfer())->setLocaleName($imageSetData[static::KEY_LOCALE_NAME]),
                );
            }

            $imageSetTransfer->setProductImages(
                $this->mapProductImages($imageSetData[static::KEY_IMAGES] ?? []),
            );

            $productImageSetTransfers->append($imageSetTransfer);
        }

        return $productImageSetTransfers;
    }

    /**
     * @param array<int, array<string, mixed>> $imagesData
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\ProductImageTransfer>
     */
    protected function mapProductImages(array $imagesData): ArrayObject
    {
        $productImageTransfers = new ArrayObject();

        foreach ($imagesData as $imageData) {
            $imageData = (array)$imageData;
            $productImageTransfers->append(
                (new ProductImageTransfer())
                    ->setExternalUrlSmall($imageData[static::KEY_EXTERNAL_URL_SMALL] ?? null)
                    ->setExternalUrlLarge($imageData[static::KEY_EXTERNAL_URL_LARGE] ?? null)
                    ->setAltTextSmall($imageData[static::KEY_ALT_TEXT_SMALL] ?? null)
                    ->setAltTextLarge($imageData[static::KEY_ALT_TEXT_LARGE] ?? null)
                    ->setSortOrder($imageData[static::KEY_SORT_ORDER] ?? null),
            );
        }

        return $productImageTransfers;
    }

    /**
     * @param array<int, array<string, mixed>> $stocksData
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\StockProductTransfer>
     */
    protected function mapStocks(array $stocksData, ?string $sku): ArrayObject
    {
        $stockProductTransfers = new ArrayObject();

        foreach ($stocksData as $stockData) {
            $stockData = (array)$stockData;
            $stockProductTransfers->append(
                (new StockProductTransfer())
                    ->setSku($sku)
                    ->setStockType($stockData[static::KEY_STOCK_NAME] ?? null)
                    ->setQuantity(new Decimal($stockData[static::KEY_QUANTITY] ?? 0))
                    ->setIsNeverOutOfStock($stockData[static::KEY_IS_NEVER_OUT_OF_STOCK] ?? false),
            );
        }

        return $stockProductTransfers;
    }

    /**
     * @param array<int, array<string, mixed>> $productBundleData
     */
    protected function mapProductBundle(array $productBundleData): ?ProductBundleTransfer
    {
        if ($productBundleData === []) {
            return null;
        }

        $productBundleTransfer = new ProductBundleTransfer();

        foreach ($productBundleData as $bundledProductData) {
            $bundledProductData = (array)$bundledProductData;
            $productBundleTransfer->addBundledProduct(
                (new ProductForBundleTransfer())
                    ->setSku($bundledProductData[static::KEY_SKU] ?? null)
                    ->setQuantity($bundledProductData[static::KEY_QUANTITY] ?? null),
            );
        }

        return $productBundleTransfer;
    }

    /**
     * @param array<int, array<string, mixed>> $productClassesData
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\ProductClassTransfer>
     */
    protected function mapProductClasses(array $productClassesData): ArrayObject
    {
        $productClassTransfers = new ArrayObject();

        foreach ($productClassesData as $productClassData) {
            $productClassData = (array)$productClassData;
            $productClassTransfers->append(
                (new ProductClassTransfer())
                    ->setKey($productClassData[static::KEY_KEY] ?? null),
            );
        }

        return $productClassTransfers;
    }

    /**
     * @param array<int, array<string, mixed>> $shipmentTypesData
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\ShipmentTypeTransfer>
     */
    protected function mapShipmentTypes(array $shipmentTypesData): ArrayObject
    {
        $shipmentTypeTransfers = new ArrayObject();

        foreach ($shipmentTypesData as $shipmentTypeData) {
            $shipmentTypeData = (array)$shipmentTypeData;
            $shipmentTypeTransfers->append(
                (new ShipmentTypeTransfer())
                    ->setUuid($shipmentTypeData[static::KEY_UUID] ?? null),
            );
        }

        return $shipmentTypeTransfers;
    }
}
