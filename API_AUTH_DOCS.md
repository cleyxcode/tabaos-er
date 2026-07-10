# Dokumentasi API Authentication Tabaos

Base URL: `http://localhost:8000/api/v1/auth` (sesuaikan dengan environment server/domain Anda)

Semua endpoint menghasilkan response JSON dengan format standar:
```json
{
  "success": true/false,
  "message": "Pesan response",
  "data": { ... } // (Opsional)
}
```

---

## 1. Register

Digunakan untuk mendaftarkan akun pengguna baru (masyarakat).

- **Endpoint:** `/register`
- **Method:** `POST`
- **Headers:** 
  - `Accept: application/json`
  - `Content-Type: application/json`

### Body Request (JSON)
| Field | Tipe | Wajib | Keterangan |
| :--- | :--- | :--- | :--- |
| `name` | String | Ya | Nama lengkap pengguna |
| `phone` | String | Ya | Nomor telepon yang aktif |
| `email` | String | Tidak | Alamat email pengguna (unik) |
| `password` | String | Ya | Minimal 8 karakter |

**Contoh Request:**
```json
{
  "name": "Budi Santoso",
  "phone": "081234567890",
  "email": "budi@example.com",
  "password": "password123"
}
```

**Contoh Response Sukses (201 Created):**
```json
{
  "success": true,
  "message": "Registrasi berhasil.",
  "data": {
    "pengguna": {
      "id": 1,
      "name": "Budi Santoso",
      "phone": "081234567890",
      "email": "budi@example.com"
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz..."
  }
}
```

---

## 2. Login

Digunakan untuk masuk ke dalam aplikasi dan mendapatkan akses token. Mendukung login menggunakan Nomor Telepon atau Email.

- **Endpoint:** `/login`
- **Method:** `POST`
- **Headers:** 
  - `Accept: application/json`
  - `Content-Type: application/json`

### Body Request (JSON)
| Field | Tipe | Wajib | Keterangan |
| :--- | :--- | :--- | :--- |
| `email` / `phone`| String | Ya | Bisa menggunakan email atau nomor telepon |
| `password` | String | Ya | Password akun |

**Contoh Request:**
```json
{
  "email": "budi@example.com", 
  "password": "password123"
}
```
*(Catatan: Anda juga bisa mengganti key "email" dengan "phone" jika login menggunakan nomor telepon)*

**Contoh Response Sukses (200 OK):**
```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "pengguna": {
      "id": 1,
      "name": "Budi Santoso",
      "phone": "081234567890",
      "email": "budi@example.com"
    },
    "token": "2|abcdefghijklmnopqrstuvwxyz..."
  }
}
```

**Contoh Response Gagal - Kredensial Salah (401 Unauthorized):**
```json
{
  "success": false,
  "message": "Nomor telepon/email atau password salah."
}
```

---

## 3. Logout

Digunakan untuk keluar dari aplikasi dan menghapus akses token saat ini sehingga tidak bisa digunakan lagi.

- **Endpoint:** `/logout`
- **Method:** `POST`
- **Headers:** 
  - `Accept: application/json`
  - `Authorization: Bearer {token_anda}`

### Body Request
*(Kosong)*

**Contoh Response Sukses (200 OK):**
```json
{
  "success": true,
  "message": "Logout berhasil.",
  "data": null
}
```

**Contoh Response Gagal - Token Tidak Valid (401 Unauthorized):**
```json
{
  "message": "Unauthenticated."
}
```
