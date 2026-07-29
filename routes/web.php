<?php

use App\Http\Controllers\ControlledDocumentPrintController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/controlled-documents/{controlledDocument}/print', ControlledDocumentPrintController::class)
    ->middleware(['auth', 'module:dms', 'can:view,controlledDocument'])
    ->name('controlled-documents.print');
