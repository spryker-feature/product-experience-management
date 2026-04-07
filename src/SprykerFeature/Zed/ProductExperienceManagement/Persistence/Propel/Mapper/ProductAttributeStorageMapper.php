<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\SynchronizationDataTransfer;

class ProductAttributeStorageMapper
{
    /**
     * @param array<\Orm\Zed\ProductExperienceManagement\Persistence\SpyProductAttributeStorage> $productAttributeStorageEntities
     *
     * @return array<\Generated\Shared\Transfer\SynchronizationDataTransfer>
     */
    public function mapProductAttributeStorageEntitiesToSynchronizationDataTransfers(array $productAttributeStorageEntities): array
    {
        $synchronizationDataTransfers = [];

        foreach ($productAttributeStorageEntities as $productAttributeStorageEntity) {
            /** @var string $data */
            $data = $productAttributeStorageEntity->getData();
            $synchronizationDataTransfers[] = (new SynchronizationDataTransfer())
                ->setData($data)
                ->setKey($productAttributeStorageEntity->getKey());
        }

        return $synchronizationDataTransfers;
    }
}
