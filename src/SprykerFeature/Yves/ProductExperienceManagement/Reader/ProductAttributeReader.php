<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\ProductExperienceManagement\Reader;

use Generated\Shared\Transfer\ProductAttributeStorageCriteriaTransfer;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use Spryker\Client\Store\StoreClientInterface;
use SprykerFeature\Client\ProductExperienceManagement\ProductExperienceManagementClientInterface;

class ProductAttributeReader implements ProductAttributeReaderInterface
{
    protected const string KEY_ATTRIBUTES = 'attributes';

    protected const string KEY_ID_PRODUCT_CONCRETE = 'id_product_concrete';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    /**
     * @var array<int, array<string, string>>
     */
    protected static array $abstractAttributesCache = [];

    /**
     * @var array<int, array<string, string>>
     */
    protected static array $concreteAttributesCache = [];

    /**
     * @var array<string, array<string>>
     */
    protected static array $visibilityMapCache = [];

    public function __construct(
        protected readonly ProductStorageClientInterface $productStorageClient,
        protected readonly ProductExperienceManagementClientInterface $productExperienceManagementClient,
        protected readonly LocaleClientInterface $localeClient,
        protected readonly StoreClientInterface $storeClient,
    ) {
    }

    /**
     * @param array<int> $productAbstractIds
     * @param array<int> $productConcreteIds
     *
     * @return array<string, string>
     */
    public function getVisibleAttributes(
        array $productAbstractIds,
        array $productConcreteIds,
        string $visibilityType,
        ?int $currentIdProductAbstract,
        ?int $currentIdProductConcrete,
    ): array {
        $allAttributes = $this->resolveAllAttributes(
            $productConcreteIds,
            $productAbstractIds,
            $currentIdProductConcrete,
            $currentIdProductAbstract,
        );

        if (!$allAttributes) {
            return [];
        }

        $allAttributeKeys = $this->collectUniqueAttributeKeys($allAttributes);
        $this->preloadVisibilityMap($allAttributeKeys);

        $currentProductAttributes = $this->resolveCurrentProductAttributes(
            $currentIdProductConcrete,
            $currentIdProductAbstract,
        );

        return $this->filterByVisibility($currentProductAttributes, $visibilityType);
    }

    /**
     * @param array<int> $productConcreteIds
     * @param array<int> $productAbstractIds
     *
     * @return array<int, array<string, string>>
     */
    protected function resolveAllAttributes(
        array $productConcreteIds,
        array $productAbstractIds,
        ?int $currentIdProductConcrete,
        ?int $currentIdProductAbstract,
    ): array {
        if ($productConcreteIds) {
            $this->preloadConcreteAttributes($productConcreteIds);

            return array_intersect_key(static::$concreteAttributesCache, array_flip($productConcreteIds));
        }

        if ($productAbstractIds) {
            $this->preloadAbstractAttributes($productAbstractIds);

            return array_intersect_key(static::$abstractAttributesCache, array_flip($productAbstractIds));
        }

        if ($currentIdProductConcrete !== null) {
            $this->preloadConcreteAttributes([$currentIdProductConcrete]);

            return array_intersect_key(static::$concreteAttributesCache, [$currentIdProductConcrete => true]);
        }

        if ($currentIdProductAbstract !== null) {
            $this->preloadAbstractAttributes([$currentIdProductAbstract]);

            return array_intersect_key(static::$abstractAttributesCache, [$currentIdProductAbstract => true]);
        }

        return [];
    }

    /**
     * @param array<int, array<string, string>> $allAttributes
     *
     * @return array<string>
     */
    protected function collectUniqueAttributeKeys(array $allAttributes): array
    {
        $keys = [];

        foreach ($allAttributes as $attributes) {
            $keys[] = array_keys($attributes);
        }

        return array_unique(array_merge(...$keys));
    }

    /**
     * @return array<string, string>
     */
    protected function resolveCurrentProductAttributes(
        ?int $currentIdProductConcrete,
        ?int $currentIdProductAbstract,
    ): array {
        if ($currentIdProductConcrete !== null) {
            return static::$concreteAttributesCache[$currentIdProductConcrete] ?? [];
        }

        if ($currentIdProductAbstract !== null) {
            return static::$abstractAttributesCache[$currentIdProductAbstract] ?? [];
        }

        return [];
    }

    /**
     * @param array<int> $productAbstractIds
     */
    protected function preloadAbstractAttributes(array $productAbstractIds): void
    {
        $idsToLoad = array_diff($productAbstractIds, array_keys(static::$abstractAttributesCache));

        if (!$idsToLoad) {
            return;
        }

        $localeName = $this->localeClient->getCurrentLocale();
        $storeName = $this->storeClient->getCurrentStore()->getNameOrFail();
        $bulkData = $this->productStorageClient->getBulkProductAbstractStorageDataByProductAbstractIdsForLocaleNameAndStore(
            array_values($idsToLoad),
            $localeName,
            $storeName,
        );

        foreach ($bulkData as $storageData) {
            static::$abstractAttributesCache[$storageData[static::KEY_ID_PRODUCT_ABSTRACT]] = $storageData[static::KEY_ATTRIBUTES] ?? [];
        }
    }

    /**
     * @param array<int> $productConcreteIds
     */
    protected function preloadConcreteAttributes(array $productConcreteIds): void
    {
        $idsToLoad = array_diff($productConcreteIds, array_keys(static::$concreteAttributesCache));

        if (!$idsToLoad) {
            return;
        }

        $localeName = $this->localeClient->getCurrentLocale();
        $bulkData = $this->productStorageClient->getBulkProductConcreteStorageData(
            array_values($idsToLoad),
            $localeName,
        );

        foreach ($bulkData as $storageData) {
            static::$concreteAttributesCache[$storageData[static::KEY_ID_PRODUCT_CONCRETE]] = $storageData[static::KEY_ATTRIBUTES] ?? [];
        }
    }

    /**
     * @param array<string> $attributeKeys
     */
    protected function preloadVisibilityMap(array $attributeKeys): void
    {
        $keysToLoad = array_diff($attributeKeys, array_keys(static::$visibilityMapCache));

        if (!$keysToLoad) {
            return;
        }

        $productAttributeStorageCriteriaTransfer = (new ProductAttributeStorageCriteriaTransfer())
            ->setAttributeKeys(array_values($keysToLoad));

        $productAttributeStorageCollectionTransfer = $this->productExperienceManagementClient
            ->getProductAttributeStorageCollection($productAttributeStorageCriteriaTransfer);

        foreach ($keysToLoad as $key) {
            static::$visibilityMapCache[$key] = [];
        }

        foreach ($productAttributeStorageCollectionTransfer->getProductAttributeStorages() as $productAttributeStorageTransfer) {
            static::$visibilityMapCache[$productAttributeStorageTransfer->getKey()] = $productAttributeStorageTransfer->getVisibilityTypes();
        }
    }

    /**
     * @param array<string, string> $attributes
     *
     * @return array<string, string>
     */
    protected function filterByVisibility(array $attributes, string $visibilityType): array
    {
        $visibleAttributes = [];

        foreach ($attributes as $key => $value) {
            $visibilityTypes = static::$visibilityMapCache[$key] ?? [];

            if (!in_array($visibilityType, $visibilityTypes, true)) {
                continue;
            }

            $visibleAttributes[$key] = $value;
        }

        return $visibleAttributes;
    }
}
