<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\ProductExperienceManagement\Persistence;

use Orm\Zed\ProductExperienceManagement\Persistence\SpyProductAttributeStorageQuery;
use Spryker\Zed\Kernel\Persistence\AbstractPersistenceFactory;
use SprykerFeature\Zed\ProductExperienceManagement\Persistence\Propel\Mapper\ProductAttributeStorageMapper;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig getConfig()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Persistence\ProductExperienceManagementEntityManagerInterface getEntityManager()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Persistence\ProductExperienceManagementRepositoryInterface getRepository()
 */
class ProductExperienceManagementPersistenceFactory extends AbstractPersistenceFactory
{
    public function createProductAttributeStorageQuery(): SpyProductAttributeStorageQuery
    {
        return SpyProductAttributeStorageQuery::create();
    }

    public function createProductAttributeStorageMapper(): ProductAttributeStorageMapper
    {
        return new ProductAttributeStorageMapper();
    }
}
