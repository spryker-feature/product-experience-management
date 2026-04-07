<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement;

use Spryker\Zed\Kernel\AbstractBundleConfig;

/**
 * @method \SprykerFeature\Shared\ProductExperienceManagement\ProductExperienceManagementConfig getSharedConfig()
 */
class ProductExperienceManagementConfig extends AbstractBundleConfig
{
    /**
     * @api
     */
    public function getProductAttributeSynchronizationPoolName(): string
    {
        return 'synchronizationPool';
    }

    /**
     * @api
     */
    public function getEventQueueName(): ?string
    {
        return null;
    }

    /**
     * @api
     *
     * @return array<string>
     */
    public function getAvailableVisibilityTypes(): array
    {
        return $this->getSharedConfig()->getAvailableVisibilityTypes();
    }

    /**
     * @api
     *
     * @return array<string>
     */
    public function getDefaultVisibilityTypes(): array
    {
        return $this->getSharedConfig()->getDefaultVisibilityTypes();
    }
}
