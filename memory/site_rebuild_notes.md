# AvrupaPazari site rebuild (user zip -> cleaned deliverable)

- Original zip: /app/upload/src ; SQL dump: /app/upload/avrupa_db.sql
- Working copy (deliverable): /app/site ; served on port 3000 via frontend "start" script (php -S ... -t /app/site router.php)
- Local MariaDB: datadir /app/upload/mysql_data, start: `sudo mysqld_safe --user=mysql --datadir=/app/upload/mysql_data --socket=/run/mysqld/mysqld.sock &`
  DB avrupa_db, user avrupa_pazari / localdev. (apt: mariadb-server php8.2-mysql/mbstring/curl/gd/xml may need reinstall after pod restart)
- WARNING: /app/site/assets/css/style.css has huge mojibake comment lines -> NEVER view it; use sed to strip lines containing "Ã".
- CSS vars (style.css): --bg --surface --surface-2 --surface-3 --text --text-2 --text-3 --accent --accent-hover --accent-light --accent-subtle --border --border-hover --card-shadow --card-shadow-hover --gold --gold-light ; theme via <html data-theme="light|dark">; fonts Instrument Sans / Space Grotesk
- User rules: no Turkish in file names/code; UI strings ONLY from DB `translations` (lang,key_group,key_name,value); each page own php+css; no hover jitter; single "Join" button opening modal (register/login/google)
- Decisions: keep DB tables; rename TR files with redirects; keep admin; deliver zip w/o uploads; DB password placeholder in config.
