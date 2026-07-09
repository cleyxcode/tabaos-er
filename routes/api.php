<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\LaporanBencanaController;
use App\Http\Controllers\Api\V1\PetugasEmergencyController;
use App\Http\Controllers\Api\V1\FaskesController;
use App\Http\Controllers\Api\V1\AmbulansController;
use App\Http\Controllers\Api\V1\RelawanController;
use App\Http\Controllers\Api\V1\PedomanBhdController;
use App\Http\Controllers\Api\V1\ZonaRawanBencanaController;
use App\Http\Controllers\Api\V1\TitikEvakuasiController;

Route::prefix('v1')->middleware('throttle:api')->group(function () {

    // A. Autentikasi
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
        
        Route::middleware('auth:pengguna')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::put('me', [AuthController::class, 'updateProfil']);
        });

        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

    });

    // Publik — tanpa login
    Route::get('petugas-emergency', [PetugasEmergencyController::class, 'index']);
    Route::get('faskes', [FaskesController::class, 'index']);
    Route::get('faskes/{faskes}', [FaskesController::class, 'show']);
    Route::get('ambulans', [AmbulansController::class, 'index']);
    Route::get('pedoman-bhd', [PedomanBhdController::class, 'index']);
    Route::get('pedoman-bhd/{pedomanBhd}', [PedomanBhdController::class, 'show']);
    Route::get('zona-rawan', [ZonaRawanBencanaController::class, 'index']);
    Route::get('zona-rawan/{zonaRawanBencana}', [ZonaRawanBencanaController::class, 'show']);
    Route::get('titik-evakuasi', [TitikEvakuasiController::class, 'index']);

    // Terautentikasi — wajib token pengguna
    Route::middleware('auth:pengguna')->group(function () {
        // B. Laporan Bencana
        Route::get('laporan', [LaporanBencanaController::class, 'index']);
        Route::get('laporan/{laporanBencana}', [LaporanBencanaController::class, 'show']);
        Route::post('laporan', [LaporanBencanaController::class, 'store']);
        
        // F. Relawan
        Route::post('relawan', [RelawanController::class, 'store']);
        Route::get('relawan/status', [RelawanController::class, 'status']);
    });
});
