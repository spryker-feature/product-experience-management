<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin;

interface ImportSchemaPluginInterface
{
    /**
     * Specification:
     * - Returns the job type this schema handles (e.g. 'simple-product').
     *
     * @api
     */
    public function getType(): string;

    /**
     * Specification:
     * - Returns the import steps that process CSV rows for this schema's job type.
     * - Steps are resolved from the business factory.
     *
     * @api
     *
     * @return array<\SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportStepInterface>
     */
    public function getImportSteps(): array;

    /**
     * Specification:
     * - Returns the column definitions for the CSV schema.
     * - Each entry maps a CSV column name to a normalized system property name.
     *
     * @api
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSchema(): array;
}
