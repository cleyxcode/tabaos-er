# PROMPT: Bangun Admin Panel Sistem Tanggap Bencana Gempa Bumi (Laravel 13 + Filament 5)

## KONTEKS PROJECT

Ini adalah admin panel untuk aplikasi tanggap darurat bencana gempa bumi dari Fakultas Kesehatan, berlokasi operasional di Kota Ambon, Maluku. Aplikasi punya dua sisi: mobile app (Flutter, dikerjakan terpisah) dan admin panel (Laravel + Filament, yang dikerjakan sekarang). Admin panel ini yang mengelola seluruh data master, verifikasi laporan bencana, dan koordinasi penanganan (relawan, ambulans, petugas).

**Environment**: Laravel 13 dan Filament 5 sudah terinstal di project. PHP sudah terpasang. Jangan install ulang Laravel/Filament dari nol — langsung kerjakan migration, model, dan resource di project yang sudah ada.

**Fokus scope prompt ini**: HANYA admin panel (migration, model, seeder, Filament Resource, Policy, Role & Permission). TIDAK termasuk: pembuatan API untuk mobile app, tidak termasuk aplikasi Flutter.

---

## PRINSIP DESAIN DATABASE

1. Dua tabel user terpisah dengan guard berbeda:
   - `users` = khusus admin & staff internal (Super Admin, Admin Faskes, Koordinator Relawan, Petugas Penanganan, Admin Konten). Login lewat Filament (guard `web`).
   - `pengguna` = khusus warga masyarakat pengguna mobile app. TIDAK bisa login ke admin panel. Untuk sekarang cukup dibuat tabel dan model-nya saja (belum perlu auth guard/API, itu di luar scope).
2. Semua koordinat disimpan sebagai `decimal(10,7)` untuk latitude/longitude.
3. Zona Rawan Bencana disimpan sebagai polygon dalam kolom `json` berisi array titik `[{lat, lng}, ...]`.
4. Gunakan `spatie/laravel-permission` untuk role & permission (install jika belum ada).
5. Semua tabel pakai `id()` bigint auto-increment, `timestamps()`, dan foreign key dengan `constrained()->cascadeOnDelete()` kecuali disebutkan nullable/nullOnDelete di bawah.

---

## SKEMA DATABASE LENGKAP (buat migration untuk semua tabel ini, urutkan sesuai dependency FK)

### 1. `wilayah` (tabel master, dibuat paling awal)
- `id`
- `nama` (string)
- `kecamatan` (string)
- `kota` (string, default `'Kota Ambon'`)
- `timestamps`

### 2. `users` (staff/admin — modifikasi migration users bawaan Laravel, tambahkan kolom)
- kolom bawaan Laravel (`name`, `email`, `password`, dll) tetap dipakai
- tambahkan: `phone` (string, nullable)
- role di-manage lewat tabel pivot spatie (`model_has_roles`), bukan kolom `role` manual

### 3. `pengguna` (user mobile app — tabel & model saja, tanpa auth guard aktif untuk sekarang)
- `id`
- `name` (string)
- `phone` (string, unique)
- `email` (string, nullable, unique)
- `password` (string)
- `timestamps`

### 4. `laporan_bencana`
- `id`
- `pengguna_id` → FK ke `pengguna`, nullable, nullOnDelete (laporan tetap ada walau akun pelapor dihapus)
- `wilayah_id` → FK ke `wilayah`, nullable, nullOnDelete
- `verified_by` → FK ke `users`, nullable, nullOnDelete
- `nama_pelapor` (string)
- `nomor_kontak` (string)
- `jenis_kejadian` (string)
- `di_lokasi_kejadian` (boolean, default true) — sesuai form "Ya, saya di lokasi kejadian" / "Tidak di lokasi"
- `latitude` (decimal 10,7, nullable)
- `longitude` (decimal 10,7, nullable)
- `alamat_lokasi` (text, nullable)
- `tanggal_kejadian` (datetime)
- `deskripsi` (text)
- `foto` (json, nullable — array path file)
- `meninggal_jumlah` (integer, default 0)
- `meninggal_jenis_kelamin` (string, nullable)
- `penyebab_meninggal` (text, nullable)
- `hilang_jumlah` (integer, default 0)
- `hilang_jenis_kelamin` (string, nullable)
- `luka_berat_jumlah` (integer, default 0)
- `luka_berat_jenis_kelamin` (string, nullable)
- `penyebab_luka_berat` (text, nullable)
- `luka_ringan_jumlah` (integer, default 0)
- `luka_ringan_jenis_kelamin` (string, nullable)
- `penyebab_luka_ringan` (text, nullable)
- `status` (enum: `pending`, `diverifikasi`, `ditangani`, `selesai`; default `pending`)
- `verified_at` (timestamp, nullable)
- `timestamps`

### 5. `relawan`
- `id`
- `pengguna_id` → FK ke `pengguna`, unique, cascadeOnDelete
- `nik` (string, nullable)
- `alamat` (text, nullable)
- `keahlian` (string, nullable)
- `status` (enum: `pending`, `disetujui`, `ditolak`; default `pending`)
- `approved_by` → FK ke `users`, nullable, nullOnDelete
- `timestamps`

### 6. `petugas_emergency`
- `id`
- `user_id` → FK ke `users`, nullable, unique, nullOnDelete
- `nama` (string)
- `kategori` (enum: `medis`, `sar`, `logistik`, `lainnya`)
- `nomor_telepon` (string)
- `latitude` (decimal 10,7, nullable)
- `longitude` (decimal 10,7, nullable)
- `alamat` (text, nullable)
- `status` (enum: `aktif`, `nonaktif`; default `aktif`)
- `timestamps`

### 7. `faskes`
- `id`
- `wilayah_id` → FK ke `wilayah`, nullable, nullOnDelete
- `admin_id` → FK ke `users`, nullable, nullOnDelete
- `nama` (string)
- `tipe` (enum: `rumah_sakit`, `puskesmas`, `apotek`)
- `alamat` (text)
- `latitude` (decimal 10,7)
- `longitude` (decimal 10,7)
- `nomor_telepon` (string, nullable)
- `jam_operasional` (string, nullable)
- `timestamps`

### 8. `ambulans`
- `id`
- `faskes_id` → FK ke `faskes`, cascadeOnDelete
- `nama_layanan` (string)
- `nomor_telepon` (string)
- `status` (enum: `tersedia`, `tidak_tersedia`; default `tersedia`)
- `jenis_layanan` (enum: `gratis`, `berbayar`)
- `timestamps`

### 9. `zona_rawan_bencana`
- `id`
- `wilayah_id` → FK ke `wilayah`, nullable, nullOnDelete
- `created_by` → FK ke `users`, nullable, nullOnDelete
- `nama_zona` (string)
- `tingkat_risiko` (enum: `tinggi`, `sedang`, `rendah`)
- `polygon` (json — array of `{lat, lng}`)
- `deskripsi` (text, nullable)
- `timestamps`

### 10. `titik_evakuasi`
- `id`
- `zona_id` → FK ke `zona_rawan_bencana`, nullable, nullOnDelete
- `nama` (string)
- `latitude` (decimal 10,7)
- `longitude` (decimal 10,7)
- `kapasitas` (integer, nullable)
- `fasilitas` (text, nullable)
- `timestamps`

### 11. `penugasan` (tabel pivot/koordinasi — inti sistem)
- `id`
- `laporan_id` → FK ke `laporan_bencana`, cascadeOnDelete
- `relawan_id` → FK ke `relawan`, nullable, nullOnDelete
- `petugas_id` → FK ke `users`, nullable, nullOnDelete
- `ambulans_id` → FK ke `ambulans`, nullable, nullOnDelete
- `status` (enum: `ditugaskan`, `dalam_perjalanan`, `selesai`, `dibatalkan`; default `ditugaskan`)
- `catatan` (text, nullable)
- `ditugaskan_at` (timestamp, nullable)
- `selesai_at` (timestamp, nullable)
- `timestamps`

### 12. `pedoman_bhd`
- `id`
- `judul` (string)
- `tipe_file` (enum: `pdf`, `video`, `gambar`, `dokumen`)
- `deskripsi` (text)
- `file_path` (string)
- `uploaded_by` → FK ke `users`, nullable, nullOnDelete
- `timestamps`

---

## ROLE & PERMISSION (spatie/laravel-permission)

Buat seeder `RolePermissionSeeder` yang membuat role berikut beserta permission-nya:

| Role | Permission yang dimiliki |
|---|---|
| `super_admin` | semua permission (wildcard / assign semua) |
| `admin_faskes` | `faskes.view`, `faskes.create`, `faskes.update`, `faskes.delete` (dibatasi ke faskes miliknya lewat Policy), `ambulans.view`, `ambulans.create`, `ambulans.update`, `ambulans.delete` |
| `koordinator_relawan` | `laporan.view`, `penugasan.view`, `penugasan.create`, `penugasan.update`, `relawan.view`, `relawan.create`, `relawan.update`, `relawan.delete`, `zona.view` |
| `petugas_penanganan` | `laporan.view`, `penugasan.view`, `penugasan.update` (hanya penugasan miliknya), `zona.view` |
| `admin_konten` | `pedoman.view`, `pedoman.create`, `pedoman.update`, `pedoman.delete` |

Tambahkan juga permission umum: `laporan.verify`, `zona.create`, `zona.update`, `zona.delete`, `user.manage` — assign `user.manage` hanya ke `super_admin`.

Buat seeder tambahan `AdminUserSeeder` yang membuat 1 akun default:
- email: `admin@tabaos-er.test`
- password: `password` (beri komentar TODO untuk diganti di production)
- assign role `super_admin`

---

## MODEL ELOQUENT — RELASI YANG WAJIB ADA

Buat semua model berikut dengan `$fillable`, `$casts` yang sesuai (terutama untuk kolom `json`, `decimal`, `datetime`, enum), dan relasi berikut:

```php
// User (staff)
use HasRoles; // trait dari spatie
public function laporanDiverifikasi() { return $this->hasMany(LaporanBencana::class, 'verified_by'); }
public function penugasan() { return $this->hasMany(Penugasan::class, 'petugas_id'); }
public function faskesDikelola() { return $this->hasMany(Faskes::class, 'admin_id'); }
public function petugasEmergency() { return $this->hasOne(PetugasEmergency::class); }
public function relawanDisetujui() { return $this->hasMany(Relawan::class, 'approved_by'); }
public function zonaDibuat() { return $this->hasMany(ZonaRawanBencana::class, 'created_by'); }
public function pedomanDiunggah() { return $this->hasMany(PedomanBhd::class, 'uploaded_by'); }

// Pengguna
public function laporan() { return $this->hasMany(LaporanBencana::class); }
public function relawan() { return $this->hasOne(Relawan::class); }

// Wilayah
public function laporan() { return $this->hasMany(LaporanBencana::class); }
public function faskes() { return $this->hasMany(Faskes::class); }
public function zonaRawan() { return $this->hasMany(ZonaRawanBencana::class); }

// LaporanBencana
public function pengguna() { return $this->belongsTo(Pengguna::class); }
public function wilayah() { return $this->belongsTo(Wilayah::class); }
public function verifikator() { return $this->belongsTo(User::class, 'verified_by'); }
public function penugasan() { return $this->hasMany(Penugasan::class, 'laporan_id'); }

// Relawan
public function pengguna() { return $this->belongsTo(Pengguna::class); }
public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
public function penugasan() { return $this->hasMany(Penugasan::class); }

// PetugasEmergency
public function user() { return $this->belongsTo(User::class); }

// Faskes
public function wilayah() { return $this->belongsTo(Wilayah::class); }
public function admin() { return $this->belongsTo(User::class, 'admin_id'); }
public function ambulans() { return $this->hasMany(Ambulans::class); }

// Ambulans
public function faskes() { return $this->belongsTo(Faskes::class); }
public function penugasan() { return $this->hasMany(Penugasan::class); }

// ZonaRawanBencana
public function wilayah() { return $this->belongsTo(Wilayah::class); }
public function pembuat() { return $this->belongsTo(User::class, 'created_by'); }
public function titikEvakuasi() { return $this->hasMany(TitikEvakuasi::class, 'zona_id'); }

// TitikEvakuasi
public function zona() { return $this->belongsTo(ZonaRawanBencana::class, 'zona_id'); }

// Penugasan
public function laporan() { return $this->belongsTo(LaporanBencana::class); }
public function relawan() { return $this->belongsTo(Relawan::class); }
public function petugas() { return $this->belongsTo(User::class, 'petugas_id'); }
public function ambulans() { return $this->belongsTo(Ambulans::class); }

// PedomanBhd
public function pengunggah() { return $this->belongsTo(User::class, 'uploaded_by'); }
```

---

## PACKAGE TAMBAHAN YANG PERLU DIINSTALL

1. `spatie/laravel-permission` — role & permission
2. `webbingbrasil/filament-map-picker` — untuk input koordinat (latitude/longitude) via peta interaktif di form Filament, dan untuk menggambar polygon pada form Zona Rawan Bencana

---

## FILAMENT RESOURCE — BUAT UNTUK SETIAP MODEL

Buat Filament Resource untuk semua model di atas KECUALI `Pengguna` (cukup buat Resource read-only/monitoring tanpa aksi create/edit/delete, karena akun warga hanya dikelola dari sisi mobile app).

Ketentuan umum tiap Resource:
- Kelompokkan navigasi (`navigationGroup`) menjadi: **Penanganan Bencana** (Laporan, Penugasan), **Direktori** (Faskes, Ambulans, Petugas Emergency, Relawan), **Pemetaan** (Zona Rawan Bencana, Titik Evakuasi, Wilayah), **Konten** (Pedoman BHD), **Pengaturan** (User, Role — hanya untuk super_admin)
- Field lat/long WAJIB pakai `Map::make()` dari `filament-map-picker`, default center koordinat Ambon (`-3.6954, 128.1814`)
- Field polygon pada Zona Rawan Bencana pakai mode draw polygon dari package yang sama, simpan sebagai json
- Field `status` pakai `Select` dengan opsi sesuai enum, tampilkan sebagai `Badge` berwarna di tabel (misal: pending = warning, diverifikasi = info, ditangani = primary, selesai = success)
- Field `foto` pada Laporan Bencana pakai `FileUpload` multiple, disimpan ke disk `public`
- Tambahkan filter (`Tables\Filters\SelectFilter`) untuk kolom `status`, `tipe`, `kategori`, `tingkat_risiko` pada masing-masing resource yang relevan
- Tambahkan global search pada kolom nama/judul yang relevan tiap resource

Ketentuan khusus:

**LaporanBencanaResource**
- Buat Relation Manager `PenugasanRelationManager` di halaman detail/edit, supaya admin bisa langsung assign relawan/petugas/ambulans dari laporan tersebut
- Tambahkan action kustom "Verifikasi Laporan" yang mengubah `status` jadi `diverifikasi`, mengisi `verified_by` = user login saat ini, dan `verified_at` = now()
- Form dibagi jadi Section: Info Pelapor, Lokasi, Waktu & Deskripsi, Data Korban (grouped per kategori: meninggal, hilang, luka berat, luka ringan)

**PenugasanResource**
- Form: Select `laporan_id` (searchable, relationship), Select `relawan_id` dan `petugas_id` (searchable), Select `ambulans_id` (filter hanya ambulans dengan status tersedia)
- Saat status diubah ke `selesai`, otomatis isi `selesai_at` = now() (pakai mutator atau `Filament\Forms\Set` di form)

**FaskesResource**
- Relation Manager `AmbulansRelationManager` di halaman detail, supaya bisa lihat/tambah ambulans langsung dari faskes terkait

**ZonaRawanBencanaResource**
- Relation Manager `TitikEvakuasiRelationManager`

**RelawanResource**
- Action kustom "Setujui" dan "Tolak" yang mengubah `status` dan mengisi `approved_by`

---

## POLICY — BUAT SESUAI MATRIX PERMISSION

Buat Policy untuk `LaporanBencana`, `Faskes`, `Ambulans`, `Relawan`, `ZonaRawanBencana`, `PedomanBhd`, `User` yang mengecek permission via `$user->can('nama.permission')`. Terapkan pengecualian berikut di Policy `Faskes` dan `Ambulans`:

```php
// FaskesPolicy
public function update(User $user, Faskes $faskes): bool
{
    if ($user->hasRole('super_admin')) return true;
    if ($user->hasRole('admin_faskes')) return $faskes->admin_id === $user->id;
    return false;
}
```

Terapkan pola serupa untuk `Penugasan`/`petugas_penanganan` — petugas hanya boleh update penugasan yang `petugas_id`-nya dirinya sendiri.

Daftarkan seluruh Policy di `AuthServiceProvider` (atau `bootstrap/app.php` jika project pakai struktur Laravel 13 baru tanpa provider array manual — sesuaikan dengan struktur project yang sudah ada).

---

## DELIVERABLES / CHECKLIST EKSEKUSI

Kerjakan berurutan, dan pastikan tiap langkah `php artisan migrate:fresh --seed` berhasil tanpa error sebelum lanjut ke langkah berikutnya:

1. Install `spatie/laravel-permission` dan `webbingbrasil/filament-map-picker`, publish config & migration spatie
2. Buat seluruh migration sesuai skema di atas, urutkan sesuai dependency FK (wilayah → users/pengguna → sisanya)
3. Buat seluruh Model dengan relasi, `$fillable`, `$casts`
4. Buat `RolePermissionSeeder` dan `AdminUserSeeder`, daftarkan di `DatabaseSeeder`
5. Buat seluruh Filament Resource sesuai ketentuan di atas, termasuk Relation Manager
6. Buat seluruh Policy dan daftarkan
7. Jalankan `php artisan migrate:fresh --seed` dan pastikan admin panel bisa diakses, login dengan akun default, dan semua Resource muncul di navigasi sesuai grup masing-masing tanpa error

Jangan buat file API, controller REST, atau kode yang berhubungan dengan Flutter — di luar scope prompt ini.
