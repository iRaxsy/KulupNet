/* KulüpNet — Toast Notification System */
(function () {
    // Create container if not present
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        container.setAttribute('role', 'alert');
        container.setAttribute('aria-live', 'polite');
        document.body.appendChild(container);
    }

    const MAX_TOASTS = 5;

    window.showToast = function (message, type = 'success', duration = 3500) {
        if (container.children.length >= MAX_TOASTS) {
            container.firstChild.remove();
        }

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span class="toast-msg">${message}</span>
            <button class="toast-close" aria-label="Kapat">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        `;

        container.appendChild(toast);

        const remove = () => {
            toast.classList.add('removing');
            toast.addEventListener('animationend', () => toast.remove());
        };

        toast.querySelector('.toast-close').addEventListener('click', remove);
        setTimeout(remove, duration);
    };

    // Auto-show from URL mesaj parameter
    const params = new URLSearchParams(window.location.search);
    const msg = params.get('mesaj');
    if (msg) {
        const messages = {
            'basari':       ['İşlem başarılı!', 'success'],
            'hata':         ['Bir hata oluştu.', 'error'],
            'etkinlik_katilimbasarili': ['Etkinliğe katılım kaydedildi.', 'success'],
            'etkinlik_kayitli':         ['Bu etkinliğe zaten kayıtlısınız.', 'info'],
            'kulup_katilma_basarisiz':  ['Zaten üyesiniz veya yetkiniz yok.', 'error'],
            'kulup_katilma_basarili':   ['Kulübe başarıyla katıldınız.', 'success'],
            'kulup_admin':              ['Kulüp yöneticisi ayrılabilir.', 'info'],
            'kulup_ayrilma_basarisiz':  ['Üyelik bulunamadı.', 'error'],
            'kulup_ayrilma_basarili':   ['Kulüpten ayrıldınız.', 'success'],
            'kayit_olmus':              ['Kaydınız alınmıştır, giriş yapabilirsiniz.', 'success'],
            'giris_yapilmadi':          ['Önce giriş yapın.', 'error'],
        };

        if (messages[msg]) {
            showToast(messages[msg][0], messages[msg][1]);
            // Clean URL without reload
            const url = new URL(window.location);
            url.searchParams.delete('mesaj');
            window.history.replaceState({}, '', url);
        }
    }
})();
