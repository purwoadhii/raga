'use strict';
/**
 * Bantuan bersama untuk `npm run setup` dan `npm start`.
 * Tidak memakai paket tambahan, jadi tidak perlu `npm install`.
 */

const fs = require('fs');
const path = require('path');
const { spawnSync } = require('child_process');

const ROOT = path.resolve(__dirname, '..');
const APP_DIR = path.join(ROOT, 'kpi');
const CONFIG_FILE = path.join(APP_DIR, 'config.php');

/** Tempat PHP biasa terpasang, dicoba berurutan. */
function phpCandidates() {
  const list = [];
  if (process.env.KPI_PHP) list.push(process.env.KPI_PHP);
  list.push('php');

  if (process.platform === 'win32') {
    list.push('C:\\xampp\\php\\php.exe', 'D:\\xampp\\php\\php.exe', 'C:\\php\\php.exe');
    for (const base of ['C:\\laragon\\bin\\php', 'D:\\laragon\\bin\\php', 'C:\\wamp64\\bin\\php', 'C:\\wamp\\bin\\php']) {
      try {
        fs.readdirSync(base).sort().reverse()
          .forEach((dir) => list.push(path.join(base, dir, 'php.exe')));
      } catch (e) { /* folder tidak ada */ }
    }
  } else {
    list.push(
      '/opt/homebrew/bin/php',
      '/usr/local/bin/php',
      '/usr/bin/php',
      '/Applications/XAMPP/xamppfiles/bin/php',
      '/opt/lampp/bin/php'
    );
    try {
      const mamp = '/Applications/MAMP/bin/php';
      fs.readdirSync(mamp).sort().reverse()
        .forEach((dir) => list.push(path.join(mamp, dir, 'bin', 'php')));
    } catch (e) { /* MAMP tidak ada */ }
  }
  return list;
}

/** Mencari PHP 8 yang bisa dijalankan. Mengembalikan { bin, version, pdoMysql } atau null. */
function findPhp() {
  let tooOld = null;
  for (const bin of phpCandidates()) {
    if (bin !== 'php' && !fs.existsSync(bin)) continue;
    const r = spawnSync(bin, ['-r', "echo PHP_VERSION, '|', extension_loaded('pdo_mysql') ? 1 : 0;"], {
      encoding: 'utf8',
      windowsHide: true,
    });
    if (r.error || r.status !== 0) continue;
    const m = /(\d+)\.(\d+)\.(\d+)[^|]*\|([01])\s*$/.exec(String(r.stdout));
    if (!m) continue;
    const info = { bin, version: `${m[1]}.${m[2]}.${m[3]}`, pdoMysql: m[4] === '1' };
    if (Number(m[1]) >= 8) return info;
    tooOld = tooOld || info;
  }
  return tooOld ? Object.assign(tooOld, { tooOld: true }) : null;
}

/** Berhenti dengan pesan yang jelas bila PHP belum siap. */
function requirePhp() {
  const php = findPhp();
  if (!php) {
    fail([
      'PHP tidak ditemukan di komputer ini.',
      '',
      'Aplikasi ini memerlukan PHP dan MySQL. Cara paling mudah: pasang XAMPP',
      'dari https://www.apachefriends.org lalu jalankan perintah ini lagi.',
      '',
      'Jika PHP sudah terpasang di tempat lain, beri tahu lokasinya, contoh:',
      process.platform === 'win32'
        ? '  set KPI_PHP=C:\\lokasi\\php.exe'
        : '  export KPI_PHP=/lokasi/php',
    ]);
  }
  if (php.tooOld) {
    fail(`PHP yang ditemukan versi ${php.version}. Aplikasi ini memerlukan PHP 8.0 atau lebih baru.`);
  }
  if (!php.pdoMysql) {
    fail([
      `PHP ${php.version} ditemukan, tetapi ekstensi pdo_mysql belum aktif.`,
      'Buka file php.ini, hapus tanda titik koma di depan baris "extension=pdo_mysql",',
      'lalu jalankan perintah ini lagi. PHP bawaan XAMPP sudah mengaktifkannya.',
    ]);
  }
  return php;
}

function isInstalled() {
  return fs.existsSync(CONFIG_FILE);
}

function say(lines) {
  console.log(Array.isArray(lines) ? lines.join('\n') : lines);
}

function fail(lines, code) {
  console.error('\n' + (Array.isArray(lines) ? lines.join('\n') : lines) + '\n');
  process.exit(code || 1);
}

module.exports = { ROOT, APP_DIR, CONFIG_FILE, findPhp, requirePhp, isInstalled, say, fail };
