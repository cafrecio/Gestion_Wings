# B11 — Punitorios por mora: implementación local

Codex CyE, 16/10/2026. **Pendiente de verificación independiente de Claude,
sin publicación ni despliegue.** `52e6bdf` sigue local por decisión
de Carlos. No publicar ese commit ni sus descendientes sin nueva autorización.

Implementación y evidencia guardadas en el commit local **`363346b`**. El pase
posterior registra ese hash en el tablero; B11 permanece `a_verificar`, tiene Claude.

## Excepción de prueba aprobada

La orden pide crear el rubro `Punitorios` por migración y por `CatalogosSeeder`,
y prohíbe modificar pruebas existentes: «Si una prueba vieja falla, frená y avisá».
`CatalogosSeederTest.php:36` exige exactamente **11 rubros**. Con el nuevo rubro,
la suite encuentra **12** y falla. Se frenó y Carlos autorizó el 16/10:
**«Actualizar los conteos y seguir»**. Solo se cambiaron las dos cantidades:
rubros 11 → 12 y subrubros 18 → 19. La prueba y su comprobación de idempotencia
pasan: 2 pruebas, 15 aserciones. Ninguna otra prueba preexistente se modifica.

## Implementación por requisito

- Migración y seeder: dos claves, porcentaje inicial cero y catálogo reservado NEGOCIO.
- `PunitorioService` y comando diario: mes vigente de Wings, sin retroactividad,
  plazo de alta, solo activos, origen único y cálculo congelado.
- Configuración por pantalla: día 1–28 y porcentaje con coma/punto y dos decimales.
- Cobro: inscripción primero; por mes, cuota y recargo. Movimientos separados,
  reversión de imputaciones y etiqueta propia en recibo/anulación.
- Ficha: condonar/reactivar por ADMIN con motivo y auditoría, usando el modal existente.
- Saldos y estado: recargo incluido en Cobranza, buscador, Inicio y Reportes;
  `monto_cuota` conserva solo capital para la comisión docente.
- 35 pruebas nuevas, en tres archivos `Punitorios*Test.php`.

| Punto de la orden | Archivo y línea comprobados | Cobertura nueva |
|---|---|---|
| 1. Migración, claves y reserva NEGOCIO | `database/migrations/2026_10_16_180000_configurar_punitorios_por_mora.php:8`; `database/seeders/CatalogosSeeder.php:23` | Motor:51; Configuración:72 y 92 |
| 2. Día 1–28; 0–100 con coma/punto | `app/Http/Controllers/ConfiguracionWebController.php:14` y `:61`; `app/Services/PunitorioService.php:12` | Configuración:33 y 54 |
| 3. Motor congelado, diario, mes vigente | `app/Services/PunitorioService.php:23`; `app/Console/Commands/AplicarPunitorios.php:10`; `routes/console.php:15` | Motor:61–181, incluidos D1–D3 y reloj |
| 4. Capital, recargo y siguiente mes; anular | `app/Services/PagoCuotaService.php:38`, `:170`, `:947`, `:1037` y `:1128`; `app/Http/Controllers/CajaWebController.php:732` | Cobro:70, 86, 99, 111, 179, 190, 240 y 251 |
| 5. Recibo separado y mes, también anulado | `app/Models/CargoAlumno.php:23`; `app/Services/ReciboService.php:333`; snapshots en PagoCuotaService:990 y :1065 | Cobro:179 y 208; PDF real:288 y script de lectura |
| 6. ADMIN, motivo, perdón/reactivación | `app/Services/PunitorioService.php:79` y `:89`; `app/Http/Controllers/AlumnoWebController.php:203`; rutas bajo `ensure.admin.web` | Motor:184; Cobro:137 y 150 |
| 7. Saldos/estado, sin comisión docente | `app/Services/CobranzaEstadoService.php:136` y `:263`; `app/Services/ReporteMensualService.php:79` y `:94`; PagoCuotaService conserva `monto_cuota` en :197 | Cobro:121, 164, 227, 263 y 277 |

Abreviaturas: **Motor** = `tests/Feature/PunitoriosMotorTest.php`;
**Configuración** = `tests/Feature/PunitoriosConfiguracionTest.php`;
**Cobro** = `tests/Feature/PunitoriosCobroTest.php`. Las líneas se contrastan con los
archivos reales; el índice MCP se reindexó, pero informa cobertura parcial/no
disponible en estas rutas y no se usa como prueba de completitud.

## Los catorce criterios del contrato

| Criterio §11 | Prueba/evidencia que lo comprueba |
|---|---|
| 1. Con cero se conserva el funcionamiento | Motor:61; Cobro:288 y suite completa, con la excepción de conteos aprobada arriba |
| 2. Base nueva preparada | Motor:51, sobre migraciones reales de RefreshDatabase |
| 3. Ambas claves tienen efecto | Configuración:33, guardados HTTP del porcentaje 12,5 y del día 15/10, seguidos de aplicación real |
| 4. Una aplicación por cuota | Motor:71 y :172; un cargo/evento, dos ejecuciones del comando |
| 5. Congelado | Motor:88; subir porcentaje y apagarlo no modifica el cargo |
| 6. Cada mes tiene el suyo | Motor:115; octubre, noviembre y diciembre procesados en sus propios meses |
| 7. Capital primero | Cobro:70, :99 y :240; prueba de navegador y captura `cobro-parcial-1366.jpg` |
| 8. Invariante de imputaciones | Cobro:70 compara acumulado de capital con suma real de pivots, aparte del cargo |
| 9. Solo ADMIN, con motivo | Motor:184 valida rol y longitudes; Cobro:150 rechaza OPERATIVO/PROFESOR/anónimo sin cambiar cargo/eventos |
| 10. Perdonar recargo no limpia capital | Cobro:137 conserva DEUDOR y $10.000 del capital |
| 11. Recibo separado | Cobro:208 comprueba conceptos, PDF y omisión de recargo condonado; :288 captura los datos enviados a la vista real sin reemplazar DomPDF |
| 12. Caja conciliada | Cobro:190: efectivo esperado $10.500, cancelación $0, recobro y cierre sin diferencia |
| 13. Reserva del catálogo | Configuración:92 comprueba rechazo de alta/borrado/edición/desactivación y filas intactas |
| 14. No elegible manualmente | Configuración:109 comprueba ambos selectores, POST manual y ausencia de movimiento; Cobro:70 y :190 sí registran el cargo por cobro |

D1: Motor:102. D2: Motor:130 y :142. D3: Motor:153. D4: Cobro:164 compara
las cuatro respuestas HTTP y sus saldos de $10.500/$500, antes y después de pagar
el capital; :121 controla estado y cobro del recargo solo. Coma/redondeo: Motor:162.
Comisión real 40%: Cobro:227 recibe $4.000 sobre $10.000, excluyendo los $500 de
recargo. Corte histórico de Reportes: Cobro:277.

## Comprobaciones realizadas

Todas las ejecuciones usaron exclusivamente `wings_testing_codex`.

- Primer bloque de 30 pruebas nuevas: **30 aprobadas, 226 aserciones**.
- Tras agregar cinco casos, `PunitoriosCobroTest`: 16 aprobadas y un fallo del
  exportador al pedir login con sesión activa; corregido solo ese test nuevo.
  Repetición del exportador: **1 aprobada, 16 aserciones**.
- Regresión dirigida: **21 aprobadas/60 aserciones** y luego **42/250**;
  los bloques se solapan y no se suman.
- Primera suite con parada al primer fallo: **203 aprobadas, 2 omitidas, 1 fallo,
  557 pendientes; 1740 aserciones; 186,88 s; salida 1**. Falla en el conteo de rubros
  descrito arriba. No es una suite completa verde ni una corrida de las 763 pruebas.
- Suite definitiva posterior a la autorización: **761 aprobadas, 2 omitidas,
  7214 aserciones, 1106,83 s; salida 0**, en `wings_testing_codex`.
  [Salida completa](evidencia-b11/suite-completa.txt). Una corrida intermedia se
  interrumpió para corregir el exportador nuevo antes de esta ejecución definitiva.
- Sintaxis PHP en los 14 archivos de aplicación modificados, comprobación de JS,
  build y compilación/limpieza de vistas sin errores.
- Al actualizar el plan, el guardián documental interpretó la palabra
  «IMPLEMENTADA» como cierre de FIN-14. Se detuvo el pase y se corrigió únicamente
  la documentación: ambos planes dicen «A verificar», con la casilla sin tildar.
  No se modificó esa prueba ni se cerró B11.
  Repetición posterior: **10 aprobadas, 52 aserciones, 7,10 s**;
  [salida de guardianes](evidencia-b11/guardianes-final.txt).

Los HTML y PDF en `evidencia-b11/` proceden de respuestas reales de Wings y de los
datos entregados por el servicio real a DomPDF, con personas ficticias. Los tokens
de sesión descartable se sustituyen antes de guardarlos. Se revisaron ingreso y
Cobrar, ficha y Configuración se revisaron en escritorio 1366 y marcos de 360/375;
cuota 10.000 + recargo 500 = total 10.500. La vista previa de un pago de $4.000
imputa $4.000 a cuota y $0 a recargo, con el botón habilitado. No se enviaron cobros
desde esas copias estáticas: las escrituras se prueban por HTTP contra Wings.

Se guardaron **31 capturas JPEG**: cuatro pantallas × dos porcentajes × tres
anchos, tres del día configurable, tres del login de control y una del pago parcial.
Además, `visor.jpg` guarda la comparación inicial tal como se abrió en el navegador.
El marco de celular tiene ancho real y alto fijo; no se achicó una ventana de Chrome
para luego recortarla. Las mediciones no muestran desborde horizontal. La barra
vertical de Windows reserva 15 píxeles dentro de algunos marcos.

El intento inicial de capturas completas agotó el tiempo del navegador; se usó la
captura nativa del viewport con el marco visible. **No se alteraron las imágenes:**
el visor oculta el espacio exterior al marco mediante CSS. Se desplazó Configuración
hasta el porcentaje y la ficha hasta la sección nueva para que sean revisables.

Los dos PDF reales se rasterizaron con Poppler y se inspeccionaron. Un exportador
inicial reutilizaba DomPDF y deformaba el segundo: se corrigió solo el test nuevo
para observar la vista sin reemplazar el generador. Los PDF definitivos tienen
una página A5; el de 0 muestra cuota/total $10.000 y el de 5 agrega su propio
renglón $500 y total $10.500. `verificar-pdf-b11.py` comprueba el texto real.

## Capturas y reproducción

[Visor comparativo](evidencia-b11/visor.html) · [Mediciones](evidencia-b11/mediciones.json)
· [PDF 0](evidencia-b11/recibo-0.pdf) · [PDF 5](evidencia-b11/recibo-5.pdf)

Rutas exactas, bajo `docs/06-pruebas/PRU-04/evidencia-b11/`:

| Pantalla | Con 0% | Con 5% |
|---|---|---|
| Cobrar | `cobrar-0-1366.jpg`, `cobrar-0-360.jpg`, `cobrar-0-375.jpg` | `cobrar-5-1366.jpg`, `cobrar-5-360.jpg`, `cobrar-5-375.jpg` |
| Ficha | `ficha-0-1366.jpg`, `ficha-0-360.jpg`, `ficha-0-375.jpg` | `ficha-5-1366.jpg`, `ficha-5-360.jpg`, `ficha-5-375.jpg` |
| Configuración | `configuracion-0-1366.jpg`, `configuracion-0-360.jpg`, `configuracion-0-375.jpg` | `configuracion-5-1366.jpg`, `configuracion-5-360.jpg`, `configuracion-5-375.jpg` |
| Recibo real | `recibo-0-1366.jpg`, `recibo-0-360.jpg`, `recibo-0-375.jpg` | `recibo-5-1366.jpg`, `recibo-5-360.jpg`, `recibo-5-375.jpg` |

Controles: `configuracion-dia-{1366,360,375}.jpg`, `login-{1366,360,375}.jpg`,
`cobro-parcial-1366.jpg`. Los PNG `recibo-{0,5}-pdf.png` muestran las páginas reales.

En PowerShell, desde la raíz del repo:

```powershell
$env:DB_DATABASE='wings_testing_codex'
php artisan test --filter='PunitoriosMotorTest|PunitoriosCobroTest|PunitoriosConfiguracionTest'
# Solo al regenerar evidencia; sigue usando únicamente la base descartable propia:
$env:B11_EVIDENCIA='1'
php artisan test --filter=test_porcentaje_cero_sin_cargos_conserva_pantallas_y_exporta_evidencia_a_pedido
Remove-Item Env:B11_EVIDENCIA
pdftoppm -r 110 -png -singlefile docs/06-pruebas/PRU-04/evidencia-b11/recibo-0.pdf docs/06-pruebas/PRU-04/evidencia-b11/recibo-0-pdf
pdftoppm -r 110 -png -singlefile docs/06-pruebas/PRU-04/evidencia-b11/recibo-5.pdf docs/06-pruebas/PRU-04/evidencia-b11/recibo-5-pdf
node scripts/pruebas/preparar-evidencia-b11.mjs
python scripts/pruebas/verificar-pdf-b11.py
php artisan test
```

El visor funciona con los archivos guardados. Para servir solo la evidencia local:
`php -S 127.0.0.1:8769 -t docs/06-pruebas/PRU-04/evidencia-b11`.

## Verificación independiente

### Aprobaciones visuales de Carlos — 16/10/2026

| Alcance mostrado | Respuesta de Carlos | Evidencia |
|---|---|---|
| Visor en celular | «En celular esta bien» | [Visor](evidencia-b11/visor.html), capturas 360/375 |
| Configuración en escritorio | «OK, aprobado» | [Captura 1366](evidencia-b11/configuracion-dia-1366.jpg) |
| Cobrar en escritorio, cuota $10.000 y recargo $500 | «OK» | [Captura 1366](evidencia-b11/cobrar-5-1366.jpg) |

Verificador visual de esos alcances: **Carlos**, conforme a AGENTS §6a.
No se extiende esta aprobación a capturas de ficha/recibo en escritorio que no se
mostraron individualmente ni a reglas, permisos o cálculos del servidor.

Claude debe contrastar reglas, permisos, importes, evidencia y prueba funcional
PEN-001 antes del cierre; de lo visual, resta revisar los alcances no aprobados arriba.
B11 no se cierra por autoevaluación. Sin despliegue; todo permanece
local conforme a la decisión de Carlos sobre `52e6bdf` y sus descendientes.
