<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\Publisher;

use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\PublisherExtension\Dependency\Plugin\PublisherPluginInterface;
use SprykerFeature\Shared\ProductExperienceManagement\ProductExperienceManagementConfig;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Business\ProductExperienceManagementFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Business\ProductExperienceManagementBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig getConfig()
 */
class ProductAttributeWritePublisherPlugin extends AbstractPlugin implements PublisherPluginInterface
{
    /**
     * {@inheritDoc}
     * - Extracts product management attribute IDs from event transfers.
     * - Writes product attribute data to storage.
     *
     * @api
     *
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     * @param string $eventName
     */
    public function handleBulk(array $eventEntityTransfers, $eventName): void
    {
        $this->getBusinessFactory()
            ->createProductAttributeStorageWriter()
            ->writeProductAttributeStorageCollectionByEvents($eventEntityTransfers);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<string>
     */
    public function getSubscribedEvents(): array
    {
        return [
            ProductExperienceManagementConfig::PRODUCT_ATTRIBUTE_PUBLISH,
            ProductExperienceManagementConfig::ENTITY_SPY_PRODUCT_MANAGEMENT_ATTRIBUTE_CREATE,
            ProductExperienceManagementConfig::ENTITY_SPY_PRODUCT_MANAGEMENT_ATTRIBUTE_UPDATE,
        ];
    }
}
