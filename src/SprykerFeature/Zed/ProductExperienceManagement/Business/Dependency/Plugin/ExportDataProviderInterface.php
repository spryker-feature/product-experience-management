<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin;

interface ExportDataProviderInterface
{
    /**
     * Internal column key that every implementation MUST include in returned rows.
     * Used as a cursor by ExportManager to fetch the next batch without OFFSET.
     */
    public const string INTERNAL_COLUMN_CURSOR_ID = '_cursor_id';

    /**
     * Specification:
     * - Returns a batch of seed rows pre-seeded with key identifiers.
     * - Each row MUST include the INTERNAL_COLUMN_CURSOR_ID key set to the row's primary-key integer value.
     * - Returns empty array when no more data is available.
     *
     * @api
     *
     * @param array<string> $columns
     *
     * @return array<int, array<string, string>>
     */
    public function getBatch(array $columns, int $limit, int $lastId = 0): array;
}
