(() => {
    const printButton = document.querySelector('[data-print-member-card]');
    if (printButton) {
        printButton.hidden = false;
        printButton.addEventListener('click', () => window.print());
    }

    const preference = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (!('IntersectionObserver' in window) || typeof Element.prototype.animate !== 'function') return;
    const running = new Set();
    const observer = new IntersectionObserver(entries => {
        for (const entry of entries) {
            if (!entry.isIntersecting) continue;
            observer.unobserve(entry.target);
            if (preference.matches) continue;
            const animation = entry.target.animate([
                { opacity: 0, transform: 'translateY(12px)' },
                { opacity: 1, transform: 'translateY(0)' },
            ], { duration: 380, easing: 'ease-out' });
            running.add(animation);
            animation.addEventListener('finish', () => running.delete(animation), { once: true });
        }
    }, { threshold: 0.08 });
    document.querySelectorAll('[data-book-reveal]').forEach(card => observer.observe(card));
    preference.addEventListener('change', () => {
        if (preference.matches) for (const animation of running) animation.finish();
    });
})();
