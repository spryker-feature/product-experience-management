<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttribute;

use Codeception\Test\Unit;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttribute\VisibilitySuggestKeysExpanderPlugin;
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
 * @group VisibilitySuggestKeysExpanderPluginTest
 * Add your own group annotations below this line
 */
class VisibilitySuggestKeysExpanderPluginTest extends Unit
{
    protected ProductExperienceManagementCommunicationTester $tester;

    public function testExpandSuggestKeysReturnsEmptyArrayWhenInputIsEmpty(): void
    {
        // Arrange
        $plugin = new VisibilitySuggestKeysExpanderPlugin();

        // Act
        $result = $plugin->expandSuggestKeys([]);

        // Assert
        $this->assertSame([], $result);
    }

    public function testExpandSuggestKeysAppendsSingleVisibilityBadgeToExtensionColumnValues(): void
    {
        // Arrange
        $plugin = new VisibilitySuggestKeysExpanderPlugin();
        $suggestKeys = [
            ['key' => 'color', 'visibility' => 'pdp'],
        ];

        // Act
        $result = $plugin->expandSuggestKeys($suggestKeys);

        // Assert
        $this->assertArrayHasKey('extension_column_values', $result[0]);
        $this->assertContains('<span class="badge text-bg-light">pdp</span>', $result[0]['extension_column_values']);
    }

    public function testExpandSuggestKeysAppendsMultipleVisibilityBadgesJoinedBySpace(): void
    {
        // Arrange
        $plugin = new VisibilitySuggestKeysExpanderPlugin();
        $suggestKeys = [
            ['key' => 'size', 'visibility' => 'pdp,plp'],
        ];

        // Act
        $result = $plugin->expandSuggestKeys($suggestKeys);

        // Assert
        $expectedBadge = '<span class="badge text-bg-light">pdp</span> <span class="badge text-bg-light">plp</span>';
        $this->assertContains($expectedBadge, $result[0]['extension_column_values']);
    }

    public function testExpandSuggestKeysAppendsEmptyStringWhenVisibilityIsEmpty(): void
    {
        // Arrange
        $plugin = new VisibilitySuggestKeysExpanderPlugin();
        $suggestKeys = [
            ['key' => 'brand', 'visibility' => ''],
        ];

        // Act
        $result = $plugin->expandSuggestKeys($suggestKeys);

        // Assert
        $this->assertArrayHasKey('extension_column_values', $result[0]);
        $this->assertContains('', $result[0]['extension_column_values']);
    }

    public function testExpandSuggestKeysAppendsEmptyStringWhenVisibilityColumnIsMissing(): void
    {
        // Arrange
        $plugin = new VisibilitySuggestKeysExpanderPlugin();
        $suggestKeys = [
            ['key' => 'material'],
        ];

        // Act
        $result = $plugin->expandSuggestKeys($suggestKeys);

        // Assert
        $this->assertArrayHasKey('extension_column_values', $result[0]);
        $this->assertContains('', $result[0]['extension_column_values']);
    }

    public function testExpandSuggestKeysProcessesAllItemsInInput(): void
    {
        // Arrange
        $plugin = new VisibilitySuggestKeysExpanderPlugin();
        $suggestKeys = [
            ['key' => 'color', 'visibility' => 'pdp'],
            ['key' => 'size', 'visibility' => 'plp'],
            ['key' => 'brand', 'visibility' => ''],
        ];

        // Act
        $result = $plugin->expandSuggestKeys($suggestKeys);

        // Assert
        $this->assertCount(3, $result);

        foreach ($result as $item) {
            $this->assertArrayHasKey('extension_column_values', $item);
        }
    }

    public function testExpandSuggestKeysPreservesExistingExtensionColumnValues(): void
    {
        // Arrange
        $plugin = new VisibilitySuggestKeysExpanderPlugin();
        $suggestKeys = [
            [
                'key' => 'color',
                'visibility' => 'pdp',
                'extension_column_values' => ['existing_value'],
            ],
        ];

        // Act
        $result = $plugin->expandSuggestKeys($suggestKeys);

        // Assert
        $this->assertContains('existing_value', $result[0]['extension_column_values']);
        $this->assertContains('<span class="badge text-bg-light">pdp</span>', $result[0]['extension_column_values']);
    }

    public function testExpandSuggestKeysTrimsBadgeLabels(): void
    {
        // Arrange
        $plugin = new VisibilitySuggestKeysExpanderPlugin();
        $suggestKeys = [
            ['key' => 'color', 'visibility' => 'pdp, plp'],
        ];

        // Act
        $result = $plugin->expandSuggestKeys($suggestKeys);

        // Assert
        $expectedBadge = '<span class="badge text-bg-light">pdp</span> <span class="badge text-bg-light">plp</span>';
        $this->assertContains($expectedBadge, $result[0]['extension_column_values']);
    }
}
