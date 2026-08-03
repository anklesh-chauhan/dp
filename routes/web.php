<?php

use App\Http\Controllers\ChangeControlReportController;
use App\Http\Controllers\ControlledDocumentOriginalArtifactController;
use App\Http\Controllers\ControlledDocumentPrintController;
use App\Http\Controllers\ControlledDocumentViewerController;
use App\Http\Controllers\CsvValidationReportController;
use App\Http\Controllers\DocumentDistributionReportController;
use App\Http\Controllers\DocumentTemplateDraftPreviewController;
use App\Http\Controllers\ReportTemplatePreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/controlled-documents/{controlledDocument}/print', ControlledDocumentPrintController::class)
    ->middleware(['auth', 'module:dms', 'can:view,controlledDocument'])
    ->defaults('access_mode', 'print')
    ->name('controlled-documents.print');

Route::get('/controlled-documents/{controlledDocument}/viewer', ControlledDocumentViewerController::class)
    ->middleware(['auth', 'module:dms', 'can:view,controlledDocument'])
    ->name('controlled-documents.viewer');

Route::get('/controlled-documents/{controlledDocument}/original-artifacts/{artifact}/viewer', ControlledDocumentViewerController::class)
    ->middleware(['auth', 'module:dms', 'can:view,controlledDocument'])
    ->name('controlled-documents.original-artifacts.viewer');

Route::get('/controlled-documents/{controlledDocument}/pdf-content', ControlledDocumentPrintController::class)
    ->middleware(['auth', 'module:dms', 'can:view,controlledDocument'])
    ->defaults('access_mode', 'view')
    ->name('controlled-documents.pdf-content');

Route::get('/controlled-documents/{controlledDocument}/download', ControlledDocumentPrintController::class)
    ->middleware(['auth', 'module:dms', 'can:view,controlledDocument'])
    ->defaults('access_mode', 'download')
    ->name('controlled-documents.download');

Route::get('/controlled-documents/{controlledDocument}/original-artifacts/{artifact}/download', ControlledDocumentOriginalArtifactController::class)
    ->middleware(['auth', 'module:dms', 'can:view,controlledDocument'])
    ->defaults('artifact_access_mode', 'download')
    ->name('controlled-documents.original-artifacts.download');

Route::get('/controlled-documents/{controlledDocument}/original-artifacts/{artifact}/view', ControlledDocumentOriginalArtifactController::class)
    ->middleware(['auth', 'module:dms', 'can:view,controlledDocument'])
    ->defaults('artifact_access_mode', 'view')
    ->name('controlled-documents.original-artifacts.view');

Route::get('/controlled-documents/{controlledDocument}/original-artifacts/{artifact}/print', ControlledDocumentOriginalArtifactController::class)
    ->middleware(['auth', 'module:dms', 'can:view,controlledDocument'])
    ->defaults('artifact_access_mode', 'print')
    ->name('controlled-documents.original-artifacts.print');

Route::get('/change-controls/{changeControl}/report', ChangeControlReportController::class)
    ->middleware(['auth', 'module:qms', 'can:View:ChangeControl'])
    ->name('change-controls.report');

Route::get('/csv-validation-projects/{csvValidationProject}/report', CsvValidationReportController::class)
    ->middleware(['auth', 'module:qms', 'can:view,csvValidationProject'])
    ->name('csv-validation-projects.report');

Route::get('/reports/document-distribution', DocumentDistributionReportController::class)
    ->middleware(['auth', 'module:dms', 'can:ViewAny:ControlledDocument'])
    ->name('reports.document-distribution');

Route::get('/report-templates/{reportTemplate}/preview', ReportTemplatePreviewController::class)
    ->middleware(['auth', 'module:dms', 'can:view,reportTemplate'])
    ->name('report-templates.preview');

Route::get('/document-templates/{documentTemplate}/draft-preview', DocumentTemplateDraftPreviewController::class)
    ->middleware(['auth', 'module:dms', 'can:view,documentTemplate'])
    ->name('document-templates.draft-preview');
