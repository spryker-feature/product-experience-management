<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttributeGui;

use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductAttributeGuiExtension\Dependency\Plugin\AttributeTableDataExpanderPluginInterface;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Communication\ProductExperienceManagementCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig getConfig()
 */
class VisibilityAttributeTableDataExpanderPlugin extends AbstractPlugin implements AttributeTableDataExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Renders visibility types as badge labels for the attribute table.
     *
     * @api
     *
     * @param array<string, mixed> $item
     *
     * @return array<string, mixed>
     */
    public function expandData(array $item): array
    {
        return $this->getFactory()
            ->createVisibilityAttributeTableExpander()
            ->expandTableData($item);
    }
}
