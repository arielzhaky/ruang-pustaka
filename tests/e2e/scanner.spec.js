import { test, expect } from '@playwright/test';
import fs from 'node:fs';
const fixture = name => fs.readFileSync(new URL('../fixtures/scanner/' + name, import.meta.url));
const svg = fixture('member.svg').toString('base64');
const copySvg = fixture('copy.svg').toString('base64');
const qr = fixture('member-qr.png');
const loginQr = fixture('login-qr.svg').toString('base64');
const loginToken = 'RPL1:' + 'A'.repeat(64); // Test-only value, never issued to a real member.

async function mockLookup(page) {
    const calls = [];
    await page.route('**/scan/lookup?**', async route => {
        const url = new URL(route.request().url()); const code = url.searchParams.get('code'); calls.push(code);
        const member = code === 'RPS00000001';
        const found = member || code === 'RPC00000001';
        const data = member ? { kind: 'member', id: 1, name: 'Siswa Uji Pemindai', member_code: 'ANG-001', barcode: code, class_name: 'VIII A', active: true, active_count: 0, late_count: 0, urls: { edit: '/members/1/edit', card: '/members/1/card', borrow: '/loans/create' } }
            : { kind: 'copy', id: 1, title: 'Buku Uji Pemindai', inventory_code: 'INV-001', barcode: code, author: 'Penulis', condition: 'baik', status: 'available', can_borrow: true, loan: null, urls: { copies: '/books/1/copies', label: '/books/1/barcode-labels' } };
        await route.fulfill({ status: found ? 200 : 404, contentType: 'application/json', body: JSON.stringify(found ? data : { message: 'Kode terbaca tetapi tidak terdaftar.' }) });
    });
    return calls;
}

// Render actual project-generated Code 128 fixtures in realistic photo/screenshot positions.
async function photo(page, variant = 'plain') {
    const bytes = await page.evaluate(async ({ svg, copySvg, loginQr, variant }) => {
        const load = async code => { const image = new Image(); image.src = 'data:image/svg+xml;base64,' + code; await image.decode(); return image; };
        const image = await load(svg), second = await load(copySvg);
        const canvas = document.createElement('canvas');
        canvas.width = variant === 'large' ? 3600 : 1000; canvas.height = variant === 'large' ? 2600 : 700;
        const ctx = canvas.getContext('2d'); ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, canvas.width, canvas.height);
        if (variant === 'blank') { /* No barcode in this picture. */ }
        else if (variant === 'large') ctx.drawImage(image, 2900, 2300, 500, 125);
        else if (variant === 'multiple') { ctx.drawImage(image, 60, 60, 550, 125); ctx.drawImage(second, 340, 500, 550, 125); }
        else if (variant === 'login-card') { ctx.drawImage(image, 60, 60, 550, 125); ctx.drawImage(await load(loginQr), 660, 400, 260, 260); }
        else {
            ctx.translate(500, 350); if (variant === 'tilted') ctx.rotate(23 * Math.PI / 180); if (variant === 'vertical') ctx.rotate(Math.PI / 2); if (variant === 'mirrored') ctx.scale(-1, 1);
            ctx.drawImage(image, -300, -65, 600, 130);
        }
        if (variant === 'faded') {
            const pixels = ctx.getImageData(0, 0, canvas.width, canvas.height);
            for (let i = 0; i < pixels.data.length; i += 4) pixels.data[i] = pixels.data[i + 1] = pixels.data[i + 2] = 135 + pixels.data[i] * .14;
            ctx.putImageData(pixels, 0, 0);
        }
        const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
        return Array.from(new Uint8Array(await blob.arrayBuffer()));
    }, { svg, copySvg, loginQr, variant });
    return Buffer.from(bytes);
}

test('reads QR plus rotated, faded, mirrored, and high-resolution Code 128 photos', async ({ page }) => {
    const calls = await mockLookup(page); await page.goto('/scanner-test');
    await page.locator('[data-scan-file]').setInputFiles({ name: 'qr.png', mimeType: 'image/png', buffer: qr });
    await expect(page.locator('[data-scan-result]')).toContainText('Siswa Uji Pemindai');
    for (const variant of ['plain', 'tilted', 'vertical', 'mirrored', 'faded', 'large']) {
        const count = calls.length;
        await page.locator('[data-scan-file]').setInputFiles({ name: variant + '.png', mimeType: 'image/png', buffer: await photo(page, variant) });
        await expect.poll(() => calls.length, { timeout: 20000, message: 'Decode ' + variant }).toBe(count + 1);
        expect(calls.at(-1)).toBe('RPS00000001');
        await expect(page.locator('[data-scan-submit]')).toBeEnabled();
    }
});

test('offers a choice when an image contains multiple codes, without automatic lookup', async ({ page }) => {
    const calls = await mockLookup(page); await page.goto('/scanner-test');
    await page.locator('[data-scan-file]').setInputFiles({ name: 'two-cards.png', mimeType: 'image/png', buffer: await photo(page, 'multiple') });
    const choices = page.locator('[data-scan-choices]'); await expect(choices).toContainText('2 kode ditemukan');
    expect(calls).toHaveLength(0);
    await choices.getByRole('button', { name: /RPC00000001/ }).click();
    await expect(page.locator('[data-scan-result]')).toContainText('Buku Uji Pemindai');
    expect(calls).toEqual(['RPC00000001']);
});

test('bad files recover, cancellation works, and an unknown QR is not reported as unreadable', async ({ page }) => {
    await mockLookup(page); await page.goto('/scanner-test');
    const picker = page.locator('[data-scan-file]');
    await picker.setInputFiles({ name: 'not-image.pdf', mimeType: 'application/pdf', buffer: Buffer.from('not a picture') });
    await expect(page.locator('[data-scan-status]')).toContainText('File gambar tidak dapat dibuka');
    await expect(page.locator('[data-scan-image]')).toBeEnabled();
    await page.route('**/zxing_reader.wasm', route => new Promise(resolve => setTimeout(async () => { await route.continue().catch(() => {}); resolve(); }, 2000)));
    await picker.setInputFiles({ name: 'code.png', mimeType: 'image/png', buffer: qr });
    await page.locator('[data-scan-cancel]').click();
    await expect(page.locator('[data-scan-status]')).toContainText('dibatalkan');
    await expect(page.locator('[data-scan-image]')).toBeEnabled();
    await page.unroute('**/zxing_reader.wasm');
    await picker.setInputFiles({ name: 'code-again.png', mimeType: 'image/png', buffer: qr });
    await expect(page.locator('[data-scan-result]')).toContainText('Siswa Uji Pemindai');
    await page.route('**/scan/lookup?**', route => route.fulfill({ status: 404, contentType: 'application/json', body: JSON.stringify({ message: 'Kode terbaca tetapi tidak terdaftar.' }) }));
    await picker.setInputFiles({ name: 'code-unknown.png', mimeType: 'image/png', buffer: qr });
    await expect(page.locator('[data-scan-status]')).toContainText('tidak terdaftar');
    await expect(page.locator('[data-scan-result]')).toBeHidden();
});

test('blank images do not select data, and a missing decoder still allows manual entry', async ({ page }) => {
    const calls = await mockLookup(page); await page.goto('/scanner-test');
    await page.locator('[data-scan-file]').setInputFiles({ name: 'blank.png', mimeType: 'image/png', buffer: await photo(page, 'blank') });
    await expect(page.locator('[data-scan-status]')).toContainText('Kode belum terbaca');
    expect(calls).toHaveLength(0);
    await page.route('**/barcode-worker.js*', route => route.abort());
    await page.reload();
    await page.locator('[data-scan-file]').setInputFiles({ name: 'code.png', mimeType: 'image/png', buffer: qr });
    await expect(page.locator('[data-scan-status]')).toContainText('Pemindai gagal dimuat');
    await page.locator('[data-scan-code]').fill('RPS00000001');
    await page.locator('[data-scan-code]').press('Enter');
    await expect(page.locator('[data-scan-result]')).toContainText('Siswa Uji Pemindai');
});

async function installCamera(page, { code = true, delay = 0, second = false, qrCode = false } = {}) {
    await page.evaluate(async ({ svg, qr, code, delay, second, qrCode }) => {
        window.cameraCalls = []; window.cameraTracks = [];
        // Keep a stable mock object: WebKit may recreate native MediaDevices wrappers.
        const mediaDevices = {};
        Object.defineProperty(navigator, 'mediaDevices', { configurable: true, value: mediaDevices });
        mediaDevices.getUserMedia = async constraints => {
            window.cameraCalls.push(constraints);
            await new Promise(resolve => setTimeout(resolve, delay));
            const image = new Image(); image.src = qrCode && qrCode !== 'login' ? 'data:image/png;base64,' + qr : 'data:image/svg+xml;base64,' + svg; await image.decode();
            const canvas = document.createElement('canvas'); canvas.width = 1920; canvas.height = 1080;
            const context = canvas.getContext('2d');
            const paint = () => { context.fillStyle = '#fff'; context.fillRect(0, 0, 1920, 1080); if (code) context.drawImage(image, 70, qrCode ? 690 : 820, qrCode ? 300 : 600, qrCode ? 300 : 150); };
            paint(); const stream = canvas.captureStream(10); const track = stream.getVideoTracks()[0]; window.cameraTracks.push(track);
            const timer = setInterval(paint, 100); const stop = track.stop.bind(track); track.stop = () => { clearInterval(timer); stop(); };
            track.getCapabilities = () => ({ zoom: { min: 1, max: 4, step: .1 }, torch: true, focusMode: ['continuous'] });
            track.getSettings = () => ({ deviceId: constraints.video.deviceId?.exact || 'rear', zoom: 1 });
            track.applyConstraints = async settings => { window.cameraCalls.push(settings); };
            return stream;
        };
        mediaDevices.enumerateDevices = async () => [{ kind: 'videoinput', deviceId: 'rear', label: 'Kamera belakang' }, ...(second ? [{ kind: 'videoinput', deviceId: 'front', label: 'Kamera depan' }] : [])];
    }, { svg: qrCode === 'login' ? loginQr : svg, qr: qr.toString('base64'), code, delay, second, qrCode });
}

for (const qrCode of [false, true]) test(`camera reads ${qrCode ? 'QR' : 'Code 128'} outside the centre and releases the stream`, async ({ page }) => {
    await mockLookup(page); await page.goto('/scanner-test'); await installCamera(page, { qrCode });
    // Device discovery must not prevent decoding on a camera that is already open.
    if (qrCode) await page.evaluate(() => { Object.defineProperty(navigator.mediaDevices, 'enumerateDevices', { configurable: true, value: async () => { throw new Error('Unavailable'); } }); });
    await page.locator('[data-scan-camera]').click();
    await expect(page.locator('[data-scan-result]')).toContainText('Siswa Uji Pemindai');
    expect(await page.evaluate(() => cameraTracks.every(track => track.readyState === 'ended'))).toBe(true);
    await expect(page.locator('[data-scan-reader]')).toBeHidden();
});

test('camera controls, switching devices, pending cancellation, and page hiding release tracks', async ({ page }) => {
    await mockLookup(page); await page.goto('/scanner-test'); await installCamera(page, { code: false, second: true });
    await page.locator('[data-scan-camera]').click();
    await expect(page.locator('[data-camera-select]')).toBeVisible();
    await page.locator('[data-camera-torch]').click(); await expect(page.locator('[data-camera-torch]')).toHaveAttribute('aria-pressed', 'true');
    await page.locator('[data-camera-zoom]').fill('2');
    await expect.poll(() => page.evaluate(() => cameraCalls.some(call => call.advanced?.[0]?.zoom === 2))).toBe(true);
    await page.locator('[data-camera-select]').selectOption('front');
    await expect.poll(() => page.evaluate(() => cameraTracks.length)).toBe(2);
    await expect(page.locator('[data-camera-torch]')).toHaveText('Nyalakan lampu');
    expect(await page.evaluate(() => cameraTracks[0].readyState)).toBe('ended');
    await page.evaluate(() => { Object.defineProperty(document, 'hidden', { configurable: true, value: true }); document.dispatchEvent(new Event('visibilitychange')); });
    await expect.poll(() => page.evaluate(() => cameraTracks.every(track => track.readyState === 'ended'))).toBe(true);
    await page.evaluate(() => { Object.defineProperty(document, 'hidden', { configurable: true, value: false }); });
    await installCamera(page, { code: false, delay: 500 });
    await page.locator('[data-scan-camera]').click(); await page.locator('[data-scan-stop]').click();
    await expect.poll(() => page.evaluate(() => cameraTracks.length)).toBe(1);
    await expect.poll(() => page.evaluate(() => cameraTracks.every(track => track.readyState === 'ended'))).toBe(true);
    await expect(page.locator('[data-scan-camera]')).toBeEnabled();
});

test('scan still selects students for visits and copies for loans, including mobile layout', async ({ page }) => {
    await mockLookup(page); await page.goto('/scanner-test?mode=visit');
    await page.locator('[data-scan-file]').setInputFiles({ name: 'member.png', mimeType: 'image/png', buffer: qr });
    await expect(page.locator('#visit-member-id')).toHaveValue('1');
    await page.goto('/scanner-test?mode=loan');
    await page.locator('[data-scan-code]').fill('RPS00000001'); await page.locator('[data-scan-code]').press('Enter');
    await expect(page.locator('#member_id')).toHaveValue('1');
    for (let i = 0; i < 2; i++) {
        await page.locator('[data-scan-code]').fill('RPC00000001'); await page.locator('[data-scan-code]').press('Enter');
        await expect(page.locator('[data-scan-submit]')).toBeEnabled();
    }
    await expect(page.locator('[data-loan-copy-list] input:checked')).toHaveCount(1);
    await page.setViewportSize({ width: 390, height: 844 });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
});

for (const source of ['image', 'camera']) test(`student QR ${source} logs in by POST without exposing the credential in the URL`, async ({ page }) => {
    let request;
    await page.route('**/login/qr', async route => {
        request = route.request();
        await route.fulfill({ contentType:'application/json', body:JSON.stringify({redirect:'/scanner-test?logged-in=1'}) });
    });
    await page.goto('/scanner-test?mode=login');
    if(source === 'image') await page.locator('[data-scan-file]').setInputFiles({name:'card.png',mimeType:'image/png',buffer:await photo(page,'login-card')});
    else { await installCamera(page,{qrCode:'login'}); await page.locator('[data-scan-camera]').click(); }
    await page.waitForURL('**/scanner-test?logged-in=1');
    expect(request.method()).toBe('POST');
    expect(request.postDataJSON()).toEqual({student_qr:loginToken});
    expect(request.headers()['x-csrf-token']).toBe('test-token');
    expect(request.url()).not.toContain(loginToken);
});

test('service scanning ignores the secret QR on a card and login rejects an identity barcode', async ({ page }) => {
    const calls=await mockLookup(page); await page.goto('/scanner-test');
    const card=await photo(page,'login-card');
    await page.locator('[data-scan-file]').setInputFiles({name:'card.png',mimeType:'image/png',buffer:card});
    await expect(page.locator('[data-scan-result]')).toContainText('Siswa Uji Pemindai');
    expect(calls).toEqual(['RPS00000001']);
    await page.locator('[data-scan-code]').fill(loginToken); await page.locator('[data-scan-code]').press('Enter');
    await expect(page.locator('[data-scan-status]')).toContainText('QR login pribadi');
    expect(calls).toHaveLength(1);
    await page.goto('/scanner-test?mode=login');
    await page.locator('[data-scan-file]').setInputFiles({name:'identity.png',mimeType:'image/png',buffer:qr});
    await expect(page.locator('[data-scan-status]')).toContainText('QR login belum ditemukan');
    await page.route('**/login/qr',route=>route.fulfill({status:429,contentType:'application/json',body:'{}'}));
    await page.locator('[data-scan-file]').setInputFiles({name:'card.png',mimeType:'image/png',buffer:card});
    await expect(page.locator('[data-scan-status]')).toContainText('Terlalu banyak percobaan');
    await expect(page.locator('[data-scan-submit]')).toBeEnabled();
    await expect(page.locator('[data-scan-code]')).toHaveValue('');
    expect(await page.locator('body').textContent()).not.toContain(loginToken);
});
