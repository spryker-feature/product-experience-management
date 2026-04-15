<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\Export\Writer;

use Generated\Shared\Transfer\ImportJobRunFileInfoTransfer;

interface ExportFileWriterInterface
{
    /**
     * Opens a temporary local file and writes the CSV header row.
     *
     * @param array<string> $columns
     */
    public function openFile(array $columns): void;

    /**
     * Writes a batch of rows to the open CSV file.
     *
     * @param array<int, array<string, string>> $rows
     * @param array<string> $columns
     */
    public function writeBatch(array $rows, array $columns): void;

    /**
     * Closes the temp file, pushes it to the configured filesystem, and returns file metadata.
     */
    public function closeAndStore(string $jobType): ImportJobRunFileInfoTransfer;
}
