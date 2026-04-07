<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\ProductExperienceManagement\Reader;

use Generated\Shared\Transfer\ProductAttributeStorageCollectionTransfer;
use Generated\Shared\Transfer\ProductAttributeStorageCriteriaTransfer;
use Generated\Shared\Transfer\ProductAttributeStorageTransfer;
use Generated\Shared\Transfer\SynchronizationDataTransfer;
use Spryker\Client\Storage\StorageClientInterface;
use Spryker\Service\Synchronization\SynchronizationServiceInterface;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use SprykerFeature\Shared\ProductExperienceManagement\ProductExperienceManagementConfig;

class ProductAttributeStorageReader implements ProductAttributeStorageReaderInterface
{
    public function __construct(
        protected readonly SynchronizationServiceInterface $synchronizationService,
        protected readonly StorageClientInterface $storageClient,
        protected readonly UtilEncodingServiceInterface $utilEncodingService,
    ) {
    }

    public function getProductAttributeStorageCollection(
        ProductAttributeStorageCriteriaTransfer $productAttributeStorageCriteriaTransfer,
    ): ProductAttributeStorageCollectionTransfer {
        $productAttributeStorageCollectionTransfer = new ProductAttributeStorageCollectionTransfer();
        $attributeKeys = $productAttributeStorageCriteriaTransfer->getAttributeKeys();

        if (!$attributeKeys) {
            return $productAttributeStorageCollectionTransfer;
        }

        $storageKeys = $this->generateKeys($attributeKeys);
        $storageData = $this->storageClient->getMulti($storageKeys);

        foreach ($storageData as $data) {
            if (!$data) {
                continue;
            }

            if (is_string($data)) {
                $data = $this->utilEncodingService->decodeJson($data, true);
            }

            if (!$data) {
                continue;
            }

            $productAttributeStorageCollectionTransfer->addProductAttributeStorage(
                (new ProductAttributeStorageTransfer())->fromArray($data, true),
            );
        }

        return $productAttributeStorageCollectionTransfer;
    }

    /**
     * @param array<string> $attributeKeys
     *
     * @return array<string>
     */
    protected function generateKeys(array $attributeKeys): array
    {
        $keyBuilder = $this->synchronizationService
            ->getStorageKeyBuilder(ProductExperienceManagementConfig::PRODUCT_ATTRIBUTE_RESOURCE_NAME);

        $storageKeys = [];

        foreach ($attributeKeys as $attributeKey) {
            $synchronizationDataTransfer = (new SynchronizationDataTransfer())
                ->setReference($attributeKey);

            $storageKeys[] = $keyBuilder->generateKey($synchronizationDataTransfer);
        }

        return $storageKeys;
    }
}
