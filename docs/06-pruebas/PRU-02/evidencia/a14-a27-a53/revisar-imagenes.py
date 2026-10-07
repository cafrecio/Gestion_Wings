from pathlib import Path
from PIL import Image, ImageDraw, ImageChops, ImageStat
import json

root = Path(__file__).parent
files = sorted((root / 'capturas').glob('*.jpg'))
checks = []
for file in files:
    with Image.open(file) as im:
        im.load()
        checks.append({'file': file.name, 'size': im.size, 'extrema': im.getextrema()})
(root / 'integridad-imagenes.json').write_text(json.dumps(checks, ensure_ascii=False, indent=2), encoding='utf-8')

for phase in ('antes', 'despues'):
    for size in ('375', 'desktop'):
        batch = [file for file in files if f'-{phase}-{size}' in file.name]
        w, h = (300, 575) if size == '375' else (600, 475)
        for start in range(0, len(batch), 8):
            sheet = Image.new('RGB', (w * 2, h * 4), 'white')
            draw = ImageDraw.Draw(sheet)
            for i, file in enumerate(batch[start:start+8]):
                x, y = (i % 2) * w, (i // 2) * h
                draw.text((x+3, y+3), file.name.replace(f'-{phase}-', '\n'), fill='black')
                with Image.open(file) as im:
                    im.thumbnail((w-6, h-35))
                    sheet.paste(im, (x+3, y+35))
            sheet.save(root / f'contacto-{phase}-{size}-{start//8+1}.jpg', quality=94)

diffs = []
for before in (root / 'capturas').glob('*-antes-desktop.jpg'):
    after = before.with_name(before.name.replace('-antes-', '-despues-'))
    if not after.exists(): continue
    with Image.open(before) as a, Image.open(after) as b:
        if a.size == b.size:
            delta = ImageChops.difference(a, b)
            diffs.append({'before':before.name, 'after':after.name, 'box':delta.getbbox(), 'mean':ImageStat.Stat(delta).mean})
        else:
            diffs.append({'before':before.name, 'after':after.name, 'sizes':[a.size,b.size]})
(root / 'comparacion-desktop.json').write_text(json.dumps(diffs, indent=2), encoding='utf-8')
print(f'{len(checks)} imágenes leídas completas; {len(diffs)} pares de escritorio')
