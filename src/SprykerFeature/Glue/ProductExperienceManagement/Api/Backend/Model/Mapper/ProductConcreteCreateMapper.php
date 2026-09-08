<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Mapper;

use Generated\Api\Backend\ProductsBackendResource;
use Generated\Shared\Transfer\ProductConcreteTransfer;

class ProductConcreteCreateMapper extends AbstractProductConcreteResourceMapper implements ProductConcreteCreateMapperInterface
{
    public function mapResourceToProductConcreteTransfer(
        ProductsBackendResource $productsBackendResource,
        ProductConcreteTransfer $productConcreteTransfer,
    ): ProductConcreteTransfer {
        $productConcreteTransfer
            ->setSku($productsBackendResource->getSku())
            ->setIsActive($productsBackendResource->getIsActive() ?? false);

        if ($productsBackendResource->getAbstractSku() !== null) {
            $productConcreteTransfer->setAbstractSku($productsBackendResource->getAbstractSku());
        }

        if ($productsBackendResource->getValidFrom() !== null) {
            $productConcreteTransfer->setValidFrom($productsBackendResource->getValidFrom());
        }

        if ($productsBackendResource->getValidTo() !== null) {
            $productConcreteTransfer->setValidTo($productsBackendResource->getValidTo());
        }

        $productConcreteTransfer->setAttributes($productsBackendResource->getAttributes() ?? []);

        $productConcreteTransfer->setLocalizedAttributes(
            $this->mapLocalizedAttributes($productsBackendResource->getLocalizedAttributes() ?? []),
        );

        $productConcreteTransfer->setPrices(
            $this->mapPrices($productsBackendResource->getPrices() ?? []),
        );

        $productConcreteTransfer->setImageSets(
            $this->mapImageSets($productsBackendResource->getImageSets() ?? []),
        );

        $productConcreteTransfer->setStocks(
            $this->mapStocks($productsBackendResource->getStocks() ?? [], $productsBackendResource->getSku()),
        );

        $productBundleTransfer = $this->mapProductBundle($productsBackendResource->getProductBundle() ?? []);

        if ($productBundleTransfer !== null) {
            $productConcreteTransfer->setProductBundle($productBundleTransfer);
        }

        $productConcreteTransfer->setProductClasses(
            $this->mapProductClasses($productsBackendResource->getProductClass() ?? []),
        );

        $productConcreteTransfer->setShipmentTypes(
            $this->mapShipmentTypes($productsBackendResource->getShipmentType() ?? []),
        );

        return $productConcreteTransfer;
    }
}
