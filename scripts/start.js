'use strict';
/**
 * npm start
 *
 * Menjalankan aplikasi KPI di komputer ini dan membukanya di browser.
 * Alamat bawaan http://localhost:8000. Ubah dengan variabel PORT atau KPI_HOST:
 *   PORT=9000 npm start
 *   KPI_HOST=0.0.0.0 npm start     (supaya bisa dibuka dari komputer lain di jaringan yang sama)
 * Tanpa membuka browser otomatis:  npm start -- --no-open
 */

const net = require('net');
const path = require('path');
const { spawn, spawnSync } = require('child_process');
const { APP_DIR, CONFIG_FILE, requirePhp, isInstalled, say, fail } = require('./lib');

const HOST = process.env.KPI_HOST || '127.0.0.1';
const WANT_PORT = parseInt(process.env.PORT || process.env.KPI_PORT || '8000', 10) || 8000;
const NO_OPEN = process.argv.includes('--no-open') || process.env.KPI_NO_OPEN === '1';

function portFree(port) {
  return new Promise((resolve) => {
    const s = net.createServer();
    s.once('error', () => resolve(false));
    s.once('listening', () => s.close(() => resolve(true)));
    s.listen(port, HOST);
  });
}

async function pickPort() {
  for (let p = WANT_PORT; p < WANT_PORT + 20; p++) {
    if (await portFree(p)) return p;
  }
  fail(`Port ${WANT_PORT} sampai ${WANT_PORT + 19} sedang dipakai. Coba port lain, contoh: PORT=9000 npm start`);
}

/** Memeriksa apakah MySQL menjawab di alamat yang tercatat di config.php. */
function mysqlReachable(php) {
  const r = spawnSync(php.bin, ['-r', "$c = require $argv[1]; echo $c['db_host'], '|', $c['db_port'];", CONFIG_FILE], {
    encoding: 'utf8',
    windowsHide: true,
  });
  const m = /^(.+)\|(\d+)\s*$/.exec(String(r.stdout || ''));
  if (!m) return Promise.resolve(true);
  const host = m[1] === 'localhost' ? '127.0.0.1' : m[1];
  return new Promise((resolve) => {
    const s = net.connect({ host, port: Number(m[2]) });
    const end = (ok) => {
      s.destroy();
      resolve(ok);
    };
    s.setTimeout(2500, () => end(false));
    s.once('connect', () => end(true));
    s.once('error', () => end(false));
  });
}

function openBrowser(url) {
  try {
    const opt = { stdio: 'ignore', detached: true, windowsHide: true };
    let child;
    if (process.platform === 'win32') child = spawn('cmd', ['/c', 'start', '', url], opt);
    else if (process.platform === 'darwin') child = spawn('open', [url], opt);
    else child = spawn('xdg-open', [url], opt);
    child.on('error', () => {});
    child.unref();
  } catch (e) { /* buka manual saja */ }
}

async function main() {
  if (!isInstalled()) {
    fail(['Aplikasi belum disiapkan.', 'Jalankan dulu:  npm run setup']);
  }
  const php = requirePhp();

  if (!(await mysqlReachable(php))) {
    say([
      '',
      'Perhatian: MySQL belum menyala.',
      'Nyalakan MySQL lebih dulu (di XAMPP: klik Start pada baris MySQL), lalu muat ulang halaman.',
    ]);
  }

  const port = await pickPort();
  const shownHost = HOST === '0.0.0.0' ? 'localhost' : HOST === '127.0.0.1' ? 'localhost' : HOST;
  const url = `http://${shownHost}:${port}/`;

  const server = spawn(php.bin, ['-S', `${HOST}:${port}`, '-t', APP_DIR, path.join(__dirname, 'router.php')], {
    stdio: ['ignore', 'ignore', 'pipe'],
    windowsHide: true,
  });

  let started = false;
  let errText = '';
  server.stderr.on('data', (buf) => {
    const text = String(buf);
    // Catatan tiap permintaan dari PHP tidak ditampilkan; hanya galat yang penting.
    text.split(/\r?\n/).forEach((line) => {
      if (/PHP (Fatal|Parse|Warning)|Failed to listen/i.test(line)) console.error(line);
    });
    if (!started) errText += text;
  });
  server.on('error', (e) => fail('PHP tidak bisa dijalankan: ' + e.message));
  server.on('exit', (code) => {
    if (!started) fail(['Aplikasi gagal dijalankan.', errText.trim()]);
    process.exit(code || 0);
  });

  const stop = () => {
    try {
      server.kill();
    } catch (e) { /* sudah berhenti */ }
  };
  process.on('SIGINT', stop);
  process.on('SIGTERM', stop);
  process.on('exit', stop);

  // Tunggu sampai server benar-benar menerima sambungan.
  const probeHost = HOST === '0.0.0.0' ? '127.0.0.1' : HOST;
  for (let i = 0; i < 50 && !started; i++) {
    await new Promise((r) => setTimeout(r, 120));
    started = await new Promise((resolve) => {
      const s = net.connect({ host: probeHost, port });
      s.once('connect', () => {
        s.destroy();
        resolve(true);
      });
      s.once('error', () => resolve(false));
    });
  }
  if (!started) {
    stop();
    fail(['Aplikasi gagal dijalankan.', errText.trim()]);
  }

  say([
    '',
    '=== Aplikasi KPI berjalan ===',
    '',
    `Buka di browser:  ${url}`,
    `sysAdmin:         ${url}sysadmin.php`,
    `infraAdmin:       ${url}infraadmin.php`,
    '',
    'Biarkan jendela ini tetap terbuka selama aplikasi dipakai.',
    'Untuk menghentikan, tekan Ctrl + C.',
    '',
  ]);
  if (!NO_OPEN) openBrowser(url);
}

main().catch((e) => fail(e && e.message ? e.message : String(e)));
