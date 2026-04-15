<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\Export\Writer;

use DateTime;
use DateTimeZone;
use Generated\Shared\Transfer\FileSystemStreamTransfer;
use Generated\Shared\Transfer\ImportJobRunFileInfoTransfer;
use Spryker\Service\FileSystem\FileSystemServiceInterface;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Export\Writer\Exception\ExportFileException;
use SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig;

class ExportFileWriter implements ExportFileWriterInterface
{
    protected const string CONTENT_TYPE_CSV = 'text/csv';

    protected const string STORED_PATH_PATTERN = '{type}/export_{timestamp}.csv';

    protected const string ORIGINAL_FILE_NAME_PATTERN = '{type}_export_{timestamp}.csv';

    /**
     * @var resource|null
     */
    protected $tempFileHandle;

    protected string $tempFilePath = '';

    public function __construct(
        protected FileSystemServiceInterface $fileSystemService,
        protected ProductExperienceManagementConfig $config,
    ) {
    }

    /**
     * @param array<string> $columns
     *
     * @throws \SprykerFeature\Zed\ProductExperienceManagement\Business\Export\Writer\Exception\ExportFileException
     */
    public function openFile(array $columns): void
    {
        $this->tempFilePath = sprintf('%s/pem_export_%s.csv', sys_get_temp_dir(), date('YmdHis'));
        $handle = fopen($this->tempFilePath, 'wb');

        if ($handle === false) {
            throw new ExportFileException(sprintf('Failed to open temp file for export: %s', $this->tempFilePath));
        }

        $this->tempFileHandle = $handle;

        fputcsv($this->tempFileHandle, $columns);
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @param array<string> $columns
     *
     * @throws \SprykerFeature\Zed\ProductExperienceManagement\Business\Export\Writer\Exception\ExportFileException
     */
    public function writeBatch(array $rows, array $columns): void
    {
        if ($this->tempFileHandle === null) {
            throw new ExportFileException('Cannot write batch: export file has not been opened. Call openFile() first.');
        }

        foreach ($rows as $row) {
            $csvRow = [];

            foreach ($columns as $column) {
                $csvRow[] = $row[$column] ?? '';
            }

            fputcsv($this->tempFileHandle, $csvRow);
        }
    }

    /**
     * @throws \SprykerFeature\Zed\ProductExperienceManagement\Business\Export\Writer\Exception\ExportFileException
     */
    public function closeAndStore(string $jobType): ImportJobRunFileInfoTransfer
    {
        if ($this->tempFileHandle === null) {
            throw new ExportFileException('Cannot close and store: export file has not been opened. Call openFile() first.');
        }

        fclose($this->tempFileHandle);
        $this->tempFileHandle = null;

        $timestamp = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d_H-i-s-v');
        $storedPath = strtr(static::STORED_PATH_PATTERN, [
            '{type}' => $jobType,
            '{timestamp}' => $timestamp,
        ]);
        $exportFileName = strtr(static::ORIGINAL_FILE_NAME_PATTERN, [
            '{type}' => $jobType,
            '{timestamp}' => $timestamp,
        ]);

        $fileSize = (int)filesize($this->tempFilePath);
        $stream = fopen($this->tempFilePath, 'rb');

        if ($stream === false) {
            throw new ExportFileException(sprintf('Failed to open temp file for reading: %s', $this->tempFilePath));
        }

        $fileSystemStreamTransfer = (new FileSystemStreamTransfer())
            ->setFileSystemName($this->config->getExportFileSystemName())
            ->setPath($storedPath);

        $this->fileSystemService->writeStream($fileSystemStreamTransfer, $stream);

        fclose($stream);
        unlink($this->tempFilePath);

        return (new ImportJobRunFileInfoTransfer())
            ->setFileSystemName($this->config->getExportFileSystemName())
            ->setStoredPath($storedPath)
            ->setOriginalFileName($exportFileName)
            ->setContentType(static::CONTENT_TYPE_CSV)
            ->setSize($fileSize);
    }
}
