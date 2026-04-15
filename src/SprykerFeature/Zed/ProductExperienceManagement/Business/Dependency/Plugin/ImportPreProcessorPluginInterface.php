<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin;

use Generated\Shared\Transfer\ImportJobRunTransfer;

/**
 * Executed before import processing starts (e.g. disable event behaviors).
 */
interface ImportPreProcessorPluginInterface
{
    public function preProcess(ImportJobRunTransfer $importJobRunTransfer): void;
}
