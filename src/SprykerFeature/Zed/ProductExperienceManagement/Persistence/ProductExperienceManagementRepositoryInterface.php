<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Persistence;

use Generated\Shared\Transfer\FilterTransfer;

interface ProductExperienceManagementRepositoryInterface
{
    /**
     * @param array<int> $productManagementAttributeIds
     *
     * @return array<\Generated\Shared\Transfer\SynchronizationDataTransfer>
     */
    public function getSynchronizationDataTransfers(
        array $productManagementAttributeIds,
        ?FilterTransfer $filterTransfer = null,
    ): array;
}
