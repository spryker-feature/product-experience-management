<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Mapper;

use Generated\Api\Backend\ProductsBackendResource;
use Generated\Shared\Transfer\ProductAbstractCollectionTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;

interface ProductConcreteTransferToResourceMapperInterface
{
    /**
     * The abstract-level properties (`stores`, `taxSet`, `categories`, `newFrom`, `newTo`) are read from the
     * parent abstract matching the concrete's abstract SKU. They stay empty when the collection holds no match.
     */
    public function mapProductConcreteTransferToResource(
        ProductConcreteTransfer $productConcreteTransfer,
        ProductAbstractCollectionTransfer $productAbstractCollectionTransfer,
    ): ProductsBackendResource;

    /**
     * @param iterable<\Generated\Shared\Transfer\ProductConcreteTransfer> $productConcreteTransfers
     *
     * @return array<\Generated\Api\Backend\ProductsBackendResource>
     */
    public function mapProductConcreteTransfersToResources(
        iterable $productConcreteTransfers,
        ProductAbstractCollectionTransfer $productAbstractCollectionTransfer,
    ): array;
}
