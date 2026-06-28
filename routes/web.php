<?php

use App\Http\Controllers\SopDocumentPrintController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sop-documents/{sopDocument}/print', SopDocumentPrintController::class)
    ->middleware(['auth', 'can:view,sopDocument'])
    ->name('sop-documents.print');
