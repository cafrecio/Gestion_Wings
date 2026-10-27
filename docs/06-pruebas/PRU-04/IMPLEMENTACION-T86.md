# T86 — Sueldo de una operativa por Cashflow

Codex CAB, 27/10/2026. **Cerrada: lógica aprobada por Claude y aspecto aprobado por Carlos.** Entrega inicial local; sin despliegue desde este chat. La aprobación escrita está en el objetivo adjunto de Carlos del 27/10: operativas sin reserva, profesoras reservadas y migración por `users.subrubro_id`/rol.

El dueño puede elegir el sueldo de una operativa en Nuevo movimiento de Cashflow y registrar su pago. El rubro Sueldos sigue protegido; los sueldos docentes se siguen pagando por Liquidaciones. Reportes → Sueldos también muestra esos pagos por persona; el gasto no se reparte por deporte. Se modificó su vista con la confirmación escrita de Carlos; no se cambió CSS.

## Cambios

- `SubrubroSueldoService::crearOReutilizar` recibe explícitamente la reserva: `paraProfesor` lo llama con `reservado: true` y `paraUsuarioOperativo` con `reservado: false`. Conserva `permitido_para=ADMIN`, `afecta_caja=false` y clasificación `NEGOCIO`.
- Migración `2026_10_27_120000_habilitar_sueldos_operativas_en_cashflow`: desmarca únicamente subrubros reservados del rubro Sueldos/EGRESO vinculados por FK a usuarios de rol OPERATIVO. No usa el nombre «Op-». Excluye cualquier ID también vinculado a una profesora y no libera otros rubros por una FK errónea.
- La migración modifica solo `es_reservado_sistema`: no toca movimientos, importes, fechas, autores, nombres ni vínculos. Es idempotente. `down` no intenta reconstruir reservas anteriores que no se guardaron; la corrección de datos no se revierte automáticamente.
- Nueva prueba permanente `SueldoOperativaT86Test`, diez casos. Se actualiza el esperado antiguo de `RubroReservadoTest` que exigía reservar el sueldo operativo; el control docente permanece y se prueba por POST forzado. La ampliación del reporte agrega ocho casos permanentes en `ReporteSueldosOperativasT86Test`: **1077 métodos** en `tests/`, actualizado en Estado, Plan y Checklist.

La migración solo se ejecutó como parte de las pruebas sobre `wings_testing_codex`. En el sitio y producción se aplicará con el despliegue correspondiente; no se modificaron sus datos.

## Comprobación del comportamiento

| Caso | Evidencia |
|---|---|
| Alta de sueldo operativo | No reservado, solo ADMIN, no afecta caja operativa, NEGOCIO y FK al rubro Sueldos. El alta web histórica también queda cubierta por `RubroReservadoTest`. |
| Selector de Cashflow | GET real de la aplicación: Sueldos contiene las dos operativas de prueba y excluye a la profesora; se comprueba el HTML y los datos del selector. |
| Pago del dueño | POST de **$48.000,75**: un movimiento directo, importe **-$48.000,75**, EGRESO/NEGOCIO, sin imputación a un deporte. |
| Cashflow, Ingresos/egresos e Inicio | Mismo movimiento y concepto; egresos/gastos del club $48.000,75, resultado y disponible -$48.000,75 en el escenario sin ingresos. Se comprueban rutas reales y datos del reporte, no solo una suma aislada. |
| Sueldo docente | Sigue reservado y oculto; POST forzado rechazado sin escribir movimientos. OPERATIVO y PROFESOR tampoco obtienen acceso para pagar por Cashflow. |
| Renombrado | PUT de usuario operativo renombra el mismo subrubro, conserva FK y todos los campos del movimiento ya cargado; sigue habilitado. |
| Migración | Libera un sueldo con nombre histórico sin «Op-»; conserva un «Op-» huérfano, un vínculo de ADMIN y los sueldos docentes. FK compartida con profesora protegida; segunda ejecución sin nuevos cambios. |
| ADMIN sin vínculo pasa a OPERATIVO | El controlador existente crea el sueldo nuevo habilitado. No se cambia la política para vínculos antiguos. |

## Reportes → Sueldos incluido en T86

Carlos confirmó por escrito: **«No, incluir operativas en Sueldos es parte de T86»**. Se completa ese alcance en el servicio y en `resources/views/reportes/sueldos.blade.php`; la interpretación inicial que dejaba la pantalla pendiente queda reemplazada. El [antecedente](evidencia-t86-codex/ALCANCE-PENDIENTE.md) conserva la diferencia original sin presentarla como actual.

El bloque «Pagos de operativas» muestra total, persona, importe y cantidad de pagos del mes hasta el corte. Se identifica por FK de usuario y rubro Sueldos/EGRESO, excluyendo conceptos docentes, movimientos operativos y referencias ajenas. Agrupa varios pagos, compensa las correcciones con su contramovimiento y no cuenta el pago anulado como vigente. Los huérfanos con nombre «Op-» no se confunden con un usuario.

Es gasto del club: al filtrar por deporte conserva el mismo total y lo aclara en pantalla. Los indicadores, filas y gráficos docentes conservan sus cálculos. El pago histórico sigue visible si el usuario cambia de rol o se desactiva mientras conserva su FK; eso no habilita nuevos pagos que sigan reservados.

[Galería para revisión visual](APROBAR-T86-SUELDOS.html): con pagos y sin pagos, escritorio 1366 y celular en iframe de **375 reales**, con login de calibración. Son capturas de Chrome del HTML devuelto por la ruta real de Laravel, assets reales y datos sintéticos de `wings_testing_codex`; no son una maqueta ni navegación al sitio de prueba. El HTML solo adapta URLs locales y neutraliza tokens. Se revisaron las imágenes; Carlos aprobó las capturas: «Las imagenes estan OK». No se modificó CSS ni JavaScript de producto.

## Duda de cambio de rol, sin política nueva

**Cambio de rol con un vínculo existente:** el controlador conserva la FK y el servicio no altera su reserva. Una operativa nueva promovida a ADMIN conserva un sueldo no reservado. Un ADMIN con FK antigua reservada que pasa a OPERATIVO conserva esa reserva y no queda habilitado por esta migración si el cambio de rol fue posterior. La migración mira el rol en el momento de ejecutarse. No se decide automáticamente qué hacer con esos casos ni se reescribe la historia; Carlos debe definirlo. [Observación actual por rutas reales](evidencia-t86-reportes-codex/observaciones-actualizadas.json). El importe $48.000,75 ya aparece en Sueldos después de promover a ADMIN.

## Pruebas iniciales del pago — corte histórico f257fd6f

| Corrida | Resultado |
|---|---|
| Antes del arreglo | Diez casos: **7 fallidos y 3 aprobados**, 18 aserciones, salida 1. Servicio original; migración todavía sin operación de datos. Los fallos incluyen elegir/pagar, reserva nueva, migración y cambio de ADMIN sin vínculo. |
| Dirigidas finales | **24 aprobadas**, 162 aserciones, 15,428 s, salida 0: T86 y `RubroReservadoTest`. |
| Diagnóstico adicional | **1/6**, 12,171 s, salida 0. Ejecuta el pago y luego consulta Sueldos; registra los cambios de rol sin fijar una nueva política como requisito de la suite permanente. |
| Rápida | **944 aprobadas, 6 omitidas**, 7.521 aserciones, 200,49 s, salida 0. |
| Completa | **1.063 aprobadas, 6 omitidas**, 9.120 aserciones, 1.031,78 s, salida 0. |

[Rojo](evidencia-t86-codex/rojo.txt) · [JUnit rojo](evidencia-t86-codex/rojo.xml) · [Dirigidas](evidencia-t86-codex/dirigidas.txt) · [JUnit dirigidas](evidencia-t86-codex/dirigidas.xml) · [Diagnóstico](evidencia-t86-codex/observaciones-final.txt) · [Rápida](evidencia-t86-codex/rapida.txt) · [Completa](evidencia-t86-codex/completa.txt).

Corridas secuenciales, `APP_ENV=testing`, `DB_DATABASE=wings_testing_codex`, host local y `DB_URL` vacío, con bootstrap que rechaza otra base o configuración cacheada. Conexión real MariaDB10.4.32 comprobada. Notificaciones simuladas. Las pruebas permanentes admiten las bases descartables propias de los tres agentes; nuestras ejecuciones usan exclusivamente Codex.

Reproducción desde la raíz, con esas variables:

```powershell
php artisan test tests/Feature/SueldoOperativaT86Test.php tests/Feature/RubroReservadoTest.php
php artisan test --exclude-group=lento
php artisan test
```

Sintaxis PHP y compilación de vistas: cinco archivos PHP sin errores; `view:cache` y `view:clear` exitosos. [Fuentes](evidencia-t86-codex/fuentes.json) · [MCP y cobertura](evidencia-t86-codex/grafo-y-cobertura.json). Se usaron búsqueda, traza y fragmento del grafo; la cobertura no disponible/metadata_changed se completó leyendo fuentes y buscando referencias reales. No se deducen ausencias del índice.

## Pruebas de la ampliación del reporte

| Corrida | Resultado |
|---|---|
| Antes de incluir operativas | **8 fallidas**, 9 aserciones, salida 1: falta el dato y la pantalla. |
| Dirigidas finales | **30 aprobadas**, 133 aserciones, 18,033 s, salida 0: reporte docente, ocho nuevas, pago T86 y guardia CSP. |
| Diagnóstico por rutas reales | **1 aprobada**, 8 aserciones, 11,701 s, salida 0: pago visible y reservas por cambio de rol documentadas. |
| Captura de la ruta real | **1 aprobada**, 4 aserciones, 12,245 s, salida 0: con pagos, vacía y login. |
| Rápida | **952 aprobadas, 6 omitidas**, 7.558 aserciones, 166,97 s, salida 0. |
| Completa | **1.071 aprobadas, 6 omitidas**, 9.157 aserciones, 972,62 s, salida 0. |

[Rojo](evidencia-t86-reportes-codex/rojo.txt) · [Dirigidas finales](evidencia-t86-reportes-codex/dirigidas-final.txt) · [Rápida](evidencia-t86-reportes-codex/rapida.txt) · [Completa](evidencia-t86-reportes-codex/completa.txt) · [Capturas y datos](evidencia-t86-reportes-codex/datos-captura.json).

Misma base local exclusiva Codex y guardias, sin corridas simultáneas. La evidencia inicial permanece histórica: su auditor verifica fuentes e informe contra `f257fd6f`, sin exigir que persista la ausencia ya corregida. La evidencia ampliada tiene su propio manifiesto y auditor.

**Cierre de tablero, 27/10/2026:** T86 `cerrado`, `hizo=Codex`, `verifica=Claude`, `tiene=nadie`. [Verificación de lógica de Claude](VERIFICACION-T86-CLAUDE.md), 32/32 y seguridad23/23; Carlos aprobó el aspecto: «Las imagenes estan OK». [Registro del cierre y guía de uso](evidencia-t86-reportes-codex/CIERRE-T86.md). La duda de reservas antiguas al cambiar de rol permanece no bloqueante, sin decidir una política. Tabla externa/copia intactas. Sin despliegue en esta actuación.

El diagnóstico inicial (1/5) consultó el reporte sin pago previo; el histórico final (1/6) agregó el pago y constató la ausencia de operativas entonces. El diagnóstico ampliado (1/8) acredita su inclusión actual. El HTML histórico T34 regenerado por la suite se devuelve a sus bytes originales de Git.
