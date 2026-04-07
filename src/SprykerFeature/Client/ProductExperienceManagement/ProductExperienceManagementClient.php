<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\ProductExperienceManagement;

use Generated\Shared\Transfer\ProductAttributeStorageCollectionTransfer;
use Generated\Shared\Transfer\ProductAttributeStorageCriteriaTransfer;
use Spryker\Client\Kernel\AbstractClient;

/**
 * @method \SprykerFeature\Client\ProductExperienceManagement\ProductExperienceManagementFactory getFactory()
 */
class ProductExperienceManagementClient extends AbstractClient implements ProductExperienceManagementClientInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getProductAttributeStorageCollection(
        ProductAttributeStorageCriteriaTransfer $productAttributeStorageCriteriaTransfer,
    ): ProductAttributeStorageCollectionTransfer {
        return $this->getFactory()
            ->createProductAttributeStorageReader()
            ->getProductAttributeStorageCollection($productAttributeStorageCriteriaTransfer);
    }
}
