(() => {
    document.querySelectorAll('[data-selection-form]').forEach(form => {
        const items = [...form.querySelectorAll('[data-select-item]')];
        const all = form.querySelector('[data-select-all]');
        const update = () => {
            const count = items.filter(item => item.checked).length;
            form.querySelector('[data-selected-count]').textContent = count + ' dipilih';
            all.checked = items.length > 0 && count === items.length;
            all.indeterminate = count > 0 && count < items.length;
            form.querySelectorAll('[data-selection-submit]').forEach(button => { button.disabled = count === 0; });
        };
        all.addEventListener('change', () => { items.forEach(item => { item.checked = all.checked; }); update(); });
        items.forEach(item => item.addEventListener('change', update));
        update();
    });
})();
