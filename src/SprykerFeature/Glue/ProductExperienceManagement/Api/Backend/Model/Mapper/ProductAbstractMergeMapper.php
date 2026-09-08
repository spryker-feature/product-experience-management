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

class ProductAbstractMergeMapper extends AbstractProductAbstractResourceMapper implements ProductAbstractMergeMapperInterface
{
    public function mergeResourceToProductAbstractTransfer(
        ProductsBackendResource $productsBackendResource,
        ProductAbstractTransfer $productAbstractTransfer,
    ): ProductAbstractTransfer {
        $productAbstractTransfer = $this->mapAbstractScalars($productsBackendResource, $productAbstractTransfer);
        $productAbstractTransfer = $this->mapAbstractTaxSet($productsBackendResource, $productAbstractTransfer);
        $productAbstractTransfer = $this->mergeStores($productsBackendResource, $productAbstractTransfer);
        $productAbstractTransfer = $this->mergeCategoryUuids($productsBackendResource, $productAbstractTransfer);

        return $productAbstractTransfer;
    }

    protected function mergeStores(
        ProductsBackendResource $productsBackendResource,
        ProductAbstractTransfer $productAbstractTransfer,
    ): ProductAbstractTransfer {
        $stores = $productsBackendResource->getStores() ?? [];

        $existingStoreNames = [];
        $existingStoreRelationTransfer = $productAbstractTransfer->getStoreRelation();

        if ($existingStoreRelationTransfer !== null) {
            foreach ($existingStoreRelationTransfer->getStores() as $storeTransfer) {
                $existingStoreNames[$storeTransfer->getName()] = true;
            }
        }

        foreach ($stores as $storeName) {
            $existingStoreNames[$storeName] = true;
        }

        $mergedStoreTransfers = new ArrayObject();

        foreach (array_keys($existingStoreNames) as $storeName) {
            $mergedStoreTransfers->append(
                (new StoreTransfer())->setName($storeName),
            );
        }

        $storeRelationTransfer = $existingStoreRelationTransfer ?? new StoreRelationTransfer();
        $storeRelationTransfer->setStores($mergedStoreTransfers);
        $storeRelationTransfer->setIdStores([]);

        $productAbstractTransfer->setStoreRelation($storeRelationTransfer);

        return $productAbstractTransfer;
    }

    protected function mergeCategoryUuids(
        ProductsBackendResource $productsBackendResource,
        ProductAbstractTransfer $productAbstractTransfer,
    ): ProductAbstractTransfer {
        $categories = $productsBackendResource->getCategories() ?? [];

        if ($categories === []) {
            return $productAbstractTransfer;
        }

        $incomingUuids = $this->extractCategoryUuids($categories);

        $existingUuids = [];

        foreach ($productAbstractTransfer->getProductCategories() as $productCategoryTransfer) {
            $categoryUuid = $productCategoryTransfer->getCategory()?->getUuid();

            if ($categoryUuid !== null) {
                $existingUuids[] = $categoryUuid;
            }
        }

        $mergedUuids = array_values(array_unique(array_merge($existingUuids, $incomingUuids)));

        $productAbstractTransfer->setCategoryUuids($mergedUuids);

        return $productAbstractTransfer;
    }
}
