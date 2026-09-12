# AvrupaPazari – PHP/MySQL classifieds site cleanup & redesign

## Problem statement
User (Turkish) uploaded their live cPanel site (PHP 8, MySQL, 4 langs) + SQL dump and asked: scan everything, delete junk, fix errors, improve design (sharp, clean, glow, NO hover jitter), rules: no Turkish in file names/code, UI texts only from DB `translations`, each page its own php+css. Deliver zip for cPanel. Later: single "Üye Ol" button opening popup (register/login/Google), vehicles first everywhere, restore post-ad popup with NL plate (RDW) auto-fill, country select at top of category sidebars.

## Architecture
- Working copy /app/site served on port 3000 (frontend start script = php -S ... router.php). Original in /app/upload/src, dump /app/upload/avrupa_db.sql, local MariaDB datadir /app/upload/mysql_data (see /app/memory/site_rebuild_notes.md).
- config.php (creds placeholders in zip), functions.php, includes/{i18n,auth,header,footer,auth-modal,post-modal}.php, auth/{login,register,logout,google,google-callback}.php, pages/*.php each with assets/css/*.css, sql/01..03, README.md.
- Deliverable zip: /app/deliverables/avrupapazari-v2.zip (also /app/site/downloads/ for download via preview URL). Excludes uploads/ except uploads/categories.

## Implemented (2026-06-12)
- Cleanup: removed debug/dead files, error_logs, lang/*.php (→ DB), mojibake CSS comments, hover transforms; legacy redirects kategori→category, esnaf-rehberi→businesses; uploads/esnaf→businesses.
- New header/nav (fixed heights, glow hovers), theme (localStorage), lang dropdown (cookie), auth modal (JSON login/register, CSRF, brute-force limit), Google OAuth (needs client id), account.php, page.php (static_pages table), jobs category, category.php, business.php, post.php, 404, maintenance.
- Post-ad modal restored (category→country/city→RDW plate lookup) as includes/post-modal.php.
- Translations: 1,100+ rows TR/NL/EN/DE incl. generated German; vehicle form texts moved to DB (sql/03).
- Admin: fixed broken sidebar links, preview-listing parse error, listing.php double <head>.
- Testing agent iteration_1: all pass; fixed cities.forEach guard afterwards.

## Round 2 (2026-06-12, later)
- Map link context (home=all, category page=?module=X; fixed wrong ikinci_el links), AJAX result-only updates on listing pages (assets/js/listing-ajax.js), chip strip hidden, Switzerland added (sql/02), jobs/category page rebuilt with sidebar (country top, cities via ajax/get-cities.php) + sort + pagination, auth modal narrow-screen overflow fixed, api/ renamed to ajax/ (preview ingress hijacks /api). Tests: iteration_2 & 3 pass.
- Zip rebuilt: /app/deliverables/avrupapazari-v2.zip (download via <preview>/downloads/avrupapazari-v2.zip)

## Round 3 (2026-06-12)
- Favorites: ajax/toggle-favorite.php, favoriteButton() on cards (home/category/account), listing.php #favBtn rewired, account.php 'Favorilerim' section, header menu link, sql/05_favorites.sql. Tests iteration_4 pass. Zip rebuilt.

## Round 4 (2026-06-12)
- Header badges (favorites count, unread messages), messaging (ajax/send-message.php, messages.php inbox+thread, listing.php modal), job posting form pages/post-job.php (+listing_jobs table, post modal routes jobs there), inner pages restyled via assets/css/listing-pages.css (maps --sh-* vars -> site tokens, dark mode), homepage width fix (removed old .homepage rule from style.css, bigger type), FOUC fix (page CSS in head via $pageStyles, filemtime asset versions). sql/06_messages_jobs.sql. Tests iteration_5 (24/26 -> fixed) & iteration_6 pass. Zip rebuilt.

## Round 5 (2026-06-12)
- Session cookie SameSite=None;Secure on HTTPS (registration failed inside preview iframe). iteration_7 pass. Zip rebuilt.

## Backlog
- P2: map.php restyle to new tokens
- P1: Move $featuresLabels in post-vehicle.php to DB
- P2: Admin UI English/translations
- P2: Jobs posting form in post modal, admin pages for static_pages & translations editing
- P2: Google OAuth live test once client id provided
