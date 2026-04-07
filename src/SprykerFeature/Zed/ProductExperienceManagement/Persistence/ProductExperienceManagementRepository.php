<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Persistence;

use Generated\Shared\Transfer\FilterTransfer;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;
use Spryker\Zed\Synchronization\Persistence\Propel\Formatter\SynchronizationDataTransferObjectFormatter;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Persistence\ProductExperienceManagementPersistenceFactory getFactory()
 */
class ProductExperienceManagementRepository extends AbstractRepository implements ProductExperienceManagementRepositoryInterface
{
    /**
     * @param array<int> $productManagementAttributeIds
     *
     * @return array<\Generated\Shared\Transfer\SynchronizationDataTransfer>
     */
    public function getSynchronizationDataTransfers(
        array $productManagementAttributeIds,
        ?FilterTransfer $filterTransfer = null,
    ): array {
        $query = $this->getFactory()->createProductAttributeStorageQuery();

        if ($productManagementAttributeIds) {
            $query->filterByFkProductManagementAttribute_In($productManagementAttributeIds);
        }

        $productAttributeStorageEntities = $this->buildQueryFromCriteria($query, $filterTransfer)
            ->setFormatter(SynchronizationDataTransferObjectFormatter::class)
            ->find();

        return $this->getFactory()
            ->createProductAttributeStorageMapper()
            ->mapProductAttributeStorageEntitiesToSynchronizationDataTransfers($productAttributeStorageEntities);
    }
}
