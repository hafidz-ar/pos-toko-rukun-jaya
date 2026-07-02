# Aplikasi POS Toko Rukun Jaya

Aplikasi Point of Sales (POS) berbasis web yang dirancang khusus untuk memenuhi kebutuhan operasional **Toko Rukun Jaya**. Aplikasi ini mengintegrasikan kasir, cek stok lintas satuan, manajemen restock dengan HPP rata-rata tertimbang, laporan keuangan (laba kotor per produk/kategori), serta backup data harian otomatis dan notifikasi terintegrasi dengan Telegram Bot.

---

## Fitur Utama

### 1. Modul Kasir & Transaksi
*   **Pencarian Cepat:** Pencarian produk berdasarkan nama, kode/barcode, atau kategori dengan tampilan foto produk yang jelas untuk menghindari kesalahan varian.
*   **Konversi Satuan:** Mendukung penjualan dalam berbagai pilihan satuan (misal: Sak, Ton, Batang, Lembar) dengan konversi otomatis ke satuan unit dasar di sistem.
*   **Floor Price Diskon:** Validasi ketat (hard reject) yang mencegah kasir memberikan diskon transaksi yang membuat total harga jatuh di bawah total HPP barang dalam keranjang.
*   **Metode Pembayaran:** Mendukung pembayaran secara Tunai dan QRIS (pencatatan manual).

### 2. Modul Cek Stok & Inventaris
*   **Stok Real-Time:** Menampilkan jumlah stok terkini dalam unit dasar dan alternatif (misal: 120 sak / 6 ton) menggunakan sistem *polling* otomatis.
*   **Manajemen Lokasi:** Penataan lokasi fisik produk (rak/zona) yang tercatat secara dinamis di sistem untuk memudahkan pencarian barang oleh karyawan.
*   **Pergerakan Stok:** Log riwayat pergerakan stok masuk dan keluar secara terperinci.
*   **Prediksi Kehabisan Stok:** Estimasi sisa hari stok habis berdasarkan kecepatan penjualan historis (*sales velocity*).

### 3. Modul Restock & Manajemen HPP
*   **Input Batch Restock:** Pembelian barang baru diinput per batch dengan harga modal (HPP) masing-masing.
*   **Weighted Average Costing:** Sistem menghitung ulang HPP produk menggunakan rumus rata-rata tertimbang setiap kali restock terjadi.
*   **Validasi Warning 20%:** Peringatan *soft-block* jika HPP atau Qty restock menyimpang lebih dari 20% dari rata-rata sebelumnya untuk menghindari salah ketik.
*   **Notifikasi Pemilik:** Notifikasi *in-app* dan Telegram Bot otomatis ke Owner untuk memantau setiap aktivitas restock (terutama jika ada restock anomali).

### 4. Modul Dashboard & Laporan Keuangan (Owner-Only)
*   **Laporan Laba Kotor:** Analisis laba kotor lengkap dengan breakdown per produk dan per kategori.
*   **Dashboard Finansial:** Grafik penjualan harian (Senin-Minggu), volume transaksi, omset, dan daftar stok kritis.
*   **Ekspor Dokumen:** Cetak struk kasir dan ekspor Laporan Keuangan ke format PDF.

### 5. Keamanan & Backup Data
*   **Akses Multi-Role:** Pembatasan ketat untuk Role **Owner** (akses seluruh sistem) dan **Karyawan** (terbatas ke Kasir, Cek Stok, Restock, dan Riwayat Transaksi miliknya sendiri).
*   **Backup & Restore:** Backup database otomatis harian dengan retensi 7 hari di server. Owner dapat mendownload file backup secara manual atau merestore database melalui upload file backup.

---

## Spesifikasi Teknologi

*   **Framework Utama:** Laravel (PHP >= 8.3)
*   **Frontend Rendering:** Inertia.js (Vue 3)
*   **Build Tool & Styling:** Vite & Tailwind CSS v4
*   **Database:** SQLite / MySQL
*   **Ekspor PDF:** Barryvdh Laravel DomPDF
*   **Notifikasi Eksternal:** Telegram Bot API

---

## Langkah Inisialisasi & Setup Proyek (Laragon)

1.  **Buka Proyek di Laragon**
    Pastikan folder proyek berada di direktori `C:\laragon\www\pos-toko-rukun-jaya`.

2.  **Jalankan Layanan Laragon**
    Buka aplikasi Laragon dan klik tombol **Start All** untuk mengaktifkan web server (Apache/Nginx) dan database server (MySQL).

3.  **Buat Database Baru**
    Buka aplikasi basis data seperti HeidiSQL atau phpMyAdmin melalui Laragon, lalu buat database baru bernama `pos-toko-rukun-jaya`.

4.  **Instal Dependensi PHP (Composer)**
    Buka terminal di root direktori proyek, lalu jalankan:
    ```bash
    composer install
    ```

5.  **Konfigurasi Berkas Environment**
    Salin berkas `.env.example` menjadi `.env`:
    ```bash
    cp .env.example .env
    ```
    Buka berkas `.env` dan pastikan konfigurasi koneksi basis data sudah sesuai:
    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=pos-toko-rukun-jaya
    DB_USERNAME=root
    DB_PASSWORD=
    ```

6.  **Generate Application Key**
    Jalankan perintah berikut untuk menggenerasikan key aplikasi:
    ```bash
    php artisan key:generate
    ```

7.  **Migrasi & Seed Database**
    Jalankan migrasi database beserta seeder untuk memuat skema tabel dan data awal (akun pengguna default, kategori, produk, lokasi gudang, dan transaksi simulasi):
    ```bash
    php artisan migrate --seed
    ```

8.  **Instal Dependensi & Jalankan Frontend (Vite)**
    Instal dependensi JavaScript dan jalankan server pengembangan Vite untuk mengompilasi aset:
    ```bash
    npm install
    npm run dev
    ```

9.  **Akses Aplikasi di Browser**
    Aplikasi dapat diakses secara lokal melalui virtual host otomatis dari Laragon pada alamat:
    `http://pos-toko-rukun-jaya.test`

### Informasi Kredensial Akun Default (Hasil Seeder)

Untuk masuk ke sistem pertama kali, Anda dapat menggunakan akun simulasi berikut:
*   **Owner (Ci Ali):** Username: `ciali` | Password: `password123`
*   **Karyawan (Budi Santoso):** Username: `budi` | Password: `password123`
*   **Karyawan (Siti Rahma):** Username: `siti` | Password: `password123`

---

## Menjalankan Pengujian (Testing)

Untuk memverifikasi fungsionalitas aplikasi dengan automated tests, jalankan perintah berikut:
```bash
composer test
```


---

## Nama Anggota Kelompok

|      Nama              |NIM        
| :----------------------|:-------------:
| Rifki Ahmad            | 23.11.5416   
| Rafiq Napriyanto       | 23.11.5383 
| Hafidz Ar Rofi         | 23.11.5400 
| Sholikhan Khoirun Akmal| 23.11.5413  
| Muhammad Ridho Pambudi | 23.11.5382

