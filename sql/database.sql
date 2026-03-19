-- Veritabanı oluşturma
CREATE DATABASE IF NOT EXISTS kulup_yonetimi CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
USE kulup_yonetimi;

-- Adminler tablosu
CREATE TABLE IF NOT EXISTS adminler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_adi VARCHAR(50) NOT NULL UNIQUE,
    sifre VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- Kulupler tablosu
CREATE TABLE IF NOT EXISTS kulupler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(100) NOT NULL,
    aciklama TEXT
) ENGINE=InnoDB;

-- Etkinlikler tablosu
CREATE TABLE IF NOT EXISTS etkinlikler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kulup_id INT,
    baslik VARCHAR(200) NOT NULL,
    aciklama TEXT,
    tarih DATETIME NOT NULL,
    konum VARCHAR(200),
    kisi_siniri INT DEFAULT NULL,
    FOREIGN KEY (kulup_id) REFERENCES kulupler(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Kullanıcılar tablosu
CREATE TABLE IF NOT EXISTS kullanicilar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(50) NOT NULL,
    soyad VARCHAR(50) NOT NULL,
    kullanici_adi VARCHAR(50) NOT NULL UNIQUE,
    sifre VARCHAR(255) NOT NULL,
    eposta VARCHAR(100) NOT NULL UNIQUE,
    cinsiyet ENUM('Erkek','Kadin') NOT NULL,
    rol ENUM('Admin','Yetkili','Organizatör','Üye') DEFAULT 'Üye',
    gizli_eposta TINYINT(1) DEFAULT 0,
    gizli_kulupler TINYINT(1) DEFAULT 0,
    gizli_etkinlikler TINYINT(1) DEFAULT 0,
    gizli_cinsiyet TINYINT(1) DEFAULT 0,
    kayit_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Kulüp üyelikleri
CREATE TABLE IF NOT EXISTS kulup_uyelikleri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id INT NOT NULL,
    kulup_id INT NOT NULL,
    kulup_rol ENUM('Yönetici','Başkan','Başkan Yardımcısı','Üye') DEFAULT 'Üye',
    katilma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
    FOREIGN KEY (kulup_id) REFERENCES kulupler(id) ON DELETE CASCADE,
    UNIQUE KEY unique_uyelik (kullanici_id, kulup_id)
) ENGINE=InnoDB;

-- Etkinlik katılımları
CREATE TABLE IF NOT EXISTS etkinlik_katilimlari (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id INT NOT NULL,
    etkinlik_id INT NOT NULL,
    katilma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
    FOREIGN KEY (etkinlik_id) REFERENCES etkinlikler(id) ON DELETE CASCADE,
    UNIQUE KEY unique_katilim (kullanici_id, etkinlik_id)
) ENGINE=InnoDB;

-- Öneri / Şikayet tablosu
CREATE TABLE IF NOT EXISTS oneri_sikayet (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id INT NOT NULL,
    tur ENUM('Öneri','Şikayet') NOT NULL,
    aciklama TEXT NOT NULL,
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Duyurular tablosu
CREATE TABLE IF NOT EXISTS duyurular (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tur VARCHAR(100) NOT NULL,
    kulup_id INT DEFAULT NULL,
    duyuru TEXT NOT NULL,
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kulup_id) REFERENCES kulupler(id) ON DELETE CASCADE
) ENGINE=InnoDB

-- Örnek Admin Hesabı
INSERT INTO adminler (kullanici_adi, sifre) VALUES ('admin', '123456');

-- Örnek Kulüpler
INSERT INTO kulupler (ad, aciklama) VALUES 
('Yazılım ve Teknoloji Kulübü', 'Yapay zeka, web geliştirme ve siber güvenlik alanlarında çalışmalar yapan öğrenci topluluğu.'),
('Girişimcilik Topluluğu', 'Yeni iş fikirlerini destekleyen ve networking imkanı sağlayan topluluk.'),
('Tiyatro ve Drama Kulübü', 'Sahne sanatlarına ilgi duyan öğrencileri bir araya getiren sanat kulübü.'),
('E-Spor Kulübü', 'Rekabetçi oyunlar ve turnuvalar düzenleyen modern spor kulübü.');

-- Örnek Etkinlikler
INSERT INTO etkinlikler (kulup_id, baslik, aciklama, tarih, konum) VALUES 
(1, 'Giriş Seviyesi Python Atölyesi', 'Python programlamaya giriş yapacağımız uygulamalı eğitim.', DATE_ADD(NOW(), INTERVAL 2 DAY), 'LAB-102'),
(2, 'Girişimcilik Zirvesi 2026', 'Mezun girişimcilerin tecrübelerini paylaşacağı panel.', DATE_ADD(NOW(), INTERVAL 5 DAY), 'Konferans Salonu'),
(4, 'Valorant Turnuvası', 'Kampüs içi ödüllü Valorant turnuvası kayıtları başladı.', DATE_ADD(NOW(), INTERVAL 7 DAY), 'Online / Discord'),
(1, 'Web Tasarım Yarışması', '24 saatlik kodlama yarışması (Hackathon).', DATE_ADD(NOW(), INTERVAL 14 DAY), 'Teknopark Binası');

-- Örnek Kullanıcı
INSERT INTO kullanicilar (ad, soyad, kullanici_adi, sifre, eposta, cinsiyet, rol) VALUES 
('İsmet Can', 'Türkmen', 'ismet', '123456', 'ismet@example.com', 'Erkek', 'Üye');
