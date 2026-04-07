<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\ProductExperienceManagement\Communication\Plugin\Publisher;

use Codeception\Test\Unit;
use SprykerFeature\Shared\ProductExperienceManagement\ProductExperienceManagementConfig;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\Publisher\ProductAttributePublisherTriggerPlugin;
use SprykerFeatureTest\Zed\ProductExperienceManagement\ProductExperienceManagementCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group ProductExperienceManagement
 * @group Communication
 * @group Plugin
 * @group Publisher
 * @group ProductAttributePublisherTriggerPluginTest
 * Add your own group annotations below this line
 */
class ProductAttributePublisherTriggerPluginTest extends Unit
{
    protected ProductExperienceManagementCommunicationTester $tester;

    public function testGetDataReturnsProductManagementAttributes(): void
    {
        // Arrange
        $this->tester->haveProductManagementAttributeEntity(
            [],
            ['key' => 'test_trigger_attribute'],
        );

        $triggerPlugin = new ProductAttributePublisherTriggerPlugin();
        $triggerPlugin->setFacade($this->tester->getFacade());

        // Act
        $data = $triggerPlugin->getData(0, 100);

        // Assert
        $this->assertNotEmpty($data);
    }

    public function testGetDataReturnsEmptyArrayForOutOfRangeOffset(): void
    {
        // Arrange
        $triggerPlugin = new ProductAttributePublisherTriggerPlugin();
        $triggerPlugin->setFacade($this->tester->getFacade());

        // Act
        $data = $triggerPlugin->getData(999999, 1);

        // Assert
        $this->assertEmpty($data);
    }

    public function testGetResourceNameReturnsProductAttributeResourceName(): void
    {
        // Arrange
        $triggerPlugin = new ProductAttributePublisherTriggerPlugin();

        // Act & Assert
        $this->assertSame(
            ProductExperienceManagementConfig::PRODUCT_ATTRIBUTE_RESOURCE_NAME,
            $triggerPlugin->getResourceName(),
        );
    }

    public function testGetEventNameReturnsPublishEventName(): void
    {
        // Arrange
        $triggerPlugin = new ProductAttributePublisherTriggerPlugin();

        // Act & Assert
        $this->assertSame(
            ProductExperienceManagementConfig::PRODUCT_ATTRIBUTE_PUBLISH,
            $triggerPlugin->getEventName(),
        );
    }

    public function testGetIdColumnNameReturnsExpectedColumn(): void
    {
        // Arrange
        $triggerPlugin = new ProductAttributePublisherTriggerPlugin();

        // Act & Assert
        $this->assertSame(
            'spy_product_management_attribute.id_product_management_attribute',
            $triggerPlugin->getIdColumnName(),
        );
    }
}
