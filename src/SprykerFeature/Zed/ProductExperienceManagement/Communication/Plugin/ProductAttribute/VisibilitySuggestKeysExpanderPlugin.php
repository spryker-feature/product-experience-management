<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttribute;

use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductAttributeExtension\Dependency\Plugin\SuggestKeysExpanderPluginInterface;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Business\ProductExperienceManagementBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig getConfig()
 */
class VisibilitySuggestKeysExpanderPlugin extends AbstractPlugin implements SuggestKeysExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Reads `visibility` from each suggest key item (already fetched by the query expander).
     * - Appends formatted HTML badge labels to `extension_column_values[]` on each item.
     *
     * @api
     *
     * @param array<array<string, mixed>> $suggestKeys
     *
     * @return array<array<string, mixed>>
     */
    public function expandSuggestKeys(array $suggestKeys): array
    {
        return $this->getBusinessFactory()
            ->createProductAttributeVisibilityExpander()
            ->expandSuggestKeysWithVisibility($suggestKeys);
    }
}
