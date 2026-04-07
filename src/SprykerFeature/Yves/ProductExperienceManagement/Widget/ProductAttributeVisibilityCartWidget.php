<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\ProductExperienceManagement\Widget;

use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Yves\Kernel\Widget\AbstractWidget;
use SprykerFeature\Shared\ProductExperienceManagement\ProductExperienceManagementConfig;

/**
 * @method \SprykerFeature\Yves\ProductExperienceManagement\ProductExperienceManagementFactory getFactory()
 */
class ProductAttributeVisibilityCartWidget extends AbstractWidget
{
    protected const string PARAMETER_ATTRIBUTES = 'attributes';

    public function __construct(
        ?QuoteTransfer $quoteTransfer,
        ?int $currentIdProductAbstract,
        ?int $currentIdProductConcrete,
    ) {
        $this->addVisibleAttributesParameter($quoteTransfer, $currentIdProductAbstract, $currentIdProductConcrete);
    }

    protected function addVisibleAttributesParameter(
        ?QuoteTransfer $quoteTransfer,
        ?int $currentIdProductAbstract,
        ?int $currentIdProductConcrete,
    ): void {
        $productAbstractIds = [];
        $productConcreteIds = [];

        if ($quoteTransfer !== null) {
            $cartProductIdExtractor = $this->getFactory()->createCartProductIdExtractor();
            $productAbstractIds = $cartProductIdExtractor->extractProductAbstractIds($quoteTransfer);
            $productConcreteIds = $cartProductIdExtractor->extractProductConcreteIds($quoteTransfer);
        }

        $attributes = $this->getFactory()
            ->createProductAttributeReader()
            ->getVisibleAttributes(
                $productAbstractIds,
                $productConcreteIds,
                ProductExperienceManagementConfig::VISIBILITY_TYPE_CART,
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
        return 'ProductAttributeVisibilityCartWidget';
    }

    public static function getTemplate(): string
    {
        return '@ProductExperienceManagement/views/product-attribute-visibility-cart/product-attribute-visibility-cart.twig';
    }
}
