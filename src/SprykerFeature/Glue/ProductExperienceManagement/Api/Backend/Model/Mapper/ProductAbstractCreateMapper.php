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
use Generated\Shared\Transfer\ProductConcreteTransfer;

class ProductAbstractCreateMapper extends AbstractProductAbstractResourceMapper implements ProductAbstractCreateMapperInterface
{
    public function mapResourceToProductAbstractTransfer(
        ProductsBackendResource $productsBackendResource,
        ProductConcreteTransfer $productConcreteTransfer,
        ProductAbstractTransfer $productAbstractTransfer,
    ): ProductAbstractTransfer {
        $productAbstractTransfer->setAttributes($productsBackendResource->getAttributes() ?? []);

        $productAbstractTransfer->setLocalizedAttributes(
            $this->mapLocalizedAttributes($productsBackendResource->getLocalizedAttributes() ?? []),
        );

        $productAbstractTransfer = $this->mapAbstractScalars($productsBackendResource, $productAbstractTransfer);
        $productAbstractTransfer = $this->mapAbstractTaxSet($productsBackendResource, $productAbstractTransfer);
        $productAbstractTransfer = $this->mapAbstractStores($productsBackendResource, $productAbstractTransfer);
        $productAbstractTransfer = $this->mapCategoryUuids($productsBackendResource, $productAbstractTransfer);
        $productAbstractTransfer = $this->copyCollectionsFromConcrete($productConcreteTransfer, $productAbstractTransfer);

        return $productAbstractTransfer;
    }

    protected function mapCategoryUuids(
        ProductsBackendResource $productsBackendResource,
        ProductAbstractTransfer $productAbstractTransfer,
    ): ProductAbstractTransfer {
        $categories = $productsBackendResource->getCategories() ?? [];

        $productAbstractTransfer->setCategoryUuids($this->extractCategoryUuids($categories));

        return $productAbstractTransfer;
    }

    protected function copyCollectionsFromConcrete(
        ProductConcreteTransfer $productConcreteTransfer,
        ProductAbstractTransfer $productAbstractTransfer,
    ): ProductAbstractTransfer {
        $priceProductTransfers = $productConcreteTransfer->getPrices();

        if ($priceProductTransfers->count() > 0) {
            $clonedPrices = new ArrayObject();

            foreach ($priceProductTransfers as $priceProductTransfer) {
                $clonedPrice = clone $priceProductTransfer;
                $clonedPrice->setIdPriceProduct(null);

                $moneyValueTransfer = $priceProductTransfer->getMoneyValue();

                if ($moneyValueTransfer !== null) {
                    $clonedMoneyValue = clone $moneyValueTransfer;
                    $clonedMoneyValue->setIdEntity(null);
                    $clonedMoneyValue->setUuid(null);
                    $clonedPrice->setMoneyValue($clonedMoneyValue);
                }

                $clonedPrices->append($clonedPrice);
            }

            $productAbstractTransfer->setPrices($clonedPrices);
        }

        $productImageSetTransfers = $productConcreteTransfer->getImageSets();

        if ($productImageSetTransfers->count() > 0) {
            $clonedImageSets = new ArrayObject();

            foreach ($productImageSetTransfers as $imageSetTransfer) {
                $clonedSet = clone $imageSetTransfer;
                $clonedSet->setIdProductImageSet(null);
                $clonedSet->setUuid(null);
                $clonedSet->setIdProduct(null);
                $clonedImageSets->append($clonedSet);
            }

            $productAbstractTransfer->setImageSets($clonedImageSets);
        }

        return $productAbstractTransfer;
    }
}
