# Key Performance Indicator (PHP + MySQL)

Aplikasi KPI berjenjang: target perusahaan diturunkan dari CEO ke Division Head, Unit Head, hingga Staff. Data tersimpan di database MySQL, sehingga semua orang memakai data yang sama dari perangkat masing-masing.

Versi satu file tanpa database ada di folder [`../kpi-demo/`](../kpi-demo/).

## Kebutuhan hosting

- PHP 8.0 atau lebih baru, dengan ekstensi **PDO MySQL** (hampir semua hosting sudah ada).
- Database **MySQL** atau **MariaDB**.
- Hosting biasa dengan cPanel sudah cukup. Tidak perlu VPS.

## Cara memasang

1. **Buat database** di panel hosting (cPanel: menu *MySQL Databases*). Catat nama database, nama pengguna, dan passwordnya.
2. **Unggah isi folder `kpi/`** ke hosting, misalnya ke `public_html/kpi/`.
3. **Buka `https://domain-anda.com/kpi/install.php`** di browser.
4. Isi data database, nama perusahaan, dan pilih data awal:
   - *Dengan data contoh*: 24 orang beserta KPI dan realisasi, untuk mencoba.
   - *Kosong, hanya akun CEO*: untuk langsung dipakai.
5. Klik **Pasang sekarang**. Tabel dibuat otomatis dan file `config.php` ditulis.
6. **Hapus `install.php`** dari hosting, lalu masuk dan ganti password lewat menu **Pengaturan**.

Jika hosting tidak mengizinkan aplikasi menulis file, salin `config.sample.php` menjadi `config.php` dan isi sendiri data databasenya.

## Akun contoh

Hanya ada bila Anda memasang dengan data contoh:

| Level | Username | Password |
| --- | --- | --- |
| CEO | `budi.santoso` | `1111` |
| Division Head | `rina.wijaya` | `2222` |
| Unit Head | `agus.prasetyo` | `3333` |
| Staff | `dewi.lestari` | `4444` |

Sebelum dipakai sungguhan, ganti semua password dan matikan tampilan akun contoh di menu **Pengaturan**.

## Fitur

- **KPI berjenjang.** Setiap KPI dapat dihubungkan ke KPI atasan. Realisasi atasan bisa dihitung otomatis sebagai jumlah atau rata-rata KPI bawahannya.
- **Bobot dan skor.** Tiap KPI punya bobot, target bulanan, dan arah penilaian (makin tinggi atau makin rendah makin baik). Capaian per KPI dibatasi maksimal 120 persen, dan batas ini dapat diubah CEO.
- **Alur persetujuan.** Staf mengisi lalu mengajukan realisasi. Atasan langsung menyetujui atau mengembalikan dengan catatan. Realisasi yang sudah disetujui tidak bisa diubah sendiri oleh staf.
- **Dashboard per level**, **Pohon KPI**, **Struktur Organisasi**, dan **Laporan** skor bulanan yang bisa disalin ke Excel.

## Hak akses

| Peran | Yang bisa dilakukan |
| --- | --- |
| Staff | Melihat dan mengisi KPI sendiri, melihat struktur organisasi |
| Unit Head | Semua di atas, ditambah menetapkan KPI staf dan menyetujui realisasi staf |
| Division Head | Semua di atas, untuk seluruh unit di divisinya |
| CEO | Semua data, menambah dan menghapus anggota, serta mengubah pengaturan |

Skor hanya terlihat untuk diri sendiri dan bawahan. Pemeriksaan hak akses dilakukan di server, bukan hanya di tampilan.

## Keamanan

- Password disimpan dalam bentuk hash (`password_hash`), tidak pernah dikirim ke browser.
- Semua perintah database memakai *prepared statement*.
- Setiap permintaan yang mengubah data diperiksa token CSRF dan hak aksesnya.
- Akun terkunci 15 menit setelah 8 kali percobaan masuk yang gagal.
- Gunakan HTTPS pada hosting Anda agar password tidak terbaca di jaringan.

## Cadangan data

Menu **Pengaturan** menyediakan unduhan data dalam bentuk JSON. Untuk cadangan yang lengkap, gunakan juga fasilitas *backup database* di panel hosting.

## Isi folder

| Berkas | Keterangan |
| --- | --- |
| `index.php` | Halaman aplikasi |
| `api.php` | Titik masuk API (JSON) |
| `install.php` | Halaman pemasangan, hapus setelah selesai |
| `config.php` | Data koneksi database, dibuat otomatis |
| `lib/` | Program inti: database, login, hak akses, data contoh |
| `assets/` | Tampilan: `app.css`, `app.js`, `logo.png` |
