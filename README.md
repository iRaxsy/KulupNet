# 🎯 KulüpNet - Kulüp & Etkinlik Yönetim Platformu

KulüpNet, üniversite topluluklarının ve öğrenci kulüplerinin tüm süreçlerini dijitalleştiren, modern, hızlı ve kullanıcı dostu bir yönetim sistemidir. Hem kulüp yöneticileri hem de öğrenciler için kapsamlı bir ekosistem sunar.

## ✨ Öne Çıkan Özellikler

### 👤 Kullanıcı Özellikleri
- **İnteraktif Dashboard:** Aktif kulüp, toplam etkinlik ve üye istatistiklerinin anlık takibi.
- **Gerçek Zamanlı Bildirim Sistemi:** Duyuru, toplantı ve etkinlikler için AJAX tabanlı anlık bildirimler.
- **Gelişmiş Kayıt Doğrulama:** Sadece güvenilir e-posta servisleri ve EDU uzantıları ile güvenli kayıt.
- **Profil ve Gizlilik:** E-posta, kulüpler ve etkinlik katılımı için detaylı gizlilik ayarları ve şık profil sayfası.
- **Toplantı Kontrolü:** Kulüp içi toplantıları takip etme ve katılım sağlama.
- **Kulüp & Etkinlik Yönetimi:** Kulüplere katılma, etkinlik takibi ve detaylı yönetim.
- **İnteraktif Takvim:** Etkinliklerin görsel ve düzenli takibi.
- **Duyurular ve Bildirimler:** Kulüplerden gelen güncel duyurular ve sistem içi bildirim takibi.
- **Öneri & Şikayet Sistemi:** Platformu geliştirmek veya kulüplere geri bildirim vermek için özel modül.
- **Tema Desteği:** Modern Karanlık (Dark) ve Açık (Light) mod seçeneği.

### 🔐 Admin & Yönetici Özellikleri
- **Kapsamlı Yönetim Paneli:** Tüm site istatistiklerini içeren detaylı admin ana sayfası.
- **Hızlı İşlemler:** Tek tıkla yeni kulüp veya etkinlik ekleme.
- **Üye Yönetimi:** Kullanıcıları yasaklama, rollerini (Admin, Organizatör, Üye vb.) güncelleme.
- **İçerik Moderasyonu:** Duyuruları, toplantıları ve etkinlikleri tam yetkiyle yönetme.
- **Geri Bildirim Takibi:** Kullanıcılardan gelen öneri ve şikayetleri görüntüleme/yönetme.
- **Sistem İstatistikleri:** Biten ve aktif etkinliklerin analizi.

## 🛠️ Teknik Alt Yapı

- **Backend:** PHP 8.0+ (PDO prepared statement güvenliği, `str_ends_with` nedeniyle 8.0 altı desteklenmez), `mbstring` eklentisi gerekli
- **Veritabanı:** MySQL / MariaDB
- **Frontend:**
  - **HTML5 & CSS3**: Modern "Glassmorphism" tasarımı, kendi tasarım sistemimiz (`content/css/tokens.css` + `content/css/ui.css`) ile.
  - **JavaScript**: Tema yönetimi, mobil drawer, scroll animasyonları, toast bildirimleri ve form UX'i için (`content/js/`).
- **Kütüphaneler & Kaynaklar:**
  - **SweetAlert2 (CDN)**: Yalnızca admin panelinde modal ve başarı/hata bildirimleri için (kullanıcı sayfalarında native `confirm()` + custom toast kullanılır).
  - **Google Fonts (Inter)**: Modern tipografi ve geniş ağırlık desteği.
  - **Heroicons**: Arayüz ikonları (Inline SVG olarak entegre edilmiştir).

## 🚀 Kurulum Adımları

1. **Hazırlık:** Bilgisayarınızda **XAMPP**, **WAMP** veya benzeri bir PHP/MySQL yerel sunucusunun yüklü olduğundan emin olun.

2. **Dosyaları Taşıma:** Tüm proje dosyalarını sunucunuzun root dizinine (`htdocs` veya `www`) kopyalayın.
3. **Veritabanı Oluşturma:**
   - PHPMyAdmin paneline girin.
   - `kulupnet` adında yeni bir veritabanı oluşturun (utf8mb4_turkish_ci).
   - `sql/database.sql` dosyasını bu veritabanına içe aktarın (Import).
4. **Bağlantı Yapılandırması:** `config/veritabani.php` ortam değişkenlerinden (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) kimlik bilgilerini okur. Apache'de `SetEnv`, nginx-fpm'de `clearenv`/`env` veya `.env` yükleyicisi ile ayarlayın. Yerel XAMPP'te varsayılan `root` + boş parola ile de çalışır.
5. **Çalıştırma:** Tarayıcınızdan `localhost/projenin_klasor_adi` adresine giderek sistemi kullanmaya başlayabilirsiniz.

## 🔑 Giriş Bilgileri

**Yönetici Paneli Girişi:** `/admin` dizininden erişilebilir.

`sql/database.sql` kurulumda `admin` kullanıcısını ve **değiştirilmesi gereken** yer tutucu bir parolayı seed eder. Kurulumdan hemen sonra giriş yapıp parolayı gücelleyin ve mümkünse `adminler` tablosundaki seed kaydını silin/yeniden üretin.

> Güvenlik notu: Depoda geçerli üretim parolaları tutulmaz. `sql/` ve `config/` dizinleri `.htaccess` ile web erişimine kapalıdır (Apache 2.4 `Require all denied`). nginx kullanıyorsanız bu dizinler için eşdeğer `location { internal; deny all; }` bloğunu site config'inize ekleyin.

## 📂 Klasör Yapısı

```text
├── admin/          # Yönetici Paneli Dosyaları
├── api/            # API Servisleri (Gelecek Özellikler İçin)
├── config/         # Veritabanı ve Sistem Yapılandırması
├── content/        # CSS, JS ve Medya Dosyaları
├── includes/       # Tema Parçaları ve Yardımcı PHP Dosyaları
├── sql/            # Veritabanı Şeması (SQL)
└── *.php           # Ana Sayfa ve Kullanıcı Modülleri
```

## 📜 Lisans

Bu proje **MIT Lisansı** altında lisanslanmıştır. Daha fazla bilgi için [LICENSE](LICENSE) dosyasına bakabilirsiniz.

---
⭐ Bu projeyi beğendiyseniz yıldız vermeyi unutmayın!
