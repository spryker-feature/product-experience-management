<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Form\DataProvider;

use SprykerFeature\Zed\ProductExperienceManagement\Communication\Form\ImportJobRunForm;
use SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementConfig;

class ImportJobRunFormDataProvider
{
    public function __construct(
        protected ProductExperienceManagementConfig $config,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return [
            ImportJobRunForm::OPTION_MAX_FILE_SIZE => $this->config->getImportMaxFileSizeString(),
            ImportJobRunForm::OPTION_ALLOWED_MIME_TYPES => array_keys($this->config->getAllowedMimeTypesWithExtensions()),
        ];
    }
}
