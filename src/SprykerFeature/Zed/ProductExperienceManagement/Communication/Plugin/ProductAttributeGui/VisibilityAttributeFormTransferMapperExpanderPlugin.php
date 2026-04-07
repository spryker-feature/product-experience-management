<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttributeGui;

use Generated\Shared\Transfer\ProductManagementAttributeTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductAttributeGuiExtension\Dependency\Plugin\AttributeFormTransferMapperExpanderPluginInterface;
use Symfony\Component\Form\FormInterface;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Communication\ProductExperienceManagementCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig getConfig()
 */
class VisibilityAttributeFormTransferMapperExpanderPlugin extends AbstractPlugin implements AttributeFormTransferMapperExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Maps the visibility types form field to the product management attribute transfer.
     *
     * @api
     */
    public function expandTransfer(
        ProductManagementAttributeTransfer $productManagementAttributeTransfer,
        FormInterface $form,
    ): ProductManagementAttributeTransfer {
        return $this->getFactory()
            ->createVisibilityAttributeFormExpander()
            ->mapVisibilityToTransfer($productManagementAttributeTransfer, $form);
    }
}
