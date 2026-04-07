<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\ProductExperienceManagement;

use Generated\Shared\Transfer\ProductAttributeStorageCollectionTransfer;
use Generated\Shared\Transfer\ProductAttributeStorageCriteriaTransfer;

interface ProductExperienceManagementClientInterface
{
    /**
     * Specification:
     * - Generates storage keys from `ProductAttributeStorageCriteriaTransfer.attributeKeys`.
     * - Fetches product attribute storage data from Redis using multi-get.
     * - Returns `ProductAttributeStorageCollectionTransfer` with `ProductAttributeStorageTransfer` objects.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ProductAttributeStorageCriteriaTransfer $productAttributeStorageCriteriaTransfer
     *
     * @return \Generated\Shared\Transfer\ProductAttributeStorageCollectionTransfer
     */
    public function getProductAttributeStorageCollection(
        ProductAttributeStorageCriteriaTransfer $productAttributeStorageCriteriaTransfer,
    ): ProductAttributeStorageCollectionTransfer;
}
