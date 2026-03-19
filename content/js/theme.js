// KulüpNet Theme Toggle System
(function() {
    const savedTheme = localStorage.getItem('kulupnet-theme') || 'dark';
    if (savedTheme === 'light') {
        document.body.classList.add('light-theme');
    }

    const btn = document.getElementById('themeToggle');
    if (btn) {
        btn.addEventListener('click', function() {
            const isLight = document.body.classList.contains('light-theme');
            const newTheme = isLight ? 'dark' : 'light';
            if (newTheme === 'light') {
                document.body.classList.add('light-theme');
            } else {
                document.body.classList.remove('light-theme');
            }
            localStorage.setItem('kulupnet-theme', newTheme);
        });
    }
})();
