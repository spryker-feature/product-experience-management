<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\ProductExperienceManagement\Communication\Plugin\Publisher;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\EventEntityTransfer;
use Orm\Zed\ProductExperienceManagement\Persistence\SpyProductAttributeStorageQuery;
use SprykerFeature\Shared\ProductExperienceManagement\ProductExperienceManagementConfig;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\Publisher\ProductAttributeWritePublisherPlugin;
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
 * @group ProductAttributeWritePublisherPluginTest
 * Add your own group annotations below this line
 */
class ProductAttributeWritePublisherPluginTest extends Unit
{
    protected ProductExperienceManagementCommunicationTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->setupQueueAdapters();
    }

    public function testHandleBulkWritesAttributeDataToStorage(): void
    {
        // Arrange
        $this->cleanupStorage();

        $productManagementAttributeEntity = $this->tester->haveProductManagementAttributeEntity(
            [
            'visibility' => implode(',', [
                ProductExperienceManagementConfig::VISIBILITY_TYPE_PDP,
                ProductExperienceManagementConfig::VISIBILITY_TYPE_PLP,
            ])],
            ['key' => 'test_publisher_write'],
        );

        $idProductManagementAttribute = $productManagementAttributeEntity->getIdProductManagementAttribute();

        $eventEntityTransfers = [
            (new EventEntityTransfer())->setId($idProductManagementAttribute),
        ];

        $publisherPlugin = new ProductAttributeWritePublisherPlugin();
        $publisherPlugin->setFacade($this->tester->getFacade());

        // Act
        $publisherPlugin->handleBulk(
            $eventEntityTransfers,
            ProductExperienceManagementConfig::PRODUCT_ATTRIBUTE_PUBLISH,
        );

        // Assert
        $productAttributeStorageEntity = SpyProductAttributeStorageQuery::create()
            ->filterByFkProductManagementAttribute($idProductManagementAttribute)
            ->findOne();

        $this->assertNotNull($productAttributeStorageEntity);
        $this->assertSame('test_publisher_write', $productAttributeStorageEntity->getAttributeKey());

        $storageData = $productAttributeStorageEntity->getData();
        $this->assertSame('test_publisher_write', $storageData['key']);
        $this->assertContains(ProductExperienceManagementConfig::VISIBILITY_TYPE_PDP, $storageData['visibility_types']);
        $this->assertContains(ProductExperienceManagementConfig::VISIBILITY_TYPE_PLP, $storageData['visibility_types']);
    }

    public function testHandleBulkDoesNothingForEmptyEvents(): void
    {
        // Arrange
        $countBefore = SpyProductAttributeStorageQuery::create()->count();

        $publisherPlugin = new ProductAttributeWritePublisherPlugin();
        $publisherPlugin->setFacade($this->tester->getFacade());

        // Act
        $publisherPlugin->handleBulk([], ProductExperienceManagementConfig::PRODUCT_ATTRIBUTE_PUBLISH);

        // Assert
        $this->assertSame($countBefore, SpyProductAttributeStorageQuery::create()->count());
    }

    public function testHandleBulkUpdatesExistingStorageRecord(): void
    {
        // Arrange
        $this->cleanupStorage();

        $productManagementAttributeEntity = $this->tester->haveProductManagementAttributeEntity(
            ['visibility' => ProductExperienceManagementConfig::VISIBILITY_TYPE_PDP],
            ['key' => 'test_publisher_update'],
        );

        $idProductManagementAttribute = $productManagementAttributeEntity->getIdProductManagementAttribute();
        $eventEntityTransfers = [
            (new EventEntityTransfer())->setId($idProductManagementAttribute),
        ];

        $publisherPlugin = new ProductAttributeWritePublisherPlugin();
        $publisherPlugin->setFacade($this->tester->getFacade());

        $publisherPlugin->handleBulk(
            $eventEntityTransfers,
            ProductExperienceManagementConfig::ENTITY_SPY_PRODUCT_MANAGEMENT_ATTRIBUTE_CREATE,
        );

        $productManagementAttributeEntity->setVisibility(
            implode(',', [
                ProductExperienceManagementConfig::VISIBILITY_TYPE_PDP,
                ProductExperienceManagementConfig::VISIBILITY_TYPE_CART,
            ]),
        );
        $productManagementAttributeEntity->save();

        // Act
        $publisherPlugin->handleBulk(
            $eventEntityTransfers,
            ProductExperienceManagementConfig::ENTITY_SPY_PRODUCT_MANAGEMENT_ATTRIBUTE_UPDATE,
        );

        // Assert
        $productAttributeStorageEntities = SpyProductAttributeStorageQuery::create()
            ->filterByFkProductManagementAttribute($idProductManagementAttribute)
            ->find();

        $this->assertCount(1, $productAttributeStorageEntities);

        $storageData = $productAttributeStorageEntities->getFirst()->getData();
        $this->assertContains(ProductExperienceManagementConfig::VISIBILITY_TYPE_PDP, $storageData['visibility_types']);
        $this->assertContains(ProductExperienceManagementConfig::VISIBILITY_TYPE_CART, $storageData['visibility_types']);
        $this->assertNotContains(ProductExperienceManagementConfig::VISIBILITY_TYPE_PLP, $storageData['visibility_types']);
    }

    public function testGetSubscribedEventsReturnsExpectedEvents(): void
    {
        // Arrange
        $publisherPlugin = new ProductAttributeWritePublisherPlugin();

        // Act
        $subscribedEvents = $publisherPlugin->getSubscribedEvents();

        // Assert
        $this->assertContains(ProductExperienceManagementConfig::PRODUCT_ATTRIBUTE_PUBLISH, $subscribedEvents);
        $this->assertContains(ProductExperienceManagementConfig::ENTITY_SPY_PRODUCT_MANAGEMENT_ATTRIBUTE_CREATE, $subscribedEvents);
        $this->assertContains(ProductExperienceManagementConfig::ENTITY_SPY_PRODUCT_MANAGEMENT_ATTRIBUTE_UPDATE, $subscribedEvents);
    }

    protected function cleanupStorage(): void
    {
        SpyProductAttributeStorageQuery::create()->deleteAll();
    }

    protected function _afterEach(): void
    {
        parent::_afterEach();

        $this->cleanupStorage();
    }
}
