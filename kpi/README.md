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

Pengelolaan aplikasi punya dua halaman admin. Akun admin terpisah dari akun anggota, tidak punya KPI, dan tidak muncul di aplikasi.

| Halaman | Link di komputer sendiri | Link di hosting |
| --- | --- | --- |
| sysAdmin | `http://localhost:8000/sysAdmin` | `https://domain-anda.com/kpi/sysAdmin` |
| infraAdmin | `http://localhost:8000/infraAdmin` | `https://domain-anda.com/kpi/infraAdmin` |

Alamat pendek di hosting memakai `mod_rewrite` (aturannya sudah ada di `.htaccess`, dan hampir semua hosting cPanel mengaktifkannya). Bila hosting Anda tidak mendukungnya, pakai alamat berakhiran `.php`: `sysadmin.php` dan `infraadmin.php`.

Semua akun admin yang aktif bisa masuk ke kedua halaman, apa pun perannya. Isi menu mengikuti halaman yang dibuka, bukan peran akun, jadi akun `admin` bisa membuka `/infraAdmin` dan akun `infraadmin` bisa membuka `/sysAdmin`. Peran (sysAdmin atau infraAdmin) hanya menjadi label akun. Sesi tiap halaman terpisah, jadi masuk ke `/sysAdmin` tidak otomatis membuka `/infraAdmin`; masuk sekali lagi di sana. Link umum `admin.php` tetap ada dan menampilkan menu sesuai peran akun.

| Halaman | Menu |
| --- | --- |
| **sysAdmin** | Ringkasan, Anggota dengan submenu Aktif dan Tidak aktif (tambah, ubah, atur role, nonaktifkan, hapus, ganti password, buka kunci login), Pengaturan aplikasi, Tampilan, Akun admin, Catatan aktivitas |
| **infraAdmin** | Status server (PHP, database, HTTPS, izin file, ruang disk), Cadangan database (.sql), Mode pemeliharaan, Catatan aktivitas dan pembersihannya |

**Akun admin bawaan.** Saat aplikasi disiapkan, dua akun dibuat otomatis:

| Username | Password | Peran |
| --- | --- | --- |
| `admin` | `123456` | sysAdmin |
| `infraadmin` | `12345678` | infraAdmin |

Keduanya bisa masuk ke `/sysAdmin` maupun `/infraAdmin`.

Segera ganti kedua password ini di menu **Akun saya**. Selama belum diganti, halaman admin menampilkan pengingat dan Status server infraAdmin menandainya. Admin lain bisa ditambahkan dari menu **Akun admin** di `/sysAdmin`.

Akun bawaan hanya dibuat sekali. Bila Anda menghapusnya, akun itu tidak muncul lagi, dan bila username-nya sudah Anda pakai untuk akun lain, akun itu tidak ditimpa. Bila semua akun admin terhapus, halaman admin meminta username dan password CEO sebagai bukti lalu membuat akun admin baru.

Aturan pengaman:

- Password admin minimal 8 karakter. Sesi admin berakhir sendiri setelah 60 menit tanpa kegiatan.
- Server hanya menerima tindakan yang memang ada di halaman yang sedang dibuka (misalnya cadangan database hanya dari `/infraAdmin`).
- Harus selalu ada minimal satu sysAdmin aktif. Akun CEO tidak bisa dihapus, dan CEO satu-satunya tidak bisa dinonaktifkan.
- Saat mode pemeliharaan aktif, aplikasi menampilkan halaman pemberitahuan dan tidak ada yang bisa masuk, tetapi halaman admin tetap bisa dibuka.
- Semua tindakan admin tercatat di Catatan aktivitas.

### Menu Tampilan (sysAdmin)

Menu **Tampilan** di `/sysAdmin` mengatur tampilan aplikasi KPI untuk semua anggota. Perubahan berlaku saat halaman aplikasi dimuat ulang.

| Bagian | Isi |
| --- | --- |
| Warna template | Delapan warna siap pakai (Biru navy bawaan, Biru cerah, Toska, Hijau, Merah marun, Ungu, Oranye, Abu-abu grafit) atau warna sendiri. Mengubah warna tombol, menu, tautan, dan panel halaman masuk, termasuk di tampilan gelap |
| Logo | Logo perusahaan di menu aplikasi, halaman admin, tab browser, dan halaman masuk (bila logo halaman masuk tidak diisi) |
| Halaman masuk | Logo halaman masuk (boleh berbeda dari logo perusahaan; bila kosong memakai logo perusahaan), gambar ilustrasi di sisi kiri (pengganti ilustrasi bawaan), gambar latar, dan pilihan menyembunyikan ilustrasi |
| Setelah masuk | Latar di belakang isi aplikasi: abu-abu bawaan, abu-abu dengan sentuhan warna template, atau gambar latar dengan tiga tingkat kejelasan |

- Gambar yang diterima: PNG, JPG, WEBP, atau GIF, paling besar 5 MB (bisa lebih kecil bila `upload_max_filesize` di hosting lebih kecil). Setiap gambar bisa dikembalikan ke bawaan dengan tombol **Pakai bawaan lagi**.
- Gambar disimpan di folder `uploads/`, bukan di database, jadi tidak ikut di cadangan `.sql`. Saat memindahkan aplikasi, salin juga folder ini. Folder ini harus bisa ditulis server; Status server infraAdmin memeriksanya.
- `uploads/.htaccess` mencegah file selain gambar dibuka dari folder ini.

## Bahasa

Pilihan bahasa ada di halaman masuk (kanan atas) dan di bagian atas setiap halaman, dengan ikon bola dunia. Pilihan ini juga ada di halaman admin `/sysAdmin` dan `/infraAdmin`.

| Wilayah | Bahasa |
| --- | --- |
| Asia | Bahasa Indonesia (bawaan), 简体中文 (Mandarin), 日本語 (Jepang), العربية (Arab) |
| Eropa | English (Inggris), Français (Prancis), Deutsch (Jerman), Español (Spanyol) |

- Pilihan disimpan di browser masing-masing, jadi setiap orang bisa memakai bahasanya sendiri. Bila belum memilih, aplikasi tampil dalam Bahasa Indonesia.
- Aplikasi dan halaman admin memakai pilihan yang sama: bahasa yang dipilih di salah satunya langsung berlaku di yang lain pada browser itu.
- Bahasa Arab ditampilkan dari kanan ke kiri.
- Nama bulan, tanda desimal, dan pesan dari server (misalnya "password salah") ikut bahasa yang dipilih.
- Data yang diisi sendiri tidak diterjemahkan: nama orang, jabatan, unit, nama KPI, pesan pemeliharaan yang ditulis infraAdmin, dan rincian di catatan aktivitas.
- Halaman pemasangan (`install.php`) dan perintah `npm run setup` masih berbahasa Indonesia.

Terjemahan tampilan aplikasi ada di `assets/i18n.js`, pesan server di `lib/i18n.php`, dan halaman admin di `lib/i18n_admin.php`. Setiap baris berisi teks Indonesia diikuti terjemahannya, jadi untuk memperbaiki sebuah terjemahan cukup ubah teks di baris itu.

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
- **8 bahasa tampilan**: Indonesia, Mandarin, Jepang, Arab, Inggris, Prancis, Jerman, dan Spanyol. Lihat [Bahasa](#bahasa).

## Hak akses

| Level | Yang bisa dilakukan |
| --- | --- |
| Staff | Melihat dan mengisi KPI sendiri, melihat struktur organisasi |
| Unit Head | Semua di atas, ditambah menetapkan KPI staf dan menyetujui realisasi staf |
| Division Head | Semua di atas, untuk seluruh unit di divisinya |
| CEO | Semua data, menambah dan menghapus anggota, serta mengubah pengaturan |

Skor hanya terlihat untuk diri sendiri dan bawahan. Pemeriksaan hak akses dilakukan di server, bukan hanya di tampilan.

### Role anggota

Selain level, setiap anggota punya **role** yang diatur sysAdmin di menu **Anggota** (buka anggota, pilih role, lalu Simpan). Role menambah hak di luar tim sendiri. Level tetap mengikuti atasan.

| Role | Yang bisa dilakukan |
| --- | --- |
| Anggota biasa (bawaan) | Sesuai levelnya seperti tabel di atas |
| Pemantau | Ditambah melihat KPI, skor, pohon KPI, dan laporan semua anggota di seluruh perusahaan, tanpa bisa mengubahnya. Cocok untuk HR atau auditor |
| Pengelola KPI | Ditambah menetapkan dan mengubah KPI semua anggota di **satu divisi atau unit** yang dipilih sysAdmin, termasuk kepalanya. Bila tidak dipilih, wilayahnya divisi anggota itu sendiri. Tidak bisa mengubah data anggota atau pengaturan |
| Superuser | Seperti CEO: melihat semua anggota, menetapkan dan mengubah KPI siapa pun di seluruh perusahaan, menambah dan mengubah anggota di Struktur Organisasi, dan mengubah pengaturan perusahaan. Cocok untuk divisi atau unit yang menyusun KPI perusahaan. Data akun CEO tetap hanya bisa diubah CEO sendiri |

- CEO selalu punya semua hak, jadi tidak diberi role.
- Persetujuan realisasi tetap oleh atasan langsung, apa pun role-nya. Realisasi yang langsung tercatat disetujui tetap hanya milik CEO.
- Role hanya bisa diubah di `/sysAdmin`, tidak dari dalam aplikasi.
- Pemantau, Pengelola KPI, dan Superuser yang punya tim bisa memilih cakupan **Tim saya** atau wilayahnya (seluruh perusahaan, atau divisi/unit yang dikelola) di halaman Tim, Pohon KPI, dan Laporan.
- Pemasangan lama otomatis mendapat kolom yang dibutuhkan saat halaman pertama kali dibuka setelah pembaruan.

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
| `lib/` | Program inti: database, login, hak akses, pemasangan, data contoh, tampilan dari menu Tampilan (`ui.php`), terjemahan pesan server (`i18n.php`) |
| `uploads/` | Logo dan gambar yang diunggah sysAdmin di menu Tampilan |
| `assets/` | Tampilan: `app.css`, `app.js`, terjemahan tampilan (`i18n.js`), `admin.css`, `logo.png` |
