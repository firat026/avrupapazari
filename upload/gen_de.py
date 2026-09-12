import asyncio, json, subprocess, os, sys
from emergentintegrations.llm.chat import LlmChat, UserMessage

# 1) dump en keys from PHP lang files (flat "group.key": value)
php = r'''<?php
$out=[]; foreach(["tr","en","nl"] as $l){ $a=require "/app/site/lang/$l.php"; foreach($a as $g=>$arr) foreach($arr as $k=>$v) $out[$l]["$g.$k"]=$v; }
echo json_encode($out, JSON_UNESCAPED_UNICODE);'''
data = json.loads(subprocess.check_output(["php", "-r", php.replace("<?php","")]))
en = data["en"]

# 2) DB already has some DE keys -> skip them
import pymysql
conn = pymysql.connect(host="localhost", user="avrupa_pazari", password="localdev", database="avrupa_db", charset="utf8mb4")
cur = conn.cursor(); cur.execute("SELECT key_group,key_name FROM translations WHERE lang='de'")
have = {f"{g}.{k}" for g,k in cur.fetchall()}
todo = {k:v for k,v in en.items() if k not in have}
print("DE keys to translate:", len(todo))

async def main():
    chat = LlmChat(api_key=os.environ.get("EMERGENT_LLM_KEY","sk-emergent-259D2EcFcB5B8B98f0"), session_id="de-trans", system_message="You are a professional UI translator. Translate JSON values from English to German for a classifieds website (vehicles, real estate, second-hand, local businesses). Keep placeholders like :count unchanged. Keep values short like UI labels. Return ONLY valid JSON with identical keys.").with_model("openai","gpt-5.4-mini")
    items = list(todo.items()); out = {}
    for i in range(0, len(items), 60):
        chunk = dict(items[i:i+60])
        resp = await chat.send_message(UserMessage(text=json.dumps(chunk, ensure_ascii=False)))
        s = resp.strip()
        if s.startswith("```"): s = s.split("\n",1)[1].rsplit("```",1)[0]
        out.update(json.loads(s))
    data["de"] = out
    json.dump(data, open("/app/upload/lang_dump.json","w"), ensure_ascii=False, indent=1)
    print("done", len(out))
asyncio.run(main())
