"""Fabrique la notice imprimable (HTML) à partir de INSTALLATION-OVH.txt.
Une seule source, deux formes : modifier le .txt, puis relancer
    python3 docs/notice-html.py "docs/Notice installation OVH.html"
"""
import html
import pathlib
import re
import sys

src = pathlib.Path(__file__).with_name('INSTALLATION-OVH.txt').read_text(encoding='utf-8')
parts = re.split(r'\n-{20,}\n(.+?)\n-{20,}\n', src)
intro_lines = [l for l in parts[0].split('\n') if l.strip() and set(l.strip()) != {'='}]
sections = list(zip(parts[1::2], parts[2::2]))


def render(txt: str) -> str:
    res, para, lst = [], [], []

    def end():
        nonlocal para, lst
        if para:
            res.append('<p>' + ' '.join(html.escape(p) for p in para) + '</p>')
            para = []
        if lst:
            res.append('<ul>' + ''.join('<li>' + html.escape(x) + '</li>' for x in lst) + '</ul>')
            lst = []

    for line in txt.split('\n'):
        s = line.strip()
        if not s or set(s) == {'='}:
            end()
            continue
        m = re.match(r'^(•|\d+\.|[AB]\.)\s+(.*)', s)
        if m:
            if para:
                end()
            lst.append(('•' if m.group(1) == '•' else m.group(1)) + '\u00a0' + m.group(2))
            continue
        if line.startswith('  ') and lst and not s.startswith('⚠'):
            lst[-1] += ' ' + s
            continue
        if lst:
            end()
        para.append(s)
    end()
    h = '\n'.join(res)
    h = h.replace('⚠', '<b class="att">⚠</b>')
    return re.sub(r'«\s*([^»]+?)\s*»', r'« <b>\1</b> »', h)


body = render('\n'.join(intro_lines[2:]))
body += ''.join(f'<section><h2>{html.escape(t.strip())}</h2>{render(c)}</section>' for t, c in sections)
doc = f'''<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><title>Notice d'installation — La Boucherie</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,700;1,500&amp;family=DM+Sans:wght@400;500;700&amp;display=swap">
<style>
@page{{size:A4;margin:16mm 16mm 18mm}}
*{{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
body{{margin:0 auto;max-width:180mm;font:400 10.5pt/1.55 'DM Sans',Arial,sans-serif;color:#1E2A36;background:#fff}}
.store{{height:14mm;background:repeating-linear-gradient(90deg,#B5412C 0 9mm,#F8F2E8 9mm 10mm,#B5412C 10mm 11.5mm,#F8F2E8 11.5mm 17mm)}}
.frange{{height:5mm;margin-bottom:6mm;background:radial-gradient(circle at 50% 0,#B5412C 0 3.6mm,transparent 3.8mm) 0 0/8mm 5mm repeat-x}}
h1{{font:500 30pt/1 'Cormorant Garamond',Georgia,serif;margin:0 0 2mm}}
.sous{{font:italic 500 16pt 'Cormorant Garamond',Georgia,serif;color:#B5412C;margin:0 0 5mm}}
h2{{font:700 15pt/1.2 'Cormorant Garamond',Georgia,serif;color:#B5412C;margin:7mm 0 2mm;padding-top:2mm;border-top:1px solid #D9DEE3;break-after:avoid}}
p{{margin:0 0 2.5mm}} ul{{margin:0 0 3mm;padding-left:6mm;list-style:none}} li{{margin:0 0 1.2mm;text-indent:-4mm;padding-left:4mm}}
.att{{color:#B5412C}}
.pied{{margin-top:8mm;padding-top:3mm;border-top:1px solid #D9DEE3;font-size:9pt;color:#4F5B66}}
</style></head><body>
<div class="store"></div><div class="frange"></div>
<h1>{html.escape(intro_lines[0].strip())}</h1>
<p class="sous">{html.escape(intro_lines[1].strip())}</p>
{body}
<p class="pied">La galerie éphémère — La Boucherie · 39 grande rue, 18240 Léré</p>
</body></html>
'''
pathlib.Path(sys.argv[1]).write_text(doc, encoding='utf-8')
print('Notice écrite :', sys.argv[1])
