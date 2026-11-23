/* ===== Konstanta ===== */
const PREF='kpi-app-pref-v1';
const MON_ID=['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
const MONF_ID=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
let MON=MON_ID.slice(), MONF=MONF_ID.slice();
/** Penanda teks yang diterjemahkan saat ditampilkan (lihat assets/i18n.js). */
const N_=s=>s;
const LV={ceo:{label:N_('CEO'),next:'div'},div:{label:N_('Division Head'),next:'unit'},unit:{label:N_('Unit Head'),next:'staff'},staff:{label:N_('Staff'),next:null}};
const PERSP={fin:N_('Keuangan'),cus:N_('Pelanggan'),ops:N_('Proses Internal'),lrn:N_('Pembelajaran & Pertumbuhan')};
const INT_UNITS=[N_('pelanggan'),N_('unit'),N_('kunjungan'),N_('temuan'),N_('program'),N_('orang')];
const UNIT_SUGGEST=[N_('Rp juta'),N_('Rp ribu'),'%',N_('indeks'),N_('unit'),N_('pelanggan'),N_('hari'),N_('hari kerja'),N_('jam'),N_('program'),N_('temuan')];
const ST_LABEL={draft:N_('Draf'),submitted:N_('Menunggu persetujuan'),approved:N_('Disetujui'),rejected:N_('Dikembalikan')};
const IC={
 home:'<path d="M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
 mine:'<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4"/><circle cx="12" cy="12" r="1"/>',
 team:'<circle cx="9" cy="8" r="3.2"/><path d="M3 19c.6-3.3 3-5 6-5s5.4 1.7 6 5"/><circle cx="17" cy="9" r="2.4"/><path d="M16 14.2c2.4.2 4.3 1.8 4.8 4.8"/>',
 tree:'<rect x="9" y="3" width="6" height="4" rx="1"/><rect x="3" y="17" width="6" height="4" rx="1"/><rect x="15" y="17" width="6" height="4" rx="1"/><path d="M12 7v5M6 17v-3h12v3"/>',
 org:'<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M4 10h16M10 10v10"/>',
 approve:'<path d="M5 12l4 4 10-10"/><path d="M20 13v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h9"/>',
 report:'<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
 settings:'<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.6 1.6 0 0 0-1.8-.3 1.6 1.6 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.6 1.6 0 0 0-1-1.5 1.6 1.6 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.6 1.6 0 0 0 .3-1.8 1.6 1.6 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.6 1.6 0 0 0 1.5-1 1.6 1.6 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.6 1.6 0 0 0 1.8.3H9a1.6 1.6 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.6 1.6 0 0 0 1 1.5 1.6 1.6 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.6 1.6 0 0 0-.3 1.8V9a1.6 1.6 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.6 1.6 0 0 0-1.5 1z"/>',
 out:'<path d="M15 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4M10 17l5-5-5-5M15 12H3"/>',
 edit:'<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M14 6l4 4"/>',
 plus:'<path d="M12 5v14M5 12h14"/>',
 caret:'<path d="M9 6l6 6-6 6"/>',
 copy:'<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3"/>',
 globe:'<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9s1.3-6.4 3.8-9z"/>',
 user:'<circle cx="12" cy="8" r="4"/><path d="M4 21c.7-4 3.8-6 8-6s7.3 2 8 6"/>',
 lock:'<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7.5a4 4 0 0 1 8 0V11"/>',
 eye:'<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
 logo:'<path d="M5 19V13M10 19V9M15 19V11M20 19V5"/><path d="M4 9l5-4 5 3 6-5"/>'
};
const ico=n=>`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${IC[n]}</svg>`;

/* ===== Util ===== */
const $=s=>document.querySelector(s), $$=s=>[...document.querySelectorAll(s)];
function esc(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}
function fmt(v,d=0){if(v==null||isNaN(v))return '–';return Number(v).toLocaleString(LOC(),{minimumFractionDigits:d,maximumFractionDigits:d})}
const fs=v=>v==null?'–':fmt(v,1);
function initials(n){return String(n).split(/\s+/).filter(Boolean).slice(0,2).map(w=>w[0]).join('').toUpperCase()}
function uid(p){return p+Math.random().toString(36).slice(2,8)}
function rng(seed){return function(){seed|=0;seed=seed+0x6D2B79F5|0;let t=Math.imul(seed^seed>>>15,1|seed);t=t+Math.imul(t^t>>>7,61|t)^t;return((t^t>>>14)>>>0)/4294967296}}
function toast(msg){const t=document.createElement('div');t.className='toast';t.textContent=msg;document.body.appendChild(t);setTimeout(()=>t.remove(),2600)}
const store={get(k){try{return localStorage.getItem(k)}catch(e){return null}},set(k,v){try{localStorage.setItem(k,v)}catch(e){}},del(k){try{localStorage.removeItem(k)}catch(e){}}};

/* ===== State ===== */
let S=null, cache=new Map();
const BOOT=window.__BOOT__||{};
/** Tampilan yang diatur sysAdmin di menu Tampilan: logo dan gambar halaman masuk. */
const UI=Object.assign({logo:'assets/logo.png',logoOwn:false,art:null,showArt:true},BOOT.ui||{});
const logoImg=(cls,size,alt)=>`<img class="${cls}${UI.logoOwn?' own':''}" src="${esc(UI.logo)}" alt="${alt}"${size?` width="${size}" height="${size}"`:''}>`;
let P={page:'home',month:new Date().getMonth(),view:null,lang:'id',rlv:'all',wide:'all'};
function loadPref(){try{const p=JSON.parse(store.get(PREF)||'null');if(p){delete p.lang;Object.assign(P,p)}}catch(e){}}
function savePref(){store.set(PREF,JSON.stringify({page:P.page,month:P.month,view:P.view,rlv:P.rlv,wide:P.wide}))}

/* ===== Bahasa =====
 * Teks di program ini ditulis dalam bahasa Indonesia dan sekaligus menjadi kunci terjemahan.
 * Terjemahannya ada di assets/i18n.js. Pilihan bahasa disimpan di cookie kpi_lang supaya
 * pesan dari server ikut memakai bahasa yang sama. */
const I18N=window.KPI_I18N||{};
const LANGS=Array.isArray(I18N.langs)&&I18N.langs.length?I18N.langs:[{code:'id',region:'asia',name:'Bahasa Indonesia',locale:'id-ID',html:'id'}];
const LANG_BY=Object.fromEntries(LANGS.map(l=>[l.code,l]));
const DICT={};
(I18N.rows||[]).forEach(r=>(I18N.order||[]).forEach((c,i)=>{if(r[i+1])(DICT[c]=DICT[c]||{})[r[0]]=r[i+1]}));
/** Terjemahkan teks; {0}, {1}, ... diganti dengan nilai berikutnya. */
function tx(s,...a){const d=DICT[P.lang],r=d&&d[s]?d[s]:s;return a.length?r.replace(/\{(\d+)\}/g,(m,i)=>a[i]==null?'':String(a[i])):r}
/** Teks ini dalam semua bahasa. */
const txAll=s=>[s,...Object.values(DICT).map(d=>d[s]).filter(Boolean)];
const INT_SET=new Set(INT_UNITS.flatMap(txAll).map(x=>x.toLowerCase()));
const LOC=()=>(LANG_BY[P.lang]||LANGS[0]).locale||'id-ID';
const DEC=()=>(1.5).toLocaleString(LOC()).replace(/\d/g,'')||',';
const isRtl=()=>(LANG_BY[P.lang]||{}).dir==='rtl';
function monthNames(style){try{const f=new Intl.DateTimeFormat(LOC(),{month:style,timeZone:'UTC'}),a=[];for(let i=0;i<12;i++)a.push(f.format(new Date(Date.UTC(2021,i,15))));return a}catch(e){return null}}
/** Nama bulan beserta tahun penilaian, mis. "Januari 2026" atau "2026年1月". */
function monY(i){if(P.lang==='id')return MONF_ID[i]+' '+S.year;try{return new Intl.DateTimeFormat(LOC(),{month:'long',year:'numeric',timeZone:'UTC'}).format(new Date(Date.UTC(S.year,i,15)))}catch(e){return MONF[i]+' '+S.year}}
function applyLang(){const L=LANG_BY[P.lang]||LANGS[0],h=document.documentElement;P.lang=L.code;h.lang=L.html||L.code;h.dir=L.dir||'ltr';
  MON=P.lang==='id'?MON_ID.slice():monthNames('short')||MON_ID.slice();MONF=P.lang==='id'?MONF_ID.slice():P.lang==='zh'?MON.slice():monthNames('long')||MONF_ID.slice()}
function saveLang(c){const p=location.pathname,dir=p.slice(0,p.lastIndexOf('/')+1)||'/';
  try{document.cookie='kpi_lang='+c+'; path='+dir+'; max-age=31536000; SameSite=Lax'}catch(e){}store.set('kpi-lang',c)}
function initLang(){let c=BOOT.lang;if(!BOOT.langSet){const s=store.get('kpi-lang');if(s&&LANG_BY[s]){c=s;saveLang(s)}}P.lang=LANG_BY[c]?c:LANGS[0].code;applyLang()}
function setLang(c){if(!LANG_BY[c])return;const un=$('#lg-user'),pw=$('#lg-pin'),keep=un?[un.value,pw.value]:null;
  P.lang=c;saveLang(c);applyLang();render();
  if(keep&&$('#lg-user')){$('#lg-user').value=keep[0];$('#lg-pin').value=keep[1]}const s=$('[data-lang-sel]');if(s)s.focus()}
/** Nama bahasa di pilihan: nama aslinya, ditambah namanya dalam bahasa yang sedang dipakai. */
function langLabel(l){const n=((I18N.names||{})[P.lang]||{})[l.code];return l.code!==P.lang&&n&&n!==l.name?l.name+' · '+n:l.name}
function langPicker(cls){const groups=[['asia',N_('Asia')],['europe',N_('Eropa')]];
  return `<label class="lang-pick ${cls}" title="${tx('Ganti bahasa')}">${ico('globe')}<select data-lang-sel aria-label="${tx('Ganti bahasa')}">${groups.map(([g,gl])=>`<optgroup label="${tx(gl)}">${LANGS.filter(l=>l.region===g).map(l=>`<option value="${l.code}" lang="${l.html||l.code}" ${l.code===P.lang?'selected':''}>${esc(langLabel(l))}</option>`).join('')}</optgroup>`).join('')}</select></label>`}

/* ===== Penghubung ke server ===== */
let CSRF=BOOT.csrf||'', busy=false;
function applyData(d){S=d;cache.clear();if(P.month>11||P.month<0)P.month=0}
async function api(action,payload){
  let r,j=null;
  try{r=await fetch('api.php',{method:'POST',credentials:'same-origin',cache:'no-store',
    headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},body:JSON.stringify(Object.assign({action},payload||{}))});
  }catch(e){const err=new Error(tx('Tidak dapat terhubung ke server. Periksa koneksi internet Anda.'));err.status=0;throw err}
  try{j=await r.json()}catch(e){}
  if(!r.ok||!j||!j.ok){const err=new Error((j&&j.error)||tx('Terjadi kesalahan di server.'));err.status=r.status;throw err}
  if(j.csrf)CSRF=j.csrf;
  if(j.data)applyData(j.data);
  return j;
}
function apiFail(e){
  if(e.status===419){toast(tx('Sesi berakhir, halaman dimuat ulang.'));setTimeout(()=>location.reload(),1200);return}
  if(e.status===401){S=null;render();toast(e.message);return}
  if(e.status===503){toast(e.message);setTimeout(()=>location.reload(),1500);return}
  toast(e.message);
}
/** Jalankan aksi; galat tampil sebagai pesan singkat. */
function run(fn){if(busy)return;busy=true;document.body.style.cursor='progress';
  Promise.resolve().then(fn).catch(apiFail).finally(()=>{busy=false;document.body.style.cursor=''})}
/** Jalankan aksi dari formulir; galat tampil di dalam formulir. */
function runForm(errSel,fn){const err=$(errSel);if(err)err.textContent='';if(busy)return;busy=true;document.body.style.cursor='progress';
  Promise.resolve().then(fn).catch(e=>{if(e.status===401||e.status===419)return apiFail(e);if(err)err.textContent=e.message;else toast(e.message)})
   .finally(()=>{busy=false;document.body.style.cursor=''})}

/* ===== Model ===== */
const userById=id=>S.users.find(u=>u.id===id);
const kpiById=id=>S.kpis.find(k=>k.id===id);
const me=()=>S?userById(S.meId):null;
const childrenUsers=id=>S.users.filter(u=>u.parentId===id);
function descendants(id){const out=[];const walk=(p,d)=>childrenUsers(p).forEach(c=>{out.push(c);c._d=d;walk(c.id,d+1)});walk(id,1);return out}
function ancestors(id){const out=[];let u=userById(id);const seen=new Set();while(u&&u.parentId&&!seen.has(u.id)){seen.add(u.id);u=userById(u.parentId);if(u)out.push(u)}return out}
const isAncestor=(a,b)=>ancestors(b).some(x=>x.id===a);
/* Role dari sysAdmin: Pemantau melihat semua anggota, Pengelola KPI juga bisa mengatur seperti CEO. */
const ROLE_NAMES={pemantau:N_('Pemantau'),pengelola:N_('Pengelola KPI')};
const viewAll=u=>!!u&&(u.level==='ceo'||u.role==='pemantau'||u.role==='pengelola');
const canManage=u=>!!u&&(u.level==='ceo'||u.role==='pengelola');
const inScope=(v,u)=>v===u||isAncestor(v,u)||viewAll(userById(v));
/* Bukan CEO tetapi boleh melihat seluruh perusahaan: halaman tim, pohon, dan laporan bisa memilih cakupan. */
const wideUser=u=>viewAll(u)&&u.level!=='ceo';
const wideOn=u=>wideUser(u)&&(P.wide!=='team'||(!childrenUsers(u.id).length&&P.page==='team'));
function everyone(){const out=[];S.users.filter(x=>!x.parentId).forEach(r=>{r._d=1;out.push(r);descendants(r.id).forEach(c=>{c._d+=1;out.push(c)})});return out}
const wideSel=u=>wideUser(u)?`<select class="inline" id="wide" aria-label="${tx('Cakupan')}"><option value="team" ${P.wide==='team'?'selected':''}>${childrenUsers(u.id).length?tx('Tim saya'):tx('Saya sendiri')}</option><option value="all" ${P.wide!=='team'?'selected':''}>${tx('Seluruh perusahaan')}</option></select>`:'';
const kpisOf=uid=>S.kpis.filter(k=>k.ownerId===uid);
const childKpis=id=>S.kpis.filter(k=>k.parentId===id);
function decOf(k){if(INT_SET.has(String(k.unit||'').toLowerCase()))return 0;if(k.unit==='%')return 1;return k.target<100?1:0}
const entry=(kid,m)=>(S.actuals[kid]||{})[m]||null;
function actual(k,m){
  const key='a|'+k.id+'|'+m; if(cache.has(key))return cache.get(key);
  let v=null;
  if(k.rollup==='manual'){const e=entry(k.id,m);v=e&&e.v!==''&&e.v!=null?+e.v:null}
  else{cache.set(key,null);const vals=childKpis(k.id).map(c=>actual(c,m)).filter(x=>x!=null);
    if(vals.length){const s=vals.reduce((a,b)=>a+b,0);v=k.rollup==='sum'?s:s/vals.length}}
  cache.set(key,v);return v;
}
function ach(k,m){const v=actual(k,m);if(v==null)return null;const t=+k.target;let a;
  if(k.polarity==='max')a=t?v/t*100:0;else a=v<=0?S.cap:t/v*100;return Math.max(0,Math.min(S.cap,a))}
function score(id,m){const key='s|'+id+'|'+m;if(cache.has(key))return cache.get(key);
  let sw=0,s=0;kpisOf(id).forEach(k=>{const a=ach(k,m);if(a!=null){sw+=+k.weight;s+=a*k.weight}});
  const r=sw?s/sw:null;cache.set(key,r);return r}
function ytd(id,m){const a=[];for(let i=0;i<=m;i++){const s=score(id,i);if(s!=null)a.push(s)}return a.length?a.reduce((x,y)=>x+y,0)/a.length:null}
function avgScore(list,m){const a=list.map(u=>score(u.id,m)).filter(x=>x!=null);return a.length?a.reduce((x,y)=>x+y,0)/a.length:null}
function rating(s){if(s==null)return{key:'na',label:tx('Belum ada data')};if(s>=100)return{key:'r1',label:tx('Melampaui Target')};if(s>=90)return{key:'r2',label:tx('Memenuhi Target')};if(s>=75)return{key:'r3',label:tx('Perlu Perhatian')};return{key:'r4',label:tx('Di Bawah Target')}}
const pill=s=>{const r=rating(s);return `<span class="pill ${r.key}">${r.label}</span>`};
const weightSum=id=>kpisOf(id).reduce((a,k)=>a+(+k.weight||0),0);
function pendingFor(id){const out=[];childrenUsers(id).forEach(u=>kpisOf(u.id).forEach(k=>{const a=S.actuals[k.id]||{};Object.keys(a).forEach(m=>{if(a[m].status==='submitted')out.push({u,k,m:+m,e:a[m]})})}));return out}
const canDef=(viewer,owner)=>canManage(viewer)||isAncestor(viewer.id,owner);
const lvlName=l=>LV[l]?tx(LV[l].label):l;
const lvlLabel=u=>lvlName(u.level);
const roleLabel=u=>ROLE_NAMES[u.role]?tx(ROLE_NAMES[u.role]):'';
function slug(s){return String(s||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'.').replace(/^\.+|\.+$/g,'')}
const unameOf=u=>u.username||slug(u.name);

/* ===== Komponen ===== */
function ring(s,sm){const r=rating(s),R=sm?30:50,C=2*Math.PI*R,f=s==null?0:Math.min(1,s/S.cap),w=sm?7:10,box=(R+w)*2;
  return `<div class="ring ${sm?'sm':''}"><svg viewBox="0 0 ${box} ${box}"><circle cx="${box/2}" cy="${box/2}" r="${R}" fill="none" stroke="var(--surface-2)" stroke-width="${w}"/>
  <circle cx="${box/2}" cy="${box/2}" r="${R}" fill="none" stroke="var(--${r.key==='na'?'line':r.key})" stroke-width="${w}" stroke-linecap="round" stroke-dasharray="${C*f} ${C}"/></svg>
  <div class="v"><b class="num">${s==null?'–':fmt(s,1)}</b>${sm?'':`<span>${tx('skor KPI')}</span>`}</div></div>`}
function track(a){const r=rating(a);const w=a==null?0:a/S.cap*100;return `<div class="track"><i class="fill-${r.key}" style="width:${w}%"></i><em style="inset-inline-start:${100/S.cap*100}%"></em></div>`}
function av(u,cls=''){return `<div class="av lv-${u.level} ${cls}">${esc(initials(u.name))}</div>`}
function lineChart(series,m){
  const sm=window.innerWidth<600,W=sm?380:640,H=sm?240:230,L=30,R=MON.some(x=>x.length>4)?24:10,T=12,B=26;
  const all=series.flatMap(s=>s.values).filter(v=>v!=null);
  let ymin=Math.min(60,Math.floor((Math.min(...all,100)-5)/10)*10);ymin=Math.max(0,ymin);const ymax=S.cap;
  const x=i=>L+i*(W-L-R)/11, y=v=>T+(ymax-v)/(ymax-ymin)*(H-T-B);
  let g='';
  for(let t=ymin;t<=ymax;t+=20){g+=`<line x1="${L}" x2="${W-R}" y1="${y(t)}" y2="${y(t)}" stroke="var(--line)" stroke-width="1"/><text x="${L-6}" y="${y(t)+4}" text-anchor="end">${t}</text>`}
  g+=`<line x1="${L}" x2="${W-R}" y1="${y(100)}" y2="${y(100)}" stroke="var(--fg)" stroke-opacity=".4" stroke-dasharray="4 4"/><text x="${W-R}" y="${y(100)-5}" text-anchor="end">${tx('target 100')}</text>`;
  g+=`<rect x="${x(m)-14}" y="${T}" width="28" height="${H-T-B}" fill="var(--accent-soft)" opacity=".7"/>`;
  MON.forEach((mn,i)=>{if(sm&&i%2&&i!==m)return;g+=`<text x="${x(i)}" y="${H-8}" text-anchor="middle" ${i===m?'style="fill:var(--fg);font-weight:700"':''}>${mn}</text>`});
  series.forEach(s=>{let d='',pen=false;s.values.forEach((v,i)=>{if(v==null){pen=false;return}d+=(pen?'L':'M')+x(i).toFixed(1)+' '+y(v).toFixed(1)+' ';pen=true});
    if(s.area){const pts=s.values.map((v,i)=>v==null?null:[x(i),y(v)]).filter(Boolean);if(pts.length>1)g+=`<path d="M${pts[0][0]} ${y(ymin)} ${pts.map(p=>'L'+p[0]+' '+p[1]).join(' ')} L${pts[pts.length-1][0]} ${y(ymin)}Z" fill="${s.color}" opacity=".09"/>`}
    g+=`<path d="${d}" fill="none" stroke="${s.color}" stroke-width="${s.w||2.5}" ${s.dash?'stroke-dasharray="5 4"':''} stroke-linejoin="round" stroke-linecap="round"/>`;
    s.values.forEach((v,i)=>{if(v!=null)g+=`<circle cx="${x(i)}" cy="${y(v)}" r="${i===m?5:3}" fill="${i===m?s.color:'var(--surface)'}" stroke="${s.color}" stroke-width="2"><title>${s.name} ${MON[i]}: ${fmt(v,1)}</title></circle>`})});
  return `<div class="chart"><svg viewBox="0 0 ${W} ${H}" role="img" aria-label="${tx('Tren skor KPI per bulan')}">${g}</svg></div>
  <div class="legend">${series.map(s=>`<span><i style="background:${s.color}"></i>${esc(s.name)}</span>`).join('')}</div>`}
function barsPeople(list,m){
  if(!list.length)return `<div class="empty">${tx('Belum ada anggota.')}</div>`;
  return `<div class="bars">${list.map(u=>{const s=score(u.id,m),r=rating(s);return `<div class="bar-row" data-act="person" data-id="${u.id}">
    <div class="lbl">${esc(u.name)} <span>· ${esc(u.dept)}</span></div><div class="val num" style="color:var(--${r.key==='na'?'muted':r.key})">${fs(s)}</div>${track(s)}</div>`}).join('')}</div>`}

/* ===== Login ===== */
const LOGO_INLINE='data:image/png;base64,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';
const APP_VER='2.0';
const TX={welcome:N_('Selamat Datang'),sub:N_('Masukkan username dan password akun Anda.'),user:N_('Username'),userPh:N_('Masukkan username'),pass:N_('Password'),passPh:N_('Masukkan password'),
  rem:N_('Ingat saya'),forgot:N_('Lupa password?'),signin:N_('Masuk'),h:N_('Target selaras, kinerja terpantau'),p:N_('Turunkan KPI dari CEO hingga staf, catat realisasi, dan pantau pencapaian di satu tempat.'),
  err:N_('Username atau password salah. Silakan coba lagi.'),forgotMsg:N_('Minta atasan langsung atau CEO untuk mengatur ulang password Anda. CEO dapat mengubahnya lewat menu Struktur Organisasi.'),
  demo:N_('Akun contoh'),show:N_('Tampilkan atau sembunyikan password'),c1:N_('Skor per Bulan'),c2:N_('Pencapaian'),stamp:N_('SESUAI TARGET'),art:N_('Ilustrasi kartu skor KPI')};
const t=k=>k==='foot'?(BOOT.company||'')+' · KPI '+APP_VER:tx(TX[k]);
function heroArt(){
  const row=(c,kind,w1,w2)=>{const box=kind==='ok'?`<rect x="172" y="${c-9}" width="18" height="18" rx="4.5" fill="#2F6BE4"/><polyline points="176.500,${c} 180,${c+3.500} 186,${c-3.500}" fill="none" stroke="#fff" stroke-width="2.200" stroke-linecap="round" stroke-linejoin="round"/>`
      :kind==='warn'?`<rect x="172" y="${c-9}" width="18" height="18" rx="4.5" fill="#F2A52B"/><line x1="181" y1="${c-4.500}" x2="181" y2="${c+1}" stroke="#fff" stroke-width="2.400" stroke-linecap="round"/><circle cx="181" cy="${c+4.500}" r="1.300" fill="#fff"/>`
      :`<rect x="172.750" y="${c-8.250}" width="16.500" height="16.500" rx="4" fill="#fff" stroke="#C3CAD9" stroke-width="1.500"/>`;
    return box+`<rect x="202" y="${c-6}" width="${w1}" height="6" rx="3" fill="#C6CEDD"/><rect x="202" y="${c+4}" width="${w2}" height="4.500" rx="2.250" fill="#DDE2EC"/>`};
  const bars=[[42,24],[63,40],[84,31],[105,56],[126,47]].map(([x,h],i)=>`<rect x="${x}" y="${140-h}" width="14" height="${h}" rx="3" fill="${i>2?'#6CA0FF':'#3E74E6'}"/>`).join('');
  return `<svg viewBox="0 0 520 330" role="img" aria-label="${t('art')}">
  <defs><linearGradient id="lg-sh" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#6CA0FF"/><stop offset="1" stop-color="#2A5FD6"/></linearGradient></defs>
  <circle cx="452" cy="50" r="4" fill="#5B8DEF"/><circle cx="476" cy="86" r="2.500" fill="#5B8DEF" opacity=".7"/><circle cx="40" cy="262" r="3" fill="#5B8DEF" opacity=".6"/><circle cx="60" cy="306" r="5" fill="#5B8DEF" opacity=".8"/>
  <rect x="24" y="44" width="176" height="112" rx="10" fill="#1D3570" stroke="#34508F"/><text x="40" y="68" font-size="10" fill="#C3CEE8">${t('c1')}</text>${bars}
  <rect x="150" y="22" width="190" height="252" rx="14" fill="#F4F6FB"/>
  <rect x="206" y="10" width="78" height="26" rx="8" fill="#0E1C40"/><circle cx="245" cy="23" r="5" fill="#4C86F5"/>
  <rect x="172" y="56" width="104" height="9" rx="4.500" fill="#12224A"/><rect x="172" y="73" width="68" height="6" rx="3" fill="#B9C2D4"/>
  ${row(108,'ok',100,62)}${row(142,'ok',112,70)}${row(176,'warn',112,58)}${row(210,'none',92,52)}
  <polyline points="174,254 186,242 198,249 212,234 226,240" fill="none" stroke="#12224A" stroke-width="2.200" stroke-linecap="round" stroke-linejoin="round"/>
  <text x="320" y="252" text-anchor="end" font-size="8.500" font-weight="700" fill="#17936B">${t('stamp')}</text>
  <circle cx="338" cy="118" r="46" fill="#E4ECFD" stroke="#A9C2F8" stroke-width="4"/><circle cx="338" cy="118" r="30" fill="#BCD0FA"/><circle cx="338" cy="118" r="14" fill="#2F6BE4"/>
  <line x1="338" y1="118" x2="394" y2="62" stroke="#12224A" stroke-width="4.500" stroke-linecap="round"/><path d="M384 72v-15M384 72h15M392 64v-15M392 64h15" fill="none" stroke="#12224A" stroke-width="3.500" stroke-linecap="round"/>
  <g transform="translate(118,234)"><path d="M0-40L34-27V4C34 28 18 41 0 50C-18 41-34 28-34 4V-27Z" fill="url(#lg-sh)"/><polyline points="-14,3 -4,13 15,-8" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></g>
  <rect x="338" y="206" width="158" height="104" rx="10" fill="#1D3570" stroke="#34508F"/><text x="354" y="230" font-size="10" fill="#C3CEE8">${t('c2')}</text>
  <circle cx="380" cy="268" r="20" fill="none" stroke="#34508F" stroke-width="7"/><circle cx="380" cy="268" r="20" fill="none" stroke="#6CA0FF" stroke-width="7" stroke-linecap="round" stroke-dasharray="115.600 125.700" transform="rotate(-90 380 268)"/>
  <text x="380" y="271.500" text-anchor="middle" font-size="9.500" font-weight="700" fill="#fff">92%</text>
  <rect x="414" y="255" width="64" height="6" rx="3" fill="#4A64A3"/><rect x="414" y="267" width="48" height="6" rx="3" fill="#4A64A3"/><rect x="414" y="279" width="56" height="6" rx="3" fill="#4A64A3"/></svg>`}
function viewLogin(){
  const demo=(BOOT.demoMode&&Array.isArray(BOOT.demoAccounts))?BOOT.demoAccounts:[];
  return `<div class="login">
  <section class="lg-hero">${!UI.showArt?'':UI.art?`<img class="lg-art" src="${esc(UI.art)}" alt="">`:heroArt()}<h2>${t('h')}</h2><p>${t('p')}</p></section>
  <section class="login-side"><div class="lg-card">
    ${langPicker('lg-lang')}
    <form class="lg-form" id="lg-form" autocomplete="off" novalidate>
      <div class="lg-brand">${logoImg('lg-logo',58,'Logo')}<span>Key Performance Indicator</span></div>
      <h1>${t('welcome')}</h1><p class="sub">${t('sub')}</p>
      <div class="lg-field"><label for="lg-user">${t('user')}</label><div class="lg-in">${ico('user')}<input id="lg-user" type="text" autocapitalize="none" autocorrect="off" spellcheck="false" placeholder="${t('userPh')}"></div></div>
      <div class="lg-field"><label for="lg-pin">${t('pass')}</label><div class="lg-in">${ico('lock')}<input id="lg-pin" type="password" autocomplete="off" placeholder="${t('passPh')}"><button type="button" data-act="eye" aria-label="${t('show')}">${ico('eye')}</button></div></div>
      <div class="lg-row"><label><input type="checkbox" id="lg-rem" ${P.remember!==false?'checked':''}>${t('rem')}</label><button type="button" class="lg-link" data-act="forgot">${t('forgot')}</button></div>
      <div class="lg-info" id="lg-info" hidden>${t('forgotMsg')}</div>
      <div class="err" id="lg-err" role="alert"></div>
      <button type="submit" class="lg-btn">${t('signin')}</button>
      <div class="lg-foot">${t('foot')}</div>
      ${demo.length?`<details class="lg-demo"><summary>${t('demo')}</summary><div class="demo-grid">${demo.map(d=>`<button type="button" class="demo-btn" data-act="demo" data-u="${esc(d.username)}" data-p="${esc(d.password)}"><b>${esc(tx(d.level))}</b><span>${esc(d.username)} · ${esc(d.password)}</span></button>`).join('')}</div></details>`:''}
    </form>
  </div></section></div>`}

/* ===== Shell ===== */
function navItems(u){const hasTeam=childrenUsers(u.id).length>0;const pend=pendingFor(u.id).length;const all=viewAll(u);
  return [['home',tx('Beranda')],['mine',tx('KPI Saya')],(hasTeam||all)&&['team',hasTeam?tx('Tim Saya'):tx('Semua Anggota')],['tree',tx('Pohon KPI')],['org',tx('Struktur Organisasi')],hasTeam&&['approve',tx('Persetujuan'),pend],(hasTeam||all)&&['report',tx('Laporan')],['settings',tx('Pengaturan')]].filter(Boolean)}
function shell(pg){const u=me();
  return `<div class="app"><aside class="side">
    <div class="brand">${logoImg('logo-img',0,'')}<div style="min-width:0"><b>Key Performance Indicator</b><span>${esc(S.company)}</span></div><button class="btn sm ghost out" data-act="logout" aria-label="${tx('Keluar')}">${ico('out')}</button></div>
    <nav class="nav">${navItems(u).map(([k,l,b])=>`<button class="${P.page===k||(k==='team'&&P.page==='person')?'on':''}" data-act="go" data-page="${k}">${ico(k)}<span>${l}</span>${b?`<span class="badge">${b}</span>`:''}</button>`).join('')}</nav>
    <div class="me-box">${av(u)}<div class="who"><b>${esc(u.name)}</b><span>${lvlLabel(u)}${roleLabel(u)?' · '+roleLabel(u):''}</span></div><button class="btn sm ghost" data-act="logout" title="${tx('Keluar')}" aria-label="${tx('Keluar')}">${ico('out')}</button></div>
  </aside><main class="main">
    <header class="top"><div><div class="eyebrow">${pg.eyebrow||''}</div><h1>${pg.title}</h1></div>
    <div class="top-tools">${pg.noPeriod?'':`<label class="period">${tx('Periode')}<select class="inline" id="period">${MONF.map((n,i)=>`<option value="${i}" ${i===P.month?'selected':''}>${monY(i)}</option>`).join('')}</select></label>`}${langPicker('top-lang')}</div></header>
    <div class="content">${pg.html}</div></main></div>`}

/* ===== Halaman: Beranda ===== */
function pageHome(){
  const u=me(),m=P.month,s=score(u.id,m),subs=descendants(u.id),direct=childrenUsers(u.id),ks=kpisOf(u.id);
  const sup=userById(u.parentId);
  let html=`<div class="card hero">${ring(s)}<div class="info"><div class="eyebrow">${lvlLabel(u)} · ${esc(u.dept)}</div><h2>${tx('Halo, {0}',esc(u.name.split(' ')[0]))}</h2>
    <div>${pill(s)} <span class="muted small">${tx('skor {0}',monY(m))}${sup?` · ${tx('atasan: {0}',esc(sup.name))}`:''}</span></div>
    <div class="hero-stats"><div><b class="num">${fs(ytd(u.id,m))}</b><span>${tx('Rata-rata {0}',MON[0]+'–'+MON[m])}</span></div><div><b class="num">${ks.length}</b><span>${tx('KPI aktif')}</span></div>
    <div><b class="num">${ks.filter(k=>{const a=ach(k,m);return a!=null&&a>=100}).length}/${ks.length}</b><span>${tx('KPI tercapai')}</span></div></div></div>
    <button class="btn primary" data-act="go" data-page="mine">${ico('mine')} ${tx('Lihat KPI Saya')}</button></div>`;
  if(subs.length){const ts=subs.map(x=>score(x.id,m));const low=ts.filter(x=>x!=null&&x<90).length;const pend=pendingFor(u.id).length;
    html+=`<div class="tiles">
    <div class="card tile"><span class="eyebrow">${tx('Rata-rata skor tim')}</span><b class="num">${fs(avgScore(subs,m))}</b><span class="sub">${tx('{0} orang di bawah Anda',subs.length)}</span></div>
    <div class="card tile"><span class="eyebrow">${tx('Memenuhi target')}</span><b class="num">${ts.filter(x=>x!=null&&x>=90).length}</b><span class="sub">${tx('skor ≥ 90')}</span></div>
    <div class="card tile"><span class="eyebrow">${tx('Perlu perhatian')}</span><b class="num" style="color:${low?'var(--r4)':'inherit'}">${low}</b><span class="sub">${tx('skor di bawah 90')}</span></div>
    <div class="card tile clickrow" data-act="go" data-page="approve"><span class="eyebrow">${tx('Menunggu persetujuan')}</span><b class="num" style="color:${pend?'var(--gold)':'inherit'}">${pend}</b><span class="sub">${tx('realisasi dari bawahan langsung')}</span></div></div>`}
  const series=[{name:tx('Skor saya'),color:'var(--accent)',values:MON.map((_,i)=>score(u.id,i)),area:true}];
  if(subs.length)series.push({name:tx('Rata-rata tim'),color:'var(--gold)',values:MON.map((_,i)=>avgScore(subs,i)),dash:true,w:2});
  const right=direct.length?`<div class="card"><div class="card-h"><div><h2>${tx('Skor bawahan langsung')}</h2><p>${MONF[m]} · ${tx('garis tipis = target 100')}</p></div></div>${barsPeople(direct.slice().sort((a,b)=>(score(b.id,m)??-1)-(score(a.id,m)??-1)),m)}</div>`
    :`<div class="card"><div class="card-h"><div><h2>${tx('Capaian KPI saya')}</h2><p>${MONF[m]}</p></div></div><div class="bars">${ks.map(k=>{const a=ach(k,m),r=rating(a);return `<div class="bar-row" data-act="go" data-page="mine"><div class="lbl">${esc(k.name)}</div><div class="val num" style="color:var(--${r.key==='na'?'muted':r.key})">${a==null?'–':fmt(a,0)+'%'}</div>${track(a)}</div>`}).join('')}</div></div>`;
  html+=`<div class="cols"><div class="card"><div class="card-h"><div><h2>${tx('Tren skor {0}',S.year)}</h2><p>${tx('Skor tertimbang bulanan, skala 0–{0}',S.cap)}</p></div></div>${lineChart(series,m)}</div>${right}</div>`;
  // KPI perlu perhatian
  const scope=[u,...subs];const att=[];
  scope.forEach(p=>kpisOf(p.id).forEach(k=>{const a=ach(k,m);if(a!=null&&a<90)att.push({p,k,a})}));
  att.sort((a,b)=>a.a-b.a);
  html+=`<div class="card"><div class="card-h"><div><h2>${tx('KPI yang perlu perhatian')}</h2><p>${subs.length?tx('Capaian di bawah 90% pada {0} di seluruh tim Anda',MONF[m]):tx('Capaian di bawah 90% pada {0}',MONF[m])}</p></div><span class="tag">${att.length} KPI</span></div>
  ${att.length?`<div class="tbl-wrap"><table><thead><tr><th>KPI</th><th>${tx('Pemilik')}</th><th class="r">${tx('Target')}</th><th class="r">${tx('Realisasi')}</th><th>${tx('Capaian')}</th></tr></thead><tbody>
  ${att.slice(0,10).map(({p,k,a})=>`<tr class="clickrow" data-act="person" data-id="${p.id}"><td class="kname"><b>${esc(k.name)}</b></td><td><div class="pname">${av(p)}<div><b>${esc(p.name)}</b><span>${esc(p.dept)}</span></div></div></td>
  <td class="r num">${fmt(k.target,decOf(k))} <span class="muted small">${esc(k.unit)}</span></td><td class="r num">${fmt(actual(k,m),decOf(k))}</td><td class="achcell"><b class="num" style="color:var(--${rating(a).key})">${fmt(a,1)}%</b>${track(a)}</td></tr>`).join('')}</tbody></table></div>`:`<div class="empty">${tx('Semua KPI berjalan sesuai target bulan ini.')}</div>`}</div>`;
  return {eyebrow:tx('Dashboard'),title:tx('Beranda'),html};
}

/* ===== Tabel KPI ===== */
function kpiTable(owner,opt={}){
  const m=P.month,v=me(),ks=kpisOf(owner.id);
  const editDef=canDef(v,owner.id), input=opt.input, isCeo=owner.level==='ceo';
  const approve=owner.parentId===v.id;
  if(!ks.length)return `<div class="empty">${tx('Belum ada KPI untuk {0}.',esc(owner.name))} ${editDef?tx('Klik "Tambah KPI" untuk membuat.'):tx('KPI akan ditetapkan oleh atasan.')}</div>`;
  let tot=0;
  const rows=ks.map(k=>{
    const e=entry(k.id,m),a=ach(k,m),val=actual(k,m),d=decOf(k),par=k.parentId&&kpiById(k.parentId),kids=childKpis(k.id);
    if(a!=null)tot+=a*k.weight/100;
    const status=k.rollup!=='manual'?`<span class="st auto">${tx('Otomatis')}</span>`:e?`<span class="st ${e.status}">${tx(ST_LABEL[e.status]||e.status)}</span>`:`<span class="st draft">${tx('Belum diisi')}</span>`;
    const editable=input&&k.rollup==='manual'&&(!e||e.status!=='approved'||isCeo);
    const valCell=editable?`<input class="inp-act" type="number" step="any" inputmode="decimal" data-kin="${k.id}" id="in-${k.id}" value="${e&&e.v!=null?e.v:''}" placeholder="${tx('isi')}">`:`<b class="num">${fmt(val,d)}</b>`;
    let act='';
    if(editDef)act+=`<button class="btn sm ghost" data-act="kpi-edit" data-id="${k.id}" title="${tx('Ubah KPI')}" aria-label="${tx('Ubah KPI')}">${ico('edit')}</button>`;
    if(approve&&e&&e.status==='submitted')act+=`<button class="btn sm good" data-act="appr" data-k="${k.id}" data-m="${m}">${tx('Setujui')}</button> <button class="btn sm" data-act="rej" data-k="${k.id}" data-m="${m}">${tx('Kembalikan')}</button>`;
    return `<tr><td class="kname"><b>${esc(k.name)}</b><div class="meta"><span class="tag">${PERSP[k.persp]?tx(PERSP[k.persp]):esc(k.persp)}</span>${k.rollup!=='manual'?`<span class="tag auto">${k.rollup==='sum'?tx('Jumlah dari {0} KPI turunan',kids.length):tx('Rata-rata dari {0} KPI turunan',kids.length)}</span>`:''}${kids.length&&k.rollup==='manual'?`<span class="tag">${tx('{0} KPI turunan',kids.length)}</span>`:''}</div>
      ${par?`<div class="up">${tx('Mendukung: {0}',`${esc(par.name)} (${esc(userById(par.ownerId)?.name||'')})`)}</div>`:''}${e&&e.status==='rejected'&&e.note?`<div class="note">${tx('Catatan atasan: {0}',esc(e.note))}</div>`:''}</td>
      <td class="r num">${fmt(k.weight)}%</td><td class="r"><b class="num">${fmt(k.target,d)}</b> <span class="muted small">${esc(k.unit)}</span><div class="pol">${k.polarity==='max'?tx('↑ makin tinggi makin baik'):tx('↓ makin rendah makin baik')}</div></td>
      <td class="r">${valCell}</td><td class="achcell"><b class="num" style="color:var(--${rating(a).key==='na'?'muted':rating(a).key})">${a==null?'–':fmt(a,1)+'%'}</b>${track(a)}</td>
      <td class="r num">${a==null?'–':fmt(a*k.weight/100,1)}</td><td>${status}</td><td class="r" style="white-space:nowrap">${act}</td></tr>`}).join('');
  const ws=weightSum(owner.id),s=score(owner.id,m);
  return `${ws!==100?`<div class="warnbox" style="margin-bottom:12px">${tx('Total bobot KPI {0}%. Sebaiknya tepat 100%.',fmt(ws))}</div>`:''}
  <div class="tbl-wrap"><table><thead><tr><th>KPI</th><th class="r">${tx('Bobot')}</th><th class="r">${tx('Target / bln')}</th><th class="r">${tx('Realisasi {0}',MON[m])}</th><th>${tx('Capaian')}</th><th class="r">${tx('Skor')}</th><th>${tx('Status')}</th><th></th></tr></thead>
  <tbody>${rows}</tbody><tfoot><tr><td>${tx('Total')}</td><td class="r num">${fmt(ws)}%</td><td></td><td></td><td>${pill(s)}</td><td class="r num">${fs(s)}</td><td colspan="2"></td></tr></tfoot></table></div>`;
}

/* ===== Halaman: KPI Saya ===== */
function pageMine(){
  const u=me(),m=P.month,s=score(u.id,m),hasManual=kpisOf(u.id).some(k=>k.rollup==='manual'),isCeo=u.level==='ceo';
  const ks=kpisOf(u.id), pend=ks.filter(k=>entry(k.id,m)?.status==='submitted').length;
  let html=`<div class="card"><div class="card-h"><div><h2>KPI ${esc(u.name)} · ${monY(m)}</h2><p>${tx('Isi realisasi bulan ini lalu ajukan ke atasan untuk disetujui. KPI bertanda "Otomatis" dihitung dari KPI bawahan.')}</p></div>
    <div class="row" style="align-items:center;gap:12px">${ring(s,true)}${canDef(u,u.id)?`<button class="btn" data-act="kpi-new" data-owner="${u.id}">${ico('plus')} ${tx('Tambah KPI')}</button>`:''}</div></div>
    ${kpiTable(u,{input:true})}
    ${hasManual?`<div class="actions-bar"><div class="grow">${isCeo?tx('Sebagai CEO, realisasi Anda langsung tercatat sebagai disetujui.'):pend?tx('{0} realisasi sedang menunggu persetujuan atasan.',pend):tx('Simpan draf jika belum final. Realisasi yang sudah disetujui tidak dapat diubah.')}</div>
    ${isCeo?`<button class="btn primary" data-act="save-act" data-mode="approved">${tx('Simpan realisasi')}</button>`:`<button class="btn" data-act="save-act" data-mode="draft">${tx('Simpan draf')}</button><button class="btn primary" data-act="save-act" data-mode="submitted">${tx('Ajukan ke atasan')}</button>`}</div>`:''}
  </div>`;
  html+=`<div class="card"><div class="card-h"><div><h2>${tx('Riwayat skor bulanan')}</h2><p>${tx('Rata-rata {0} sampai {1}:',S.year,MON[m])} <b class="num">${fs(ytd(u.id,m))}</b></p></div></div>${lineChart([{name:tx('Skor saya'),color:'var(--accent)',values:MON.map((_,i)=>score(u.id,i)),area:true}],m)}</div>`;
  return {eyebrow:lvlLabel(u)+' · '+esc(u.dept),title:tx('KPI Saya'),html};
}

/* ===== Halaman: Detail orang ===== */
function pagePerson(){
  const v=me(),u=userById(P.view);
  if(!u||!inScope(v.id,u.id)){P.page='team';return pageTeam()}
  const m=P.month,s=score(u.id,m),chain=ancestors(u.id).reverse().filter(a=>inScope(v.id,a.id));
  const kids=childrenUsers(u.id);
  let html=`<div class="crumbs">${chain.map(a=>`<a data-act="person" data-id="${a.id}">${esc(a.name)}</a><span>›</span>`).join('')}<span>${esc(u.name)}</span></div>
  <div class="card hero">${ring(s)}<div class="info"><div class="eyebrow">${lvlLabel(u)} · ${esc(u.dept)}</div><h2>${esc(u.name)}</h2><div class="muted">${esc(u.title)}</div>
  <div>${pill(s)}</div><div class="hero-stats"><div><b class="num">${fs(ytd(u.id,m))}</b><span>${tx('Rata-rata {0}',MON[0]+'–'+MON[m])}</span></div><div><b class="num">${kpisOf(u.id).length}</b><span>KPI</span></div>${kids.length?`<div><b class="num">${fs(avgScore(descendants(u.id),m))}</b><span>${tx('Rata-rata timnya')}</span></div>`:''}</div></div>
  ${canDef(v,u.id)?`<button class="btn" data-act="kpi-new" data-owner="${u.id}">${ico('plus')} ${tx('Tambah KPI')}</button>`:''}</div>
  <div class="card"><div class="card-h"><div><h2>${tx('Daftar KPI')} · ${monY(m)}</h2><p>${canDef(v,u.id)?tx('Anda dapat mengubah KPI ini dan menurunkannya dari KPI atasannya.'):''}</p></div></div>${kpiTable(u)}</div>
  <div class="cols"><div class="card"><div class="card-h"><div><h2>${tx('Tren skor')}</h2></div></div>${lineChart([{name:u.name,color:'var(--accent)',values:MON.map((_,i)=>score(u.id,i)),area:true}],m)}</div>
  ${kids.length?`<div class="card"><div class="card-h"><div><h2>${tx('Bawahan langsung')}</h2><p>${MONF[m]}</p></div></div>${barsPeople(kids,m)}</div>`:`<div class="card"><div class="card-h"><h2>${tx('Profil')}</h2></div><div class="small" style="display:grid;gap:8px"><div><span class="muted">${tx('Atasan:')}</span> ${esc(userById(u.parentId)?.name||'–')}</div><div><span class="muted">${tx('Unit:')}</span> ${esc(u.dept)}</div><div><span class="muted">${tx('Level:')}</span> ${lvlLabel(u)}</div></div></div>`}</div>`;
  return {eyebrow:tx('Detail kinerja'),title:esc(u.name),html};
}

/* ===== Halaman: Tim ===== */
function pageTeam(){
  const u=me(),m=P.month,wide=wideOn(u),subs=wide?everyone():descendants(u.id),hasTeam=childrenUsers(u.id).length>0;
  const rows=subs.map(p=>{const s=score(p.id,m),ws=weightSum(p.id);return `<tr class="clickrow team-row" data-act="person" data-id="${p.id}" data-q="${esc((p.name+' '+p.title+' '+p.dept).toLowerCase())}" style="--d:${p._d-1}">
    <td><div class="pname">${av(p)}<div><b>${esc(p.name)}</b><span>${esc(p.title)}</span></div></div></td><td><span class="tag">${lvlLabel(p)}</span></td>
    <td class="r num">${kpisOf(p.id).length}${ws!==100?` <span class="tag gold" title="${tx('Total bobot')}">${ws}%</span>`:''}</td><td class="r num"><b>${fs(s)}</b></td><td class="r num">${fs(ytd(p.id,m))}</td><td>${pill(s)}</td></tr>`}).join('');
  const html=`<div class="card"><div class="card-h"><div><h2>${wide?tx('Semua anggota'):tx('Anggota tim')} (${subs.length})</h2><p>${wide?tx('Role Anda ({0}) bisa melihat KPI semua anggota.',roleLabel(u))+' ':''}${wide&&!canManage(u)?tx('Klik nama untuk melihat KPI-nya.'):tx('Klik nama untuk melihat dan mengatur KPI-nya.')}</p></div><div class="row" style="gap:8px;align-items:center;flex-wrap:wrap">${hasTeam?wideSel(u):''}<input class="search" id="team-q" type="search" placeholder="${tx('Cari nama atau unit…')}" aria-label="${tx('Cari anggota')}"></div></div>
  <div class="tbl-wrap"><table><thead><tr><th>${tx('Nama')}</th><th>${tx('Level')}</th><th class="r">KPI</th><th class="r">${tx('Skor {0}',MON[m])}</th><th class="r">${tx('Rata-rata')}</th><th>${tx('Status')}</th></tr></thead><tbody id="team-body">${rows}</tbody></table></div></div>`;
  return {eyebrow:wide?esc(S.company):esc(u.dept),title:wide?tx('Semua Anggota'):tx('Tim Saya'),html};
}

/* ===== Halaman: Pohon KPI ===== */
function pageTree(){
  const u=me(),m=P.month;
  const node=(k,depth)=>{const o=userById(k.ownerId),kids=childKpis(k.id).filter(c=>inScope(u.id,c.ownerId)),a=ach(k,m),r=rating(a),d=decOf(k);
    return `<details ${depth<2?'open':''}><summary><div class="node"><span class="caret ${kids.length?'':'none'}">${ico('caret')}</span>
    <span class="lvl-dot" style="background:var(--${r.key==='na'?'line':r.key})"></span>
    <div class="kn"><b>${esc(k.name)}</b><span>${esc(o?.name||'')} · ${lvlLabel(o||{level:''})}${k.rollup!=='manual'?` · <span style="color:var(--accent)">${k.rollup==='sum'?tx('jumlah otomatis'):tx('rata-rata otomatis')}</span>`:''}</span></div>
    <div class="figs"><div><small>${tx('Target')}</small><b class="num">${fmt(k.target,d)}</b> <span class="muted small">${esc(k.unit)}</span></div><div><small>${tx('Realisasi')}</small><b class="num">${fmt(actual(k,m),d)}</b></div><div style="min-width:64px"><small>${tx('Capaian')}</small><b class="num" style="color:var(--${r.key==='na'?'muted':r.key})">${a==null?'–':fmt(a,0)+'%'}</b></div></div></div></summary>
    ${kids.map(c=>node(c,depth+1)).join('')}</details>`};
  const wide=wideOn(u),top=wide?S.users.filter(x=>!x.parentId):[u];
  const roots=top.flatMap(x=>kpisOf(x.id));
  const ups=wide?[]:roots.map(k=>k.parentId&&kpiById(k.parentId)).filter(Boolean);
  const html=`<div class="card"><div class="card-h"><div><h2>${tx('Penurunan KPI dari {0}',top.map(x=>esc(x.name)).join(', '))}</h2><p>${tx('Setiap KPI diturunkan ke bawahan. KPI bertanda "otomatis" menjumlahkan atau merata-ratakan realisasi KPI turunannya. Klik baris untuk buka/tutup.')}</p></div>
  <div class="row" style="gap:12px;align-items:center;flex-wrap:wrap">${wideSel(u)}<div class="legend" style="margin:0"><span><i style="background:var(--r1)"></i>≥100%</span><span><i style="background:var(--r2)"></i>90–99%</span><span><i style="background:var(--r3)"></i>75–89%</span><span><i style="background:var(--r4)"></i>&lt;75%</span></div></div></div>
  ${ups.length?`<div class="small muted" style="margin-bottom:8px">${tx('KPI Anda mendukung: {0}',[...new Set(ups.map(p=>p.name+' ('+(userById(p.ownerId)?.name||'')+')'))].map(esc).join(', '))}</div>`:''}
  <div class="tree">${roots.length?roots.map(k=>node(k,0)).join(''):`<div class="empty">${tx('Belum ada KPI.')}</div>`}</div></div>`;
  return {eyebrow:tx('Cascading'),title:tx('Pohon KPI'),html};
}

/* ===== Halaman: Struktur Organisasi ===== */
function pageOrg(){
  const v=me(),m=P.month,isCeo=canManage(v),meCeo=v.level==='ceo';
  const pc=(u,cls='')=>{const sc=inScope(v.id,u.id);const s=sc?score(u.id,m):null;
    return `<div class="pc ${cls} ${sc?'click':''}" ${sc?`data-act="person" data-id="${u.id}"`:''}>${cls==='ceo'||cls==='div'?`<span class="lvl">${lvlLabel(u)}</span>`:''}${av(u)}<div class="t"><b>${esc(u.name)}</b><span>${esc(u.title)}</span></div>
    ${sc?`<span class="sc ${rating(s).key} num">${fs(s)}</span>`:''}${isCeo&&(meCeo||u.level!=='ceo')?`<button class="edit" data-act="user-edit" data-id="${u.id}" aria-label="${tx('Ubah {0}',esc(u.name))}">${ico('edit')}</button>`:''}</div>`};
  const roots=S.users.filter(u=>!u.parentId);
  let html=`<div class="card"><div class="card-h"><div><h2>${esc(S.company)}</h2><p>${tx('{0} orang · {1} divisi · {2} unit.',S.users.length,S.users.filter(u=>u.level==='div').length,S.users.filter(u=>u.level==='unit').length)} ${viewAll(v)?'':tx('Skor hanya tampil untuk Anda dan tim Anda.')}</p></div>
  ${isCeo?`<button class="btn primary" data-act="user-new">${ico('plus')} ${tx('Tambah anggota')}</button>`:''}</div><div style="overflow-x:auto"><div class="org">`;
  roots.forEach(r=>{const divs=childrenUsers(r.id);
    html+=`<div class="org-top">${pc(r,'ceo')}</div>${divs.length?`<div class="org-stem"></div><div class="org-divs" style="--n:${Math.max(1,divs.length)}">${divs.map(d=>{
      const units=childrenUsers(d.id);
      return `<div class="org-col">${pc(d,'div')}${units.map(un=>`<div class="unit-box">${pc(un)}${childrenUsers(un.id).length?`<div class="staff">${childrenUsers(un.id).map(s=>pc(s)).join('')}</div>`:''}</div>`).join('')}</div>`}).join('')}</div>`:''}`});
  html+=`</div></div></div>`;
  return {eyebrow:tx('Organisasi'),title:tx('Struktur Organisasi'),html};
}

/* ===== Halaman: Persetujuan ===== */
function pageApprove(){
  const u=me(),list=pendingFor(u.id);
  const by={};list.forEach(x=>(by[x.u.id]=by[x.u.id]||[]).push(x));
  const html=Object.keys(by).length?Object.values(by).map(items=>{const p=items[0].u;return `<div class="card"><div class="card-h"><div class="pname">${av(p)}<div><b>${esc(p.name)}</b><span>${esc(p.title)}</span></div></div>
    <button class="btn good sm" data-act="appr-all" data-id="${p.id}">${tx('Setujui semua')} (${items.length})</button></div>
    <div class="tbl-wrap"><table><thead><tr><th>KPI</th><th>${tx('Periode')}</th><th class="r">${tx('Target')}</th><th class="r">${tx('Realisasi')}</th><th>${tx('Capaian')}</th><th></th></tr></thead><tbody>
    ${items.sort((a,b)=>a.m-b.m).map(({k,m,e})=>{const a0=ach(k,m),d=decOf(k);return `<tr><td class="kname"><b>${esc(k.name)}</b><div class="up">${tx('Diajukan {0}',esc(e.at||''))}</div></td><td>${MONF[m]}</td><td class="r num">${fmt(k.target,d)} <span class="muted small">${esc(k.unit)}</span></td><td class="r num"><b>${fmt(e.v,d)}</b></td>
    <td class="achcell"><b class="num" style="color:var(--${rating(a0).key})">${fmt(a0,1)}%</b>${track(a0)}</td><td class="r" style="white-space:nowrap"><button class="btn sm good" data-act="appr" data-k="${k.id}" data-m="${m}">${tx('Setujui')}</button> <button class="btn sm" data-act="rej" data-k="${k.id}" data-m="${m}">${tx('Kembalikan')}</button></td></tr>`}).join('')}</tbody></table></div></div>`}).join('')
    :`<div class="card empty">${tx('Tidak ada realisasi yang menunggu persetujuan Anda.')}</div>`;
  return {eyebrow:tx('Alur kerja'),title:tx('Persetujuan Realisasi'),html,noPeriod:true};
}

/* ===== Halaman: Laporan ===== */
function reportPeople(u){return wideOn(u)?everyone():[u,...descendants(u.id)]}
function pageReport(){
  const u=me(),m=P.month,lv=P.rlv||'all',all=reportPeople(u);
  const list=all.filter(p=>lv==='all'||p.level===lv);
  const cnt={r1:0,r2:0,r3:0,r4:0,na:0};list.forEach(p=>cnt[rating(score(p.id,m)).key]++);
  const n=list.length||1;
  const levels=['all',...['ceo','div','unit','staff'].filter(l=>all.some(p=>p.level===l))];
  const html=`<div class="card"><div class="card-h"><div><h2>${tx('Sebaran penilaian')} · ${MONF[m]}</h2><p>${tx('{0} orang',list.length)}</p></div>
    <div class="row" style="gap:8px;align-items:center;flex-wrap:wrap">${wideSel(u)}<select class="inline" id="rlv" aria-label="${tx('Filter level')}">${levels.map(l=>`<option value="${l}" ${l===lv?'selected':''}>${l==='all'?tx('Semua level'):lvlName(l)}</option>`).join('')}</select></div></div>
    <div class="stack">${['r1','r2','r3','r4','na'].map(k=>cnt[k]?`<i class="fill-${k}" style="width:${cnt[k]/n*100}%" title="${rating({r1:110,r2:95,r3:80,r4:50,na:null}[k]).label}: ${cnt[k]}"></i>`:'').join('')}</div>
    <div class="legend">${[['r1',tx('Melampaui (≥100)')],['r2',tx('Memenuhi (90–99)')],['r3',tx('Perlu perhatian (75–89)')],['r4',tx('Di bawah target (<75)')],['na',tx('Belum ada data')]].map(([k,l])=>`<span><i class="fill-${k}"></i>${l}: <b>${cnt[k]}</b></span>`).join('')}</div></div>
  <div class="card"><div class="card-h"><div><h2>${tx('Skor bulanan {0}',S.year)}</h2><p>${tx('Klik nama untuk detail. Tombol salin menyalin tabel agar bisa ditempel ke Excel.')}</p></div><button class="btn" data-act="copy-report">${ico('copy')} ${tx('Salin tabel')}</button></div>
  <div class="tbl-wrap"><table class="heat"><thead><tr><th>${tx('Nama')}</th>${MON.map(x=>`<th class="c">${x}</th>`).join('')}<th class="c">${tx('Rata² s.d. {0}',MON[m])}</th></tr></thead><tbody>
  ${list.map(p=>`<tr class="clickrow" data-act="person" data-id="${p.id}"><td><div class="pname">${av(p)}<div><b>${esc(p.name)}</b><span>${esc(p.dept)}</span></div></div></td>${MON.map((_,i)=>{const s=score(p.id,i);return `<td class="h num ${rating(s).key}">${s==null?'·':fmt(s,0)}</td>`}).join('')}<td class="h num ${rating(ytd(p.id,P.month)).key}"><b>${fs(ytd(p.id,P.month))}</b></td></tr>`).join('')}
  </tbody></table></div></div>`;
  return {eyebrow:wideOn(u)?esc(S.company):tx('Analisis'),title:tx('Laporan Kinerja'),html};
}

/* ===== Halaman: Pengaturan ===== */
function pageSettings(){
  const u=me(),isCeo=canManage(u);
  let html=`<div class="card"><div class="card-h"><div><h2>${tx('Ganti password')}</h2><p>${tx('Username Anda: {0}.',`<b>${esc(unameOf(u))}</b>`)} ${tx('Username dan password dipakai untuk masuk ke aplikasi.')}</p></div></div>
    <label class="field" style="max-width:320px">${tx('Password lama')}<input id="pin0" type="password" maxlength="64" autocomplete="current-password"></label>
    <div class="grid2"><label class="field">${tx('Password baru')}<input id="pin1" type="password" maxlength="64" autocomplete="new-password"></label><label class="field">${tx('Ulangi password baru')}<input id="pin2" type="password" maxlength="64" autocomplete="new-password"></label></div>
    <div class="err" id="pin-err"></div>
    <div class="actions-bar"><button class="btn primary" data-act="pin-save">${tx('Simpan password')}</button></div></div>`;
  if(isCeo)html+=`<div class="card"><div class="card-h"><div><h2>${tx('Perusahaan & penilaian')}</h2></div></div><div class="grid2">
    <label class="field">${tx('Nama perusahaan')}<input id="set-co" value="${esc(S.company)}"></label>
    <label class="field">${tx('Batas maksimal capaian per KPI (%)')}<input id="set-cap" type="number" min="100" max="200" value="${S.cap}"><span class="hint">${tx('Capaian di atas batas ini dihitung sama dengan batas. Umumnya 110–120%.')}</span></label>
    <label class="field">${tx('Tahun penilaian')}<input id="set-year" type="number" min="2000" max="2100" value="${S.year}"><span class="hint">${tx('Mengubah tahun akan menampilkan periode tahun tersebut.')}</span></label>
    ${S.demoMode?`<label class="field" style="justify-content:flex-end"><span style="display:flex;gap:8px;align-items:center;font-weight:600;color:var(--fg)"><input type="checkbox" id="set-demo" checked style="width:18px;height:18px;accent-color:var(--accent)">${tx('Tampilkan akun contoh di halaman masuk')}</span><span class="hint">${tx('Matikan sebelum aplikasi dipakai sungguhan.')}</span></label>`:''}</div>
    <div class="actions-bar"><button class="btn primary" data-act="set-save">${tx('Simpan')}</button></div>
    <div class="small muted" style="margin-top:10px">${tx('Rumus capaian: KPI ↑ = realisasi ÷ target × 100%. KPI ↓ = target ÷ realisasi × 100%. Skor orang = rata-rata tertimbang capaian dengan bobot KPI.')} ${tx('Kategori: ≥100 Melampaui, 90–99 Memenuhi, 75–89 Perlu Perhatian, <75 Di Bawah Target.')}</div></div>
  <div class="card"><div class="card-h"><div><h2>${tx('Cadangan data')}</h2><p>${tx('Data tersimpan di database server. Unduh salinan sewaktu-waktu, dan gunakan juga fasilitas cadangan database di panel hosting Anda.')}</p></div></div>
    <div class="actions-bar"><a class="btn" href="api.php?action=export" download>${ico('copy')} ${tx('Unduh cadangan (JSON)')}</a></div></div>`;
  return {eyebrow:esc(u.name),title:tx('Pengaturan'),html,noPeriod:true};
}

/* ===== Modal ===== */
function openModal(html){$('#modal').innerHTML=`<div class="modal-bg" data-act="modal-bg"><div class="modal" role="dialog" aria-modal="true">${html}</div></div>`;const f=$('#modal input,#modal select');if(f)f.focus()}
function closeModal(){$('#modal').innerHTML=''}
function kpiForm(k,ownerId){
  const owner=userById(ownerId),sup=userById(owner.parentId),pk=sup?kpisOf(sup.id):[];
  k=k||{name:'',persp:'fin',unit:'',target:'',polarity:'max',weight:'',parentId:'',rollup:'manual',desc:''};
  const used=weightSum(ownerId)-(+k.weight||0);
  openModal(`<div><div class="eyebrow">${k.id?tx('Ubah KPI'):tx('KPI baru')} · ${esc(owner.name)}</div><h2 style="font-size:18px;margin-top:4px">${k.id?esc(k.name):tx('Tambah KPI')}</h2></div>
  <label class="field">${tx('Nama KPI')}<input id="f-name" value="${esc(k.name)}" placeholder="${tx('mis. Penjualan Individu')}"></label>
  <label class="field">${tx('Diturunkan dari KPI atasan')}${sup?` (${esc(sup.name)})`:''}<select id="f-parent" ${sup?'':'disabled'}><option value="">— ${tx('Tidak terhubung')} —</option>${pk.map(p=>`<option value="${p.id}" ${p.id===k.parentId?'selected':''}>${esc(p.name)}</option>`).join('')}</select><span class="hint">${tx('Menghubungkan KPI ini ke KPI atasan agar terlihat di Pohon KPI dan bisa diakumulasi.')}</span></label>
  <div class="grid2"><label class="field">${tx('Perspektif')}<select id="f-persp">${Object.entries(PERSP).map(([a,b])=>`<option value="${a}" ${a===k.persp?'selected':''}>${tx(b)}</option>`).join('')}</select></label>
  <label class="field">${tx('Arah penilaian')}<select id="f-pol"><option value="max" ${k.polarity==='max'?'selected':''}>${tx('↑ Makin tinggi makin baik')}</option><option value="min" ${k.polarity==='min'?'selected':''}>${tx('↓ Makin rendah makin baik')}</option></select></label>
  <label class="field">${tx('Target per bulan')}<input id="f-target" type="number" step="any" value="${k.target}"></label>
  <label class="field">${tx('Satuan')}<input id="f-unit" value="${esc(k.unit)}" placeholder="${tx('mis. Rp juta, %, unit')}" list="units"><datalist id="units">${UNIT_SUGGEST.map(x=>`<option value="${esc(tx(x))}">`).join('')}</datalist></label>
  <label class="field">${tx('Bobot (%)')}<input id="f-weight" type="number" min="0" max="100" value="${k.weight}"><span class="hint">${tx('Bobot KPI lain: {0}%. Sisa: {1}%.',used,100-used)}</span></label>
  <label class="field">${tx('Cara mengisi realisasi')}<select id="f-roll"><option value="manual" ${k.rollup==='manual'?'selected':''}>${tx('Diisi manual')}</option><option value="sum" ${k.rollup==='sum'?'selected':''}>${tx('Otomatis: jumlah KPI turunan')}</option><option value="avg" ${k.rollup==='avg'?'selected':''}>${tx('Otomatis: rata-rata KPI turunan')}</option></select></label></div>
  <label class="field">${tx('Definisi / cara ukur (opsional)')}<textarea id="f-desc">${esc(k.desc||'')}</textarea></label>
  <div class="err" id="f-err"></div>
  <div class="foot">${k.id?`<button class="btn danger left" data-act="kpi-del" data-id="${k.id}">${tx('Hapus KPI')}</button>`:''}<button class="btn" data-act="close">${tx('Batal')}</button><button class="btn primary" data-act="kpi-save" data-id="${k.id||''}" data-owner="${ownerId}">${tx('Simpan KPI')}</button></div>`)}
function userForm(u){
  const isNew=!u;u=u||{name:'',title:'',parentId:'',dept:'',level:''};
  let opts;
  if(isNew)opts=S.users.filter(x=>x.level!=='staff');
  else if(!u.parentId)opts=[];
  else{const pl=userById(u.parentId)?.level;opts=S.users.filter(x=>x.level===pl)}
  openModal(`<div><div class="eyebrow">${isNew?tx('Anggota baru'):tx('Ubah anggota')}</div><h2 style="font-size:18px;margin-top:4px">${isNew?tx('Tambah anggota organisasi'):esc(u.name)}</h2></div>
  <div class="grid2"><label class="field">${tx('Nama lengkap')}<input id="u-name" value="${esc(u.name)}"></label><label class="field">${tx('Jabatan')}<input id="u-title" value="${esc(u.title)}" placeholder="${tx('mis. Sales Executive')}"></label></div>
  ${opts.length||isNew?`<label class="field">${tx('Atasan langsung')}<select id="u-parent">${opts.map(o=>`<option value="${o.id}" ${o.id===u.parentId?'selected':''}>${esc(o.name)} – ${lvlLabel(o)}, ${esc(o.dept)}</option>`).join('')}</select><span class="hint">${isNew?tx('Level otomatis satu tingkat di bawah atasan ({0}).',['ceo','div','unit','staff'].map(lvlName).join(isRtl()?' ← ':' → ')):tx('Atasan bisa dipindah ke orang lain di level yang sama.')}</span></label>`:''}
  <div class="grid2"><label class="field">${tx('Divisi / unit')}<input id="u-dept" value="${esc(u.dept)}" placeholder="${tx('mis. Unit Penjualan Retail')}"></label><label class="field">${tx('Username')}<input id="u-uname" value="${isNew?'':esc(unameOf(u))}" autocapitalize="none" spellcheck="false" placeholder="${tx('otomatis dari nama, mis. budi.santoso')}"></label></div>
  <label class="field">${tx('Password')}<input id="u-pin" type="password" value="" maxlength="64" autocomplete="new-password" placeholder="${isNew?tx('minimal 4 karakter'):tx('kosongkan jika tidak diubah')}"><span class="hint">${isNew?tx('Username dan password ini dipakai anggota untuk masuk.'):tx('Isi hanya bila ingin mengganti password anggota ini.')}</span></label>
  <div class="err" id="u-err"></div>
  <div class="foot">${!isNew&&u.parentId?`<button class="btn danger left" data-act="user-del" data-id="${u.id}">${tx('Hapus anggota')}</button>`:''}<button class="btn" data-act="close">${tx('Batal')}</button><button class="btn primary" data-act="user-save" data-id="${isNew?'':u.id}">${tx('Simpan')}</button></div>`)}

/* ===== Render ===== */
const PAGES={home:pageHome,mine:pageMine,team:pageTeam,person:pagePerson,tree:pageTree,org:pageOrg,approve:pageApprove,report:pageReport,settings:pageSettings};
function render(){cache.clear();const u=me();
  if(!u){$('#app').innerHTML=viewLogin();return}
  const allowed=navItems(u).map(x=>x[0]).concat('person');if(!allowed.includes(P.page))P.page='home';
  $('#app').innerHTML=shell(PAGES[P.page]());savePref()}
function go(page,view){P.page=page;if(view!==undefined)P.view=view;render();window.scrollTo(0,0)}

/* ===== Aksi ===== */
const A={
  login(){const un=$('#lg-user').value.trim(),pw=$('#lg-pin').value,err=$('#lg-err'),btn=$('.lg-btn');
    err.textContent='';
    if(!un||!pw){err.textContent=t('err');return}
    if(busy)return;busy=true;btn.disabled=true;
    api('login',{username:un,password:pw})
      .then(()=>{P.page='home';P.view=null;render();toast(tx('Selamat datang, {0}',me().name))})
      .catch(e=>{err.textContent=e.message;btn.disabled=false})
      .finally(()=>{busy=false})},
  demo(el){$('#lg-user').value=el.dataset.u;$('#lg-pin').value=el.dataset.p;A.login()},
  eye(){const i=$('#lg-pin');i.type=i.type==='password'?'text':'password';i.focus()},
  forgot(){const i=$('#lg-info');i.hidden=!i.hidden},
  logout(){run(async()=>{try{await api('logout')}catch(e){}S=null;P.page='home';P.view=null;savePref();render()})},
  go(el){go(el.dataset.page)},
  person(el){if(el.dataset.id===S.meId)return go('mine');go('person',el.dataset.id)},
  'save-act'(el){const mode=el.dataset.mode==='submitted'?'submitted':'draft',m=P.month,items=[];let bad=false;
    $$('[data-kin]').forEach(inp=>{inp.style.borderColor='';const raw=inp.value.trim();
      if(raw===''){items.push({kpiId:inp.dataset.kin,value:null});return}
      const v=Number(raw.replace(',','.'));
      if(isNaN(v)||v<0){bad=true;inp.style.borderColor='var(--r4)';return}
      items.push({kpiId:inp.dataset.kin,value:v})});
    if(bad){toast(tx('Ada nilai yang tidak valid. Gunakan angka, misalnya 1250 atau 95,5.'));return}
    run(async()=>{const r=await api('save_actuals',{year:S.year,month:m,mode,items});render();
      toast(r.saved?(mode==='submitted'?tx('{0} realisasi diajukan ke atasan',r.saved):tx('{0} realisasi tersimpan',r.saved)):tx('Belum ada realisasi yang diisi'))})},
  appr(el){run(async()=>{await api('approve',{kpiId:el.dataset.k,month:+el.dataset.m,year:S.year});render();toast(tx('Realisasi disetujui'))})},
  'appr-all'(el){run(async()=>{const r=await api('approve_all',{userId:el.dataset.id});render();toast(tx('{0} realisasi disetujui',r.saved))})},
  rej(el){const k=kpiById(el.dataset.k),m=+el.dataset.m;
    openModal(`<div><div class="eyebrow">${tx('Kembalikan realisasi')}</div><h2 style="font-size:18px;margin-top:4px">${esc(k.name)} · ${MONF[m]}</h2></div>
    <label class="field">${tx('Catatan untuk {0}',esc(userById(k.ownerId).name))}<textarea id="rej-note" placeholder="${tx('mis. Mohon lampirkan bukti pendukung.')}"></textarea></label>
    <div class="err" id="rej-err"></div>
    <div class="foot"><button class="btn" data-act="close">${tx('Batal')}</button><button class="btn primary" data-act="rej-ok" data-k="${k.id}" data-m="${m}">${tx('Kembalikan')}</button></div>`)},
  'rej-ok'(el){const note=$('#rej-note').value.trim();
    runForm('#rej-err',async()=>{await api('reject',{kpiId:el.dataset.k,month:+el.dataset.m,year:S.year,note});closeModal();render();toast(tx('Realisasi dikembalikan dengan catatan'))})},
  'kpi-new'(el){kpiForm(null,el.dataset.owner)},
  'kpi-edit'(el){const k=kpiById(el.dataset.id);kpiForm(Object.assign({},k),k.ownerId)},
  'kpi-save'(el){const g=id=>$(id).value.trim();
    const d={id:el.dataset.id||null,ownerId:el.dataset.owner,name:g('#f-name'),target:g('#f-target'),weight:g('#f-weight'),
      persp:g('#f-persp'),polarity:g('#f-pol'),unit:g('#f-unit'),parentId:$('#f-parent').value||null,rollup:g('#f-roll'),desc:g('#f-desc')};
    runForm('#f-err',async()=>{await api('kpi_save',d);closeModal();render();toast(tx('KPI disimpan'))})},
  'kpi-del'(el){if(!el.classList.contains('armed')){el.classList.add('armed');el.textContent=tx('Klik lagi untuk hapus');return}
    runForm('#f-err',async()=>{await api('kpi_delete',{id:el.dataset.id});closeModal();render();toast(tx('KPI dihapus'))})},
  'user-new'(){userForm(null)},
  'user-edit'(el,ev){ev.stopPropagation();userForm(Object.assign({},userById(el.dataset.id)))},
  'user-save'(el){const g=id=>($(id)?.value||'').trim();
    const d={id:el.dataset.id||null,name:g('#u-name'),title:g('#u-title'),dept:g('#u-dept'),
      username:g('#u-uname'),password:$('#u-pin')?$('#u-pin').value:'',parentId:$('#u-parent')?$('#u-parent').value:null};
    runForm('#u-err',async()=>{await api('user_save',d);closeModal();render();toast(tx('Data anggota disimpan'))})},
  'user-del'(el){if(childrenUsers(el.dataset.id).length){$('#u-err').textContent=tx('Pindahkan dulu bawahannya ke atasan lain sebelum menghapus.');return}
    if(!el.classList.contains('armed')){el.classList.add('armed');el.textContent=tx('Klik lagi untuk hapus');return}
    runForm('#u-err',async()=>{await api('user_delete',{id:el.dataset.id});closeModal();render();toast(tx('Anggota dihapus'))})},
  close(){closeModal()},
  'modal-bg'(el,ev){if(ev.target===el)closeModal()},
  'pin-save'(){const old=$('#pin0').value,a=$('#pin1').value,b=$('#pin2').value;
    runForm('#pin-err',async()=>{
      if(a.length<4)throw new Error(tx('Password baru minimal 4 karakter.'));
      if(a!==b)throw new Error(tx('Kedua password baru tidak sama.'));
      await api('change_password',{current:old,password:a});
      $('#pin0').value='';$('#pin1').value='';$('#pin2').value='';toast(tx('Password berhasil diganti'))})},
  'set-save'(){const d={company:$('#set-co').value.trim(),cap:$('#set-cap').value,year:$('#set-year').value};
    if($('#set-demo'))d.demoMode=$('#set-demo').checked;
    run(async()=>{await api('settings_save',d);render();toast(tx('Pengaturan disimpan'))})},
  'copy-report'(){const u=me(),list=reportPeople(u).filter(p=>(P.rlv||'all')==='all'||p.level===P.rlv);
    const rows=[[tx('Nama'),tx('Jabatan'),tx('Unit'),...MON,tx('Rata-rata')].join('\t'),...list.map(p=>[p.name,p.title,p.dept,...MON.map((_,i)=>{const s=score(p.id,i);return s==null?'':s.toFixed(1).replace('.',DEC())}),(ytd(p.id,P.month)??0).toFixed(1).replace('.',DEC())].join('\t'))].join('\n')
    navigator.clipboard?.writeText(rows).then(()=>toast(tx('Tabel disalin. Tempel di Excel dengan Ctrl+V')),()=>toast(tx('Browser menolak menyalin. Coba lagi.')))},
};
document.addEventListener('click',e=>{const el=e.target.closest('[data-act]');if(!el)return;const f=A[el.dataset.act];if(f)f(el,e)});
document.addEventListener('change',e=>{if(e.target.matches('[data-lang-sel]')){setLang(e.target.value);return}if(e.target.id==='period'){P.month=+e.target.value;render()}if(e.target.id==='rlv'){P.rlv=e.target.value;render()}if(e.target.id==='wide'){P.wide=e.target.value;render()}});
document.addEventListener('submit',e=>{e.preventDefault();if(e.target.id==='lg-form')A.login()});
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal()});
document.addEventListener('input',e=>{if(e.target.id==='team-q'){const q=e.target.value.toLowerCase();$$('#team-body tr').forEach(tr=>tr.hidden=q&&!tr.dataset.q.includes(q))}});
loadPref();
initLang();
if(BOOT.data)applyData(BOOT.data);
render();
