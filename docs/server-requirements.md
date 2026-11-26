# Kebutuhan Server, Batas Unggah, dan Kelayakan Penyimpanan (DCH)

Dokumen ini menyelaraskan batas unggah berkas pada aplikasi **Digital Creative Hub (DCH)** dengan konfigurasi PHP dan web server agar validasi berjalan konsisten dan tidak membebani penyimpanan server lokal.

## 1. Batas Unggah Aplikasi (Sinkron Backend & UI)

| Jenis Berkas | Format Diizinkan | Batas Maksimal Aplikasi | Lokasi Penyimpanan (`public` disk) |
| :--- | :--- | :--- | :--- |
| Karya (`artworks`) | `JPG`, `JPEG`, `PNG`, `WEBP` | **10 MB** (`10240 KB`) | `storage/app/public/artworks` |
| Sampul Artikel (`articles`) | `JPG`, `JPEG`, `PNG`, `WEBP` | **10 MB** (`10240 KB`) | `storage/app/public/articles` |
| Cover Profil (`users.cover_image`) | `JPG`, `JPEG`, `PNG`, `WEBP` | **4 MB** (`4096 KB`) | `storage/app/public/covers` |
| Foto Profil (`users.avatar`) | `JPG`, `JPEG`, `PNG`, `WEBP` | **2 MB** (`2048 KB`) | `storage/app/public/avatars` |
| Bukti Role (`users.proof_image`) | Gambar standar (`image`) | **2 MB** (`2048 KB`) | `storage/app/public/proofs` |

> **Catatan:** Batas unggah karya ditetapkan sebesar **10 MB** (bukan 150 MB) agar layak untuk penyimpanan disk lokal saat ini dan tetap nyaman untuk gambar portofolio beresolusi tinggi.

## 2. Konfigurasi PHP (`php.ini`)

Pastikan `upload_max_filesize` sedikit di atas 10 MB dan `post_max_size` lebih besar dari `upload_max_filesize` agar request di atas 10 MB tetap diteruskan ke validasi Laravel (menghasilkan pesan validasi yang jelas, bukan error kosong atau `PostTooLargeException` sebelum validasi form).

```ini
file_uploads = On
upload_max_filesize = 12M
post_max_size = 16M
memory_limit = 256M
max_file_uploads = 20
```

## 3. Konfigurasi Web Server

### Nginx
Tambahkan pada blok `http` atau `server`:

```nginx
client_max_body_size 16M;
```

### Apache (`.htaccess` atau VirtualHost)
Jika menggunakan pembatasan ukuran body di Apache:

```apache
LimitRequestBody 16777216
```
*(16.777.216 byte = 16 MB)*

## 4. Penyimpanan Lokal & Rekomendasi Pengembangan Lanjutan

- Pastikan symbolic link publik telah dibuat:
  ```bash
  php artisan storage:link
  ```
- Saat ini aplikasi menggunakan **penyimpanan lokal** (`FILESYSTEM_DISK=local` / disk `public`) dan **belum** terintegrasi dengan object storage eksternal (S3/R2/GCS) maupun pipeline kompresi gambar otomatis.
- Apabila di masa mendatang jumlah unggahan meningkat signifikan atau batas ukuran perlu dinaikkan di atas 10 MB, disarankan untuk:
  1. Memigrasikan disk `public` ke object storage kompatibel S3 melalui konfigurasi `config/filesystems.php`.
  2. Menambahkan antrean (queue job) kompresi/thumbnail gambar dan pembersihan berkas yatim secara berkala.
