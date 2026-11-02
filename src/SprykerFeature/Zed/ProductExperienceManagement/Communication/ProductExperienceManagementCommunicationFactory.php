<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication;

use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Expander\VisibilityAttributeFormExpander;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Expander\VisibilityAttributeFormExpanderInterface;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Expander\VisibilityAttributeTableExpander;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Expander\VisibilityAttributeTableExpanderInterface;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Business\ProductExperienceManagementFacade getFacade()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig getConfig()
 */
class ProductExperienceManagementCommunicationFactory extends AbstractCommunicationFactory
{
    public function createVisibilityAttributeFormExpander(): VisibilityAttributeFormExpanderInterface
    {
        return new VisibilityAttributeFormExpander(
            $this->getConfig(),
        );
    }

    public function createVisibilityAttributeTableExpander(): VisibilityAttributeTableExpanderInterface
    {
        return new VisibilityAttributeTableExpander(
            $this->getConfig(),
        );
    }
}
