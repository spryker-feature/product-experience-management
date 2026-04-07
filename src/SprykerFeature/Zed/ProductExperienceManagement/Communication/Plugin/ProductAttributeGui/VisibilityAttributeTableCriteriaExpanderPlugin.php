<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttributeGui;

use Generated\Shared\Transfer\ProductAttributeTableCriteriaTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductAttributeGuiExtension\Dependency\Plugin\AttributeTableCriteriaExpanderPluginInterface;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Communication\ProductExperienceManagementCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig getConfig()
 */
class VisibilityAttributeTableCriteriaExpanderPlugin extends AbstractPlugin implements AttributeTableCriteriaExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Adds the visibility column to `ProductAttributeTableCriteriaTransfer` `withColumns`.
     * - Adds visibility type filter conditions to `ProductAttributeTableCriteriaTransfer` `queryConditions`.
     *
     * @api
     */
    public function expandProductAttributeTableCriteria(
        ProductAttributeTableCriteriaTransfer $productAttributeTableCriteriaTransfer,
    ): ProductAttributeTableCriteriaTransfer {
        return $this->getFactory()
            ->createVisibilityAttributeTableExpander()
            ->expandTableCriteria($productAttributeTableCriteriaTransfer);
    }
}
