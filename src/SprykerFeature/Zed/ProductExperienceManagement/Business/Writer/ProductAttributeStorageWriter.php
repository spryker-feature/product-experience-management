<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\Writer;

use Generated\Shared\Transfer\ProductAttributeStorageTransfer;
use Generated\Shared\Transfer\ProductManagementAttributeConditionsTransfer;
use Generated\Shared\Transfer\ProductManagementAttributeCriteriaTransfer;
use Generated\Shared\Transfer\ProductManagementAttributeTransfer;
use Spryker\Zed\EventBehavior\Business\EventBehaviorFacadeInterface;
use Spryker\Zed\ProductAttribute\Business\ProductAttributeFacadeInterface;
use SprykerFeature\Zed\ProductExperienceManagement\Persistence\ProductExperienceManagementEntityManagerInterface;

class ProductAttributeStorageWriter implements ProductAttributeStorageWriterInterface
{
    public function __construct(
        protected readonly ProductAttributeFacadeInterface $productAttributeFacade,
        protected readonly EventBehaviorFacadeInterface $eventBehaviorFacade,
        protected readonly ProductExperienceManagementEntityManagerInterface $entityManager,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     */
    public function writeProductAttributeStorageCollectionByEvents(array $eventEntityTransfers): void
    {
        $productManagementAttributeIds = $this->eventBehaviorFacade->getEventTransferIds($eventEntityTransfers);

        if (!$productManagementAttributeIds) {
            return;
        }

        $productManagementAttributeCriteriaTransfer = (new ProductManagementAttributeCriteriaTransfer())
            ->setProductManagementAttributeConditions(
                (new ProductManagementAttributeConditionsTransfer())
                    ->setProductManagementAttributeIds($productManagementAttributeIds),
            );

        $productManagementAttributeCollectionTransfer = $this->productAttributeFacade
            ->getProductManagementAttributeCollection($productManagementAttributeCriteriaTransfer);

        foreach ($productManagementAttributeCollectionTransfer->getProductManagementAttributes() as $productManagementAttributeTransfer) {
            $productAttributeStorageTransfer = $this->mapToStorageTransfer($productManagementAttributeTransfer);

            $this->entityManager->saveProductAttributeStorage(
                $productManagementAttributeTransfer->getIdProductManagementAttributeOrFail(),
                $productManagementAttributeTransfer->getKeyOrFail(),
                $productAttributeStorageTransfer,
            );
        }
    }

    protected function mapToStorageTransfer(
        ProductManagementAttributeTransfer $productManagementAttributeTransfer,
    ): ProductAttributeStorageTransfer {
        $visibility = $productManagementAttributeTransfer->getVisibility() ?? '';
        $visibilityTypes = $visibility !== '' ? array_map('trim', explode(',', $visibility)) : [];

        return (new ProductAttributeStorageTransfer())
            ->setKey($productManagementAttributeTransfer->getKey())
            ->setInputType($productManagementAttributeTransfer->getInputType())
            ->setIsSuper($productManagementAttributeTransfer->getIsSuper())
            ->setVisibilityTypes($visibilityTypes);
    }
}
