<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\Export\Manager;

use Generated\Shared\Transfer\ImportJobCriteriaTransfer;
use Generated\Shared\Transfer\ImportJobExportResultTransfer;
use Generated\Shared\Transfer\ImportJobRunFileInfoTransfer;
use Generated\Shared\Transfer\ImportJobTransfer;
use RuntimeException;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ExportDataProviderInterface;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ExportSchemaPluginInterface;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Export\ColumnResolver\ExportColumnResolverInterface;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Export\PlaceholderProvider\ExportPlaceholderProviderInterface;
use SprykerFeature\Zed\ProductExperienceManagement\Business\Export\Writer\ExportFileWriterInterface;
use SprykerFeature\Zed\ProductExperienceManagement\Persistence\ProductExperienceManagementRepositoryInterface;
use SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig;

class ExportManager implements ExportManagerInterface
{
    /**
     * @param array<\SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportSchemaPluginInterface> $schemaPlugins
     */
    public function __construct(
        protected ProductExperienceManagementRepositoryInterface $repository,
        protected ExportColumnResolverInterface $exportColumnResolver,
        protected ExportPlaceholderProviderInterface $exportPlaceholderProvider,
        protected array $schemaPlugins,
        protected ProductExperienceManagementConfig $config,
        protected ExportFileWriterInterface $exportFileWriter,
    ) {
    }

    public function exportData(ImportJobCriteriaTransfer $criteriaTransfer): ImportJobExportResultTransfer
    {
        $importJob = $this->resolveImportJob($criteriaTransfer);
        $columnHeaders = $this->resolveColumnHeaders($importJob);

        $exportResult = (new ImportJobExportResultTransfer())
            ->setType($importJob->getTypeOrFail())
            ->setColumns($columnHeaders);

        if ($criteriaTransfer->getIsWithData() === true) {
            $schemaPlugin = $this->resolveExportSchemaPlugin($importJob);

            $fileInfo = $this->executeBatchExport(
                $schemaPlugin,
                $columnHeaders,
                $importJob->getTypeOrFail(),
            );

            $exportResult->setFileInfo($fileInfo);
        }

        return $exportResult;
    }

    /**
     * @return array<string>
     */
    protected function resolveColumnHeaders(ImportJobTransfer $importJobTransfer): array
    {
        return $this->exportColumnResolver->resolveColumnHeaders(
            $importJobTransfer,
            $this->exportPlaceholderProvider->getPlaceholderValues(),
        );
    }

    protected function resolveImportJob(ImportJobCriteriaTransfer $criteriaTransfer): ImportJobTransfer
    {
        $importJobCollection = $this->repository->getImportJobCollection($criteriaTransfer);

        return $importJobCollection->getImportJobs()->getIterator()->current();
    }

    protected function resolveExportSchemaPlugin(ImportJobTransfer $importJob): ExportSchemaPluginInterface
    {
        $type = $importJob->getTypeOrFail();

        foreach ($this->schemaPlugins as $schemaPlugin) {
            if ($schemaPlugin instanceof ExportSchemaPluginInterface && $schemaPlugin->getType() === $type) {
                return $schemaPlugin;
            }
        }

        throw new RuntimeException(sprintf('No export schema plugin found for job type "%s".', $type));
    }

    /**
     * @param array<string> $columns
     */
    protected function executeBatchExport(
        ExportSchemaPluginInterface $schemaPlugin,
        array $columns,
        string $jobType,
    ): ImportJobRunFileInfoTransfer {
        $this->exportFileWriter->openFile($columns);

        $dataProvider = $schemaPlugin->getExportDataProvider();
        $exportSteps = $schemaPlugin->getExportSteps();
        $batchSize = $this->config->getExportCsvBatchSize();
        $lastId = 0;

        while (true) {
            $seedRows = $dataProvider->getBatch($columns, $batchSize, $lastId);

            if ($seedRows === []) {
                break;
            }

            $lastId = (int)$seedRows[array_key_last($seedRows)][ExportDataProviderInterface::INTERNAL_COLUMN_CURSOR_ID];

            foreach ($exportSteps as $step) {
                $seedRows = $step->exportRows($seedRows, $columns);
            }

            $this->exportFileWriter->writeBatch($seedRows, $columns);
        }

        return $this->exportFileWriter->closeAndStore($jobType);
    }
}
