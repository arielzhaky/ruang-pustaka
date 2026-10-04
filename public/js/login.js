(() => {
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
