<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin;

use Generated\Shared\Transfer\ImportJobRunTransfer;

/**
 * Executed after import processing finishes (e.g. fire publish events, re-enable event behaviors).
 */
interface ImportPostProcessorPluginInterface
{
    public function postProcess(ImportJobRunTransfer $importJobRunTransfer): void;
}
