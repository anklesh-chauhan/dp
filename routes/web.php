<?php

use App\Http\Controllers\ChangeControlReportController;
use App\Http\Controllers\ControlledDocumentPrintController;
use App\Http\Controllers\CsvValidationReportController;
use App\Http\Controllers\DocumentDistributionReportController;
use App\Http\Controllers\ReportTemplatePreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/controlled-documents/{controlledDocument}/print', ControlledDocumentPrintController::class)
    ->middleware(['auth', 'module:dms', 'can:view,controlledDocument'])
    ->name('controlled-documents.print');

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
