# Segunda vuelta T100/T102 — evidencia Codex CAB, 01/11/2026

Control solicitado de lo devuelto, sin repetir la batería monetaria/de negocio anterior. Informes: [T100](../VERIFICACION-T100-CODEX.md#segunda-vuelta--01112026-codex-cab) y [T102](../VERIFICACION-T102-CODEX.md#segunda-vuelta--01112026-codex-cab).

## Corte y entorno

- Corrección controlada: `103360f7`. HEAD antes de la completa: `1f0af02ff13cb2ca93418d7e4c6af5e77aa1d594`.
- Solo `wings_testing_codex`, copia física en `storage/app/control-gw-261101/app`; vendor físico, configuración local ficticia. No base del club, despliegue ni SQL publicado.
- `preparar-corte.py` copia fuentes y documentos del corte comprometido; no incorpora cambios ajenos sin publicar. `fuentes-antes-completa.json` fija hashes de las 878 fuentes.
- Las pruebas nuevas que otros agentes agreguen después no forman parte de este corte ni se presentan como verificadas.

## Comandos puntuales ejecutados

En la copia aislada, con `$env:DB_DATABASE='wings_testing_codex'`, y `CONTROL_APP`/`CONTROL_EVIDENCE` apuntando a la copia y esta carpeta:

```powershell
php artisan test tests/Feature/AdelantoCanceladoT100Test.php tests/Feature/ControlT94CarreraTest.php tests/Feature/ControlT94ClaudeTest.php --filter 'test_el_adelanto_recuerda_el_cobro_del_que_nacio|test_editando_el_grupo_con_frecuencia_usada_antes|test_intercambiar_dos_frecuencias_no_da_error_del_servidor' --no-ansi
php artisan test tests/Feature/InscripcionConcurrenteTest.php --no-ansi
php artisan test <ruta-absoluta>/DiagnosticoS2SegundaCodexTest.php --filter=test_diagnostico_s2_ya_llega_al_alta --no-ansi
```

Cada comando se lanzó como proceso nuevo. Resultados: S1 3/6, S2 2/23, diagnóstico 1/9; los tres código 0. `resultados-puntuales.json` conserva códigos. `s2-diagnostico.json` guarda el estado de Primera carga, destino y fila creada.

Desde el checkout principal, sin escribir datos: `php scripts/manual/verificar-manual.php`: 59 páginas, 0 problemas, código 0.

## Pantallas y lectura

`preparar-pantallas.php` crea únicamente un fixture descartable con servicios/modelos reales. Chrome sobre `http://127.0.0.1:8111`: panel de Detalle, formulario Cancelar, panel Anular de ficha, sus dos avisos de éxito, selector con nivel actual inactivo y recibo de liquidación sin pagar. Capturas `.jpg` y DOM `.txt` con prefijo `gw-segunda-`. El aviso de anulación se leyó en la salida visible de Chrome, pero su captura/DOM posteriores ya no lo conservaron; esa limitación y el fragmento observado están en `observacion-aviso-anulado.md`.

La primera cancelación sí quedó ejecutada, pero su captura no alcanzó el aviso transitorio. Se preparó otro fixture y se volvió a cancelar para obtener `gw-segunda-flash-cancelado-final.jpg`, donde sí se ve «Cobro cancelado.». Los archivos `flash-cancelado` y `flash-cancelado-inmediato` conservan esos intentos anteriores y **no prueban el aviso**. El segundo intento de esperar por texto devolvió un error del control del navegador, aunque sus diagnósticos reconocieron el aviso visible; la captura y DOM finales posteriores sí lo acreditan.

`lectura-condiciones.json` guarda líneas y hashes del servicio/manual/ficha. `barrido-frases.txt` guarda las dos generalizaciones residuales de la ficha de fuentes (T100 H1). Esto es contraste de texto, no nueva ejecución de sus reglas monetarias. `diff-correcciones.txt` conserva el diff leído de la corrección de Claude.

## Única completa

`correr-completa.ps1`: un `php artisan test --no-ansi`, sin excluir lentos. Comprueba ausencia de otras suites antes de iniciar y monitorea el árbol de procesos incluyendo `cmd.exe`, diferenciando su PHPUnit de otros procesos. Archivos `suite-inicio.json`, `suite-completa.txt`, `suite-completa.stderr.txt`, `monitor-completa.jsonl` y `suite-resultado.json`. El resumen y código finales se completan al terminar, sin asumir que código 1 significa falla de una aserción ni atribuirle una causa no comprobada.

Resultado final: **1.479 aprobadas, 0 fallidas, 6 omitidas, 11.614 aserciones, 1.311,52 s, código 0**. `verificacion-final.json`: 878 fuentes intactas en copia/raíz, 244 muestras sin otra suite/PHP. Reserva LIBERADA. T102 cerrada; T100 devuelta por H1 residual de ficha.

Presentación de salidas: se quitaron espacios finales y blancos al final de los `.txt` que los tenían para cumplir `git diff --check`. Sus bytes originales exactos se conservan comprimidos en `.txt.raw.gz`, con hashes y lista de transformaciones en `formato-salidas.json`. No se quitaron errores, casos, resúmenes ni códigos.
