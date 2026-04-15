<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication;

use Orm\Zed\ProductExperienceManagement\Persistence\SpyImportJobQuery;
use Spryker\Service\FileSystem\FileSystemServiceInterface;
use Spryker\Service\UtilDateTime\UtilDateTimeServiceInterface;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Expander\VisibilityAttributeFormExpander;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Expander\VisibilityAttributeFormExpanderInterface;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Expander\VisibilityAttributeTableExpander;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Expander\VisibilityAttributeTableExpanderInterface;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Form\DataProvider\ImportJobFormDataProvider;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Form\DataProvider\ImportJobRunFormDataProvider;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Form\ImportJobForm;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Form\ImportJobRunForm;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Table\ImportJobRunTable;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Table\ImportJobTable;
use SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementDependencyProvider;
use Symfony\Component\Form\FormInterface;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Business\ProductExperienceManagementFacade getFacade()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig getConfig()
 */
class ProductExperienceManagementCommunicationFactory extends AbstractCommunicationFactory
{
    public function createImportJobTable(): ImportJobTable
    {
        return new ImportJobTable(SpyImportJobQuery::create(), $this->getConfig());
    }

    public function createImportJobRunTable(int $idImportJob): ImportJobRunTable
    {
        return new ImportJobRunTable($idImportJob, $this->getUtilDateTimeService());
    }

    public function getUtilDateTimeService(): UtilDateTimeServiceInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::SERVICE_UTIL_DATE_TIME);
    }

    public function createImportJobForm(): FormInterface
    {
        return $this->getFormFactory()->create(
            ImportJobForm::class,
            null,
            $this->createImportJobFormDataProvider()->getOptions(),
        );
    }

    public function createImportJobFormDataProvider(): ImportJobFormDataProvider
    {
        return new ImportJobFormDataProvider(
            $this->getImportSchemaPlugins(),
        );
    }

    /**
     * @return array<\SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportSchemaPluginInterface>
     */
    public function getImportSchemaPlugins(): array
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::PLUGINS_IMPORT_SCHEMA);
    }

    public function createImportJobRunForm(): FormInterface
    {
        return $this->getFormFactory()->create(
            ImportJobRunForm::class,
            null,
            $this->createImportJobRunFormDataProvider()->getOptions(),
        );
    }

    public function createImportJobRunFormDataProvider(): ImportJobRunFormDataProvider
    {
        return new ImportJobRunFormDataProvider(
            $this->getConfig(),
        );
    }

    public function getFileSystemService(): FileSystemServiceInterface
    {
        return $this->getProvidedDependency(ProductExperienceManagementDependencyProvider::SERVICE_FILE_SYSTEM);
    }

    public function createVisibilityAttributeFormExpander(): VisibilityAttributeFormExpanderInterface
    {
        return new VisibilityAttributeFormExpander(
            $this->getConfig(),
        );
    }

    public function createVisibilityAttributeTableExpander(): VisibilityAttributeTableExpanderInterface
    {
        return new VisibilityAttributeTableExpander(
            $this->getConfig(),
        );
    }
}
