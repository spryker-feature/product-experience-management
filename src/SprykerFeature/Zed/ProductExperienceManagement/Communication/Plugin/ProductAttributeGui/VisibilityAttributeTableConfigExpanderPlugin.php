<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttributeGui;

use Spryker\Zed\Gui\Communication\Table\TableConfiguration;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductAttributeGuiExtension\Dependency\Plugin\AttributeTableConfigExpanderPluginInterface;

class VisibilityAttributeTableConfigExpanderPlugin extends AbstractPlugin implements AttributeTableConfigExpanderPluginInterface
{
    protected const string COL_VISIBILITY = 'visibility';

    /**
     * {@inheritDoc}
     * - Adds the visibility column as a raw column to the attribute table configuration.
     *
     * @api
     */
    public function expandConfig(TableConfiguration $config): TableConfiguration
    {
        $config->setRawColumns(
            array_merge($config->getRawColumns(), [static::COL_VISIBILITY]),
        );

        return $config;
    }
}
