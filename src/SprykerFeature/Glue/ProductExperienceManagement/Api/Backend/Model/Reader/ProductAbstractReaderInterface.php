<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Reader;

use Generated\Shared\Transfer\ProductAbstractCollectionTransfer;

interface ProductAbstractReaderInterface
{
    /**
     * @param array<string|null> $productAbstractSkus
     */
    public function getProductAbstractCollectionBySkus(array $productAbstractSkus): ProductAbstractCollectionTransfer;
}
