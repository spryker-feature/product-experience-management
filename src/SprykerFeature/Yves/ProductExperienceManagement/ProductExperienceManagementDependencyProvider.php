<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\ProductExperienceManagement;

use Spryker\Yves\Kernel\AbstractBundleDependencyProvider;
use Spryker\Yves\Kernel\Container;

class ProductExperienceManagementDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string CLIENT_PRODUCT_STORAGE = 'CLIENT_PRODUCT_STORAGE';

    public const string CLIENT_PRODUCT_EXPERIENCE_MANAGEMENT = 'CLIENT_PRODUCT_EXPERIENCE_MANAGEMENT';

    public const string CLIENT_LOCALE = 'CLIENT_LOCALE';

    public const string CLIENT_STORE = 'CLIENT_STORE';

    public function provideDependencies(Container $container): Container
    {
        $container = parent::provideDependencies($container);
        $container = $this->addProductStorageClient($container);
        $container = $this->addProductExperienceManagementClient($container);
        $container = $this->addLocaleClient($container);
        $container = $this->addStoreClient($container);

        return $container;
    }

    protected function addProductStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRODUCT_STORAGE, function (Container $container) {
            return $container->getLocator()->productStorage()->client();
        });

        return $container;
    }

    protected function addProductExperienceManagementClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRODUCT_EXPERIENCE_MANAGEMENT, function (Container $container) {
            return $container->getLocator()->productExperienceManagement()->client();
        });

        return $container;
    }

    protected function addLocaleClient(Container $container): Container
    {
        $container->set(static::CLIENT_LOCALE, function (Container $container) {
            return $container->getLocator()->locale()->client();
        });

        return $container;
    }

    protected function addStoreClient(Container $container): Container
    {
        $container->set(static::CLIENT_STORE, function (Container $container) {
            return $container->getLocator()->store()->client();
        });

        return $container;
    }
}
