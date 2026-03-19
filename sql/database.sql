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
    FOREIGN KEY (kulup_id) REFERENCES kulupler(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Örnek admin hesabı (şifre: 123456)
-- Not: Şifreler düz metin olarak saklanmaktadır.
INSERT INTO adminler (kullanici_adi, sifre) VALUES ('admin', '123456');
