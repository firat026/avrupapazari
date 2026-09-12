import re
src = open('/app/upload/src/includes/header.php', encoding='utf-8').read()
lines = src.split('\n')
start = next(i for i, l in enumerate(lines) if 'İLAN VER MODAL' in l)
script = next(i for i, l in enumerate(lines) if l.strip() == '<script>' and i > start)
end = next(i for i, l in enumerate(lines) if l.strip() == '</script>' and i > script)
html = '\n'.join(lines[start + 1:script])
js = '\n'.join(lines[script + 1:end])

# strip legacy header-search dropdown code (elements no longer exist)
js = re.sub(r"\(function\(\)\{\n\s*var overlay=document\.getElementById\('sOverlay'\).*?(?=\n\s*var ivOverlay)", "(function(){", js, flags=re.S)
js = js.replace("document.getElementById('ilanVerBtn2').addEventListener('click',openIV);",
                "document.querySelectorAll('[data-open-post]').forEach(function(b){b.addEventListener('click',function(e){e.preventDefault();openIV();});});")
js = js.replace("<?= BASE_URL ?>/post-vehicle.php", "<?= BASE_URL ?>/pages/post-vehicle.php")
assert "<?= BASE_URL ?>/pages/post-vehicle.php" in js

# CSS: .iv-* rules from the inline <style>
style = re.search(r'<style>(.*?)</style>', src, flags=re.S).group(1)
css_lines = [l.strip() for l in style.split('\n') if l.strip().startswith('.iv-') or 'keyframes ivUp' in l or '@keyframes spin' in l]
css = '/* Post-ad modal (category -> country/city -> NL plate lookup via RDW) */\n' + '\n'.join(css_lines) + '''
.iv-cat-item:hover{transform:none}
.iv-continue:hover{transform:none}
@media(max-width:768px){.iv-cat-grid{grid-template-columns:1fr}.iv-car-specs{grid-template-columns:repeat(2,1fr)}.iv-modal{border-radius:16px}.iv-body{padding:20px}}
'''
open('/app/site/assets/css/post-modal.css', 'w', encoding='utf-8').write(css)

php_head = '''<?php
declare(strict_types=1);
// Post-ad modal: category -> country + city -> Dutch plate (kenteken) lookup via RDW open data.
$navCountries = [];
$citiesJson = '{}';
try {
    $nameCol = ['tr' => 'name_tr', 'nl' => 'name_nl', 'en' => 'name_en', 'de' => 'name_en'][$lang] ?? 'name_en';
    $navCountries = getDB()->query("SELECT id, code, {$nameCol} AS name FROM countries WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();
    $grouped = [];
    foreach (getDB()->query("SELECT name, country_id FROM cities WHERE is_active = 1 ORDER BY name ASC") as $city) {
        $grouped[(int)$city['country_id']][] = $city['name'];
    }
    $citiesJson = json_encode($grouped, JSON_UNESCAPED_UNICODE) ?: '{}';
} catch (Throwable $e) {}
?>
'''
open('/app/site/includes/post-modal.php', 'w', encoding='utf-8').write(php_head + html + '\n<script>\n' + js + '\n</script>\n')
print('html lines', script - start, 'js chars', len(js), 'css lines', len(css_lines))
