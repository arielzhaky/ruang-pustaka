// Creates original typographic/vector covers from the library's own catalogue.
// Usage: node scripts/create-illustrated-covers.mjs input.json output-directory
// This only writes image files and a manifest; it does not change the database.
import fs from 'node:fs/promises';
import path from 'node:path';
import { chromium } from '@playwright/test';

const [input, output] = process.argv.slice(2);
if (!input || !output) throw new Error('Provide input JSON and output directory.');
const books = JSON.parse(await fs.readFile(input, 'utf8'));
await fs.mkdir(output, { recursive: true });
const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const palettes = [
  ['#163e43','#efad69','#e9efd9','#77a88d'],
  ['#24294f','#e5bb78','#f2e9db','#869ad1'],
  ['#643f55','#e5a680','#f5e9d6','#b794b0'],
  ['#263f66','#eda88c','#edf1e3','#87b1bf'],
  ['#573c32','#d3aa64','#f6ecd9','#9da476'],
  ['#334636','#e1bc7b','#edf0db','#9ebc96'],
];
function theme(book) {
  const t = book.title.toLowerCase();
  if (/landak/.test(t)) return 'hedgehog';
  if (/angin|bulan|hujan|dream/.test(t)) return 'moon';
  if (/wayang|rahwana|roro|jaka|majapahit|jayaprana/.test(t)) return 'heritage';
  if (/nabi|rasul|rosul|islam|surga|songo|shalat|sholat|lailatul|religius|khattab/.test(t)) return 'arch';
  if (/video|dvd|vcd/.test(t)) return 'film';
  if (/sains|science|fisika|bandara|teknik|kolam renang/.test(t)) return 'science';
  if (/kamus|grammar|berbicara|retorika|berpidato/.test(t)) return 'language';
  if (/uud|mpr|pahlawan|kartini|kennedy|pki/.test(t)) return 'civic';
  if (/burung|bangau|bebek/.test(t)) return 'bird';
  if (/kancil|biri|rusa|katak|kura|dongeng|belalang|harimau/.test(t)) return 'forest';
  if (/toba|gunung|tanah|air|maxigrow|bandung/.test(t)) return 'landscape';
  if (/masak/.test(t)) return 'kitchen';
  if (/miiko|shinchan|angel|otome|komik|nakayoshi|second power|sijuki/.test(t)) return 'comic';
  return 'book';
}
function illustration(kind, [ink, gold, paper, accent], seed) {
  const leaf = (x,y,r=0) => `<g transform="translate(${x} ${y}) rotate(${r})"><path d="M0 0Q-65-80 0-120Q65-80 0 0" fill="${accent}"/><path d="M0 0V-110" stroke="${ink}" stroke-width="3"/></g>`;
  const stars = Array.from({length:12},(_,i)=>{const x=35+(i*83+seed*13)%450,y=10+(i*43)%230;return `<path d="M${x-4} ${y}h8M${x} ${y-4}v8" stroke="${gold}" stroke-width="2"/>`;}).join('');
  const hills = `<path d="M0 230Q110 120 255 232T510 220V310H0Z" fill="${accent}"/><path d="M0 270Q170 215 295 267T510 255V310H0Z" fill="${gold}"/>`;
  const openBook = `<path d="M95 95Q180 72 250 118Q325 72 415 95V250Q325 225 250 274Q175 225 95 250Z" fill="${paper}" stroke="${gold}" stroke-width="4"/><path d="M250 120V270" stroke="${ink}" stroke-width="3"/><g fill="none" stroke="${accent}" stroke-width="5"><path d="M123 126Q178 114 223 140M123 151Q178 139 223 165M123 176Q178 164 223 190M278 140Q325 114 389 126M278 165Q325 139 389 151M278 190Q325 164 389 176"/></g>`;
  switch(kind) {
    case 'hedgehog': return `${leaf(75,290,-25)}${leaf(430,280,35)}<ellipse cx="263" cy="285" rx="153" ry="14" fill="${ink}" opacity=".2"/><path d="M130 250L95 222L130 209L105 175L146 172L138 127L178 142L196 94L229 125L271 92L284 134L329 119L327 170L372 169L350 240Z" fill="${gold}"/><path d="M138 242Q150 153 255 157Q321 163 340 204L398 239Q411 258 376 264H166Q140 264 138 242" fill="${paper}"/><circle cx="338" cy="218" r="7" fill="${ink}"/><circle cx="395" cy="241" r="8" fill="${ink}"/><circle cx="290" cy="192" r="19" fill="${gold}"/><path d="M182 264v14m114-14v14" stroke="${ink}" stroke-width="9" stroke-linecap="round"/>`;
    case 'moon': return `${stars}<circle cx="293" cy="106" r="84" fill="${gold}"/><circle cx="324" cy="76" r="72" fill="${ink}"/><path d="M20 176Q105 119 190 176T490 167M50 207Q130 152 230 207T510 196" fill="none" stroke="${paper}" opacity=".65" stroke-width="3"/>${hills}${leaf(95,299,-20)}`;
    case 'heritage': return `${stars}<circle cx="255" cy="131" r="118" fill="${gold}"/><path d="M85 294V258H113V222H144V187H170V150H203V106H235V69H273V106H305V150H339V187H367V222H400V258H427V294Z" fill="${ink}"/><path d="M223 294V232Q255 187 287 232V294" fill="${accent}"/><path d="M143 237H365M176 201H333M207 163H303M229 126H283" stroke="${paper}" opacity=".55" stroke-width="3"/>`;
    case 'arch': return `${stars}<path d="M110 304V144Q110 78 255 15Q400 78 400 144V304Z" fill="${gold}"/><path d="M132 304V149Q132 94 255 41Q378 94 378 149V304Z" fill="${ink}"/><circle cx="262" cy="115" r="40" fill="${paper}"/><circle cx="278" cy="104" r="35" fill="${ink}"/><path d="M165 260L255 210L345 260L255 303Z" fill="${accent}"/><path d="M165 241L255 192L345 241L255 283Z" fill="${paper}"/><path d="M255 192v91" stroke="${gold}" stroke-width="3"/>`;
    case 'film': return `<rect x="70" y="63" width="370" height="211" rx="12" fill="${paper}"/><path d="M224 116L310 168L224 220Z" fill="${ink}"/><path d="M75 55L430 15L436 64L81 104Z" fill="${gold}"/><g stroke="${ink}" stroke-width="16"><path d="M116 51L148 89M190 44L221 80M262 36L294 72M337 27L368 64"/></g>`;
    case 'science': return `${stars}<g transform="translate(255 155)" fill="none" stroke="${gold}" stroke-width="4"><ellipse rx="175" ry="62"/><ellipse rx="175" ry="62" transform="rotate(60)"/><ellipse rx="175" ry="62" transform="rotate(120)"/></g><circle cx="255" cy="155" r="39" fill="${paper}"/><circle cx="119" cy="90" r="16" fill="${accent}"/><circle cx="367" cy="64" r="12" fill="${paper}"/><circle cx="353" cy="257" r="19" fill="${accent}"/>`;
    case 'language': return `<path d="M65 25H335Q355 25 355 45V154Q355 174 335 174H163L96 217V174H65Q45 174 45 154V45Q45 25 65 25" fill="${gold}"/><text x="100" y="128" font-family="Georgia" font-size="88" fill="${ink}">Aa</text><path d="M228 144H437Q457 144 457 164V264Q457 284 437 284H405V310L361 284H228Q208 284 208 264V164Q208 144 228 144" fill="${paper}"/><path d="M244 190H418M244 219H387M244 248H343" stroke="${accent}" stroke-width="7"/>`;
    case 'civic': return `<circle cx="255" cy="144" r="123" fill="${gold}"/><path d="M93 125L255 40L417 125Z" fill="${paper}"/><path d="M100 282H410M87 296H423M106 137H404" stroke="${paper}" stroke-width="14"/><g fill="${paper}">${[123,191,259,327].map(x=>`<rect x="${x}" y="151" width="37" height="117" rx="3"/>`).join('')}</g><path d="M255 62L263 80L285 82L269 96L274 116L255 105L236 116L241 96L225 82L247 80Z" fill="${ink}"/>`;
    case 'bird': return `${leaf(97,287,-30)}${leaf(425,284,35)}<path d="M65 281H440" stroke="${gold}" stroke-width="8"/><path d="M168 150Q117 118 113 76Q179 88 208 124Q257 32 326 70Q365 101 330 146Q338 239 243 258Q172 260 143 207Z" fill="${paper}"/><path d="M172 163Q236 120 294 156Q270 224 207 219Z" fill="${accent}"/><path d="M340 109L391 127L338 140Z" fill="${gold}"/><circle cx="313" cy="105" r="7" fill="${ink}"/><path d="M229 254V280M256 251V280" stroke="${gold}" stroke-width="6"/>`;
    case 'forest': return `<circle cx="267" cy="109" r="87" fill="${gold}"/>${hills}${leaf(95,284,-22)}${leaf(419,286,22)}<path d="M170 277V221Q170 165 221 152L202 95L251 133L280 85L294 158Q349 174 329 224L361 239L324 248Q313 278 282 278Z" fill="${paper}"/><path d="M208 264Q218 224 249 221" fill="none" stroke="${accent}" stroke-width="18"/><circle cx="299" cy="191" r="6" fill="${ink}"/><circle cx="350" cy="238" r="7" fill="${ink}"/>`;
    case 'landscape': return `${stars}<circle cx="359" cy="66" r="48" fill="${gold}"/><path d="M0 260L161 49L308 260Z" fill="${accent}"/><path d="M154 260L330 95L510 260Z" fill="${gold}"/><path d="M130 90L161 49L197 100L169 89L151 102Z" fill="${paper}"/><path d="M0 253Q143 225 246 263T510 256V310H0Z" fill="${paper}"/><path d="M35 276H175M220 289H455" stroke="${accent}" stroke-width="3"/>`;
    case 'kitchen': return `<circle cx="255" cy="161" r="128" fill="${gold}"/><circle cx="255" cy="161" r="100" fill="${paper}"/><circle cx="255" cy="161" r="76" fill="none" stroke="${accent}" stroke-width="3"/>${leaf(255,205,55)}<path d="M80 50V280M58 50V112Q80 137 102 112V50M435 50V280M419 50V134H435" stroke="${paper}" fill="none" stroke-width="8" stroke-linecap="round"/>`;
    case 'comic': return `${stars}<path d="M70 41H440V246H306L237 296V246H70Z" fill="${paper}"/><path d="M255 58L278 113L343 93L316 151L377 180L310 193L320 251L267 222L216 262L218 204L152 205L197 161L149 119L216 125Z" fill="${gold}"/><path d="M255 120v60" stroke="${ink}" stroke-width="15" stroke-linecap="round"/><circle cx="255" cy="206" r="9" fill="${ink}"/>`;
    default: return `${stars}${leaf(78,285,-30)}${leaf(439,285,30)}${openBook}`;
  }
}

const executablePath = process.env.CHROME_PATH || (process.platform === 'darwin' ? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' : undefined);
const browser = await chromium.launch({ executablePath, headless:true });
const page = await browser.newPage({viewport:{width:600,height:840},deviceScaleFactor:1.5});
const manifest=[];
try {
  for (const book of books) {
    if (!Number.isInteger(book.id) || !book.title) throw new Error('Invalid book record.');
    const p=palettes[book.id % palettes.length], kind=theme(book);
    const author=book.author && book.author.toLowerCase() !== 'belum diketahui' ? book.author : '';
    const svg=`<svg xmlns="http://www.w3.org/2000/svg" width="600" height="840" viewBox="0 0 600 840"><rect width="600" height="840" fill="${p[0]}"/><rect x="20" y="20" width="560" height="800" rx="3" fill="none" stroke="${p[1]}" opacity=".55"/><path d="M35 20V820" stroke="${p[1]}" opacity=".22"/><circle cx="560" cy="-10" r="195" fill="${p[3]}" opacity=".09"/><g transform="translate(45 385)">${illustration(kind,p,book.id)}</g><path d="M55 713H545" stroke="${p[1]}" opacity=".55"/></svg>`;
    await page.setContent(`<html lang="id"><meta charset="utf-8"><style>*{box-sizing:border-box}body{margin:0;width:600px;height:840px;color:${p[2]};background:${p[0]};font-family:Arial,sans-serif}.art{position:absolute;inset:0}.brand{position:absolute;top:48px;left:55px;font-size:12px;letter-spacing:3px;color:${p[1]}}.mark{position:absolute;top:44px;right:55px;font:italic 25px Georgia;color:${p[1]}}.title{position:absolute;left:55px;top:106px;width:490px;height:249px;margin:0;font-family:Georgia,serif;font-weight:normal;font-size:48px;line-height:1.13;overflow-wrap:anywhere;display:flex;align-items:center}.author{position:absolute;top:735px;left:55px;width:490px;height:40px;display:flex;align-items:center;font-size:16px;line-height:1.25;letter-spacing:1px;overflow-wrap:anywhere}.footer{position:absolute;top:793px;left:55px;right:55px;display:flex;justify-content:space-between;color:${p[1]};font-size:9px;letter-spacing:1.3px}</style><div class="art">${svg}</div><div class="brand">RUANG PUSTAKA</div><div class="mark">rp.</div><h1 class="title"><span>${esc(book.title)}</span></h1><div class="author"><span>${esc(author)}</span></div><div class="footer"><span>SMPN 26 TANGERANG</span><span>SAMPUL ILUSTRASI</span></div></html>`);
    const fits=await page.evaluate(()=>{
      for(const sel of ['.title','.author']){
        const box=document.querySelector(sel),content=box.firstElementChild;
        let size=parseFloat(getComputedStyle(box).fontSize);
        while((content.getBoundingClientRect().height>box.clientHeight||content.scrollWidth>box.clientWidth)&&size>12){box.style.fontSize=`${--size}px`;}
        if(content.getBoundingClientRect().height>box.clientHeight||content.scrollWidth>box.clientWidth)return false;
      }
      return true;
    });
    if(!fits)throw new Error(`Text overflow: ${book.id}`);
    const filename=`illustrated-${book.id}.jpg`;
    await page.screenshot({path:path.join(output,filename),type:'jpeg',quality:92});
    manifest.push({id:book.id,title:book.title,author:book.author,code:book.catalog_code,theme:kind,file:filename,kind:'original-illustration',width:900,height:1260});
  }
} finally {await browser.close();}
await fs.writeFile(path.join(output,'manifest.json'),JSON.stringify(manifest,null,2)+'\n');
console.log(`Created ${manifest.length} illustrated covers.`);
