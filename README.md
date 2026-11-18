# raga

Kumpulan aplikasi web internal. Setiap aplikasi berada di foldernya sendiri.

## Daftar aplikasi

| Folder | Aplikasi | Keterangan |
| --- | --- | --- |
| [`kpi/`](kpi/) | Key Performance Indicator | KPI berjenjang dari CEO hingga Staff. Versi PHP + MySQL |
| [`kpi-demo/`](kpi-demo/) | KPI (versi coba) | Aplikasi KPI dalam satu file HTML, tanpa database. Untuk mencoba tampilannya |

## Menjalankan aplikasi KPI di komputer sendiri

Yang perlu terpasang lebih dulu:

- **Node.js** (untuk perintah `npm`).
- **PHP 8 dan MySQL.** Cara termudah adalah memasang [XAMPP](https://www.apachefriends.org), lalu menyalakan **MySQL** dari XAMPP Control Panel.

Pertama kali:

```bash
git clone https://github.com/purwoadhii/raga.git
cd raga
npm run setup
npm start
```

Berikutnya, setiap kali ada pembaruan:

```bash
git pull
npm start
```

| Perintah | Kegunaan |
| --- | --- |
| `npm run setup` | Membuat database dan tabel, mengisi data awal, dan menulis `kpi/config.php`. Cukup sekali. Tekan Enter di setiap pertanyaan untuk memakai nilai bawaan XAMPP (pengguna `root`, tanpa password, database `kpi`) |
| `npm start` | Menjalankan aplikasi dan membukanya di browser, di alamat `http://localhost:8000` |

Setelah `npm start`, ada tiga alamat:

| Alamat | Untuk |
| --- | --- |
| `http://localhost:8000` | Aplikasi KPI, untuk CEO, Division Head, Unit Head, dan Staff |
| `http://localhost:8000/sysAdmin` | sysAdmin: anggota, pengaturan aplikasi, akun admin |
| `http://localhost:8000/infraAdmin` | infraAdmin: status server, cadangan database, mode pemeliharaan |

Akun admin bawaan: username `admin`, password `123456`, masuk lewat link sysAdmin. Segera ganti passwordnya di menu **Akun saya**. Penjelasan halaman admin ada di [kpi/README.md](kpi/README.md#halaman-admin).

Tidak perlu `npm install`, karena tidak ada paket tambahan yang dipakai. `npm run setup` aman dijalankan lagi: bila aplikasi sudah disiapkan, datanya tidak diubah.

Pengaturan tambahan:

- Port lain: `PORT=9000 npm start` (Windows: `set PORT=9000` lalu `npm start`).
- Supaya bisa dibuka dari komputer lain di jaringan yang sama: `KPI_HOST=0.0.0.0 npm start`.
- PHP terpasang di tempat yang tidak biasa: isi `KPI_PHP` dengan lokasi `php` atau `php.exe`.
- Menyiapkan tanpa pertanyaan: `npm run setup -- --yes`.

## Cara lain

- **Dipasang di hosting** (cPanel dan sejenisnya): unggah isi folder `kpi/`, lalu buka `install.php`. Panduannya ada di [kpi/README.md](kpi/README.md).
- **Versi coba** (folder `kpi-demo/`): unduh repository ini (tombol **Code** lalu **Download ZIP**), ekstrak, lalu klik dua kali file `index.html`.
