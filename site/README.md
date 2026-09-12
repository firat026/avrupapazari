# AvrupaPazari — cPanel kurulum

## 1. Dosyalar
Zip içeriğini `public_html` içine yükleyin (mevcut dosyaların üzerine yazın).
- `uploads/` klasörünüz sizde kalır; zip yalnızca `uploads/categories/` (yeni kategori görselleri) içerir.
- Sunucudaki `uploads/esnaf` klasörünü `uploads/businesses` olarak **yeniden adlandırın** (SQL bunu bekler).
- Artık kullanılmayan dosyaları sunucudan silin: `lang/`, `pages/home.php`, `pages/navigation.php`, `pages/check-users.php`,
  `import-postcodes.php`, `api/` klasörü (artık `ajax/`), `assets/css/navigation.css`, `assets/css/esnaf-rehberi.css`,
  `assets/css/kategori.css`, `assets/css/mobile/`, tüm `error_log` dosyaları, `admin/pages/`.

## 2. config.php
`DB_HOST / DB_NAME / DB_USER / DB_PASS` değerlerini cPanel MySQL bilgilerinizle doldurun.
Site alt klasördeyse `BASE_URL` = `/klasor` (sonda `/` yok); kök dizinde ise `''` bırakın.
Google ile giriş için Google Cloud Console → OAuth Client oluşturup `GOOGLE_CLIENT_ID` ve `GOOGLE_CLIENT_SECRET` girin.
Yetkili yönlendirme URI: `https://ALANADINIZ/auth/google-callback.php`. Boş bırakılırsa Google butonu görünmez.

## 3. SQL (phpMyAdmin → İçe Aktar, sırayla)
1. `sql/01_translations.sql` — tüm arayüz metinleri (TR/NL/EN/DE), eski lang/*.php yerine
2. `sql/02_schema_updates.sql` — İş İlanları kategorisi, kategori görselleri, statik sayfalar tablosu, sıralama (Araçlar önce)
3. `sql/03_vehicle_form_translations.sql` — araç ilan formu metinleri
4. `sql/04_category_filters.sql` — kategori/iş ilanları filtre metinleri
5. `sql/05_favorites.sql` — favoriler metinleri
6. `sql/06_messages_jobs.sql` — mesajlaşma metinleri + `listing_jobs` tablosu + iş ilanı formu metinleri

Hepsi tekrar çalıştırılabilir (mevcut verinize zarar vermez).

## 4. Yapı
- `index.php` anasayfa · `page.php?slug=about|contact|privacy|terms|cookies` statik sayfalar (DB: `static_pages`)
- `account.php` hesabım · `auth/` giriş, kayıt, çıkış, Google
- `pages/` her sayfa kendi `.php` + `assets/css/<sayfa>.css`
- `includes/` header, footer, auth-modal, post-modal (kategori → ülke/şehir → NL plaka RDW), i18n, auth
- Metinler yalnızca `translations` tablosundan gelir; anahtar yoksa İngilizce, o da yoksa anahtar adı gösterilir.
- Eski URL'ler yönlendirilir: `pages/kategori.php` → `pages/category.php`, `pages/esnaf-rehberi.php` → `pages/businesses.php`.

## Bilinen sınırlamalar / sonraki tur
- Emlak, Araçlar, İkinci El, Esnaf, Harita iç sayfaları eski tasarımını korur (koyu temada içerik açık kalır).
- `pages/post-vehicle.php` içindeki araç donanım etiketleri (`$featuresLabels`) henüz DB'ye taşınmadı.
