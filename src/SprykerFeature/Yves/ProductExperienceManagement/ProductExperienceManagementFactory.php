<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\ProductExperienceManagement;

use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use Spryker\Client\Store\StoreClientInterface;
use Spryker\Yves\Kernel\AbstractFactory;
use SprykerFeature\Client\ProductExperienceManagement\ProductExperienceManagementClientInterface;
use SprykerFeature\Yves\ProductExperienceManagement\Extractor\CartProductIdExtractor;
use SprykerFeature\Yves\ProductExperienceManagement\Extractor\CartProductIdExtractorInterface;
use SprykerFeature\Yves\ProductExperienceManagement\Reader\ProductAttributeReader;
use SprykerFeature\Yves\ProductExperienceManagement\Reader\ProductAttributeReaderInterface;

class ProductExperienceManagementFactory extends AbstractFactory
{
    public function createCartProductIdExtractor(): CartProductIdExtractorInterface
    {
        return new CartProductIdExtractor();
    }

    public function createProductAttributeReader(): ProductAttributeReaderInterface
    {
        return new ProductAttributeReader(
            $this->getProductStorageClient(),
            $this->getProductExperienceManagementClient(),
            $this->getLocaleClient(),
            $this->getStoreClient(),
        );
    }

    public function getProductStorageClient(): ProductStorageClientInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::CLIENT_PRODUCT_STORAGE);
    }

    public function getProductExperienceManagementClient(): ProductExperienceManagementClientInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::CLIENT_PRODUCT_EXPERIENCE_MANAGEMENT);
    }

    public function getLocaleClient(): LocaleClientInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::CLIENT_LOCALE);
    }

    public function getStoreClient(): StoreClientInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::CLIENT_STORE);
    }
}
