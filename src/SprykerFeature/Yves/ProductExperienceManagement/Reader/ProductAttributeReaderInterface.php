<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\ProductExperienceManagement\Reader;

interface ProductAttributeReaderInterface
{
    /**
     * @param array<int> $productAbstractIds
     * @param array<int> $productConcreteIds
     *
     * @return array<string, string>
     */
    public function getVisibleAttributes(
        array $productAbstractIds,
        array $productConcreteIds,
        string $visibilityType,
        ?int $currentIdProductAbstract,
        ?int $currentIdProductConcrete,
    ): array;
}
