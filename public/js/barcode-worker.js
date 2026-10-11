/* Decoder stays off the UI thread, including retries on large photos. */
importScripts('../vendor/zxing-wasm/reader.js');
const ready = ZXingWASM.prepareZXingModule({
    overrides: { locateFile: () => new URL('../vendor/zxing-wasm/zxing_reader.wasm', self.location.href).href },
    fireImmediately: true,
});
const options = { formats: ['Code128', 'QRCode'], tryHarder: true, tryRotate: true, tryInvert: true, tryDownscale: true, tryDenoise: true, maxNumberOfSymbols: 32, minLineCount: 2, textMode: 'Plain' };
const read = async (data, extra = {}) => (await ZXingWASM.readBarcodes(data, { ...options, ...extra }))
    .filter(result => result.isValid && result.text).map(({ text, format }) => ({ text, format }));
// Contrast stretching helps faded labels; keep local shadows for the adaptive binarizer.
function contrast(source) {
    const data = new Uint8ClampedArray(source.data.length);
    const histogram = new Uint32Array(256);
    for (let i = 0; i < data.length; i += 4) histogram[Math.round(source.data[i] * .299 + source.data[i + 1] * .587 + source.data[i + 2] * .114)]++;
    const threshold = source.width * source.height * .005;
    let low = 0, high = 255, count = 0;
    while (low < 254 && (count += histogram[low]) < threshold) low++;
    count = 0;
    while (high > low + 1 && (count += histogram[high]) < threshold) high--;
    for (let i = 0; i < data.length; i += 4) {
        const gray = source.data[i] * .299 + source.data[i + 1] * .587 + source.data[i + 2] * .114;
        data[i] = data[i + 1] = data[i + 2] = (gray - low) * 255 / Math.max(1, high - low);
        data[i + 3] = 255;
    }
    return { data, width: source.width, height: source.height };
}
// Nearest-neighbour rotation preserves narrow bars; white padding keeps every edge.
function transform(source, degrees = 0, mirror = false) {
    const angle = degrees * Math.PI / 180, c = Math.cos(angle), s = Math.sin(angle);
    const width = Math.ceil(Math.abs(source.width * c) + Math.abs(source.height * s));
    const height = Math.ceil(Math.abs(source.width * s) + Math.abs(source.height * c));
    const data = new Uint8ClampedArray(width * height * 4).fill(255);
    for (let y = 0; y < height; y++) for (let x = 0; x < width; x++) {
        let sx = Math.round(c * (x - width / 2) + s * (y - height / 2) + source.width / 2);
        const sy = Math.round(-s * (x - width / 2) + c * (y - height / 2) + source.height / 2);
        if (mirror) sx = source.width - 1 - sx;
        if (sx < 0 || sy < 0 || sx >= source.width || sy >= source.height) continue;
        const from = (sy * source.width + sx) * 4, to = (y * width + x) * 4;
        data[to] = source.data[from]; data[to + 1] = source.data[from + 1]; data[to + 2] = source.data[from + 2];
    }
    return { data, width, height };
}
function retrySize(source) {
    const scale = Math.min(1, 2200 / Math.max(source.width, source.height));
    if (scale === 1) return source;
    const width = Math.round(source.width * scale), height = Math.round(source.height * scale);
    const data = new Uint8ClampedArray(width * height * 4);
    for (let y = 0; y < height; y++) for (let x = 0; x < width; x++) {
        const from = (Math.min(source.height - 1, Math.floor(y / scale)) * source.width + Math.min(source.width - 1, Math.floor(x / scale))) * 4;
        const to = (y * width + x) * 4;
        data[to] = source.data[from]; data[to + 1] = source.data[from + 1]; data[to + 2] = source.data[from + 2]; data[to + 3] = 255;
    }
    return { data, width, height };
}
self.onmessage = async ({ data: { id, image, thorough, variation = 0 } }) => {
    try {
        await ready;
        let results = await read(image);
        if (!results.length && thorough) {
            self.postMessage({ id, progress: 'Mencoba kontras dan orientasi lain…' });
            results = await read(contrast(image));
            const retry = retrySize(image);
            if (!results.length) results = await read(transform(retry, 0, true));
            for (const angle of [15, -15, 30, -30, 45]) {
                if (results.length) break;
                results = await read(transform(retry, angle));
            }
        } else if (!results.length && variation) {
            // Rotate alternating live frames without making every frame expensive.
            const angle = [0, 15, -15, 30, -30][variation % 5];
            if (angle) results = await read(transform(image, angle));
        }
        self.postMessage({ id, results });
    } catch (_) {
        self.postMessage({ id, error: 'Mesin pembaca belum siap. Muat ulang halaman lalu coba kembali.' });
    }
};
