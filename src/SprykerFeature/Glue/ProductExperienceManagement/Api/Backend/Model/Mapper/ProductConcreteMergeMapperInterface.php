<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Mapper;

use Generated\Api\Backend\ProductsBackendResource;
use Generated\Shared\Transfer\ProductConcreteTransfer;

interface ProductConcreteMergeMapperInterface
{
    public function mapResourceToProductConcreteTransfer(
        ProductsBackendResource $productsBackendResource,
        ProductConcreteTransfer $productConcreteTransfer,
    ): ProductConcreteTransfer;
}
