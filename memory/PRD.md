# Avrupapazarı – Pure PHP Frontend Clone

## Problem Statement
Kullanıcı, bir anasayfanın piksel-piksel statik klonunu saf PHP ile istiyor: `index.php`, `header.php`, `nav.php`, `style.css` ayrı dosyalar; dark mode (localStorage), dil seçici (TR/EN/NL/DE), mock kategori linkleri, glow efektli temiz/premium tasarım, hover'da KESİNLİKLE titreme yok. Kullanıcı Türkçe iletişim kuruyor.

## Architecture
- `/app/php_site/` – tüm site (index.php, config.php, router.php, includes/, assets/, lang/, pages/)
- PHP built-in server port 3000'de çalışır. `/app/frontend/package.json` içindeki `start` scripti `php -S 0.0.0.0:3000 -t /app/php_site /app/php_site/router.php` olarak değiştirildi; böylece supervisor `frontend` servisi React yerine PHP sitesini sunar (platform yeniden başlatsa bile).
- React frontend ve FastAPI backend kullanılmıyor (backend hâlâ çalışıyor ama etkisiz).

## Implemented (2026-06)
- PHP 8.2 kuruldu, modüler include yapısı, 4 dil, dark mode toggle, kategori sayfaları
- Hover titremesi kaldırıldı (translateY yok), box-shadow glow
- Canlı ön izleme düzeltildi (frontend start → PHP server, router.php eklendi)

## Backlog
- P1: Kullanıcının canlı ön izleme sonrası tasarım geri bildirimleri
- P2: Footer/orta bölüm içerik zenginleştirme, mobil menü iyileştirmeleri
