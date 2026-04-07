<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\ProductExperienceManagement;

use Spryker\Client\Kernel\AbstractFactory;
use Spryker\Client\Storage\StorageClientInterface;
use Spryker\Service\Synchronization\SynchronizationServiceInterface;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use SprykerFeature\Client\ProductExperienceManagement\Reader\ProductAttributeStorageReader;
use SprykerFeature\Client\ProductExperienceManagement\Reader\ProductAttributeStorageReaderInterface;

class ProductExperienceManagementFactory extends AbstractFactory
{
    public function createProductAttributeStorageReader(): ProductAttributeStorageReaderInterface
    {
        return new ProductAttributeStorageReader(
            $this->getSynchronizationService(),
            $this->getStorageClient(),
            $this->getUtilEncodingService(),
        );
    }

    public function getSynchronizationService(): SynchronizationServiceInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::SERVICE_SYNCHRONIZATION);
    }

    public function getStorageClient(): StorageClientInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::CLIENT_STORAGE);
    }

    public function getUtilEncodingService(): UtilEncodingServiceInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::SERVICE_UTIL_ENCODING);
    }
}
