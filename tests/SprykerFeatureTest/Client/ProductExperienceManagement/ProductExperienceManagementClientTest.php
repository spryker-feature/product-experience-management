<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Client\ProductExperienceManagement;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductAttributeStorageCriteriaTransfer;
use Generated\Shared\Transfer\ProductAttributeStorageTransfer;
use Spryker\Client\Storage\StorageClientInterface;
use SprykerFeature\Client\ProductExperienceManagement\ProductExperienceManagementDependencyProvider;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Client
 * @group ProductExperienceManagement
 * @group ProductExperienceManagementClientTest
 * Add your own group annotations below this line
 */
class ProductExperienceManagementClientTest extends Unit
{
    protected ProductExperienceManagementClientTester $tester;

    public function testGetProductAttributeStorageCollectionReturnsTransfersFromStorage(): void
    {
        // Arrange
        $storageClientMock = $this->createStorageClientMock([
            'key_0' => json_encode([
                'key' => 'color',
                'input_type' => 'text',
                'is_super' => false,
                'visibility_types' => ['PDP', 'PLP'],
            ]),
            'key_1' => json_encode([
                'key' => 'size',
                'input_type' => 'text',
                'is_super' => true,
                'visibility_types' => ['PDP'],
            ]),
        ]);

        $this->tester->setDependency(
            ProductExperienceManagementDependencyProvider::CLIENT_STORAGE,
            $storageClientMock,
        );

        $criteriaTransfer = (new ProductAttributeStorageCriteriaTransfer())
            ->setAttributeKeys(['color', 'size']);

        // Act
        $collectionTransfer = $this->tester->getClient()->getProductAttributeStorageCollection($criteriaTransfer);

        // Assert
        $productAttributeStorages = $collectionTransfer->getProductAttributeStorages();
        $this->assertCount(2, $productAttributeStorages);

        $indexed = $this->indexByKey($productAttributeStorages);
        $this->assertArrayHasKey('color', $indexed);
        $this->assertArrayHasKey('size', $indexed);
        $this->assertInstanceOf(ProductAttributeStorageTransfer::class, $indexed['color']);
        $this->assertSame(['PDP', 'PLP'], $indexed['color']->getVisibilityTypes());
        $this->assertSame(['PDP'], $indexed['size']->getVisibilityTypes());
        $this->assertTrue($indexed['size']->getIsSuper());
        $this->assertFalse($indexed['color']->getIsSuper());
    }

    public function testGetProductAttributeStorageCollectionReturnsEmptyCollectionForEmptyKeys(): void
    {
        // Arrange
        $storageClientMock = $this->createStorageClientMock([]);

        $this->tester->setDependency(
            ProductExperienceManagementDependencyProvider::CLIENT_STORAGE,
            $storageClientMock,
        );

        $criteriaTransfer = (new ProductAttributeStorageCriteriaTransfer())
            ->setAttributeKeys([]);

        // Act
        $collectionTransfer = $this->tester->getClient()->getProductAttributeStorageCollection($criteriaTransfer);

        // Assert
        $this->assertCount(0, $collectionTransfer->getProductAttributeStorages());
    }

    public function testGetProductAttributeStorageCollectionSkipsNullStorageEntries(): void
    {
        // Arrange
        $storageClientMock = $this->createStorageClientMock([
            'key_0' => json_encode([
                'key' => 'color',
                'input_type' => 'text',
                'is_super' => false,
                'visibility_types' => ['PDP'],
            ]),
            'key_1' => null,
        ]);

        $this->tester->setDependency(
            ProductExperienceManagementDependencyProvider::CLIENT_STORAGE,
            $storageClientMock,
        );

        $criteriaTransfer = (new ProductAttributeStorageCriteriaTransfer())
            ->setAttributeKeys(['color', 'nonexistent']);

        // Act
        $collectionTransfer = $this->tester->getClient()->getProductAttributeStorageCollection($criteriaTransfer);

        // Assert
        $productAttributeStorages = $collectionTransfer->getProductAttributeStorages();
        $this->assertCount(1, $productAttributeStorages);

        $indexed = $this->indexByKey($productAttributeStorages);
        $this->assertArrayHasKey('color', $indexed);
        $this->assertArrayNotHasKey('nonexistent', $indexed);
    }

    public function testGetProductAttributeStorageCollectionHandlesArrayStorageData(): void
    {
        // Arrange
        $storageClientMock = $this->createStorageClientMock([
            'key_0' => [
                'key' => 'material',
                'input_type' => 'text',
                'is_super' => false,
                'visibility_types' => ['Cart'],
            ],
        ]);

        $this->tester->setDependency(
            ProductExperienceManagementDependencyProvider::CLIENT_STORAGE,
            $storageClientMock,
        );

        $criteriaTransfer = (new ProductAttributeStorageCriteriaTransfer())
            ->setAttributeKeys(['material']);

        // Act
        $collectionTransfer = $this->tester->getClient()->getProductAttributeStorageCollection($criteriaTransfer);

        // Assert
        $productAttributeStorages = $collectionTransfer->getProductAttributeStorages();
        $this->assertCount(1, $productAttributeStorages);

        $indexed = $this->indexByKey($productAttributeStorages);
        $this->assertArrayHasKey('material', $indexed);
        $this->assertSame(['Cart'], $indexed['material']->getVisibilityTypes());
    }

    /**
     * @param array<string, mixed> $multiGetReturn
     */
    protected function createStorageClientMock(array $multiGetReturn): StorageClientInterface
    {
        $storageClientMock = $this->createMock(StorageClientInterface::class);
        $storageClientMock->method('getMulti')
            ->willReturn($multiGetReturn);

        return $storageClientMock;
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ProductAttributeStorageTransfer> $productAttributeStorages
     *
     * @return array<string, \Generated\Shared\Transfer\ProductAttributeStorageTransfer>
     */
    protected function indexByKey(ArrayObject $productAttributeStorages): array
    {
        $indexed = [];

        foreach ($productAttributeStorages as $transfer) {
            $indexed[$transfer->getKey()] = $transfer;
        }

        return $indexed;
    }
}
