<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ImportStepResponseTransfer;
use SprykerFeatureTest\Zed\ProductExperienceManagement\ProductExperienceManagementBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group ProductExperienceManagement
 * @group Business
 * @group ImportStep
 * @group ProductCsvImport
 * @group ProductCsvImportConcreteStepTest
 */
class ProductCsvImportConcreteStepTest extends Unit
{
    protected const string COLUMN_ABSTRACT_SKU = 'abstract_sku';

    protected const string COLUMN_CONCRETE_SKU = 'concrete_sku';

    protected const string COLUMN_PRODUCT_STATUS = 'product_status';

    protected const string PROPERTY_NAME_PRODUCT_STATUS_IN_FILE = 'Product Status';

    protected const string CONCRETE_SKU = 'concrete-sku-product-status-test';

    protected const int CSV_ROW_NUMBER = 3;

    protected ProductExperienceManagementBusinessTester $tester;

    public function testReturnsErrorWhenProductStatusIsAnAbstractProductStatus(): void
    {
        // Arrange
        $rows = [static::CSV_ROW_NUMBER => $this->buildConcreteRow('approved')];

        // Act
        $response = $this->executeStep($rows);

        // Assert
        $this->assertFalse($response->getIsSuccessful());
        $this->assertSame(
            sprintf(
                'The value \'approved\' in field \'Product Status\' is not valid because it is not a concrete product status for concrete SKU \'%s\'. Expected: one of the concrete product statuses active, inactive. Please update the value.',
                static::CONCRETE_SKU,
            ),
            $this->findProductStatusErrorMessage($response),
        );
    }

    public function testReturnsErrorWhenProductStatusIsEmpty(): void
    {
        // Arrange
        $rows = [static::CSV_ROW_NUMBER => $this->buildConcreteRow('')];

        // Act
        $response = $this->executeStep($rows);

        // Assert
        $this->assertFalse($response->getIsSuccessful());
        $this->assertSame(
            sprintf(
                'The value \'\' in field \'Product Status\' is not valid because the field is empty for concrete SKU \'%s\'. Expected: one of the concrete product statuses active, inactive. Please update the value.',
                static::CONCRETE_SKU,
            ),
            $this->findProductStatusErrorMessage($response),
        );
    }

    public function testReturnsNoProductStatusErrorWhenProductStatusIsActive(): void
    {
        // Arrange
        $rows = [static::CSV_ROW_NUMBER => $this->buildConcreteRow('active')];

        // Act
        $response = $this->executeStep($rows);

        // Assert
        $this->assertNull($this->findProductStatusErrorMessage($response));
    }

    public function testReturnsNoProductStatusErrorWhenProductStatusIsCapitalized(): void
    {
        // Arrange
        $rows = [static::CSV_ROW_NUMBER => $this->buildConcreteRow('Inactive')];

        // Act
        $response = $this->executeStep($rows);

        // Assert
        $this->assertNull($this->findProductStatusErrorMessage($response));
    }

    /**
     * @return array<string, string>
     */
    protected function buildConcreteRow(string $productStatus): array
    {
        return [
            static::COLUMN_ABSTRACT_SKU => '',
            static::COLUMN_CONCRETE_SKU => static::CONCRETE_SKU,
            static::COLUMN_PRODUCT_STATUS => $productStatus,
        ];
    }

    /**
     * @param array<int, array<string, string>> $rows
     */
    protected function executeStep(array $rows): ImportStepResponseTransfer
    {
        /** @var \SprykerFeature\Zed\ProductExperienceManagement\Business\ProductExperienceManagementBusinessFactory $factory */
        $factory = $this->tester->getFactory();

        return $factory->createProductCsvImportConcreteStep()->executeBatch(
            $rows,
            [static::COLUMN_PRODUCT_STATUS => static::PROPERTY_NAME_PRODUCT_STATUS_IN_FILE],
        );
    }

    protected function findProductStatusErrorMessage(ImportStepResponseTransfer $response): ?string
    {
        foreach ($response->getErrors() as $error) {
            if (str_contains((string)$error->getErrorMessage(), static::PROPERTY_NAME_PRODUCT_STATUS_IN_FILE)) {
                return $error->getErrorMessage();
            }
        }

        return null;
    }
}
