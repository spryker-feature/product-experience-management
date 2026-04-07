<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\Publisher;

use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\ProductManagementAttributeConditionsTransfer;
use Generated\Shared\Transfer\ProductManagementAttributeCriteriaTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\PublisherExtension\Dependency\Plugin\PublisherTriggerPluginInterface;
use SprykerFeature\Shared\ProductExperienceManagement\ProductExperienceManagementConfig;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Business\ProductExperienceManagementBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Business\ProductExperienceManagementFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig getConfig()
 */
class ProductAttributePublisherTriggerPlugin extends AbstractPlugin implements PublisherTriggerPluginInterface
{
    /**
     * @uses \Orm\Zed\ProductAttribute\Persistence\Map\SpyProductManagementAttributeTableMap::COL_ID_PRODUCT_MANAGEMENT_ATTRIBUTE
     *
     * @var string
     */
    protected const string COL_ID_PRODUCT_MANAGEMENT_ATTRIBUTE = 'spy_product_management_attribute.id_product_management_attribute';

    /**
     * {@inheritDoc}
     * - Retrieves product management attributes by offset and limit.
     *
     * @api
     *
     * @param int $offset
     * @param int $limit
     *
     * @return array<\Generated\Shared\Transfer\ProductManagementAttributeTransfer|\Spryker\Shared\Kernel\Transfer\AbstractTransfer>
     */
    public function getData(int $offset, int $limit): array
    {
        $productManagementAttributeCriteriaTransfer = $this->createProductManagementAttributeCriteria($offset, $limit);

        return $this->getBusinessFactory()
            ->getProductAttributeFacade()
            ->getProductManagementAttributeCollection($productManagementAttributeCriteriaTransfer)
            ->getProductManagementAttributes()
            ->getArrayCopy();
    }

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
    public function getEventName(): string
    {
        return ProductExperienceManagementConfig::PRODUCT_ATTRIBUTE_PUBLISH;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getIdColumnName(): ?string
    {
        return static::COL_ID_PRODUCT_MANAGEMENT_ATTRIBUTE;
    }

    protected function createProductManagementAttributeCriteria(int $offset, int $limit): ProductManagementAttributeCriteriaTransfer
    {
        return (new ProductManagementAttributeCriteriaTransfer())
            ->setProductManagementAttributeConditions(new ProductManagementAttributeConditionsTransfer())
            ->setPagination(
                (new PaginationTransfer())
                    ->setLimit($limit)
                    ->setOffset($offset),
            );
    }
}
