<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SummaryController;

Route::get('/', function () {
    $defaultPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'default.php';
    if (file_exists($defaultPath)) {
        require $defaultPath;
        exit;
    }

    abort(404, 'Welcome page not found.');
});
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/api/readings', [DashboardController::class, 'api']);
Route::view('/learn_more', 'learn_more');
Route::get('/summary', [SummaryController::class, 'index'])->name('summary.index');
Route::get('/api/summaries', [SummaryController::class, 'api'])->name('summary.api');     
Route::post('/api/summaries/delete', [SummaryController::class, 'delete'])->name('summary.delete');
