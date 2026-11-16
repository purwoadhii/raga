# raga

Kumpulan aplikasi web internal. Setiap aplikasi berada di foldernya sendiri.

## Daftar aplikasi

| Folder | Aplikasi | Keterangan |
| --- | --- | --- |
| [`kpi/`](kpi/) | Key Performance Indicator | KPI berjenjang dari CEO hingga Staff. Versi PHP + MySQL, dipasang di hosting |
| [`kpi-demo/`](kpi-demo/) | KPI (versi coba) | Aplikasi KPI dalam satu file HTML, tanpa database. Untuk mencoba tampilannya |

## Cara membuka

- **Aplikasi dengan database** (folder `kpi/`): unggah ke hosting yang mendukung PHP dan MySQL, lalu buka `install.php`. Panduannya ada di [kpi/README.md](kpi/README.md).
- **Versi coba** (folder `kpi-demo/`): unduh repository ini (tombol **Code** lalu **Download ZIP**), ekstrak, lalu klik dua kali file `index.html`.
