# T109: rompe cada regla en una copia aislada y exige que alguna prueba falle. No toca la carpeta compartida.
import os, pathlib, re, shutil, subprocess, sys, tempfile
raiz = pathlib.Path(r"C:/xampp/htdocs/gestion-wings")
copia = pathlib.Path(tempfile.mkdtemp(prefix="t109-"))
A = ["app/Http/Middleware/IgnorarFiltrosInvalidos.php", "app/Http/Controllers/CajaWebController.php", "app/Http/Controllers/ReciboController.php", "bootstrap/app.php", "tests/Feature/FiltrosInvalidosT109Test.php"]
M_, C, R, B = A[0], A[1], A[2], A[3]
M = [
 ("cualquier fecha vale", M_, "if ($valor !== '' && !$this->esFecha($valor)) {", "if (false) {"),
 ("listas no se limpian", M_, "if (is_array($request->query->all()[$filtro] ?? null)) {", "if (false) {"),
 ("la pieza no se aplica", B, "['security.headers', 'filtros.validos']", "['security.headers']"),
 ("mes sin validar", C, "if (!$soloPendientes && (!is_string($mes) || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $mes))) {", "if (false) {"),
 ("mes valido se pisa", C, "if (!$soloPendientes && (!is_string($mes) || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $mes))) {", "if (!$soloPendientes) {"),
 ("cuota inexistente sin 404", R, "abort_unless(Pago::whereKey($pagoId)->exists(), 404, 'Pago no encontrado');", ""),
 ("liquidacion inexistente sin 404", R, "abort_unless($liquidacion, 404, 'Liquidación no encontrada');", ""),
 ("sin pagar sin 404", R, "abort_unless($liquidacion->estaPagada(), 404, 'Solo se pueden generar recibos de liquidaciones pagadas');", ""),
]
try:
    subprocess.run(["git", "worktree", "add", "-q", "--detach", str(copia), "HEAD"], cwd=raiz, check=True)
    for f in A + [".env"]:
        (copia / f).parent.mkdir(parents=True, exist_ok=True); shutil.copy(raiz / f, copia / f)
    # Una copia real de vendor: enlazada, Composer carga las clases de la carpeta compartida.
    shutil.copytree(raiz / "vendor", copia / "vendor", symlinks=True)
    shutil.copytree(raiz / "public" / "build", copia / "public" / "build")
    env = dict(os.environ, DB_DATABASE="wings_testing_claude")
    for nombre, f, a, b in M:
        p = copia / f; orig = p.read_bytes(); s = orig.decode("utf-8")
        assert s.count(a) == 1, (nombre, s.count(a))
        p.write_bytes(s.replace(a, b).encode("utf-8"))
        r = subprocess.run(["php", "artisan", "test", "--filter=FiltrosInvalidosT109Test"], cwd=copia, env=env, capture_output=True, text=True, encoding="utf-8", errors="replace")
        limpio = re.sub(r"\x1b\[[0-9;]*m", "", r.stdout)
        tot = [l.strip() for l in limpio.splitlines() if "Tests:" in l]
        print(("DETECTADA " if r.returncode else "NO DETECTADA ") + nombre + " · " + (tot[-1] if tot else limpio[-200:]), flush=True)
        p.write_bytes(orig)
finally:
    subprocess.run(["git", "worktree", "remove", "--force", str(copia)], cwd=raiz, capture_output=True)
    shutil.rmtree(copia, ignore_errors=True)
