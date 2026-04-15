<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin;

interface ExportStepInterface
{
    /**
     * Specification:
     * - Receives a flat list of export rows and the resolved column headers.
     * - Populates rows with data from a specific domain (e.g., prices, stock, images).
     * - The first step in the chain is responsible for initializing the row structure.
     * - Subsequent steps fill in columns relevant to their domain.
     *
     * @api
     *
     * @param array<int, array<string, string>> $rows
     * @param array<string> $columns
     *
     * @return array<int, array<string, string>>
     */
    public function exportRows(array $rows, array $columns): array;
}
