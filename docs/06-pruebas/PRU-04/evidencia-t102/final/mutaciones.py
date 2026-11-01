# T102: rompe cada regla y exige que alguna prueba falle. Deja los archivos como estaban.
import os, pathlib, re, subprocess, sys
raiz = pathlib.Path(r"C:/xampp/htdocs/gestion-wings")
G, N, V = "app/Http/Controllers/GrupoWebController.php", "app/Http/Controllers/NivelWebController.php", "resources/views/grupos/_form.blade.php"
M = [
 ("inactivar ignora grupos activos", N, "if ($gruposActivos > 0) {", "if (false) {"),
 ("inactivar cuenta grupos inactivos", N, "$nivel->grupos()->where('activo', true)->count()", "$nivel->grupos()->count()"),
 ("inactivar sin candado", N, "Nivel::whereKey($id)->lockForUpdate()->firstOrFail()", "Nivel::whereKey($id)->firstOrFail()"),
 ("crear ofrece inactivos", G, "$niveles  = Nivel::where('activo', true)->orderBy('nombre')->get();", "$niveles  = Nivel::orderBy('nombre')->get();"),
 ("destino sin candado", G, "$nivel = Nivel::whereKey($nivelId)->lockForUpdate()->first();", "$nivel = Nivel::whereKey($nivelId)->first();"),
 ("destino acepta inactivo", G, "if (!$nivel || !$nivel->activo) {", "if (!$nivel) {"),
 ("destino ocupado no se mira", G, "return $ocupado ? 'Ya existe un grupo con ese deporte y nivel.' : null;", "return null;"),
 ("editar no ofrece el nivel actual", G, "->orWhere('id', $grupo->nivel_id)", ""),
 ("el nivel no se guarda", G, "$grupo->update(['nivel_id' => $nivelNuevo]);", ""),
 ("activar grupo ignora el nivel", G, "if (!$nivel?->activo) {", "if (false) {"),
 ("activar grupo sin candado", G, "$nivel = Nivel::whereKey($grupo->nivel_id)->lockForUpdate()->first();", "$nivel = Nivel::whereKey($grupo->nivel_id)->first();"),
 ("formulario no marca el actual", V, "old('nivel_id', $grupo->nivel_id ?? null) == $nivel->id", "old('nivel_id') == $nivel->id"),
]
env = dict(os.environ, DB_DATABASE="wings_testing_claude")
for nombre, f, a, b in M:
    if len(sys.argv) > 1 and not any(x in nombre for x in sys.argv[1:]): continue
    p = raiz / f; orig = p.read_bytes(); s = orig.decode("utf-8")
    assert s.count(a) == 1, (nombre, s.count(a))
    try:
        p.write_bytes(s.replace(a, b).encode("utf-8"))
        r = subprocess.run(["php", "artisan", "test", "--filter=NivelInactivo"], cwd=raiz, env=env, capture_output=True, text=True, encoding="utf-8", errors="replace")
        limpio = re.sub(r"\x1b\[[0-9;]*m", "", r.stdout)
        tot = [l.strip() for l in limpio.splitlines() if "Tests:" in l]
        print(("DETECTADA " if r.returncode else "NO DETECTADA ") + nombre + " · " + (tot[-1] if tot else "?"), flush=True)
    finally:
        p.write_bytes(orig)
