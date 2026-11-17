# Key Performance Indicator (versi coba)

Aplikasi KPI berjenjang dalam satu file, `index.html`. Cukup klik dua kali filenya untuk membukanya di browser, tanpa pemasangan apa pun.

Versi ini untuk mencoba tampilan dan alur kerjanya. **Data tersimpan di browser perangkat masing-masing**, jadi data staf dan atasan tidak tersambung. Untuk dipakai sungguhan, gunakan versi PHP + MySQL di folder [`../kpi/`](../kpi/).

## Fitur

- **KPI berjenjang (cascading):** setiap KPI bisa diturunkan dari KPI atasan. Realisasi atasan bisa dihitung otomatis sebagai jumlah atau rata-rata KPI bawahannya.
- **Bobot, target, dan skor:** tiap KPI punya bobot, target bulanan, dan arah penilaian (makin tinggi atau makin rendah makin baik). Capaian per KPI dibatasi 120% (bisa diubah CEO).
- **Alur persetujuan:** staf mengisi dan mengajukan realisasi, atasan langsung menyetujui atau mengembalikan dengan catatan.
- **Dashboard per level**, **Pohon KPI**, **Struktur Organisasi** (dikelola CEO), dan **Laporan** skor bulanan.

## Akun contoh

Aplikasi berisi data contoh perusahaan fiktif (24 orang, Januari sampai November 2026).

| Level | Username | Password |
| --- | --- | --- |
| CEO | `budi.santoso` | `1111` |
| Division Head | `rina.wijaya` | `2222` |
| Unit Head | `agus.prasetyo` | `3333` |
| Staff | `dewi.lestari` | `4444` |

Ganti password lewat menu **Pengaturan**. CEO dapat mengubah username dan password semua orang lewat menu **Struktur Organisasi**.

## Penyimpanan data

Data tersimpan di browser perangkat yang dipakai (localStorage). Gunakan **Pengaturan > Cadangan data** untuk menyalin atau memulihkan data.
