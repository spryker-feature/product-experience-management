<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\Export\Manager;

use Generated\Shared\Transfer\ImportJobCriteriaTransfer;
use Generated\Shared\Transfer\ImportJobExportResultTransfer;

interface ExportManagerInterface
{
    /**
     * Resolves the job and expands columns from schema definition.
     * When `ImportJobCriteriaTransfer.isWithData` is true, runs export steps and populates rows.
     * Otherwise returns columns only (template mode).
     */
    public function exportData(ImportJobCriteriaTransfer $criteriaTransfer): ImportJobExportResultTransfer;
}
