# Avrupapazarı - PHP Anasayfa Klonu

Sade, temiz ve glow efektli marketplace anasayfası. Tüm dosyalar ayrı ayrı yapılandırıldı.

## 📁 Dosya Yapısı

```
php_site/
├── index.php                  # Ana sayfa
├── config.php                 # Yapılandırma + dil yönetimi + mock veriler
├── includes/
│   ├── header.php             # Üst logo, dark mode, dil, giriş butonları
│   ├── nav.php                # Kategori navigasyonu (Harita, Vasıtalar, vb.)
│   └── footer.php             # Alt bilgi
├── lang/
│   ├── tr.php                 # Türkçe (varsayılan)
│   ├── en.php                 # English
│   ├── nl.php                 # Nederlands
│   └── de.php                 # Deutsch
├── pages/
│   ├── vasitalar.php          # Kategori sayfaları
│   ├── emlak.php
│   ├── ikinci-el.php
│   ├── hizmetler.php
│   ├── is-ilanlari.php
│   ├── harita.php             # OpenStreetMap embed
│   └── _category_template.php # Ortak şablon
└── assets/
    ├── css/style.css          # Tüm stiller (~500 satır)
    └── js/script.js           # Dark mode toggle (localStorage)
```

## 🚀 Çalıştırma

**Yerel PHP sunucusu ile:**
```bash
cd php_site
php -S localhost:8000
```
Tarayıcıda: `http://localhost:8000`

**XAMPP / MAMP:** Klasörü `htdocs` içine kopyala, `http://localhost/php_site/` aç.

**Apache/Nginx:** Doğrudan web root'a yükle.

## ✨ Özellikler

- 🎨 **Sade & temiz tasarım** - Plus Jakarta Sans fontu, yeşil (#10b981) marka rengi
- 🌗 **Dark mode** - Toggle butonu, localStorage'a kaydeder, sistem tercihini algılar
- 🌍 **4 dil** - TR/EN/NL/DE (session tabanlı, sayfa tazelenmeden korunur)
- 💫 **Glow efektleri** - Kartlarda hover'da yumuşak yeşil parıltı, sabit transform yok (titremesiz)
- 📱 **Responsive** - Mobil/tablet/desktop tam uyumlu
- 🎴 **Bento grid** - 5 kategori kartı, üstte 2 büyük + altta 3 küçük
- 🏷️ **Son ilanlar** - 8 mock ilan kartı, hover glow'lu
- 📍 **Harita sayfası** - OpenStreetMap embed
- 🔘 **Sabit + İlan Ver** - Sağ ortada, yeşil floating action button

## 🎯 Kişiselleştirme

- **Renk değiştirme:** `assets/css/style.css` içinde `--brand: #10b981` satırını değiştir
- **Yeni dil ekleme:** `lang/` içine yeni bir `xx.php` dosyası kopyala, `config.php`'ye ekle
- **İlanları güncelleme:** `config.php` içindeki `$LISTINGS` dizisini düzenle
- **Kategori ekleme:** `config.php` içindeki `$CATEGORIES` dizisine ekle

## 📌 Notlar

- Backend/veritabanı YOK - tüm veriler `config.php` içinde mock olarak duruyor
- Dark mode `localStorage` üzerinden kalıcı çalışır
- Dil değişikliği PHP `session` üzerinden kalıcı
- Harici görsellerler Unsplash CDN'den gelir (internet gereklidir)
