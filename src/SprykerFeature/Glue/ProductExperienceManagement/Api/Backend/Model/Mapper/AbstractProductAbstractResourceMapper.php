<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Mapper;

use ArrayObject;
use Generated\Api\Backend\ProductsBackendResource;
use Generated\Shared\Transfer\ProductAbstractTransfer;
use Generated\Shared\Transfer\StoreRelationTransfer;
use Generated\Shared\Transfer\StoreTransfer;

abstract class AbstractProductAbstractResourceMapper extends AbstractProductConcreteResourceMapper
{
    protected function mapAbstractScalars(
        ProductsBackendResource $productsBackendResource,
        ProductAbstractTransfer $productAbstractTransfer,
    ): ProductAbstractTransfer {
        $productAbstractTransfer->setNewFrom(
            $productsBackendResource->getNewFrom() ?? $productAbstractTransfer->getNewFrom(),
        );
        $productAbstractTransfer->setNewTo(
            $productsBackendResource->getNewTo() ?? $productAbstractTransfer->getNewTo(),
        );

        return $productAbstractTransfer;
    }

    protected function mapAbstractTaxSet(
        ProductsBackendResource $productsBackendResource,
        ProductAbstractTransfer $productAbstractTransfer,
    ): ProductAbstractTransfer {
        $taxSet = $productsBackendResource->getTaxSet();

        if ($taxSet === null) {
            return $productAbstractTransfer;
        }

        $productAbstractTransfer->setTaxSetUuid($taxSet->getUuid());

        return $productAbstractTransfer;
    }

    protected function mapAbstractStores(
        ProductsBackendResource $productsBackendResource,
        ProductAbstractTransfer $productAbstractTransfer,
    ): ProductAbstractTransfer {
        $stores = $productsBackendResource->getStores() ?? [];

        $storeTransfers = new ArrayObject();

        foreach ($stores as $storeName) {
            $storeTransfers->append(
                (new StoreTransfer())->setName($storeName),
            );
        }

        $storeRelationTransfer = $productAbstractTransfer->getStoreRelation() ?? new StoreRelationTransfer();
        $storeRelationTransfer->setStores($storeTransfers);
        $storeRelationTransfer->setIdStores([]);

        $productAbstractTransfer->setStoreRelation($storeRelationTransfer);

        return $productAbstractTransfer;
    }

    /**
     * @param array<int, array<string, mixed>> $categories
     *
     * @return array<string>
     */
    protected function extractCategoryUuids(array $categories): array
    {
        $categoryUuids = [];

        foreach ($categories as $category) {
            $category = (array)$category;
            if (isset($category['uuid'])) {
                $categoryUuids[] = $category['uuid'];
            }
        }

        return $categoryUuids;
    }
}
