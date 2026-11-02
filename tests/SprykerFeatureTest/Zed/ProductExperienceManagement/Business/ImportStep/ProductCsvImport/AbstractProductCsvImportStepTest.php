<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ImportStepResponseTransfer;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport\AbstractProductCsvImportStep;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group ProductExperienceManagement
 * @group Business
 * @group ImportStep
 * @group ProductCsvImport
 * @group AbstractProductCsvImportStepTest
 * Add your own group annotations below this line
 */
class AbstractProductCsvImportStepTest extends Unit
{
    public function testHelpersBuildRowErrorAndPublishEventTransfers(): void
    {
        // Arrange
        $step = new class extends AbstractProductCsvImportStep {
            /**
             * {@inheritDoc}
             */
            public function executeBatch(array $rows, array $propertyNamesInFile = []): ImportStepResponseTransfer
            {
                return (new ImportStepResponseTransfer())
                    ->setIsSuccessful(false)
                    ->addError($this->createImportStepError(7, 'Bad row'))
                    ->addPublishEvent($this->createImportPublishEvent('Entity.publish', 42));
            }
        };

        // Act
        $response = $step->executeBatch([]);

        // Assert
        $this->assertSame(7, $response->getErrors()->offsetGet(0)->getCsvRowNumber());
        $this->assertSame('Bad row', $response->getErrors()->offsetGet(0)->getErrorMessage());
        $this->assertSame('Entity.publish', $response->getPublishEvents()->offsetGet(0)->getEventName());
        $this->assertSame(42, $response->getPublishEvents()->offsetGet(0)->getEntityId());
    }
}
