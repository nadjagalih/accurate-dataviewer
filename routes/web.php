<?php

use App\Http\Controllers\Accurate\ExplorerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ExplorerController::class, 'dashboard'])
    ->name('dashboard');

Route::prefix('accurate')->name('accurate.')->group(function (): void {
    Route::get('databases', [ExplorerController::class, 'databases'])
        ->name('databases');
    Route::get('inbound', [ExplorerController::class, 'inbound'])
        ->name('inbound');
    Route::get('daily-inbound', [ExplorerController::class, 'dailyInbound'])
        ->name('daily-inbound');
    Route::get('inbound-pr', [ExplorerController::class, 'inboundPr'])
        ->name('inbound-pr');
    Route::get('{module}/list', [ExplorerController::class, 'list'])
        ->whereIn('module', ['purchase-order', 'purchase-requisition', 'item-transfer'])
        ->name('list');
    Route::get('{module}/detail', [ExplorerController::class, 'detail'])
        ->whereIn('module', ['purchase-order', 'purchase-requisition', 'item-transfer'])
        ->name('detail');
});
