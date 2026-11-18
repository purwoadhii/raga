# Key Performance Indicator (PHP + MySQL)

Aplikasi KPI berjenjang: target perusahaan diturunkan dari CEO ke Division Head, Unit Head, hingga Staff. Data tersimpan di database MySQL, sehingga semua orang memakai data yang sama dari perangkat masing-masing.

Versi satu file tanpa database ada di folder [`../kpi-demo/`](../kpi-demo/).

## Menjalankan di komputer sendiri

Dari folder utama repository, dengan Node.js, PHP 8, dan MySQL sudah terpasang (misalnya lewat XAMPP, dengan MySQL dinyalakan):

```bash
npm run setup
npm start
```

`npm run setup` membuat database dan tabelnya lalu menulis `config.php`. `npm start` menjalankan aplikasi di `http://localhost:8000` dan membukanya di browser. Penjelasan lengkapnya ada di [README utama](../README.md).

## Halaman admin

Pengelolaan aplikasi punya link sendiri untuk tiap peran. Akunnya terpisah dari akun anggota, tidak punya KPI, dan tidak muncul di aplikasi.

| Peran | Link di komputer sendiri | Link di hosting |
| --- | --- | --- |
| sysAdmin | `http://localhost:8000/sysAdmin` | `https://domain-anda.com/kpi/sysAdmin` |
| infraAdmin | `http://localhost:8000/infraAdmin` | `https://domain-anda.com/kpi/infraAdmin` |

Alamat pendek di hosting memakai `mod_rewrite` (aturannya sudah ada di `.htaccess`, dan hampir semua hosting cPanel mengaktifkannya). Bila hosting Anda tidak mendukungnya, pakai alamat berakhiran `.php`: `sysadmin.php` dan `infraadmin.php`.

Tiap link hanya menerima akun perannya. Akun infraAdmin yang mencoba masuk lewat link sysAdmin ditolak, dan sebaliknya. Sesinya juga terpisah, jadi keduanya bisa masuk bersamaan di satu browser. Link umum `admin.php` tetap ada dan menerima kedua peran.

| Peran | Menu |
| --- | --- |
| **sysAdmin** | Ringkasan, Anggota (tambah, ubah, nonaktifkan, hapus, ganti password, buka kunci login), Pengaturan aplikasi, Akun admin, Catatan aktivitas |
| **infraAdmin** | Status server (PHP, database, HTTPS, izin file, ruang disk), Cadangan database (.sql), Mode pemeliharaan, Catatan aktivitas dan pembersihannya |

**Akun admin bawaan.** Saat aplikasi disiapkan, satu akun sysAdmin dibuat otomatis:

| Username | Password | Link |
| --- | --- | --- |
| `admin` | `123456` | `/sysAdmin` |

Segera ganti password ini di menu **Akun saya**. Selama belum diganti, halaman admin menampilkan pengingat dan Status server infraAdmin menandainya. Akun infraAdmin dibuat oleh sysAdmin dari menu **Akun admin**.

Akun bawaan hanya dibuat sekali. Bila Anda menghapusnya, akun itu tidak muncul lagi. Bila semua akun admin terhapus, halaman admin meminta username dan password CEO sebagai bukti lalu membuat akun admin baru.

Aturan pengaman:

- Password admin minimal 8 karakter. Sesi admin berakhir sendiri setelah 60 menit tanpa kegiatan.
- Setiap peran hanya bisa membuka menunya sendiri, dan server menolak tindakan di luar perannya.
- Harus selalu ada minimal satu sysAdmin aktif. Akun CEO tidak bisa dihapus, dan CEO satu-satunya tidak bisa dinonaktifkan.
- Saat mode pemeliharaan aktif, aplikasi menampilkan halaman pemberitahuan dan tidak ada yang bisa masuk, tetapi halaman admin tetap bisa dibuka.
- Semua tindakan admin tercatat di Catatan aktivitas.

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
| `sysadmin.php`, `infraadmin.php` | Halaman masuk tiap peran admin, dibuka lewat alamat pendek `/sysAdmin` dan `/infraAdmin` |
| `admin.php` | Halaman admin, dipakai oleh kedua link di atas |
| `install.php` | Halaman pemasangan, hapus setelah selesai |
| `config.php` | Data koneksi database, dibuat otomatis |
| `lib/` | Program inti: database, login, hak akses, pemasangan, data contoh |
| `assets/` | Tampilan: `app.css`, `app.js`, `admin.css`, `logo.png` |
