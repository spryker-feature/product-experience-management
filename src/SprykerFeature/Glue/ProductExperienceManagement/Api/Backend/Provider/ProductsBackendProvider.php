<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Provider;

use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\ProductConcreteConditionsTransfer;
use Generated\Shared\Transfer\ProductConcreteCriteriaTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Spryker\ApiPlatform\State\Provider\AbstractBackendProvider;
use Spryker\Zed\Product\Business\ProductFacadeInterface;
use SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Mapper\ProductConcreteTransferToResourceMapperInterface;
use SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Reader\ProductAbstractReaderInterface;

class ProductsBackendProvider extends AbstractBackendProvider
{
    protected const string QUERY_PARAM_FILTER = 'filter';

    protected const string FILTER_RESOURCE_NAME = 'products';

    protected const string FILTER_FIELD_SKU = 'sku';

    protected const string FILTER_FIELD_SKUS = 'skus';

    protected const string FILTER_FIELD_ABSTRACT_SKU = 'abstractSku';

    public function __construct(
        protected readonly ProductFacadeInterface $productFacade,
        protected readonly ProductConcreteTransferToResourceMapperInterface $productConcreteTransferToResourceMapper,
        protected readonly ProductAbstractReaderInterface $productAbstractReader,
    ) {
    }

    protected function provideItem(): ?object
    {
        $sku = $this->getUriVariables()['sku'];

        $productConcreteCriteriaTransfer = (new ProductConcreteCriteriaTransfer())
            ->setProductConcreteConditions(
                (new ProductConcreteConditionsTransfer())->addSku($sku),
            );

        $productConcreteCollectionTransfer = $this->productFacade->getProductConcreteCollection($productConcreteCriteriaTransfer);

        foreach ($productConcreteCollectionTransfer->getProducts() as $productConcreteTransfer) {
            return $this->productConcreteTransferToResourceMapper->mapProductConcreteTransferToResource(
                $productConcreteTransfer,
                $this->productAbstractReader->getProductAbstractCollectionBySkus(
                    [$productConcreteTransfer->getAbstractSku()],
                ),
            );
        }

        return null;
    }

    /**
     * @return array<\Generated\Api\Backend\ProductsBackendResource>
     */
    protected function provideCollection(): array
    {
        $productConcreteCriteriaTransfer = (new ProductConcreteCriteriaTransfer())
            ->setPagination($this->buildPageBasedPaginationTransfer())
            ->setProductConcreteConditions($this->buildConditionsFromRequest());

        $productConcreteCollectionTransfer = $this->productFacade->getProductConcreteCollection($productConcreteCriteriaTransfer);

        $abstractSkus = array_map(
            static fn (ProductConcreteTransfer $productConcreteTransfer): ?string => $productConcreteTransfer->getAbstractSku(),
            $productConcreteCollectionTransfer->getProducts()->getArrayCopy(),
        );

        return $this->productConcreteTransferToResourceMapper->mapProductConcreteTransfersToResources(
            $productConcreteCollectionTransfer->getProducts(),
            $this->productAbstractReader->getProductAbstractCollectionBySkus($abstractSkus),
        );
    }

    protected function buildPageBasedPaginationTransfer(): PaginationTransfer
    {
        $paginationTransfer = $this->getPagination();

        $offset = ($paginationTransfer->getPageOrFail() - 1) * $paginationTransfer->getMaxPerPageOrFail();

        return (new PaginationTransfer())
            ->setOffset($offset)
            ->setLimit($paginationTransfer->getMaxPerPageOrFail());
    }

    protected function buildConditionsFromRequest(): ProductConcreteConditionsTransfer
    {
        $productConcreteConditionsTransfer = new ProductConcreteConditionsTransfer();
        $filters = $this->extractResourceFilters();

        if (!empty($filters[static::FILTER_FIELD_SKU])) {
            $productConcreteConditionsTransfer->addSku($filters[static::FILTER_FIELD_SKU]);
        }

        if (!empty($filters[static::FILTER_FIELD_SKUS])) {
            foreach ((array)$filters[static::FILTER_FIELD_SKUS] as $sku) {
                $productConcreteConditionsTransfer->addSku($sku);
            }
        }

        if (!empty($filters[static::FILTER_FIELD_ABSTRACT_SKU])) {
            $productConcreteConditionsTransfer->addProductAbstractSku($filters[static::FILTER_FIELD_ABSTRACT_SKU]);
        }

        return $productConcreteConditionsTransfer;
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractResourceFilters(): array
    {
        $resourceFilters = [];

        foreach ($this->getRequest()->query->all(static::QUERY_PARAM_FILTER) as $key => $value) {
            [$resourceName, $field] = array_pad(explode('.', (string)$key, 2), 2, null);

            if ($resourceName !== static::FILTER_RESOURCE_NAME || $field === null) {
                continue;
            }

            $resourceFilters[$field] = $value;
        }

        return $resourceFilters;
    }
}
