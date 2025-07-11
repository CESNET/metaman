<?php

use App\Http\Controllers\EntityidController;
use App\Http\Controllers\StatisticController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('entityid', EntityidController::class)->name('api:entityid');
Route::get('statistics', StatisticController::class)->name('api:statistics');
