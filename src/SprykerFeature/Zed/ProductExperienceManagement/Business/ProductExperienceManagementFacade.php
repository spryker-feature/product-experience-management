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
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Business\ProductExperienceManagementBusinessFactory getFactory()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Persistence\ProductExperienceManagementEntityManagerInterface getEntityManager()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Persistence\ProductExperienceManagementRepositoryInterface getRepository()
 */
class ProductExperienceManagementFacade extends AbstractFacade implements ProductExperienceManagementFacadeInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getImportJobCollection(ImportJobCriteriaTransfer $criteriaTransfer): ImportJobCollectionTransfer
    {
        return $this->getRepository()->getImportJobCollection($criteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createImportJobCollection(ImportJobCollectionRequestTransfer $collectionRequestTransfer): ImportJobCollectionResponseTransfer
    {
        return $this->getFactory()->createImportJobWriter()->createImportJobCollection($collectionRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createImportJobRunCollection(ImportJobRunCollectionRequestTransfer $collectionRequestTransfer): ImportJobRunCollectionResponseTransfer
    {
        return $this->getFactory()->createImportJobRunWriter()->createImportJobRunCollection($collectionRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getImportJobRunCollection(ImportJobRunCriteriaTransfer $criteriaTransfer): ImportJobRunCollectionTransfer
    {
        return $this->getRepository()->getImportJobRunCollection($criteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getImportJobRunErrorCollection(ImportJobRunErrorCriteriaTransfer $criteriaTransfer): ImportJobRunErrorCollectionTransfer
    {
        return $this->getRepository()->getImportJobRunErrorCollection($criteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function exportData(ImportJobCriteriaTransfer $criteriaTransfer): ImportJobExportResultTransfer
    {
        return $this->getFactory()->createExportManager()->exportData($criteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function processNextPendingRun(): void
    {
        $this->getFactory()->createImportJobRunManager()->processNextPendingRun();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<string>
     */
    public function getAvailableVisibilityTypes(): array
    {
        return $this->getFactory()
            ->getConfig()
            ->getAvailableVisibilityTypes();
    }
}
