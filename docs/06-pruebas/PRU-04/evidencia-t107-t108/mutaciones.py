# T107 y T108: rompe cada regla en una copia aislada y exige que alguna prueba falle. No toca la carpeta compartida.
import os, pathlib, re, shutil, subprocess, sys, tempfile

raiz = pathlib.Path(r"C:/xampp/htdocs/gestion-wings")
copia = pathlib.Path(tempfile.mkdtemp(prefix="t107-t108-"))

archivos = [
    "app/Services/CajaService.php",
    "resources/views/caja/cierre.blade.php",
    "app/Http/Controllers/AlumnoWebController.php",
    "app/Services/InscripcionService.php",
    "tests/Feature/CierrePantallaViejaT107Test.php",
    "tests/Feature/InscripcionSegundaFichaT108Test.php",
]

CS = "app/Services/CajaService.php"
CB = "resources/views/caja/cierre.blade.php"
AC = "app/Http/Controllers/AlumnoWebController.php"
IS = "app/Services/InscripcionService.php"

mutaciones = [
    # T107
    ("T107: cierre sin validar visto", CS, "CierrePantallaViejaT107Test",
     "if ($visto !== null && $esperado !== null && $this->centavos($visto) !== $this->centavos($esperado)) {",
     "if (false) {"),
    ("T107: vista sin input esperado_visto", CB, "CierrePantallaViejaT107Test",
     '<input type="hidden" name="esperado_visto" value="{{ $arqueo[\'efectivo_esperado\'] }}">',
     '{{-- sin esperado_visto --}}'),
    ("T107: esperado_visto sin regla numeric", CS, "CierrePantallaViejaT107Test",
     "'esperado_visto' => 'nullable|numeric',",
     "// 'esperado_visto' => 'nullable|numeric',"),

    # T108
    ("T108: segunda ficha no consulta yaEsDelClub", AC, "InscripcionSegundaFichaT108Test",
     "if (!app(\\App\\Services\\InscripcionService::class)->yaEsDelClub($alumno->dni, $alumno->id)) {",
     "if (true) {"),
    ("T108: preview no avisa yaEsDelClub", AC, "InscripcionSegundaFichaT108Test",
     ": ($service->yaEsDelClub($data['dni'])",
     ": (false"),
    ("T108: yaEsDelClub filtra solo activos", IS, "InscripcionSegundaFichaT108Test",
     'return Alumno::whereRaw("REPLACE(REPLACE(REPLACE(dni, \'.\', \'\'), \' \', \'\'), \'-\', \'\') = ?", [self::dni($dni)])',
     'return Alumno::where(\'activo\', true)->whereRaw("REPLACE(REPLACE(REPLACE(dni, \'.\', \'\'), \' \', \'\'), \'-\', \'\') = ?", [self::dni($dni)])'),
    ("T108: yaEsDelClub compara dni sin limpiar", IS, "InscripcionSegundaFichaT108Test",
     'return Alumno::whereRaw("REPLACE(REPLACE(REPLACE(dni, \'.\', \'\'), \' \', \'\'), \'-\', \'\') = ?", [self::dni($dni)])',
     'return Alumno::where(\'dni\', $dni)'),
]

try:
    print(f"Creando worktree aislado en {copia}...")
    subprocess.run(["git", "worktree", "add", "-q", "--detach", str(copia), "HEAD"], cwd=raiz, check=True)
    for f in archivos + [".env"]:
        dest = copia / f
        dest.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy(raiz / f, dest)

    print("Copiando vendor y build para aislamiento...")
    shutil.copytree(raiz / "vendor", copia / "vendor", symlinks=True)
    shutil.copytree(raiz / "public" / "build", copia / "public" / "build")

    env = dict(os.environ, DB_DATABASE="wings_testing_gemini")
    salida = []

    for nombre, f, test_filter, orig_str, mut_str in mutaciones:
        p = copia / f
        orig_bytes = p.read_bytes()
        s = orig_bytes.decode("utf-8")
        assert s.count(orig_str) == 1, f"Error en patrón de mutación '{nombre}': count={s.count(orig_str)}"

        p.write_bytes(s.replace(orig_str, mut_str).encode("utf-8"))

        r = subprocess.run(
            ["php", "artisan", "test", f"--filter={test_filter}"],
            cwd=copia,
            env=env,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace"
        )

        limpio = re.sub(r"\x1b\[[0-9;]*m", "", r.stdout)
        tot = [line.strip() for line in limpio.splitlines() if "Tests:" in line]
        res_line = ("DETECTADA " if r.returncode != 0 else "NO DETECTADA ") + nombre + " · " + (tot[-1] if tot else limpio[-200:])
        print(res_line, flush=True)
        salida.append(res_line)

        p.write_bytes(orig_bytes)

    out_file = raiz / "docs/06-pruebas/PRU-04/evidencia-t107-t108/mutaciones.txt"
    out_file.write_text("\n".join(salida) + "\n", encoding="utf-8")

finally:
    print("Limpiando worktree...")
    subprocess.run(["git", "worktree", "remove", "--force", str(copia)], cwd=raiz, capture_output=True)
    shutil.rmtree(copia, ignore_errors=True)
