<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Mapper;

use Generated\Api\Backend\ProductsBackendResource;
use Generated\Shared\Transfer\ProductAbstractTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;

interface ProductAbstractCreateMapperInterface
{
    public function mapResourceToProductAbstractTransfer(
        ProductsBackendResource $productsBackendResource,
        ProductConcreteTransfer $productConcreteTransfer,
        ProductAbstractTransfer $productAbstractTransfer,
    ): ProductAbstractTransfer;
}
