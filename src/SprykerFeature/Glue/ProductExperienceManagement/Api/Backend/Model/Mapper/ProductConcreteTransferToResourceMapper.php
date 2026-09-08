<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Mapper;

use DateTimeImmutable;
use Generated\Api\Backend\ProductsBackendResource;
use Generated\Shared\Transfer\PriceProductTransfer;
use Generated\Shared\Transfer\ProductAbstractCollectionTransfer;
use Generated\Shared\Transfer\ProductAbstractTransfer;
use Generated\Shared\Transfer\ProductCategoryTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ProductImageSetTransfer;
use Generated\Shared\Transfer\StockProductTransfer;
use Generated\Shared\Transfer\TaxSetTransfer;
use Spryker\Service\Serializer\SerializerServiceInterface;

class ProductConcreteTransferToResourceMapper implements ProductConcreteTransferToResourceMapperInterface
{
    protected const string DATE_FORMAT = 'Y-m-d H:i:s';

    protected const string KEY_NAME = 'name';

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

    protected const string KEY_UUID = 'uuid';

    protected const string KEY_KEY = 'key';

    protected const string KEY_ALT_TEXT_SMALL = 'altTextSmall';

    protected const string KEY_ALT_TEXT_LARGE = 'altTextLarge';

    protected const string KEY_VOLUME_PRICES = 'volumePrices';

    protected const string KEY_VOLUME_PRICES_STORAGE = 'volume_prices';

    protected const string KEY_VOLUME_NET_PRICE = 'net_price';

    protected const string KEY_VOLUME_GROSS_PRICE = 'gross_price';

    protected const string KEY_NET_PRICE = 'netPrice';

    protected const string KEY_GROSS_PRICE = 'grossPrice';

    protected const string KEY_ABSTRACT_SKU = 'abstractSku';

    protected const string KEY_IS_ACTIVE = 'isActive';

    protected const string KEY_VALID_FROM = 'validFrom';

    protected const string KEY_VALID_TO = 'validTo';

    protected const string KEY_ATTRIBUTES = 'attributes';

    protected const string KEY_SUPER_ATTRIBUTE_VALUES = 'superAttributeValues';

    protected const string KEY_LOCALIZED_ATTRIBUTES = 'localizedAttributes';

    protected const string KEY_LOCALE = 'locale';

    protected const string KEY_PRICES = 'prices';

    protected const string KEY_IMAGE_SETS = 'imageSets';

    protected const string KEY_STOCKS = 'stocks';

    protected const string KEY_PRODUCT_BUNDLE = 'productBundle';

    protected const string KEY_PRODUCT_CLASS = 'productClass';

    protected const string KEY_SHIPMENT_TYPE = 'shipmentType';

    protected const string KEY_STORES = 'stores';

    protected const string KEY_TAX_SET = 'taxSet';

    protected const string KEY_CATEGORIES = 'categories';

    protected const string KEY_CATEGORY_KEY = 'categoryKey';

    protected const string KEY_URL = 'url';

    protected const string KEY_NEW_FROM = 'newFrom';

    protected const string KEY_NEW_TO = 'newTo';

    public function __construct(
        protected readonly SerializerServiceInterface $serializerService,
    ) {
    }

    public function mapProductConcreteTransferToResource(
        ProductConcreteTransfer $productConcreteTransfer,
        ProductAbstractCollectionTransfer $productAbstractCollectionTransfer,
    ): ProductsBackendResource {
        return $this->mapWithIndexedProductAbstracts(
            $productConcreteTransfer,
            $this->indexProductAbstractsBySku($productAbstractCollectionTransfer),
            $this->indexTaxSetsByProductAbstractSku($productAbstractCollectionTransfer),
        );
    }

    /**
     * {@inheritDoc}
     *
     * @param iterable<\Generated\Shared\Transfer\ProductConcreteTransfer> $productConcreteTransfers
     *
     * @return array<\Generated\Api\Backend\ProductsBackendResource>
     */
    public function mapProductConcreteTransfersToResources(
        iterable $productConcreteTransfers,
        ProductAbstractCollectionTransfer $productAbstractCollectionTransfer,
    ): array {
        $productAbstractsBySku = $this->indexProductAbstractsBySku($productAbstractCollectionTransfer);
        $taxSetsBySku = $this->indexTaxSetsByProductAbstractSku($productAbstractCollectionTransfer);

        $productsBackendResources = [];

        foreach ($productConcreteTransfers as $productConcreteTransfer) {
            $productsBackendResources[] = $this->mapWithIndexedProductAbstracts(
                $productConcreteTransfer,
                $productAbstractsBySku,
                $taxSetsBySku,
            );
        }

        return $productsBackendResources;
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\ProductAbstractTransfer> $productAbstractsBySku
     * @param array<string, \Generated\Shared\Transfer\TaxSetTransfer> $taxSetsBySku
     */
    protected function mapWithIndexedProductAbstracts(
        ProductConcreteTransfer $productConcreteTransfer,
        array $productAbstractsBySku,
        array $taxSetsBySku,
    ): ProductsBackendResource {
        $abstractSku = $productConcreteTransfer->getAbstractSku() ?? '';

        $productsBackendResourceData = $this->prepareResourceData(
            $productConcreteTransfer,
            $productAbstractsBySku[$abstractSku] ?? null,
            $taxSetsBySku[$abstractSku] ?? null,
        );

        return $this->serializerService->denormalize(
            $productsBackendResourceData,
            ProductsBackendResource::class,
        );
    }

    /**
     * @return array<string, \Generated\Shared\Transfer\ProductAbstractTransfer>
     */
    protected function indexProductAbstractsBySku(ProductAbstractCollectionTransfer $productAbstractCollectionTransfer): array
    {
        $productAbstractsBySku = [];

        foreach ($productAbstractCollectionTransfer->getProductAbstracts() as $productAbstractTransfer) {
            $productAbstractsBySku[$productAbstractTransfer->getSkuOrFail()] = $productAbstractTransfer;
        }

        return $productAbstractsBySku;
    }

    /**
     * @return array<string, \Generated\Shared\Transfer\TaxSetTransfer>
     */
    protected function indexTaxSetsByProductAbstractSku(ProductAbstractCollectionTransfer $productAbstractCollectionTransfer): array
    {
        $taxSetsBySku = [];

        foreach ($productAbstractCollectionTransfer->getProductTaxSets() as $productAbstractTaxSetCollectionTransfer) {
            $taxSetTransfer = $productAbstractTaxSetCollectionTransfer->getTaxSet();

            if ($taxSetTransfer === null) {
                continue;
            }

            $taxSetsBySku[$productAbstractTaxSetCollectionTransfer->getProductAbstractSkuOrFail()] = $taxSetTransfer;
        }

        return $taxSetsBySku;
    }

    /**
     * @return array<string, mixed>
     */
    protected function prepareResourceData(
        ProductConcreteTransfer $productConcreteTransfer,
        ?ProductAbstractTransfer $productAbstractTransfer,
        ?TaxSetTransfer $taxSetTransfer,
    ): array {
        return [
            static::KEY_SKU => $productConcreteTransfer->getSku(),
            static::KEY_ABSTRACT_SKU => $productConcreteTransfer->getAbstractSku(),
            static::KEY_IS_ACTIVE => $productConcreteTransfer->getIsActive(),
            static::KEY_VALID_FROM => $this->formatDateTime($productConcreteTransfer->getValidFrom()),
            static::KEY_VALID_TO => $this->formatDateTime($productConcreteTransfer->getValidTo()),
            static::KEY_ATTRIBUTES => $productConcreteTransfer->getAttributes() ?: [],
            static::KEY_SUPER_ATTRIBUTE_VALUES => $productConcreteTransfer->getSuperAttributeValues() ?: [],
            static::KEY_LOCALIZED_ATTRIBUTES => $this->extractLocalizedAttributes($productConcreteTransfer),
            static::KEY_PRICES => $this->extractPrices($productConcreteTransfer),
            static::KEY_IMAGE_SETS => $this->extractImageSets($productConcreteTransfer),
            static::KEY_STOCKS => $this->extractStocks($productConcreteTransfer),
            static::KEY_PRODUCT_BUNDLE => $this->extractProductBundle($productConcreteTransfer),
            static::KEY_PRODUCT_CLASS => $this->extractProductClasses($productConcreteTransfer),
            static::KEY_SHIPMENT_TYPE => $this->extractShipmentTypes($productConcreteTransfer),
            static::KEY_STORES => $this->extractStoreNames($productAbstractTransfer),
            static::KEY_TAX_SET => $this->extractTaxSet($taxSetTransfer),
            static::KEY_CATEGORIES => $this->extractCategories($productAbstractTransfer),
            static::KEY_NEW_FROM => $this->formatDateTime($productAbstractTransfer?->getNewFrom()),
            static::KEY_NEW_TO => $this->formatDateTime($productAbstractTransfer?->getNewTo()),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function extractStoreNames(?ProductAbstractTransfer $productAbstractTransfer): array
    {
        $storeRelationTransfer = $productAbstractTransfer?->getStoreRelation();

        if (!$storeRelationTransfer) {
            return [];
        }

        $storeNames = [];

        foreach ($storeRelationTransfer->getStores() as $storeTransfer) {
            $storeNames[] = $storeTransfer->getNameOrFail();
        }

        return $storeNames;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function extractTaxSet(?TaxSetTransfer $taxSetTransfer): ?array
    {
        if (!$taxSetTransfer) {
            return null;
        }

        return [
            static::KEY_UUID => $taxSetTransfer->getUuid(),
            static::KEY_NAME => $taxSetTransfer->getName(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function extractCategories(?ProductAbstractTransfer $productAbstractTransfer): array
    {
        if (!$productAbstractTransfer) {
            return [];
        }

        $categoryCollection = [];

        foreach ($productAbstractTransfer->getProductCategories() as $productCategoryTransfer) {
            $categoryCollection[] = $this->flattenCategory($productCategoryTransfer);
        }

        return $categoryCollection;
    }

    /**
     * @return array<string, mixed>
     */
    protected function flattenCategory(ProductCategoryTransfer $productCategoryTransfer): array
    {
        $categoryTransfer = $productCategoryTransfer->getCategory();
        $localizedAttributes = [];

        foreach ($categoryTransfer?->getLocalizedAttributes() ?? [] as $categoryLocalizedAttributesTransfer) {
            $localizedAttributes[] = [
                static::KEY_LOCALE_NAME => $categoryLocalizedAttributesTransfer->getLocale()?->getLocaleName() ?? '',
                static::KEY_NAME => $categoryLocalizedAttributesTransfer->getName(),
                static::KEY_URL => $categoryLocalizedAttributesTransfer->getUrl(),
            ];
        }

        return [
            static::KEY_UUID => $categoryTransfer?->getUuid(),
            static::KEY_CATEGORY_KEY => $categoryTransfer?->getCategoryKey(),
            static::KEY_IS_ACTIVE => $categoryTransfer?->getIsActive(),
            static::KEY_LOCALIZED_ATTRIBUTES => $localizedAttributes,
        ];
    }

    protected function formatDateTime(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (new DateTimeImmutable($value))->format(static::DATE_FORMAT);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function extractLocalizedAttributes(ProductConcreteTransfer $productConcreteTransfer): array
    {
        $localizedAttributesCollection = [];

        foreach ($productConcreteTransfer->getLocalizedAttributes() as $localizedAttributesTransfer) {
            $localizedAttributesItem = $localizedAttributesTransfer->toArray(false, true);
            unset($localizedAttributesItem[static::KEY_LOCALE]);

            $localizedAttributesItem[static::KEY_LOCALE_NAME] = $localizedAttributesTransfer->getLocale()?->getLocaleName() ?? '';
            $localizedAttributesCollection[] = $localizedAttributesItem;
        }

        return $localizedAttributesCollection;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function extractPrices(ProductConcreteTransfer $productConcreteTransfer): array
    {
        $priceCollection = [];

        foreach ($productConcreteTransfer->getPrices() as $priceProductTransfer) {
            $uuid = $priceProductTransfer->getMoneyValue()?->getUuid();

            if ($uuid !== null && isset($priceCollection[$uuid])) {
                continue;
            }

            $flattenedPrice = $this->flattenPrice($priceProductTransfer);
            $key = $uuid ?? count($priceCollection);
            $priceCollection[$key] = $flattenedPrice;
        }

        return array_values($priceCollection);
    }

    /**
     * @return array<string, mixed>
     */
    protected function flattenPrice(PriceProductTransfer $priceProductTransfer): array
    {
        $moneyValueTransfer = $priceProductTransfer->getMoneyValue();

        return [
            static::KEY_UUID => $moneyValueTransfer?->getUuid(),
            static::KEY_PRICE_TYPE_NAME => $priceProductTransfer->getPriceTypeName(),
            static::KEY_CURRENCY_CODE => $moneyValueTransfer?->getCurrency()?->getCode(),
            static::KEY_STORE_NAME => $moneyValueTransfer?->getStore()?->getName(),
            static::KEY_GROSS_AMOUNT => $moneyValueTransfer?->getGrossAmount(),
            static::KEY_NET_AMOUNT => $moneyValueTransfer?->getNetAmount(),
            static::KEY_VOLUME_PRICES => $this->decodeVolumePrices($moneyValueTransfer?->getPriceData()),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function decodeVolumePrices(?string $priceData): array
    {
        if ($priceData === null || $priceData === '') {
            return [];
        }

        $decoded = json_decode($priceData, true);

        if (!is_array($decoded)) {
            return [];
        }

        $storageVolumePrices = $decoded[static::KEY_VOLUME_PRICES_STORAGE] ?? [];
        $volumePrices = [];

        foreach ($storageVolumePrices as $storageVolumePrice) {
            $volumePrices[] = [
                static::KEY_QUANTITY => $storageVolumePrice[static::KEY_QUANTITY] ?? null,
                static::KEY_NET_PRICE => $storageVolumePrice[static::KEY_VOLUME_NET_PRICE] ?? null,
                static::KEY_GROSS_PRICE => $storageVolumePrice[static::KEY_VOLUME_GROSS_PRICE] ?? null,
            ];
        }

        return $volumePrices;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function extractImageSets(ProductConcreteTransfer $productConcreteTransfer): array
    {
        $imageSetCollection = [];

        foreach ($productConcreteTransfer->getImageSets() as $productImageSetTransfer) {
            $imageSetCollection[] = $this->flattenImageSet($productImageSetTransfer);
        }

        return $imageSetCollection;
    }

    /**
     * @return array<string, mixed>
     */
    protected function flattenImageSet(ProductImageSetTransfer $productImageSetTransfer): array
    {
        $images = [];

        foreach ($productImageSetTransfer->getProductImages() as $productImageTransfer) {
            $images[] = [
                static::KEY_EXTERNAL_URL_SMALL => $productImageTransfer->getExternalUrlSmall(),
                static::KEY_EXTERNAL_URL_LARGE => $productImageTransfer->getExternalUrlLarge(),
                static::KEY_ALT_TEXT_SMALL => $productImageTransfer->getAltTextSmall(),
                static::KEY_ALT_TEXT_LARGE => $productImageTransfer->getAltTextLarge(),
                static::KEY_SORT_ORDER => $productImageTransfer->getSortOrder(),
            ];
        }

        return [
            static::KEY_UUID => $productImageSetTransfer->getUuid(),
            static::KEY_NAME => $productImageSetTransfer->getName(),
            static::KEY_LOCALE_NAME => $productImageSetTransfer->getLocale()?->getLocaleName(),
            static::KEY_IMAGES => $images,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function extractStocks(ProductConcreteTransfer $productConcreteTransfer): array
    {
        $stockCollection = [];

        foreach ($productConcreteTransfer->getStocks() as $stockProductTransfer) {
            $stockCollection[] = $this->flattenStock($stockProductTransfer);
        }

        return $stockCollection;
    }

    /**
     * @return array<string, mixed>
     */
    protected function flattenStock(StockProductTransfer $stockProductTransfer): array
    {
        return [
            static::KEY_UUID => $stockProductTransfer->getStockUuid(),
            static::KEY_STOCK_NAME => $stockProductTransfer->getStockType(),
            static::KEY_QUANTITY => $stockProductTransfer->getQuantityOrFail()->toInt(),
            static::KEY_IS_NEVER_OUT_OF_STOCK => $stockProductTransfer->getIsNeverOutOfStock(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function extractProductBundle(ProductConcreteTransfer $productConcreteTransfer): array
    {
        $productBundleTransfer = $productConcreteTransfer->getProductBundle();

        if (!$productBundleTransfer) {
            return [];
        }

        $bundledProducts = [];

        foreach ($productBundleTransfer->getBundledProducts() as $productForBundleTransfer) {
            $bundledProducts[] = [
                static::KEY_SKU => $productForBundleTransfer->getSku(),
                static::KEY_QUANTITY => $productForBundleTransfer->getQuantity(),
            ];
        }

        return $bundledProducts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function extractProductClasses(ProductConcreteTransfer $productConcreteTransfer): array
    {
        $productClassCollection = [];

        foreach ($productConcreteTransfer->getProductClasses() as $productClassTransfer) {
            $productClassCollection[] = [
                static::KEY_KEY => $productClassTransfer->getKey(),
                static::KEY_NAME => $productClassTransfer->getName(),
            ];
        }

        return $productClassCollection;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function extractShipmentTypes(ProductConcreteTransfer $productConcreteTransfer): array
    {
        $shipmentTypeCollection = [];

        foreach ($productConcreteTransfer->getShipmentTypes() as $shipmentTypeTransfer) {
            $shipmentTypeCollection[] = [
                static::KEY_UUID => $shipmentTypeTransfer->getUuid(),
                static::KEY_NAME => $shipmentTypeTransfer->getName(),
            ];
        }

        return $shipmentTypeCollection;
    }
}
