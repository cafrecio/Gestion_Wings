"""Verifica documentos propios contra los esperados congelados, sin acceder a Wings."""
import hashlib
import json
import re
import sys
from collections import Counter
from pathlib import Path

base = Path(__file__).resolve().parent.parent
complete = '--completo' in sys.argv
total = Counter()
for day in (13, 14, 15, 16):
    frozen = json.loads((base / f'evidencia-dia-{day}/esperados-originales.json').read_text(encoding='utf-8-sig'))
    source = base.parents[2] / frozen['fuente']
    assert hashlib.sha256(source.read_bytes()).hexdigest() == frozen['fuente_sha256'], 'Cambio la fuente'
    report = (base / f'DIA-{day}.md').read_text(encoding='utf-8-sig')
    rows = {}
    for line in report.splitlines():
        if re.match(rf'^\| {day}\.\d+ \|', line):
            cells = [c.strip() for c in line.strip().strip('|').split('|')]
            assert len(cells) == 6, (day, cells)
            assert cells[0] not in rows, cells[0]
            rows[cells[0]] = cells
    assert len(rows) == len(frozen['casos']), day
    counts = Counter()
    for case in frozen['casos']:
        row = rows[case['id']]
        assert row[:4] == [case['id'], case['quien'], case['tarea'], case['esperado']], case['id']
        if complete:
            assert row[5] in {'Sí', 'No', 'Parcial'}, (case['id'], row[5])
            assert 'Pendiente de ejecutar' not in row[4], case['id']
        counts[row[5]] += 1
    total.update(counts)
    print(f'Dia {day}: {dict(counts)}; esperados literales intactos')
print('Total:', dict(total))
assert sum(total.values()) == 39
