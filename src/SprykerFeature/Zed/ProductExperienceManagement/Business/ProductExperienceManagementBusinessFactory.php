<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Business;

use Spryker\Zed\EventBehavior\Business\EventBehaviorFacadeInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\ProductAttribute\Business\ProductAttributeFacadeInterface;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Writer\ProductAttributeStorageWriter;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Writer\ProductAttributeStorageWriterInterface;
use SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementDependencyProvider;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig getConfig()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Persistence\ProductExperienceManagementRepositoryInterface getRepository()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Persistence\ProductExperienceManagementEntityManagerInterface getEntityManager()
 */
class ProductExperienceManagementBusinessFactory extends AbstractBusinessFactory
{
    public function createProductAttributeStorageWriter(): ProductAttributeStorageWriterInterface
    {
        return new ProductAttributeStorageWriter(
            $this->getProductAttributeFacade(),
            $this->getEventBehaviorFacade(),
            $this->getEntityManager(),
        );
    }

    public function getEventBehaviorFacade(): EventBehaviorFacadeInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::FACADE_EVENT_BEHAVIOR);
    }

    public function getProductAttributeFacade(): ProductAttributeFacadeInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::FACADE_PRODUCT_ATTRIBUTE);
    }
}
