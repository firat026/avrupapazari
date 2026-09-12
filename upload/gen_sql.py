import json
dump = json.load(open('/app/upload/lang_dump.json'))
extra = json.load(open('/app/upload/extra_keys.json'))
order = ['tr','nl','en','de']
def esc(s): return s.replace('\\','\\\\').replace("'", "\\'")
rows_ignore = []
for lang in order:
    for k, v in sorted(dump.get(lang, {}).items()):
        g, n = k.split('.', 1)
        rows_ignore.append(f"('{lang}','{esc(g)}','{esc(n)}','{esc(v)}')")
rows_force = []
for k, vals in extra.items():
    g, n = k.split('.', 1)
    for lang, v in zip(order, vals):
        rows_force.append(f"('{lang}','{esc(g)}','{esc(n)}','{esc(v)}')")
out = ["-- AvrupaPazari: UI translations (import via phpMyAdmin). Safe to re-run.",
       "SET NAMES utf8mb4;", "",
       "-- 1) Texts moved from the old lang/*.php files (+ German). Existing DB rows are kept.",
       "INSERT IGNORE INTO `translations` (`lang`,`key_group`,`key_name`,`value`) VALUES",
       ",\n".join(rows_ignore) + ";", "",
       "-- 2) New UI texts (auth modal, account, jobs, static pages). Always updated.",
       "INSERT INTO `translations` (`lang`,`key_group`,`key_name`,`value`) VALUES",
       ",\n".join(rows_force),
       "ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);", ""]
open('/app/site/sql/01_translations.sql','w').write("\n".join(out))
print(len(rows_ignore), len(rows_force))
