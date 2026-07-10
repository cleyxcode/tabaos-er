# Tabaos — Flutter Integration Prompt

> **Gunakan dokumen ini sebagai prompt lengkap ke AI/Cursor/Copilot di project Flutter.**
> Salin seluruh isi file ini lalu paste sebagai konteks awal ke sesi baru.

---

## 1. Gambaran Umum Sistem

Aplikasi **Tabaos** adalah sistem tanggap darurat gempa bumi untuk Kota Ambon, Maluku.
Backend: **Laravel 13 + Sanctum**, Base URL: `https://<domain>/api/v1`

Terdapat **3 tipe pengguna** yang masing-masing punya token Sanctum terpisah:

| Role | Login endpoint | Guard |
|---|---|---|
| `masyarakat` | `POST /auth/login` | `pengguna` |
| `relawan` | `POST /relawan-auth/login` | `akun_relawan` |
| `faskes` | `POST /faskes-auth/login` | `akun_faskes` |

Setelah login, simpan token dan **role string** ke `SharedPreferences` / `SecureStorage`.  
Saat app dibuka kembali, baca role → arahkan ke home masing-masing tanpa login ulang.

---

## 2. Arsitektur Flutter yang Direkomendasikan

```
lib/
├── core/
│   ├── api/
│   │   ├── api_client.dart          # Dio instance + interceptor token
│   │   ├── endpoints.dart           # Semua string URL
│   │   └── api_response.dart        # Generic wrapper
│   ├── auth/
│   │   ├── auth_repository.dart     # Login/logout semua role
│   │   ├── auth_provider.dart       # Riverpod/Provider/Bloc
│   │   └── user_session.dart        # Model sesi + role enum
│   └── services/
│       ├── storage_service.dart     # SharedPreferences wrapper
│       └── location_service.dart    # Geolocator wrapper
│
├── features/
│   ├── splash/
│   │   └── splash_screen.dart       # Cek token → route sesuai role
│   │
│   ├── login/
│   │   └── login_screen.dart        # Satu screen, 3 tab role
│   │
│   ├── masyarakat/                  # Role: masyarakat
│   │   ├── home/
│   │   ├── laporan/
│   │   └── profil/
│   │
│   ├── relawan/                     # Role: relawan
│   │   ├── home/
│   │   ├── laporan/
│   │   ├── peta/
│   │   └── notifikasi/
│   │
│   └── faskes/                      # Role: faskes
│       ├── home/
│       ├── laporan/
│       ├── peta/
│       └── profil/
│
└── main.dart
```

---

## 3. Routing Berdasarkan Role

```dart
// Di splash_screen.dart — logika routing
Future<void> _checkSession() async {
  final token = await storage.read('token');
  final role  = await storage.read('role'); // 'masyarakat' | 'relawan' | 'faskes'

  if (token == null) {
    router.go('/login');
    return;
  }

  switch (role) {
    case 'masyarakat': router.go('/masyarakat/home'); break;
    case 'relawan':    router.go('/relawan/home');    break;
    case 'faskes':     router.go('/faskes/home');     break;
    default:           router.go('/login');
  }
}
```

---

## 4. API Client (Dio)

```dart
// core/api/api_client.dart
import 'package:dio/dio.dart';

class ApiClient {
  static const _baseUrl = 'https://<domain>/api/v1';
  late final Dio _dio;

  ApiClient(StorageService storage) {
    _dio = Dio(BaseOptions(
      baseUrl: _baseUrl,
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 15),
      headers: {'Accept': 'application/json'},
    ));

    // Request interceptor — inject token
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await storage.read('token');
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
      onError: (DioException e, handler) async {
        if (e.response?.statusCode == 401) {
          await storage.deleteAll();
          // navigasi ke login
        }
        handler.next(e);
      },
    ));
  }

  Dio get dio => _dio;
}
```

---

## 5. Semua Endpoint — Referensi Lengkap

### 5A. Masyarakat (guard: `pengguna`)

#### Auth

```
POST   /auth/register
Body : { name, phone, email, password, password_confirmation }
Resp : { success, data: { pengguna, token } }

POST   /auth/login
Body : { email|phone, password }
Resp : { success, data: { pengguna, token } }

POST   /auth/logout          [Auth: Bearer token masyarakat]
GET    /auth/me              [Auth]
PUT    /auth/me              [Auth] Body: { name?, email? }

POST   /auth/forgot-password
Body : { email }
Resp : { success, message }

POST   /auth/reset-password
Body : { email, otp, password, password_confirmation }
```

#### Data Publik (tanpa login)

```
GET    /petugas-emergency
GET    /faskes              ?wilayah_id=&tipe=
GET    /faskes/{id}
GET    /ambulans
GET    /pedoman-bhd         ?page=
GET    /pedoman-bhd/{id}
GET    /zona-rawan          ?page=
GET    /zona-rawan/{id}
GET    /titik-evakuasi
```

#### Laporan Bencana (Auth: masyarakat)

```
GET    /laporan             ?page=
Resp : { success, data: [...], meta: { current_page, last_page, total } }

GET    /laporan/{id}
Resp : { success, data: { id, nama_pelapor, jenis_kejadian, status,
                          status_penanganan, latitude, longitude,
                          alamat_lokasi, tanggal_kejadian, deskripsi,
                          foto:[], meninggal_jumlah, luka_berat_jumlah,
                          luka_ringan_jumlah, hilang_jumlah,
                          wilayah: { id, nama } } }

POST   /laporan             multipart/form-data
Body : {
  nama_pelapor, nomor_kontak, jenis_kejadian,
  di_lokasi_kejadian (bool),
  latitude?, longitude?, alamat_lokasi?,
  wilayah_id?,
  tanggal_kejadian (ISO 8601),
  deskripsi,
  foto[] (file, optional, max 10)
}
```

#### Daftar / Status Relawan (Auth: masyarakat)

```
POST   /relawan
Body : { nik, alamat, keahlian, organisasi? }

GET    /relawan/status
Resp : { status: 'pending'|'disetujui'|'ditolak', ... }
```

---

### 5B. Relawan (guard: `akun_relawan`)

#### Auth Relawan

```
POST   /relawan-auth/login
Body : { email, password }
Resp : {
  success, token,
  akun_relawan: {
    id, email, status,
    relawan: { id, nama, keahlian, organisasi }
  }
}
Simpan: token → 'token', role → 'relawan', id → 'user_id'

POST   /relawan-auth/logout   [Auth]
GET    /relawan-auth/me       [Auth]
Resp  : { success, akun_relawan: { ...+ relawan.pengguna } }
```

#### Operasional Relawan (Auth: akun_relawan + status aktif)

```
PUT    /relawan/lokasi
Body : { latitude, longitude }
Resp : { success, message, updated_at }
⚡ Panggil setiap 60 detik selama app aktif di foreground

POST   /relawan/fcm-token
Body : { fcm_token }
⚡ Panggil sekali setelah login berhasil

GET    /relawan/laporan-terdekat
Query: lat, lng, radius? (default 10), page? (default 1)
Resp : {
  success,
  data: [{
    id, jenis_kejadian, deskripsi, status, status_penanganan,
    latitude, longitude, alamat_lokasi, tanggal_kejadian, created_at,
    korban: { meninggal_jumlah, luka_berat_jumlah, luka_ringan_jumlah, hilang_jumlah },
    jarak_km,
    relawan_ditugaskan: { id, nama } | null
  }],
  meta: { current_page, last_page, total }
}

GET    /relawan/laporan/{id}
Resp : {
  success,
  laporan: { ...semua field + pengguna, wilayah, relawanDitugaskan },
  relawan_terdekat: [{ id, nama, latitude, longitude, jarak_km }]
}

POST   /relawan/laporan/{id}/claim
Resp : { success, message, laporan }
Error 409: laporan sudah diklaim relawan lain

PUT    /relawan/laporan/{id}/selesai
Resp : { success, message, laporan }
Error 403: bukan relawan yang mengklaim

GET    /relawan/peta
Query: lat, lng, radius? (default 20)
Resp : {
  success,
  laporan: [{ id, latitude, longitude, jenis_kejadian, status_penanganan }],
  relawan_aktif: [{ id, nama, latitude, longitude, lokasi_updated_at }]
}

GET    /relawan/notifikasi
Query: page?
Resp : {
  success,
  data: [{ id, laporan: { id, jenis_kejadian, alamat_lokasi }, sudah_dibaca, created_at }],
  unread_count: int,
  meta: { current_page, last_page, total }
}

PUT    /relawan/notifikasi/{id}/baca
Resp : { success, message }
```

---

### 5C. Faskes (guard: `akun_faskes`)

#### Auth Faskes

```
POST   /faskes-auth/login
Body : { email, password }
Resp : {
  success, token,
  akun_faskes: {
    id, nama_petugas, email,
    faskes: { id, nama, tipe, alamat, latitude, longitude }
  }
}
Simpan: token → 'token', role → 'faskes', id → 'user_id'

POST   /faskes-auth/logout   [Auth]
GET    /faskes-auth/me       [Auth]
Resp  : { success, akun_faskes: { ...+ faskes.ambulans[] } }
```

#### Operasional Faskes (Auth: akun_faskes + status aktif)

```
POST   /faskes/fcm-token
Body : { fcm_token }

GET    /faskes/laporan
Query: lat?, lng?, radius? (default 15), status? (filter), page?
Resp : {
  success,
  data: [{
    id, jenis_kejadian, deskripsi, status, status_penanganan,
    latitude, longitude, alamat_lokasi, tanggal_kejadian,
    korban: { meninggal_jumlah, luka_berat_jumlah, luka_ringan_jumlah, hilang_jumlah },
    jarak_km
  }],
  meta: { current_page, last_page, total }
}

GET    /faskes/laporan/{id}
Resp : { success, laporan: { ...+ pengguna, wilayah, relawanDitugaskan, penugasan[] } }

GET    /faskes/peta
Query: radius? (default 15)
Resp : {
  success,
  faskes_saya: { id, nama, latitude, longitude },
  laporan: [{ id, latitude, longitude, jenis_kejadian, status, status_penanganan }],
  relawan_aktif: [{ id, nama, latitude, longitude }]
}

GET    /faskes/profil
Resp : { success, profil: { ...faskes + ambulans[] } }

GET    /faskes/notifikasi
Query: page?
Resp : {
  success,
  data: [...laporan terdekat dari faskes],
  meta: { current_page, last_page, total }
}
```

---

## 6. Model Dart — Semua Role

```dart
// ─── Session / Auth ───────────────────────────────────────────────────────────

enum UserRole { masyarakat, relawan, faskes }

class UserSession {
  final String token;
  final UserRole role;
  final int userId;
  final String nama;

  UserSession({required this.token, required this.role,
               required this.userId, required this.nama});

  factory UserSession.fromLoginResponse(Map<String, dynamic> json, UserRole role) {
    switch (role) {
      case UserRole.masyarakat:
        return UserSession(
          token:  json['data']['token'],
          role:   role,
          userId: json['data']['pengguna']['id'],
          nama:   json['data']['pengguna']['name'],
        );
      case UserRole.relawan:
        return UserSession(
          token:  json['token'],
          role:   role,
          userId: json['akun_relawan']['id'],
          nama:   json['akun_relawan']['relawan']?['nama'] ?? '',
        );
      case UserRole.faskes:
        return UserSession(
          token:  json['token'],
          role:   role,
          userId: json['akun_faskes']['id'],
          nama:   json['akun_faskes']['nama_petugas'],
        );
    }
  }
}

// ─── Laporan Bencana ─────────────────────────────────────────────────────────

class LaporanBencana {
  final int id;
  final String jenis_kejadian;
  final String deskripsi;
  final String status;               // pending | diverifikasi | ditangani | selesai
  final String status_penanganan;    // belum_ditangani | sedang_ditangani | selesai_ditangani
  final double? latitude;
  final double? longitude;
  final String? alamat_lokasi;
  final DateTime tanggal_kejadian;
  final double? jarak_km;
  final KorbanInfo korban;
  final RelawanInfo? relawan_ditugaskan;

  // factory fromJson ...
}

class KorbanInfo {
  final int meninggal_jumlah;
  final int luka_berat_jumlah;
  final int luka_ringan_jumlah;
  final int hilang_jumlah;
}

class RelawanInfo {
  final int id;
  final String nama;
}

// ─── Notifikasi Relawan ──────────────────────────────────────────────────────

class RelawanNotifikasi {
  final int id;
  final LaporanSingkat laporan;
  final bool sudah_dibaca;
  final DateTime created_at;
}

class LaporanSingkat {
  final int id;
  final String jenis_kejadian;
  final String? alamat_lokasi;
}
```

---

## 7. Auth Repository

```dart
class AuthRepository {
  final ApiClient _client;
  final StorageService _storage;

  // ── Masyarakat ──────────────────────────────────────────────────────────────
  Future<UserSession> loginMasyarakat(String emailOrPhone, String password) async {
    final resp = await _client.dio.post('/auth/login', data: {
      'email': emailOrPhone,
      'password': password,
    });
    final session = UserSession.fromLoginResponse(resp.data, UserRole.masyarakat);
    await _saveSession(session);
    return session;
  }

  // ── Relawan ─────────────────────────────────────────────────────────────────
  Future<UserSession> loginRelawan(String email, String password) async {
    final resp = await _client.dio.post('/relawan-auth/login', data: {
      'email': email, 'password': password,
    });
    final session = UserSession.fromLoginResponse(resp.data, UserRole.relawan);
    await _saveSession(session);
    return session;
  }

  // ── Faskes ──────────────────────────────────────────────────────────────────
  Future<UserSession> loginFaskes(String email, String password) async {
    final resp = await _client.dio.post('/faskes-auth/login', data: {
      'email': email, 'password': password,
    });
    final session = UserSession.fromLoginResponse(resp.data, UserRole.faskes);
    await _saveSession(session);
    return session;
  }

  Future<void> logout(UserRole role) async {
    final endpoints = {
      UserRole.masyarakat: '/auth/logout',
      UserRole.relawan:    '/relawan-auth/logout',
      UserRole.faskes:     '/faskes-auth/logout',
    };
    try {
      await _client.dio.post(endpoints[role]!);
    } finally {
      await _storage.deleteAll();
    }
  }

  Future<void> _saveSession(UserSession session) async {
    await _storage.write('token', session.token);
    await _storage.write('role', session.role.name);   // 'masyarakat'|'relawan'|'faskes'
    await _storage.write('user_id', session.userId.toString());
    await _storage.write('nama', session.nama);
  }
}
```

---

## 8. Fitur Spesifik Per Role

### 8A. Masyarakat — Halaman Utama

```
BottomNavigationBar:
  1. Beranda          → info kontak darurat, tombol SOS
  2. Lapor            → form laporan bencana (POST /laporan)
  3. Riwayat Laporan  → list (GET /laporan) + detail (GET /laporan/{id})
  4. Info             → faskes, ambulans, pedoman BHD, zona rawan
  5. Profil           → GET /auth/me + edit profil
```

### 8B. Relawan — Halaman Utama

```
BottomNavigationBar:
  1. Beranda          → statistik: laporan pending di sekitar, laporan diklaim
  2. Laporan Terdekat → GET /relawan/laporan-terdekat (list + pull to refresh)
                        → tap → detail → tombol Klaim / Selesaikan
  3. Peta             → GET /relawan/peta
                        → FlutterMap / google_maps_flutter
                        → marker merah = laporan, marker biru = relawan lain
  4. Notifikasi       → GET /relawan/notifikasi (badge unread_count)
  5. Profil           → GET /relawan-auth/me

Background Tasks:
  - Timer 60 detik → PUT /relawan/lokasi (kirim lat/lng saat aktif)
  - FCM token update → POST /relawan/fcm-token saat login
  - FCM push notification type='laporan_baru' → navigasi ke detail laporan
```

### 8C. Faskes — Halaman Utama

```
BottomNavigationBar:
  1. Beranda          → profil faskes, laporan terbaru di sekitar
  2. Laporan          → GET /faskes/laporan (filter by status, radius)
                        → tap → GET /faskes/laporan/{id}
  3. Peta             → GET /faskes/peta (laporan + relawan aktif di sekitar)
  4. Notifikasi       → GET /faskes/notifikasi
  5. Profil           → GET /faskes/profil + list ambulans
```

---

## 9. Penanganan Error Standar

```dart
// Semua response error mengikuti format:
// { "success": false, "message": "..." }
// atau
// { "success": false, "message": "...", "errors": { "field": ["msg"] } }

class ApiException implements Exception {
  final int statusCode;
  final String message;
  final Map<String, List<String>>? errors;

  static ApiException fromDioError(DioException e) {
    final data = e.response?.data;
    return ApiException(
      statusCode: e.response?.statusCode ?? 0,
      message:    data?['message'] ?? 'Terjadi kesalahan',
      errors:     (data?['errors'] as Map?)?.map(
        (k, v) => MapEntry(k, List<String>.from(v)),
      ),
    );
  }
}

// HTTP status yang perlu dihandle:
// 401 → token expired/invalid → hapus sesi → redirect login
// 403 → akun nonaktif atau tidak berwenang → tampilkan pesan
// 409 → conflict (laporan sudah diklaim) → tampilkan pesan
// 422 → validasi gagal → tampilkan field errors
// 429 → rate limited → tampilkan "Terlalu banyak percobaan, coba lagi"
```

---

## 10. FCM Push Notification (Relawan)

Payload yang dikirim server saat laporan baru masuk:
```json
{
  "notification": {
    "title": "Laporan Bencana Baru",
    "body":  "Ada laporan Gempa Bumi di dekat lokasi kamu."
  },
  "data": {
    "laporan_id": "42",
    "type":       "laporan_baru"
  }
}
```

Handle di Flutter:
```dart
FirebaseMessaging.onMessage.listen((message) {
  final type = message.data['type'];
  if (type == 'laporan_baru') {
    final id = int.parse(message.data['laporan_id']!);
    // tampilkan local notification
    // update badge notifikasi
  }
});

FirebaseMessaging.onMessageOpenedApp.listen((message) {
  final id = int.parse(message.data['laporan_id']!);
  router.go('/relawan/laporan/$id');
});
```

---

## 11. Location Service (Relawan)

```dart
// Kirim lokasi ke server setiap 60 detik saat app aktif
class LocationService {
  Timer? _timer;

  void startTracking(ApiClient client) {
    _timer = Timer.periodic(const Duration(seconds: 60), (_) async {
      final pos = await Geolocator.getCurrentPosition();
      await client.dio.put('/relawan/lokasi', data: {
        'latitude':  pos.latitude,
        'longitude': pos.longitude,
      });
    });
  }

  void stopTracking() => _timer?.cancel();
}
```

---

## 12. Peta (Flutter Map / Google Maps)

Untuk halaman peta relawan & faskes, gunakan data dari endpoint `/relawan/peta` atau `/faskes/peta`:

```dart
// Marker laporan
for (final laporan in data.laporan) {
  markers.add(Marker(
    point: LatLng(laporan.latitude, laporan.longitude),
    child: Icon(
      Icons.warning,
      color: laporan.status_penanganan == 'belum_ditangani'
          ? Colors.red
          : Colors.orange,
    ),
  ));
}

// Marker relawan aktif
for (final relawan in data.relawan_aktif) {
  markers.add(Marker(
    point: LatLng(relawan.latitude, relawan.longitude),
    child: const Icon(Icons.person_pin, color: Colors.blue),
  ));
}
```

---

## 13. Checklist Implementasi Flutter

### Setup Awal
- [ ] Tambah dependency: `dio`, `flutter_secure_storage`, `geolocator`, `firebase_messaging`, `flutter_map` / `google_maps_flutter`, `riverpod` / `bloc`
- [ ] Buat `ApiClient` dengan Dio + interceptor token
- [ ] Buat `StorageService` wrapper untuk token/role
- [ ] Buat `UserSession` model + `UserRole` enum

### Auth & Routing
- [ ] `SplashScreen` — cek token → route sesuai role
- [ ] `LoginScreen` — 3 tab (Masyarakat / Relawan / Faskes)
- [ ] `AuthRepository` dengan `loginMasyarakat`, `loginRelawan`, `loginFaskes`, `logout`
- [ ] Go Router / auto_route dengan guard berdasarkan role

### Masyarakat
- [ ] Home + SOS button
- [ ] Form laporan bencana (multipart dengan upload foto)
- [ ] Riwayat laporan + detail + badge status
- [ ] Halaman info (faskes, pedoman BHD, zona rawan)
- [ ] Edit profil

### Relawan
- [ ] Home dengan ringkasan laporan
- [ ] List laporan terdekat + pull to refresh + infinite scroll
- [ ] Detail laporan + tombol Klaim / Selesaikan
- [ ] Peta interaktif (laporan + relawan lain)
- [ ] Notifikasi dengan badge unread
- [ ] Background location update setiap 60 detik
- [ ] FCM token update setelah login
- [ ] Handle FCM push notification → navigasi ke detail laporan

### Faskes
- [ ] Home dengan info faskes
- [ ] List laporan + filter status + radius
- [ ] Detail laporan lengkap
- [ ] Peta area faskes
- [ ] Halaman profil faskes + ambulans
- [ ] Notifikasi laporan di sekitar

---

## 14. Catatan Penting

1. **Token per guard** — token masyarakat TIDAK bisa dipakai di endpoint relawan atau faskes. Pastikan header `Authorization: Bearer <token>` menggunakan token dari login role yang sesuai.

2. **Status akun** — jika login berhasil tapi akun `nonaktif`, server mengembalikan `403`. Tampilkan pesan "Akun tidak aktif, hubungi administrator" dan jangan simpan token.

3. **Radius default** — laporan terdekat relawan default 10 km, faskes 15 km, peta relawan 20 km. Semua bisa di-override via query param `radius`.

4. **Claim laporan** — satu laporan hanya bisa diklaim satu relawan. Jika sudah diklaim relawan lain, server kembalikan `409 Conflict`.

5. **Lokasi relawan dianggap aktif** — server hanya menampilkan relawan sebagai "aktif" jika `lokasi_updated_at` ≤ 30 menit yang lalu. Pastikan timer 60 detik berjalan.

6. **Format tanggal** — semua datetime dari server dalam format ISO 8601 UTC. Gunakan `DateTime.parse()` dan tampilkan dengan `intl` package sesuai zona waktu lokal.

7. **status vs status_penanganan** — `status` adalah status verifikasi laporan (pending/diverifikasi/ditangani/selesai), `status_penanganan` adalah status penanganan laporan oleh relawan (belum_ditangani/sedang_ditangani/selesai_ditangani). Keduanya perlu ditampilkan.
