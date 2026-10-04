(() => {
    const root = document.querySelector('.landing');
    const toggle = document.getElementById('motion-toggle');
    if (!root || !toggle) return;

    const preference = window.matchMedia('(prefers-reduced-motion: reduce)');
    let paused = false;
    const activeReveals = new Set();
    const syncMotion = () => {
        const stopped = preference.matches || paused;
        root.dataset.motion = stopped ? 'paused' : 'running';
        toggle.disabled = preference.matches;
        toggle.setAttribute('aria-pressed', String(stopped));
        toggle.querySelector('span').textContent = preference.matches
            ? 'Animasi nonaktif' : (paused ? 'Putar animasi' : 'Jeda animasi');
        for (const animation of activeReveals) {
            if (stopped) animation.finish();
        }
    };
    toggle.hidden = false;
    toggle.addEventListener('click', () => { paused = !paused; syncMotion(); });
    preference.addEventListener('change', syncMotion);
    syncMotion();

    if ('IntersectionObserver' in window && typeof Element.prototype.animate === 'function') {
        const observer = new IntersectionObserver(entries => {
            for (const entry of entries) {
                if (!entry.isIntersecting) continue;
                observer.unobserve(entry.target);
                if (preference.matches || paused) continue;
                const animation = entry.target.animate([
                    { opacity: 0, transform: 'translateY(18px)' },
                    { opacity: 1, transform: 'translateY(0)' },
                ], { duration: 550, easing: 'ease-out' });
                activeReveals.add(animation);
                animation.addEventListener('finish', () => activeReveals.delete(animation), { once: true });
            }
        }, { threshold: 0.12 });
        document.querySelectorAll('[data-reveal]').forEach(element => observer.observe(element));
    }
})();
