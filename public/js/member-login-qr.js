document.querySelectorAll('[data-login-qr-management]').forEach(form => {
    form.addEventListener('submit', event => {
        const action = event.submitter?.value;
        if (action === 'replace' && !confirm('Ganti QR login? QR lama tidak berlaku lagi. Cetak ulang kartu setelah diganti.')) event.preventDefault();
        if (action === 'revoke' && !confirm('Nonaktifkan QR login? Siswa masih bisa masuk menggunakan email dan kata sandi.')) event.preventDefault();
    });
});
