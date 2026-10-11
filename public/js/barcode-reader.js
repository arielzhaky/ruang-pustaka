(() => {
    'use strict';
    const workerUrl = new URL('barcode-worker.js', document.currentScript.src);
    workerUrl.search = new URL(document.currentScript.src).search;
    class LibraryBarcodeReader {
        constructor() { this.worker = null; this.pending = null; this.sequence = 0; }
        decode(image, options = {}, progress = () => {}) {
            if (this.pending) return Promise.reject(new Error('Pembacaan sebelumnya masih berjalan.'));
            return new Promise((resolve, reject) => {
                try {
                    if (!this.worker) {
                        this.worker = new Worker(workerUrl, { name: 'ruang-pustaka-barcode' });
                        this.worker.onmessage = ({ data }) => {
                            if (data.id !== this.pending?.id) return;
                            if (data.progress) { this.pending.progress(data.progress); return; }
                            const pending = this.pending; this.pending = null; clearTimeout(pending.timer);
                            data.error ? pending.reject(new Error(data.error)) : pending.resolve(data.results);
                        };
                        this.worker.onerror = () => this.cancel('Pemindai gagal dimuat. Muat ulang halaman dan coba lagi.');
                    }
                    const id = ++this.sequence;
                    const timer = setTimeout(() => this.cancel('Gambar terlalu sulit diproses. Potong gambar agar kartu atau label terlihat lebih besar, lalu coba lagi.'), 25000);
                    this.pending = { id, timer, resolve, reject, progress };
                    this.worker.postMessage({ id, image, ...options }, [image.data.buffer]);
                } catch (_) { this.cancel('Pemindai tidak didukung browser ini. Coba Safari/Chrome terbaru atau gunakan kode manual.'); reject(new Error('Pemindai tidak dapat dimuat.')); }
            });
        }
        cancel(reason = 'Pembacaan dibatalkan.') {
            this.worker?.terminate(); this.worker = null;
            if (this.pending) {
                clearTimeout(this.pending.timer); this.pending.reject(new DOMException(reason, 'AbortError')); this.pending = null;
            }
        }
        async image(file) {
            // Browser image decoding applies EXIF orientation before drawing.
            const url = URL.createObjectURL(file);
            const image = new Image();
            try {
                image.src = url; await image.decode();
                if (!image.naturalWidth || image.naturalWidth * image.naturalHeight > 50000000) throw new Error('Gambar terlalu besar. Potong gambar ke satu kartu/label dahulu.');
                // Keep far more detail than the on-screen preview. Decoding never uses preview pixels.
                return LibraryBarcodeReader.pixels(image, image.naturalWidth, image.naturalHeight, 4400);
            } catch (error) {
                if (error.message?.startsWith('Gambar terlalu besar')) throw error;
                throw new Error('File gambar tidak dapat dibuka. Gunakan JPG, PNG, atau WebP; ubah HEIC/PDF ke JPG atau ambil screenshot terlebih dahulu.');
            } finally { URL.revokeObjectURL(url); image.src = ''; }
        }
        static pixels(source, width, height, maxSide = 1920) {
            const scale = Math.min(1, maxSide / Math.max(width, height), Math.sqrt(16000000 / (width * height)));
            const canvas = document.createElement('canvas'); canvas.width = Math.max(1, Math.round(width * scale)); canvas.height = Math.max(1, Math.round(height * scale));
            const context = canvas.getContext('2d', { willReadFrequently: true });
            context.fillStyle = '#fff'; context.fillRect(0, 0, canvas.width, canvas.height);
            context.drawImage(source, 0, 0, canvas.width, canvas.height);
            return context.getImageData(0, 0, canvas.width, canvas.height);
        }
    }
    window.LibraryBarcodeReader = LibraryBarcodeReader;
})();
