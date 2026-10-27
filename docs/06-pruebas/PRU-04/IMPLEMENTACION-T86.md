# T86 — Sueldo de una operativa por Cashflow

Codex CAB, 27/10/2026. **Implementada localmente; entrega a Claude para verificación.** Sin despliegue. La aprobación escrita está en el objetivo adjunto de Carlos del 27/10: operativas sin reserva, profesoras reservadas y migración por `users.subrubro_id`/rol.

El dueño puede elegir el sueldo de una operativa en Nuevo movimiento de Cashflow y registrar su pago. El rubro Sueldos sigue protegido; los sueldos docentes se siguen pagando por Liquidaciones. No se editaron vistas ni CSS.

## Cambios

- `SubrubroSueldoService::crearOReutilizar` recibe explícitamente la reserva: `paraProfesor` lo llama con `reservado: true` y `paraUsuarioOperativo` con `reservado: false`. Conserva `permitido_para=ADMIN`, `afecta_caja=false` y clasificación `NEGOCIO`.
- Migración `2026_10_27_120000_habilitar_sueldos_operativas_en_cashflow`: desmarca únicamente subrubros reservados del rubro Sueldos/EGRESO vinculados por FK a usuarios de rol OPERATIVO. No usa el nombre «Op-». Excluye cualquier ID también vinculado a una profesora y no libera otros rubros por una FK errónea.
- La migración modifica solo `es_reservado_sistema`: no toca movimientos, importes, fechas, autores, nombres ni vínculos. Es idempotente. `down` no intenta reconstruir reservas anteriores que no se guardaron; la corrección de datos no se revierte automáticamente.
- Nueva prueba permanente `SueldoOperativaT86Test`, diez casos. Se actualiza el esperado antiguo de `RubroReservadoTest` que exigía reservar el sueldo operativo; el control docente permanece y se prueba por POST forzado. Contador real: **1069 métodos** en `tests/`, actualizado en Estado, Plan y Checklist.

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

## Dos límites expresos para Carlos y Claude

**Reportes → Sueldos no incluye operativas.** Su servicio y su vista actuales analizan docentes, clases, asistencias y costo por deporte. Tras registrar el pago sintético, se pidió esa ruta real: devuelve una lista de profesoras vacía y costo cero en el escenario sin docentes; el sueldo operativo sí figura en Ingresos/egresos e Inicio. No se considera cumplida su inclusión en el análisis de Sueldos. Se deja la diferencia documentada: agregar operativas a ese análisis requiere decidir su presentación y alcance; esta entrega respeta la orden de no tocar vistas/CSS y no las disfraza como profesoras ni reparte su costo por deportes.

[Comprobación posterior del alcance pendiente](evidencia-t86-codex/ALCANCE-PENDIENTE.md): rótulos exactos de la pantalla y por qué agregar datos desde el servidor no resuelve la diferencia. El objetivo sigue abierto por ese punto hasta aclarar el alcance; no se toma silencio como decisión.

**Cambio de rol con un vínculo existente:** el controlador conserva la FK y el servicio no altera su reserva. Una operativa nueva promovida a ADMIN conserva un sueldo no reservado. Un ADMIN con FK antigua reservada que pasa a OPERATIVO conserva esa reserva y no queda habilitado por esta migración si el cambio de rol fue posterior. La migración mira el rol en el momento de ejecutarse. No se decide automáticamente qué hacer con esos casos ni se reescribe la historia; Carlos debe definirlo. [Observación por rutas reales](evidencia-t86-codex/observaciones.json).

## Pruebas

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

**Entrega de tablero:** T86 `a_verificar`, `hizo=Codex`, `verifica=Claude`, `tiene=Claude`. Claude verifica el pago y las protecciones; los dos límites anteriores quedan visibles y no se presentan como resueltos. La tabla de pruebas externa y su copia no se cambian. T85/T93 concurrentes quedan fuera del commit. Sin push manual, servidor, base del club ni despliegue.

El diagnóstico inicial (1/5) consultó el reporte sin un pago previo; la versión final (1/6) agrega el pago y conserva el límite del reporte. Se conservan ambas salidas y solo se acredita la final para ese punto. El HTML histórico T34 regenerado por la suite se devolvió a sus bytes originales de Git.
