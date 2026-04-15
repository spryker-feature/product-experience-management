<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\ProductExperienceManagement\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ImportJobCollectionRequestTransfer;
use Generated\Shared\Transfer\ImportJobTransfer;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductCsvImportSchemaPlugin;
use SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementDependencyProvider;
use SprykerFeatureTest\Zed\ProductExperienceManagement\ProductExperienceManagementBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group ProductExperienceManagement
 * @group Business
 * @group Facade
 * @group CreateImportJobCollectionTest
 */
class CreateImportJobCollectionTest extends Unit
{
    protected ProductExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->setDependency(
            ProductExperienceManagementDependencyProvider::PLUGINS_IMPORT_SCHEMA,
            [new ProductCsvImportSchemaPlugin()],
        );
    }

    public function testCreatesImportJobWithGeneratedReference(): void
    {
        // Arrange
        $collectionRequest = (new ImportJobCollectionRequestTransfer())
            ->addImportJob(
                (new ImportJobTransfer())
                    ->setName('My Test Job')
                    ->setType('products-csv-import'),
            );

        // Act
        $collectionResponse = $this->tester->getFacade()->createImportJobCollection($collectionRequest);

        // Assert
        $this->assertCount(0, $collectionResponse->getErrors());
        $this->assertCount(1, $collectionResponse->getImportJobs());

        $createdJob = $collectionResponse->getImportJobs()->getIterator()->current();
        $this->assertNotNull($createdJob->getIdImportJob());
        $this->assertSame('My Test Job', $createdJob->getName());
        $this->assertSame('products-csv-import', $createdJob->getType());
        $this->assertNotEmpty($createdJob->getReference());
    }

    public function testCreatesImportJobWithExplicitReference(): void
    {
        // Arrange
        $collectionRequest = (new ImportJobCollectionRequestTransfer())
            ->addImportJob(
                (new ImportJobTransfer())
                    ->setName('Explicit Ref Job')
                    ->setType('products-csv-import')
                    ->setReference('explicit-ref'),
            );

        // Act
        $collectionResponse = $this->tester->getFacade()->createImportJobCollection($collectionRequest);

        // Assert
        $this->assertCount(0, $collectionResponse->getErrors());
        $createdJob = $collectionResponse->getImportJobs()->getIterator()->current();
        $this->assertSame('explicit-ref', $createdJob->getReference());
    }

    public function testReturnsErrorForUnknownJobType(): void
    {
        // Arrange
        $collectionRequest = (new ImportJobCollectionRequestTransfer())
            ->addImportJob(
                (new ImportJobTransfer())
                    ->setName('Unknown Type Job')
                    ->setType('non-existent-type'),
            );

        // Act
        $collectionResponse = $this->tester->getFacade()->createImportJobCollection($collectionRequest);

        // Assert
        $this->assertCount(1, $collectionResponse->getErrors());
        $this->assertCount(0, $collectionResponse->getImportJobs());
        $this->assertSame(
            'No schema found for job type "non-existent-type".',
            $collectionResponse->getErrors()->getIterator()->current()->getMessage(),
        );
    }

    public function testSkipsInvalidJobAndCreatesValidOneInSameRequest(): void
    {
        // Arrange
        $collectionRequest = (new ImportJobCollectionRequestTransfer())
            ->addImportJob(
                (new ImportJobTransfer())
                    ->setName('Valid Job')
                    ->setType('products-csv-import'),
            )
            ->addImportJob(
                (new ImportJobTransfer())
                    ->setName('Invalid Job')
                    ->setType('non-existent-type'),
            );

        // Act
        $collectionResponse = $this->tester->getFacade()->createImportJobCollection($collectionRequest);

        // Assert
        $this->assertCount(1, $collectionResponse->getErrors());
        $this->assertCount(1, $collectionResponse->getImportJobs());
        $this->assertSame('Valid Job', $collectionResponse->getImportJobs()->getIterator()->current()->getName());
    }

    public function testPopulatesDefinitionFromSchemaPlugin(): void
    {
        // Arrange
        $collectionRequest = (new ImportJobCollectionRequestTransfer())
            ->addImportJob(
                (new ImportJobTransfer())
                    ->setName('Schema Job')
                    ->setType('products-csv-import'),
            );

        // Act
        $collectionResponse = $this->tester->getFacade()->createImportJobCollection($collectionRequest);

        // Assert
        $createdJob = $collectionResponse->getImportJobs()->getIterator()->current();
        $this->assertNotNull($createdJob->getDefinition());
        $this->assertIsArray($createdJob->getDefinition());
        $this->assertNotEmpty($createdJob->getDefinition());
    }
}
