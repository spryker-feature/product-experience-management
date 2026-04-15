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
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductCsvImportSchemaPlugin;
use SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementDependencyProvider;
use SprykerFeatureTest\Zed\ProductExperienceManagement\ProductExperienceManagementBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group ProductExperienceManagement
 * @group Business
 * @group Facade
 * @group ExportByJobCriteriaTest
 */
class ExportByJobCriteriaTest extends Unit
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

    public function testReturnsColumnsWithoutDataWhenIsWithDataIsFalse(): void
    {
        // Arrange
        $schemaPlugin = new ProductCsvImportSchemaPlugin();
        $this->tester->haveImportJob([
            ImportJobTransfer::REFERENCE => 'export-test-job',
            ImportJobTransfer::TYPE => 'simple-product',
            ImportJobTransfer::DEFINITION => $schemaPlugin->getSchema(),
        ]);

        $criteriaTransfer = (new ImportJobCriteriaTransfer())
            ->setImportJobConditions(
                (new ImportJobConditionsTransfer())->addReference('export-test-job'),
            )
            ->setIsWithData(false);

        // Act
        $exportResult = $this->tester->getFacade()->exportData($criteriaTransfer);

        // Assert
        $this->assertSame('simple-product', $exportResult->getType());
        $this->assertNull($exportResult->getFileInfo(), 'File should not be generated when isWithData is false');

        $columns = $exportResult->getColumns();
        $this->assertNotEmpty($columns);
        $this->assertContains('Abstract SKU', $columns);
        $this->assertContains('Concrete SKU', $columns);
        $this->assertGreaterThanOrEqual(count($schemaPlugin->getSchema()), count($columns), 'Columns should contain at least as many entries as the schema definition');
    }

    public function testExportColumnsContainExpandedPlaceholders(): void
    {
        // Arrange
        $schemaPlugin = new ProductCsvImportSchemaPlugin();
        $this->tester->haveImportJob([
            ImportJobTransfer::REFERENCE => 'placeholder-job',
            ImportJobTransfer::TYPE => 'simple-product',
            ImportJobTransfer::DEFINITION => $schemaPlugin->getSchema(),
        ]);

        $criteriaTransfer = (new ImportJobCriteriaTransfer())
            ->setImportJobConditions(
                (new ImportJobConditionsTransfer())->addReference('placeholder-job'),
            );

        // Act
        $exportResult = $this->tester->getFacade()->exportData($criteriaTransfer);

        // Assert
        $columns = $exportResult->getColumns();

        // Static columns should be present
        $this->assertContains('Abstract SKU', $columns);
        $this->assertContains('Categories', $columns);
        $this->assertContains('Tax Set Name', $columns);

        // Placeholder-expanded columns should exist (locale-based)
        $hasLocalizedName = false;
        $hasImageColumn = false;

        foreach ($columns as $column) {
            if (str_starts_with($column, 'Name (')) {
                $hasLocalizedName = true;
            }

            if (str_starts_with($column, 'Image Small (')) {
                $hasImageColumn = true;
            }
        }

        $this->assertTrue($hasLocalizedName, 'Expected localized Name column');
        $this->assertTrue($hasImageColumn, 'Expected Image column with expanded placeholders');
    }
}
