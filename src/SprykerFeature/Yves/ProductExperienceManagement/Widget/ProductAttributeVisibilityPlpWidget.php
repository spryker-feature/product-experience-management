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
class ProductAttributeVisibilityPlpWidget extends AbstractWidget
{
    protected const string KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    /**
     * @param array<int, array<string, mixed>> $products
     */
    public function __construct(
        array $products,
        ?int $currentIdProductAbstract,
    ) {
        $this->setVisibleAttributes($products, $currentIdProductAbstract);
    }

    /**
     * @param array<int, array<string, mixed>> $products
     */
    protected function setVisibleAttributes(
        array $products,
        ?int $currentIdProductAbstract,
    ): void {
        $allProductAbstractIds = array_values(array_filter(
            array_column($products, static::KEY_ID_PRODUCT_ABSTRACT),
        ));

        $attributes = $this->getFactory()
            ->createProductAttributeReader()
            ->getVisibleAttributes(
                $allProductAbstractIds,
                [],
                ProductExperienceManagementConfig::VISIBILITY_TYPE_PLP,
                $currentIdProductAbstract,
                null,
            );

        if (!$attributes) {
            return;
        }

        $this->addParameter('attributes', $attributes);
    }

    public static function getName(): string
    {
        return 'ProductAttributeVisibilityPlpWidget';
    }

    public static function getTemplate(): string
    {
        return '@ProductExperienceManagement/views/product-attribute-visibility-plp/product-attribute-visibility-plp.twig';
    }
}
