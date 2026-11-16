<?php
/**
 * Data contoh: struktur organisasi, KPI berjenjang, dan realisasi 10 bulan.
 * Hanya dipakai saat pemasangan jika pengguna memilih "pasang dengan data contoh".
 */

function kpi_seed_demo(PDO $db, int $year): void
{
    $users = [
        ['budi.santoso', 'Budi Santoso', 'Chief Executive Officer', 'ceo', null, 'Direksi', '1111'],
        ['rina.wijaya', 'Rina Wijaya', 'Kepala Divisi Penjualan & Pemasaran', 'div', 'budi.santoso', 'Divisi Penjualan & Pemasaran', '2222'],
        ['hendra.gunawan', 'Hendra Gunawan', 'Kepala Divisi Operasional', 'div', 'budi.santoso', 'Divisi Operasional', '2222'],
        ['siti.rahayu', 'Siti Rahayu', 'Kepala Divisi Keuangan & SDM', 'div', 'budi.santoso', 'Divisi Keuangan & SDM', '2222'],
        ['agus.prasetyo', 'Agus Prasetyo', 'Kepala Unit Penjualan Retail', 'unit', 'rina.wijaya', 'Unit Penjualan Retail', '3333'],
        ['maya.putri', 'Maya Putri', 'Kepala Unit Pemasaran Digital', 'unit', 'rina.wijaya', 'Unit Pemasaran Digital', '3333'],
        ['joko.susilo', 'Joko Susilo', 'Kepala Unit Produksi', 'unit', 'hendra.gunawan', 'Unit Produksi', '3333'],
        ['lina.marlina', 'Lina Marlina', 'Kepala Unit Logistik & Gudang', 'unit', 'hendra.gunawan', 'Unit Logistik & Gudang', '3333'],
        ['dian.permata', 'Dian Permata', 'Kepala Unit Keuangan & Akuntansi', 'unit', 'siti.rahayu', 'Unit Keuangan & Akuntansi', '3333'],
        ['rudi.hartono', 'Rudi Hartono', 'Kepala Unit SDM', 'unit', 'siti.rahayu', 'Unit SDM', '3333'],
        ['dewi.lestari', 'Dewi Lestari', 'Sales Executive', 'staff', 'agus.prasetyo', 'Unit Penjualan Retail', '4444'],
        ['fajar.nugroho', 'Fajar Nugroho', 'Sales Executive', 'staff', 'agus.prasetyo', 'Unit Penjualan Retail', '4444'],
        ['sari.indah', 'Sari Indah', 'Sales Executive', 'staff', 'agus.prasetyo', 'Unit Penjualan Retail', '4444'],
        ['rizky.ramadhan', 'Rizky Ramadhan', 'Digital Marketing Specialist', 'staff', 'maya.putri', 'Unit Pemasaran Digital', '4444'],
        ['nadia.safitri', 'Nadia Safitri', 'Content & Ads Specialist', 'staff', 'maya.putri', 'Unit Pemasaran Digital', '4444'],
        ['andi.saputra', 'Andi Saputra', 'Operator Produksi', 'staff', 'joko.susilo', 'Unit Produksi', '4444'],
        ['bayu.firmansyah', 'Bayu Firmansyah', 'Operator Produksi', 'staff', 'joko.susilo', 'Unit Produksi', '4444'],
        ['eko.wahyudi', 'Eko Wahyudi', 'Operator Produksi', 'staff', 'joko.susilo', 'Unit Produksi', '4444'],
        ['taufik.hidayat', 'Taufik Hidayat', 'Staf Logistik', 'staff', 'lina.marlina', 'Unit Logistik & Gudang', '4444'],
        ['wulan.sari', 'Wulan Sari', 'Admin Gudang', 'staff', 'lina.marlina', 'Unit Logistik & Gudang', '4444'],
        ['yoga.pratama', 'Yoga Pratama', 'Staf Akuntansi', 'staff', 'dian.permata', 'Unit Keuangan & Akuntansi', '4444'],
        ['putri.ayu', 'Putri Ayu', 'Staf Keuangan', 'staff', 'dian.permata', 'Unit Keuangan & Akuntansi', '4444'],
        ['intan.permatasari', 'Intan Permatasari', 'Staf Rekrutmen', 'staff', 'rudi.hartono', 'Unit SDM', '4444'],
        ['galih.saputra', 'Galih Saputra', 'Staf Pelatihan & Pengembangan', 'staff', 'rudi.hartono', 'Unit SDM', '4444'],
    ];

    $uid = [];
    $ins = $db->prepare('INSERT INTO users (name, title, level, parent_id, dept, username, password_hash) VALUES (?,?,?,?,?,?,?)');
    foreach ($users as [$uname, $name, $title, $level, $parent, $dept, $pw]) {
        $ins->execute([$name, $title, $level, $parent ? $uid[$parent] : null, $dept, $uname, password_hash($pw, PASSWORD_DEFAULT)]);
        $uid[$uname] = (int)$db->lastInsertId();
    }

    // [kunci, pemilik, nama, perspektif, satuan, target, arah, bobot, kunci induk, rollup]
    $kpis = [
        ['c1', 'budi.santoso', 'Pendapatan Perusahaan', 'fin', 'Rp juta', 6000, 'max', 30, null, 'sum'],
        ['c2', 'budi.santoso', 'Margin Laba Bersih', 'fin', '%', 15, 'max', 20, null, 'manual'],
        ['c3', 'budi.santoso', 'Indeks Kepuasan Pelanggan', 'cus', 'indeks', 85, 'max', 15, null, 'manual'],
        ['c4', 'budi.santoso', 'Realisasi Biaya Operasional vs Anggaran', 'ops', '%', 95, 'min', 15, null, 'manual'],
        ['c5', 'budi.santoso', 'Ketepatan Waktu Pengiriman', 'ops', '%', 95, 'max', 10, null, 'avg'],
        ['c6', 'budi.santoso', 'Retensi Karyawan', 'lrn', '%', 95, 'max', 10, null, 'manual'],

        ['d1a', 'rina.wijaya', 'Pendapatan Penjualan', 'fin', 'Rp juta', 6000, 'max', 40, 'c1', 'sum'],
        ['d1b', 'rina.wijaya', 'Jumlah Pelanggan Baru', 'cus', 'pelanggan', 120, 'max', 20, 'c1', 'sum'],
        ['d1c', 'rina.wijaya', 'Indeks Kepuasan Pelanggan', 'cus', 'indeks', 85, 'max', 25, 'c3', 'manual'],
        ['d1d', 'rina.wijaya', 'Realisasi Biaya Pemasaran vs Anggaran', 'ops', '%', 100, 'min', 15, 'c4', 'manual'],

        ['u11a', 'agus.prasetyo', 'Penjualan Retail', 'fin', 'Rp juta', 4000, 'max', 50, 'd1a', 'sum'],
        ['u11b', 'agus.prasetyo', 'Pelanggan Baru Retail', 'cus', 'pelanggan', 80, 'max', 25, 'd1b', 'sum'],
        ['u11c', 'agus.prasetyo', 'Tingkat Komplain Pelanggan', 'cus', '%', 5, 'min', 25, 'd1c', 'manual'],

        ['u12a', 'maya.putri', 'Penjualan Kanal Online', 'fin', 'Rp juta', 2000, 'max', 40, 'd1a', 'sum'],
        ['u12b', 'maya.putri', 'Pelanggan Baru Online', 'cus', 'pelanggan', 40, 'max', 30, 'd1b', 'sum'],
        ['u12c', 'maya.putri', 'Biaya Akuisisi per Pelanggan', 'ops', 'Rp ribu', 150, 'min', 30, 'd1d', 'manual'],

        ['d2a', 'hendra.gunawan', 'Volume Produksi', 'ops', 'unit', 48000, 'max', 20, null, 'sum'],
        ['d2b', 'hendra.gunawan', 'Realisasi Biaya Produksi vs Anggaran', 'ops', '%', 95, 'min', 30, 'c4', 'manual'],
        ['d2c', 'hendra.gunawan', 'Ketepatan Waktu Pengiriman', 'ops', '%', 95, 'max', 30, 'c5', 'avg'],
        ['d2d', 'hendra.gunawan', 'Tingkat Produk Cacat', 'cus', '%', 2, 'min', 20, 'c3', 'manual'],

        ['u21a', 'joko.susilo', 'Volume Produksi Unit', 'ops', 'unit', 48000, 'max', 40, 'd2a', 'sum'],
        ['u21b', 'joko.susilo', 'Tingkat Produk Cacat', 'cus', '%', 2, 'min', 35, 'd2d', 'avg'],
        ['u21c', 'joko.susilo', 'Efisiensi Biaya Produksi', 'ops', '%', 95, 'min', 25, 'd2b', 'manual'],

        ['u22a', 'lina.marlina', 'Ketepatan Waktu Pengiriman', 'ops', '%', 95, 'max', 50, 'd2c', 'avg'],
        ['u22b', 'lina.marlina', 'Akurasi Stok Gudang', 'ops', '%', 98, 'max', 30, null, 'avg'],
        ['u22c', 'lina.marlina', 'Biaya Logistik vs Anggaran', 'ops', '%', 95, 'min', 20, 'd2b', 'manual'],

        ['d3a', 'siti.rahayu', 'Margin Laba Bersih', 'fin', '%', 15, 'max', 30, 'c2', 'manual'],
        ['d3b', 'siti.rahayu', 'Waktu Penyampaian Laporan Keuangan', 'ops', 'hari kerja', 5, 'min', 20, null, 'manual'],
        ['d3c', 'siti.rahayu', 'Retensi Karyawan', 'lrn', '%', 95, 'max', 25, 'c6', 'manual'],
        ['d3d', 'siti.rahayu', 'Jam Pelatihan per Karyawan', 'lrn', 'jam', 4, 'max', 25, 'c6', 'manual'],

        ['u31a', 'dian.permata', 'Waktu Tutup Buku Bulanan', 'ops', 'hari kerja', 5, 'min', 40, 'd3b', 'manual'],
        ['u31b', 'dian.permata', 'Piutang Tertagih', 'fin', '%', 90, 'max', 35, 'd3a', 'avg'],
        ['u31c', 'dian.permata', 'Akurasi Anggaran', 'fin', '%', 95, 'max', 25, 'd3a', 'manual'],

        ['u32a', 'rudi.hartono', 'Retensi Karyawan', 'lrn', '%', 95, 'max', 35, 'd3c', 'manual'],
        ['u32b', 'rudi.hartono', 'Jam Pelatihan per Karyawan', 'lrn', 'jam', 4, 'max', 30, 'd3d', 'manual'],
        ['u32c', 'rudi.hartono', 'Rata-rata Waktu Rekrutmen', 'ops', 'hari', 30, 'min', 35, null, 'avg'],
    ];

    // KPI staf per unit: [nama, perspektif, satuan, target, arah, bobot, kunci induk]
    $staffKpi = [
        'agus.prasetyo' => [
            ['Penjualan Individu', 'fin', 'Rp juta', 1350, 'max', 50, 'u11a'],
            ['Pelanggan Baru', 'cus', 'pelanggan', 27, 'max', 30, 'u11b'],
            ['Kunjungan Pelanggan', 'ops', 'kunjungan', 60, 'max', 20, null],
        ],
        'maya.putri' => [
            ['Penjualan Online', 'fin', 'Rp juta', 1000, 'max', 40, 'u12a'],
            ['Leads Menjadi Pelanggan', 'cus', 'pelanggan', 20, 'max', 30, 'u12b'],
            ['Engagement Rate Konten', 'cus', '%', 5, 'max', 30, null],
        ],
        'joko.susilo' => [
            ['Output Produksi', 'ops', 'unit', 16000, 'max', 50, 'u21a'],
            ['Tingkat Produk Cacat', 'cus', '%', 2, 'min', 30, 'u21b'],
            ['Kepatuhan K3', 'lrn', '%', 100, 'max', 20, null],
        ],
        'lina.marlina' => [
            ['Ketepatan Waktu Pengiriman', 'ops', '%', 95, 'max', 50, 'u22a'],
            ['Akurasi Stok', 'ops', '%', 98, 'max', 30, 'u22b'],
            ['Waktu Proses Pesanan', 'ops', 'jam', 24, 'min', 20, null],
        ],
        'dian.permata' => [
            ['Piutang Tertagih', 'fin', '%', 90, 'max', 40, 'u31b'],
            ['Penyelesaian Rekonsiliasi Bank', 'ops', 'hari kerja', 3, 'min', 35, null],
            ['Kesalahan Pencatatan Jurnal', 'ops', 'temuan', 2, 'min', 25, null],
        ],
        'rudi.hartono' => [
            ['Waktu Rekrutmen', 'ops', 'hari', 30, 'min', 40, 'u32c'],
            ['Program Pelatihan Terlaksana', 'lrn', 'program', 2, 'max', 35, null],
            ['Indeks Kepuasan Karyawan', 'lrn', 'indeks', 80, 'max', 25, null],
        ],
    ];

    $staffOf = [];
    foreach ($users as [$uname, , , $level, $parent]) {
        if ($level === 'staff') {
            $staffOf[$parent][] = $uname;
        }
    }
    foreach ($staffKpi as $unit => $list) {
        foreach ($staffOf[$unit] ?? [] as $staff) {
            foreach ($list as $i => $d) {
                $kpis[] = [$staff . '.k' . $i, $staff, $d[0], $d[1], $d[2], $d[3], $d[4], $d[5], $d[6], 'manual'];
            }
        }
    }

    $kid = [];
    $ins = $db->prepare('INSERT INTO kpis (owner_id, name, persp, unit, target, polarity, weight, parent_id, rollup, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $ord = [];
    foreach ($kpis as [$key, $owner, $name, $persp, $unit, $target, $pol, $weight, $parentKey, $rollup]) {
        $o = $ord[$owner] = ($ord[$owner] ?? 0) + 1;
        $ins->execute([$uid[$owner], $name, $persp, $unit, $target, $pol, $weight, $parentKey ? $kid[$parentKey] : null, $rollup, $o]);
        $kid[$key] = (int)$db->lastInsertId();
    }

    /* ---- Realisasi contoh ---- */
    mt_srand(20261116);
    $rand = static fn (): float => mt_rand() / mt_getrandmax();

    $bias = [];
    foreach ($users as [$uname]) {
        $bias[$uname] = ($rand() - 0.5) * 0.26 - 0.035;
    }
    $bias['fajar.nugroho']   = -0.20;
    $bias['bayu.firmansyah'] = -0.16;
    $bias['nadia.safitri']   = -0.12;
    $bias['maya.putri']      = -0.10;
    $bias['joko.susilo']     = -0.08;
    $bias['dewi.lestari']    = 0.08;
    $bias['taufik.hidayat']  = 0.06;
    $bias['dian.permata']    = 0.05;

    $ownerOf = [];
    foreach ($kpis as [$key, $owner, , , , , , , , $rollup]) {
        $ownerOf[$key] = [$owner, $rollup];
    }
    $levelOf = [];
    foreach ($users as [$uname, , , $level]) {
        $levelOf[$uname] = $level;
    }

    $ins = $db->prepare('INSERT INTO actuals (kpi_id, yr, mo, val, status, note, submitted_by) VALUES (?,?,?,?,?,?,?)');
    $lastMonth = min(10, (int)date('n') - 1);
    if ($lastMonth < 0) {
        $lastMonth = 10;
    }

    foreach ($kpis as [$key, $owner, $name, $persp, $unit, $target, $pol, $weight, $parentKey, $rollup]) {
        if ($rollup !== 'manual') {
            continue;
        }
        $dec = in_array($unit, ['pelanggan', 'unit', 'kunjungan', 'temuan', 'program', 'orang'], true) ? 0
             : ($unit === '%' ? 1 : ($target < 100 ? 1 : 0));
        for ($m = 0; $m <= $lastMonth; $m++) {
            $f = 1 + $bias[$owner] + 0.005 * $m + ($rand() - 0.5) * 0.18;
            if ($f < 0.55) {
                $f = 0.55;
            }
            $v = $pol === 'max' ? $target * $f : $target / $f;
            if (($unit === '%' || $unit === 'indeks') && $pol === 'max') {
                $v = min($v, 100);
            }
            $v = round($v, $dec);

            if ($m < $lastMonth) {
                $ins->execute([$kid[$key], $year, $m, $v, 'approved', '', $uid[$owner]]);
            } elseif ($levelOf[$owner] === 'staff' && $rand() < 0.8) {
                $ins->execute([$kid[$key], $year, $m, $v, 'submitted', '', $uid[$owner]]);
            } elseif ($levelOf[$owner] === 'unit' && $rand() < 0.4) {
                $ins->execute([$kid[$key], $year, $m, $v, 'submitted', '', $uid[$owner]]);
            } elseif ($levelOf[$owner] === 'ceo') {
                $ins->execute([$kid[$key], $year, $m, $v, 'approved', '', $uid[$owner]]);
            }
        }
    }

    // Satu contoh realisasi yang dikembalikan atasan, agar alur terlihat.
    $db->prepare('INSERT INTO actuals (kpi_id, yr, mo, val, status, note, submitted_by) VALUES (?,?,?,?,"rejected",?,?)
        ON DUPLICATE KEY UPDATE status = "rejected", note = VALUES(note)')
       ->execute([$kid['fajar.nugroho.k0'], $year, $lastMonth, 980,
                  'Mohon lampirkan rekap faktur penjualan sebagai bukti.', $uid['fajar.nugroho']]);
}
