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
use App\Http\Controllers\Api\V1\WilayahController;
use App\Http\Controllers\Api\RelawanAuthController;
use App\Http\Controllers\Api\RelawanOperasionalController;
use App\Http\Controllers\Api\AdminPesanController;
use App\Http\Controllers\Api\V1\Admin\RelawanVerifikasiController;

// =============================================================================
// ROUTE RELAWAN — didaftarkan sebelum route publik faskes/{id}
// =============================================================================

Route::prefix('v1/relawan-auth')->middleware('throttle:api')->group(function () {
    Route::post('login', [RelawanAuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('forgot-password', [RelawanAuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('reset-password', [RelawanAuthController::class, 'resetPassword'])->middleware('throttle:5,1');
    Route::middleware('auth:akun_relawan')->group(function () {
        Route::post('logout', [RelawanAuthController::class, 'logout']);
        Route::get('me',     [RelawanAuthController::class, 'me']);
    });
});

Route::prefix('v1/relawan')
    ->middleware(['throttle:api', 'auth:akun_relawan', 'akun.aktif:akun_relawan'])
    ->group(function () {
        Route::put('lokasi',               [RelawanOperasionalController::class, 'updateLokasi']);
        Route::post('fcm-token',           [RelawanOperasionalController::class, 'updateFcmToken']);
        Route::get('laporan-terdekat',     [RelawanOperasionalController::class, 'laporanTerdekat']);
        Route::get('laporan-riwayat',      [RelawanOperasionalController::class, 'laporanRiwayat']);
        Route::get('laporan/{id}',         [RelawanOperasionalController::class, 'detailLaporan'])->whereNumber('id');
        Route::post('laporan/{id}/claim',  [RelawanOperasionalController::class, 'claimLaporan'])->whereNumber('id');
        Route::put('laporan/{id}/selesai', [RelawanOperasionalController::class, 'selesaikanLaporan'])->whereNumber('id');
        Route::get('peta',                 [RelawanOperasionalController::class, 'dataPeta']);
        Route::get('notifikasi',           [RelawanOperasionalController::class, 'notifikasi']);
        Route::put('notifikasi/{id}/baca', [RelawanOperasionalController::class, 'tandaiBaca'])->whereNumber('id');
        Route::get('pesan-admin',              [AdminPesanController::class, 'indexRelawan']);
        Route::get('pesan-admin/{id}',         [AdminPesanController::class, 'showRelawan'])->whereNumber('id');
        Route::put('pesan-admin/{id}/baca',    [AdminPesanController::class, 'tandaiBacaRelawan'])->whereNumber('id');
    });

Route::prefix('v1/admin')
    ->middleware(['throttle:api', 'admin.api'])
    ->group(function () {
        Route::post('relawan/{relawan}/verifikasi', [RelawanVerifikasiController::class, 'verifikasi'])
            ->whereNumber('relawan');
        Route::post('relawan/{relawan}/tolak', [RelawanVerifikasiController::class, 'tolak'])
            ->whereNumber('relawan');
    });

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
    Route::get('wilayah/lokasi', [WilayahController::class, 'deteksiLokasi']);
    Route::get('wilayah/opsi', [WilayahController::class, 'opsiFilter']);
    Route::get('faskes', [FaskesController::class, 'index']);
    Route::get('faskes/{faskes}', [FaskesController::class, 'show'])->whereNumber('faskes');
    Route::get('ambulans', [AmbulansController::class, 'index']);
    Route::get('edukasi', [PedomanBhdController::class, 'index']);
    Route::get('edukasi/{pedomanBhd}', [PedomanBhdController::class, 'show'])->whereNumber('pedomanBhd');
    Route::get('pedoman-bhd', [PedomanBhdController::class, 'index']); // alias kompatibel
    Route::get('pedoman-bhd/{pedomanBhd}', [PedomanBhdController::class, 'show'])->whereNumber('pedomanBhd');
    Route::get('zona-rawan', [ZonaRawanBencanaController::class, 'index']);
    Route::get('zona-rawan/{zonaRawanBencana}', [ZonaRawanBencanaController::class, 'show']);
    Route::get('titik-evakuasi', [TitikEvakuasiController::class, 'index']);

    // Terautentikasi — wajib token pengguna
    Route::middleware('auth:pengguna')->group(function () {
        // B. Laporan Bencana
        Route::get('laporan', [LaporanBencanaController::class, 'index']);
        Route::get('laporan/{laporanBencana}', [LaporanBencanaController::class, 'show'])->whereNumber('laporanBencana');
        Route::post('laporan', [LaporanBencanaController::class, 'store']);
        
        // F. Relawan
        Route::post('relawan', [RelawanController::class, 'store']);
        Route::get('relawan/status', [RelawanController::class, 'status']);
    });
});
