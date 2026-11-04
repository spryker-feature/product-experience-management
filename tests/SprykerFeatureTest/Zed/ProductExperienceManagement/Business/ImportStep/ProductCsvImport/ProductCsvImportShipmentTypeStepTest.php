<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ShipmentTypeTransfer;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\AbstractProductCsvImportStep;
use SprykerFeatureTest\Zed\ProductExperienceManagement\ProductExperienceManagementBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group ProductExperienceManagement
 * @group Business
 * @group ImportStep
 * @group ProductCsvImport
 * @group ProductCsvImportShipmentTypeStepTest
 */
class ProductCsvImportShipmentTypeStepTest extends Unit
{
    protected const string COLUMN_ABSTRACT_SKU = 'abstract_sku';

    protected const string COLUMN_CONCRETE_SKU = 'concrete_sku';

    protected const string COLUMN_SHIPMENT_TYPES = 'shipment_types';

    protected const int CSV_ROW_NUMBER = 3;

    protected ProductExperienceManagementBusinessTester $tester;

    protected function _after(): void
    {
        $this->createStep()->resetCache();

        parent::_after();
    }

    /**
     * The step loads every shipment type once and remembers that in a warmed-up flag. Resetting the cache array
     * without that flag left the step with an empty cache it believed to be complete, so every shipment type was
     * reported as missing from the second batch on.
     */
    public function testStillResolvesShipmentTypesAfterTheCacheWasReset(): void
    {
        // Arrange
        $shipmentTypeTransfer = $this->tester->haveShipmentType([ShipmentTypeTransfer::IS_ACTIVE => true]);
        $productConcreteTransfer = $this->tester->haveProduct();
        $row = [
            static::COLUMN_ABSTRACT_SKU => $productConcreteTransfer->getAbstractSkuOrFail(),
            static::COLUMN_CONCRETE_SKU => $productConcreteTransfer->getSkuOrFail(),
            static::COLUMN_SHIPMENT_TYPES => $shipmentTypeTransfer->getKeyOrFail(),
        ];
        $step = $this->createStep();
        $warmedUpResponse = $step->executeBatch([static::CSV_ROW_NUMBER => $row]);

        // Act
        $step->resetCache();
        $response = $step->executeBatch([static::CSV_ROW_NUMBER + 1 => $row]);

        // Assert
        $this->assertTrue($warmedUpResponse->getIsSuccessful());
        $this->assertTrue($response->getIsSuccessful(), 'After a cache reset the step must load the shipment types again');
        $this->assertCount(0, $response->getErrors());
    }

    protected function createStep(): AbstractProductCsvImportStep
    {
        /** @var \SprykerFeature\Zed\ProductExperienceManagement\Business\ProductExperienceManagementBusinessFactory $factory */
        $factory = $this->tester->getFactory();
        /** @var \SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\AbstractProductCsvImportStep $step */
        $step = $factory->createProductCsvImportShipmentTypeStep();

        return $step;
    }
}
