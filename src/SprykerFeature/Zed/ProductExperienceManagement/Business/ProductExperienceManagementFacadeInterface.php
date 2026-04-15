<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business;

use Generated\Shared\Transfer\ImportJobCollectionRequestTransfer;
use Generated\Shared\Transfer\ImportJobCollectionResponseTransfer;
use Generated\Shared\Transfer\ImportJobCollectionTransfer;
use Generated\Shared\Transfer\ImportJobCriteriaTransfer;
use Generated\Shared\Transfer\ImportJobExportResultTransfer;
use Generated\Shared\Transfer\ImportJobRunCollectionRequestTransfer;
use Generated\Shared\Transfer\ImportJobRunCollectionResponseTransfer;
use Generated\Shared\Transfer\ImportJobRunCollectionTransfer;
use Generated\Shared\Transfer\ImportJobRunCriteriaTransfer;
use Generated\Shared\Transfer\ImportJobRunErrorCollectionTransfer;
use Generated\Shared\Transfer\ImportJobRunErrorCriteriaTransfer;

interface ProductExperienceManagementFacadeInterface
{
    /**
     * Specification:
     * - Retrieves a collection of import jobs filtered by the provided criteria.
     * - Returns `ImportJobCollectionTransfer` with matching import jobs and pagination.
     *
     * @api
     */
    public function getImportJobCollection(ImportJobCriteriaTransfer $criteriaTransfer): ImportJobCollectionTransfer;

    /**
     * Specification:
     * - Creates import job records from the provided collection request.
     * - Generates a unique reference from the job name if not provided.
     * - Populates the job definition from the matching schema plugin `\SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportSchemaPluginInterface` if not provided.
     * - Persists each import job to the database.
     * - Returns `ImportJobCollectionResponseTransfer` with created jobs or errors.
     *
     * @api
     */
    public function createImportJobCollection(ImportJobCollectionRequestTransfer $collectionRequestTransfer): ImportJobCollectionResponseTransfer;

    /**
     * Specification:
     * - Creates import job run records from the provided collection request.
     * - Sets pending status and initializes counters for each run.
     * - Persists each import job run to the database.
     * - Returns `ImportJobRunCollectionResponseTransfer` with created runs or errors.
     *
     * @api
     */
    public function createImportJobRunCollection(ImportJobRunCollectionRequestTransfer $collectionRequestTransfer): ImportJobRunCollectionResponseTransfer;

    /**
     * Specification:
     * - Retrieves a collection of import job runs filtered by the provided criteria.
     * - Returns `ImportJobRunCollectionTransfer` with matching runs and pagination.
     *
     * @api
     */
    public function getImportJobRunCollection(ImportJobRunCriteriaTransfer $criteriaTransfer): ImportJobRunCollectionTransfer;

    /**
     * Specification:
     * - Retrieves a collection of import job run errors filtered by the provided criteria.
     * - Orders errors by CSV row number ascending.
     * - Returns `ImportJobRunErrorCollectionTransfer` with matching errors and pagination.
     *
     * @api
     */
    public function getImportJobRunErrorCollection(ImportJobRunErrorCriteriaTransfer $criteriaTransfer): ImportJobRunErrorCollectionTransfer;

     /**
      * Specification:
      * - Resolves the import job by the provided criteria.
      * - Expands pattern-based schema definitions into concrete CSV column names.
      * - Resolves placeholders ({locale}, {store}, etc.) using actual system configuration from stores, locales.
      * - When `ImportJobCriteriaTransfer.isWithData` is true, runs export steps and populates rows with product data.
      * - When `ImportJobCriteriaTransfer.isWithData` is false or not set, returns empty rows (template only mode).
      *
      * @api
      */
    public function exportData(ImportJobCriteriaTransfer $criteriaTransfer): ImportJobExportResultTransfer;

    /**
     * Specification:
     * - Finds the oldest pending import job run.
     * - Does nothing if no pending run exists.
     * - Marks the run as processing and records the start timestamp.
     * - Executes pre-processor plugins `\SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportPreProcessorPluginInterface` before processing.
     * - Reads the CSV file in batches and runs each batch through applicable import steps.
     * - Records per-row errors for failed CSV rows.
     * - Executes post-processor plugins `\SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportPostProcessorPluginInterface` after processing.
     * - Marks the run as done or failed with final counters and finish timestamp.
     *
     * @api
     */
    public function processNextPendingRun(): void;

    /**
     * Specification:
     * - Returns available visibility types for product attributes (e.g. PDP, PLP, Cart).
     *
     * @api
     *
     * @return array<string>
     */
    public function getAvailableVisibilityTypes(): array;
}
