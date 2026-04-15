<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Persistence;

use Generated\Shared\Transfer\ImportJobRunErrorTransfer;
use Generated\Shared\Transfer\ImportJobRunTransfer;
use Generated\Shared\Transfer\ImportJobTransfer;
use Generated\Shared\Transfer\ProductAttributeStorageTransfer;
use Orm\Zed\ProductExperienceManagement\Persistence\SpyImportJob;
use Orm\Zed\ProductExperienceManagement\Persistence\SpyImportJobRun;
use Orm\Zed\ProductExperienceManagement\Persistence\SpyImportJobRunError;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Persistence\ProductExperienceManagementPersistenceFactory getFactory()
 */
class ProductExperienceManagementEntityManager extends AbstractEntityManager implements ProductExperienceManagementEntityManagerInterface
{
    public function createImportJob(ImportJobTransfer $importJobTransfer): ImportJobTransfer
    {
        $importJobEntity = $this->getFactory()->createMapper()->mapImportJobTransferToEntity($importJobTransfer, new SpyImportJob());
        $importJobEntity->save();

        return $importJobTransfer->setIdImportJob($importJobEntity->getIdImportJob());
    }

    public function updateImportJob(ImportJobTransfer $importJobTransfer): ImportJobTransfer
    {
        $importJobEntity = $this->getFactory()
            ->createImportJobQuery()
            ->findPk($importJobTransfer->getIdImportJobOrFail());

        $importJobEntity = $this->getFactory()->createMapper()->mapImportJobTransferToEntity($importJobTransfer, $importJobEntity);
        $importJobEntity->save();

        return $importJobTransfer;
    }

    public function createImportJobRun(ImportJobRunTransfer $importJobRunTransfer): ImportJobRunTransfer
    {
        $importJobRunEntity = $this->getFactory()->createMapper()->mapImportJobRunTransferToEntity($importJobRunTransfer, new SpyImportJobRun());
        $importJobRunEntity->save();

        return $importJobRunTransfer->setIdImportJobRun($importJobRunEntity->getIdImportJobRun());
    }

    public function updateImportJobRun(ImportJobRunTransfer $importJobRunTransfer): ImportJobRunTransfer
    {
        $importJobRunEntity = $this->getFactory()
            ->createImportJobRunQuery()
            ->findPk($importJobRunTransfer->getIdImportJobRunOrFail());

        $importJobRunEntity = $this->getFactory()->createMapper()->mapImportJobRunTransferToEntity($importJobRunTransfer, $importJobRunEntity);
        $importJobRunEntity->save();

        return $importJobRunTransfer;
    }

    public function createImportJobRunError(ImportJobRunErrorTransfer $importJobRunErrorTransfer): ImportJobRunErrorTransfer
    {
        $importJobRunErrorEntity = (new SpyImportJobRunError())
            ->setFkImportJobRun($importJobRunErrorTransfer->getFkImportJobRunOrFail())
            ->setCsvRowNumber($importJobRunErrorTransfer->getCsvRowNumber())
            ->setErrorMessage($importJobRunErrorTransfer->getErrorMessageOrFail());

        $importJobRunErrorEntity->save();

        return $importJobRunErrorTransfer->setIdImportJobRunError($importJobRunErrorEntity->getIdImportJobRunError());
    }

    public function saveProductAttributeStorage(
        int $idProductManagementAttribute,
        string $attributeKey,
        ProductAttributeStorageTransfer $productAttributeStorageTransfer,
    ): void {
        $productAttributeStorageEntity = $this->getFactory()
            ->createProductAttributeStorageQuery()
            ->filterByFkProductManagementAttribute($idProductManagementAttribute)
            ->findOneOrCreate();

        $productAttributeStorageEntity
            ->setAttributeKey($attributeKey)
            ->setData($productAttributeStorageTransfer->toArray())
            ->save();
    }
}
