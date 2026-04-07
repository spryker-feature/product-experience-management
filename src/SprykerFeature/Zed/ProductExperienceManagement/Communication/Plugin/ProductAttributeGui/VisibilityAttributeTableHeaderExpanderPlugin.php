<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttributeGui;

use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductAttributeGuiExtension\Dependency\Plugin\AttributeTableHeaderExpanderPluginInterface;

class VisibilityAttributeTableHeaderExpanderPlugin extends AbstractPlugin implements AttributeTableHeaderExpanderPluginInterface
{
    protected const string COL_VISIBILITY = 'visibility';

    protected const string HEADER_DISPLAY_AT = 'Display At';

    /**
     * {@inheritDoc}
     * - Returns the visibility column header for the attribute table.
     *
     * @api
     *
     * @return array<string, string>
     */
    public function expandHeader(): array
    {
        return [static::COL_VISIBILITY => static::HEADER_DISPLAY_AT];
    }
}
