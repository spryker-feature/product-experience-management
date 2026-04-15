<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin;

interface ExportSchemaPluginInterface
{
    /**
     * Specification:
     * - Returns the export steps that read product data from the database.
     * - Each step handles a specific data domain (prices, stock, images, etc.).
     *
     * @api
     *
     * @return array<\SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ExportStepInterface>
     */
    public function getExportSteps(): array;

    /**
     * Specification:
     * - Returns a data provider that supplies batches of seed rows for export.
     * - Seed rows are pre-seeded with key identifiers (e.g. Abstract SKU).
     *
     * @api
     */
    public function getExportDataProvider(): ExportDataProviderInterface;
}
