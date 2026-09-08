<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Processor;

use ArrayObject;
use Generated\Api\Backend\ProductsBackendResource;
use Generated\Shared\Transfer\ProductAbstractCollectionRequestTransfer;
use Generated\Shared\Transfer\ProductAbstractConditionsTransfer;
use Generated\Shared\Transfer\ProductAbstractCriteriaTransfer;
use Generated\Shared\Transfer\ProductAbstractRelationsTransfer;
use Generated\Shared\Transfer\ProductAbstractTransfer;
use Generated\Shared\Transfer\ProductConcreteCollectionRequestTransfer;
use Generated\Shared\Transfer\ProductConcreteConditionsTransfer;
use Generated\Shared\Transfer\ProductConcreteCriteriaTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Spryker\ApiPlatform\State\Processor\AbstractBackendProcessor;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Spryker\Zed\Product\Business\ProductFacadeInterface;
use SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Exception\ProductsBackendExceptionFactoryInterface;
use SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Mapper\ProductAbstractCreateMapperInterface;
use SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Mapper\ProductAbstractMergeMapperInterface;
use SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Mapper\ProductConcreteCreateMapperInterface;
use SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Mapper\ProductConcreteMergeMapperInterface;
use SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Mapper\ProductConcreteTransferToResourceMapperInterface;
use SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Reader\ProductAbstractReaderInterface;
use SprykerFeature\Glue\ProductExperienceManagement\ProductExperienceManagementConfig;

class ProductsBackendProcessor extends AbstractBackendProcessor
{
    use TransactionTrait;

    public function __construct(
        protected readonly ProductFacadeInterface $productFacade,
        protected readonly ProductConcreteCreateMapperInterface $productConcreteCreateMapper,
        protected readonly ProductConcreteMergeMapperInterface $productConcreteMergeMapper,
        protected readonly ProductConcreteTransferToResourceMapperInterface $productConcreteTransferToResourceMapper,
        protected readonly ProductAbstractCreateMapperInterface $productAbstractCreateMapper,
        protected readonly ProductAbstractMergeMapperInterface $productAbstractMergeMapper,
        protected readonly ProductExperienceManagementConfig $productExperienceManagementConfig,
        protected readonly ProductsBackendExceptionFactoryInterface $productsBackendExceptionFactory,
        protected readonly ProductAbstractReaderInterface $productAbstractReader,
    ) {
    }

    public function saveProductAbstract(
        ProductsBackendResource $productsBackendResource,
        ProductConcreteTransfer $productConcreteTransfer
    ): ProductConcreteTransfer {
        if ($productsBackendResource->getAbstractSku() === null) {
            return $this->createAbstractForConcrete($productsBackendResource, $productConcreteTransfer);
        }

        if ($this->hasAbstractFields($productsBackendResource)) {
            $this->updateExistingAbstract($productsBackendResource, $productsBackendResource->getAbstractSku());
        }

        return $productConcreteTransfer;
    }

    protected function processPost(mixed $data): ProductsBackendResource
    {
        $productConcreteTransfer = $this->productConcreteCreateMapper
            ->mapResourceToProductConcreteTransfer($data, new ProductConcreteTransfer());

        $this->getTransactionHandler()->handleTransaction(function () use ($data, &$productConcreteTransfer): void {
            $productConcreteTransfer = $this->executePostTransaction($data, $productConcreteTransfer);
        });

        $reloadedProductConcreteTransfer = $this->loadProductConcreteBySku($data->getSku());

        return $this->mapProductConcreteTransferToResource($reloadedProductConcreteTransfer);
    }

    protected function executePostTransaction(
        ProductsBackendResource $productsBackendResource,
        ProductConcreteTransfer $productConcreteTransfer,
    ): ProductConcreteTransfer {
        $productConcreteTransfer = $this->saveProductAbstract($productsBackendResource, $productConcreteTransfer);

        $productConcreteCollectionResponseTransfer = $this->productFacade->createProductCollection(
            (new ProductConcreteCollectionRequestTransfer())->addProduct($productConcreteTransfer),
        );

        $this->assertNoErrors($productConcreteCollectionResponseTransfer->getErrors());

        return $productConcreteTransfer;
    }

    protected function createAbstractForConcrete(
        ProductsBackendResource $productsBackendResource,
        ProductConcreteTransfer $productConcreteTransfer,
    ): ProductConcreteTransfer {
        $abstractSku = sprintf($this->productExperienceManagementConfig->getAbstractSkuPattern(), $productsBackendResource->getSku());

        $productAbstractTransfer = $this->productAbstractCreateMapper->mapResourceToProductAbstractTransfer(
            $productsBackendResource,
            $productConcreteTransfer,
            (new ProductAbstractTransfer())->setSku($abstractSku),
        );

        $productAbstractCollectionResponseTransfer = $this->productFacade->createProductAbstractCollection(
            (new ProductAbstractCollectionRequestTransfer())->addProductAbstract($productAbstractTransfer),
        );

        $this->assertNoErrors($productAbstractCollectionResponseTransfer->getErrors());

        $productConcreteTransfer->setAbstractSku($abstractSku);
        $productConcreteTransfer->setFkProductAbstract($productAbstractTransfer->getIdProductAbstract());

        return $productConcreteTransfer;
    }

    protected function updateExistingAbstract(
        ProductsBackendResource $productsBackendResource,
        string $abstractSku,
    ): void {
        $productAbstractTransfer = $this->loadProductAbstractBySku($abstractSku);

        if ($productAbstractTransfer === null) {
            return;
        }

        $productAbstractTransfer = $this->productAbstractMergeMapper
            ->mergeResourceToProductAbstractTransfer($productsBackendResource, $productAbstractTransfer);

        $productAbstractCollectionResponseTransfer = $this->productFacade->updateProductAbstractCollection(
            (new ProductAbstractCollectionRequestTransfer())->addProductAbstract($productAbstractTransfer),
        );

        $this->assertNoErrors($productAbstractCollectionResponseTransfer->getErrors());
    }

    protected function processPatch(mixed $data): ProductsBackendResource
    {
        $sku = $this->getUriVariables()['sku'];

        $existingProductConcreteTransfer = $this->findProductConcreteBySku($sku);

        if ($existingProductConcreteTransfer === null) {
            throw $this->productsBackendExceptionFactory->createProductNotFoundException($sku);
        }

        $productConcreteTransfer = $this->productConcreteMergeMapper
            ->mapResourceToProductConcreteTransfer($data, $existingProductConcreteTransfer);

        $productConcreteTransfer->setSku($sku);

        $abstractSku = $existingProductConcreteTransfer->getAbstractSku();

        $this->getTransactionHandler()->handleTransaction(function () use ($data, $productConcreteTransfer, $abstractSku): void {
            $productConcreteCollectionResponseTransfer = $this->productFacade->updateProductCollection(
                (new ProductConcreteCollectionRequestTransfer())->addProduct($productConcreteTransfer),
            );

            $this->assertNoErrors($productConcreteCollectionResponseTransfer->getErrors());

            if ($this->hasAbstractFields($data) && $abstractSku !== null) {
                $this->updateExistingAbstract($data, $abstractSku);
            }
        });

        return $this->mapProductConcreteTransferToResource($this->loadProductConcreteBySku($sku));
    }

    protected function mapProductConcreteTransferToResource(
        ProductConcreteTransfer $productConcreteTransfer,
    ): ProductsBackendResource {
        return $this->productConcreteTransferToResourceMapper->mapProductConcreteTransferToResource(
            $productConcreteTransfer,
            $this->productAbstractReader->getProductAbstractCollectionBySkus([$productConcreteTransfer->getAbstractSku()]),
        );
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ErrorTransfer> $errorTransfers
     *
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     */
    protected function assertNoErrors(ArrayObject $errorTransfers): void
    {
        if ($errorTransfers->count() === 0) {
            return;
        }

        $messages = [];

        foreach ($errorTransfers as $errorTransfer) {
            $messages[] = $errorTransfer->getMessageOrFail();
        }

        throw $this->productsBackendExceptionFactory->createValidationException($messages);
    }

    protected function findProductConcreteBySku(string $sku): ?ProductConcreteTransfer
    {
        $productConcreteCriteriaTransfer = (new ProductConcreteCriteriaTransfer())
            ->setProductConcreteConditions(
                (new ProductConcreteConditionsTransfer())->addSku($sku),
            );

        $productConcreteCollectionTransfer = $this->productFacade
            ->getProductConcreteCollection($productConcreteCriteriaTransfer);

        foreach ($productConcreteCollectionTransfer->getProducts() as $productConcreteTransfer) {
            return $productConcreteTransfer;
        }

        return null;
    }

    protected function loadProductConcreteBySku(string $sku): ProductConcreteTransfer
    {
        $productConcreteCriteriaTransfer = (new ProductConcreteCriteriaTransfer())
            ->setProductConcreteConditions(
                (new ProductConcreteConditionsTransfer())->addSku($sku),
            );

        $productConcreteCollectionTransfer = $this->productFacade
            ->getProductConcreteCollection($productConcreteCriteriaTransfer);

        foreach ($productConcreteCollectionTransfer->getProducts() as $productConcreteTransfer) {
            return $productConcreteTransfer;
        }

        return (new ProductConcreteTransfer())->setSku($sku);
    }

    protected function loadProductAbstractBySku(string $abstractSku): ?ProductAbstractTransfer
    {
        $productAbstractCriteriaTransfer = (new ProductAbstractCriteriaTransfer())
            ->setProductAbstractConditions(
                (new ProductAbstractConditionsTransfer())->addSku($abstractSku),
            )
            ->setProductAbstractRelations(
                (new ProductAbstractRelationsTransfer())
                    ->setWithStoreRelations(true)
                    ->setWithProductCategories(true)
                    ->setWithImageSets(true),
            );

        $productAbstractCollectionTransfer = $this->productFacade
            ->getProductAbstractCollection($productAbstractCriteriaTransfer);

        foreach ($productAbstractCollectionTransfer->getProductAbstracts() as $productAbstractTransfer) {
            /** @var array<string, mixed>|string $attributes */
            $attributes = $productAbstractTransfer->getAttributes();

            if (is_string($attributes)) {
                $decodedAttributes = json_decode($attributes, true);
                $productAbstractTransfer->setAttributes(is_array($decodedAttributes) ? $decodedAttributes : []);
            }

            return $productAbstractTransfer;
        }

        return null;
    }

    protected function hasAbstractFields(ProductsBackendResource $productsBackendResource): bool
    {
        return $productsBackendResource->getStores() !== null
            || $productsBackendResource->getTaxSet() !== null
            || $productsBackendResource->getCategories() !== null
            || $productsBackendResource->getNewFrom() !== null
            || $productsBackendResource->getNewTo() !== null;
    }
}
