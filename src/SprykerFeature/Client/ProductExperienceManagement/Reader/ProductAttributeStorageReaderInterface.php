<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\ProductExperienceManagement\Reader;

use Generated\Shared\Transfer\ProductAttributeStorageCollectionTransfer;
use Generated\Shared\Transfer\ProductAttributeStorageCriteriaTransfer;

interface ProductAttributeStorageReaderInterface
{
    public function getProductAttributeStorageCollection(
        ProductAttributeStorageCriteriaTransfer $productAttributeStorageCriteriaTransfer,
    ): ProductAttributeStorageCollectionTransfer;
}
