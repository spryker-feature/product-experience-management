<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement;

use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig getConfig()
 */
class ProductExperienceManagementDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string SERVICE_FILE_SYSTEM = 'SERVICE_FILE_SYSTEM';

    public const string FACADE_EVENT = 'FACADE_EVENT';

    public const string FACADE_PRODUCT = 'FACADE_PRODUCT';

    public const string FACADE_LOCALE = 'FACADE_LOCALE';

    public const string FACADE_STORE = 'FACADE_STORE';

    public const string FACADE_TAX = 'FACADE_TAX';

    public const string FACADE_PRICE_PRODUCT = 'FACADE_PRICE_PRODUCT';

    public const string FACADE_CURRENCY = 'FACADE_CURRENCY';

    public const string FACADE_STOCK = 'FACADE_STOCK';

    public const string FACADE_PRODUCT_IMAGE = 'FACADE_PRODUCT_IMAGE';

    public const string FACADE_PRODUCT_CATEGORY = 'FACADE_PRODUCT_CATEGORY';

    public const string FACADE_CATEGORY = 'FACADE_CATEGORY';

    public const string FACADE_SHIPMENT_TYPE = 'FACADE_SHIPMENT_TYPE';

    public const string FACADE_MERCHANT = 'FACADE_MERCHANT';

    public const string FACADE_MERCHANT_PRODUCT = 'FACADE_MERCHANT_PRODUCT';

    public const string PLUGINS_IMPORT_SCHEMA = 'PLUGINS_IMPORT_SCHEMA';

    public const string PLUGINS_IMPORT_PRE_PROCESSOR = 'PLUGINS_IMPORT_PRE_PROCESSOR';

    public const string PLUGINS_IMPORT_POST_PROCESSOR = 'PLUGINS_IMPORT_POST_PROCESSOR';

    public const string SERVICE_UTIL_DATE_TIME = 'SERVICE_UTIL_DATE_TIME';

    public const string SERVICE_UTIL_ENCODING = 'SERVICE_UTIL_ENCODING';

    public const string FACADE_EVENT_BEHAVIOR = 'FACADE_EVENT_BEHAVIOR';

    public const string FACADE_PRODUCT_ATTRIBUTE = 'FACADE_PRODUCT_ATTRIBUTE';

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);
        $container = $this->addFileSystemService($container);
        $container = $this->addEventFacade($container);
        $container = $this->addProductFacade($container);
        $container = $this->addLocaleFacade($container);
        $container = $this->addStoreFacade($container);
        $container = $this->addTaxFacade($container);
        $container = $this->addPriceProductFacade($container);
        $container = $this->addCurrencyFacade($container);
        $container = $this->addStockFacade($container);
        $container = $this->addProductImageFacade($container);
        $container = $this->addProductCategoryFacade($container);
        $container = $this->addCategoryFacade($container);
        $container = $this->addShipmentTypeFacade($container);
        $container = $this->addMerchantFacade($container);
        $container = $this->addMerchantProductFacade($container);
        $container = $this->addImportSchemaPlugins($container);
        $container = $this->addImportPreProcessorPlugins($container);
        $container = $this->addImportPostProcessorPlugins($container);
        $container = $this->addEventBehaviorFacade($container);
        $container = $this->addProductAttributeFacade($container);
        $container = $this->addUtilEncodingService($container);

        return $container;
    }

    public function providePersistenceLayerDependencies(Container $container): Container
    {
        $container = parent::providePersistenceLayerDependencies($container);
        $container = $this->addUtilEncodingService($container);

        return $container;
    }

    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);
        $container = $this->addEventBehaviorFacade($container);
        $container = $this->addFileSystemService($container);
        $container = $this->addImportSchemaPlugins($container);
        $container = $this->addUtilDateTimeService($container);

        return $container;
    }

    protected function addFileSystemService(Container $container): Container
    {
        $container->set(static::SERVICE_FILE_SYSTEM, function (Container $container) {
            return $container->getLocator()->fileSystem()->service();
        });

        return $container;
    }

    protected function addEventFacade(Container $container): Container
    {
        $container->set(static::FACADE_EVENT, function (Container $container) {
            return $container->getLocator()->event()->facade();
        });

        return $container;
    }

    protected function addProductFacade(Container $container): Container
    {
        $container->set(static::FACADE_PRODUCT, function (Container $container) {
            return $container->getLocator()->product()->facade();
        });

        return $container;
    }

    protected function addLocaleFacade(Container $container): Container
    {
        $container->set(static::FACADE_LOCALE, function (Container $container) {
            return $container->getLocator()->locale()->facade();
        });

        return $container;
    }

    protected function addStoreFacade(Container $container): Container
    {
        $container->set(static::FACADE_STORE, function (Container $container) {
            return $container->getLocator()->store()->facade();
        });

        return $container;
    }

    protected function addTaxFacade(Container $container): Container
    {
        $container->set(static::FACADE_TAX, function (Container $container) {
            return $container->getLocator()->tax()->facade();
        });

        return $container;
    }

    protected function addPriceProductFacade(Container $container): Container
    {
        $container->set(static::FACADE_PRICE_PRODUCT, function (Container $container) {
            return $container->getLocator()->priceProduct()->facade();
        });

        return $container;
    }

    protected function addCurrencyFacade(Container $container): Container
    {
        $container->set(static::FACADE_CURRENCY, function (Container $container) {
            return $container->getLocator()->currency()->facade();
        });

        return $container;
    }

    protected function addStockFacade(Container $container): Container
    {
        $container->set(static::FACADE_STOCK, function (Container $container) {
            return $container->getLocator()->stock()->facade();
        });

        return $container;
    }

    protected function addProductImageFacade(Container $container): Container
    {
        $container->set(static::FACADE_PRODUCT_IMAGE, function (Container $container) {
            return $container->getLocator()->productImage()->facade();
        });

        return $container;
    }

    protected function addProductCategoryFacade(Container $container): Container
    {
        $container->set(static::FACADE_PRODUCT_CATEGORY, function (Container $container) {
            return $container->getLocator()->productCategory()->facade();
        });

        return $container;
    }

    protected function addCategoryFacade(Container $container): Container
    {
        $container->set(static::FACADE_CATEGORY, function (Container $container) {
            return $container->getLocator()->category()->facade();
        });

        return $container;
    }

    protected function addShipmentTypeFacade(Container $container): Container
    {
        $container->set(static::FACADE_SHIPMENT_TYPE, function (Container $container) {
            return $container->getLocator()->shipmentType()->facade();
        });

        return $container;
    }

    protected function addMerchantFacade(Container $container): Container
    {
        $container->set(static::FACADE_MERCHANT, function (Container $container) {
            return $container->getLocator()->merchant()->facade();
        });

        return $container;
    }

    protected function addMerchantProductFacade(Container $container): Container
    {
        $container->set(static::FACADE_MERCHANT_PRODUCT, function (Container $container) {
            return $container->getLocator()->merchantProduct()->facade();
        });

        return $container;
    }

    protected function addImportSchemaPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_IMPORT_SCHEMA, function () {
            return $this->getImportSchemaPlugins();
        });

        return $container;
    }

    /**
     * @return array<\SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportSchemaPluginInterface>
     */
    protected function getImportSchemaPlugins(): array
    {
        return [];
    }

    protected function addUtilDateTimeService(Container $container): Container
    {
        $container->set(static::SERVICE_UTIL_DATE_TIME, function (Container $container) {
            return $container->getLocator()->utilDateTime()->service();
        });

        return $container;
    }

    protected function addImportPreProcessorPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_IMPORT_PRE_PROCESSOR, function () {
            return $this->getImportPreProcessorPlugins();
        });

        return $container;
    }

    /**
     * @return array<\SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportPreProcessorPluginInterface>
     */
    protected function getImportPreProcessorPlugins(): array
    {
        return [];
    }

    protected function addImportPostProcessorPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_IMPORT_POST_PROCESSOR, function () {
            return $this->getImportPostProcessorPlugins();
        });

        return $container;
    }

    /**
     * @return array<\SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportPostProcessorPluginInterface>
     */
    protected function getImportPostProcessorPlugins(): array
    {
        return [];
    }

    protected function addUtilEncodingService(Container $container): Container
    {
        $container->set(static::SERVICE_UTIL_ENCODING, function (Container $container) {
            return $container->getLocator()->utilEncoding()->service();
        });

        return $container;
    }

    protected function addEventBehaviorFacade(Container $container): Container
    {
        $container->set(static::FACADE_EVENT_BEHAVIOR, function (Container $container) {
            return $container->getLocator()->eventBehavior()->facade();
        });

        return $container;
    }

    protected function addProductAttributeFacade(Container $container): Container
    {
        $container->set(static::FACADE_PRODUCT_ATTRIBUTE, function (Container $container) {
            return $container->getLocator()->productAttribute()->facade();
        });

        return $container;
    }
}
