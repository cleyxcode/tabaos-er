# PROMPT: Bangun REST API untuk Mobile App (Laravel 13 + Sanctum) — Sistem Tanggap Bencana Gempa Bumi

## KONTEKS PROJECT

Ini lanjutan dari project `tabaos-project` yang admin panelnya (Laravel 13 + Filament 5) sudah selesai dibuat, lengkap dengan migration, model, dan seluruh skema database (12 tabel: `wilayah`, `users`, `pengguna`, `laporan_bencana`, `relawan`, `petugas_emergency`, `faskes`, `ambulans`, `zona_rawan_bencana`, `titik_evakuasi`, `penugasan`, `pedoman_bhd`).

Sekarang buat **REST API** di project yang sama untuk dikonsumsi aplikasi mobile Flutter (dikerjakan terpisah, di luar scope prompt ini). API ini murni untuk pengguna mobile (warga masyarakat), menggunakan tabel `pengguna` — BUKAN tabel `users` (staff/admin).

**Environment**: migration, model, dan relasi SEMUA SUDAH ADA dari pekerjaan sebelumnya. Jangan buat ulang migration/model yang sudah ada — hanya tambahkan yang memang belum ada (guard, controller, resource, request, route). Kalau ada model/tabel yang ternyata belum ada, baru buat sesuai skema di prompt admin panel sebelumnya.

---

## PRINSIP DESAIN API

1. Autentikasi pakai **Laravel Sanctum**, dengan guard terpisah bernama `pengguna` (jangan pakai guard `web`/`users` yang dipakai admin panel).
2. Semua endpoint diawali `/api/v1/...` (versioning dari awal).
3. Response format konsisten (lihat bagian "Format Response Standar" di bawah) — WAJIB dipakai di semua endpoint, jangan campur format bebas.
4. Endpoint dibagi 2 kelompok:
   - **Publik** (tidak perlu login): direktori faskes, ambulans, kontak darurat, pemetaan rawan bencana, pedoman BHD — karena info ini harus tetap bisa diakses warga saat kondisi darurat walau belum sempat daftar akun.
   - **Terautentikasi** (butuh token Sanctum): buat laporan bencana, riwayat laporan pribadi, daftar jadi relawan, update profil.
5. Gunakan **Laravel API Resource** (`php artisan make:resource`) untuk semua response, jangan return Model/query builder mentah.
6. Gunakan **Form Request** (`php artisan make:request`) untuk semua validasi input, jangan validasi inline di controller.
7. Pakai **rate limiting** khusus untuk endpoint publik yang rawan disalahgunakan (`throttle:60,1` sebagai default, sesuaikan per endpoint jika perlu).
8. Field lokasi (`latitude`, `longitude`) dan `polygon` (untuk zona rawan) harus konsisten formatnya di response — lihat bagian "Format Lokasi".

---

## KONFIGURASI GUARD `pengguna` (SANCTUM)

### 1. Update `config/auth.php`

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'pengguna' => [
        'driver' => 'sanctum',
        'provider' => 'pengguna',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
    'pengguna' => [
        'driver' => 'eloquent',
        'model' => App\Models\Pengguna::class,
    ],
],
```

### 2. Model `Pengguna` Harus Implement `HasApiTokens`

```php
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Pengguna extends Authenticatable
{
    use HasApiTokens;
    // relasi laporan(), relawan() tetap seperti yang sudah dibuat sebelumnya
}
```

### 3. Route API Sanctum Diarahkan ke Guard `pengguna`

Di `bootstrap/app.php` (struktur Laravel 13), pastikan middleware `auth:sanctum` pada grup route mobile diarahkan memakai provider `pengguna`. Cara paling aman: definisikan middleware khusus di route group menggunakan `auth:pengguna` dengan Sanctum sudah dikonfigurasi provider-nya seperti di atas — Sanctum otomatis resolve ke model `Pengguna` selama guard `pengguna` dipakai.

---

## FORMAT RESPONSE STANDAR

Semua response API — sukses maupun gagal — WAJIB pakai format ini (buat helper/trait `ApiResponse` yang dipakai di semua controller):

**Sukses:**
```json
{
  "success": true,
  "message": "Berhasil mengambil data",
  "data": { }
}
```

**Sukses dengan pagination:**
```json
{
  "success": true,
  "message": "Berhasil mengambil data",
  "data": [ ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 68
  }
}
```

**Gagal (validasi):**
```json
{
  "success": false,
  "message": "Data yang dikirim tidak valid",
  "errors": {
    "nomor_kontak": ["Nomor kontak wajib diisi"]
  }
}
```

**Gagal (umum, 404/403/500):**
```json
{
  "success": false,
  "message": "Laporan tidak ditemukan"
}
```

Buat `App\Exceptions\Handler` (atau `bootstrap/app.php` sesuai struktur Laravel 13) supaya SEMUA exception di route `/api/*` otomatis di-render ke format JSON di atas (jangan biarkan Laravel return HTML error page untuk request API).

---

## FORMAT LOKASI (WAJIB KONSISTEN)

Field lokasi selalu dikembalikan sebagai object nested, bukan field lat/long terpisah di root — supaya gampang di-parsing langsung ke `LatLng` di Flutter:

```json
{
  "location": {
    "lat": -3.6954,
    "lng": 128.1814
  }
}
```

Untuk zona rawan bencana (polygon):
```json
{
  "polygon": [
    { "lat": -3.690, "lng": 128.180 },
    { "lat": -3.695, "lng": 128.185 }
  ]
}
```

Implementasikan ini lewat accessor di API Resource masing-masing (`LaporanBencanaResource`, `FaskesResource`, dst), JANGAN ubah struktur kolom database yang sudah ada (`latitude`/`longitude` tetap kolom terpisah di DB, hanya di-transform saat jadi response JSON).

---

## DAFTAR ENDPOINT LENGKAP

### A. Autentikasi (`/api/v1/auth`)

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| POST | `/api/v1/auth/register` | Publik | Daftar akun `pengguna` baru (name, phone, email nullable, password) |
| POST | `/api/v1/auth/login` | Publik | Login pakai phone/email + password, return Sanctum token |
| POST | `/api/v1/auth/logout` | Token | Revoke token yang sedang dipakai |
| GET | `/api/v1/auth/me` | Token | Ambil profil pengguna yang sedang login |
| PUT | `/api/v1/auth/me` | Token | Update profil (name, email) |

Validasi register: `phone` unique di tabel `pengguna`, `password` minimal 8 karakter, `password_confirmation` wajib sama.

### B. Laporan dan Bantuan (`/api/v1/laporan`)

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| GET | `/api/v1/laporan` | Token | Riwayat laporan milik pengguna yang login saja (filter `pengguna_id` = user login, JANGAN tampilkan laporan orang lain) |
| GET | `/api/v1/laporan/{id}` | Token | Detail 1 laporan — validasi kepemilikan, kalau bukan laporan miliknya return 403 |
| POST | `/api/v1/laporan` | Token | Buat laporan baru, sesuai field lengkap form (lihat detail field di bawah) |

Field yang diterima saat `POST /api/v1/laporan` (sesuai form mobile yang sudah ada):
```
nama_pelapor (string, required)
nomor_kontak (string, required)
jenis_kejadian (string, required)
di_lokasi_kejadian (boolean, required)
latitude (numeric, required_if:di_lokasi_kejadian,true)
longitude (numeric, required_if:di_lokasi_kejadian,true)
alamat_lokasi (string, nullable)
tanggal_kejadian (date, required)
deskripsi (string, required)
foto (array of file image, nullable, max 5 file, max 2MB per file)
meninggal_jumlah (integer, nullable, default 0)
meninggal_jenis_kelamin (string, nullable)
penyebab_meninggal (string, nullable)
hilang_jumlah (integer, nullable, default 0)
hilang_jenis_kelamin (string, nullable)
luka_berat_jumlah (integer, nullable, default 0)
luka_berat_jenis_kelamin (string, nullable)
penyebab_luka_berat (string, nullable)
luka_ringan_jumlah (integer, nullable, default 0)
luka_ringan_jenis_kelamin (string, nullable)
penyebab_luka_ringan (string, nullable)
```

Saat laporan dibuat: otomatis set `pengguna_id` = user login, `status` = `pending`, simpan file foto ke disk `public` di folder `laporan-bencana/{id}/`, simpan path-nya sebagai array json di kolom `foto`.

### C. Panggilan Darurat (`/api/v1/petugas-emergency`)

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| GET | `/api/v1/petugas-emergency` | Publik | List petugas emergency, hanya yang `status = aktif` |

Query parameter:
- `kategori` (filter: `medis`, `sar`, `logistik`, `lainnya`)
- `search` (cari berdasarkan nama)

Response harus include nomor darurat resmi statis (112, 119, 110, 115) sebagai bagian terpisah dari list dinamis — bisa berupa config array di controller, bukan dari database, karena ini nomor tetap.

### D. Rumah Sakit dan Puskesmas Terdekat (`/api/v1/faskes`)

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| GET | `/api/v1/faskes` | Publik | List semua faskes |
| GET | `/api/v1/faskes/{id}` | Publik | Detail 1 faskes, termasuk daftar ambulans miliknya (nested) |

Query parameter:
- `tipe` (filter: `rumah_sakit`, `puskesmas`, `apotek`)
- `wilayah_id` (filter berdasarkan wilayah)
- `lat` & `lng` & `radius_km` (opsional — kalau dikirim, urutkan hasil berdasarkan jarak terdekat dari titik ini, pakai formula Haversine di query)
- `search` (cari berdasarkan nama)

### E. Ambulans (`/api/v1/ambulans`)

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| GET | `/api/v1/ambulans` | Publik | List ambulans, termasuk data faskes induknya (nested, untuk tampilkan lokasi) |

Query parameter:
- `status` (filter: `tersedia`, `tidak_tersedia`)
- `jenis_layanan` (filter: `gratis`, `berbayar`)

### F. Daftar Relawan (`/api/v1/relawan`)

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| POST | `/api/v1/relawan` | Token | Daftar jadi relawan (nik, alamat, keahlian) — otomatis `pengguna_id` = user login, `status` = `pending` |
| GET | `/api/v1/relawan/status` | Token | Cek status pendaftaran relawan milik user login (pending/disetujui/ditolak), return 404 kalau belum pernah daftar |

Validasi: 1 pengguna hanya boleh punya 1 data relawan (sesuai constraint unique di DB) — kalau sudah pernah daftar, `POST` kedua harus return error jelas ("Kamu sudah terdaftar sebagai relawan").

### G. Pedoman Bantu Hidup Dasar (`/api/v1/pedoman-bhd`)

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| GET | `/api/v1/pedoman-bhd` | Publik | List semua pedoman |
| GET | `/api/v1/pedoman-bhd/{id}` | Publik | Detail 1 pedoman + URL file lengkap (bukan path relatif) |

Query parameter:
- `tipe_file` (filter: `pdf`, `video`, `gambar`, `dokumen`)

### H. Pemetaan Rawan Bencana (`/api/v1/zona-rawan` dan `/api/v1/titik-evakuasi`)

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| GET | `/api/v1/zona-rawan` | Publik | List semua zona rawan bencana, termasuk polygon dan titik evakuasi di dalamnya (nested) |
| GET | `/api/v1/zona-rawan/{id}` | Publik | Detail 1 zona |
| GET | `/api/v1/titik-evakuasi` | Publik | List semua titik evakuasi (bisa dipanggil terpisah tanpa harus lewat zona) |

Query parameter `/api/v1/zona-rawan`:
- `tingkat_risiko` (filter: `tinggi`, `sedang`, `rendah`)
- `wilayah_id` (filter berdasarkan wilayah)
- `search` (cari berdasarkan nama zona)

---

## STRUKTUR FILE YANG PERLU DIBUAT

```
app/Http/Controllers/Api/V1/
    AuthController.php
    LaporanBencanaController.php
    PetugasEmergencyController.php
    FaskesController.php
    AmbulansController.php
    RelawanController.php
    PedomanBhdController.php
    ZonaRawanBencanaController.php
    TitikEvakuasiController.php

app/Http/Requests/Api/
    RegisterRequest.php
    LoginRequest.php
    UpdateProfilRequest.php
    StoreLaporanBencanaRequest.php
    StoreRelawanRequest.php

app/Http/Resources/Api/
    PenggunaResource.php
    LaporanBencanaResource.php
    PetugasEmergencyResource.php
    FaskesResource.php
    AmbulansResource.php
    RelawanResource.php
    PedomanBhdResource.php
    ZonaRawanBencanaResource.php
    TitikEvakuasiResource.php

app/Traits/
    ApiResponse.php   (helper format response standar di atas)

routes/api.php   (semua route didaftarkan di sini, prefix v1, group publik vs token terpisah jelas)
```

---

## CONTOH STRUKTUR ROUTE (`routes/api.php`)

```php
Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::middleware('auth:pengguna')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::put('me', [AuthController::class, 'updateProfil']);
        });
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

    // Terautentikasi — wajib token
    Route::middleware('auth:pengguna')->group(function () {
        Route::get('laporan', [LaporanBencanaController::class, 'index']);
        Route::get('laporan/{laporanBencana}', [LaporanBencanaController::class, 'show']);
        Route::post('laporan', [LaporanBencanaController::class, 'store']);
        Route::post('relawan', [RelawanController::class, 'store']);
        Route::get('relawan/status', [RelawanController::class, 'status']);
    });
});
```

Terapkan `throttle:api` (default Laravel) di seluruh grup `v1`, dan tambahkan `throttle:10,1` khusus di route `register` dan `login` untuk cegah brute force.

---

## SINKRONISASI DENGAN ADMIN PANEL (PENTING)

Karena data ini dibagi dengan admin panel Filament yang sudah dibuat sebelumnya, pastikan:

1. Laporan yang dibuat lewat API otomatis muncul di Filament `LaporanBencanaResource` tanpa perlu perubahan apapun di sisi admin (karena satu tabel yang sama).
2. Field `status` laporan HANYA bisa diubah dari admin panel (lewat action "Verifikasi Laporan" yang sudah dibuat) — API mobile **tidak boleh punya endpoint untuk mengubah status laporan**. Ini sudah otomatis aman selama endpoint `PUT/PATCH /laporan/{id}` memang tidak dibuat di atas.
3. Field `status` relawan (`pending`/`disetujui`/`ditolak`) juga HANYA diubah dari admin panel (action "Setujui"/"Tolak" yang sudah dibuat) — API mobile hanya boleh **membaca** status ini lewat `GET /relawan/status`.
4. Data faskes, ambulans, petugas emergency, pedoman BHD, zona rawan, dan titik evakuasi SEPENUHNYA dikelola dari admin panel — API mobile hanya expose endpoint `GET` (read-only) untuk semua ini, TIDAK ADA endpoint create/update/delete di sisi mobile untuk data-data ini.

---

## DELIVERABLES / CHECKLIST EKSEKUSI

Kerjakan berurutan, pastikan setiap langkah bisa dites lewat `sail artisan route:list --path=api` sebelum lanjut ke langkah berikutnya:

1. Install Sanctum kalau belum ada (`sail composer require laravel/sanctum`, publish config & migration, migrate)
2. Konfigurasi guard `pengguna` di `config/auth.php`, tambahkan `HasApiTokens` ke model `Pengguna`
3. Buat trait `ApiResponse` untuk format response standar
4. Buat semua Form Request sesuai validasi yang disebutkan di atas
5. Buat semua API Resource, terapkan format lokasi nested (`location: {lat, lng}`) sesuai ketentuan
6. Buat semua Controller sesuai daftar endpoint di atas, gunakan Resource dan Form Request yang sudah dibuat
7. Daftarkan semua route di `routes/api.php` sesuai struktur contoh di atas, dengan rate limiting yang sesuai
8. Test manual tiap endpoint pakai `sail artisan route:list --path=api` untuk pastikan semua route terdaftar dengan benar, lalu tes minimal endpoint `register`, `login`, dan `POST laporan` lewat Postman/Insomnia untuk pastikan response format sesuai standar di atas

Jangan buat endpoint apapun yang mengizinkan mobile user mengubah data master (faskes, ambulans, zona rawan, dll) atau mengubah status laporan/relawan — itu wewenang admin panel saja, sesuai pembagian yang sudah ditentukan.
