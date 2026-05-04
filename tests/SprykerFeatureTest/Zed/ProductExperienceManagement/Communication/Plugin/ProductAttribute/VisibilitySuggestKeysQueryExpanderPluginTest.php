<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttribute;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductAttributeQueryCriteriaTransfer;
use Orm\Zed\ProductAttribute\Persistence\Map\SpyProductManagementAttributeTableMap;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttribute\VisibilitySuggestKeysQueryExpanderPlugin;
use SprykerFeatureTest\Zed\ProductExperienceManagement\ProductExperienceManagementCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group ProductExperienceManagement
 * @group Communication
 * @group Plugin
 * @group ProductAttribute
 * @group VisibilitySuggestKeysQueryExpanderPluginTest
 * Add your own group annotations below this line
 */
class VisibilitySuggestKeysQueryExpanderPluginTest extends Unit
{
    protected ProductExperienceManagementCommunicationTester $tester;

    public function testExpandSuggestKeysQueryCriteriaAddsVisibilityColumn(): void
    {
        // Arrange
        $plugin = new VisibilitySuggestKeysQueryExpanderPlugin();
        $productAttributeQueryCriteriaTransfer = new ProductAttributeQueryCriteriaTransfer();

        // Act
        $result = $plugin->expandSuggestKeysQueryCriteria($productAttributeQueryCriteriaTransfer);

        // Assert
        $this->assertArrayHasKey(
            SpyProductManagementAttributeTableMap::COL_VISIBILITY,
            $result->getWithColumns(),
        );
        $this->assertSame(
            'visibility',
            $result->getWithColumns()[SpyProductManagementAttributeTableMap::COL_VISIBILITY],
        );
    }

    public function testExpandSuggestKeysQueryCriteriaPreservesExistingWithColumns(): void
    {
        // Arrange
        $plugin = new VisibilitySuggestKeysQueryExpanderPlugin();
        $existingColumn = 'spy_product_management_attribute.input_type';
        $productAttributeQueryCriteriaTransfer = (new ProductAttributeQueryCriteriaTransfer())
            ->setWithColumns([$existingColumn => 'input_type']);

        // Act
        $result = $plugin->expandSuggestKeysQueryCriteria($productAttributeQueryCriteriaTransfer);

        // Assert
        $this->assertArrayHasKey($existingColumn, $result->getWithColumns());
        $this->assertArrayHasKey(SpyProductManagementAttributeTableMap::COL_VISIBILITY, $result->getWithColumns());
    }

    public function testExpandSuggestKeysQueryCriteriaReturnsSameTransferInstance(): void
    {
        // Arrange
        $plugin = new VisibilitySuggestKeysQueryExpanderPlugin();
        $productAttributeQueryCriteriaTransfer = new ProductAttributeQueryCriteriaTransfer();

        // Act
        $result = $plugin->expandSuggestKeysQueryCriteria($productAttributeQueryCriteriaTransfer);

        // Assert
        $this->assertSame($productAttributeQueryCriteriaTransfer, $result);
    }
}
