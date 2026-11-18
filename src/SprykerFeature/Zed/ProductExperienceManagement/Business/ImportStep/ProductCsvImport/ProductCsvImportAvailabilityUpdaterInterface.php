<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\ImportStep\ProductCsvImport;

use Generated\Shared\Transfer\ImportStepResponseTransfer;

interface ProductCsvImportAvailabilityUpdaterInterface
{
    /**
     * @param array<string, array{idProduct: int, abstractSku: string, isNeverOutOfStock: bool}> $processedConcreteSkus concreteSku => data
     */
    public function updateAvailabilityForConcreteSkus(array $processedConcreteSkus, ImportStepResponseTransfer $response): void;
}
