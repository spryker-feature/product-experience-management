<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business;

use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Zed\Category\Business\CategoryFacadeInterface;
use Spryker\Zed\Currency\Business\CurrencyFacadeInterface;
use Spryker\Zed\DataExchangeExtension\Dependency\Plugin\ColumnPlaceholderProviderInterface;
use Spryker\Zed\DataExchangeExtension\Dependency\Plugin\ExportStepInterface;
use Spryker\Zed\DataExchangeExtension\Dependency\Plugin\ImportStepInterface;
use Spryker\Zed\EventBehavior\Business\EventBehaviorFacadeInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use Spryker\Zed\Merchant\Business\MerchantFacadeInterface;
use Spryker\Zed\MerchantProduct\Business\MerchantProductFacadeInterface;
use Spryker\Zed\PriceProduct\Business\PriceProductFacadeInterface;
use Spryker\Zed\Product\Business\ProductFacadeInterface;
use Spryker\Zed\ProductApproval\Business\ProductApprovalFacadeInterface;
use Spryker\Zed\ProductAttribute\Business\ProductAttributeFacadeInterface;
use Spryker\Zed\ProductCategory\Business\ProductCategoryFacadeInterface;
use Spryker\Zed\ProductImage\Business\ProductImageFacadeInterface;
use Spryker\Zed\ShipmentType\Business\ShipmentTypeFacadeInterface;
use Spryker\Zed\Stock\Business\StockFacadeInterface;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use Spryker\Zed\Tax\Business\TaxFacadeInterface;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Expander\ProductAttributeVisibilityExpander;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Expander\ProductAttributeVisibilityExpanderInterface;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Export\DataProvider\ProductCsvExportDataProvider;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Export\PlaceholderProvider\ProductCsvExportPlaceholderProvider;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ExportStep\ProductCsv\ProductCsvCategoryExportStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ExportStep\ProductCsv\ProductCsvCoreExportStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ExportStep\ProductCsv\ProductCsvImageExportStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ExportStep\ProductCsv\ProductCsvLocalizedAttributesExportStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ExportStep\ProductCsv\ProductCsvMerchantExportStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ExportStep\ProductCsv\ProductCsvPriceExportStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ExportStep\ProductCsv\ProductCsvShipmentTypeExportStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ExportStep\ProductCsv\ProductCsvStockExportStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ExportStep\ProductCsv\ProductCsvStoreExportStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ExportStep\ProductCsv\ProductCsvUrlExportStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\ProductCsvImportAbstractLocalizedAttributesStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\ProductCsvImportAbstractStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\ProductCsvImportAbstractStoreStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\ProductCsvImportCategoryStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\ProductCsvImportClassStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\ProductCsvImportConcreteLocalizedAttributesStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\ProductCsvImportConcreteStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\ProductCsvImportImageStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\ProductCsvImportMerchantStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\ProductCsvImportPriceStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\ProductCsvImportPriceStoreStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\ProductCsvImportSearchStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\ProductCsvImportShipmentTypeStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\ProductCsvImportStockStep;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\ProductCsvImportUrlStep;
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
    public function createExportPlaceholderProvider(): ColumnPlaceholderProviderInterface
    {
        return new ProductCsvExportPlaceholderProvider(
            $this->getStoreFacade(),
            $this->getLocaleFacade(),
            $this->getPriceProductFacade(),
            $this->getStockFacade(),
        );
    }

    public function createProductCsvExportDataProvider(): ProductCsvExportDataProvider
    {
        return new ProductCsvExportDataProvider();
    }

    /**
     * @return array<\Spryker\Zed\DataExchangeExtension\Dependency\Plugin\ExportStepInterface>
     */
    public function createProductCsvExportSteps(): array
    {
        return [
            $this->createProductCsvCoreExportStep(),
            $this->createProductCsvStoreExportStep(),
            $this->createProductCsvCategoryExportStep(),
            $this->createProductCsvMerchantExportStep(),
            $this->createProductCsvLocalizedAttributesExportStep(),
            $this->createProductCsvUrlExportStep(),
            $this->createProductCsvPriceExportStep(),
            $this->createProductCsvStockExportStep(),
            $this->createProductCsvShipmentTypeExportStep(),
            $this->createProductCsvImageExportStep(),
        ];
    }

    public function createProductCsvCoreExportStep(): ExportStepInterface
    {
        return new ProductCsvCoreExportStep();
    }

    public function createProductCsvStoreExportStep(): ExportStepInterface
    {
        return new ProductCsvStoreExportStep();
    }

    public function createProductCsvCategoryExportStep(): ExportStepInterface
    {
        return new ProductCsvCategoryExportStep();
    }

    public function createProductCsvMerchantExportStep(): ExportStepInterface
    {
        return new ProductCsvMerchantExportStep();
    }

    public function createProductCsvLocalizedAttributesExportStep(): ExportStepInterface
    {
        return new ProductCsvLocalizedAttributesExportStep($this->getUtilEncodingService());
    }

    public function createProductCsvUrlExportStep(): ExportStepInterface
    {
        return new ProductCsvUrlExportStep();
    }

    public function createProductCsvPriceExportStep(): ExportStepInterface
    {
        return new ProductCsvPriceExportStep();
    }

    public function createProductCsvStockExportStep(): ExportStepInterface
    {
        return new ProductCsvStockExportStep();
    }

    public function createProductCsvShipmentTypeExportStep(): ExportStepInterface
    {
        return new ProductCsvShipmentTypeExportStep();
    }

    public function createProductCsvImageExportStep(): ExportStepInterface
    {
        return new ProductCsvImageExportStep();
    }

    /**
     * @return array<\Spryker\Zed\DataExchangeExtension\Dependency\Plugin\ImportStepInterface>
     */
    public function createProductCsvImportSteps(): array
    {
        return [
            $this->createProductCsvImportAbstractStep(),
            $this->createProductCsvImportConcreteStep(),
            $this->createProductCsvImportAbstractLocalizedAttributesStep(),
            $this->createProductCsvImportConcreteLocalizedAttributesStep(),
            $this->createProductCsvImportAbstractStoreStep(),
            $this->createProductCsvImportUrlStep(),
            $this->createProductCsvImportPriceStep(),
            $this->createProductCsvImportPriceStoreStep(),
            $this->createProductCsvImportStockStep(),
            $this->createProductCsvImportSearchStep(),
            $this->createProductCsvImportImageStep(),
            $this->createProductCsvImportShipmentTypeStep(),
            $this->createProductCsvImportClassStep(),
            $this->createProductCsvImportCategoryStep(),
            $this->createProductCsvImportMerchantStep(),
        ];
    }

    public function createProductCsvImportAbstractStep(): ProductCsvImportAbstractStep
    {
        return new ProductCsvImportAbstractStep($this->getUtilEncodingService(), $this->getProductApprovalFacade());
    }

    public function createProductCsvImportConcreteStep(): ImportStepInterface
    {
        return new ProductCsvImportConcreteStep($this->getConfig(), $this->getUtilEncodingService());
    }

    public function createProductCsvImportAbstractLocalizedAttributesStep(): ImportStepInterface
    {
        return new ProductCsvImportAbstractLocalizedAttributesStep($this->getUtilEncodingService());
    }

    public function createProductCsvImportConcreteLocalizedAttributesStep(): ImportStepInterface
    {
        return new ProductCsvImportConcreteLocalizedAttributesStep($this->getUtilEncodingService());
    }

    public function createProductCsvImportAbstractStoreStep(): ImportStepInterface
    {
        return new ProductCsvImportAbstractStoreStep();
    }

    public function createProductCsvImportUrlStep(): ImportStepInterface
    {
        return new ProductCsvImportUrlStep($this->getProductFacade());
    }

    public function createProductCsvImportPriceStep(): ImportStepInterface
    {
        return new ProductCsvImportPriceStep();
    }

    public function createProductCsvImportPriceStoreStep(): ImportStepInterface
    {
        return new ProductCsvImportPriceStoreStep();
    }

    public function createProductCsvImportStockStep(): ImportStepInterface
    {
        return new ProductCsvImportStockStep($this->getStoreFacade(), $this->getStockFacade());
    }

    public function createProductCsvImportSearchStep(): ImportStepInterface
    {
        return new ProductCsvImportSearchStep();
    }

    public function createProductCsvImportImageStep(): ImportStepInterface
    {
        return new ProductCsvImportImageStep();
    }

    public function createProductCsvImportShipmentTypeStep(): ImportStepInterface
    {
        return new ProductCsvImportShipmentTypeStep();
    }

    public function createProductCsvImportClassStep(): ImportStepInterface
    {
        return new ProductCsvImportClassStep();
    }

    public function createProductCsvImportCategoryStep(): ImportStepInterface
    {
        return new ProductCsvImportCategoryStep();
    }

    public function createProductCsvImportMerchantStep(): ImportStepInterface
    {
        return new ProductCsvImportMerchantStep();
    }

    public function createProductAttributeStorageWriter(): ProductAttributeStorageWriterInterface
    {
        return new ProductAttributeStorageWriter(
            $this->getProductAttributeFacade(),
            $this->getEventBehaviorFacade(),
            $this->getEntityManager(),
        );
    }

    public function getProductFacade(): ProductFacadeInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::FACADE_PRODUCT);
    }

    public function getLocaleFacade(): LocaleFacadeInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::FACADE_LOCALE);
    }

    public function getStoreFacade(): StoreFacadeInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::FACADE_STORE);
    }

    public function getTaxFacade(): TaxFacadeInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::FACADE_TAX);
    }

    public function getPriceProductFacade(): PriceProductFacadeInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::FACADE_PRICE_PRODUCT);
    }

    public function getCurrencyFacade(): CurrencyFacadeInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::FACADE_CURRENCY);
    }

    public function getStockFacade(): StockFacadeInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::FACADE_STOCK);
    }

    public function getProductImageFacade(): ProductImageFacadeInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::FACADE_PRODUCT_IMAGE);
    }

    public function getProductCategoryFacade(): ProductCategoryFacadeInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::FACADE_PRODUCT_CATEGORY);
    }

    public function getCategoryFacade(): CategoryFacadeInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::FACADE_CATEGORY);
    }

    public function getShipmentTypeFacade(): ShipmentTypeFacadeInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::FACADE_SHIPMENT_TYPE);
    }

    public function getMerchantFacade(): MerchantFacadeInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::FACADE_MERCHANT);
    }

    public function getMerchantProductFacade(): MerchantProductFacadeInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::FACADE_MERCHANT_PRODUCT);
    }

    public function getEventBehaviorFacade(): EventBehaviorFacadeInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::FACADE_EVENT_BEHAVIOR);
    }

    public function getProductAttributeFacade(): ProductAttributeFacadeInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::FACADE_PRODUCT_ATTRIBUTE);
    }

    public function getProductApprovalFacade(): ProductApprovalFacadeInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::FACADE_PRODUCT_APPROVAL);
    }

    public function getUtilEncodingService(): UtilEncodingServiceInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::SERVICE_UTIL_ENCODING);
    }

    public function createProductAttributeVisibilityExpander(): ProductAttributeVisibilityExpanderInterface
    {
        return new ProductAttributeVisibilityExpander();
    }
}
