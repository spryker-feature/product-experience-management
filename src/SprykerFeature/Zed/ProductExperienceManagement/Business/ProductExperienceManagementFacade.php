<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Business;

use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Business\ProductExperienceManagementBusinessFactory getFactory()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Persistence\ProductExperienceManagementEntityManagerInterface getEntityManager()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Persistence\ProductExperienceManagementRepositoryInterface getRepository()
 */
class ProductExperienceManagementFacade extends AbstractFacade implements ProductExperienceManagementFacadeInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<string>
     */
    public function getAvailableVisibilityTypes(): array
    {
        return $this->getFactory()
            ->getConfig()
            ->getAvailableVisibilityTypes();
    }
}
