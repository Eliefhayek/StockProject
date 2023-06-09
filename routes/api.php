<?php

use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/stock',[ReportController::class,'Report']);
Route::get('/try',[ReportController::class,'getSymbol']);
Route::get('/chart',[ReportController::class,"chart"]);
Route::get('/swot',[ReportController::class,"SwotReview"]);
Route::get('/pdf',[ReportController::class,'CreatePDF']);
Route::get('/test',[ReportController::class,'testing']);
Route::get('/testt',[ReportController::class,'testt']);
