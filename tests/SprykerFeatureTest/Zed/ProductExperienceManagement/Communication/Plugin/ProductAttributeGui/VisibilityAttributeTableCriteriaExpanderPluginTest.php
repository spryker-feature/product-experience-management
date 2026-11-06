<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttributeGui;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductAttributeTableCriteriaTransfer;
use Generated\Shared\Transfer\ProductAttributeTableQueryConditionTransfer;
use Orm\Zed\ProductAttribute\Persistence\Map\SpyProductManagementAttributeTableMap;
use SprykerFeature\Shared\ProductExperienceManagement\ProductExperienceManagementConfig;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttributeGui\VisibilityAttributeTableCriteriaExpanderPlugin;
use SprykerFeatureTest\Zed\ProductExperienceManagement\ProductExperienceManagementCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group ProductExperienceManagement
 * @group Communication
 * @group Plugin
 * @group ProductAttributeGui
 * @group VisibilityAttributeTableCriteriaExpanderPluginTest
 * Add your own group annotations below this line
 */
class VisibilityAttributeTableCriteriaExpanderPluginTest extends Unit
{
    /**
     * @uses \SprykerFeature\Zed\ProductExperienceManagement\Communication\Expander\VisibilityAttributeTableExpander::COL_VISIBILITY
     */
    protected const string COLUMN_ALIAS_VISIBILITY = 'visibility';

    /**
     * @uses \SprykerFeature\Zed\ProductExperienceManagement\Communication\Expander\VisibilityAttributeTableExpander::COMBINE_OPERATOR
     */
    protected const string CONDITION_COMBINE_OPERATOR_OR = 'or';

    protected ProductExperienceManagementCommunicationTester $tester;

    public function testGivenNoVisibilityTypeFilterWhenExpandProductAttributeTableCriteriaThenOnlyTheVisibilityColumnIsAdded(): void
    {
        // Arrange
        $productAttributeTableCriteriaTransfer = new ProductAttributeTableCriteriaTransfer();

        // Act
        $productAttributeTableCriteriaTransfer = (new VisibilityAttributeTableCriteriaExpanderPlugin())
            ->expandProductAttributeTableCriteria($productAttributeTableCriteriaTransfer);

        // Assert
        $this->assertSame(
            [SpyProductManagementAttributeTableMap::COL_VISIBILITY => static::COLUMN_ALIAS_VISIBILITY],
            $productAttributeTableCriteriaTransfer->getWithColumns(),
        );
        $this->assertCount(0, $productAttributeTableCriteriaTransfer->getQueryConditions());
        $this->assertNull($productAttributeTableCriteriaTransfer->getConditionCombineOperator());
    }

    /**
     * @dataProvider getStorefrontVisibilityTypesDataProvider
     */
    public function testGivenAStorefrontVisibilityTypeFilterWhenExpandProductAttributeTableCriteriaThenAttributesContainingThatTypeAreMatched(
        string $visibilityType
    ): void {
        // Arrange
        $productAttributeTableCriteriaTransfer = (new ProductAttributeTableCriteriaTransfer())
            ->setVisibilityTypes([$visibilityType]);

        // Act
        $productAttributeTableCriteriaTransfer = (new VisibilityAttributeTableCriteriaExpanderPlugin())
            ->expandProductAttributeTableCriteria($productAttributeTableCriteriaTransfer);

        // Assert
        $this->assertSame(
            [[$this->createContainsVisibilityTypeExpression(), sprintf('%%%s%%', $visibilityType)]],
            $this->extractQueryConditions($productAttributeTableCriteriaTransfer),
        );
        $this->assertSame(static::CONDITION_COMBINE_OPERATOR_OR, $productAttributeTableCriteriaTransfer->getConditionCombineOperator());
    }

    public function testGivenTheInternalVisibilityTypeFilterWhenExpandProductAttributeTableCriteriaThenAttributesWithoutAnyVisibilityAreMatched(): void
    {
        // Arrange
        $productAttributeTableCriteriaTransfer = (new ProductAttributeTableCriteriaTransfer())
            ->setVisibilityTypes([ProductExperienceManagementConfig::VISIBILITY_TYPE_INTERNAL]);

        // Act
        $productAttributeTableCriteriaTransfer = (new VisibilityAttributeTableCriteriaExpanderPlugin())
            ->expandProductAttributeTableCriteria($productAttributeTableCriteriaTransfer);

        // Assert
        $expectedExpression = sprintf(
            '(%s IS NULL OR %s = ?)',
            SpyProductManagementAttributeTableMap::COL_VISIBILITY,
            SpyProductManagementAttributeTableMap::COL_VISIBILITY,
        );
        $this->assertSame(
            [[$expectedExpression, '']],
            $this->extractQueryConditions($productAttributeTableCriteriaTransfer),
        );
        $this->assertSame(static::CONDITION_COMBINE_OPERATOR_OR, $productAttributeTableCriteriaTransfer->getConditionCombineOperator());
    }

    public function testGivenSeveralVisibilityTypeFiltersWhenExpandProductAttributeTableCriteriaThenOneConditionPerTypeIsCombinedWithOr(): void
    {
        // Arrange
        $productAttributeTableCriteriaTransfer = (new ProductAttributeTableCriteriaTransfer())
            ->setVisibilityTypes([
                ProductExperienceManagementConfig::VISIBILITY_TYPE_PDP,
                ProductExperienceManagementConfig::VISIBILITY_TYPE_PLP,
                ProductExperienceManagementConfig::VISIBILITY_TYPE_CART,
            ]);

        // Act
        $productAttributeTableCriteriaTransfer = (new VisibilityAttributeTableCriteriaExpanderPlugin())
            ->expandProductAttributeTableCriteria($productAttributeTableCriteriaTransfer);

        // Assert
        $containsVisibilityTypeExpression = $this->createContainsVisibilityTypeExpression();
        $this->assertSame(
            [
                [$containsVisibilityTypeExpression, sprintf('%%%s%%', ProductExperienceManagementConfig::VISIBILITY_TYPE_PDP)],
                [$containsVisibilityTypeExpression, sprintf('%%%s%%', ProductExperienceManagementConfig::VISIBILITY_TYPE_PLP)],
                [$containsVisibilityTypeExpression, sprintf('%%%s%%', ProductExperienceManagementConfig::VISIBILITY_TYPE_CART)],
            ],
            $this->extractQueryConditions($productAttributeTableCriteriaTransfer),
        );
        $this->assertSame(static::CONDITION_COMBINE_OPERATOR_OR, $productAttributeTableCriteriaTransfer->getConditionCombineOperator());
    }

    /**
     * @return array<string, list<string>>
     */
    protected function getStorefrontVisibilityTypesDataProvider(): array
    {
        return [
            'product detail page' => [ProductExperienceManagementConfig::VISIBILITY_TYPE_PDP],
            'product listing page' => [ProductExperienceManagementConfig::VISIBILITY_TYPE_PLP],
            'cart' => [ProductExperienceManagementConfig::VISIBILITY_TYPE_CART],
        ];
    }

    protected function createContainsVisibilityTypeExpression(): string
    {
        return sprintf('%s LIKE ?', SpyProductManagementAttributeTableMap::COL_VISIBILITY);
    }

    /**
     * @return list<array{string|null, string|null}>
     */
    protected function extractQueryConditions(ProductAttributeTableCriteriaTransfer $productAttributeTableCriteriaTransfer): array
    {
        return array_map(
            static fn (ProductAttributeTableQueryConditionTransfer $productAttributeTableQueryConditionTransfer): array => [
                $productAttributeTableQueryConditionTransfer->getExpression(),
                $productAttributeTableQueryConditionTransfer->getValue(),
            ],
            $productAttributeTableCriteriaTransfer->getQueryConditions()->getArrayCopy(),
        );
    }
}
