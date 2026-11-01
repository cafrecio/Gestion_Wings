"""Congela fuentes de la carpeta compartida antes/después de las suites T97."""
from pathlib import Path
import hashlib
import json
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[5]
out = Path(__file__).resolve().parent / (sys.argv[1] + '.json')
paths = set()
for area in ('app', 'tests', 'database/migrations', 'resources/views', 'resources/js', 'resources/css', 'routes', 'config', 'bootstrap'):
    paths.update(p for p in (ROOT / area).rglob('*') if p.is_file())
data = {
    'head': subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=ROOT, text=True).strip(),
    'sha256': {p.relative_to(ROOT).as_posix(): hashlib.sha256(p.read_bytes()).hexdigest() for p in sorted(paths)},
}
out.write_text(json.dumps(data, indent=2, ensure_ascii=False) + '\n', encoding='utf-8')
print(f'{out.name}: {len(paths)} fuentes')
