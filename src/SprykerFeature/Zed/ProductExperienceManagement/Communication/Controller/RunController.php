<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\ProductExperienceManagement\Communication\Controller;

use Generated\Shared\Transfer\ImportJobConditionsTransfer;
use Generated\Shared\Transfer\ImportJobCriteriaTransfer;
use Generated\Shared\Transfer\ImportJobRunCollectionRequestTransfer;
use Generated\Shared\Transfer\ImportJobRunConditionsTransfer;
use Generated\Shared\Transfer\ImportJobRunCriteriaTransfer;
use Generated\Shared\Transfer\ImportJobRunErrorConditionsTransfer;
use Generated\Shared\Transfer\ImportJobRunErrorCriteriaTransfer;
use Generated\Shared\Transfer\ImportJobRunFileInfoTransfer;
use Generated\Shared\Transfer\ImportJobRunTransfer;
use Generated\Shared\Transfer\ImportJobTransfer;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Form\ImportJobRunForm;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Communication\ProductExperienceManagementCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\ProductExperienceManagement\Business\ProductExperienceManagementFacade getFacade()
 */
class RunController extends AbstractController
{
    protected const string ROUTE_RUN_INDEX = '/product-experience-management/run/index';

    protected const string ROUTE_RUN_DOWNLOAD_ERRORS = '/product-experience-management/run/download-errors';

    protected const string ROUTE_TEMPLATE_DOWNLOAD = '/product-experience-management/template/download';

    protected const string PARAM_ID_IMPORT_JOB = 'idImportJob';

    protected const string PARAM_IMPORT_JOB_REFERENCE = 'importJobReference';

    protected const string PARAM_ID_IMPORT_JOB_RUN = 'idImportJobRun';

    /**
     * @return array<string, mixed>
     */
    public function indexAction(Request $request): array
    {
        $idImportJob = $this->castId($request->query->get(static::PARAM_ID_IMPORT_JOB));
        $table = $this->getFactory()->createImportJobRunTable($idImportJob);
        $importJob = $this->resolveImportJobById($idImportJob);

        return $this->viewResponse([
            'table' => $table->render(),
            'idImportJob' => $idImportJob,
            'importJobReference' => $importJob->getReference() ?? '',
        ]);
    }

    public function tableAction(Request $request): JsonResponse
    {
        $idImportJob = $this->castId($request->query->get(static::PARAM_ID_IMPORT_JOB));
        $table = $this->getFactory()->createImportJobRunTable($idImportJob);

        return $this->jsonResponse($table->fetchData());
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response|array<string, mixed>
     */
    public function createAction(Request $request): array|Response
    {
        $importJobReference = (string)$request->query->get(static::PARAM_IMPORT_JOB_REFERENCE);
        $importJob = $this->resolveImportJobByReference($importJobReference);

        $form = $this->getFactory()->createImportJobRunForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get(ImportJobRunForm::FIELD_FILE)->getData();

            $fileInfo = $this->buildFileInfoFromUpload($uploadedFile);

            $importJobRunTransfer = (new ImportJobRunTransfer())
                ->setImportJobReference($importJobReference)
                ->setFileInfo($fileInfo);

            $collectionRequest = (new ImportJobRunCollectionRequestTransfer())
                ->addImportJobRun($importJobRunTransfer)
                ->setIsTransactional(true);

            $collectionResponse = $this->getFacade()->createImportJobRunCollection($collectionRequest);

            if ($collectionResponse->getErrors()->count() > 0) {
                foreach ($collectionResponse->getErrors() as $errorTransfer) {
                    $this->addErrorMessage($errorTransfer->getMessageOrFail());
                }

                return $this->viewResponse([
                    'form' => $form->createView(),
                    'idImportJob' => $importJob->getIdImportJob(),
                    'templateDownloadUrl' => sprintf('%s?%s=%s', static::ROUTE_TEMPLATE_DOWNLOAD, static::PARAM_IMPORT_JOB_REFERENCE, urlencode($importJobReference)),
                ]);
            }

            $createdRun = $collectionResponse->getImportJobRuns()->getIterator()->current();

            $this->addSuccessMessage('Import run created successfully. Processing is queued.');

            return $this->redirectResponse(sprintf('%s?%s=%d', static::ROUTE_RUN_INDEX, static::PARAM_ID_IMPORT_JOB, $createdRun->getFkImportJobOrFail()));
        }

        return $this->viewResponse([
            'form' => $form->createView(),
            'idImportJob' => $importJob->getIdImportJob(),
            'templateDownloadUrl' => sprintf('%s?%s=%s', static::ROUTE_TEMPLATE_DOWNLOAD, static::PARAM_IMPORT_JOB_REFERENCE, urlencode($importJobReference)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function detailAction(Request $request): array
    {
        $idImportJobRun = $this->castId($request->query->get(static::PARAM_ID_IMPORT_JOB_RUN));

        $conditions = (new ImportJobRunConditionsTransfer())->addIdImportJobRun($idImportJobRun);
        $criteria = (new ImportJobRunCriteriaTransfer())->setImportJobRunConditions($conditions);

        $jobRunCollection = $this->getFacade()->getImportJobRunCollection($criteria);
        $jobRun = $jobRunCollection->getImportJobRuns()->offsetGet(0);

        $errorConditions = (new ImportJobRunErrorConditionsTransfer())->addIdImportJobRun($idImportJobRun);
        $errorCriteria = (new ImportJobRunErrorCriteriaTransfer())->setImportJobRunErrorConditions($errorConditions);
        $errorCollection = $this->getFacade()->getImportJobRunErrorCollection($errorCriteria);

        $errorCount = $errorCollection->getImportJobRunErrors()->count();
        $errorThreshold = $this->getFactory()->getConfig()->getImportErrorDisplayThreshold();
        $showErrorsInline = $errorCount <= $errorThreshold;

        return $this->viewResponse([
            'jobRun' => $jobRun,
            'errorCount' => $errorCount,
            'showErrorsInline' => $showErrorsInline,
            'inlineErrors' => $showErrorsInline ? $errorCollection->getImportJobRunErrors() : null,
            'errorsDownloadUrl' => sprintf('%s?%s=%d', static::ROUTE_RUN_DOWNLOAD_ERRORS, static::PARAM_ID_IMPORT_JOB_RUN, $idImportJobRun),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function errorsAction(Request $request): array
    {
        $idImportJobRun = $this->castId($request->query->get(static::PARAM_ID_IMPORT_JOB_RUN));

        $errorConditions = (new ImportJobRunErrorConditionsTransfer())->addIdImportJobRun($idImportJobRun);
        $errorCriteria = (new ImportJobRunErrorCriteriaTransfer())->setImportJobRunErrorConditions($errorConditions);

        $errorCollection = $this->getFacade()->getImportJobRunErrorCollection($errorCriteria);

        return $this->viewResponse([
            'errors' => $errorCollection->getImportJobRunErrors(),
            'idImportJobRun' => $idImportJobRun,
        ]);
    }

    public function downloadErrorsAction(Request $request): Response
    {
        $idImportJobRun = $this->castId($request->query->get(static::PARAM_ID_IMPORT_JOB_RUN));

        $errorConditions = (new ImportJobRunErrorConditionsTransfer())->addIdImportJobRun($idImportJobRun);
        $errorCriteria = (new ImportJobRunErrorCriteriaTransfer())->setImportJobRunErrorConditions($errorConditions);
        $errorCollection = $this->getFacade()->getImportJobRunErrorCollection($errorCriteria);

        $response = new StreamedResponse(static function () use ($errorCollection): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Row Number', 'Error Message']);

            foreach ($errorCollection->getImportJobRunErrors() as $error) {
                fputcsv($handle, [
                    $error->getCsvRowNumber() ?? '',
                    $error->getErrorMessage(),
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="import_errors_run_%d.csv"', $idImportJobRun));

        return $response;
    }

    protected function buildFileInfoFromUpload(UploadedFile $uploadedFile): ImportJobRunFileInfoTransfer
    {
        return (new ImportJobRunFileInfoTransfer())
            ->setUploadedFilePath($uploadedFile->getPathname())
            ->setOriginalFileName($uploadedFile->getClientOriginalName())
            ->setContentType($uploadedFile->getMimeType() ?: 'text/csv')
            ->setSize((int)$uploadedFile->getSize());
    }

    protected function resolveImportJobByReference(string $reference): ImportJobTransfer
    {
        $criteria = (new ImportJobCriteriaTransfer())
            ->setImportJobConditions(
                (new ImportJobConditionsTransfer())->addReference($reference),
            );

        $collection = $this->getFacade()->getImportJobCollection($criteria);
        $importJobs = $collection->getImportJobs();

        if ($importJobs->count() === 0) {
            throw new NotFoundHttpException(
                sprintf('Import job with reference "%s" not found.', $reference),
            );
        }

        return $importJobs->getIterator()->current();
    }

    protected function resolveImportJobById(int $idImportJob): ImportJobTransfer
    {
        $criteria = (new ImportJobCriteriaTransfer())
            ->setImportJobConditions(
                (new ImportJobConditionsTransfer())->addIdImportJob($idImportJob),
            );

        $collection = $this->getFacade()->getImportJobCollection($criteria);
        $importJobs = $collection->getImportJobs();

        if ($importJobs->count() === 0) {
            throw new NotFoundHttpException(
                sprintf('Import job with ID %d not found.', $idImportJob),
            );
        }

        return $importJobs->getIterator()->current();
    }
}
