<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\ProductExperienceManagement\Widget;

use Spryker\Yves\Kernel\Widget\AbstractWidget;
use SprykerFeature\Shared\ProductExperienceManagement\ProductExperienceManagementConfig;

/**
 * @method \SprykerFeature\Yves\ProductExperienceManagement\ProductExperienceManagementFactory getFactory()
 */
class ProductAttributeVisibilityPdpWidget extends AbstractWidget
{
    protected const string PARAMETER_ATTRIBUTES = 'attributes';

    public function __construct(
        int $currentIdProductAbstract,
        ?int $currentIdProductConcrete,
    ) {
        $this->addVisibleAttributesParameter($currentIdProductAbstract, $currentIdProductConcrete);
    }

    protected function addVisibleAttributesParameter(
        int $currentIdProductAbstract,
        ?int $currentIdProductConcrete,
    ): void {
        $attributes = $this->getFactory()
            ->createProductAttributeReader()
            ->getVisibleAttributes(
                [$currentIdProductAbstract],
                $currentIdProductConcrete !== null ? [$currentIdProductConcrete] : [],
                ProductExperienceManagementConfig::VISIBILITY_TYPE_PDP,
                $currentIdProductAbstract,
                $currentIdProductConcrete,
            );

        if (!$attributes) {
            return;
        }

        $this->addParameter(static::PARAMETER_ATTRIBUTES, $attributes);
    }

    public static function getName(): string
    {
        return 'ProductAttributeVisibilityPdpWidget';
    }

    public static function getTemplate(): string
    {
        return '@ProductExperienceManagement/views/product-attribute-visibility-pdp/product-attribute-visibility-pdp.twig';
    }
}
