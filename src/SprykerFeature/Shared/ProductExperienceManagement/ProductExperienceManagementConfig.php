<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Shared\ProductExperienceManagement;

use Spryker\Shared\Kernel\AbstractSharedConfig;

class ProductExperienceManagementConfig extends AbstractSharedConfig
{
    /**
     * Specification:
     * - Defines the resource name used for product attribute storage key generation.
     *
     * @api
     *
     * @var string
     */
    public const string PRODUCT_ATTRIBUTE_RESOURCE_NAME = 'product_attribute';

    /**
     * Specification:
     *  - Queue name as used for publishing merchant events.
     *
     * @api
     *
     * @var string
     */
    public const string PUBLISH_PRODUCT_ATTRIBUTE = 'publish.product_attribute';

    /**
     * Specification:
     * - Defines the queue name for synchronizing product attribute data to storage.
     *
     * @api
     *
     * @var string
     */
    public const string PRODUCT_ATTRIBUTE_SYNC_STORAGE_QUEUE = 'sync.storage.product_attribute';

    /**
     * Specification:
     * - Defines the error queue name for failed product attribute storage synchronization messages.
     *
     * @api
     *
     * @var string
     */
    public const string PRODUCT_ATTRIBUTE_SYNC_STORAGE_ERROR_QUEUE = 'sync.storage.product_attribute.error';

    /**
     * Specification:
     * - Defines the event name used for triggering product attribute publish.
     *
     * @api
     *
     * @var string
     */
    public const string PRODUCT_ATTRIBUTE_PUBLISH = 'ProductExperienceManagement.product_attribute.publish';

    /**
     * Specification:
     * - Defines the event name triggered when a product management attribute entity is created.
     *
     * @api
     *
     * @var string
     */
    public const string ENTITY_SPY_PRODUCT_MANAGEMENT_ATTRIBUTE_CREATE = 'Entity.spy_product_management_attribute.create';

    /**
     * Specification:
     * - Defines the event name triggered when a product management attribute entity is updated.
     *
     * @api
     *
     * @var string
     */
    public const string ENTITY_SPY_PRODUCT_MANAGEMENT_ATTRIBUTE_UPDATE = 'Entity.spy_product_management_attribute.update';

    /**
     * Specification:
     * - Represents the Product Detail Page visibility type for product attributes.
     *
     * @api
     *
     * @var string
     */
    public const string VISIBILITY_TYPE_PDP = 'PDP';

    /**
     * Specification:
     * - Represents the Product Listing Page visibility type for product attributes.
     *
     * @api
     *
     * @var string
     */
    public const string VISIBILITY_TYPE_PLP = 'PLP';

    /**
     * Specification:
     * - Represents the Cart visibility type for product attributes.
     *
     * @api
     *
     * @var string
     */
    public const string VISIBILITY_TYPE_CART = 'Cart';

    /**
     * Specification:
     * - Represents the internal-only visibility type, meaning the attribute is not displayed to customers.
     *
     * @api
     *
     * @var string
     */
    public const string VISIBILITY_TYPE_INTERNAL = 'None';

    /**
     * Specification:
     * - Returns available visibility types for product attributes.
     *
     * @api
     *
     * @return array<string>
     */
    public function getAvailableVisibilityTypes(): array
    {
        return [
            static::VISIBILITY_TYPE_PDP,
            static::VISIBILITY_TYPE_PLP,
            static::VISIBILITY_TYPE_CART,
        ];
    }

    /**
     * Specification:
     * - Returns default visibility types pre-selected for new product attributes.
     *
     * @api
     *
     * @return array<string>
     */
    public function getDefaultVisibilityTypes(): array
    {
        return [
            static::VISIBILITY_TYPE_PDP,
        ];
    }
}
