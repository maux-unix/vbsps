<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ParkingPredictionController;
use App\Http\Controllers\LiveParkingSlotController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [LiveParkingSlotController::class, 'index']);

Route::get('/parking-prediction', [ParkingPredictionController::class, 'index']);

Route::get('/checksum-analysis', [\App\Http\Controllers\LiveParkingSlotController::class, 'indexingChecksum'])->name('checksum.analysis');
