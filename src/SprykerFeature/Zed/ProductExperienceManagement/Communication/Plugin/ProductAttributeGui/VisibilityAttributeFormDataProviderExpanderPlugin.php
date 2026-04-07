<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttributeGui;

use Generated\Shared\Transfer\ProductManagementAttributeTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductAttributeGuiExtension\Dependency\Plugin\AttributeFormDataProviderExpanderPluginInterface;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Communication\ProductExperienceManagementCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig getConfig()
 */
class VisibilityAttributeFormDataProviderExpanderPlugin extends AbstractPlugin implements AttributeFormDataProviderExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     * - In create mode, sets default visibility types from configuration.
     * - In edit mode, parses the comma-separated visibility string from the transfer into an array.
     *
     * @api
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function expandData(array $data, ?ProductManagementAttributeTransfer $productManagementAttributeTransfer): array
    {
        return $this->getFactory()
            ->createVisibilityAttributeFormExpander()
            ->expandFormData($data, $productManagementAttributeTransfer);
    }

    /**
     * {@inheritDoc}
     * - Adds visibility type choices to the form options.
     *
     * @api
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function expandOptions(array $options, ?int $idProductManagementAttribute): array
    {
        return $this->getFactory()
            ->createVisibilityAttributeFormExpander()
            ->expandFormOptions($options);
    }
}
