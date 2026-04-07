<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\ProductExperienceManagement\Extractor;

use Generated\Shared\Transfer\QuoteTransfer;

interface CartProductIdExtractorInterface
{
    /**
     * @return array<int>
     */
    public function extractProductAbstractIds(QuoteTransfer $quoteTransfer): array;

    /**
     * @return array<int>
     */
    public function extractProductConcreteIds(QuoteTransfer $quoteTransfer): array;
}
