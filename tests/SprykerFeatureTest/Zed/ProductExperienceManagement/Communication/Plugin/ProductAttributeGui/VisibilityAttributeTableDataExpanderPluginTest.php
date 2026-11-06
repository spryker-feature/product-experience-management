<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttributeGui;

use Codeception\Test\Unit;
use SprykerFeature\Shared\ProductExperienceManagement\ProductExperienceManagementConfig;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttributeGui\VisibilityAttributeTableDataExpanderPlugin;
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
 * @group VisibilityAttributeTableDataExpanderPluginTest
 * Add your own group annotations below this line
 */
class VisibilityAttributeTableDataExpanderPluginTest extends Unit
{
    /**
     * @uses \SprykerFeature\Zed\ProductExperienceManagement\Communication\Expander\VisibilityAttributeTableExpander::COL_VISIBILITY
     */
    protected const string COLUMN_VISIBILITY = 'visibility';

    /**
     * @uses \SprykerFeature\Zed\ProductExperienceManagement\Communication\Expander\VisibilityAttributeTableExpander::BADGE_TEMPLATE
     */
    protected const string BADGE_TEMPLATE = '<span class="badge text-bg-light">%s</span>';

    protected ProductExperienceManagementCommunicationTester $tester;

    public function testGivenASingleVisibilityTypeWhenExpandDataThenOneBadgeWithThatLabelIsRendered(): void
    {
        // Arrange
        $item = [static::COLUMN_VISIBILITY => ProductExperienceManagementConfig::VISIBILITY_TYPE_PLP];

        // Act
        $expandedItem = (new VisibilityAttributeTableDataExpanderPlugin())->expandData($item);

        // Assert
        $this->assertSame(
            [static::COLUMN_VISIBILITY => sprintf(static::BADGE_TEMPLATE, ProductExperienceManagementConfig::VISIBILITY_TYPE_PLP)],
            $expandedItem,
        );
    }

    public function testGivenEveryVisibilityTypeWhenExpandDataThenOneBadgePerTypeIsRenderedInStoredOrder(): void
    {
        // Arrange
        $item = [
            static::COLUMN_VISIBILITY => sprintf(
                '%s,%s,%s',
                ProductExperienceManagementConfig::VISIBILITY_TYPE_PDP,
                ProductExperienceManagementConfig::VISIBILITY_TYPE_PLP,
                ProductExperienceManagementConfig::VISIBILITY_TYPE_CART,
            ),
        ];

        // Act
        $expandedItem = (new VisibilityAttributeTableDataExpanderPlugin())->expandData($item);

        // Assert
        $expectedBadges = implode(' ', [
            sprintf(static::BADGE_TEMPLATE, ProductExperienceManagementConfig::VISIBILITY_TYPE_PDP),
            sprintf(static::BADGE_TEMPLATE, ProductExperienceManagementConfig::VISIBILITY_TYPE_PLP),
            sprintf(static::BADGE_TEMPLATE, ProductExperienceManagementConfig::VISIBILITY_TYPE_CART),
        ]);
        $this->assertSame([static::COLUMN_VISIBILITY => $expectedBadges], $expandedItem);
    }

    public function testGivenAnAttributeWithoutVisibilityWhenExpandDataThenTheVisibilityCellIsEmpty(): void
    {
        // Arrange
        $item = [static::COLUMN_VISIBILITY => ''];

        // Act
        $expandedItem = (new VisibilityAttributeTableDataExpanderPlugin())->expandData($item);

        // Assert
        $this->assertSame([static::COLUMN_VISIBILITY => ''], $expandedItem);
    }

    public function testGivenARowWithoutTheVisibilityColumnWhenExpandDataThenTheVisibilityCellIsEmpty(): void
    {
        // Act
        $expandedItem = (new VisibilityAttributeTableDataExpanderPlugin())->expandData([]);

        // Assert
        $this->assertSame([static::COLUMN_VISIBILITY => ''], $expandedItem);
    }
}
