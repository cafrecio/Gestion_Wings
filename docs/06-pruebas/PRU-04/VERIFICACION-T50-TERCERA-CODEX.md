# T50 — Tercera verificación de Codex CyE

21/10/2026. Revisión independiente del commit `d274cfd41cd7d1eb21b4d7622cae61fe67391776`, presente en `origin/main` según `git ls-remote`. Equipo comprobado: `Ventas_CyE`. Sin modificar producto ni tabla de pruebas.

## Resultado

**Idioma corregido. Guardia del auxiliar incompleta; T50 devuelta a Gemini por este único punto.** H1 conserva la decisión de Carlos («Inscripción a partir de hoy»); H2 y H3 mantienen sus controles aprobados.

Se leyeron los cuerpos de `cuotaAltaPreview`, `inscripcionPreview`, `validationMessages`, el auxiliar completo, `config/database.php` y el parser de conexiones de Laravel. El MCP codebase-memory devolvió `Transport closed` en estado, búsqueda y cobertura; la comprobación se hizo con fuente directa y ejecución.

## Comprobaciones propias

- Regresiones permanentes T50: **13 aprobadas, 71 aserciones, 29,686 s, salida 0**, en `wings_testing_codex`. [Salida](evidencia-verificacion-t50-tercera-codex/regresiones-permanentes.txt).
- Los mismos controles independientes de la segunda revisión, copiados sin cambiar sus esperados: **17 aprobados, 214 aserciones, 57,638 s, salida 0**. Incluyen 20 peticiones inválidas por alta, edición y ambas previsualizaciones, rechazo 422 en castellano y huellas de todas las tablas sin cambios; también inscripción desde el alta y cuota futura excluida hasta su mes. El control de idioma que antes estaba rojo ahora pasa. [Salida](evidencia-verificacion-t50-tercera-codex/controles-independientes.txt) · [Respuestas y casos](evidencia-verificacion-t50-tercera-codex/controles.jsonl).
- Sintaxis PHP correcta en controlador, prueba permanente y auxiliar. Vistas compiladas y limpiadas correctamente. Diff de vistas/CSS vacío respecto del commit revisado.
- Suite rápida: **908 aprobadas, 6 omitidas, 7286 aserciones, 193,47 s, salida 0**, `wings_testing_codex`, corte permanente 1029. Se comprobó antes de correr que no hubiera otro proceso `artisan test`/`phpunit`. [Salida](evidencia-verificacion-t50-tercera-codex/suite-rapida.txt). El HTML T34 regenerado por una prueba se restaura a su versión previa; no es un cambio de T50.
- Guardianes tras actualizar la devolución en el tablero: **9 aprobados, 43 aserciones, 0,48 s, salida 0** (`DocumentacionNoMienteTest`, `TableroAlDiaTest`, `TablerosNoDivergenTest`). [Salida](evidencia-verificacion-t50-tercera-codex/guardianes-finales.txt). No se agregaron pruebas permanentes ni se cambió su contador por los controles guardados fuera de `tests/`.

## Única observación pendiente: comprobar la conexión efectiva

El auxiliar lee `config('database.connections.mysql.database')` y lo compara con la lista permitida. Esa lectura **no resuelve `DB_URL`**. `config/database.php` admite esa variable, y el parser de Laravel combina sus valores por encima del nombre de base configurado por separado.

Reproducción sobre el auxiliar original, sin editarlo:

| Entorno y configuración | Resultado observado |
|---|---|
| `testing`, base no autorizada, sin URL | Rechazo de guardia, salida 1, no llega a preparación. |
| `production`, base autorizada, sin URL | Rechazo de guardia, salida 1, no llega a preparación. |
| `testing`, `DB_DATABASE=wings_testing_codex`, `DB_URL=mysql://root@127.0.0.1:1/wings_t50_no_autorizada` | **Pasa la guardia**, imprime «Preparando base...» y llega al intento de migración contra `wings_t50_no_autorizada`, puerto 1. La conexión es rechazada. |

Todas las conexiones de la reproducción se fuerzan a `127.0.0.1:1`, sin base real accesible ni escrituras. El tercer proceso termina con salida 0 pese al error impreso; la evidencia determinante es que supera la guardia e intenta usar la base no autorizada. No se afirma que se haya migrado una base real ni que exista una puerta web de este problema.

[Reproductor](evidencia-verificacion-t50-tercera-codex/verificar-guardia.ps1) · [Resultados estructurados](evidencia-verificacion-t50-tercera-codex/guardia-resultados.json) · [Salida con URL](evidencia-verificacion-t50-tercera-codex/guardia-url-base-no-autorizada.txt).

Ejecutar desde la raíz, sin otra suite activa:

```powershell
pwsh -NoProfile -File docs/06-pruebas/PRU-04/evidencia-verificacion-t50-tercera-codex/verificar-guardia.ps1
```

El reproductor devuelve 1 mientras cualquiera de las configuraciones prohibidas supere la guardia. No modifica `.env` y rechaza ejecutarse con configuración cacheada.

**Criterio de la devolución:** antes de migrar, sembrar o escribir, validar el entorno y el nombre de base de la conexión que realmente se utilizará, una vez resuelta su configuración, incluido `DB_URL`. Agregar el caso anterior al control de la guardia; debe abortar por seguridad antes de imprimir preparación. Se conserva la lista autorizada de Gemini; no se pide cambiar reglas de negocio ni pantallas.

## Alcance y límites

Se verificó el cambio de idioma mediante peticiones HTTP a la aplicación dentro del arnés Laravel. No se hizo un recorrido nuevo de Chrome: esta vuelta cambia mensajes de validación del servidor y un auxiliar CLI; el recorrido de pantalla de H3 sigue siendo el documentado en la segunda revisión. No hay aprobación nueva de diseño.

La suite rápida corresponde al alcance de esta corrección: no cambió un servicio de dinero ni su cálculo. No se presenta como suite completa del corte 1029. La completa aislada anterior conserva su corte histórico 1025.

La primera invocación de PHPUnit en sandbox no pudo superar `is_readable` sobre `vendor/autoload.php`; se repitió fuera de esa restricción con autorización automática y la base propia. Las corridas válidas son las enlazadas. No se tocó base del club, servidor ni bases de otros agentes. No se ejecutó el auxiliar con un destino accesible.
