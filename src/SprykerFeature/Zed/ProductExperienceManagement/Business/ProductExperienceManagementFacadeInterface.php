<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Business;

interface ProductExperienceManagementFacadeInterface
{
    /**
     * Specification:
     * - Returns available visibility types for product attributes (e.g. PDP, PLP, Cart).
     *
     * @api
     *
     * @return array<string>
     */
    public function getAvailableVisibilityTypes(): array;
}
