<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ImportStepResponseTransfer;
use Orm\Zed\ProductCategory\Persistence\SpyProductCategory;
use Orm\Zed\ProductCategory\Persistence\SpyProductCategoryQuery;
use Spryker\Zed\ProductCategory\Dependency\ProductCategoryEvents;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\ProductCsvImportCategoryStep;
use SprykerFeatureTest\Zed\ProductExperienceManagement\ProductExperienceManagementBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group ProductExperienceManagement
 * @group Business
 * @group ImportStep
 * @group ProductCsvImport
 * @group ProductCsvImportCategoryStepTest
 */
class ProductCsvImportCategoryStepTest extends Unit
{
    protected const string COLUMN_ABSTRACT_SKU = 'abstract_sku';

    protected const string COLUMN_CONCRETE_SKU = 'concrete_sku';

    protected const string COLUMN_CATEGORIES = 'categories';

    protected const int CSV_ROW_NUMBER = 2;

    protected const int KEPT_PRODUCT_ORDER = 5;

    protected const string UNKNOWN_CATEGORY_KEY = 'unknown-category-key';

    protected ProductExperienceManagementBusinessTester $tester;

    protected function _after(): void
    {
        $this->createStep()->resetCache();

        parent::_after();
    }

    public function testExecuteBatchReplacesCategoryAssignmentsWithCategoriesFromFile(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->haveProductAbstract()->getIdProductAbstractOrFail();
        [$removedCategoryTransfer, $keptCategoryTransfer, $addedCategoryTransfer] = [$this->tester->haveCategory(), $this->tester->haveCategory(), $this->tester->haveCategory()];
        $this->haveProductCategories($idProductAbstract, [$removedCategoryTransfer->getIdCategoryOrFail(), $keptCategoryTransfer->getIdCategoryOrFail()]);

        // Act
        $importStepResponseTransfer = $this->executeStep([static::CSV_ROW_NUMBER => $this->createAbstractRow($idProductAbstract, $keptCategoryTransfer->getCategoryKeyOrFail() . ';' . $addedCategoryTransfer->getCategoryKeyOrFail())]);

        // Assert
        $this->assertTrue($importStepResponseTransfer->getIsSuccessful());
        $this->assertEqualsCanonicalizing([$keptCategoryTransfer->getIdCategoryOrFail() => static::KEPT_PRODUCT_ORDER, $addedCategoryTransfer->getIdCategoryOrFail() => 0], $this->findProductOrdersIndexedByIdCategory($idProductAbstract));
        $this->assertProductCategoryPublishEvent($importStepResponseTransfer, $idProductAbstract);
    }

    public function testExecuteBatchRemovesAllCategoryAssignmentsWhenCategoriesValueIsEmpty(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->haveProductAbstract()->getIdProductAbstractOrFail();
        $this->haveProductCategories($idProductAbstract, [$this->tester->haveCategory()->getIdCategoryOrFail()]);

        // Act
        $importStepResponseTransfer = $this->executeStep([static::CSV_ROW_NUMBER => $this->createAbstractRow($idProductAbstract, '')]);

        // Assert
        $this->assertTrue($importStepResponseTransfer->getIsSuccessful());
        $this->assertSame([], $this->findProductOrdersIndexedByIdCategory($idProductAbstract));
        $this->assertProductCategoryPublishEvent($importStepResponseTransfer, $idProductAbstract);
    }

    public function testExecuteBatchKeepsCategoryAssignmentsWhenCategoriesColumnIsAbsent(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->haveProductAbstract()->getIdProductAbstractOrFail();
        $idCategory = $this->tester->haveCategory()->getIdCategoryOrFail();
        $this->haveProductCategories($idProductAbstract, [$idCategory]);

        // Act
        $importStepResponseTransfer = $this->executeStep([static::CSV_ROW_NUMBER => [static::COLUMN_ABSTRACT_SKU => $this->findAbstractSku($idProductAbstract)]]);

        // Assert
        $this->assertTrue($importStepResponseTransfer->getIsSuccessful());
        $this->assertSame([$idCategory => static::KEPT_PRODUCT_ORDER], $this->findProductOrdersIndexedByIdCategory($idProductAbstract));
        $this->assertCount(0, $importStepResponseTransfer->getPublishEvents());
    }

    public function testExecuteBatchKeepsCategoryAssignmentsAndReportsNoPublishEventWhenCategoriesFromFileAreUnchanged(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->haveProductAbstract()->getIdProductAbstractOrFail();
        $categoryTransfer = $this->tester->haveCategory();
        $this->haveProductCategories($idProductAbstract, [$categoryTransfer->getIdCategoryOrFail()]);

        // Act
        $importStepResponseTransfer = $this->executeStep([static::CSV_ROW_NUMBER => $this->createAbstractRow($idProductAbstract, $categoryTransfer->getCategoryKeyOrFail())]);

        // Assert
        $this->assertTrue($importStepResponseTransfer->getIsSuccessful());
        $this->assertSame([$categoryTransfer->getIdCategoryOrFail() => static::KEPT_PRODUCT_ORDER], $this->findProductOrdersIndexedByIdCategory($idProductAbstract));
        $this->assertCount(0, $importStepResponseTransfer->getPublishEvents());
    }

    public function testExecuteBatchReturnsErrorAndKeepsCategoryAssignmentsWhenOneCategoryFromFileDoesNotExist(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->haveProductAbstract()->getIdProductAbstractOrFail();
        [$idCategory, $notAddedCategoryKey] = [$this->tester->haveCategory()->getIdCategoryOrFail(), $this->tester->haveCategory()->getCategoryKeyOrFail()];
        $this->haveProductCategories($idProductAbstract, [$idCategory]);

        // Act
        $importStepResponseTransfer = $this->executeStep([static::CSV_ROW_NUMBER => $this->createAbstractRow($idProductAbstract, $notAddedCategoryKey . ';' . static::UNKNOWN_CATEGORY_KEY)]);

        // Assert
        $this->assertFalse($importStepResponseTransfer->getIsSuccessful());
        $this->assertSame(static::CSV_ROW_NUMBER, $importStepResponseTransfer->getErrors()->offsetGet(0)->getCsvRowNumber());
        $this->assertSame([$idCategory => static::KEPT_PRODUCT_ORDER], $this->findProductOrdersIndexedByIdCategory($idProductAbstract));
    }

    public function testExecuteBatchAppliesLastRowWhenSameProductAbstractAppearsTwiceInBatch(): void
    {
        // Arrange
        $idProductAbstract = $this->tester->haveProductAbstract()->getIdProductAbstractOrFail();
        [$firstCategoryTransfer, $lastCategoryTransfer] = [$this->tester->haveCategory(), $this->tester->haveCategory()];

        // Act
        $importStepResponseTransfer = $this->executeStep([
            static::CSV_ROW_NUMBER => $this->createAbstractRow($idProductAbstract, $firstCategoryTransfer->getCategoryKeyOrFail() . ';' . $lastCategoryTransfer->getCategoryKeyOrFail()),
            static::CSV_ROW_NUMBER + 1 => $this->createAbstractRow($idProductAbstract, $lastCategoryTransfer->getCategoryKeyOrFail()),
        ]);

        // Assert
        $this->assertTrue($importStepResponseTransfer->getIsSuccessful());
        $this->assertSame([$lastCategoryTransfer->getIdCategoryOrFail() => 0], $this->findProductOrdersIndexedByIdCategory($idProductAbstract));
    }

    public function testExecuteBatchAppliesAbstractRowCategoriesAndIgnoresEmptyCategoriesOfConcreteRowOfSimpleProduct(): void
    {
        // Arrange
        $productConcreteTransfer = $this->tester->haveProduct();
        $idProductAbstract = $productConcreteTransfer->getFkProductAbstractOrFail();
        $categoryTransfer = $this->tester->haveCategory();

        // Act
        $importStepResponseTransfer = $this->executeStep([
            static::CSV_ROW_NUMBER => $this->createAbstractRow($idProductAbstract, $categoryTransfer->getCategoryKeyOrFail()),
            static::CSV_ROW_NUMBER + 1 => [
                static::COLUMN_ABSTRACT_SKU => $productConcreteTransfer->getAbstractSkuOrFail(),
                static::COLUMN_CONCRETE_SKU => $productConcreteTransfer->getSkuOrFail(),
                static::COLUMN_CATEGORIES => '',
            ],
        ]);

        // Assert
        $this->assertTrue($importStepResponseTransfer->getIsSuccessful());
        $this->assertSame([$categoryTransfer->getIdCategoryOrFail() => 0], $this->findProductOrdersIndexedByIdCategory($idProductAbstract));
    }

    protected function createStep(): ProductCsvImportCategoryStep
    {
        /** @var \SprykerFeature\Zed\ProductExperienceManagement\Business\ProductExperienceManagementBusinessFactory $factory */
        $factory = $this->tester->getFactory();

        return $factory->createProductCsvImportCategoryStep();
    }

    /**
     * @param array<int, array<string, string>> $rows
     */
    protected function executeStep(array $rows): ImportStepResponseTransfer
    {
        $importStep = $this->createStep();
        $importStep->resetCache();

        return $importStep->executeBatch($rows);
    }

    /**
     * @return array<string, string>
     */
    protected function createAbstractRow(int $idProductAbstract, string $categories): array
    {
        return [
            static::COLUMN_ABSTRACT_SKU => $this->findAbstractSku($idProductAbstract),
            static::COLUMN_CATEGORIES => $categories,
        ];
    }

    protected function findAbstractSku(int $idProductAbstract): string
    {
        return $this->tester->getLocator()->product()->facade()->findProductAbstractById($idProductAbstract)->getSkuOrFail();
    }

    /**
     * @param array<int> $categoryIds
     */
    protected function haveProductCategories(int $idProductAbstract, array $categoryIds): void
    {
        foreach ($categoryIds as $idCategory) {
            (new SpyProductCategory())
                ->setFkProductAbstract($idProductAbstract)
                ->setFkCategory($idCategory)
                ->setProductOrder(static::KEPT_PRODUCT_ORDER)
                ->save();
        }
    }

    /**
     * @return array<int, int>
     */
    protected function findProductOrdersIndexedByIdCategory(int $idProductAbstract): array
    {
        $productOrdersIndexedByIdCategory = [];

        foreach (SpyProductCategoryQuery::create()->filterByFkProductAbstract($idProductAbstract)->find() as $productCategoryEntity) {
            $productOrdersIndexedByIdCategory[$productCategoryEntity->getFkCategory()] = (int)$productCategoryEntity->getProductOrder();
        }

        return $productOrdersIndexedByIdCategory;
    }

    protected function assertProductCategoryPublishEvent(ImportStepResponseTransfer $importStepResponseTransfer, int $idProductAbstract): void
    {
        $productCategoryPublishEventEntityIds = [];

        foreach ($importStepResponseTransfer->getPublishEvents() as $importPublishEventTransfer) {
            if ($importPublishEventTransfer->getEventName() === ProductCategoryEvents::PRODUCT_CATEGORY_PUBLISH) {
                $productCategoryPublishEventEntityIds[] = $importPublishEventTransfer->getEntityId();
            }
        }

        $this->assertSame([$idProductAbstract], $productCategoryPublishEventEntityIds);
    }
}
