<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Reader;

use Generated\Shared\Transfer\ProductAbstractCollectionTransfer;
use Generated\Shared\Transfer\ProductAbstractConditionsTransfer;
use Generated\Shared\Transfer\ProductAbstractCriteriaTransfer;
use Generated\Shared\Transfer\ProductAbstractRelationsTransfer;
use Spryker\Zed\Product\Business\ProductFacadeInterface;

class ProductAbstractReader implements ProductAbstractReaderInterface
{
    public function __construct(
        protected readonly ProductFacadeInterface $productFacade,
    ) {
    }

    /**
     * @param array<string|null> $productAbstractSkus
     */
    public function getProductAbstractCollectionBySkus(array $productAbstractSkus): ProductAbstractCollectionTransfer
    {
        $productAbstractSkus = array_values(array_unique(array_filter($productAbstractSkus)));

        if ($productAbstractSkus === []) {
            return new ProductAbstractCollectionTransfer();
        }

        $productAbstractCriteriaTransfer = (new ProductAbstractCriteriaTransfer())
            ->setProductAbstractConditions(
                (new ProductAbstractConditionsTransfer())->setSkus($productAbstractSkus),
            )
            ->setProductAbstractRelations(
                (new ProductAbstractRelationsTransfer())
                    ->setWithStoreRelations(true)
                    ->setWithProductCategories(true)
                    ->setWithTaxSet(true),
            );

        return $this->productFacade->getProductAbstractCollection($productAbstractCriteriaTransfer);
    }
}
