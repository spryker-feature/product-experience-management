<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin;

use Generated\Shared\Transfer\ImportStepResponseTransfer;

/**
 * Provides a single step in the CSV import pipeline for processing a batch of rows.
 *
 * Use this plugin to implement domain-specific validation, transformation, and persistence
 * for a specific aspect of a product import (e.g. prices, stock, images, URLs).
 * Each step receives the full batch of rows and returns validation errors and publish events.
 *
 * Do not use this plugin for cross-step state management or for operations that require
 * access to data from previous steps — steps must be stateless and idempotent.
 */
interface ImportStepInterface
{
    /**
     * Specification:
     * - Processes a batch of CSV rows for one import step.
     * - Returns a response containing validation errors and publish events.
     * - Receives an optional reverse map (system name → file header) for error messages.
     *
     * @api
     *
     * @param array<int, array<string, string>> $rows Batch of CSV rows indexed by row number
     * @param array<string, string> $propertyNamesInFile System property name → original CSV column name
     */
    public function executeBatch(array $rows, array $propertyNamesInFile = []): ImportStepResponseTransfer;
}
