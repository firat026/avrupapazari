import re, sys
for path in ['pages/search-vehicles.php', 'pages/property.php', 'pages/second-hand.php', 'pages/businesses.php']:
    full = '/app/site/' + path
    lines = open(full, encoding='utf-8').read().split('\n')
    def find(pred, start=0):
        for i in range(start, len(lines)):
            if pred(lines[i]): return i
        return -1
    aside = find(lambda l: 'class="sh-sidebar"' in l)
    hdr = find(lambda l: 'class="sh-filter-header"' in l, aside)
    if hdr < 0: print(path, 'no header'); continue
    indent = len(lines[hdr]) - len(lines[hdr].lstrip())
    close = re.compile(r'^ {%d}</div>\s*$' % indent)
    hdr_end = find(lambda l: close.match(l), hdr + 1)
    loc = find(lambda l: 'Location' in l and '<!--' in l, hdr_end)
    if loc < 0:
        loc = find(lambda l: 'name="country_id"' in l, hdr_end)
        while loc > hdr_end and 'sh-filter-group' not in lines[loc]: loc -= 1
    loc_end = find(lambda l: close.match(l), loc + 1)
    block = lines[loc:loc_end + 1]
    del lines[loc:loc_end + 1]
    lines[hdr_end + 1:hdr_end + 1] = [''] + block
    open(full, 'w', encoding='utf-8').write('\n'.join(lines))
    print(path, 'moved block lines', loc, loc_end, '->', hdr_end + 1)
