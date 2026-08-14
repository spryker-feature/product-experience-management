<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ImportStepResponseTransfer;
use Spryker\Shared\ProductApproval\ProductApprovalConfig;
use Spryker\Zed\ProductApproval\Business\ProductApprovalFacadeInterface;
use SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementDependencyProvider;
use SprykerFeatureTest\Zed\ProductExperienceManagement\ProductExperienceManagementBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group ProductExperienceManagement
 * @group Business
 * @group ImportStep
 * @group ProductCsvImport
 * @group ProductCsvImportAbstractStepTest
 */
class ProductCsvImportAbstractStepTest extends Unit
{
    protected const string COLUMN_ABSTRACT_SKU = 'abstract_sku';

    protected const string COLUMN_PRODUCT_STATUS = 'product_status';

    protected const string COLUMN_TAX_SET_NAME = 'tax_set_name';

    protected const string PROPERTY_NAME_PRODUCT_STATUS_IN_FILE = 'Product Status';

    protected const int CSV_ROW_NUMBER = 2;

    protected const string PRODUCT_CONCRETE_STATUS_ACTIVE = 'active';

    protected ProductExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->setDependency(
            ProductExperienceManagementDependencyProvider::FACADE_PRODUCT_APPROVAL,
            $this->createProductApprovalFacadeMock(),
        );
    }

    public function testReturnsErrorWhenProductStatusIsAConcreteProductStatus(): void
    {
        // Arrange
        $rows = [static::CSV_ROW_NUMBER => $this->buildAbstractRow(static::PRODUCT_CONCRETE_STATUS_ACTIVE)];

        // Act
        $response = $this->executeStep($rows);

        // Assert
        $this->assertFalse($response->getIsSuccessful());
        $this->assertSame(
            sprintf(
                'The value \'%s\' in field \'%s\' is not valid because it is not an abstract product status. Expected: one of the abstract product statuses %s. Please update the value.',
                static::PRODUCT_CONCRETE_STATUS_ACTIVE,
                static::PROPERTY_NAME_PRODUCT_STATUS_IN_FILE,
                $this->formatAllowedProductStatuses(),
            ),
            $this->findProductStatusErrorMessage($response),
        );
    }

    public function testReturnsErrorWhenProductStatusIsEmpty(): void
    {
        // Arrange
        $rows = [static::CSV_ROW_NUMBER => $this->buildAbstractRow('')];

        // Act
        $response = $this->executeStep($rows);

        // Assert
        $this->assertFalse($response->getIsSuccessful());
        $this->assertSame(
            sprintf(
                'The value \'\' in field \'%s\' is not valid because the field is empty. Expected: one of the abstract product statuses %s. Please update the value.',
                static::PROPERTY_NAME_PRODUCT_STATUS_IN_FILE,
                $this->formatAllowedProductStatuses(),
            ),
            $this->findProductStatusErrorMessage($response),
        );
    }

    public function testReturnsNoProductStatusErrorWhenProductStatusIsAnApprovalStatus(): void
    {
        // Arrange
        $rows = [static::CSV_ROW_NUMBER => $this->buildAbstractRow(ProductApprovalConfig::STATUS_APPROVED)];

        // Act
        $response = $this->executeStep($rows);

        // Assert
        $this->assertNull($this->findProductStatusErrorMessage($response));
    }

    public function testReturnsNoProductStatusErrorWhenProductStatusIsCapitalized(): void
    {
        // Arrange
        $rows = [static::CSV_ROW_NUMBER => $this->buildAbstractRow(ucfirst(ProductApprovalConfig::STATUS_APPROVED))];

        // Act
        $response = $this->executeStep($rows);

        // Assert
        $this->assertNull($this->findProductStatusErrorMessage($response));
    }

    protected function createProductApprovalFacadeMock(): ProductApprovalFacadeInterface
    {
        $productApprovalFacadeMock = $this->createMock(ProductApprovalFacadeInterface::class);
        $productApprovalFacadeMock
            ->method('getApplicableApprovalStatuses')
            ->with(ProductApprovalConfig::STATUS_DRAFT)
            ->willReturn($this->getApplicableApprovalStatuses());

        return $productApprovalFacadeMock;
    }

    /**
     * @return array<string>
     */
    protected function getApplicableApprovalStatuses(): array
    {
        return [
            ProductApprovalConfig::STATUS_DENIED,
            ProductApprovalConfig::STATUS_APPROVED,
            ProductApprovalConfig::STATUS_WAITING_FOR_APPROVAL,
        ];
    }

    protected function formatAllowedProductStatuses(): string
    {
        return implode(', ', array_merge([ProductApprovalConfig::STATUS_DRAFT], $this->getApplicableApprovalStatuses()));
    }

    /**
     * @return array<string, string>
     */
    protected function buildAbstractRow(string $productStatus): array
    {
        return [
            static::COLUMN_ABSTRACT_SKU => 'abstract-sku-product-status-test',
            static::COLUMN_PRODUCT_STATUS => $productStatus,
            static::COLUMN_TAX_SET_NAME => '',
        ];
    }

    /**
     * @param array<int, array<string, string>> $rows
     */
    protected function executeStep(array $rows): ImportStepResponseTransfer
    {
        /** @var \SprykerFeature\Zed\ProductExperienceManagement\Business\ProductExperienceManagementBusinessFactory $factory */
        $factory = $this->tester->getFactory();

        return $factory->createProductCsvImportAbstractStep()->executeBatch(
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
