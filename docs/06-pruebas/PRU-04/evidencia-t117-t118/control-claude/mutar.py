import os, re, subprocess, sys
C = 'C:/Users/cafre/AppData/Local/Temp/claude/c--xampp-htdocs-gestion-wings/97be1849-567e-4b3c-be68-0eeacf26049c/scratchpad/copia-t117/'
os.chdir(C)
env = dict(os.environ, DB_DATABASE='wings_testing_claude')
PRUEBA = 'tests/Feature/SesionVencidaYMayusculasT117T118Test.php'
M = [
    ('M1 sin el JSON del 419', 'bootstrap/app.php', "if ($e->getStatusCode() === 419 && !$request->is('api/*')) {", "if ($e->getStatusCode() === 419 && !$request->is('api/*') && !$request->expectsJson()) {"),
    ('M2 el 419 de api va al ingreso', 'bootstrap/app.php', "if ($e->getStatusCode() === 419 && !$request->is('api/*')) {", "if ($e->getStatusCode() === 419) {"),
    ('M3 sin el aviso guardado', 'bootstrap/app.php', "$request->session()->flash('error', 'Tu sesión expiró. Iniciá sesión nuevamente.');", ""),
    ('M4 Revisión strtoupper', 'resources/views/revision-cobranza/index.blade.php', "mb_strtoupper($resolucionLabel, 'UTF-8')", "strtoupper($resolucionLabel)"),
    ('M5 detalle strtoupper', 'resources/views/caja/detalle.blade.php', "mb_strtoupper(mb_substr($mov->alumno->deporte->nombre ?? '', 0, 3, 'UTF-8'), 'UTF-8')", "strtoupper(mb_substr($mov->alumno->deporte->nombre ?? '', 0, 3, 'UTF-8'))"),
    ('M6 detalle substr', 'resources/views/caja/detalle.blade.php', "mb_strtoupper(mb_substr($mov->alumno->deporte->nombre ?? '', 0, 3, 'UTF-8'), 'UTF-8')", "mb_strtoupper(substr($mov->alumno->deporte->nombre ?? '', 0, 3), 'UTF-8')"),
    ('M7 movimientos substr', 'resources/views/movimientos/index.blade.php', "mb_substr($mov->observaciones, 0, 36, 'UTF-8')", "substr($mov->observaciones, 0, 36)"),
    ('M8 movimientos strlen', 'resources/views/movimientos/index.blade.php', "mb_strlen($mov->observaciones ?? '', 'UTF-8') > 36", "strlen($mov->observaciones ?? '') > 36"),
    ('M9 tipo de caja, alta', 'app/Http/Controllers/TipoCajaWebController.php', "mb_strtoupper($request->abreviatura, 'UTF-8')", "strtoupper($request->abreviatura)"),
    ('M10 tipo de caja, edición', 'app/Http/Controllers/TipoCajaWebController.php', "mb_strtoupper($datos['abreviatura'], 'UTF-8')", "strtoupper($datos['abreviatura'])"),
]


def correr():
    r = subprocess.run(['php', 'artisan', 'test', PRUEBA], env=env, capture_output=True, text=True, encoding='utf-8', errors='replace')
    limpio = re.sub(r'\x1b\[[0-9;]*m', '', r.stdout)
    t = [l.strip() for l in limpio.splitlines() if 'Tests:' in l]
    return t[-1] if t else limpio[-300:]


subprocess.run(['php', 'artisan', 'view:clear'], env=env, capture_output=True)
print('sin mutar:', correr(), flush=True)
for nombre, f, v, n in M:
    s = open(f, encoding='utf-8', newline='').read()
    assert s.count(v) == 1, (nombre, s.count(v))
    open(f, 'w', encoding='utf-8', newline='').write(s.replace(v, n))
    subprocess.run(['php', 'artisan', 'view:clear'], env=env, capture_output=True)
    try:
        print(nombre, '=>', correr(), flush=True)
    finally:
        open(f, 'w', encoding='utf-8', newline='').write(s)
subprocess.run(['php', 'artisan', 'view:clear'], env=env, capture_output=True)
print('fin mutaciones', flush=True)
