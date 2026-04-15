<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Persistence;

use Orm\Zed\ProductExperienceManagement\Persistence\SpyImportJobQuery;
use Orm\Zed\ProductExperienceManagement\Persistence\SpyImportJobRunErrorQuery;
use Orm\Zed\ProductExperienceManagement\Persistence\SpyImportJobRunQuery;
use Orm\Zed\ProductExperienceManagement\Persistence\SpyProductAttributeStorageQuery;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Zed\Kernel\Persistence\AbstractPersistenceFactory;
use SprykerFeature\Zed\ProductExperienceManagement\Persistence\Propel\Mapper\ProductAttributeStorageMapper;
use SprykerFeature\Zed\ProductExperienceManagement\Persistence\Propel\Mapper\ProductExperienceManagementMapper;
use SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementDependencyProvider;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig getConfig()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Persistence\ProductExperienceManagementEntityManager getEntityManager()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Persistence\ProductExperienceManagementRepository getRepository()
 */
class ProductExperienceManagementPersistenceFactory extends AbstractPersistenceFactory
{
    public function createImportJobQuery(): SpyImportJobQuery
    {
        return SpyImportJobQuery::create();
    }

    public function createImportJobRunQuery(): SpyImportJobRunQuery
    {
        return SpyImportJobRunQuery::create();
    }

    public function createImportJobRunErrorQuery(): SpyImportJobRunErrorQuery
    {
        return SpyImportJobRunErrorQuery::create();
    }

    public function createMapper(): ProductExperienceManagementMapper
    {
        return new ProductExperienceManagementMapper($this->getUtilEncodingService());
    }

    public function getUtilEncodingService(): UtilEncodingServiceInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::SERVICE_UTIL_ENCODING);
    }

    public function createProductAttributeStorageQuery(): SpyProductAttributeStorageQuery
    {
        return SpyProductAttributeStorageQuery::create();
    }

    public function createProductAttributeStorageMapper(): ProductAttributeStorageMapper
    {
        return new ProductAttributeStorageMapper();
    }
}
