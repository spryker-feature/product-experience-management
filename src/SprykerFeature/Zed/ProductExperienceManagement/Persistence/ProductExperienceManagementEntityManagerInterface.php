<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Persistence;

use Generated\Shared\Transfer\ProductAttributeStorageTransfer;

interface ProductExperienceManagementEntityManagerInterface
{
    public function saveProductAttributeStorage(
        int $idProductManagementAttribute,
        string $attributeKey,
        ProductAttributeStorageTransfer $productAttributeStorageTransfer,
    ): void;
}
