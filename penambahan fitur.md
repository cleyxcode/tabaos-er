## KONTEKS & STATUS PROJECT

Project ini adalah sistem tanggap darurat bencana gempa bumi **"Tabaos"** dari Fakultas Kesehatan, beroperasi di Kota Ambon, Maluku. Stack yang dipakai:

- **Backend**: Laravel 13 + Laravel Sail (Docker) + Sanctum
- **Admin Panel**: Filament 5
- **Mobile App**: Flutter (dikerjakan terpisah, tidak disentuh di prompt ini)

**Project sudah berjalan** — migration, model, seeder, Filament Resource, dan API sudah ada. Prompt ini adalah **revisi/penambahan**, bukan build dari awal. Jangan timpa atau hapus kode yang sudah ada kecuali disebutkan eksplisit.

**Jalankan semua perintah artisan via Laravel Sail:**
```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
./vendor/bin/sail composer require ...
```
Jangan pakai `php artisan` langsung — project ini menggunakan Laravel Sail.

---

## RINGKASAN PERUBAHAN YANG DIPERLUKAN

Sistem sebelumnya hanya punya 2 tipe akun:
1. `users` — staff/admin internal, login ke Filament
2. `pengguna` — warga masyarakat, login ke mobile app

**Revisi ini menambahkan 2 tipe akun baru yang masing-masing punya login mobile app sendiri:**
1. **Akun Relawan** (`akun_relawan`) — relawan yang sudah disetujui admin, login ke app relawan (Flutter terpisah)
2. **Akun Faskes** (`akun_faskes`) — petugas faskes, login ke app faskes (Flutter terpisah)

Kedua akun ini **dikelola penuh oleh admin** melalui Filament panel, dan mengakses data via **API endpoint baru** yang terpisah dari endpoint masyarakat.

---

## BAGIAN 1 — DATABASE: TABEL BARU

### 1A. Tabel `akun_relawan`

Tabel ini adalah akun login khusus untuk relawan yang sudah disetujui. **Berbeda dari tabel `relawan`** yang sudah ada (tabel `relawan` tetap dipakai untuk menyimpan data profil relawan yang mendaftar dari mobile app masyarakat). Tabel baru ini adalah akun operasional relawan setelah disetujui.

```php
Schema::create('akun_relawan', function (Blueprint $table) {
    $table->id();
    $table->foreignId('relawan_id')          // FK ke tabel relawan yang sudah ada
          ->unique()
          ->constrained('relawan')
          ->cascadeOnDelete();
    $table->string('email')->unique();
    $table->string('password');
    $table->string('fcm_token')->nullable(); // untuk push notification
    $table->decimal('latitude', 10, 7)->nullable();   // lokasi terakhir relawan
    $table->decimal('longitude', 10, 7)->nullable();
    $table->timestamp('lokasi_updated_at')->nullable();
    $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
    $table->rememberToken();
    $table->timestamps();
});
```

### 1B. Tabel `akun_faskes`

Akun login untuk petugas faskes. Terhubung ke tabel `faskes` yang sudah ada.

```php
Schema::create('akun_faskes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('faskes_id')           // FK ke tabel faskes yang sudah ada
          ->constrained('faskes')
          ->cascadeOnDelete();
    $table->string('nama_petugas');
    $table->string('email')->unique();
    $table->string('password');
    $table->string('fcm_token')->nullable();
    $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
    $table->rememberToken();
    $table->timestamps();
});
```

### 1C. Tabel `relawan_notifikasi`

History notifikasi yang diterima relawan saat laporan baru masuk.

```php
Schema::create('relawan_notifikasi', function (Blueprint $table) {
    $table->id();
    $table->foreignId('akun_relawan_id')
          ->constrained('akun_relawan')
          ->cascadeOnDelete();
    $table->foreignId('laporan_id')
          ->constrained('laporan_bencana')
          ->cascadeOnDelete();
    $table->boolean('sudah_dibaca')->default(false);
    $table->timestamp('dibaca_at')->nullable();
    $table->timestamps();
});
```

### 1D. Modifikasi Tabel `laporan_bencana` (ALTER, bukan buat ulang)

Tambahkan 2 kolom baru via migration terpisah:

```php
Schema::table('laporan_bencana', function (Blueprint $table) {
    $table->foreignId('akun_relawan_ditugaskan')
          ->nullable()
          ->nullOnDelete()
          ->constrained('akun_relawan')
          ->after('status');
    $table->enum('status_penanganan', [
        'belum_ditangani',
        'sedang_ditangani',
        'selesai_ditangani'
    ])->default('belum_ditangani')->after('akun_relawan_ditugaskan');
});
```

---

## BAGIAN 2 — MODEL ELOQUENT

### 2A. Model `AkunRelawan`

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class AkunRelawan extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'akun_relawan';

    protected $fillable = [
        'relawan_id', 'email', 'password',
        'fcm_token', 'latitude', 'longitude',
        'lokasi_updated_at', 'status',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'latitude'          => 'decimal:7',
        'longitude'         => 'decimal:7',
        'lokasi_updated_at' => 'datetime',
    ];

    // Relasi ke profil relawan (tabel relawan yang sudah ada)
    public function relawan()
    {
        return $this->belongsTo(Relawan::class);
    }

    // Shortcut ke data pengguna via relawan
    public function pengguna()
    {
        return $this->hasOneThrough(Pengguna::class, Relawan::class, 'id', 'id', 'relawan_id', 'pengguna_id');
    }

    public function notifikasi()
    {
        return $this->hasMany(RelawanNotifikasi::class);
    }

    public function laporanDitangani()
    {
        return $this->hasMany(LaporanBencana::class, 'akun_relawan_ditugaskan');
    }
}
```

### 2B. Model `AkunFaskes`

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class AkunFaskes extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'akun_faskes';

    protected $fillable = [
        'faskes_id', 'nama_petugas', 'email',
        'password', 'fcm_token', 'status',
    ];

    protected $hidden = ['password', 'remember_token'];

    public function faskes()
    {
        return $this->belongsTo(Faskes::class);
    }
}
```

### 2C. Model `RelawanNotifikasi`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RelawanNotifikasi extends Model
{
    protected $table = 'relawan_notifikasi';

    protected $fillable = [
        'akun_relawan_id', 'laporan_id',
        'sudah_dibaca', 'dibaca_at',
    ];

    protected $casts = [
        'sudah_dibaca' => 'boolean',
        'dibaca_at'    => 'datetime',
    ];

    public function akunRelawan()
    {
        return $this->belongsTo(AkunRelawan::class);
    }

    public function laporan()
    {
        return $this->belongsTo(LaporanBencana::class, 'laporan_id');
    }
}
```

### 2D. Tambahkan Relasi di Model yang Sudah Ada

Di model `Relawan` (sudah ada), tambahkan:
```php
public function akunRelawan()
{
    return $this->hasOne(AkunRelawan::class);
}
```

Di model `Faskes` (sudah ada), tambahkan:
```php
public function akunFaskes()
{
    return $this->hasMany(AkunFaskes::class);
}
```

---

## BAGIAN 3 — SANCTUM GUARD BARU

Di `config/auth.php`, tambahkan 2 guard baru untuk Sanctum agar token akun relawan dan faskes tidak tercampur dengan token pengguna masyarakat:

```php
'guards' => [
    // ... guard yang sudah ada tetap ...

    'akun_relawan' => [
        'driver'   => 'sanctum',
        'provider' => 'akun_relawan',
    ],

    'akun_faskes' => [
        'driver'   => 'sanctum',
        'provider' => 'akun_faskes',
    ],
],

'providers' => [
    // ... provider yang sudah ada tetap ...

    'akun_relawan' => [
        'driver' => 'eloquent',
        'model'  => App\Models\AkunRelawan::class,
    ],

    'akun_faskes' => [
        'driver' => 'eloquent',
        'model'  => App\Models\AkunFaskes::class,
    ],
],
```

Di `config/sanctum.php`, pastikan kedua model terdaftar di `guard`:
```php
'guard' => ['web', 'akun_relawan', 'akun_faskes'],
```

---

## BAGIAN 4 — API ENDPOINT BARU

Semua endpoint baru berada di prefix `/api/v1`. Buat file route terpisah atau tambahkan ke `routes/api.php` yang sudah ada dengan group prefix terpisah agar tidak bertabrakan dengan route masyarakat yang sudah ada.

### 4A. Endpoint Autentikasi Relawan (`/api/v1/relawan-auth/...`)

```
POST   /api/v1/relawan-auth/login
       Body: { email, password }
       Response: { token, akun_relawan: { id, email, status, relawan: { nama, keahlian, organisasi } } }

POST   /api/v1/relawan-auth/logout
       Auth: Bearer token (guard akun_relawan)
       Response: { message: 'Logged out' }

GET    /api/v1/relawan-auth/me
       Auth: Bearer token (guard akun_relawan)
       Response: data akun relawan + relasi relawan + pengguna
```

### 4B. Endpoint Operasional Relawan (`/api/v1/relawan/...`)

Semua endpoint di bawah ini wajib pakai middleware `auth:akun_relawan` dan status akun harus `aktif`.

```
PUT    /api/v1/relawan/lokasi
       Body: { latitude, longitude }
       Action: update latitude, longitude, lokasi_updated_at di akun_relawan
       Response: { message: 'Lokasi diperbarui', updated_at }

POST   /api/v1/relawan/fcm-token
       Body: { fcm_token }
       Action: simpan fcm_token ke akun_relawan yang login
       Response: { message: 'FCM token tersimpan' }

GET    /api/v1/relawan/laporan-terdekat
       Query: ?lat={lat}&lng={lng}&radius=10&page=1
       Action: ambil laporan_bencana dengan status_penanganan = belum_ditangani atau sedang_ditangani,
               hitung jarak dari koordinat relawan menggunakan Haversine formula,
               urutkan dari yang terdekat, pagination 10 per halaman
       Response: {
           data: [
               {
                   id, jenis_kejadian, deskripsi, status, status_penanganan,
                   latitude, longitude, alamat_lokasi,
                   tanggal_kejadian, created_at,
                   korban: { meninggal_jumlah, luka_berat_jumlah, luka_ringan_jumlah, hilang_jumlah },
                   jarak_km: float,                    // hasil kalkulasi Haversine
                   relawan_ditugaskan: { id, nama } | null
               }
           ],
           meta: { current_page, last_page, total }
       }

GET    /api/v1/relawan/laporan/{id}
       Action: detail laporan + semua akun_relawan aktif dalam radius 20km dari laporan
               (untuk ditampilkan di peta sebagai marker relawan lain)
       Response: {
           laporan: { ...semua field laporan... },
           relawan_terdekat: [
               { id, nama, latitude, longitude, jarak_km }
           ]
       }

POST   /api/v1/relawan/laporan/{id}/claim
       Action: set akun_relawan_ditugaskan = id relawan yang login,
               ubah status_penanganan = sedang_ditangani.
               Tolak (409 Conflict) jika laporan sudah punya relawan lain yang mengklaim.
       Response: { message: 'Laporan berhasil diklaim', laporan }

PUT    /api/v1/relawan/laporan/{id}/selesai
       Action: ubah status_penanganan = selesai_ditangani.
               Hanya relawan yang mengklaim (akun_relawan_ditugaskan == id login) yang boleh update.
       Response: { message: 'Laporan ditandai selesai', laporan }

GET    /api/v1/relawan/peta
       Query: ?lat={lat}&lng={lng}&radius=20
       Action: return semua laporan aktif (belum_ditangani + sedang_ditangani) +
               semua akun_relawan aktif yang lokasi_updated_at < 30 menit yang lalu
               dalam radius tertentu dari koordinat dikirim
       Response: {
           laporan: [ { id, latitude, longitude, jenis_kejadian, status_penanganan } ],
           relawan_aktif: [ { id, nama, latitude, longitude, lokasi_updated_at } ]
       }

GET    /api/v1/relawan/notifikasi
       Query: ?page=1
       Response: {
           data: [
               { id, laporan: { id, jenis_kejadian, alamat_lokasi }, sudah_dibaca, created_at }
           ],
           unread_count: integer
       }

PUT    /api/v1/relawan/notifikasi/{id}/baca
       Action: set sudah_dibaca = true, dibaca_at = now()
       Response: { message: 'Notifikasi ditandai dibaca' }
```

### 4C. Endpoint Autentikasi Faskes (`/api/v1/faskes-auth/...`)

```
POST   /api/v1/faskes-auth/login
       Body: { email, password }
       Response: { token, akun_faskes: { id, nama_petugas, email, faskes: { id, nama, tipe, alamat, latitude, longitude } } }

POST   /api/v1/faskes-auth/logout
       Auth: Bearer token (guard akun_faskes)
       Response: { message: 'Logged out' }

GET    /api/v1/faskes-auth/me
       Auth: Bearer token (guard akun_faskes)
       Response: data akun faskes + relasi faskes lengkap
```

### 4D. Endpoint Operasional Faskes (`/api/v1/faskes/...`)

Semua endpoint wajib middleware `auth:akun_faskes` dan status akun harus `aktif`.

```
POST   /api/v1/faskes/fcm-token
       Body: { fcm_token }
       Response: { message: 'FCM token tersimpan' }

GET    /api/v1/faskes/laporan
       Query: ?lat={lat}&lng={lng}&radius=15&status=pending&page=1
               (lat/lng default ke koordinat faskes sendiri kalau tidak dikirim)
       Action: ambil laporan_bencana dalam radius tertentu dari faskes,
               bisa difilter by status laporan, urutkan terdekat
       Response: {
           data: [
               {
                   id, jenis_kejadian, deskripsi, status, status_penanganan,
                   latitude, longitude, alamat_lokasi, tanggal_kejadian,
                   korban: { meninggal_jumlah, luka_berat_jumlah, luka_ringan_jumlah, hilang_jumlah },
                   jarak_km: float
               }
           ],
           meta: { current_page, last_page, total }
       }

GET    /api/v1/faskes/laporan/{id}
       Response: detail laporan lengkap

GET    /api/v1/faskes/peta
       Query: ?radius=15
       Action: return semua laporan aktif + semua akun_relawan aktif
               dalam radius dari koordinat faskes yang login
       Response: {
           faskes_saya: { id, nama, latitude, longitude },
           laporan: [ { id, latitude, longitude, jenis_kejadian, status, status_penanganan } ],
           relawan_aktif: [ { id, nama, latitude, longitude } ]
       }

GET    /api/v1/faskes/profil
       Response: detail faskes milik akun login + list ambulans terkait

GET    /api/v1/faskes/notifikasi
       Response: history notifikasi laporan yang masuk ke area faskes
```

---

## BAGIAN 5 — CONTROLLER & SERVICE

### 5A. Struktur Controller

Buat controller baru di `app/Http/Controllers/Api/`:

```
app/Http/Controllers/Api/
  RelawanAuthController.php     → login, logout, me
  RelawanOperasionalController.php → lokasi, fcm-token, laporan-terdekat, laporan detail,
                                     claim, selesai, peta, notifikasi
  FaskesAuthController.php      → login, logout, me
  FaskesOperasionalController.php → fcm-token, laporan, peta, profil, notifikasi
```

### 5B. Service: HaversineService

Buat `app/Services/HaversineService.php` yang dipakai oleh semua controller yang perlu kalkulasi jarak:

```php
<?php

namespace App\Services;

class HaversineService
{
    /**
     * Hitung jarak antara dua koordinat dalam kilometer.
     */
    public function hitungJarak(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Filter query builder berdasarkan radius (raw SQL Haversine).
     * Dipakai di Eloquent: LaporanBencana::query()->haversine(lat, lng, radius)
     */
    public function scopeQuery($query, float $lat, float $lng, float $radiusKm = 10)
    {
        return $query->selectRaw("
            *,
            ( 6371 * acos(
                cos(radians(?)) * cos(radians(latitude))
                * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude))
            )) AS jarak_km
        ", [$lat, $lng, $lat])
        ->havingRaw('jarak_km <= ?', [$radiusKm])
        ->orderBy('jarak_km');
    }
}
```

### 5C. Service: NotifikasiService

Buat `app/Services/NotifikasiService.php` untuk kirim push notification ke relawan saat laporan baru dibuat:

```php
<?php

namespace App\Services;

use App\Models\AkunRelawan;
use App\Models\LaporanBencana;
use App\Models\RelawanNotifikasi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotifikasiService
{
    protected HaversineService $haversine;

    public function __construct(HaversineService $haversine)
    {
        $this->haversine = $haversine;
    }

    /**
     * Kirim notifikasi ke semua relawan aktif dalam radius dari lokasi laporan.
     * Dipanggil dari LaporanBencana observer atau setelah POST /laporan berhasil.
     */
    public function kirimKeRelawanTerdekat(LaporanBencana $laporan, float $radiusKm = 10): void
    {
        if (! $laporan->latitude || ! $laporan->longitude) {
            return;
        }

        // Ambil relawan aktif dalam radius
        $relawanTerdekat = AkunRelawan::where('status', 'aktif')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereNotNull('fcm_token')
            ->get()
            ->filter(function ($akun) use ($laporan, $radiusKm) {
                $jarak = $this->haversine->hitungJarak(
                    $laporan->latitude, $laporan->longitude,
                    $akun->latitude, $akun->longitude
                );
                return $jarak <= $radiusKm;
            });

        foreach ($relawanTerdekat as $akun) {
            // Simpan ke history notifikasi
            RelawanNotifikasi::create([
                'akun_relawan_id' => $akun->id,
                'laporan_id'      => $laporan->id,
                'sudah_dibaca'    => false,
            ]);

            // Kirim FCM push notification
            $this->kirimFcm(
                token: $akun->fcm_token,
                title: 'Laporan Bencana Baru',
                body: "Ada laporan {$laporan->jenis_kejadian} di dekat lokasi kamu.",
                data: [
                    'laporan_id' => (string) $laporan->id,
                    'type'       => 'laporan_baru',
                ]
            );
        }
    }

    protected function kirimFcm(string $token, string $title, string $body, array $data = []): void
    {
        try {
            Http::withHeaders([
                'Authorization' => 'key=' . config('services.fcm.server_key'),
                'Content-Type'  => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $token,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                    'sound' => 'default',
                ],
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('FCM gagal kirim: ' . $e->getMessage());
        }
    }
}
```

Tambahkan di `config/services.php`:
```php
'fcm' => [
    'server_key' => env('FCM_SERVER_KEY'),
],
```

Tambahkan di `.env`:
```
FCM_SERVER_KEY=your_fcm_server_key_here
```

### 5D. Observer: Trigger Notifikasi Otomatis

Buat `app/Observers/LaporanBencanaObserver.php`:

```php
<?php

namespace App\Observers;

use App\Models\LaporanBencana;
use App\Services\NotifikasiService;

class LaporanBencanaObserver
{
    public function __construct(protected NotifikasiService $notifikasi) {}

    public function created(LaporanBencana $laporan): void
    {
        // Kirim notif ke relawan terdekat setiap ada laporan baru masuk
        $this->notifikasi->kirimKeRelawanTerdekat($laporan, radiusKm: 10);
    }
}
```

Daftarkan di `app/Providers/AppServiceProvider.php`:
```php
use App\Models\LaporanBencana;
use App\Observers\LaporanBencanaObserver;

public function boot(): void
{
    LaporanBencana::observe(LaporanBencanaObserver::class);
}
```

---

## BAGIAN 6 — ROUTES

Tambahkan di `routes/api.php` (jangan hapus route yang sudah ada):

```php
use App\Http\Controllers\Api\RelawanAuthController;
use App\Http\Controllers\Api\RelawanOperasionalController;
use App\Http\Controllers\Api\FaskesAuthController;
use App\Http\Controllers\Api\FaskesOperasionalController;

// =============================================
// ROUTE RELAWAN
// =============================================
Route::prefix('v1/relawan-auth')->group(function () {
    Route::post('login', [RelawanAuthController::class, 'login']);
    Route::middleware('auth:akun_relawan')->group(function () {
        Route::post('logout', [RelawanAuthController::class, 'logout']);
        Route::get('me',     [RelawanAuthController::class, 'me']);
    });
});

Route::prefix('v1/relawan')
    ->middleware(['auth:akun_relawan', 'akun.aktif:akun_relawan'])
    ->group(function () {
        Route::put('lokasi',                          [RelawanOperasionalController::class, 'updateLokasi']);
        Route::post('fcm-token',                      [RelawanOperasionalController::class, 'updateFcmToken']);
        Route::get('laporan-terdekat',                [RelawanOperasionalController::class, 'laporanTerdekat']);
        Route::get('laporan/{id}',                    [RelawanOperasionalController::class, 'detailLaporan']);
        Route::post('laporan/{id}/claim',             [RelawanOperasionalController::class, 'claimLaporan']);
        Route::put('laporan/{id}/selesai',            [RelawanOperasionalController::class, 'selesaikanLaporan']);
        Route::get('peta',                            [RelawanOperasionalController::class, 'dataPeta']);
        Route::get('notifikasi',                      [RelawanOperasionalController::class, 'notifikasi']);
        Route::put('notifikasi/{id}/baca',            [RelawanOperasionalController::class, 'tandaiBaca']);
    });

// =============================================
// ROUTE FASKES
// =============================================
Route::prefix('v1/faskes-auth')->group(function () {
    Route::post('login', [FaskesAuthController::class, 'login']);
    Route::middleware('auth:akun_faskes')->group(function () {
        Route::post('logout', [FaskesAuthController::class, 'logout']);
        Route::get('me',     [FaskesAuthController::class, 'me']);
    });
});

Route::prefix('v1/faskes')
    ->middleware(['auth:akun_faskes', 'akun.aktif:akun_faskes'])
    ->group(function () {
        Route::post('fcm-token',    [FaskesOperasionalController::class, 'updateFcmToken']);
        Route::get('laporan',       [FaskesOperasionalController::class, 'laporan']);
        Route::get('laporan/{id}',  [FaskesOperasionalController::class, 'detailLaporan']);
        Route::get('peta',          [FaskesOperasionalController::class, 'dataPeta']);
        Route::get('profil',        [FaskesOperasionalController::class, 'profil']);
        Route::get('notifikasi',    [FaskesOperasionalController::class, 'notifikasi']);
    });
```

### Middleware `akun.aktif`

Buat middleware baru `app/Http/Middleware/AkunAktif.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AkunAktif
{
    public function handle(Request $request, Closure $next, string $guard): mixed
    {
        $user = $request->user($guard);

        if (! $user || $user->status !== 'aktif') {
            return response()->json([
                'message' => 'Akun ini tidak aktif. Hubungi administrator.',
            ], 403);
        }

        return $next($request);
    }
}
```

Daftarkan di `bootstrap/app.php` (Laravel 13):
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'akun.aktif' => \App\Http\Middleware\AkunAktif::class,
    ]);
})
```

---

## BAGIAN 7 — FILAMENT RESOURCE BARU

Tambahkan 2 Filament Resource baru untuk manajemen akun relawan dan akun faskes. Letakkan di navigation group **"Manajemen Akun"** (group baru, di atas group yang sudah ada).

### 7A. `AkunRelawanResource`

```
Navigasi : Manajemen Akun → Akun Relawan
Icon     : heroicon-o-identification
```

**Kolom tabel:**
| Kolom | Tampilan |
|---|---|
| relawan.pengguna.name | Label: "Nama Relawan" |
| relawan.keahlian | Label: "Keahlian" |
| email | Label: "Email Akun" |
| status | Badge: aktif=success, nonaktif=danger |
| relawan.status | Badge: disetujui=success, pending=warning, ditolak=danger — Label: "Status Relawan" |
| lokasi_updated_at | Label: "Lokasi Terakhir Update", format datetime Indonesia |
| created_at | Label: "Dibuat" |

**Filter tabel:**
- SelectFilter `status` (aktif / nonaktif)
- SelectFilter `relawan.status` (disetujui / pending / ditolak)

**Form (create & edit):**
```php
// Section: Data Akun
Select::make('relawan_id')
    ->label('Relawan')
    ->relationship(
        'relawan',
        'id',
        fn($query) => $query->where('status', 'disetujui') // hanya tampilkan relawan yang sudah disetujui
                             ->whereDoesntHave('akunRelawan') // belum punya akun
    )
    ->getOptionLabelFromRecordUsing(fn($r) => $r->pengguna->name . ' — ' . $r->keahlian)
    ->searchable()
    ->required()
    ->helperText('Hanya relawan berstatus disetujui dan belum memiliki akun yang muncul di sini.')

TextInput::make('email')
    ->email()
    ->required()
    ->unique(ignoreRecord: true)

TextInput::make('password')
    ->password()
    ->required(fn($context) => $context === 'create') // wajib saat create, opsional saat edit
    ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : null)
    ->dehydrated(fn($state) => filled($state))
    ->helperText('Kosongkan jika tidak ingin mengubah password.')

// Section: Status Akun
Select::make('status')
    ->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'])
    ->required()
    ->default('aktif')
```

**Actions:**
- Action "Nonaktifkan" — ubah status ke `nonaktif`, konfirmasi dulu (modal)
- Action "Aktifkan" — ubah status ke `aktif`
- Action "Reset Password" — modal input password baru, hash dan simpan

**Halaman View (InfoList):**
Tampilkan read-only: nama relawan, keahlian, organisasi, email akun, status, koordinat lokasi terakhir + waktu update.

### 7B. `AkunFaskesResource`

```
Navigasi : Manajemen Akun → Akun Faskes
Icon     : heroicon-o-building-office-2
```

**Kolom tabel:**
| Kolom | Tampilan |
|---|---|
| nama_petugas | Label: "Nama Petugas" |
| faskes.nama | Label: "Faskes" |
| faskes.tipe | Badge: rumah_sakit=info, puskesmas=success, apotek=warning |
| email | Label: "Email Akun" |
| status | Badge: aktif=success, nonaktif=danger |
| created_at | Label: "Dibuat" |

**Filter tabel:**
- SelectFilter `status` (aktif / nonaktif)
- SelectFilter `faskes.tipe` (rumah_sakit / puskesmas / apotek)

**Form (create & edit):**
```php
// Section: Data Akun
Select::make('faskes_id')
    ->label('Fasilitas Kesehatan')
    ->relationship('faskes', 'nama')
    ->searchable()
    ->required()

TextInput::make('nama_petugas')
    ->required()
    ->maxLength(255)

TextInput::make('email')
    ->email()
    ->required()
    ->unique(ignoreRecord: true)

TextInput::make('password')
    ->password()
    ->required(fn($context) => $context === 'create')
    ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : null)
    ->dehydrated(fn($state) => filled($state))
    ->helperText('Kosongkan jika tidak ingin mengubah password.')

// Section: Status
Select::make('status')
    ->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'])
    ->required()
    ->default('aktif')
