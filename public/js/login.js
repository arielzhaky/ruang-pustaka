(() => {
    const emailForm = document.querySelector('[data-login-email-form]');
    const qrPanel = document.querySelector('[data-login-qr-panel]');
    const methods = document.querySelector('[data-login-methods]');
    if (emailForm && qrPanel && methods) {
        methods.hidden = false;
        methods.querySelectorAll('[data-login-method]').forEach(button => button.addEventListener('click', () => {
            const qr = button.dataset.loginMethod === 'qr';
            emailForm.hidden = qr; qrPanel.hidden = !qr;
            methods.querySelectorAll('button').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
            if (!qr) qrPanel.querySelector('[data-barcode-scanner]').dispatchEvent(new Event('barcode-scanner:stop'));
        }));
    }
    const password = document.getElementById('password');
    const toggle = document.getElementById('toggle-password');
    if (!password || !toggle) return;

    toggle.hidden = false;
    toggle.addEventListener('click', () => {
        const visible = password.type === 'password';
        password.type = visible ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', String(visible));
        toggle.setAttribute('aria-label', visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        toggle.querySelector('[data-eye-open]').hidden = visible;
        toggle.querySelector('[data-eye-closed]').hidden = !visible;
    });
})();
