# Evidencia T65 — Codex CAB, 27/10/2026

Base descartable: `wings_testing_codex`; producto `10cbaaa0`, implementación auditada `13b85f68`.

| Archivo | Alcance |
|---|---|
| `base.*` | Las18 originales, antes de agregar las pruebas de Codex. Salida0. |
| `final-area.*` | 18 originales +12 funcionales nuevas +2 concurrencia +2 CSP. Solo H1/H2/H3 en rojo. Salida1. |
| `concurrencia.*` | Repetición aislada de las dos pruebas con ocho cruces reales. Salida0. |
| `rapida.*` | Suite rápida completa: tres rojos T65 y dos T86. Salida1. |
| `t86-aislada.*` | T86 repetida aisladamente:6 verdes/2 rojos. Causa no establecida. Salida1. |
| `claves-foraneas.tsv` | Inventario de FKs del esquema migrado de Codex; no datos de personas. |
| `migraciones.txt` | Localización de las FKs directas de alumnos y clases en migraciones. |
| `fuentes.json` | HEAD y SHA256 de15 archivos de producto, sin cambios durante las pruebas. |
| `comprobaciones.json` | Integridad del corte, contadores, archivo histórico y enlaces. |

Cada ejecución conserva texto, JUnit XML y salida de proceso en `*-exit.txt`. La salida1 es real y esperada en la devolución: las regresiones deben ponerse verdes con el arreglo de Gemini, sin alterar lo esperado ni omitirlas.

Entorno y comandos utilizados (PowerShell, desde la raíz del repositorio):

```powershell
$env:APP_ENV='testing'
$env:DB_DATABASE='wings_testing_codex'
$env:DB_HOST='127.0.0.1'
$env:DB_URL=''
$env:CACHE_STORE='array'
$env:SESSION_DRIVER='array'

# Antes: comprobar que no haya otra suite en esta computadora.
Get-CimInstance Win32_Process -Filter "name='php.exe'" |
    Where-Object CommandLine -match 'artisan test|phpunit'

$t65Bootstrap='docs/06-pruebas/PRU-04/evidencia-verificacion-t92-codex/bootstrap.php'
php artisan test tests/Feature/EliminarAlumnoYClaseT65Test.php --bootstrap=$t65Bootstrap
php artisan test tests/Feature/EliminarAlumnoYClaseT65Test.php tests/Feature/VerificacionEliminarT65CodexTest.php tests/Feature/EliminarT65ConcurrenteCodexTest.php tests/Feature/CspSinCodigoIncrustadoTest.php --bootstrap=$t65Bootstrap
php artisan test --exclude-group=lento --bootstrap=$t65Bootstrap
php artisan test tests/Feature/ReporteSueldosOperativasT86Test.php --bootstrap=$t65Bootstrap
php artisan test tests/Feature/EliminarT65ConcurrenteCodexTest.php --bootstrap=$t65Bootstrap
```

Para la evidencia se agregaron `--colors=never --log-junit=<archivo.xml>` y redirección de texto al archivo correspondiente. El bootstrap impide ejecutar si el entorno o la base no son los descartables de Codex. Las pruebas permanentes de concurrencia permiten las bases propias de los tres agentes; no habilitan la base del club.

La captura T34 que regenera la suite se restituyó a sus bytes originales de HEAD, SHA256 `d1a4ebb97d9a55f77c96a6ba476e816e7e03427e06b94ed1c392c6119f2835af`, y quedó fuera del commit. No se corrigieron los dos fallos T86 ni se ejecutó una suite completa/despliegue.

Las salidas de texto se normalizaron quitando espacios finales y líneas vacías finales para `git diff --check`; los resultados y los JUnit se conservan. En el archivo histórico se excluyó únicamente el separador vacío posterior a la entrada; su texto se comprobó contra HEAD y su SHA256 está en la bitácora.
