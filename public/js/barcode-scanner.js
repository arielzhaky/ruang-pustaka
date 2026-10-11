(() => {
    'use strict';
    let activeCamera = null;
    const money = value => 'Rp' + Number(value || 0).toLocaleString('id-ID');
    const element = (tag, text, className = '') => {
        const node = document.createElement(tag);
        if (text !== undefined) node.textContent = text;
        node.className = className;
        return node;
    };
    document.querySelectorAll('[data-barcode-scanner]').forEach(panel => {
        const form = panel.querySelector('[data-scan-form]');
        const input = panel.querySelector('[data-scan-code]');
        const kind = panel.querySelector('[data-scan-kind]');
        const status = panel.querySelector('[data-scan-status]');
        const result = panel.querySelector('[data-scan-result]');
        const submit = panel.querySelector('[data-scan-submit]');
        const cameraButton = panel.querySelector('[data-scan-camera]');
        const stopButton = panel.querySelector('[data-scan-stop]');
        const readerNode = panel.querySelector('[data-scan-reader]');
        const fileInput = panel.querySelector('[data-scan-file]');
        const imageButton = panel.querySelector('[data-scan-image]');
        const cameraTools = panel.querySelector('[data-camera-tools]');
        const cameraSelect = panel.querySelector('[data-camera-select]');
        const zoomControl = panel.querySelector('[data-camera-zoom]');
        const zoomLabel = panel.querySelector('[data-zoom-label]');
        const torchButton = panel.querySelector('[data-camera-torch]');
        const preview = panel.querySelector('[data-scan-preview]');
        const previewImage = preview.querySelector('img');
        const cancelImage = panel.querySelector('[data-scan-cancel]');
        const choices = panel.querySelector('[data-scan-choices]');
        let scanner = null;
        let stream = null;
        let video = null;
        let frameTimer = null;
        let zoomTimer = null;
        let fileGeneration = 0;
        let previewUrl = null;
        let torchOn = false;
        let starting = false;
        let running = false;
        let processing = false;
        let readingFile = false;
        let cameraGeneration = 0;
        let generation = 0;
        const message = (text, tone = '') => { status.textContent = text; status.dataset.tone = tone; };
        const syncControls = () => {
            if (panel.dataset.mode === 'login') document.querySelectorAll('[data-login-method]').forEach(button => { button.disabled = processing; });
            cameraButton.disabled = starting || running || processing || readingFile;
            imageButton.disabled = fileInput.disabled = starting || processing || readingFile;
            submit.disabled = starting || processing || readingFile;
            stopButton.hidden = !starting && !running;
            cameraTools.hidden = !running;
            cancelImage.hidden = !readingFile;
        };
        const clearPreview = () => {
            if (previewUrl) URL.revokeObjectURL(previewUrl);
            previewUrl = null; previewImage.removeAttribute('src'); preview.hidden = true;
        };
        const getScanner = () => {
            if (typeof LibraryBarcodeReader === 'undefined') throw new Error('Pemindai belum termuat. Muat ulang halaman atau gunakan kode manual.');
            return scanner ||= new LibraryBarcodeReader();
        };
        const stopCamera = async () => {
            cameraGeneration++; clearTimeout(frameTimer); clearTimeout(zoomTimer);
            running = starting = false;
            stream?.getTracks().forEach(track => track.stop()); stream = null;
            if (video) { video.pause(); video.srcObject = null; }
            scanner?.cancel();
            if (activeCamera === stopCamera) activeCamera = null;
            readerNode.hidden = true; torchOn = false; torchButton.setAttribute('aria-pressed', 'false'); torchButton.textContent = 'Nyalakan lampu';
            syncControls();
        };
        const addLink = (container, label, href, primary = false) => {
            const url = new URL(href, location.href);
            if (url.origin !== location.origin) return;
            const link = element('a', label, 'btn btn-sm ' + (primary ? 'btn-primary' : 'btn-outline-primary'));
            link.href = url.href;
            container.append(link);
        };
        const details = rows => {
            const list = element('dl', undefined, 'scan-details');
            rows.forEach(([label, value]) => { list.append(element('dt', label), element('dd', value === null || value === undefined || value === '' ? '—' : String(value))); });
            return list;
        };
        const returnForm = data => {
            const token = document.querySelector('meta[name=csrf-token]')?.content;
            if (!token) return;
            const url = new URL(data.loan.return_url, location.href);
            if (url.origin !== location.origin) return;
            const nativeForm = element('form', undefined, 'scanner-return-form');
            nativeForm.method = 'POST'; nativeForm.action = url.href;
            const hidden = element('input'); hidden.type = 'hidden'; hidden.name = '_token'; hidden.value = token;
            const label = element('label', 'Kondisi saat dikembalikan', 'form-label');
            const select = element('select', undefined, 'form-select'); select.name = 'return_condition'; select.required = true; select.id = readerNode.id + '-condition'; label.htmlFor = select.id;
            [['baik', 'Baik'], ['rusak', 'Rusak'], ['hilang', 'Hilang']].forEach(([value, text]) => { const option = element('option', text); option.value = value; select.append(option); });
            const noteLabel = element('label', 'Catatan (opsional)', 'form-label mt-2');
            const note = element('input', undefined, 'form-control'); note.name = 'return_notes'; note.maxLength = 2000; note.id = readerNode.id + '-notes'; noteLabel.htmlFor = note.id;
            const hint = element('p', 'Denda aktual dihitung saat disimpan. Jika hilang, hanya penggantian harga buku: ' + (data.replacement_price ? money(data.replacement_price) : 'lengkapi harga buku melalui Data Buku dahulu') + '.', 'small text-secondary mt-2');
            const button = element('button', 'Konfirmasi pengembalian', 'btn btn-primary mt-2'); button.type = 'submit';
            nativeForm.append(hidden, label, select, noteLabel, note, hint, button);
            nativeForm.addEventListener('submit', event => {
                if (!confirm('Simpan pengembalian / kehilangan ' + data.inventory_code + ' milik ' + data.loan.student_name + '? Biaya dihitung saat disimpan.')) event.preventDefault();
            });
            result.append(nativeForm);
        };
        const render = data => {
            result.replaceChildren(); result.hidden = false;
            result.append(element('h3', data.kind === 'member' ? data.name : data.title));
            const actions = element('div', undefined, 'scan-result-actions');
            if (data.kind === 'member') {
                result.append(details([['Kode anggota', data.member_code], ['Barcode', data.barcode], ['NIS', data.nis], ['Kelas', data.class_name], ['Jenis kelamin', data.gender], ['Usia', data.age ? data.age + ' tahun' : null], ['Email', data.email], ['Telepon', data.phone], ['Status', data.active ? 'Aktif' : 'Nonaktif'], ['Pinjaman aktif', data.active_count], ['Terlambat', data.late_count]]));
                addLink(actions, 'Data siswa', data.urls.edit); addLink(actions, 'Kartu / Barcode', data.urls.card);
                if (data.active) addLink(actions, 'Catat peminjaman', data.urls.borrow, true);
            } else {
                const labels = { available: 'Tersedia', borrowed: 'Sedang dipinjam', reserved: 'Disiapkan untuk reservasi', archived: 'Nonaktif / arsip', rusak: 'Rusak', hilang: 'Hilang' };
                result.append(details([['Kode inventaris', data.inventory_code], ['Barcode', data.barcode], ['Penulis', data.author], ['Rak', data.shelf_location], ['Kondisi', data.condition], ['Status', labels[data.status] || data.status]]));
                if (data.loan) result.append(details([['Peminjam', data.loan.student_name + ' · ' + data.loan.member_code], ['Kode transaksi', data.loan.loan_code], ['Batas kembali', data.loan.due_date], ['Keterlambatan', data.loan.late_days + ' hari'], ['Perkiraan denda', money(data.loan.estimated_fine)]]));
                if (data.hold) result.append(details([['Reservasi untuk', data.hold.student_name], ['Ambil sebelum', data.hold.expires_at + ' WIB']]));
                addLink(actions, 'Data eksemplar', data.urls.copies); addLink(actions, 'Cetak label', data.urls.label);
            }
            result.append(actions);
            if (data.kind === 'copy' && data.loan && panel.dataset.mode !== 'loan') returnForm(data);
        };
        const refreshLoanSubmit = () => {
            const button = document.querySelector('[data-loan-submit]');
            if (button) button.disabled = !document.querySelector('#member_id option[value]:not([value=""])') || !document.querySelector('[data-loan-copy-list] input');
        };
        const fillLoan = data => {
            if (data.kind === 'member') {
                const select = document.getElementById('member_id');
                if (!select) return;
                if (!data.active) {
                    select.value = ''; select.dispatchEvent(new Event('change', { bubbles: true }));
                    message('Akun siswa nonaktif. Pilihan siswa dikosongkan; pindai kartu siswa yang aktif.', 'error'); return;
                }
                let option = Array.from(select.options).find(item => item.value === String(data.id));
                if (!option) { option = element('option', data.name + ' — ' + data.member_code); option.value = String(data.id); select.append(option); }
                select.value = String(data.id); select.dispatchEvent(new Event('change', { bubbles: true }));
                refreshLoanSubmit();
                message('Siswa dipilih: ' + data.name + '. Lanjutkan scan eksemplar.', 'success');
                return;
            }
            if (!data.can_borrow) { message(data.borrow_reason || 'Eksemplar belum dapat dipinjam.', 'error'); return; }
            const list = document.querySelector('[data-loan-copy-list]');
            if (!list) return;
            let checkbox = Array.from(list.querySelectorAll('input[name="book_copy_ids[]"]')).find(item => item.value === String(data.id));
            if (checkbox?.checked) { message('Eksemplar ' + data.inventory_code + ' sudah dipilih. Tidak ditambahkan dua kali.', 'success'); return; }
            const max = Number(panel.dataset.maxCopies || 3);
            if (list.querySelectorAll('input[name="book_copy_ids[]"]:checked').length >= max) { message('Pilihan sudah mencapai batas ' + max + ' eksemplar. Hapus salah satu pilihan terlebih dahulu.', 'error'); return; }
            if (!checkbox) {
                const row = element('div', undefined, 'form-check mb-2');
                checkbox = element('input', undefined, 'form-check-input'); checkbox.type = 'checkbox'; checkbox.name = 'book_copy_ids[]'; checkbox.value = String(data.id); checkbox.id = 'copy-' + data.id;
                const label = element('label', data.title + ' — ' + data.inventory_code, 'form-check-label'); label.htmlFor = checkbox.id;
                row.append(checkbox, label); list.querySelector('[data-copy-empty]')?.remove(); list.append(row);
            }
            checkbox.checked = true; checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            refreshLoanSubmit();
            message('Eksemplar dipilih: ' + data.inventory_code + '. Periksa formulir sebelum menyimpan peminjaman.', 'success');
        };
        const lookup = async code => {
            const cleaned = code.trim();
            if (!cleaned || processing || readingFile) return;
            if (panel.dataset.mode !== 'login' && /^RPL1:/i.test(cleaned)) {
                input.value = ''; message('Ini QR login pribadi. Untuk pelayanan perpustakaan, scan barcode identitas RPS pada kartu.', 'error'); return;
            }
            if (starting) { message('Hentikan atau tunggu kamera siap sebelum mencari kode.', 'error'); return; }
            processing = true; syncControls();
            await stopCamera();
            result.hidden = true;
            const requestGeneration = ++generation;
            message('Kode terbaca. Mencari data…');
            try {
                if (panel.dataset.mode === 'login') {
                    const token = cleaned.toUpperCase();
                    input.value = ''; clearPreview(); choices.hidden = true;
                    if (!/^RPL1:[A-F0-9]{64}$/.test(token)) throw new Error('Gunakan QR login pada kartu siswa terbaru. Barcode identitas RPS dan label buku tidak bisa dipakai untuk login.');
                    message('QR login terbaca. Sedang masuk…');
                    const response = await fetch(panel.dataset.loginUrl, { method: 'POST', credentials: 'same-origin', cache: 'no-store',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' }, body: JSON.stringify({ student_qr: token }) });
                    if (response.status === 429) throw new Error('Terlalu banyak percobaan. Tunggu satu menit lalu coba lagi.');
                    if (response.status === 419) throw new Error('Halaman login sudah kedaluwarsa. Muat ulang halaman lalu scan kembali.');
                    if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('Login belum dapat diproses. Muat ulang halaman dan coba lagi.');
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.errors?.student_qr?.[0] || data.message || 'QR login tidak berlaku.');
                    const destination = new URL(data.redirect, location.href);
                    if (destination.origin !== location.origin) throw new Error('Alamat tujuan login tidak valid.');
                    location.assign(destination.href); return;
                }
                const url = new URL(panel.dataset.lookupUrl, location.href);
                url.searchParams.set('code', cleaned); url.searchParams.set('kind', kind.value);
                if (panel.dataset.mode === 'loan') {
                    const memberId = document.getElementById('member_id')?.value;
                    if (memberId) url.searchParams.set('member_id', memberId);
                }
                const response = await fetch(url.href, { credentials: 'same-origin', headers: { Accept: 'application/json' }, cache: 'no-store' });
                if (response.status === 401 || response.status === 403) throw new Error('Sesi login berakhir atau akses tidak tersedia. Masuk kembali sebagai petugas.');
                if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('Data belum dapat dimuat. Muat ulang halaman lalu coba kembali.');
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Kode terbaca, tetapi datanya belum dapat dimuat.');
                if (requestGeneration !== generation) return;
                render(data);
                if (panel.dataset.mode === 'visit') {
                    const select = document.getElementById('visit-member-id');
                    if (data.kind !== 'member' || !data.active) {
                        select.value = ''; message('Pilih siswa dengan akun aktif untuk mencatat kunjungan.', 'error');
                    } else {
                        let option = [...select.options].find(item => item.value === String(data.id));
                        if (!option) { option = element('option', data.name + ' · ' + data.member_code); option.value = String(data.id); select.append(option); }
                        select.value = String(data.id);
                        message('Siswa dipilih: ' + data.name + '. Klik Catat kunjungan hari ini untuk menyimpan.', 'success');
                    }
                } else if (panel.dataset.mode === 'loan') fillLoan(data);
                else message(data.kind === 'member' ? 'Identitas siswa ditemukan.' : data.loan ? 'Pinjaman aktif ditemukan. Periksa data sebelum mengonfirmasi pengembalian.' : 'Data eksemplar ditemukan. Tidak ada pengembalian yang disimpan otomatis.', 'success');
                input.value = ''; input.focus();
            } catch (error) { message(error.message || 'Koneksi bermasalah. Coba kembali.', 'error'); }
            finally { processing = false; syncControls(); }
        };
        form.addEventListener('submit', event => { event.preventDefault(); void lookup(input.value); });
        const showCodes = async results => {
            if (panel.dataset.mode === 'login') {
                results = results.filter(item => /^RPL1:[A-F0-9]{64}$/i.test(item.text.trim()));
                if (!results.length) { message('QR login belum ditemukan. Gunakan bagian QR login pada kartu siswa terbaru.', 'error'); return; }
                if (new Set(results.map(item => item.text.trim().toUpperCase())).size > 1) { message('Ada beberapa QR login. Potong gambar atau arahkan kamera ke satu kartu siswa saja.', 'error'); return; }
            } else {
                const hasLoginQr = results.some(item => /^RPL1:/i.test(item.text.trim()));
                results = results.filter(item => !/^RPL1:/i.test(item.text.trim()));
                if (hasLoginQr && !results.length) { message('Ini QR login pribadi. Untuk pelayanan perpustakaan, scan barcode identitas RPS pada kartu.', 'error'); return; }
            }
            const codes = [...new Map(results.map(item => [item.text.trim(), item])).values()].filter(item => item.text.trim().length > 0);
            choices.replaceChildren(); choices.hidden = true;
            if (!codes.length) { message('Kode belum terbaca. Potong gambar mendekati kartu/label, pastikan seluruh kode terlihat, lalu coba lagi.', 'error'); return; }
            if (codes.length === 1) {
                const code = codes[0].text.trim();
                if (code.length > 100) { message('QR terbaca, tetapi isinya terlalu panjang. Gunakan QR berisi kode anggota atau kode inventaris perpustakaan.', 'error'); return; }
                input.value = code; await lookup(code); return;
            }
            choices.hidden = false;
            choices.append(element('p', codes.length + ' kode ditemukan. Pilih kode yang ingin digunakan:', 'fw-semibold mb-2'));
            const list = element('div', undefined, 'scan-code-choices');
            codes.forEach((item, index) => {
                const code = item.text.trim();
                const button = element('button', panel.dataset.mode === 'login' ? 'QR login ' + (index + 1) : item.format + ' · ' + (code.length > 100 ? code.slice(0, 60) + '… (bukan kode perpustakaan)' : code), 'btn btn-outline-primary');
                button.type = 'button'; button.disabled = code.length > 100;
                button.addEventListener('click', () => { input.value = code; void lookup(code); });
                list.append(button);
            });
            choices.append(list); message('Beberapa kode terbaca. Belum ada data yang dipilih.', 'success');
        };
        const cameraError = error => {
            if (error.name === 'NotAllowedError' || error.name === 'SecurityError') return 'Izin kamera belum diberikan. Izinkan kamera pada pengaturan situs, lalu coba lagi.';
            if (error.name === 'NotFoundError') return 'Kamera tidak ditemukan. Hubungkan kamera atau pilih gambar kartu/label.';
            if (error.name === 'NotReadableError') return 'Kamera sedang dipakai aplikasi lain. Tutup aplikasi tersebut lalu coba lagi.';
            return 'Kamera belum dapat dibuka. Coba kamera lain, muat ulang halaman, atau pilih gambar kartu/label.';
        };
        const startCamera = async (deviceId = '') => {
            if (processing || readingFile) return;
            await stopCamera();
            if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) { message('Kamera membutuhkan HTTPS atau localhost. Gunakan link HTTPS, pilih gambar, atau ketik kode.', 'error'); return; }
            if (activeCamera) await activeCamera();
            const token = ++cameraGeneration;
            starting = true; activeCamera = stopCamera; syncControls(); clearPreview(); choices.hidden = result.hidden = true;
            readerNode.hidden = false; message('Membuka kamera… Izinkan akses kamera jika diminta.');
            let opened = null;
            try {
                getScanner();
                opened = await navigator.mediaDevices.getUserMedia({ audio: false, video: {
                    ...(deviceId ? { deviceId: { exact: deviceId } } : { facingMode: { ideal: 'environment' } }),
                    width: { ideal: 1920 }, height: { ideal: 1080 }, frameRate: { ideal: 15, max: 30 },
                } });
                if (token !== cameraGeneration || document.hidden) { opened.getTracks().forEach(track => track.stop()); return; }
                stream = opened;
                video = element('video'); video.autoplay = true; video.muted = true; video.playsInline = true;
                video.setAttribute('aria-label', 'Pratinjau kamera, seluruh gambar dipindai');
                readerNode.replaceChildren(video); video.srcObject = stream; await video.play();
                if (token !== cameraGeneration || document.hidden) { opened.getTracks().forEach(track => track.stop()); return; }
                const track = stream.getVideoTracks()[0];
                const capabilities = track.getCapabilities?.() || {};
                if (capabilities.focusMode?.includes('continuous')) {
                    try { await track.applyConstraints({ advanced: [{ focusMode: 'continuous' }] }); } catch (_) { /* Use the camera's default autofocus. */ }
                }
                if (token !== cameraGeneration) return;
                starting = false; running = true; syncControls();
                torchButton.hidden = !capabilities.torch;
                zoomControl.parentElement.hidden = !(capabilities.zoom && capabilities.zoom.max > capabilities.zoom.min);
                if (capabilities.zoom) {
                    zoomControl.min = capabilities.zoom.min; zoomControl.max = Math.max(capabilities.zoom.min, Math.min(capabilities.zoom.max, 6)); zoomControl.step = capabilities.zoom.step || .1;
                    zoomControl.value = track.getSettings?.().zoom || capabilities.zoom.min;
                    zoomLabel.textContent = Number(zoomControl.value).toFixed(1) + '×';
                }
                // Camera discovery is optional: scanning still works if the browser blocks it.
                let devices = [];
                try { devices = await navigator.mediaDevices.enumerateDevices(); } catch (_) { /* Keep the open camera. */ }
                if (token !== cameraGeneration) return;
                cameraSelect.replaceChildren();
                devices.filter(device => device.kind === 'videoinput').forEach((device, i) => {
                    const option = element('option', device.label || 'Kamera ' + (i + 1)); option.value = device.deviceId; cameraSelect.append(option);
                });
                cameraSelect.value = track.getSettings?.().deviceId || deviceId || cameraSelect.options[0]?.value || '';
                cameraSelect.parentElement.hidden = cameraSelect.options.length < 2;
                message('Seluruh tampilan kamera dipindai. Arahkan kartu atau label hingga tulisannya terlihat jelas.');
                let frame = 0;
                const scanFrame = async () => {
                    if (!running || token !== cameraGeneration) return;
                    try {
                        if (video.readyState >= 2 && video.videoWidth) {
                            let results = await getScanner().decode(LibraryBarcodeReader.pixels(video, video.videoWidth, video.videoHeight), { variation: frame++ });
                            if (panel.dataset.mode === 'login') results = results.filter(item => /^RPL1:[A-F0-9]{64}$/i.test(item.text.trim()));
                            else results = results.filter(item => !/^RPL1:/i.test(item.text.trim()));
                            if (!running || token !== cameraGeneration) return;
                            if (results.length) { await stopCamera(); await showCodes(results); return; }
                            if (frame === 24) message('Belum terbaca. Dekatkan kartu, hindari pantulan cahaya, atau gunakan zoom/lampu jika tersedia.');
                        }
                    } catch (error) {
                        if (token !== cameraGeneration) return;
                        await stopCamera(); message(error.message, 'error'); return;
                    }
                    frameTimer = setTimeout(scanFrame, 120);
                };
                void scanFrame();
            } catch (error) {
                opened?.getTracks().forEach(track => track.stop());
                if (token !== cameraGeneration) return;
                await stopCamera(); message(cameraError(error), 'error');
            }
        };
        cameraButton.addEventListener('click', () => { if (!starting && !running) void startCamera(); });
        cameraSelect.addEventListener('change', () => { void startCamera(cameraSelect.value); });
        stopButton.addEventListener('click', async () => { await stopCamera(); message('Kamera dihentikan. Kolom kode siap digunakan.'); input.focus(); });
        zoomControl.addEventListener('input', () => {
            zoomLabel.textContent = Number(zoomControl.value).toFixed(1) + '×'; clearTimeout(zoomTimer);
            zoomTimer = setTimeout(async () => {
                const track = stream?.getVideoTracks()[0]; if (!track || !running) return;
                try { await track.applyConstraints({ advanced: [{ zoom: Number(zoomControl.value) }] }); }
                catch (_) { message('Zoom belum didukung kamera ini. Dekatkan kartu secara perlahan.'); }
            }, 150);
        });
        torchButton.addEventListener('click', async () => {
            const track = stream?.getVideoTracks()[0]; if (!track || !running) return;
            try {
                const enabled = !torchOn;
                await track.applyConstraints({ advanced: [{ torch: enabled }] });
                if (!running || stream?.getVideoTracks()[0] !== track) return;
                torchOn = enabled; torchButton.setAttribute('aria-pressed', String(torchOn)); torchButton.textContent = torchOn ? 'Matikan lampu' : 'Nyalakan lampu';
            } catch (_) { if (running) message('Lampu kamera belum dapat digunakan. Cari tempat dengan cahaya lebih terang.'); }
        });
        imageButton.addEventListener('click', () => fileInput.click());
        const cancelFile = () => {
            fileGeneration++; readingFile = false; scanner?.cancel(); fileInput.value = ''; syncControls();
        };
        cancelImage.addEventListener('click', () => { cancelFile(); clearPreview(); message('Pembacaan gambar dibatalkan.'); });
        panel.addEventListener('barcode-scanner:stop', () => { cancelFile(); void stopCamera(); clearPreview(); choices.hidden = true; input.value = ''; message(''); });
        fileInput.addEventListener('change', async () => {
            const file = fileInput.files?.[0]; if (!file || processing || starting || readingFile) return;
            if (file.size > 20 * 1024 * 1024) { message('Gambar maksimal 20 MB. Potong gambar atau simpan sebagai JPG lalu coba lagi.', 'error'); fileInput.value = ''; return; }
            await stopCamera();
            const token = ++fileGeneration;
            readingFile = true; syncControls(); clearPreview(); choices.hidden = result.hidden = true;
            message('Membaca detail gambar…');
            try {
                const reader = getScanner();
                const pixels = await reader.image(file);
                if (token !== fileGeneration) return;
                previewUrl = URL.createObjectURL(file); previewImage.src = previewUrl; preview.hidden = false;
                const results = await reader.decode(pixels, { thorough: true }, text => { if (token === fileGeneration) message(text); });
                if (token !== fileGeneration) return;
                readingFile = false; syncControls(); await showCodes(results);
            } catch (error) { if (token === fileGeneration) message(error.message || 'Gambar belum dapat dibaca. Coba foto yang lebih dekat.', 'error'); }
            finally { if (token === fileGeneration) { readingFile = false; fileInput.value = ''; syncControls(); } }
        });
        window.addEventListener('pagehide', () => { generation++; cancelFile(); void stopCamera(); clearPreview(); });
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) return;
            if (readingFile) { cancelFile(); message('Pembacaan gambar dihentikan karena halaman ditinggalkan. Pilih gambar untuk mencoba lagi.'); }
            if (running || starting) { void stopCamera(); message('Kamera dihentikan karena halaman ditinggalkan.'); }
        });
        syncControls();
    });
})();
