<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\Export\PlaceholderProvider;

use Orm\Zed\ProductImage\Persistence\Map\SpyProductImageSetToProductImageTableMap;
use Orm\Zed\ProductImage\Persistence\SpyProductImageSetToProductImageQuery;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use Spryker\Zed\PriceProduct\Business\PriceProductFacadeInterface;
use Spryker\Zed\Stock\Business\StockFacadeInterface;
use Spryker\Zed\Store\Business\StoreFacadeInterface;

class ProductCsvExportPlaceholderProvider implements ExportPlaceholderProviderInterface
{
    protected const string PLACEHOLDER_LOCALE = 'locale';

    protected const string PLACEHOLDER_STORE = 'store';

    protected const string PLACEHOLDER_CURRENCY = 'currency';

    protected const string PLACEHOLDER_PRICE_MODE = 'price_mode';

    protected const string PLACEHOLDER_PRICE_DIMENSION = 'price_dimension';

    protected const string PLACEHOLDER_WAREHOUSE = 'warehouse';

    protected const string PLACEHOLDER_SIZE = 'size';

    protected const string PLACEHOLDER_SORT_ORDER = 'sort_order';

    protected const string IMAGE_SIZE_SMALL = 'Small';

    protected const string IMAGE_SIZE_LARGE = 'Large';

    protected const string DEFAULT_SORT_ORDER = '0';

    protected const string ALIAS_MIN_SORT_ORDER = 'minSortOrder';

    protected const string ALIAS_MAX_SORT_ORDER = 'maxSortOrder';

    protected const string DEFAULT_PRICE_MODE = 'Default';

    public function __construct(
        protected StoreFacadeInterface $storeFacade,
        protected LocaleFacadeInterface $localeFacade,
        protected PriceProductFacadeInterface $priceProductFacade,
        protected StockFacadeInterface $stockFacade,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getPlaceholderValues(): array
    {
        $stores = $this->storeFacade->getAllStores();
        $stores = $this->localeFacade->expandStoreTransfersWithLocales($stores);

        return [
            static::PLACEHOLDER_LOCALE => $this->resolveLocales($stores),
            static::PLACEHOLDER_STORE => $this->resolveStoreNames($stores),
            static::PLACEHOLDER_CURRENCY => $this->resolveCurrencies($stores),
            static::PLACEHOLDER_PRICE_MODE => $this->resolvePriceModes(),
            static::PLACEHOLDER_PRICE_DIMENSION => ['Gross', 'Net'],
            static::PLACEHOLDER_WAREHOUSE => $this->resolveWarehouses(),
            static::PLACEHOLDER_SIZE => [static::IMAGE_SIZE_SMALL, static::IMAGE_SIZE_LARGE],
            static::PLACEHOLDER_SORT_ORDER => $this->resolveImageSortOrders(),
        ];
    }

    /**
     * Collects unique locales from all stores, formatted as EN-US.
     *
     * @param array<\Generated\Shared\Transfer\StoreTransfer> $stores
     *
     * @return array<string>
     */
    protected function resolveLocales(array $stores): array
    {
        $locales = [];

        foreach ($stores as $storeTransfer) {
            foreach ($storeTransfer->getAvailableLocaleIsoCodes() as $localeCode) {
                $formatted = strtoupper(str_replace('_', '-', $localeCode));
                $locales[$formatted] = true;
            }
        }

        return array_keys($locales);
    }

    /**
     * @param array<\Generated\Shared\Transfer\StoreTransfer> $stores
     *
     * @return array<string>
     */
    protected function resolveStoreNames(array $stores): array
    {
        $names = [];

        foreach ($stores as $storeTransfer) {
            $names[] = $storeTransfer->getNameOrFail();
        }

        return array_unique($names);
    }

    /**
     * Collects unique currency codes from all stores.
     *
     * @param array<\Generated\Shared\Transfer\StoreTransfer> $stores
     *
     * @return array<string>
     */
    protected function resolveCurrencies(array $stores): array
    {
        $currencies = [];

        foreach ($stores as $storeTransfer) {
            foreach ($storeTransfer->getAvailableCurrencyIsoCodes() as $currencyCode) {
                $currencies[$currencyCode] = true;
            }
        }

        return array_keys($currencies);
    }

    /**
     * @return array<string>
     */
    protected function resolvePriceModes(): array
    {
        $modes = [];

        foreach ($this->priceProductFacade->getPriceTypeValues() as $priceTypeTransfer) {
            $modes[] = ucfirst(strtolower($priceTypeTransfer->getNameOrFail()));
        }

        return $modes !== [] ? $modes : [static::DEFAULT_PRICE_MODE];
    }

    /**
     * @return array<string>
     */
    protected function resolveWarehouses(): array
    {
        return $this->stockFacade->getAvailableStockTypes();
    }

    /**
     * Resolves distinct image sort orders from the database.
     *
     * @return array<string>
     */
    protected function resolveImageSortOrders(): array
    {
        $result = SpyProductImageSetToProductImageQuery::create()
            ->withColumn(sprintf('MIN(%s)', SpyProductImageSetToProductImageTableMap::COL_SORT_ORDER), static::ALIAS_MIN_SORT_ORDER)
            ->withColumn(sprintf('MAX(%s)', SpyProductImageSetToProductImageTableMap::COL_SORT_ORDER), static::ALIAS_MAX_SORT_ORDER)
            ->select([static::ALIAS_MIN_SORT_ORDER, static::ALIAS_MAX_SORT_ORDER])
            ->find()
            ->getData();

        /** @var array<string, mixed>|null $result */
        $result = $result[0] ?? null;

        if ($result === null) {
            return [static::DEFAULT_SORT_ORDER];
        }

        return array_map('strval', range((int)$result[static::ALIAS_MIN_SORT_ORDER], (int)$result[static::ALIAS_MAX_SORT_ORDER]));
    }
}
