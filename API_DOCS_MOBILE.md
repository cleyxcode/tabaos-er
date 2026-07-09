# Dokumentasi API Mobile (Tabaos Disaster App)

Dokumen ini berisi panduan teknis bagi developer aplikasi mobile (Flutter/React Native) untuk melakukan integrasi dengan backend server Tabaos.

## Informasi Dasar
- **Base URL:** `http://your-domain.com/api/v1` (Ubah dengan domain production/staging)
- **Format Response:** JSON (selalu mengembalikan standard JSON)
- **Otentikasi:** Bearer Token (Laravel Sanctum)

---

## Standar Response & Error Handling

Semua endpoint selalu memulangkan struktur data JSON seperti berikut:

### 1. Success Response (200, 201)
```json
{
    "success": true,
    "message": "Pesan sukses operasi.",
    "data": { ... } // atau array [...]
}
```

### 2. Validation Error Response (422)
Jika ada field yang kurang atau salah format saat metode `POST` / `PUT`:
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "phone": ["The phone field is required."],
        "password": ["The password must be at least 6 characters."]
    }
}
```

### 3. Client/Server Error Response (401, 403, 404, 500)
- `401 Unauthorized` (Token salah/expired)
- `404 Not Found` (Data tidak ditemukan)
- `500 Server Error`
```json
{
    "success": false,
    "message": "Unauthenticated." // atau pesan error lainnya
}
```

---

## A. Autentikasi Pengguna
Endpoint ini digunakan untuk alur registrasi, login, dan profile pengguna warga. 
> Semua request `POST` / `PUT` membutuhkan header `Accept: application/json` dan `Content-Type: application/json`.

### 1. Registrasi
- **Endpoint:** `POST /auth/register`
- **Body Request:**
```json
{
    "name": "John Doe",
    "phone": "081234567890",
    "password": "password123",
    "password_confirmation": "password123"
}
```
- **Response (201 Created):**
```json
{
    "success": true,
    "message": "Registrasi berhasil.",
    "data": {
        "pengguna": {
            "id": 1,
            "name": "John Doe",
            "phone": "081234567890",
            "email": null,
            "created_at": "2026-07-09T06:35:09.000000Z"
        },
        "token": "1|abcdef12345..."
    }
}
```

### 2. Login
- **Endpoint:** `POST /auth/login`
- **Body Request:** (Bisa login menggunakan `phone` atau `email`)
```json
{
    "login": "081234567890",
    "password": "password123"
}
```
- **Response (200 OK):** (Mengembalikan `data.token` untuk dipakai di endpoint terautentikasi)

### 3. Profil Saya (Me)
- **Endpoint:** `GET /auth/me`
- **Headers:** `Authorization: Bearer {token}`
- **Response:** Mengembalikan object user berjalan.

### 4. Update Profil
- **Endpoint:** `PUT /auth/me`
- **Headers:** `Authorization: Bearer {token}`
- **Body:** `name`, `phone`, `email` (semua opsional). Jika merubah password sertakan `password`, `password_confirmation`.

### 5. Logout
- **Endpoint:** `POST /auth/logout`
- **Headers:** `Authorization: Bearer {token}`

---

## B. Endpoint Publik (Tanpa Login)

### 1. Daftar Petugas Emergency
- **Endpoint:** `GET /petugas-emergency`
- **Response:**
```json
{
    "success": true,
    "message": "Data petugas emergency berhasil diambil.",
    "data": {
        "nomor_darurat": [
            { "nama": "Nomor Darurat Nasional", "nomor": "112", "kategori": "darurat" },
            { "nama": "Ambulans / Medis Darurat", "nomor": "119", "kategori": "medis" }
            // ... list hardcode nomor nasional
        ],
        "petugas": [
            // List data petugas daerah (polisi setempat, rs setempat)
        ]
    }
}
```

### 2. Direktori Faskes (Fasilitas Kesehatan)
- **Endpoint:** `GET /faskes?lat={lat}&lng={lng}`
*(Query `lat` dan `lng` bersifat opsional. Jika dikirim, API akan mengurutkan faskes dari jarak terdekat dan menambahkan atribut `jarak_km` pada setiap object faskes)*.
- **Response Sample:**
```json
{
    "success": true,
    "message": "Data faskes berhasil diambil.",
    "data": [
        {
            "id": 1,
            "nama": "RSUD Dr. M. Haulussy",
            "kategori": "Rumah Sakit",
            "alamat": "Jl. Dr. Kayadoe",
            "no_telepon": "0911-344871",
            "kapasitas": 500,
            "location": {
                "lat": -3.704257,
                "lng": 128.172901
            },
            "jarak_km": 1.2
        }
    ]
}
```

### 3. Ambulans Aktif
- **Endpoint:** `GET /ambulans`
- **Deskripsi:** Menampilkan ambulans yang `status` nya `tersedia`.

### 4. Titik Evakuasi
- **Endpoint:** `GET /titik-evakuasi`
- **Deskripsi:** Daftar lokasi aman dengan mapping `location: {lat, lng}` beserta relasinya ke Zona Rawan.

### 5. Zona Rawan Bencana (Pemetaan Poligon)
- **Endpoint:** `GET /zona-rawan`
- **Response Sample:**
```json
{
    "success": true,
    "message": "Data zona rawan bencana berhasil diambil.",
    "data": [
        {
            "id": 1,
            "nama_zona": "Pesisir Pantai Natsepa",
            "tingkat_risiko": "tinggi",
            "deskripsi": "Rawan Tsunami",
            "polygon": [
                { "lat": -3.619, "lng": 128.324 },
                { "lat": -3.621, "lng": 128.330 },
                { "lat": -3.615, "lng": 128.332 }
            ]
        }
    ]
}
```

### 6. Pedoman BHD (Bantuan Hidup Dasar)
- **Endpoint:** `GET /pedoman-bhd`
- **Deskripsi:** Artikel edukasi tentang P3K dan cara mitigasi. Foto di-return dalam bentuk URL lengkap.

---

## C. Endpoint Terproteksi (Wajib Token)

Semua endpoint di bawah ini WAJIB menyertakan:
`Authorization: Bearer {token}` di HTTP Header.

### 1. Buat Laporan Bencana
- **Endpoint:** `POST /laporan`
- **Headers:** `Content-Type: multipart/form-data`
- **Body Form-Data:**
  - `jenis_bencana`: string (wajib, max 50 char)
  - `deskripsi`: string (wajib)
  - `lat`: numeric (wajib)
  - `lng`: numeric (wajib)
  - `foto[]`: file (opsional, bisa multiple max 3 file, tipe image)
  - `korban_meninggal`, `korban_luka_berat`, `korban_luka_ringan`, `korban_hilang`: integer (opsional, default 0)

- **Response:**
```json
{
    "success": true,
    "message": "Laporan bencana berhasil dibuat.",
    "data": {
        "id": 10,
        "pelapor": { "id": 1, "name": "John Doe" },
        "jenis_bencana": "Banjir Bandang",
        "deskripsi": "Banjir setinggi 1 meter...",
        "status": "menunggu",
        "location": {
            "lat": -3.7001,
            "lng": 128.1690
        },
        "korban": {
            "meninggal": 0,
            "luka_berat": 2,
            "luka_ringan": 5,
            "hilang": 1
        },
        "foto": [
            "http://your-domain.com/storage/laporan/foto1.jpg"
        ],
        "created_at": "2026-07-09T08:00:00.000000Z"
    }
}
```

### 2. Riwayat Laporan Saya
- **Endpoint:** `GET /laporan`
- **Deskripsi:** Akan mengembalikan list laporan yang dibuat oleh akun pengguna yang sedang login saat ini (termasuk status validasi BPBD).

### 3. Daftar Jadi Relawan
- **Endpoint:** `POST /relawan`
- **Body:**
```json
{
    "keahlian": "Medis / Evakuasi / Dapur Umum",
    "organisasi": "PMI Ambon (Opsional)"
}
```
- **Catatan:** Jika akun pengguna tersebut sudah mendaftar (berstatus menunggu atau aktif), API akan menolak (*HTTP 422 Validasi: Anda sudah terdaftar*).

### 4. Cek Status Relawan
- **Endpoint:** `GET /relawan/status`
- **Response:**
```json
{
    "success": true,
    "message": "Status relawan berhasil diambil.",
    "data": {
        "is_registered": true,
        "status": "aktif",
        "keahlian": "Medis",
        "organisasi": "PMI Ambon"
    }
}
```
*(Bisa digunakan di aplikasi untuk merubah tombol "Daftar Relawan" menjadi label "Status: Aktif").*

---
**Tips Untuk Developer Mobile:**
1. Untuk endpoint yang memerlukan File Upload (`POST /laporan`), pastikan menggunakan implementasi `MultipartRequest` pada dio/http di Flutter.
2. Jika server me-return Error 401 Unauthorized secara mendadak, tandanya *Token Expired* atau di-revoke oleh server, segera redirect user ke halaman Login/Landing.
