'use strict';
/**
 * npm run setup
 *
 * Menyiapkan aplikasi KPI di komputer ini: membuat database (bila belum ada),
 * membuat tabel, mengisi data awal, dan menulis kpi/config.php.
 *
 * Cukup tekan Enter di setiap pertanyaan untuk memakai nilai bawaan XAMPP.
 * Tanpa pertanyaan sama sekali:  npm run setup -- --yes
 * Nilai bisa juga diberikan lewat variabel lingkungan: KPI_DB_HOST, KPI_DB_PORT,
 * KPI_DB_NAME, KPI_DB_USER, KPI_DB_PASS, KPI_COMPANY, KPI_MODE (demo | empty),
 * KPI_CEO_NAME, KPI_CEO_USER, KPI_CEO_PASS.
 */

const path = require('path');
const readline = require('readline');
const { spawnSync } = require('child_process');
const { APP_DIR, requirePhp, isInstalled, say, fail } = require('./lib');

const env = process.env;
const autoYes = process.argv.includes('--yes') || process.argv.includes('-y') || !process.stdin.isTTY;

/** Bertanya satu per satu; juga bekerja bila jawaban dikirim lewat pipa. */
function makeAsker() {
  const rl = readline.createInterface({ input: process.stdin, output: process.stdout });
  const queue = [];
  const waiting = [];
  let closed = false;
  rl.on('line', (line) => (waiting.length ? waiting.shift()(line) : queue.push(line)));
  rl.on('close', () => {
    closed = true;
    waiting.splice(0).forEach((resolve) => resolve(''));
  });
  return {
    ask(label, def, hint) {
      const shown = hint !== undefined ? hint : def;
      process.stdout.write(`${label}${shown ? ` [${shown}]` : ''}: `);
      return new Promise((resolve) => {
        const done = (line) => resolve(String(line).trim() || def);
        if (queue.length) done(queue.shift());
        else if (closed) done('');
        else waiting.push(done);
      });
    },
    close() {
      rl.close();
    },
  };
}

async function collect() {
  const o = {
    db_host: env.KPI_DB_HOST || '127.0.0.1',
    db_port: env.KPI_DB_PORT || '3306',
    db_user: env.KPI_DB_USER || 'root',
    db_pass: env.KPI_DB_PASS || '',
    db_name: env.KPI_DB_NAME || 'kpi',
    company: env.KPI_COMPANY || 'Perusahaan',
    mode: env.KPI_MODE === 'empty' ? 'empty' : 'demo',
    ceo_name: env.KPI_CEO_NAME || '',
    ceo_user: env.KPI_CEO_USER || '',
    ceo_pass: env.KPI_CEO_PASS || '',
    year: new Date().getFullYear(),
  };
  if (autoYes) return o;

  say([
    'Jawab beberapa pertanyaan berikut. Tekan Enter untuk memakai nilai di dalam kurung.',
    'Untuk XAMPP biasanya cukup tekan Enter terus sampai selesai.',
    '',
  ]);
  const q = makeAsker();
  o.db_host = await q.ask('Alamat MySQL', o.db_host);
  o.db_port = await q.ask('Port MySQL', o.db_port);
  o.db_user = await q.ask('Nama pengguna MySQL', o.db_user);
  o.db_pass = await q.ask('Password MySQL', o.db_pass, o.db_pass ? 'tersimpan' : 'kosong');
  o.db_name = await q.ask('Nama database', o.db_name);
  o.company = await q.ask('Nama perusahaan', o.company);

  const demo = await q.ask('Isi dengan data contoh? (y/t)', o.mode === 'demo' ? 'y' : 't');
  o.mode = /^(t|n)/i.test(demo) ? 'empty' : 'demo';

  if (o.mode === 'empty') {
    say('\nBuat akun CEO, yaitu akun pertama yang mengatur semuanya.');
    o.ceo_name = await q.ask('Nama CEO', o.ceo_name || 'Direktur Utama');
    o.ceo_user = await q.ask('Username CEO', o.ceo_user || 'admin');
    for (;;) {
      o.ceo_pass = await q.ask('Password CEO (minimal 6 karakter)', o.ceo_pass, o.ceo_pass ? 'tersimpan' : '');
      if (o.ceo_pass.length >= 6) break;
      say('Password terlalu pendek, coba lagi.');
      o.ceo_pass = '';
    }
  }
  q.close();
  return o;
}

async function main() {
  say('\n=== Persiapan Aplikasi KPI ===\n');

  if (isInstalled()) {
    say([
      'Aplikasi sudah pernah disiapkan (file kpi/config.php sudah ada).',
      'Langsung jalankan:  npm start',
      '',
      'Untuk menyiapkan ulang dari awal, hapus file kpi/config.php lalu jalankan perintah ini lagi.',
      '',
    ]);
    return;
  }

  const php = requirePhp();
  say(`PHP ${php.version} ditemukan.\n`);

  const o = await collect();
  if (o.mode === 'empty' && String(o.ceo_pass).length < 6) {
    fail('Untuk pemasangan tanpa data contoh, isi KPI_CEO_PASS dengan password minimal 6 karakter.');
  }

  say('\nMenyiapkan database...');
  const r = spawnSync(php.bin, [path.join(APP_DIR, 'lib', 'cli_install.php')], {
    encoding: 'utf8',
    windowsHide: true,
    env: Object.assign({}, env, { KPI_SETUP: JSON.stringify(o) }),
  });
  if (r.error) fail('PHP tidak bisa dijalankan: ' + r.error.message);
  if (r.status !== 0) {
    fail(String(r.stderr || r.stdout || 'Pemasangan gagal.').trim(), r.status || 1);
  }

  let res = {};
  try {
    res = JSON.parse(String(r.stdout).trim().split('\n').pop());
  } catch (e) { /* ringkasan tidak wajib */ }

  const lines = [''];
  if (res.reused) {
    lines.push(`Database "${o.db_name}" sudah berisi data aplikasi ini, jadi dipakai apa adanya (${res.users} orang, ${res.kpis} KPI).`);
  } else {
    lines.push(`Selesai. Database "${o.db_name}" siap dengan ${res.users || 0} orang dan ${res.kpis || 0} KPI.`);
  }
  if (res.demo) {
    lines.push(
      '',
      'Akun contoh untuk masuk:',
      '  CEO            budi.santoso    password 1111',
      '  Division Head  rina.wijaya     password 2222',
      '  Unit Head      agus.prasetyo   password 3333',
      '  Staff          dewi.lestari    password 4444'
    );
  } else if (!res.reused) {
    lines.push('', `Masuk dengan username "${o.ceo_user}" dan password yang baru Anda buat.`);
  }
  const admins = res.admins && typeof res.admins === 'object' ? res.admins : {};
  const pages = { admin: '/sysAdmin', infraadmin: '/infraAdmin' };
  if (Object.keys(admins).length) {
    lines.push('', 'Akun admin bawaan:');
    for (const [user, pass] of Object.entries(admins)) {
      lines.push(`  ${(pages[user] || '').padEnd(12)} username ${user.padEnd(11)} password ${pass}`);
    }
    lines.push('  Segera ganti password ini di menu Akun saya.');
  }
  lines.push('', 'Langkah berikutnya:  npm start', '');
  say(lines);
}

main().catch((e) => fail(e && e.message ? e.message : String(e)));
