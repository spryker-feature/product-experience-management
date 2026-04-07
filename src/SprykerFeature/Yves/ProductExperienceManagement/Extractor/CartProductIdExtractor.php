<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\ProductExperienceManagement\Extractor;

use Generated\Shared\Transfer\QuoteTransfer;

class CartProductIdExtractor implements CartProductIdExtractorInterface
{
    public function extractProductAbstractIds(QuoteTransfer $quoteTransfer): array
    {
        $productAbstractIds = [];

        foreach ($quoteTransfer->getItems() as $itemTransfer) {
            $idProductAbstract = $itemTransfer->getIdProductAbstract();

            if ($idProductAbstract === null) {
                continue;
            }

            $productAbstractIds[] = $idProductAbstract;
        }

        return array_unique($productAbstractIds);
    }

    public function extractProductConcreteIds(QuoteTransfer $quoteTransfer): array
    {
        $productConcreteIds = [];

        foreach ($quoteTransfer->getItems() as $itemTransfer) {
            $idProductConcrete = $itemTransfer->getId();

            if ($idProductConcrete === null) {
                continue;
            }

            $productConcreteIds[] = $idProductConcrete;
        }

        return array_unique($productConcreteIds);
    }
}
