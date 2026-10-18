# T36 — Verificación persistente de movimientos de caja

Implementación local de Codex CAB, 17/10/2026. Sin despliegue ni cierre propio.
Inicio exclusivo del tablero en `85bdc0c`.

## Alcance y decisiones

- DU-54: «Exigir todos tildados para validar».
- Carlos respondió: **«Destildar solo los movimientos que cambien»**. Rechazar no borra todos.
- Solo ADMIN activo, igual que la validación, puede tildar o destildar.
- Mantener el diseño aprobado de T37: cuatro botones 2 × 2 a la derecha y pareja Resumen/Volver a la derecha. La aprobación anterior no se presenta como aprobación nueva de T36.
- Retirar todo JavaScript incrustado de Detalle y Resumen, incluidos sus tres bloques y un onsubmit. No intervenir en las demás vistas de T73.
- No desplegar ni tocar archivos de T38/T41; el árbol tiene trabajo paralelo ajeno.

## Estado inicial comprobado

Leídos los cuerpos de `validarCaja`, `rechazarCaja`, `actualizarMovimientoEnCaja`,
`cancelarCobroOperativo` y `reflejarCajaEnCashflow`, las dos vistas y la delegación de
modales/anti doble-submit de `ds-app.js`. Los tildes solo vivían en el navegador y
validar no los consultaba. Detalle recorre todos los movimientos: manuales y cobros,
activos y cancelados. Cashflow refleja solo ACTIVO.
El índice estructural sirvió de orientación; las vistas y los cuerpos se comprobaron
contra los archivos porque su cobertura no era completa.

## Implementación

Migración nueva: fecha de revisión, usuario revisor (FK anulable) y huella SHA-256
sobre el contenido persistido del movimiento, con orden y formatos estables.
No se fabrican revisiones históricas ni se permite asignarlas masivamente por fillable.
El modelo limpia las tres columnas al editar contenido. La huella también invalida
actualizaciones masivas: la cancelación de un pago usa un update que no dispara eventos
Eloquent. Así se protege esa vía sin modificar PagoCuotaService, reservado a Claude.
En ese caso puede quedar la fecha histórica en la fila, pero la revisión se considera
inválida en pantalla y en el servicio; nunca se cuenta solo por fecha no nula.

PATCH de revisión bajo el grupo ADMIN existente; se valida también el actor activo en
el servicio. Exige caja CERRADA con conteo y pertenencia del movimiento a esa caja.
Bloquea cajón, caja y movimiento. Una huella de pantalla vieja no puede confirmar
contenido distinto: responde 422, con indicación de recargar.
El navegador conserva el último estado guardado, deshabilita la casilla mientras guarda
y bloquea Validar mientras hay pedidos pendientes. Un error revierte la casilla y muestra
un aviso en castellano; el contador solo usa confirmaciones del servidor.

`CajaService::validarCaja` bloquea y comprueba todos los renglones antes de cambiar
estado o generar asientos. Informa «Falta 1 movimiento…» o «Faltan N movimientos…».
Los cancelados visibles también requieren revisión y siguen sin sumarse a Cashflow.
Una caja vacía valida; las ya VALIDADA conservan el camino idempotente previo.

**Resumen:** conserva su POST Validar al mismo servicio. Ante revisiones faltantes,
el controlador redirige a Detalle con el número faltante en el aviso superior.
No valida ni genera asientos durante esa redirección. No se crea una revisión diferente
para Resumen ni una puerta que eluda la regla.

Los modales utilizan la delegación existente de ds-app.js; se agrega allí únicamente
el cierre al pulsar el fondo de los dos modales de caja. El contador, el guardado y la
confirmación de eliminar van en `resources/js/caja-detalle.js`, con entrada Vite.
La confirmación se atiende en el formulario antes del anti doble-submit delegado.
No se añaden funciones globales, frameworks ni estilos compartidos.

## Pruebas verificadas

Base exclusiva `wings_testing_codex`; almacenamiento exclusivo `storage/app/t36-runtime`.
Nunca se ejecutó suite simultánea a los fixtures de navegador en esa base.

| Recorrido | Resultado |
|---|---|
| Primeras T36 + CSP | 14 aprobadas, 78 aserciones, 13,93 s |
| T36, A25, Cashflow, Reportes y CSP | 68 aprobadas, 449 aserciones, 179,44 s |
| Concurrencia T36, cancelación real y seis cruces FIN-11 | 9 aprobadas, 125 aserciones, 109,57 s |
| Suite completa compartida | 818 aprobadas, 2 omitidas, 1 fallida; 7584 aserciones, 1332,77 s; salida 1 |
| Repetición integrada tras `cbc851e` de Claude | 50 aprobadas, 314 aserciones, 34,81 s; salida 0 |

La suite completa **no fue verde**. Único fallo: prueba de T38
`ReingresoCuotaT38Test::test_el_cobro_no_recalcula_la_cuota_del_reingreso_en_el_mes_del_alta`:
esperaba `4000.00` y obtuvo `7000.00`. En esa corrida T38 estaba en trabajo paralelo,
sin versionar; su método cobra mediante PagoCuotaService, no valida una caja. No se corrigió
ni se eligió una regla de reingreso desde T36. [Salida completa](evidencia-t36/suite-completa.txt).
Todas las pruebas nuevas de T36 y las regresiones de caja pasaron en esa corrida.
Tras el commit de Claude, se repitieron T38, las pruebas funcionales T36, seis cruces
FIN-11 y los guardianes: las 50 pasaron, incluido el caso antes fallido. El fallo queda
resuelto en esta comprobación dirigida del código integrado; no se vuelve a declarar
abierto ni se convierte la corrida completa anterior en verde.

Se ejecutaron 821 casos. El árbol llegó a declarar 823 por trabajo paralelo;
no se presenta esa diferencia como pruebas extra ejecutadas en la suite completa.
Después Claude publicó T38 en `cbc851e`: HEAD pasa a 804 pruebas; con las 14 nuevas de
T36 se publican **818 pruebas**. Los cuatro contadores del commit usan ese número;
la carpeta compartida conserva 823 mientras Gemini no entregue sus cinco pruebas.
Guardian documental en copia de HEAD + T36, sin base: **1 aprobada/7 aserciones**.
No se incluyen pruebas ni código ajeno por arrastre.

Se comprueban tildar/destildar, autor y recarga; todo marcado; uno pendiente; Resumen;
llamada directa; caja vacía; permisos de OPERATIVO/PROFESOR; rechazo y edición de uno;
cancelación real de cobro; cancelados visibles; pantalla vieja y movimiento de otra caja.
Se adaptaron preparaciones antiguas de Cashflow/Reportes/FIN-11 para revisar mediante
el servicio real, sin borrar sus aserciones originales ni forzar columnas de revisión.
La primera regresión detectó nueve casos antiguos que validaban sin marcar (24 pasaron);
las preparaciones corregidas dieron el resultado verde de 68 indicado arriba.

Dos conexiones reales de MariaDB: revisión primero/validación después y validación
primero/destildar después. La segunda conexión recibe timeout por el bloqueo; su registro
SQL no tiene INSERT/UPDATE/DELETE. Tras commit se reintenta con el estado vigente:
valida una vez o rechaza destildar una caja ya validada. No se afirma reanudación automática.

Chrome real local, datos sintéticos y servidor de Wings: **ocho comprobaciones aprobadas**
en [resultado.json](evidencia-t36/resultado.json). Persistencia al recargar, bloqueo desde
Detalle y Resumen, apertura/cierre por botón y fondo de ambos modales, fallo real de red
con reversión de casilla, validación con todo revisado, operativa sin casillas y confirmación
de eliminar cancelada sin dejar el formulario inutilizable.

Capturas reales de Detalle a [1366](evidencia-t36/detalle-uno-verificado-1366.png),
[360](evidencia-t36/detalle-uno-verificado-360.png) y
[375](evidencia-t36/detalle-uno-verificado-375.png), inspeccionadas.
Móviles en iframe de ancho real dentro de Chrome grande, a partir del DOM de Wings;
[login de control](evidencia-t36/login-control-360.png) entra completo.
[Rechazo en Detalle](evidencia-t36/rechazo-detalle-360.png),
[rechazo en Resumen](evidencia-t36/rechazo-resumen-360.png) y
[cancelación](evidencia-t36/cancelacion-operativa-360.png) inspeccionados.
No se usó una maqueta escrita a mano. Las capturas no certifican teléfono físico.
Completadas e inspeccionadas también [Resumen 1366](evidencia-t36/resumen-1366.png),
[Resumen 375](evidencia-t36/resumen-375.png),
[tildes al deslizar a 360](evidencia-t36/detalle-tildes-derecha-360.png),
[a 375](evidencia-t36/detalle-tildes-derecha-375.png) y
[aviso con uno pendiente](evidencia-t36/detalle-falta-uno-360.png).
Doce capturas; sus hashes SHA-256 quedan en resultado.json. Segunda pasada completa
del recorrido también aprobada. Resumen conserva su disposición anterior.

`npm run build` pasó (5,64 s en la última compilación); PHP lint de los 16 archivos
propios y sintaxis de ambos módulos JS y del recorrido correctos; diff sin errores.
Conteo literal: **8 bloques en 8 vistas y 8 manejadores**. Se retiraron 3 bloques y 1
manejador; test CSP, AGENTS, estado, plan y T73 actualizados juntos.

Compilación final de vistas y limpieza correctas; ambas Blade también pasan php -l.
[Regresiones](evidencia-t36/regresiones.txt), [concurrencia](evidencia-t36/concurrencia.txt)
y [build](evidencia-t36/build.txt) conservan las salidas.
En los TXT versionados solo se normalizaron los espacios finales y el fin de archivo;
los registros originales permanecen en storage/app/t36-*.log.

## Entrega y límites

T36 queda `a_verificar`, tiene y verifica Claude, hizo Codex. Se registra la decisión
textual de Carlos en contrato, informe, continuidad, log y paso del tablero.
La verificación independiente de reglas corresponde a Claude; no se autocierra.
El fallo de reingreso y su repetición posterior aprobada quedan expresamente visibles.
Las capturas son evidencia local, no aprobación nueva de Carlos ni prueba en un teléfono.
Sin despliegue ni modificaciones propias en archivos reservados a T38/T41.
