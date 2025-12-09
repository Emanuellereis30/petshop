(() => {
    const storageKey = 'petshop-theme';
    const root = document.documentElement;

    const applyTheme = (theme) => {
        const safeTheme = theme === 'dark' ? 'dark' : 'light';
        root.setAttribute('data-theme', safeTheme);
        const icon = document.querySelector('#themeToggle i');
        if (icon) {
            icon.classList.remove('fa-moon', 'fa-sun');
            icon.classList.add(safeTheme === 'dark' ? 'fa-sun' : 'fa-moon');
        }
    };

    const savedTheme = localStorage.getItem(storageKey);
    applyTheme(savedTheme === 'dark' ? 'dark' : 'light');

    window.addEventListener('DOMContentLoaded', () => {
        const toggleButton = document.getElementById('themeToggle');
        if (!toggleButton) {
            return;
        }

        toggleButton.addEventListener('click', () => {
            const nextTheme = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            applyTheme(nextTheme);
            localStorage.setItem(storageKey, nextTheme);
        });
    });
})();

