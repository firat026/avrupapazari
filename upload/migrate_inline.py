import re, json, subprocess, asyncio, os
from emergentintegrations.llm.chat import LlmChat, UserMessage

files = {'pages/post-vehicle.php': 'post_vehicle', 'pages/preview-listing.php': 'preview_listing'}
rows_all = []
for path, group in files.items():
    src = open('/app/site/' + path, encoding='utf-8').read()
    m = re.search(r'\n\$t = \[\n.*?\n\];\n', src, flags=re.S)
    block = m.group(0)
    data = json.loads(subprocess.check_output(['php', '-r', block.replace('$t = [', '$t = [', 1) + ' echo json_encode($t, JSON_UNESCAPED_UNICODE);']))
    en = data.get('en') or data.get('tr')
    async def de(en=en):
        chat = LlmChat(api_key=os.environ.get('EMERGENT_LLM_KEY', 'sk-emergent-259D2EcFcB5B8B98f0'), session_id='de-' + group, system_message='Translate JSON values from English to German for a car classifieds form UI. Keep placeholders unchanged. Return ONLY valid JSON with identical keys.').with_model('openai', 'gpt-5.4-mini')
        items = list(en.items()); out = {}
        for i in range(0, len(items), 60):
            r = (await chat.send_message(UserMessage(text=json.dumps(dict(items[i:i+60]), ensure_ascii=False)))).strip()
            if r.startswith('```'): r = r.split('\n', 1)[1].rsplit('```', 1)[0]
            out.update(json.loads(r))
        return out
    data['de'] = asyncio.run(de())
    esc = lambda s: str(s).replace('\\', '\\\\').replace("'", "\\'")
    for lang in ['tr', 'nl', 'en', 'de']:
        for k, v in (data.get(lang) or {}).items():
            rows_all.append(f"('{lang}','{group}','{esc(k)}','{esc(v)}')")
    src = src.replace(block, "\n$tr = translationGroup('" + group + "');\n", 1)
    src = re.sub(r"\n\$tr = \$t\[\$lang\];\n", "\n", src)
    src = src.replace("$allowed_langs = ['tr', 'nl', 'en'];\nif (!in_array($lang, $allowed_langs)) $lang = 'tr';\n", "")
    open('/app/site/' + path, 'w', encoding='utf-8').write(src)
    print(path, {l: len(v) for l, v in data.items()})
sql = "-- Vehicle posting form texts (moved from PHP arrays). Safe to re-run.\nSET NAMES utf8mb4;\nINSERT INTO `translations` (`lang`,`key_group`,`key_name`,`value`) VALUES\n" + ",\n".join(rows_all) + "\nON DUPLICATE KEY UPDATE `value` = VALUES(`value`);\n"
open('/app/site/sql/03_vehicle_form_translations.sql', 'w', encoding='utf-8').write(sql)
print('rows', len(rows_all))
