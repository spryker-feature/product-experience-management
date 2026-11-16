<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ImportStepResponseTransfer;
use Generated\Shared\Transfer\MerchantProductTransfer;
use Orm\Zed\MerchantProduct\Persistence\SpyMerchantProductAbstractQuery;
use Spryker\Zed\Product\Dependency\ProductEvents;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\ProductCsvImportMerchantStep;
use SprykerFeatureTest\Zed\ProductExperienceManagement\ProductExperienceManagementBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group ProductExperienceManagement
 * @group Business
 * @group ImportStep
 * @group ProductCsvImport
 * @group ProductCsvImportMerchantStepTest
 */
class ProductCsvImportMerchantStepTest extends Unit
{
    protected const string COLUMN_ABSTRACT_SKU = 'abstract_sku';

    protected const string COLUMN_CONCRETE_SKU = 'concrete_sku';

    protected const string COLUMN_MERCHANT = 'merchant';

    protected const int CSV_ROW_NUMBER = 2;

    protected const string UNKNOWN_MERCHANT_REFERENCE = 'unknown-merchant-reference';

    protected ProductExperienceManagementBusinessTester $tester;

    protected function _after(): void
    {
        $this->createStep()->resetCache();

        parent::_after();
    }

    public function testExecuteBatchReassignsProductAbstractToMerchantFromFileWhenItBelongsToAnotherMerchant(): void
    {
        // Arrange
        $productAbstractTransfer = $this->tester->haveProductAbstract();
        $oldMerchantTransfer = $this->tester->haveMerchant();
        $this->haveMerchantProductAbstract($oldMerchantTransfer->getIdMerchantOrFail(), $productAbstractTransfer->getIdProductAbstractOrFail());
        $newMerchantTransfer = $this->tester->haveMerchant();

        // Act
        $importStepResponseTransfer = $this->executeStep($productAbstractTransfer->getSkuOrFail(), $newMerchantTransfer->getMerchantReferenceOrFail());

        // Assert
        $this->assertTrue($importStepResponseTransfer->getIsSuccessful());
        $this->assertSame([$newMerchantTransfer->getIdMerchantOrFail()], $this->findMerchantIds($productAbstractTransfer->getIdProductAbstractOrFail()));
        $this->assertProductAbstractPublishEvent($importStepResponseTransfer, $productAbstractTransfer->getIdProductAbstractOrFail());
    }

    public function testExecuteBatchKeepsOneAssignmentWhenProductAbstractIsImportedAgainWithSameMerchant(): void
    {
        // Arrange
        $productAbstractTransfer = $this->tester->haveProductAbstract();
        $merchantTransfer = $this->tester->haveMerchant();
        $this->haveMerchantProductAbstract($merchantTransfer->getIdMerchantOrFail(), $productAbstractTransfer->getIdProductAbstractOrFail());

        // Act
        $importStepResponseTransfer = $this->executeStep($productAbstractTransfer->getSkuOrFail(), $merchantTransfer->getMerchantReferenceOrFail());

        // Assert
        $this->assertTrue($importStepResponseTransfer->getIsSuccessful());
        $this->assertSame([$merchantTransfer->getIdMerchantOrFail()], $this->findMerchantIds($productAbstractTransfer->getIdProductAbstractOrFail()));
    }

    public function testExecuteBatchAssignsMerchantFromFileWhenProductAbstractHasNoMerchant(): void
    {
        // Arrange
        $productAbstractTransfer = $this->tester->haveProductAbstract();
        $merchantTransfer = $this->tester->haveMerchant();

        // Act
        $importStepResponseTransfer = $this->executeStep($productAbstractTransfer->getSkuOrFail(), $merchantTransfer->getMerchantReferenceOrFail());

        // Assert
        $this->assertTrue($importStepResponseTransfer->getIsSuccessful());
        $this->assertSame([$merchantTransfer->getIdMerchantOrFail()], $this->findMerchantIds($productAbstractTransfer->getIdProductAbstractOrFail()));
        $this->assertProductAbstractPublishEvent($importStepResponseTransfer, $productAbstractTransfer->getIdProductAbstractOrFail());
    }

    public function testExecuteBatchReturnsErrorAndAssignsNoMerchantWhenMerchantFromFileDoesNotExist(): void
    {
        // Arrange
        $productAbstractTransfer = $this->tester->haveProductAbstract();

        // Act
        $importStepResponseTransfer = $this->executeStep($productAbstractTransfer->getSkuOrFail(), static::UNKNOWN_MERCHANT_REFERENCE);

        // Assert
        $this->assertFalse($importStepResponseTransfer->getIsSuccessful());
        $this->assertSame(static::CSV_ROW_NUMBER, $importStepResponseTransfer->getErrors()->offsetGet(0)->getCsvRowNumber());
        $this->assertSame([], $this->findMerchantIds($productAbstractTransfer->getIdProductAbstractOrFail()));
    }

    public function testExecuteBatchRemovesMerchantAssignmentWhenMerchantValueIsEmpty(): void
    {
        // Arrange
        $productAbstractTransfer = $this->tester->haveProductAbstract();
        $this->haveMerchantProductAbstract($this->tester->haveMerchant()->getIdMerchantOrFail(), $productAbstractTransfer->getIdProductAbstractOrFail());

        // Act
        $importStepResponseTransfer = $this->executeStep($productAbstractTransfer->getSkuOrFail(), '');

        // Assert
        $this->assertTrue($importStepResponseTransfer->getIsSuccessful());
        $this->assertSame([], $this->findMerchantIds($productAbstractTransfer->getIdProductAbstractOrFail()));
        $this->assertProductAbstractPublishEvent($importStepResponseTransfer, $productAbstractTransfer->getIdProductAbstractOrFail());
    }

    public function testExecuteBatchReportsNoChangeWhenMerchantValueIsEmptyAndProductAbstractHasNoMerchant(): void
    {
        // Arrange
        $productAbstractTransfer = $this->tester->haveProductAbstract();

        // Act
        $importStepResponseTransfer = $this->executeStep($productAbstractTransfer->getSkuOrFail(), '');

        // Assert
        $this->assertTrue($importStepResponseTransfer->getIsSuccessful());
        $this->assertCount(0, $importStepResponseTransfer->getPublishEvents());
    }

    public function testExecuteBatchKeepsMerchantAssignmentWhenMerchantColumnIsAbsent(): void
    {
        // Arrange
        $productAbstractTransfer = $this->tester->haveProductAbstract();
        $merchantTransfer = $this->tester->haveMerchant();
        $this->haveMerchantProductAbstract($merchantTransfer->getIdMerchantOrFail(), $productAbstractTransfer->getIdProductAbstractOrFail());

        // Act
        $importStepResponseTransfer = $this->executeStepWithRows([static::CSV_ROW_NUMBER => [static::COLUMN_ABSTRACT_SKU => $productAbstractTransfer->getSkuOrFail()]]);

        // Assert
        $this->assertTrue($importStepResponseTransfer->getIsSuccessful());
        $this->assertSame([$merchantTransfer->getIdMerchantOrFail()], $this->findMerchantIds($productAbstractTransfer->getIdProductAbstractOrFail()));
    }

    public function testExecuteBatchAppliesLastRowWhenSameProductAbstractAppearsTwiceInBatch(): void
    {
        // Arrange
        $productAbstractTransfer = $this->tester->haveProductAbstract();
        $merchantTransfer = $this->tester->haveMerchant();
        $this->haveMerchantProductAbstract($this->tester->haveMerchant()->getIdMerchantOrFail(), $productAbstractTransfer->getIdProductAbstractOrFail());

        // Act
        $importStepResponseTransfer = $this->executeStepWithRows([
            static::CSV_ROW_NUMBER => [static::COLUMN_ABSTRACT_SKU => $productAbstractTransfer->getSkuOrFail(), static::COLUMN_MERCHANT => ''],
            static::CSV_ROW_NUMBER + 1 => [static::COLUMN_ABSTRACT_SKU => $productAbstractTransfer->getSkuOrFail(), static::COLUMN_MERCHANT => $merchantTransfer->getMerchantReferenceOrFail()],
        ]);

        // Assert
        $this->assertTrue($importStepResponseTransfer->getIsSuccessful());
        $this->assertSame([$merchantTransfer->getIdMerchantOrFail()], $this->findMerchantIds($productAbstractTransfer->getIdProductAbstractOrFail()));
    }

    public function testExecuteBatchKeepsMerchantAssignmentWhenConcreteRowOfSimpleProductHasEmptyMerchant(): void
    {
        // Arrange
        $productConcreteTransfer = $this->tester->haveProduct();
        $merchantTransfer = $this->tester->haveMerchant();
        $this->haveMerchantProductAbstract($merchantTransfer->getIdMerchantOrFail(), $productConcreteTransfer->getFkProductAbstractOrFail());

        // Act
        $importStepResponseTransfer = $this->executeStepWithRows([
            static::CSV_ROW_NUMBER => [
                static::COLUMN_ABSTRACT_SKU => $productConcreteTransfer->getAbstractSkuOrFail(),
                static::COLUMN_CONCRETE_SKU => $productConcreteTransfer->getSkuOrFail(),
                static::COLUMN_MERCHANT => '',
            ],
        ]);

        // Assert
        $this->assertTrue($importStepResponseTransfer->getIsSuccessful());
        $this->assertSame([$merchantTransfer->getIdMerchantOrFail()], $this->findMerchantIds($productConcreteTransfer->getFkProductAbstractOrFail()));
    }

    protected function createStep(): ProductCsvImportMerchantStep
    {
        /** @var \SprykerFeature\Zed\ProductExperienceManagement\Business\ProductExperienceManagementBusinessFactory $factory */
        $factory = $this->tester->getFactory();

        return $factory->createProductCsvImportMerchantStep();
    }

    protected function executeStep(string $abstractSku, string $merchantReference): ImportStepResponseTransfer
    {
        return $this->executeStepWithRows([
            static::CSV_ROW_NUMBER => [
                static::COLUMN_ABSTRACT_SKU => $abstractSku,
                static::COLUMN_MERCHANT => $merchantReference,
            ],
        ]);
    }

    /**
     * @param array<int, array<string, string>> $rows
     */
    protected function executeStepWithRows(array $rows): ImportStepResponseTransfer
    {
        $importStep = $this->createStep();
        $importStep->resetCache();

        return $importStep->executeBatch($rows);
    }

    protected function haveMerchantProductAbstract(int $idMerchant, int $idProductAbstract): void
    {
        $this->tester->haveMerchantProduct([
            MerchantProductTransfer::ID_MERCHANT => $idMerchant,
            MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $idProductAbstract,
        ]);
    }

    protected function assertProductAbstractPublishEvent(ImportStepResponseTransfer $importStepResponseTransfer, int $idProductAbstract): void
    {
        $this->assertCount(1, $importStepResponseTransfer->getPublishEvents());
        $this->assertSame(ProductEvents::PRODUCT_ABSTRACT_PUBLISH, $importStepResponseTransfer->getPublishEvents()->offsetGet(0)->getEventName());
        $this->assertSame($idProductAbstract, $importStepResponseTransfer->getPublishEvents()->offsetGet(0)->getEntityId());
    }

    /**
     * @return array<int>
     */
    protected function findMerchantIds(int $idProductAbstract): array
    {
        $merchantIds = [];

        foreach (SpyMerchantProductAbstractQuery::create()->filterByFkProductAbstract($idProductAbstract)->find() as $merchantProductAbstractEntity) {
            $merchantIds[] = $merchantProductAbstractEntity->getFkMerchant();
        }

        return $merchantIds;
    }
}
