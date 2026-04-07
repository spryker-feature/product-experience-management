<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Persistence;

use Generated\Shared\Transfer\ProductAttributeStorageTransfer;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Persistence\ProductExperienceManagementPersistenceFactory getFactory()
 */
class ProductExperienceManagementEntityManager extends AbstractEntityManager implements ProductExperienceManagementEntityManagerInterface
{
    public function saveProductAttributeStorage(
        int $idProductManagementAttribute,
        string $attributeKey,
        ProductAttributeStorageTransfer $productAttributeStorageTransfer,
    ): void {
        $productAttributeStorageEntity = $this->getFactory()
            ->createProductAttributeStorageQuery()
            ->filterByFkProductManagementAttribute($idProductManagementAttribute)
            ->findOneOrCreate();

        $productAttributeStorageEntity
            ->setAttributeKey($attributeKey)
            ->setData($productAttributeStorageTransfer->toArray())
            ->save();
    }
}
