<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement;

use Spryker\Zed\Kernel\AbstractBundleConfig;

/**
 * @method \SprykerFeature\Shared\ProductExperienceManagement\ProductExperienceManagementConfig getSharedConfig()
 */
class ProductExperienceManagementConfig extends AbstractBundleConfig
{
    /**
     * Specification:
     * - Defines the concrete product status that activates a concrete product.
     *
     * @see \Orm\Zed\Product\Persistence\Map\SpyProductTableMap::COL_IS_ACTIVE
     */
    protected const string PRODUCT_CONCRETE_STATUS_ACTIVE = 'active';

    /**
     * Specification:
     * - Defines the concrete product status that deactivates a concrete product.
     *
     * @see \Orm\Zed\Product\Persistence\Map\SpyProductTableMap::COL_IS_ACTIVE
     */
    protected const string PRODUCT_CONCRETE_STATUS_INACTIVE = 'inactive';

    /**
     * Specification:
     * - Returns the product status values accepted for concrete product rows during CSV import.
     * - Abstract product approval statuses are not accepted for concrete product rows.
     *
     * @api
     *
     * @see \Orm\Zed\Product\Persistence\Map\SpyProductTableMap::COL_IS_ACTIVE
     *
     * @return array<string>
     */
    public function getProductConcreteStatuses(): array
    {
        return [
            static::PRODUCT_CONCRETE_STATUS_ACTIVE,
            static::PRODUCT_CONCRETE_STATUS_INACTIVE,
        ];
    }

    /**
     * Specification:
     * - Returns the concrete product status value that activates a concrete product during CSV import.
     * - Any other accepted concrete product status leaves the concrete product inactive.
     *
     * @api
     *
     * @see \Orm\Zed\Product\Persistence\Map\SpyProductTableMap::COL_IS_ACTIVE
     */
    public function getProductConcreteStatusActive(): string
    {
        return static::PRODUCT_CONCRETE_STATUS_ACTIVE;
    }

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
