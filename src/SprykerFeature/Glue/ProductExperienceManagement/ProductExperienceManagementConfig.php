<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\ProductExperienceManagement;

use Spryker\Glue\Kernel\AbstractBundleConfig;

/**
 * @method \SprykerFeature\Shared\ProductExperienceManagement\ProductExperienceManagementConfig getSharedConfig()
 */
class ProductExperienceManagementConfig extends AbstractBundleConfig
{
    /**
     * Specification:
     * - Returns the sprintf pattern used to generate an abstract product SKU from a concrete product SKU.
     * - The `%s` placeholder is replaced with the concrete SKU.
     *
     * @api
     */
    public function getAbstractSkuPattern(): string
    {
        return $this->getSharedConfig()->getAbstractSkuPattern();
    }
}
