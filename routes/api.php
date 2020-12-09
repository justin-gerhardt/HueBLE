<?php

use App\Http\Controllers\API\LightGroupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\BulbController;

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


Route::get('bulbs/{bulb}/lit', [BulbController::class, 'isLit']);
Route::post('bulbs/{bulb}/lit', [BulbController::class, 'setState']);
Route::apiResource('bulbs', BulbController::class);

Route::get('groups/{group}/lit', [LightGroupController::class, 'isLit']);
Route::post('groups/{group}/lit', [LightGroupController::class, 'setState']);
Route::apiResource('groups', LightGroupController::class);

