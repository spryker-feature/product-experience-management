<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\Synchronization;

use Generated\Shared\Transfer\FilterTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\SynchronizationExtension\Dependency\Plugin\SynchronizationDataBulkRepositoryPluginInterface;
use SprykerFeature\Shared\ProductExperienceManagement\ProductExperienceManagementConfig;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig getConfig()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Persistence\ProductExperienceManagementRepositoryInterface getRepository()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Business\ProductExperienceManagementFacadeInterface getFacade()
 */
class ProductAttributeSynchronizationDataBulkRepositoryPlugin extends AbstractPlugin implements SynchronizationDataBulkRepositoryPluginInterface
{
    /**
     * @uses \Orm\Zed\ProductExperienceManagement\Persistence\Map\SpyProductAttributeStorageTableMap::COL_ID_PRODUCT_ATTRIBUTE_STORAGE
     *
     * @var string
     */
    protected const string COL_ID_PRODUCT_ATTRIBUTE_STORAGE = 'spy_product_attribute_storage.id_product_attribute_storage';

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getResourceName(): string
    {
        return ProductExperienceManagementConfig::PRODUCT_ATTRIBUTE_RESOURCE_NAME;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function hasStore(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param int $offset
     * @param int $limit
     * @param array<int> $ids
     *
     * @return array<\Generated\Shared\Transfer\SynchronizationDataTransfer>
     */
    public function getData(int $offset, int $limit, array $ids = []): array
    {
        $filterTransfer = $this->createFilterTransfer($offset, $limit);

        return $this->getRepository()->getSynchronizationDataTransfers($ids, $filterTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<string>
     */
    public function getParams(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getQueueName(): string
    {
        return ProductExperienceManagementConfig::PRODUCT_ATTRIBUTE_SYNC_STORAGE_QUEUE;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSynchronizationQueuePoolName(): ?string
    {
        return $this->getConfig()->getProductAttributeSynchronizationPoolName();
    }

    protected function createFilterTransfer(int $offset, int $limit): FilterTransfer
    {
        return (new FilterTransfer())
            ->setOrderBy(static::COL_ID_PRODUCT_ATTRIBUTE_STORAGE)
            ->setOffset($offset)
            ->setLimit($limit);
    }
}
