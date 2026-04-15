<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\ProductExperienceManagement\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ImportJobConditionsTransfer;
use Generated\Shared\Transfer\ImportJobCriteriaTransfer;
use Generated\Shared\Transfer\ImportJobTransfer;
use SprykerFeatureTest\Zed\ProductExperienceManagement\ProductExperienceManagementBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group ProductExperienceManagement
 * @group Business
 * @group Facade
 * @group GetImportJobCollectionTest
 */
class GetImportJobCollectionTest extends Unit
{
    protected ProductExperienceManagementBusinessTester $tester;

    public function testReturnsEmptyCollectionWhenNoJobsExist(): void
    {
        // Arrange
        $criteriaTransfer = (new ImportJobCriteriaTransfer())
            ->setImportJobConditions(
                (new ImportJobConditionsTransfer())->addReference('non-existent-reference'),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getImportJobCollection($criteriaTransfer);

        // Assert
        $this->assertCount(0, $collectionTransfer->getImportJobs());
    }

    public function testReturnsAllJobs(): void
    {
        // Arrange
        $this->tester->haveImportJob([
            ImportJobTransfer::NAME => 'Job A',
            ImportJobTransfer::REFERENCE => 'job-a',
        ]);
        $this->tester->haveImportJob([
            ImportJobTransfer::NAME => 'Job B',
            ImportJobTransfer::REFERENCE => 'job-b',
        ]);

        $criteriaTransfer = (new ImportJobCriteriaTransfer())
            ->setImportJobConditions(
                (new ImportJobConditionsTransfer())
                    ->addReference('job-a')
                    ->addReference('job-b'),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getImportJobCollection($criteriaTransfer);

        // Assert
        $this->assertCount(2, $collectionTransfer->getImportJobs());
    }

    public function testFiltersJobsByReference(): void
    {
        // Arrange
        $importJob = $this->tester->haveImportJob([
            ImportJobTransfer::NAME => 'Target Job',
            ImportJobTransfer::REFERENCE => 'target-ref',
        ]);
        $this->tester->haveImportJob([
            ImportJobTransfer::NAME => 'Other Job',
            ImportJobTransfer::REFERENCE => 'other-ref',
        ]);

        $criteriaTransfer = (new ImportJobCriteriaTransfer())
            ->setImportJobConditions(
                (new ImportJobConditionsTransfer())->addReference('target-ref'),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getImportJobCollection($criteriaTransfer);

        // Assert
        $this->assertCount(1, $collectionTransfer->getImportJobs());
        $this->assertSame(
            'target-ref',
            $collectionTransfer->getImportJobs()->getIterator()->current()->getReference(),
        );
    }

    public function testReturnsJobWithAllFields(): void
    {
        // Arrange
        $definition = [
            ['property_name_in_file' => 'Abstract SKU', 'system_property_name' => 'abstract_sku'],
        ];
        $this->tester->haveImportJob([
            ImportJobTransfer::NAME => 'Full Job',
            ImportJobTransfer::REFERENCE => 'full-ref',
            ImportJobTransfer::TYPE => 'simple-product',
            ImportJobTransfer::DEFINITION => $definition,
            ImportJobTransfer::DESCRIPTION => 'Test description',
        ]);

        $criteriaTransfer = (new ImportJobCriteriaTransfer())
            ->setImportJobConditions(
                (new ImportJobConditionsTransfer())->addReference('full-ref'),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getImportJobCollection($criteriaTransfer);

        // Assert
        $job = $collectionTransfer->getImportJobs()->getIterator()->current();
        $this->assertSame('Full Job', $job->getName());
        $this->assertSame('full-ref', $job->getReference());
        $this->assertSame('simple-product', $job->getType());
        $this->assertSame('Test description', $job->getDescription());
        $this->assertSame($definition, $job->getDefinition());
    }
}
