# Digital Creative Hub (DCH)

Platform portofolio dan komunitas kreatif berbasis **Laravel 12** bagi mahasiswa, dosen, dan klien untuk membagikan karya visual serta artikel kreatif.

## Fitur Utama
- **Eksplorasi & Pencarian Karya:** Galeri portofolio dengan filter kategori, pencarian kata kunci, serta interaksi *like/unlike*, *save/unsave*, dan *share*.
- **Manajemen Profil Kreator:** Pengelolaan biodata, *headline*, institusi, kota, negara/lokasi, tautan website, Instagram (`ig`), foto profil (*avatar*), dan *cover image*.
- **Perlindungan HKI & Etika Karya:** Pilihan lisensi karya (*All Rights Reserved*, *Creative Commons CC BY / CC BY-NC / CC BY-SA / CC BY-NC-SA / CC0*), pernyataan wajib orisinalitas/izin unggah, serta mekanisme pelaporan manual dugaan plagiarisme/pelanggaran hak cipta untuk ditinjau admin/kurator.
- **Kurasi & Moderasi Konten:** Karya dan artikel baru otomatis berstatus `pending` hingga disetujui (`approved`) atau ditolak (`rejected` beserta catatan alasan) oleh akun dengan role `admin` melalui panel `/admin/moderation`.

## Dokumentasi Teknis
- **Diagram ERD (Mermaid):** [docs/erd.mmd](docs/erd.mmd) — sumber diagram yang dapat dirender ulang menggunakan Mermaid CLI / Mermaid Live Editor (mencakup relasi many-to-many `artwork_likes` & `artwork_saves`, `artwork_reports`, kolom HKI, dan kolom moderasi).
- **Kebutuhan Server & Batas Unggah:** [docs/server-requirements.md](docs/server-requirements.md) — panduan penyelarasan batas unggah **10 MB** dengan `upload_max_filesize`, `post_max_size`, dan `client_max_body_size`.

## Instalasi Lokal
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

## Pengujian
```bash
php artisan test
```
