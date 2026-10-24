<!-- Bildirim Zili Bileşeni -->
<?php
// Bu include kullanıcı sayfalarında kullanılır; $csrf_token_bildirim sayfada zaten
// csrf_token() çağrılmışsa oturumdan okunur (aksi halde boş kalır ve POST reddedilir).
$csrf_token_bildirim = function_exists('csrf_token') ? csrf_token() : '';
?>
<script>window.KULUPNET_CSRF_TOKEN = <?php echo json_encode($csrf_token_bildirim, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>;</script>
<div class="bildirim-wrapper" id="bildirimWrapper">
    <button class="bildirim-btn" id="bildirimBtn" title="Bildirimler" onclick="toggleBildirimler()">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:18px;height:18px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
        <span class="bildirim-badge" id="bildirimBadge" style="display:none;">0</span>
    </button>
    <div class="bildirim-dropdown" id="bildirimDropdown">
        <div class="bildirim-header">
            <div class="bildirim-tabs">
                <button class="bildirim-tab active" id="tabYeni" onclick="degistirTab('yeni')">Yeni</button>
                <button class="bildirim-tab" id="tabGecmis" onclick="degistirTab('gecmis')">Geçmiş</button>
            </div>
            <button class="bildirim-tumunu-oku" id="tumunuOkuBtn" onclick="tumunuOkunduIsaretle()" title="Tümünü okundu işaretle">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </button>
        </div>
        <div class="bildirim-list" id="bildirimList">
            <div class="bildirim-loading">Yükleniyor...</div>
        </div>
    </div>
</div>

<style>
    .bildirim-wrapper {
        position: relative;
        display: inline-flex;
        align-items: center;
    }
    .bildirim-btn {
        background: none; border: none; color: #94a3b8; cursor: pointer;
        padding: 0.5rem; border-radius: 0.5rem; transition: all 0.2s;
        position: relative; display: flex; align-items: center; justify-content: center;
    }
    .bildirim-btn:hover { color: #f1f5f9; background: rgba(255,255,255,0.05); }
    .bildirim-badge {
        position: absolute; top: 2px; right: 2px;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white; font-size: 0.6rem; font-weight: 700;
        min-width: 16px; height: 16px; border-radius: 999px;
        display: flex; align-items: center; justify-content: center;
        padding: 0 4px; line-height: 1; box-shadow: 0 2px 8px rgba(239,68,68,0.4);
        animation: badgePulse 2s ease-in-out infinite;
    }
    @keyframes badgePulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    .bildirim-dropdown {
        position: absolute; top: calc(100% + 8px); right: 0;
        width: 360px; max-height: 440px;
        background: rgba(15, 23, 42, 0.97); backdrop-filter: blur(24px);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 1rem; box-shadow: 0 25px 60px rgba(0,0,0,0.5);
        z-index: 1000; display: none; overflow: hidden;
        animation: dropdownSlide 0.2s ease forwards;
    }
    .bildirim-dropdown.active { display: block; }
    @keyframes dropdownSlide {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .bildirim-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .bildirim-tabs {
        display: flex; gap: 0.25rem; background: rgba(255,255,255,0.03);
        padding: 0.2rem; border-radius: 0.5rem;
    }
    .bildirim-tab {
        background: none; border: none; padding: 0.3rem 0.75rem;
        border-radius: 0.4rem; font-size: 0.75rem; font-weight: 600;
        color: #64748b; cursor: pointer; transition: all 0.2s;
    }
    .bildirim-tab:hover { color: #94a3b8; }
    .bildirim-tab.active { background: rgba(99,102,241,0.15); color: #818cf8; }
    
    .bildirim-tumunu-oku {
        background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2);
        color: #818cf8; padding: 0.3rem 0.6rem; border-radius: 0.5rem;
        cursor: pointer; font-size: 0.7rem; font-weight: 600;
        display: flex; align-items: center; gap: 0.25rem;
        transition: all 0.2s; font-family: 'Inter', sans-serif;
    }
    .bildirim-tumunu-oku:hover { background: rgba(99,102,241,0.2); }
    .bildirim-list {
        max-height: 370px; overflow-y: auto; padding: 0.5rem;
    }
    .bildirim-list::-webkit-scrollbar { width: 4px; }
    .bildirim-list::-webkit-scrollbar-track { background: transparent; }
    .bildirim-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 2px; }
    .bildirim-item {
        display: flex; gap: 0.75rem; padding: 0.75rem;
        border-radius: 0.75rem; transition: all 0.2s; cursor: pointer;
        text-decoration: none; border: 1px solid transparent;
    }
    .bildirim-item.okunmus { opacity: 0.6; }
    .bildirim-item:hover { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.06); }
    .bildirim-icon {
        width: 36px; height: 36px; border-radius: 0.625rem;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .bildirim-icon svg { width: 18px; height: 18px; }
    .bildirim-icon.duyuru { background: rgba(99,102,241,0.15); color: #818cf8; }
    .bildirim-icon.toplanti { background: rgba(245,158,11,0.15); color: #fbbf24; }
    .bildirim-icon.etkinlik { background: rgba(16,185,129,0.15); color: #34d399; }
    .bildirim-content { flex: 1; min-width: 0; }
    .bildirim-content-title {
        font-size: 0.8rem; font-weight: 600; color: #e2e8f0;
        margin-bottom: 0.15rem;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .bildirim-content-mesaj {
        font-size: 0.75rem; color: #94a3b8; line-height: 1.4;
        display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .bildirim-zaman {
        font-size: 0.65rem; color: #475569; margin-top: 0.25rem;
        display:flex; align-items:center; gap:0.25rem;
    }
    .bildirim-empty {
        text-align: center; padding: 2.5rem 1rem; color: #475569;
    }
    .bildirim-empty svg { width: 40px; height: 40px; margin-bottom: 0.75rem; color: #334155; }
    .bildirim-empty p { font-size: 0.85rem; }
    .bildirim-loading {
        text-align: center; padding: 2rem; color: #64748b; font-size: 0.85rem;
    }

    @media (max-width: 480px) {
        .bildirim-dropdown { width: 300px; right: -40px; }
    }
</style>

<script>
(function() {
    let bildirimVerileri = [];
    let aktifTab = 'yeni'; // 'yeni' veya 'gecmis'
    let bildirimAcik = false;

    function apiUrl(aksiyon, parametreler = '') {
        const currentPath = window.location.pathname;
        const projeIdx = currentPath.indexOf('/proje2/');
        let base = '';
        if (projeIdx >= 0) {
            base = currentPath.substring(0, projeIdx + 8);
        } else {
            base = 'api/';
            return 'api/bildirimler.php?aksiyon=' + aksiyon + parametreler;
        }
        return base + 'api/bildirimler.php?aksiyon=' + aksiyon + parametreler;
    }

    function linkUrl(link) {
        const currentPath = window.location.pathname;
        const projeIdx = currentPath.indexOf('/proje2/');
        if (projeIdx >= 0) {
            const base = currentPath.substring(0, projeIdx + 8);
            return base + link;
        }
        return link;
    }

    window.toggleBildirimler = function() {
        const dropdown = document.getElementById('bildirimDropdown');
        bildirimAcik = !bildirimAcik;
        if (bildirimAcik) {
            dropdown.classList.add('active');
            bildirimleriGetir();
        } else {
            dropdown.classList.remove('active');
        }
    };

    window.degistirTab = function(tab) {
        if (aktifTab === tab) return;
        aktifTab = tab;
        
        document.getElementById('tabYeni').classList.toggle('active', tab === 'yeni');
        document.getElementById('tabGecmis').classList.toggle('active', tab === 'gecmis');
        document.getElementById('tumunuOkuBtn').style.display = tab === 'yeni' ? 'flex' : 'none';
        
        bildirimleriGetir();
    };

    document.addEventListener('click', function(e) {
        const wrapper = document.getElementById('bildirimWrapper');
        if (wrapper && !wrapper.contains(e.target)) {
            const dropdown = document.getElementById('bildirimDropdown');
            if (dropdown) dropdown.classList.remove('active');
            bildirimAcik = false;
        }
    });

    function zamanFarki(tarih) {
        const simdi = new Date();
        const t = new Date(tarih.replace(' ', 'T'));
        const fark = Math.floor((simdi - t) / 1000);
        if (fark < 60) return 'Az önce';
        if (fark < 3600) return Math.floor(fark / 60) + ' dk önce';
        if (fark < 86400) return Math.floor(fark / 3600) + ' saat önce';
        if (fark < 604800) return Math.floor(fark / 86400) + ' gün önce';
        return t.toLocaleDateString('tr-TR');
    }

    function turIkon(tur) {
        const ikonlar = {
            'duyuru': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>',
            'toplanti': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>',
            'etkinlik': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>'
        };
        return ikonlar[tur] || ikonlar['duyuru'];
    }

    function bildirimleriGetir() {
        const params = aktifTab === 'gecmis' ? '&okunanlar=1' : '';
        fetch(apiUrl('getir', params))
            .then(r => r.json())
            .then(data => {
                if (data.hata) return;
                bildirimVerileri = data.bildirimler || [];
                renderBildirimler();
                if (aktifTab === 'yeni') {
                    guncelleBadge(data.okunmamis_sayisi || 0);
                }
            })
            .catch(() => {});
    }

    function renderBildirimler() {
        const list = document.getElementById('bildirimList');
        if (bildirimVerileri.length === 0) {
            const mesaj = aktifTab === 'yeni' ? 'Tüm bildirimler okundu!' : 'Henüz geçmiş bildirim yok.';
            list.innerHTML = `
                <div class="bildirim-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <p>${mesaj}</p>
                </div>`;
            return;
        }
        list.innerHTML = bildirimVerileri.map(b => `
            <div class="bildirim-item ${aktifTab === 'gecmis' ? 'okunmus' : ''}" onclick="bildirimTikla('${b.tur}', ${b.id}, '${b.link}')">
                <div class="bildirim-icon ${b.tur}">${turIkon(b.tur)}</div>
                <div class="bildirim-content">
                    <div class="bildirim-content-title">${htmlEscape(b.baslik)}</div>
                    <div class="bildirim-content-mesaj">${htmlEscape(b.mesaj)}</div>
                    <div class="bildirim-zaman">${zamanFarki(b.tarih)}</div>
                </div>
            </div>
        `).join('');
    }

    function htmlEscape(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function guncelleBadge(sayi) {
        const badge = document.getElementById('bildirimBadge');
        if (sayi > 0) {
            badge.textContent = sayi > 99 ? '99+' : sayi;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    window.bildirimTikla = function(tur, id, link) {
        if (aktifTab === 'yeni') {
            // Okundu işaretle
            const formData = new FormData();
            formData.append('tur', tur);
            formData.append('ilgili_id', id);
            formData.append('csrf_token', window.KULUPNET_CSRF_TOKEN || '');
            fetch(apiUrl('oku'), { method: 'POST', body: formData })
                .then(() => {
                    bildirimVerileri = bildirimVerileri.filter(b => !(b.tur === tur && b.id === id));
                    renderBildirimler();
                    guncelleBadge(bildirimVerileri.length);
                });
        }
        // Sayfaya yönlendir
        window.location.href = linkUrl(link);
    };

    window.tumunuOkunduIsaretle = function() {
        if (bildirimVerileri.length === 0 || aktifTab !== 'yeni') return;
        const idler = bildirimVerileri.map(b => ({ tur: b.tur, id: b.id }));
        const formData = new FormData();
        formData.append('idler', JSON.stringify(idler));
        formData.append('csrf_token', window.KULUPNET_CSRF_TOKEN || '');
        fetch(apiUrl('tumunu_oku'), { method: 'POST', body: formData })
            .then(() => {
                bildirimVerileri = [];
                renderBildirimler();
                guncelleBadge(0);
            });
    };

    // Sayfa yüklendiğinde badge'i güncelle (sadece unread çekerek)
    fetch(apiUrl('getir'))
        .then(r => r.json())
        .then(data => guncelleBadge(data.okunmamis_sayisi || 0));

    // Her 60 saniyede bir unread sayısını kontrol et
    setInterval(() => {
        fetch(apiUrl('getir'))
            .then(r => r.json())
            .then(data => {
                guncelleBadge(data.okunmamis_sayisi || 0);
                // Eğer dropdown açıksa ve 'yeni' tabındaysak listeyi de tazele
                if (bildirimAcik && aktifTab === 'yeni') {
                    bildirimVerileri = data.bildirimler || [];
                    renderBildirimler();
                }
            });
    }, 60000);
})();
</script>
