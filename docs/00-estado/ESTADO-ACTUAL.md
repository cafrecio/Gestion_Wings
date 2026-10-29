# Wings — Estado actual

## Prueba sin confirmaciones nativas en el sitio de prueba — Claude CAB, 28/10/2026

Pedido de Carlos para la prueba por días: las confirmaciones del navegador traban al Chrome automático de Codex.
Con `PRUEBA_SIN_CONFIRMACIONES=true` en el `.env` del sitio de prueba, el layout marca la página y
`resources/js/prueba-sin-confirmaciones.js` las acepta solas. En producción nunca se activa
(`App\Support\PruebaSinConfirmaciones`, `PruebaSinConfirmacionesTest`). No verificado por otro agente:
es herramienta de prueba detrás de una variable. `scripts/pruebas/rutina/rutina.php` suma `crear_serie` y
`motivos` en las listas (B3). La reemplaza a futuro MF10 (ventana propia de Wings).

## Sitio de prueba desplegado en `b271e8dd` — Claude CAB, 28/10/2026

Pedido de Carlos. Incluye T87, T88, B3, T54, T65, T25, T89, T62, T70/T71, T51, T45 y T46 (sus «sin desplegar» de abajo quedan históricos) y la Ayuda de T12/T13 a medias, que Carlos autorizó. Respaldo `/root/respaldos/wingstest-antes-despliegue-2026-10-28.sql.gz`; 5 migraciones DONE; /login 200, CSP en bloqueo, reloj simulado intacto (01/11), sin errores nuevos. Producción no se tocó. Sigue la prueba corta de 3-4 días; el guion se conversa antes con Carlos.

## T46 — Profesora inactiva sin clases futuras ni acceso, Codex CyE 28/10/2026

**CERRADA 28/10:** lógica verificada por Claude CAB (276/276 del área y 4/4 ataques propios, [informe](../06-pruebas/PRU-04/VERIFICACION-T46-CLAUDE.md)); OK visual de Carlos. Sin desplegar.
Bloquea la baja con clases activas futuras/en curso y pide reasignar por el circuito existente.
Una inactiva con clases dictadas pendientes aparece en Nueva liquidación; sin pendientes, no.
La baja desactiva usuario, invalida recuerdo y elimina sesiones. Reactivar profesora conserva
el usuario inactivo hasta habilitación explícita; activar solo la cuenta no elude la baja.
15 propias/113 verdes; área50/343. Rápida1128/6 omitidas/8635 aserciones/373,71s;
completa1264/6 omitidas/10426/1636,60s, salidas0 en wings_testing_codex.17 fuentes propias intactas.
Solo una prueba T12/T13 ajena cambió durante la completa; fuera de esta entrega.
[Entrega](../06-pruebas/PRU-04/IMPLEMENTACION-T46.md) · [Chrome1366/375](../06-pruebas/PRU-04/APROBAR-T46.html). Las tres entregas T51/T45/T46 completas; sin despliegue.

## T45 — Vigencia de la forma de liquidar, Codex CyE 28/10/2026

Implementada localmente; `a_verificar` por Claude y aspecto pendiente de Carlos.
Bloqueo por abiertas identificadas; cambio desde el mes siguiente con historial de vigencias.
Mes actual, cerradas/pagadas y cortes anteriores conservados; sin porcentaje no se liquida.
El formulario permite cargar la forma próxima conservando tarifa/porcentaje anteriores.
14 propias/106 aserciones verdes, incluido esquema anterior con DDL real (rojo antes, verde después).
Rápida definitiva1113/6 omitidas/8523 aserciones/229,88s; completa1249/6 omitidas/10314/1317,09s, salidas0 en wings_testing_codex.
18 fuentes propias y629 globales intactas. [Entrega](../06-pruebas/PRU-04/IMPLEMENTACION-T45.md) · [Chrome1366/375](../06-pruebas/PRU-04/APROBAR-T45.html). Sin despliegue.

## T12 y T13 — Manual del usuario y Ayuda en Wings: Corrección Etapa 1, Gemini CAB 28/10/2026

Corrección de Etapa 1 completada bajo el método obligatorio con 3 muestras reales (Operativa, Dueño y Profesor), fichas de fuentes y revisión independiente sin hallazgos abiertos; `a_verificar`, tiene=Claude, verifica=Claude, paso «Corrección etapa 1: control de Claude y OK de Carlos».
El contenido vive en archivos Markdown bajo `resources/manual/{rol}/{slug}.md`, leídos por `ManualService`.
Entrada «Ayuda» en Sistema para Admin y Operativo, y en navegación para Profesor.
Botón «?» en la barra superior (topbar) implementado en las pantallas de muestra: `/caja/cobrar/{alumnoId}` (`cobrar-cuota`) y `/cashflow` (`corregir-movimiento-caja-validada`).
Botón «Imprimir» funcional con `@media print` que oculta menús, cabeceras y botoneras para una hoja impresa limpia.
Muestra 1 (Operativa): «Cobrar una cuota» con 4 pasos reales y «Qué hago si…» (ajustado a controles reales, sin supuestos).
Muestra 2 (Dueño): «Corregir un movimiento mal cargado en una caja ya validada» (DU-27) con explicación de la regla contable, 4 pasos y hueco de reversión de deuda documentado.
Muestra 3 (Profesor): «Tomar asistencia en una clase» con 3 pasos y casos de borde (clase cancelada, sin ocurrir, corrección con motivo).
Lista de 54 páginas propuestas cubriendo DU-20, DU-27, DU-29 y el capítulo de Primera Carga en `T12-LISTA-DE-PAGINAS.md`.
Fichas de fuentes por página en `docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/` con correspondencia exacta archivo:línea.
11 capturas reales tomadas en base local con huellas MD5 100% únicas servidas por la ruta autenticada `/ayuda/img/{rol}/{archivo}`.
17 pruebas propias / 79 aserciones verdes en `AyudaManualT12T13Test` (permisos por rol, 403 ante URL forzada, servicio autenticado de imágenes del manual con control estricto de roles, 404, login, botón ? y botones volver/imprimir).
Revisión independiente ejecutada bajo `REVISOR-MANUAL.md` con 0 graves y 0 medios abiertos (`evidencia-t12-t13/revisiones/2026-10-28-etapa1.md`).
`scripts/manual/verificar-manual.php` en 0 problemas. Rápida 1134 aprobadas / 6 omitidas.
[Informe](../06-pruebas/PRU-04/IMPLEMENTACION-T12-T13.md) · [Galería](../06-pruebas/PRU-04/APROBAR-T12-T13-MUESTRAS.html). Sin despliegue.

## T51 — Correcciones manuales con historia, Codex CyE 28/10/2026

Implementada localmente; `a_verificar` por Claude y aspecto pendiente de Carlos.
Detalle conserva antes/después, autor y fecha/hora, incluso al eliminar. Cobros automáticos
sin Editar/Eliminar por pantalla o ruta. Arqueo y gasto pendiente usan el movimiento vigente.
9 pruebas propias / 77 aserciones verdes; rápida 1088 aprobadas / 6 omitidas.
Completa 1217 aprobadas / 6 omitidas / 7 HTTP 503 en Inicio; los dos archivos repetidos
solos: 17/152 verdes. Causa no comprobada; no se declara completa verde.
[Entrega](../06-pruebas/PRU-04/IMPLEMENTACION-T51.md) · [Chrome real 1366/375](../06-pruebas/PRU-04/APROBAR-T51.html). Sin despliegue.

## T70/T71 — Implementadas localmente, a verificar por Claude, Codex CyE 28/10/2026

Historial de Inactivo y fecha del tramo actual; Reporte de Alumnos con altas por fecha
de ingreso y registros distintos que pasaron a Inactivo en el mes, sin duplicar al
alternar. Inactivos anteriores sin fecha. T84/T85 y reingreso T38 conservados.
Deuda actual separada en Cobranza/Reportes, fuera de Inicio y del por cobrar; inscripción
compartida sin duplicar. Pérdida solo ADMIN, campo distinto de condonación, auditoría
atómica y ficha con autor/fecha/importe. Al volver, lo perdido no se repone.
15 pruebas propias y área/repetición 37/222 verdes; capturas reales 1366/marco375 y cliente
sin navegación comprobados. Completa: 1214 aprobadas, 6 omitidas, 10081 aserciones, 1671,44s;
salida 0 en wings_testing_codex. Las 23 fuentes propias siguen iguales al corte probado.
[Entrega](../06-pruebas/PRU-04/IMPLEMENTACION-T70-T71.md) · [Galería](../06-pruebas/PRU-04/APROBAR-T70-T71.html).
T70/T71 a_verificar, tiene=Claude, verifica=Claude; falta su revisión y el aspecto de Carlos.
Sin base del club, sitio de prueba ni despliegue. Los cambios T62 concurrentes quedaron
en 5b0869cd; el contador compartido 1220 incluye 15 T70/T71 y 10 T62, sin atribuirlas a Codex.

## T89 — Opciones A/A/B/B implementadas localmente, Codex CAB 28/10/2026

Carlos eligió Movimientos A, Cashflow A, Inicio B y Detalle B y pidió ver Movimientos. Autor real y recibo por fila; consulta de otra caja filtrada por rubro OPERATIVO, sin acciones de escritura; acceso Historial junto a Editar en Cashflow; Todos ausentes en gris para lista cargada sin presentes; hora de registro separada de fecha contable. Se reutiliza el diseño existente, sin CSS compartido ni JavaScript nuevos. [Implementación y verificaciones](../06-pruebas/PRU-04/IMPLEMENTACION-T89-CODEX.md). Completa1182 aprobadas/6 omitidas y área final53/454 verdes; [capturas finales1366/375](../06-pruebas/PRU-04/APROBAR-T89.html). A verificar por Claude; aspecto final de Carlos pendiente, sin despliegue.

## T65 — Segunda verificación aprobada en lógica, Codex CAB 28/10/2026

Verificado7e9c2e7f contra el informe propio: cargos antes que cuotas y sólo inscripción reasignable; bajas analíticas con septiembre conservado y octubre retirado; excepciones responden422, ofrecen inactivo y no se borran. Contrapruebas con código13b85f68 rojas, incluidas Gemini y Sueldos con escenario actual. Área49/287, concurrencia2/75 aparte y complementarias3/53 verdes. Rápida1067 aprobadas/6 omitidas/1 roja: sólo contador documental1188 frente a1195 por siete métodos T25 ajenos aún sin commit, repetido aislado. Sin fallas de cobro ni producto corregido por Codex. [Segunda vuelta y alcance ALU-024](../06-pruebas/PRU-04/VERIFICACION-T65-CODEX.md#8-segunda-vuelta--codex-cab-28102026). T65 a_verificar,tieneCarlos,verificaCodex: falta OK visual. Solo wings_testing_codex; sin sitio/servidor/base del club ni despliegue.

## B3 — Devolución 27/10 corregida y a verificar por Claude, Codex CAB

Claude devolvió asistencia de51d26fb8 por tratar como nuevo a quien no tiene pagos, ignorando la regla de §4. Corregido: los ids de `primera_carga.detalle.alumnos`, solo con estado TERMINADA, tienen historia. Al día/En plazo/Moroso sin motivo; Deudor con motivo de deuda. Altas posteriores conservan dos clases de margen; PENDIENTE/sin detalle no exime. El dato ya existía: la pregunta que dejó el informe inicial era un error, no una decisión pendiente de Carlos.
Siete permanentes adaptan la roja de Claude:5 fallidas/2 aprobadas antes; área58/292 y técnica día21 1/5 verdes. Rápida1028 aprobadas/6 omitidas/7983 aserciones/224,98s, salida0; contador1153. Sin Blade/CSS/JS ni lógica de dinero modificados. [Devolución e inventario de otros usos](../06-pruebas/PRU-04/IMPLEMENTACION-B3-ASISTENCIA.md) · [Capturas nuevas1366/375](../06-pruebas/PRU-04/APROBAR-B3-PRIMERA-CARGA.html). B3 a_verificar por Claude; dinero17f99d13 aprobado en lógica, aspecto pendiente de Carlos. Solo wings_testing_codex, sin sitio, servidor, club ni despliegue.

## T88 y B3 — Ambas entregas a verificar por Claude, Codex CAB 27/10/2026

Objetivo escrito de Carlos: primero dinero, después asistencia. La entrega de dinero controla el efectivo real bajo bloqueo al registrar/editar; parciales y meses anteriores residuales exigen motivo antes de escribir. Registro atómico común, Inicio del dueño y resumen diario24h, sin mensajes individuales. Exceso de plan excluido por DU-34. [Informe](../06-pruebas/PRU-04/IMPLEMENTACION-T88-B3-DINERO.md) y [capturas reales1366/375](../06-pruebas/PRU-04/APROBAR-T88-B3-DINERO.html); aprobación visual pendiente.
Dinero: rojo final18 con14 fallidas/4 aprobadas contra clases originales; área32/143, técnica2/11 y concurrencia real7/115 verdes. La completa inicial1070/6/40 fallidas se conservó y sus40 fallos pasaron luego aislados; ese primer corte no se presentó verde. Asistencia:20 nuevas,15 iniciales11 rojas/4 verdes y cinco adicionales3 rojas/2 verdes; área71/344. Condición en lista, margen interno, Nuevo desde la siguiente a dos clases y DEUDOR con historia con motivo, MOROSO sin justificar. Mismo registro, Inicio y resumen; EXTRA/RECUPERA fuera de B3. [Informe asistencia](../06-pruebas/PRU-04/IMPLEMENTACION-B3-ASISTENCIA.md) · [capturas reales1366/375](../06-pruebas/PRU-04/APROBAR-B3-ASISTENCIA.html).
En la entrega inicial: rápida1011 aprobadas/6 omitidas/7855 aserciones/190,04s y completa1130 aprobadas/6 omitidas/9454 aserciones/962,45s, salida0, corte1136. Seis fuentes propias idénticas durante esas suites. T54 posterior quedó fuera de ese corte. Claude aprobó dinero y devolvió asistencia por primera carga; ver corrección arriba. Se conservan las preguntas sobre avisos de orígenes anulados/corregidos y corte financiero histórico; primera carga ya estaba decidida. Sin servidor, sitio de prueba, base del club ni despliegue.

## T86 — Pago operativo y Reportes Sueldos cerrados; lógica Claude y aspecto Carlos, Codex CAB 27/10/2026

[Entrega y evidencia](../06-pruebas/PRU-04/IMPLEMENTACION-T86.md). Operativas no reservadas, solo ADMIN y sin caja operativa; docentes conservan reserva/Liquidaciones. Migración por FK/rol sin prefijo, excluye docentes y solo cambia reserva; idempotencia y renombrado comprobados. Sin migrar servidor/base del club.
Carlos confirmó «No, incluir operativas en Sueldos es parte de T86». Sueldos muestra pagos netos por persona y total del mes; gasto del club sin reparto por deporte, indicadores docentes conservados. Cambios de rol/inactividad no ocultan pagos históricos con FK conservada. Duda para Carlos: reservas antiguas al cambiar de rol para nuevos pagos.
18 permanentes T86: diez de pago y ocho de reporte, rojo previo conservado. Dirigidas30/133; rápida952 aprobadas/6 omitidas/7558 aserciones; completa 1071 aprobadas/6 omitidas/9157 aserciones/972,62s, salida0. Solo wings_testing_codex. Sintaxis y vistas correctas; única vista autorizada Sueldos, sin CSS/JS de producto.
[Galería real1366/375 y login de calibración](../06-pruebas/PRU-04/APROBAR-T86-SUELDOS.html), Carlos: «Las imagenes estan OK». Lógica aprobada por [Claude](../06-pruebas/PRU-04/VERIFICACION-T86-CLAUDE.md). T86 cerrado,hizo=Codex,verifica=Claude,tiene=nadie. T92 sigue cerrada79340268. Sin push ni despliegue; T85/T93 ajenas fuera.

## T92 — Segunda verificación aprobada, Codex CAB 27/10/2026

[Informe y evidencia, incluida la primera vuelta histórica](../06-pruebas/PRU-04/VERIFICACION-T92-CODEX.md). Segunda vuelta sobre 6a06cf09: solo suma revisiones nuevas, 1→1→2. Ocho casos originales sin cambiar esperados: 8/44 verdes; tres resoluciones ya resueltas: 3/24, sin cambios de fila. Área45/151 (incluye adicionales), T40 11/54; rápida934 aprobadas/6 omitidas/7472 aserciones/220,18 s, salida0. T92 cerrada, verifica=Codex, tiene=nadie. Solo wings_testing_codex, sin servidor ni base del club, push manual o despliegue. T85 ajena excluida. Corte, reintento, aviso, hora e índice único conservados.
T90 conserva el cierre aprobado. Su auditor documental se corrigió para verificar los seis archivos compartidos en el commit concurrente 691b1f83, que ya había incorporado la entrega junto con T85; las ediciones posteriores del tablero no alteran ese corte.

## T90 — Verificación independiente aprobada, Codex CAB 27/10/2026

[Informe y evidencia](../06-pruebas/PRU-04/VERIFICACION-T90-CODEX.md). Implementación Claude563749b2;14 fuentes T90 idénticas tras la publicación paralela de T92. Solo wings_testing_codex local, MariaDB10.4.32 y destino efectivo verificado.
Siete puntos aprobados: HORA/COMISIÓN cerradas o pagadas rechazan cancelar para ambos roles, por formulario/JSON/XHR y serie; filas de seis tablas intactas. Abierta, sin liquidación, FIN-12, reactivación y serie libre conservados. Panel muestra el mensaje recibido; API alternativa deshabilitada, sin escrituras por el PUT de prueba.
Propias77/528, área160/1160 (T904/21 incluida), técnica día231/7, rápida933 aprobadas/6 omitidas/7465 aserciones/204,82s y JS2/2. Fuente técnica copiada sin cambiar bytes y guardia estricta Codex; ninguna salida ajena sobrescrita.
T90 cerrada con verifica=Codex; despliegue no realizado. CLA-012 y CLA-015 siguen en T62. Copia de tabla solo leída; propuesta CLA-013 en el informe. Sin servidor ni base del club, sin producto ni push; sin prometer concurrencia no probada.

## T14 — Días 17–18 recorridos con límites, Codex CyE 26/10/2026

[Informes y galería](../06-pruebas/PRU-04/EVIDENCIA-DIAS-17-18.html): **13 situaciones, 7 Sí, 5 Parcial y 1 No**. TEST/staging/wingstest, ec1b0c6e. Reloj del 26/10 y del 27/10 una vez cada uno, simulación previa una y cuatro rutinas reales una vez cada una; cajas #16 y #17 validadas, contado/esperado $55.700/$53.000, diferencia cero. No despliegue de producto.

Santino cobró agosto/septiembre $56.000 y quedó Moroso $28.000; Botiquín $6.500 registrado; Martín creado en Fútbol al 30% y usuario vinculado. Claves/usuario por controlador como ADMIN tras pedido directo de Carlos; ingreso/menús web comprobados. #76 guardada con Lucas y Tomás presentes, opción EXTRA propuesta por exceso; sin motivo de corrección histórica. Alma $58.000 MP, CUOTA-65 real revisado y Al día. Berenice agosto $43.000 pagado, pendientes septiembre/octubre $86.000, Deudor. #70 reasignada a Martín y lista final 14/15, Felipe ausente; Hernán sin clases propias hoy.

**Límites conservados:** Julieta no se reactivó después de dos confirmaciones nativas fallidas de Chrome; sigue inactiva con cinco cuotas/$143.000, sin cobro ni reactivación por consola. En #70, Codex pulsó primero el Guardar de asistencias y envió todos ausentes por error; luego Martín guardó la lista prevista de 14. No acredita primera lista limpia. Dos pares de capturas de otra pestaña están identificados y excluidos; recapturas 13/14 posteriores válidas.

**Cierre final comprobado en Inicio, Cobranza, Reportes y caja:** deuda $234.000; 104 activos (100 Al día/0 En plazo/2 Morosos/2 Deudores); ingresos octubre $1.981.850,75, egresos $251.150, resultado $1.730.700,75, disponible $3.483.700,75 (EFE $1.271.600, MP $1.946.550,75, Galicia $137.550, Nación $128.000); sin asistencia 0 y por validar $0. Diferencia prevista del guion por Julieta: −$143.000 en ingresos/resultado/MP/disponible y una activa Al día menos. No conformidad integral.

T14 sigue en curso con Claude: revisión independiente, control interno y medición de velocidad. No repetir jornadas, reloj ni rutinas. Último inicio simulado 27/10/2026 15:00. Sin código de producto, tabla externa, suite, migraciones ni push. Antecedentes del intento bloqueado conservados en las carpetas de evidencia.

## T83 — Acceso CyE verificado y cerrado, 26/10/2026

Implementó Claude y verificó Codex sobre 26e7f559: desde VENTAS_CYE, ssh vps hostname devolvió srv1753195.hstgr.cloud, salida 0, alias por 2222. [Evidencia y límites](../04-tecnico/seguridad-vps/2026-10-26-acceso-ssh-cye/README.md). El bloqueo previo de acceso quedó resuelto; las jornadas posteriores se describen arriba.

## T14 — Días 13 a 16 registrados, Codex CAB 26/10/2026

[Informes y evidencia acreditada](../06-pruebas/PRU-04/EVIDENCIA-DIAS-13-16.html): **39 casos, 25 Sí/12 Parcial/2 No**. Cuatro relojes y cuatro rutinas ejecutados una vez cada uno, stdout copiado. Servidor ec1b0c6e/staging/wingstest comprobado al inicio después del aviso de despliegue de Carlos.
Cajas #12/#13/#14 validadas. Final: deuda $391.000; 104 activos (99 Al día/0 En plazo/1 Moroso/4 Deudores); octubre ingresos $1.824.850,75, egresos $240.850, resultado $1.584.000,75; disponible $3.337.000,75, por validar $0 y sin asistencia 1 (#76).
Julieta no pudo reactivarse por confirmación nativa fallida (13.11); no se cobró: una activa y $143.000 menos que el guion. Rocío no ingresó con la clave documentada; Martín no se creó porque Fútbol solo ofreció comisión, no hora: 15.4/15.5 dependen de esa alta y se saltearon. No se repararon diferencias.
Lista #49 y horario #77 guardados sin pedir motivo; Hernán vio el nuevo horario. Adultos creado con planes $32.000/$42.000 y cero alumnos; Botiquín habilitado para OPERATIVO, sin prueba con su sesión el domingo. Gracia sigue 10. Liquidaciones solo abierta, sin generar.
Incidente propio: 40 archivos de evidencia conservaron la pestaña anterior tras recuperarla; identificados y excluidos de las imágenes de la galería. Observaciones directas conservadas y recapturas posteriores rotuladas. No se atribuye evidencia histórica a una recaptura.
T14 continúa en curso; Claude revisa independientemente entrega y diferencias. No repetir operaciones, rutinas ni reloj: último inicio 25/10/2026 15:00. Sin producto, tabla externa, suite, despliegue ni push; dashboard por casos conserva corte anterior. Los cortes 10–12 y anteriores de abajo son históricos, no la siguiente orden.

## T81 — Lógica verificada por Codex CAB, 25/10/2026

**APROBADA en el corte de verificación**; el tablero actual ya declara T81 cerrada y desplegada en test. Verificador independiente: **4 aprobadas/142 aserciones**;
suite rápida en `wings_testing_codex`: **926 aprobadas, 6 omitidas, 7.425 aserciones,
170,53 s, salida 0**. Tres teléfonos probados con ADMIN/OPERATIVO: celular propio,
fallback real al tutor y vacío. Cobranza conserva importes y celdas económicas frente
a la vista del padre de T81. Búsqueda/autocomplete por DNI, ficha, serialización,
recibo PDF real y plantilla Excel comprobados. Accessor nuevo solo consumido por las
dos vistas del pedido; rastreo MCP completado contra fuentes por cobertura insuficiente.
Carlos ya aprobó lo visual. T81 figura ahora `cerrado`, `hizo=Gemini`, `verifica=Codex`; el aviso de despliegue posterior dio inicio al recorrido 13–16.
[Informe y límites](../06-pruebas/PRU-04/VERIFICACION-T81-CODEX.md). Sin producto, tabla,
servidor, datos del club ni push; no suite completa. La salida lateral regenerada de T34
se preservó en la evidencia propia y su archivo original se devolvió a HEAD.

## T79 y T80 — Verificación independiente, Codex CAB, 25/10/2026

T79 aprobada: 52 reportes, 26 cortes y dos evoluciones iguales salvo IDs temporales; Cobranza
idéntica, 6.999 pagos con partes iguales y lote sin datos viejos tras excepción. T80 aprobada
con observación documental: de 21 avisos CSP del 17–21/10, 11 del 19/10 no son de Login;
ninguno en los registros conservados posterior al corte T73 del 21/10 14:04. Política idéntica
de 280 caracteres, ahora bloqueante. Chrome real: 59 pantallas de tres roles, todas HTTP 200,
cero bloqueos/errores; control inline separado bloqueado. 97 casos de parámetros correctos.
Dirigidas 38/271 y completa **1041 aprobadas, 6 omitidas, 8996 aserciones, 1059,07 s, salida 0**,
solo wings_testing_codex y una suite a la vez. T34 devuelto con copia recuperable. Ambas siguen
a_verificar con verifica=Codex; no se corrigió su código ni se desplegó. [Informe y límites](../06-pruebas/PRU-04/VERIFICACION-T79-T80-CODEX.md).

## T77 — Implementada localmente, 25/10/2026, Codex CAB

Decisión de Carlos: un mes sin deuda guardada no se ofrece como pendiente ni suma al total.
Se retiró la cuota corriente en memoria. El selector T18 admite elegir el mes actual, con el
precio/descuento aplicable; el envío como deuda se rechaza bajo bloqueo sin escribir. Servicio de pagos intacto.
Nueve pruebas nuevas; finales del área, contador/CSP y repetición T78: **68 aprobadas/442 aserciones**.
Completa en `wings_testing_codex`: **1034 aprobadas, 6 omitidas, 1 fallo de fixture T78**, después
repetido con sus tres pruebas verdes. Se conserva la salida completa sin llamarla verde.
Chrome real, cobro sintético conciliado y capturas 1366/375. Carlos observó el importe móvil:
se amplió/alineó con Mes (280 px ambos), Agregar debajo a la derecha. Carlos aprobó: «Ahora esta Ok» (25/10).
[Entrega y propuesta COB-028 acción/a/b](../06-pruebas/PRU-04/IMPLEMENTACION-T77.md) · [Galería](../06-pruebas/PRU-04/APROBAR-T77.html).
Gemini registró su [verificación independiente aprobada](../06-pruebas/PRU-04/VERIFICACION-T77-GEMINI.md) el 25/10; T77 sigue a_verificar, sin autocierre por Codex.
Tabla Carlos/Claude intacta. Solo bases descartables propias, sin reparación histórica ni servidor remoto/despliegue.

## T14 — Días 10–12, entrega parcial por bloqueo de Chrome, Codex CAB 25/10/2026

[Día 10](../06-pruebas/PRU-04/DIA-10.md): 18 Sí/4 No. [Día 11](../06-pruebas/PRU-04/DIA-11.md): 23 Sí/4 No. [Día 12](../06-pruebas/PRU-04/DIA-12.md): 20 Sí/3 Parcial/2 No. [Galería y evidencia](../06-pruebas/PRU-04/EVIDENCIA-DIAS-10-12.html). 74 situaciones con esperados congelados antes de operar (incluyen 11.1 negativo y 11.1b/c del patch inicial, que ya no están en la fuente actual). Once recibos descargados/renderizados; 26 listas guardadas y recargadas y 14 clases de septiembre validadas para liquidación sin cargar presencias. No conformidad integral ni cierre de T14.

Cajas #8/#9/#10 cerradas y validadas, todas sin diferencia; cambios retenidos $10.000. Diferencias sin reparar: 10.7 no permitió pagar solo cuota dejando inscripción; anulación de CUOTA-25 no alcanzable como dueño en las pantallas recorridas, por lo que 10.18 no pudo cobrarse; Alumnos incluyó inactiva sin filtro solo activos; Cobranza no mostró teléfonos. Se conserva el desvío neto de $24.400 por el pago anterior de Joaquín y la cuota no cobrada de Tomás. No se atribuye causa de código sin verificarla.

12.7 parcial: se canceló la primera confirmación de adelanto, sin atravesarla para observar un eventual aviso posterior por deuda. 12.24/USU-005 adelantado al día 10 por Carlos; recuperación de clave por consola específicamente autorizada solo para usuario ficticio 8, staging/wingstest, con rol/estado conservados. Ingreso nuevo de Rocío comprobado; guardado por Usuarios y rechazo de la clave anterior perdida no acreditados.

Bloqueo pendiente: 12.22 mostró confirmación de reactivación de Julieta, pero el control de Chrome falló al aceptarla. Última búsqueda siguió Inactivo; pago 12.23 no ejecutado. Sin reparación por base ni fabricación de recibo. La herramienta de control nativo venció sin obtener aprobación de aplicación. Internet corregido a $18.950, fecha 19/10 y una sola fila con historial. Último Inicio leído: deuda $779.600, ingresos $1.378.150,75, egresos $235.550, resultado $1.142.600,75 y disponible $2.827.600,75; Sin asistencia 0 y cajas pendientes 0. Distribución final de estados sin reconsulta, no heredada.

El siguiente paso de aquel corte fue reemplazado por el guion 13–16, ya recorrido arriba; la reactivación se reintentó en 13.11 y siguió sin completarse. No reejecutar los días 10–12 ni su reloj. La entrega conserva revisión independiente pendiente. Tabla de pruebas y producto intactos; sin suite/despliegue por esta entrega documental. Dashboard por casos conserva corte anterior.

## T14 — Días 7 a 9 recorridos, Codex CAB 25/10/2026

[Día 7](../06-pruebas/PRU-04/DIA-07.md): 16 Sí. [Día 8](../06-pruebas/PRU-04/DIA-08.md): 6 Sí/3 No. [Día 9](../06-pruebas/PRU-04/DIA-09.md): 3 Sí/3 No. Las 31 situaciones se recorrieron en Chrome sobre test/wingstest `b816d181`, con esperados literales guardados antes de operar. [Galería y diferencias](../06-pruebas/PRU-04/EVIDENCIA-DIAS-07-09.html). Tabla de pruebas intacta, sin consultas de negocio a base ni reparaciones.

Desvío de ejecución en 8.3: Codex envió CUOTA-25 por $144.000 MP después de que el campo de octubre rechazó el cambio a $4.000 y conservó $48.000. Debían ser $100.000. El caso parcial no queda acreditado; no se atribuye un fallo de cobro parcial al producto. Se conservó el cobro, sin cancelar ni reemplazar: explica deuda $44.000 menor, un Al día más/Moroso menos e ingresos/resultado/MP $44.000 mayores desde ese punto. En 8.5 Helena recibió propuesta EXTRA además de Guadalupe y Berenice; no se reparó ni se decidió la causa. El PDF anulado de Dante rotula al cancelador Administración aunque se operó como Sandra; observación conservada.

Cajas #6 y #7 validadas: Sandra con sobrante físico $500 sin ingreso, Pablo sin diferencia. Clase única #81 creada para 31/10 12–13; listas persistidas y Sin asistencia 32. Julieta inactiva conserva $143.000 en ficha. Precio de Patín Principiantes 2/sem $44.000 guardado: Bianca octubre sigue $40.000, Mora noviembre sugiere $44.000 sin cobrar. Rocío activa, subrubro e ingreso operativo comprobados; sin abrir caja.

Cierre real del domingo: deuda $1.187.000, 100 activos (75 Al día/0 En plazo/9 Morosos/16 Deudores), octubre ingresos $921.000/egresos $86.400/resultado $834.600, aportes $15.000, disponible $2.484.600. Cajas pendientes 0. Último inicio de reloj 18/10 11:00; avanza hasta medianoche. No repetir operaciones ni mover reloj para revisar. T14 sigue en curso y requiere revisión independiente; no hay aprobación integral. Sin suite ni despliegue por esta ejecución humana y entrega documental. El dashboard por casos conserva el corte anterior y no consolida estas jornadas.

## T14 — Días 5 y 6 recorridos, Codex CAB 24/10/2026

Recorrido humano en Chrome sobre test/wingstest, autorizado por Carlos en «Prueba humana III». [Día 5](../06-pruebas/PRU-04/DIA-05.md): 14 situaciones, 12 Sí y 2 Parcial. [Día 6](../06-pruebas/PRU-04/DIA-06.md): 13 situaciones, 10 Sí, 2 Parcial y 1 No. Esperados conservados; capturas y recibos descargados en las carpetas de cada día. Sin reemplazos ni correcciones del producto o de datos fuera del guion.

Cajas #3, #4 y #5 validadas; #3 con faltante físico $500, sin gasto por diferencia. El dueño validó #5 Pablo antes de #4 Sandra. Cierre 15/10 por pantalla: deuda $1.612.000, ingresos de octubre $607.000, egresos $73.700, resultado $533.300 y disponible $2.148.300, sin cajas por validar. Reloj preparado una vez por día, último inicio 15/10 15:00; no moverlo ni repetir cobros para verificar esta entrega.

Diferencias conservadas: alta de clase 17/10 bloqueada en el navegador por mínimo 24/10; Sin asistencia muestra 34, no 32; motivo del cobro anulado visible en PDF pero no en Detalle de caja observado. Precio de Lucas desde noviembre y recepción real del aviso de Zoe no acreditados. T14 sigue en curso: otro agente verifica los informes/evidencia y se continúa con el siguiente guion autorizado. Los recuentos por caso del dashboard del 23/10 de abajo todavía no incorporan estas jornadas. Sin suite ni despliegue por esta operación manual y entrega documental.

## T14 — Dashboard por caso y ambiente real, Codex CyE 23/10/2026

[Dashboard Carlos](../06-pruebas/PRU-04/control-integral/CONTROL-INTEGRAL.html): estado general, números por módulo, línea de tiempo de 60 días, módulos paginados y ficha por caso. Método, esperado, historial, fechas/roles/versiones, indicadores y planes separados de implementación/retest.
331 casos / 20 módulos: **15 parciales en test/wingstest, 316 sin prueba consolidada, 18 vínculos caso–ejecución, cero casos completos OK/Fallo/Reparado**. Tres planes: discrepancia de Cobrar, presentación del alta y cobertura parcial; no tres fallas completas acreditadas. 16 operaciones: 14 coinciden y dos parciales; una operación histórica sin código justificado. Dos consultas del alta se vinculan retrospectivamente a ALU-002.e, sin nueva ejecución.
Los **41 métodos aislados son apoyo**, no aceptación del sitio. Esto reemplaza como recuento de aceptación las cifras mixtas 21/310 y 20/69 de los cortes anteriores. Selección de salida: 15/69 parcialmente probados en test. Días 1–3 por conciliar por caso; futuros sin resultados inventados.
[Entrega IA](../06-pruebas/PRU-04/control-integral/informes-ia/SEGUIMIENTO-CASOS.md). Sin cambios de producto, base, reloj ni nueva ejecución de Wings; sin render/móvil nuevos. T14 sigue en curso; otro agente verifica la entrega, los planes y evidencia. Sin despliegue.

## T14 — Antecedente v3 (recuento mixto, reemplazado arriba), Codex CyE 23/10/2026

[Visor v3](../06-pruebas/PRU-04/control-integral/CONTROL-INTEGRAL.html): Listo/Frena/Falta,
69 casos seleccionados para salida, 20 parcialmente ejercitados y cero firmas independientes
registradas. Selección de trabajo, no sustituto de PRU-05. El tablero conserva 12 requisitos previos
abiertos de Carlos; no se presentan como bugs nuevos reproducidos. Entrada 85 KB frente a 1,65 MB;
catálogo completo y detalle técnico se abren aparte desde el mismo JSON. Codex ejecutó y contrastó:
los 59 registros quedan Pendiente de revisión independiente. Otro chat no equivale a otra cuenta.
Sin nueva suite, datos de negocio ni despliegue; control estático, sin medición de apertura móvil.

## T14 — Antecedente inicial (recuento mixto, reemplazado arriba), Codex CyE 23/10/2026

[Tabla única](../06-pruebas/PRU-04/control-integral/CONTROL-INTEGRAL.html): 16 situaciones del Día 4
(14 coinciden, T2/T6 parciales), una medición y D4-OBS-01; 21/331 casos con evidencia parcial,
85/1.438 letras parcialmente ejercitadas, 310 casos sin evidencia incorporada, cero casos completos.
Faltaba cargar los informes en el registro y ejecutar rechazos activos; omisión de este chat.
El 23/10: **41 métodos dirigidos aprobados, 331 aserciones, salida 0**, local `wings_testing_codex`,
`a15d6095`, 43,623 s. Permisos GET/PATCH, IDs/huellas, validaciones, reflejos y errores de recibos;
sin afirmar CSRF real, otras acciones POST por rol, sesiones, concurrencia ni carga. Límites y
evidencia por método en la tabla. Consolidación/corrida pendientes de revisión independiente.
Sin modificar aplicación, datos de wingstest, reloj ni producción. T14 en curso; seguir Día 5 con
datos conservados y resolver D4-OBS-01. Catálogo Carlos/Claude intacto, solo lectura.

## T14 — Día 4 contrastado técnicamente, Codex CyE 22/10/2026

[Verificación del recorrido 99d1f64a](../06-pruebas/PRU-04/VERIFICACION-DIA-04.md): cinco pagos,
seis imputaciones, siete movimientos y siete reflejos únicos conciliados. Arqueo $119.500, diferencia
$0; disponible $1.926.500 y deuda guardada $1.899.000 coinciden con SQL/servicios/capturas revisadas.
Alta de Mora y listas 28 (10/12), 74 (12/14) persistidas; clase futura 6 sin marcas.
Se mantienen T2/T6 parciales: presentación en ficha y ausencia de prueba de reparto automático.
**D4-OBS-01 abierta:** Julieta AL_DIA sin cuota octubre guardada; Cobrar añade una cuota potencial en
memoria. Sofía tiene inscripción impaga; el adelanto real de noviembre fue de Julieta. Resolver el
tratamiento de cuotas no guardadas, sin fabricar deuda ni repetir pagos para forzar conciliación.
Lectura CLI de Reportes: 29 SQL, 35,83 ms con 101 activos; no acredita carga ni seguridad HTTP.
Sin escrituras de negocio, cambio de reloj/configuración, suite ni despliegue. T14 sigue en curso;
no declara aceptación integral ni cierre de todos los casos. Conservar datos para el Día 5.

## T14 — Día 4 recorrido entregado, Codex CyE 22/10/2026

Carlos autorizó el recorrido en Prueba Manual GW I. [Resultados por tarea e intervención técnica](../06-pruebas/PRU-04/DIA-04.md): 16 registradas, 14 Sí y T2/T6 parciales; Julieta sustituyó a Sofía por decisión de Carlos y él confirmó el adelanto. Caja #2 VALIDADA: contado $119.500, diferencia $0, cambio $10.000, retiro $109.500; ingresos $201.000, egresos $8.500, neto $192.500. Clases 28 y 74 guardadas y recargadas; Sin asistencia 33→32. Matrícula 101, deuda $1.899.000, disponible $1.926.500; octubre ingresos económicos $320.000 y aportes $15.000 separados del resultado.
El bloqueo de T13 se resolvió a pedido de Carlos: solo caché de ingreso de staging, con contador/temporizador vencidos el 22/10 pero futuros para el reloj simulado 13/10. Respaldo privado y limpieza puntual; Sandra y Admin ingresaron, nuevo contador venció con la fecha de la prueba. Se conservó el ajuste de sesión de Claude. Reloj preparado una sola vez; sin cambios de código ni producción. No repetir operaciones. [Evidencia y límites](../06-pruebas/PRU-04/evidencia-dia-04/LEEME.md). Sigue contraste técnico/verificación independiente; T14 permanece en curso, sin aceptación integral del sistema.

## T14 — Relato d34dee7a revisado: guion apto, Codex CyE 22/10/2026

Los ajustes de clase, reportes, aportes y redirecciones fueron contrastados. Avanzadas: 12 alumnas,
Emma y Martina entre ellas; Principiantes del miércoles permite consultar la nómina sin tomar lista.
[Dictamen y controles técnicos, §6](../06-pruebas/PRU-04/REVISION-DIA-04-7cf48ece-CODEX.md).
Codex normalizó seguimiento funcional: planificado, referencias parciales, contador relativo y frontera
19→20/10; aclaró consulta de nómina y preparación del reloj por el equipo técnico. Sin modificar catálogo fuente.
Siguiente al corte de esa revisión: preparación técnica y recorrido autorizado (avance posterior arriba). T14 en curso;
sin ejecución, cambios de reloj/datos/producto ni despliegue en esta revisión. No acredita aceptación del sistema.

## T14 — Revisión del nuevo relato 7cf48ece, Codex CyE 22/10/2026

[Informe único](../06-pruebas/PRU-04/REVISION-DIA-04-7cf48ece-CODEX.md): enfoque humano correcto;
ajustar lista de Avanzadas frente al alta en Principiantes, reporte de matrícula actual frente a altas
mensuales, indumentaria APORTE y navegación real tras guardar/validar. El arqueo propuesto suma $119.500.
Cobertura funcional aún planificada; mapeo de casos, frontera de plazo y controles financieros quedan
en este chat. Solo lectura en prueba y código; no se ejecutó el Día 4 ni se cambió reloj/datos/producto.
T14 en curso; el relato requiere esos ajustes antes del inicio humano.

## T14 — Roles aclarados y reinicio del relato del Día 4, Codex CyE 22/10/2026

**Última precisión de Carlos:** dos meses ficticios, día a día, primero camino feliz y después casos
raros graduales. Día 4 sin errores deliberados forzados; preparar la siguiente jornada tras analizar
la anterior. El 2/11 es un hito, no el límite del ensayo. Los roles siguientes conservan su alcance.
La actividad normal y repetida debe llenar la base y dar historia a los reportes; no cada operación
requiere un caso nuevo ni un error. Las excepciones se incorporan sin dominar las jornadas.

Carlos asignó: Gemini escribe jornadas orientadas a objetivos, errores humanos y controles por pantalla;
Codex Prueba humana opera e informa; Casos prueba humana I prepara y contrasta los controles técnicos.
[Instrucciones actualizadas](../06-pruebas/PRU-04/INSTRUCCIONES-PRUEBA-GEMINI.md).
El Día 4 anterior y su orden de ejecución quedan como antecedentes pendientes de reescritura.
Las observaciones técnicas de la revisión siguiente las retoma este chat, sin encargárselas a Gemini.
Se conserva la historia de los días 1 a 3 y se acumulan datos para evaluar rendimiento.
T14 en curso: entrega documental; sin ejecución, cambios de producto, reloj/datos ni despliegue.

## T14 — Día 4 devuelto tras revisar a86c3509, Codex CyE 22/10/2026

[Informe de revisión](../06-pruebas/PRU-04/REVISION-DIA-04-CODEX-2026-10-22.md). Adelanto y rutas principales corregidos; siguen contradicciones en base inicial, catálogo y dinero, plazo del alta, comisión por hora/comisión, referencias al catálogo y servicios. Consultas de solo lectura confirmaron Alquileres EGRESO, Indumentaria institucional APORTE, Lucía HORA y plazo 7/gracia 10. No se ejecutó la jornada ni se modificó producto, reloj o datos de negocio. Este informe es antecedente técnico; la nueva distribución de responsabilidades figura arriba. T14 continúa en curso.

## T14 — Control integral preparado, Codex CyE 21/10/2026

[Tabla](../06-pruebas/PRU-04/control-integral/CONTROL-INTEGRAL.html): 39 recorridos, 14 controles transversales y referencias de solo lectura a los 331 casos existentes. Resultado actual de todos: pendiente; cero ejecuciones. Incluye seguridad, validaciones, datos, aspecto y performance como dimensiones separadas. [Alcance y fuentes comprobadas](../06-pruebas/PRU-04/control-integral/RELEVAMIENTO.md). Relevadas 18 pantallas de entrada/listado con ADMIN; sin operar formularios, otros roles ni móvil. Sitio de prueba leído por SSH en `56379295`, reloj real 21/10; no se modificó. La tabla espera revisión independiente y después alimenta los cuentos de Gemini; T14 continúa en curso, sin declarar aptitud para producción.

## T50 — CERRADA, verificación final Codex CyE 21/10/2026

`738de598` resuelve la guardia efectiva/DB_URL. Arnés original: 3 rechazos por seguridad, salida 1 en cada auxiliar, sin llegar a preparación; arnés salida 0. Control permitido supera la guardia; todos los destinos CLI forzados a puerto cerrado, sin escrituras. Base propia: 13/71 permanentes y 17/214 independientes verdes; rápida **908 aprobadas, 6 omitidas, 7286 aserciones, 158,31 s, salida 0**, corte 1029. H1 conserva la decisión de Carlos; H2/H3 e idioma confirmados. Gemini implementó; Codex verifica y cierra por tablero. [Informe final](../06-pruebas/PRU-04/VERIFICACION-T50-FINAL-CODEX.md). Sin cambios de producto por Codex ni despliegue.

## T50 — Antecedente: tercera verificación, Codex CyE 21/10/2026

Revisado `d274cfd4`: idioma corregido, 13/71 regresiones permanentes y 17/214 controles independientes verdes en `wings_testing_codex`. H1 conserva la decisión de Carlos; H2/H3 siguen aprobados. La guardia CLI rechaza production y nombres de base no autorizados sin URL, pero **acepta un nombre permitido en DB_DATABASE aunque DB_URL resuelva otra base**. Reproducido sobre el auxiliar original con puerto cerrado 127.0.0.1:1, sin escrituras: supera la guardia y llega al intento de migración contra la base no autorizada. T50 devuelta a Gemini solo por validar la conexión efectiva antes de escribir. Corte permanente 1029; suite rápida y detalles en el [informe de tercera verificación](../06-pruebas/PRU-04/VERIFICACION-T50-TERCERA-CODEX.md). Sin cambios de producto, tabla ni despliegue.

## T50 — Antecedente: segunda verificación, Codex CAB 21/10/2026

H2 y H3 de Gemini `de35ae0` confirmados: fecha mal formada al editar devuelve 422 en castellano sin modificar registros; límite y fecha por defecto vienen del reloj Wings, y el aviso específico sobrevive a la consulta asíncrona. H1 no se reabre: Carlos decidió «Inscripción a partir de hoy»; inscripción $5.000 y cuota futura computable $0 comprobados en las tres pantallas. Completa propia aislada: **1019 aprobadas, 6 omitidas, 8802 aserciones, 981,62 s, salida 0**, corte 1025; main conserva contador 1028 tras integrar T61.

Se devuelve a Gemini únicamente por observaciones complementarias: previsualizaciones de cuota/inscripción con fecha mal formada todavía responden en inglés, y el auxiliar de preparación de Gemini no comprueba base/entorno antes de migrar/escribir. Las 12 regresiones pasan (55 aserciones); los 16 controles con H1 vigente pasan y una matriz adicional falla solo por idioma (17 controles, 214 aserciones, 20 respuestas 422 sin cambios en tablas). Navegador real con ambos relojes y recuperación de validez comprobados. [Informe completo](../06-pruebas/PRU-04/VERIFICACION-T50-SEGUNDA-CODEX.md). Sin cambios de producto, tabla ni despliegue; no pedir otra decisión sobre inscripción.


## T61 cerrada — Claude CAB 21/10/2026

Segunda vuelta de Codex verificada (TRASPASO solo en las transferencias del sistema; caja, Cashflow, reflejos, pagos y anulaciones lo rechazan) y visual aprobado por Carlos. Rama `codex/t61-segunda-vuelta` integrada a `main` junto con la segunda vuelta de T50 de Gemini; contador 1028. [Verificación](../06-pruebas/PRU-04/VERIFICACION-T61-CLAUDE.md). Sin despliegue.

## T61 — Segunda vuelta H1–H3, Codex CAB 21/10/2026

Implementada en copia aislada `codex/t61-segunda-vuelta` (base 2c7cebe), con cambios T50 ajenos preservados. TRASPASO solo para transferencias reservadas: catálogo común, caja, correcciones y otras puertas protegidas; transferencia aprobada intacta. 16/198 dirigidas y 125/851 del área verdes; completa 1020 aprobadas/6 omitidas, 8846 aserciones, 934,96 s, salida 0. Inventarios propios previo y posterior sin anomalías. Capturas reales antes/después guardadas: escritorio 1366 y móviles recapturados a 375 × 1000, con medición DOM y cabeceras JPEG comprobadas. Pendiente revisión de Claude/Carlos; no se da el visual por aprobado. [Entrega](../06-pruebas/PRU-04/IMPLEMENTACION-T61.md#segunda-vuelta--h1-a-h3). Sin reparación histórica ni despliegue.

Antecedente: verificación de Claude CAB del 20/10/2026, devuelta por los casos siguientes.

La transferencia única está bien (un envío, dos patas, sin duplicar, resultado del club igual, saldos correctos). Se devuelve por H1–H3: la clasificación TRASPASO sigue ofrecida en cualquier subrubro, y con ella la caja de la operativa y la corrección de Cashflow cargan gastos de una sola pata que no cuentan en el resultado (reproducido: $50.000 en caja y $40.000 corregidos desaparecen de los egresos). [Informe](../06-pruebas/PRU-04/VERIFICACION-T61-CLAUDE.md). Sin despliegue.

T43, revisión de Carlos 20/10: decisiones parciales JSON, cabecera separada y pie Total confirmado implementados localmente; 18/134 dirigidas finales verdes; completa 1017 aprobadas, 6 omitidas, 8791 aserciones, 844,65 s, salida 0. Escritorio mantiene posición y texto sin guardar; cabecera 375 capturada. Última interacción móvil bloqueada por acceso al navegador deshabilitado en Work. Carlos aprobó la corrección: «Ahora esta OK». Publicada en main: b8e1ac1e, remoto comprobado. Pendiente revisión independiente de lógica por Claude. Ver IMPLEMENTACION-T43.md.

## T43 — Implementación local, Codex CyE 20/10/2026

DU-36: origen persistente por parte y revisión del dueño sobre atrasadas. Validar/rechazar con auditoría; cierre/pago bloqueados hasta resolver. Cálculo original conservado; pago y recibo según confirmado, salvo monto final absoluto A23 conservado y separado del subtotal incluido. Históricos sin inventar origen, cerradas/pagadas intactas. Recálculo conserva decisiones solo de partes económicamente iguales.

15 regresiones permanentes; dirigidas previas 39/225 verdes y rápida 895 aprobadas/6 omitidas, 7147 aserciones, 186,82 s, salida 0. Completa **1012 aprobadas/6 omitidas, 8743 aserciones, 740,41 s, salida 0**, wings_testing_codex, corte 1018. Capturas reales 1366/375 y PDF generado: agosto validado y septiembre incluidos, julio rechazado fuera, $60.000. Carlos pidió corregir la ubicación de Validar: corregida a la derecha, Rechazar a la izquierda y posición estable; capturas renovadas 1366/375. Carlos aprobó la corrección tras probar el ejemplo local: «Ahora esta OK»; entrega a revisión de lógica por Claude, sin despliegue. [Entrega](../06-pruebas/PRU-04/IMPLEMENTACION-T43.md) · [Galería](../06-pruebas/PRU-04/APROBAR-T43.html). Lógica para Claude.

## T42 — Implementada, a verificar por Claude, Codex CyE 20/10/2026

DU-19: comisión por parte en max(mes cobro, mes pagado), con asistencia en mes liquidado; ingreso adelantado en Reportes/Inicio por mes pagado. Caja/Cashflow conservan fecha e importes del dinero, disponible y resultado global. Carlos resolvió conservar el mes original solo cuando no pueda comprobarse el pagado. Desglose completo e historia analítica sin reescribir estados previos; cerradas/pagadas intactas. COB-012 e/h actualizado en repo y datos vivos (relectura versión 10). Copia LIQ-002 rectificada; control del dueño sigue en T43.

19 regresiones nuevas. Dirigidas 29/150 verdes; completa **997 aprobadas, 6 omitidas, 8652 aserciones, 1242,09 s, salida 0**, wings_testing_codex, corte 1003. Sintaxis/vistas correctas, sin cambios de vistas/CSS ni despliegue. [Entrega y evidencia](../06-pruebas/PRU-04/IMPLEMENTACION-T42.md). Sigue T43.

## T50 — Segunda vuelta: H2 y H3 corregidos; decisión de Carlos sobre H1, Gemini CAB 21/10/2026

Corrección de hallazgos de la verificación independiente de Codex (commit `3704063`):
- **H1 (Inscripción en alta futura):** Decisión expresa de Carlos del 21/10: «Inscripción a partir de hoy». La inscripción se debe desde el alta, como funciona actualmente; la cuota del mes que aún no empezó no cuenta. No se modifica la lógica de inscripción. Claude actualizó ALU-007 en la tabla.
- **H2 (Fecha mal formada al editar):** Corregido en `AlumnoWebController::validationRules` y métodos de vista previa (`cuotaAltaPreview`, `inscripcionPreview`): se blindó el análisis de `fecha_alta` con bloque `try/catch` y regla `'bail'` previa a la regla `'date'`, impidiendo que un string no parseable alcance `Carbon::parse` antes de validar. Responde 422 con mensaje descriptivo en castellano (`La fecha de alta no es una fecha válida.`) sin escrituras en base de datos.
- **H3 (Reloj Wings en cliente y previsualización):** En `resources/views/alumnos/_form.blade.php`, se envía `data-max` calculado por el servidor con `FechaIngresoRegla::limiteMaximo()->format('Y-m-d')` y la fecha por defecto usa el reloj de Wings (`now()->format('Y-m-d')`). En `resources/js/alumnos-inscripcion.js`, la previsualización conserva el mensaje de error específico de fecha fuera de límite devuelto por el backend (`datos.errors.fecha_alta[0]`) sin sobreescribirlo por el error genérico de conexión. Compilado con Vite.
- **Pruebas:** 12 pruebas en `tests/Feature/FechaIngresoFuturaT50Test.php` (agrega `test_h2_edicion_con_fecha_mal_formada_devuelve_422_sin_escrituras` y `test_h3_formulario_alta_y_edicion_traen_data_max_y_fecha_defecto_wings`, 55 aserciones, verdes en `wings_testing_gemini`). Controles de Codex pasan 15 de 16 (H1 se mantiene según decisión de Carlos). [Informe de implementación](../06-pruebas/PRU-04/IMPLEMENTACION-T50.md). Antecedente de entrega a Codex: segunda verificación H2/H3 confirmada; observaciones complementarias arriba. Sin despliegue.

## T50 — Antecedente de primera verificación, Codex CAB 21/10/2026

Verificación independiente de 3704063: H1, inscripción futura deja EN_PLAZO y suma en Cobrar/Inicio antes del ingreso; H2, fecha mal formada al editar da 500; H3, Chrome calcula `max` con fecha de computadora y difiere del reloj simulado Wings. Sin correcciones de producto ni cambios en tabla de pruebas. Completa propia 1017 aprobadas/6 omitidas, 8791 aserciones, 984,61 s, salida 0; controles propios 16/115 con dos fallas, desfase comprobado en Chrome local. [Informe de verificación](../06-pruebas/PRU-04/VERIFICACION-T50-CODEX.md). Devuelta a Gemini; sin despliegue. Implementación histórica debajo, sin convertirla en aprobación.

DU-06 (caso ALU-007): «Aceptar solo hasta el mes siguiente». La fecha de ingreso (`fecha_alta`) puede ser pasada, de hoy o futura solo hasta el último día del mes siguiente a hoy (reloj Wings / `Carbon::now()`, respetando reloj simulado). Ejemplo: al 20/10/2026 el límite es 30/11/2026; en diciembre de cualquier año es el 31 de enero. Fechas posteriores se rechazan con mensaje en castellano: *«La fecha de ingreso no puede ser posterior al dd/mm/aaaa.»*.

1. **Implementación:** `App\Support\FechaIngresoRegla` (soporte compartido con `limiteMaximo`, `esValida` y `mensajeError`). `AlumnoWebController::store` y `update` (en edición solo valida si se modifica la fecha, no bloquea alumnos históricos), previsualizaciones de cuota e inscripción (`cuotaAltaPreview`, `inscripcionPreview` y `PagoCuotaService::previsualizarCuotaAlta`) retornan 422. `PrimeraCargaExcelService::revisar` marca celda en columna E con error impidiendo carga. `resources/js/alumnos-form.js` fija `max` en el input y validación nativa en cliente (sin JavaScript en Blade, AGENTS.md §5-bis).
2. **Diseño visual:** **Sin tocar Blade** (AGENTS.md §1). El mensaje se muestra con la estructura existente de errores (`@error('fecha_alta')`). Capturas generadas en `docs/06-pruebas/PRU-04/evidencia-t50/` (1366px y marco de 375px) y presentadas en `APROBAR-T50.html`.
3. **Pruebas de implementación (20/10):** Gemini informó 10 pruebas nuevas en `tests/Feature/FechaIngresoFuturaT50Test.php` (44 aserciones, verdes en su base). Entrega inicial a revisión, reemplazada por la devolución independiente de arriba. [Informe de implementación](../06-pruebas/PRU-04/IMPLEMENTACION-T50.md) · [Evidencia visual histórica](../06-pruebas/PRU-04/APROBAR-T50.html). Sin despliegue.

## T61 — Corrección de funcionamiento solicitada por Carlos, 20/10/2026

Carlos aclaró que necesita un formulario de transferencia con cuenta de salida, cuenta de destino e importe, y que sus rubros y subrubros son exclusivos del sistema, sin edición ni cambio de nombre. Esto reemplaza el diseño anterior de dos cargas independientes. Codex implementó ambos movimientos vinculados en una sola transacción, protección del catálogo y nuevas capturas. La galería anterior no representa la transferencia solicitada. Formulario corregido y Rubros con botones 96 × 32: Carlos aprobó «Apruebo el visual corregido» el 20/10. Dirigidas 22/287 verdes; completa 976 aprobadas/6 omitidas, 8527 aserciones, 804,11 s, salida 0. Seguimiento FIN-09: fecha de un mes anterior pide confirmar antes de escribir y avisa al dueño tras guardar. Carlos aprobó «Apruebo el aviso». Completa del seguimiento 977/6 omitidas, 8541 aserciones, 697,65 s, salida 0 (983 pruebas). T61 a verificar lógica por Claude. Sin despliegue.

### Antecedente del recorrido anterior

Implementada por Codex e integrada. Recorrido real terminado en base ficticia separada `wings_testing_codex_t61`: 22 capturas y medidas de Chrome a 1366/375, incluidos Crear/Editar subrubros de ingreso/egreso, Inicio, Cashflow, Reportes y operativa. Efectivo $306.000, Banco $307.000; resultado del club $7.000 y disponible $613.000. Operativa sin los conceptos ADMIN y acceso directo a Cashflow denegado. Once fuentes T61 iguales a integración; no se modificó aplicación ni diseño para capturar.

Se conserva la suite propia de integración: 952 aprobadas/4 omitidas, 8455 aserciones, 1102,57 s, salida 0, corte 956; no certifica los cambios paralelos posteriores. Mi recorrido queda entregado. **Pendientes aprobación visual de Carlos y verificación de lógica por Claude**; T61 sigue a_verificar, sin autocierre ni despliegue. [Control](../06-pruebas/PRU-04/VERIFICACION-T61-CYE.md) · [Galería](../06-pruebas/PRU-04/RECORRIDO-T61.html).
## T52 — Verificada y cerrada, Codex CyE 20/10/2026

Revisión propia de e1e7c499: el scope exige siempre haber ingresado y haber llegado al grupo si hubo cambio. H5 repetido por PUT/GET/POST web: ingreso 01/11, cambio 20/10; no aparece el 20/10, POST 422 y sin marca. H1–H4, reactivación por tres roles, plan diferido, cerradas/pagadas HORA/COMISIÓN y consultas 12→12 conservados.

9 independientes + 12 regresiones: **21 aprobadas, 139 aserciones, 19,79 s, salida 0**, wings_testing_codex. Completa compartida T42/T52: 997/6 omitidas, 8652 aserciones, 1242,09 s, salida 0. Visual previo de Carlos conservado («Las vistas están OK»). Migración histórica conservadora declarada, sin inventar fechas reales; no se desplegó ni migró el club. [Informe y antecedentes](../06-pruebas/PRU-04/VERIFICACION-T52-CODEX.md).

## T73 — Sin JavaScript dentro de las vistas, Claude CyE 19/10/2026

Implementada localmente, **a verificar en navegador** (orden de Carlos 13/10, AGENTS §5-bis). Salen los 8 bloques `<script>` y los 8 `onsubmit=confirm` que quedaban: el ingreso usa `.btn-toggle-password` de `ds-app.js`; grupos y profesores, `data-enviar-al-cambiar`; caja/editar, clases/show, clases/index, liquidaciones/create y alumnos/index, su módulo en `resources/js/`; las confirmaciones de borrado, `form[data-confirmar-envio]` resuelto en `ds-app.js` antes del anti doble-submit. `CspSinCodigoIncrustadoTest` en cero. Sin cambios de aspecto. [Entrega](../06-pruebas/PRU-04/IMPLEMENTACION-T73.md).

## T40 — Las cuotas del día 1 se reintentan solas y avisan, CERRADA 19/10/2026 (implementó Claude CyE, verificó lógica Gemini CyE)

CERRADA (DU-52, caso CBZ-020). `cobranza:generar-deudas --si-falta` corre todos los días a las 6:00; el día 1 genera y los días siguientes solo si el mes no figura generado y el anterior sí. Constancia en la tabla nueva `generaciones_cuotas` (migración que anota el mes en curso al instalarse). Avisos al dueño por `AvisoAdminService`: generación con atraso, intento fallido y línea en el resumen diario. Regla en el [contrato de cobranza §2](../02-contratos/Wings-contrato-estadosAlum-cobranza-asistencia-V1.md). Verificación de lógica por Gemini (CyE) 19/10: 9 pruebas dirigidas en verde (39 aserciones, 8.92 s) y `GenerarDeudasMensualesCommandTest` (6 aserciones, 10.09 s) en `wings_testing_gemini`, salida 0. [Informe de verificación Gemini](../06-pruebas/PRU-04/VERIFICACION-T40-GEMINI.md). Sin cambios visuales. Tarea cerrada. Sin despliegue: requiere migrar en el servidor.

## Verificación T56/T68 — Claude CAB 19/10/2026

T56 cerrada: la lógica de Gemini (plan obligatorio al cambiar de grupo; el cobro no reactiva a un inactivo sin plan del mes) conserva su verificación histórica. La aprobación anterior de quitar secciones no vale para la versión con datos reales de T68 (DU-57 aclarada el 19/10). La verificación de Codex detectó el importe calculado en lugar del final ajustado y un onsubmit anterior, que queda para T73. **H1: corrección local y regresión permanente, Codex CyE 19/10**; antes 1 fallida/11 aserciones y después 16 dirigidas/108 verdes. Suite completa: 919 aprobadas/4 omitidas/2 fallidas; Carlos autorizó H1 y su publicación directamente; implementación en `02b72588`. Claude revisa la lógica y Carlos el visual con APROBAR-T68.html. Los cambios visuales paralelos de la carpeta compartida se preservan y no se certifican en H1. [Corrección y evidencia](../06-pruebas/PRU-04/VERIFICACION-T68-CODEX.md#corrección-de-h1). El incidente del contador de 0414b68 fue corregido en d7b0a5c. [Informe histórico T56/T68](../06-pruebas/PRU-04/VERIFICACION-T56-T68.md).

## T59 y T47 — Dueño a Cashflow; avisos en castellano, CERRADAS 19/10/2026 (implementó Claude, verificó visual Carlos, verificó lógica Gemini)

T59 (DU-24, MOV-006): el dueño que entra a Nuevo movimiento de caja va directo a Cashflow; si envía el formulario de caja no se registra nada. T47 (DU-02, ACC-014): abrir a mano la dirección de una acción muestra «Esta dirección no se abre a mano» (405) en lugar de «Página no disponible»; un registro inexistente avisa en castellano y un fallo de base no muestra la consulta (`App\Support\AvisoError`, 29 `catch` de 6 controladores). 10 pruebas nuevas, Chrome 1366/375. Visual aprobado por Carlos el 19/10 («T47 visual aprobado»). Verificación de lógica por Gemini (CyE) 19/10: 23 pruebas dirigidas en verde (431 aserciones, 16.53 s); suite completa en `wings_testing_gemini`: 921 aprobadas, 4 omitidas, 8179 aserciones, 1118.68 s, salida 0. [Informe de verificación Gemini](../06-pruebas/PRU-04/VERIFICACION-T59-T47-GEMINI.md). Ambas tareas cerradas. Sin despliegue.

## T66 — Profesora obligatoria, CERRADA 19/10/2026 (implementó Codex, verificó visual Carlos, verificó lógica Gemini)

Alta única, serie, edición hoy/futuro y reasignación exigen al menos una profesora activa del deporte, sin escrituras parciales. Antes de implementar se detectó la contradicción entre la excepción antigua de reasignación pagada y FIN-12. **Carlos resolvió el 19/10: «Bloquear clases en liquidaciones pagadas. En caso de que la liquidacion este cerrada, Hay que cancelar la liquidacion antes de modificar».** Se retiró la excepción del servidor y se actualizó el contrato: cancelada no admite reasignación; cerrada pendiente exige cancelación previa; pagada intocable. Motivos, roles y choques conservados. Lectura real de base descartable y sitio de prueba: cero clases sin profesora en ambos; sin reparar datos. 16 pruebas nuevas; suite completa 920 aprobadas, 4 omitidas, 8167 aserciones en `wings_testing_gemini`, salida 0, corte 924. [Entrega Codex](../06-pruebas/PRU-04/IMPLEMENTACION-T66.md) · [Verificación de lógica Gemini](../06-pruebas/PRU-04/VERIFICACION-T66-GEMINI.md). Visual aprobado por Carlos el 19/10: «Vista aprobadas». Tarea cerrada. Sin despliegue. T46 permanece separada.

## T74 — Inicio de la operativa: vencidos y en plazo, Claude CAB 19/10/2026

DU-60 (CBZ-022): Inicio separa Vencidos y En plazo; sumados coinciden con Cobrar. **CERRADA 19/10:** lógica de Claude verificada por Codex contra código, HTTP, bordes de gracia, inscripción, pago parcial, adelanto, inactivas, enlaces y permisos. Consultas 24 con 1 y 12 alumnos. Dirigidas 32/251 y suite completa 935 aprobadas/4 omitidas, 8243 aserciones, 1283,46 s, salida 0 en wings_testing_codex. Codex corrigió alineación y medidas de botones con las clases existentes; Carlos: «Apruebo el visual T74 actualizado». Capturas reales 1366/375 legibles. [Implementación](../06-pruebas/PRU-04/IMPLEMENTACION-T74.md) · [Verificación y evidencia](../06-pruebas/PRU-04/VERIFICACION-T74-CODEX.md). Sin cambios de Cobranza ni despliegue.

## Corrección T34 C1 y C2 — Gemini CyE, 19/10/2026

**CERRADA: verificación APROBADA por Codex CyE sobre c017bac3.** C1: 20 consultas con 2 y 16 tarjetas, un COUNT por petición y valor fresco tras PATCH. C2: dos rollbacks reales después de UPDATE (HORA/COMISIÓN), filas intactas; período y generación exitosa correctos. H1/H2 se conservan. Dirigidas 27/321 verdes; rápida 820 aprobadas, 4 omitidas, 6657 aserciones, 404.38 s, salida 0. Completa ya ejecutada con esta corrección: 935 aprobadas/4 omitidas, 8243 aserciones, 1283,46 s. [Reverificación actual](../06-pruebas/PRU-04/VERIFICACION-T34-C1-C2-CODEX.md). T67 cerrada; sin cambios de producto ni despliegue.
- C1: Memoización de `badgeClasesPendientes` en `$request->attributes` en `AppServiceProvider`. Verificado por Codex: 20 consultas con 2 y 16 tarjetas, un solo COUNT en cada petición; secuencia web 1→0→1 al validar/desvalidar, sin valor viejo.
- C2: `LiquidacionWebController::store` envuelve validación y generación en `DB::transaction(...)`. Cualquier fallo o duplicado produce rollback sin dejar clases validadas. `incluir_hoy` restringido a clases cuyo mes y año coincidan con el período solicitado y valida `!$clase->integraLiquidacionCerrada()`.
- Pruebas: 5 regresiones permanentes en `ClaseSinAsistenciaYDniT34T67Test.php` (11 tests, 78 aserciones, 10.68 s, verdes). Suite completa en `wings_testing_gemini`: 934 aprobadas, 4 omitidas, 0 fallidas de código (8239 aserciones, 1498.66 s). Suite rápida: 820 aprobadas, 4 omitidas (291.24 s). Contador a 939 pruebas. Sin cambios visuales. [Informe de entrega](../06-pruebas/PRU-04/CORRECCION-T34-C1-C2-GEMINI.md). Sin despliegue.

## Verificación previa T34 — Codex CyE, 19/10/2026

**CON HALLAZGOS en c/f.** H1 corregido: tarjeta distingue Pendiente, Sin presentes y Cargada, coherente con ficha. H2 corregido en el PATCH: CERRADA pendiente/pagada, HORA/COMISIÓN, rechaza JSON/formulario y conserva filas de clase, liquidación y detalle. Visual aprobado por Carlos en fb88bd3c; **T67 permanece cerrada**. [Informe y evidencia](../06-pruebas/PRU-04/VERIFICACION-T34-T67-CODEX.md#verificación-de-la-corrección).

## T44 — Vigencia de precios a verificar, Codex CAB 18/10/2026

**Corrección visual 19/10:** Carlos rechazó Nuevo junto al título. Separado en ambos formularios y todos los anchos; regla explícita en Design Rules y skill. [Capturas nuevas](../06-pruebas/PRU-04/evidencia-t44-titulo/GALERIA.html); Carlos aprobó ambas pantallas el 19/10: «Ok, pasan» (verifica visual=Carlos). Suite de corrección: 882 aprobadas, 3 omitidas, 7821 aserciones, 1134.95 s, sin fallas; corte 885.

Historial y tarifa común incorporados: 19 casos nuevos; selección de precios/formato 24/251; regresión 89/455 aprobada.
Carlos permite varios aumentos al mes: existentes toman el último al mes siguiente; altas usan su día.
Cambios de frecuencia: baja siempre al mes siguiente; subida ahora con cuota pendiente o parcial;
si ya está saldada, al mes siguiente. El plan programado no reemplaza al vigente antes de su fecha.
La tarifa del catálogo no recalcula cuotas generadas; el cambio inmediato de plan es la excepción explícita.
Migración con planes existentes probada 1/8. [Entrega y límites](../06-pruebas/PRU-04/IMPLEMENTACION-T44.md).
Suite final: 878 aprobadas, 3 omitidas, 7792 aserciones, 1102.93 s; 22 fuentes propias idénticas. Entregado a Claude para reglas/código; aspecto aprobado por Carlos el 19/10 («Ok, pasan»), sin otra revisión visual de IA. Sin despliegue.

## T58 — El dueño crea desde la ficha la cuota de un mes pasado, Claude 18/10/2026

DU-23. En la ficha, solo para el dueño, «Meses sin cuota generada»: los meses desde el alta sin
cuota y con plan, al precio del plan de ese mes, con «Crear» y confirmación. La operativa recibe
403. 8 pruebas; suite 858 aprobadas. A verificar por Gemini; el aspecto lo aprueba Carlos.
[Informe](../06-pruebas/PRU-04/IMPLEMENTACION-T58.md). T63 y T57 verificadas y cerradas.

## T39 — La anulación de un cobro del dueño va con la fecha del original, Claude 18/10/2026

DU-21 y decisión de Carlos del 18/10: el asiento en contra lleva la fecha del cobro y la deuda
(cuota, inscripción y recargo) vuelve en ese mismo mes, que queda como si el cobro no hubiera
existido. Cambian `PagoCuotaService::anularCobroAdmin` y el cálculo de recargos de Reportes;
dos pruebas viejas que controlaban la regla anterior se actualizaron. 5 pruebas nuevas; suite
856 aprobadas. A verificar por Gemini. [Informe](../06-pruebas/PRU-04/IMPLEMENTACION-T39.md).

## T63 y T57 — No desactivar en uso y medio de pago del cajón, entrega local 18/10/2026 (Gemini CAB)

- **T63 (DU-41):** No se desactiva un deporte ni un grupo que tenga alumnos activos o clases futuras (no canceladas, desde hoy en adelante). Respuesta JSON con 422 y redirección web con mensaje detallado que indica cantidad exacta de alumnos activos y clases futuras a resolver. Reactivación sin freno. 10 pruebas nuevas en `tests/Feature/DesactivarDeporteGrupoEnUsoTest.php`.
- **T57 (DU-16, DU-59):** El medio de pago asignado al cajón de mostrador no se puede desactivar ni por toggle ni por update. El dueño puede cambiar el medio de pago del cajón en cualquier momento, pero **únicamente si no hay ninguna caja ABIERTA**. Si hay caja abierta, interfaz y backend (`DomainException`) bloquean el cambio y exigen cerrarla primero. Se guarda autor y timestamp del cambio (`configurado_por_id`, `configurado_at`). 9 pruebas nuevas en `tests/Feature/MedioPagoCajonTest.php`.
- **Diseño / Capturas:** Vista `caja/configuracion` actualizada respetando tokens, sin JavaScript incrustado y con botones de un solo verbo (`Volver`, `Guardar`). Capturas generadas con Chrome headless en escritorio y marco 375 (`docs/06-pruebas/PRU-04/evidencia-t57/`).
- **Suite:** 844 aprobadas, 2 omitidas, 0 fallidas, 7655 aserciones en `wings_testing_gemini`.
- [Informe de implementación](../06-pruebas/PRU-04/IMPLEMENTACION-T63-T57.md). Sin despliegue ni autocierre (pasa a verificación por Claude).

## T75 — Consistencia móvil aplicada, entrega local 18/10/2026

**Revisión posterior de Carlos, 18/10:** observó cinco vistas. Se corrigieron el padding duplicado
al eliminar, el ancho móvil de fechas/horas, la tipografía y jerarquía de Liquidación, las variantes
inexistentes de los accesos de Inicio y la elección Activo/Inactivo de Revisión con nota y Confirmar.
99 casos nuevos a 360/375/1366 sin desbordes; interruptor y campos comprobados en navegador.
[Entrega y galería de solo las cinco observaciones](../06-pruebas/PRU-04/IMPLEMENTACION-T75-OBSERVACIONES.md).
Carlos eligió «Jerarquía fija y consistente»: total 1.1rem/800, resumen 0.9rem/700 y fila 0.8rem/600.
Aplicado a ocho vistas de Caja/Cashflow, Cobranza, Inicio administrativo, Liquidación y Reportes.
126 controles de jerarquía/espacio con montos largos positivos/negativos, sin desbordes.
[Seguimiento y galería vigente de cinco vistas](../06-pruebas/PRU-04/IMPLEMENTACION-T75-JERARQUIA.md).
Carlos aprobó las cinco vistas: «Esas opciones estan muy bien aprobadas» (verifica visual=Carlos).
Solo queda el control de la interacción de Revisión por Claude. T75 no está cerrada. Suite: 858 aprobadas/3 omitidas, 7704 aserciones; incluye una prueba temporal de T39.
PHP final idéntico al de la suite; solo CSS móvil de Cashflow posterior, comprobado nuevamente en navegador.
La interacción de Revisión lleva JavaScript externo; el alcance sin JavaScript de abajo es histórico.

**Entrega anterior:**

Carlos pidió aplicar tamaño Cobrar, padding común, mismo borde derecho y posiciones estables.
CSS hasta 640 px y clases en once vistas: botones con texto 96 × 32, padding 0, gap 8;
contenido 16 y referencia interior 24. Incluye ingreso, modales y acciones de tabla.
101 fuentes revisadas, 99 casos antes/después a 360/375/1366 y dos complementos;
1.476 mediciones de botones y 904 de filas, cero desbordes; 126 controles especiales conservados.
99 escritorios sin cambios de geometría; Detalle conserva su 2 × 2. Volver de Liquidaciones a derecha.
Suite aislada sobre c2db5d4 y fuentes propias: 846 aprobadas/3 omitidas, 7655 aserciones, salida 0; sin cambios posteriores a esa base.
Último ajuste de dos reglas CSS: PHP idéntico, nueva captura completa y guardianes 9/36 aprobados.
Carlos revisa la nueva uniformidad: 1.053 PNG reales, sin aprobación heredada de T36/T37.
[Entrega y galería completa](../06-pruebas/PRU-04/IMPLEMENTACION-T75-CONSISTENCIA.md). Sin despliegue ni autocierre.

## T38 — Reactivar genera la cuota del mes, cerrada por Gemini 18/10/2026

DU-12 y decisión de Carlos del 17/10 (plazo de alta para el reingreso). Reactivar desde la
ficha o el listado crea la cuota del mes en curso con el porcentaje de reingreso del día, salvo
que ya exista; sin plan para el mes no reactiva. La cuota guarda `fecha_reingreso` y tiene el
plazo de un alta en estados y en la mora. El cobro que reactiva a un inactivo completa lo mismo.
18 pruebas nuevas; verificada de forma independiente por Gemini CAB sobre `wings_testing_gemini`:
código, puertas de entrada, importes, plazos, mora, cobro a inactivos, Chrome a 1366 y marco 360px
(12 capturas) y suite completa (**821 aprobadas, 2 omitidas, 0 fallidas, 7593 aserciones, 852,91 s**).
[Implementación](../06-pruebas/PRU-04/IMPLEMENTACION-T38.md) · [Verificación Gemini](../06-pruebas/PRU-04/VERIFICACION-T38-GEMINI.md).

## T36 — Revisión persistente de caja, cerrada por Claude 18/10/2026

Claude verificó reglas y registró aprobación visual de Carlos («visualmente están bien»).
Las cifras siguientes conservan el corte de implementación del 17/10.

El tilde se guarda con autor, fecha y huella del movimiento. Solo ADMIN activo revisa
una caja CERRADA con conteo; validar controla todos los renglones de Detalle en el
servicio, también desde Resumen. Si faltan, informa cuántos y lleva a Detalle.
Caja vacía e idempotencia de las ya VALIDADA conservadas; cancelados no suman en Cashflow.
Carlos: «Destildar solo los movimientos que cambien». Rechazar conserva marcas vigentes;
editar o cancelar invalida las afectadas, incluso en actualizaciones masivas.
Las dos vistas sin JavaScript incrustado: quedan 8 bloques en 8 vistas y 8 manejadores.
68 pruebas dirigidas/449 aserciones y 9 de cancelación/concurrencia real/125 aserciones,
aprobadas. Chrome comprobó persistencia, ambas puertas de validación, modales, error
de red y confirmación de eliminar. Suite completa: 818 aprobadas, 2 omitidas y 1 fallo
en `ReingresoCuotaT38Test`, trabajo paralelo de Claude en esa corrida: esperaba 4000.00 y obtuvo 7000.00.
7584 aserciones, 1332,77 s, salida 1. Tras cbc851e de Claude, 50 pruebas integradas aprobadas/314 aserciones (34,81 s), incluido ese caso. Sin reparación propia de T38 ni certificación de suite completa verde.
[Informe y capturas](../06-pruebas/PRU-04/IMPLEMENTACION-T36.md). Sin despliegue ni autocierre.

## T37 — Motivo obligatorio de rechazo, cerrada por Claude 17/10/2026

Las pruebas siguientes conservan el corte de implementación; el aspecto lo verificó Carlos.

Controlador web y servicio exigen texto no vacío de hasta 500 caracteres; solo
espacios es vacío. Sin mínimo adicional ni cambios a rechazos históricos.
Los dos modales requieren motivo y muestran errores al inicio de la pantalla.
Ocho pruebas nuevas; T37/A25 y regresiones: 102 aprobadas/853 aserciones. Cuatro recorridos Chrome
con dueño sintético, Detalle/Resumen y anchos 1366/360, pasaron; 14 capturas reales.
Suite completa propia: 784 aprobadas/2 omitidas, 7395 aserciones, 965,22 s; [informe y límites](../06-pruebas/PRU-04/IMPLEMENTACION-T37.md).
Seguimiento visual pedido por Carlos el 17/10: corregido localmente el desborde de Detalle.
Información adaptable como Resumen; contador y botones envuelven; tabla con desplazamiento
interno en celular. 48 variantes comprobadas (ADMIN/OPERATIVO, cuatro estados, con/sin
movimientos, 360/375/1366); ocho comprobaciones adicionales de acciones al deslizar a 375.
[Capturas y alcance](../06-pruebas/PRU-04/DETALLE-CAJA-MOBILE.md). Visual aprobado por Carlos, 17/10: «Bien ambas imagenes!!».
Tabla con scroll y «Modal OK» ya aceptados; también aprobadas las dos imágenes a 375
de botones 2 × 2 a la derecha y Resumen/Volver a la derecha, entregadas en `4b050ac`.
Verificador visual: Carlos (§6a); no requiere otra IA. Seis visitas 360/375/1366 documentadas.
Suite posterior a la alineación: 784 aprobadas/2 omitidas, 7395 aserciones, 901,37 s,
base y archivos temporales exclusivos de Codex; salida 0.
Suite del primer ajuste: 783 aprobadas, 2 omitidas, 1 fallida por Excel temporal ausente
en PrimeraCargaExcelTest; T37 aprobada. Repetición dirigida: 40 aprobadas, 2 fallidas por
archivos temporales de Primera carga. Esa corrida no fue verde; se conserva en el informe.
Con carpeta temporal exclusiva, repetición de los mismos casos: 42 aprobadas/714 aserciones,
52,19 s. Sin modificar código. El fallo de la corrida completa sigue registrado.
Claude verificó y cerró T37 en `870cbb4`; visual aprobado por Carlos. Sin despliegue.
Alcance resuelto por Carlos (17/10): «Entregar según el prompt; Inicio queda pendiente». CAJ-029.c exige el motivo en Inicio,
pero el prompt de T37 pide verlo en Resumen y solo autoriza las dos vistas de caja.
`OperativoDashboardController` pasa un conteo de rechazadas y `operativo/dashboard`
muestra ese aviso, sin motivos. Inicio queda pendiente por esa decisión; T37 se entrega con el alcance del prompt.

## B11 — Implementada localmente, a verificar por Claude, 16/10/2026

Motor diario del mes vigente, configuración, cobro por mes con capital primero,
recibo, perdón/reactivación y saldos incorporados; porcentaje inicial cero.
Carlos autorizó «Actualizar los conteos y seguir»: solo dos cantidades de
`CatalogosSeederTest` cambian (11/18 → 12/19); las demás pruebas viejas se conservan.
Suite completa propia: **761 aprobadas, 2 omitidas, 7214 aserciones, 1106,83 s**.
35 pruebas nuevas y 31 capturas reales en escritorio y marcos de 360/375.
[Entrega, criterios y evidencia](../06-pruebas/PRU-04/IMPLEMENTACION-B11.md).
Carlos aprobó el visor celular («En celular esta bien»), Configuración escritorio
(«OK, aprobado») y Cobrar escritorio («OK»); ver capturas y alcance en la entrega.
Pendiente revisión independiente de lógica y del resto visual; sin publicación ni despliegue.
`52e6bdf` y sus descendientes permanecen locales por decisión de Carlos.

## T11 — Comprobación local de Codex, 16/10/2026

Application > Manifest sin errores ni avisos; dos screenshots reconocidos y CDP
sin errores de manifest/instalabilidad. Tres pruebas propias pasan (63 aserciones).
Se corrigió un enlace 404 del informe; la observación y la evidencia están en
[Tercera vuelta](../06-pruebas/PRU-04/VERIFICACION-T11-CODEX-TERCERA.md).
Durante la revisión entró `512f375`: Claude registra el cierre por instalación y
prueba de Carlos en su teléfono. Codex conserva ese cierre; no comprobó la prueba
física ni leyó el chat privado citado. Suite propia: 724 aprobadas, 2 omitidas,
1 fallo documental (727 pruebas descubiertas, 728 en árbol); contadores corregidos
después y guardián aislado 1/7 verde. No se afirma suite completa verde.
Sin despliegue.

## T3 — Verificada por Codex y cerrada, 16/10/2026

Codex CyE comparó `f4e6436` con su anterior: 70 defectos, cero cambios abierto/cerrado
y cuerpos intactos; 56 encabezados distintos. Copia aislada: cinco archivos escritos,
tres ediciones manuales detectadas y CRLF verde. Se devuelve porque cambiar solo
el paso de A6 altera el cierre de 08/10 a 16/10 y el guardián sigue verde.
Suite propia: 723 aprobadas/2 omitidas, 6929 aserciones, 887.55 s; los diez casos
de PerdonConcurrenteT33Test pasan en la suite completa. Siguen siendo 725 pruebas.
[Verificación y evidencia](../06-pruebas/PRU-04/VERIFICACION-T3-CODEX.md).
Sin corrección del generador, producto, datos del club, reloj o despliegue.
**Corregido por Claude el 16/10 (segunda vuelta):** cada tarea guarda su fecha de cierre aparte
(`cerrado_el`); cambiar otro dato no la mueve. Lo cerrado conserva el encabezado que tenía antes
de T3, con sus matices. Dos pruebas nuevas reproducen el caso de A6: 727 pruebas.
**Segunda verificación de Codex CyE, 16/10:** A6 corregido, 67 encabezados/renglones
cerrados restaurados; 66 fechas coinciden y B15 se respalda con SERVIDOR.md y 031bdec.
Devuelta a Claude: cerrar B11 emite `Undefined array key 2` en lib.php:660, aunque
guarda. Corregir el grupo opcional y probar el cierre HTML sin avisos. Suite propia
725 aprobadas/2 omitidas, 6947 aserciones, 681.68 s; diez casos concurrentes T33 verdes.
Sin arreglos ni despliegue. [Segunda vuelta](../06-pruebas/PRU-04/VERIFICACION-T3-CODEX.md#segunda-vuelta).
**Tercera vuelta local de Codex, 16/10:** seis pruebas/27 aserciones pasan; la nueva
detecta el aviso con la biblioteca anterior. Cerrar B11 solo en copia genera HTML
sin avisos y con sello/casilla correctos; origen intacto. Codex actualizó los cuatro
contadores a 728; guardián aislado 1/7 verde. El commit de Claude `c6ef9b6` conserva
los hashes comprobados; los cuatro guardianes finales pasan 12/55. T3 cerrada por
Codex, sin presentar como verde la suite inicial con el fallo de conteo.
[Evidencia](../06-pruebas/PRU-04/VERIFICACION-T3-CODEX.md#tercera-vuelta).

## T2 — Retiro de los importadores viejos de la primera carga, 13/10/2026

Gemini CyE retiró los importadores de consola antiguos (`wings:importar-padron`, `wings:importar-deuda-inicial` y `wings:exportar-padron`), sus servicios asociados (`CargaSaldoInicialPadronService`, `CargaDeudaInicialExcelService`) y sus 16 pruebas asociadas, cumpliendo la decisión P0/P1 de PRU-02 tras consolidarse el importador web único (`/sistema/primera-carga`). Documentación histórica, órdenes y planillas archivadas en `docs/99-archivo/`. Suite en 722 pruebas. [Informe de entrega](../06-pruebas/PRU-04/IMPLEMENTACION-T2.md).

## T33 — Perdón de inscripción y reactivación, 13/10/2026

Codex CyE implementa DU-25/DU-58: ADMIN condona inscripción pendiente y reactiva
cuotas/inscripción perdonadas desde ficha, con motivo 10–500 y ambas decisiones conservadas.
Pagos parciales y original intactos; sin caja/cashflow/recibos ni matrícula reactivada.
31 pruebas nuevas, capturas reales 1366/marco360 y login. Suite local 736/2 verde, 7001 aserciones.
Reporte anterior conserva cuota histórica, pero usa perdón actual de inscripción;
se informa sin modificar esa regla. [Entrega T33](../06-pruebas/PRU-04/IMPLEMENTACION-T33.md).
Sin despliegue. Claude verifica código y pantalla antes de cerrar.

## T31 — El que debe la inscripción también es moroso o deudor, 13/10/2026

Gemini CyE implementó la regla de Carlos (12/10 y 13/10/2026):
- La inscripción impaga convierte al alumno en MOROSO o DEUDOR según su fecha de ingreso (`fecha_alta`).
- Plazo propio del alumno nuevo (`dias_plazo_alta`, 7 días por defecto) o gracia general (`dias_gracia_cobranza`, 10 días): lo que venza más tarde.
- Jerarquía de cobranza: `DEUDOR > MOROSO > EN_PLAZO > AL_DIA`.
- Sincronización exacta entre Cobranza y el buscador de Cobrar caja.
- 18 pruebas nuevas en `CobranzaInscripcionEstadoTest`. Suite en 738 pruebas (incluyendo T33).
- [Informe de entrega](../06-pruebas/PRU-04/IMPLEMENTACION-T31.md).

## T27 — Implementación local y comprobación, 12/10/2026

[Propuesta e inventario](../06-pruebas/PRU-04/PROPUESTA-T27.md): Cashflow como entrada
única, con origen/acciones por fila y acceso separado a cajas sin validar. Maqueta
renderizada por Laravel con datos ficticios: 32 PNG, marco360/login, 20 visitas sin fallos en etapa 1.
Carlos aprobó la propuesta visual el 12/10: «Sí, apruebo la propuesta visual».
Decidió editar el mismo registro conservando historial del valor anterior y el nuevo.
Eliminar conserva el original y crea un asiento en contra.
Motivo opcional para editar y eliminar, por decisión de Carlos.
Se puede corregir cualquier mes anterior, sin límite.
Caja validada y liquidación pagada: solo consulta, sin edición ni eliminación desde T27.
Liquidación sin pagar: Editar lleva al flujo existente, conservando sus restricciones.
Carlos decidió actualizar el reporte del mes original, con historial. Contraasiento con fecha original.
Reglas respondidas; etapa 2 implementada localmente, suite completa verde.
Editor de directos, contraasiento único, historial antes/después y control de versión/reenvío.
18 pruebas propias pasan: 16 de recorrido y 2 con conexiones MariaDB reales.
53 capturas finales, marco360/login, 36 visitas sin fallos. [Informe](../06-pruebas/PRU-04/IMPLEMENTACION-T27.md).
Contradicción corregida: Historial mezclaba egresos operativos positivos
con egresos directos negativos y pintaba estos últimos como ingresos. La captura publicada
`evidencia-t27/capturas/historial-actual-escritorio.png` muestra internet +$23,500.75 en verde.
Se normaliza para cumplir la consistencia de Historial con Cashflow/Inicio/Reportes en T27.
Hallazgos del relevamiento: rechazarCaja acepta solo CERRADA, no VALIDADA;
liquidación pagada no se corrige. Además, el controlador limita edición de operativos
a su caja mientras el contrato limita por propietario solo el cierre; no se cambió.
Suite propia: 687 aprobadas, 2 omitidas; 6588 aserciones, 501,04 s en wings_testing_codex.
Carlos aprobó el resto de las capturas finales y señaló el importe grande desalineado en Historial móvil.
Se amplió solo la columna Monto: diez importes alineados en escritorio y marco360; cuatro visitas sin fallos.
Carlos aprobó Historial corregido: «Esta OK ahora» y «Sí, aprobado»; todas las capturas finales aprobadas.
T27 queda a verificar por Claude para reglas; no se cierra ni se despliega.

## T30 — Centavos y formato compartido, 12/10/2026

Codex CAB implementó la presentación `$48,000.50`, siempre dos decimales, en pantallas,
avisos, PDF nuevos y comunicaciones. Excel sigue numérico; gráficos y recibos emitidos
se conservan. Regla común PHP/JS, sin cambiar cálculos ni validaciones de T24.
T28 se publicó independiente primero (`e1e73d8`), T30 se aplica después.
Suite propia: 669 aprobadas/2 omitidas, 6395 aserciones; 671 pruebas.
[Inventario, capturas y límites](../06-pruebas/PRU-04/IMPLEMENTACION-T30.md).
Revisión visual 12/10: Carlos señaló porcentaje desbordado en la leyenda de Reportes;
porcentaje junto al rubro e importe debajo en celular. Nueva medición de la leyenda,
563 controles/0 fallas en la revisión, sin cambiar fórmulas. Capturas nuevas en
`evidencia-t30/revision-porcentajes-ultima/`; suite propia 669 aprobadas/2 omitidas,
6395 aserciones, 530,97 s.
Carlos aprobó todas las vistas: «El resto de las vistas OK» y «Esta OK» para la
leyenda corregida (12/10). Claude verificó la regla, corrigió con autorización
la portabilidad de las pruebas y cerró T30 en `b4fdd6e`; no pedir otra aprobación visual.
Sin despliegue por Codex.

## T29 — Buscador de Cobrar lista solo a deudores verificado y cerrado, 12/10/2026

Gemini CAB verificó la implementación de Claude (`8a8a6b8`) para T29 (D01-5 de la prueba por días, Carlos 12/10/2026).
Comprobaciones aprobadas:
1. Sin buscar: la lista trae únicamente a los alumnos con deuda y coincide exactamente con el encabezado («X alumnos con deuda pendiente» o «Sin deudas pendientes»).
2. A cualquier alumno al día o sin cuota generada se llega buscándolo por nombre, apellido o DNI y se le puede cobrar normalmente. Se comprobó el flujo completo de cobro real (`POST web.caja.pagar`).
3. El que debe solo inscripción aparece como deudor; cuota de mes futuro no aparece ni suma en el encabezado.
4. Alumno inactivo con deuda no aparece ni suma en el encabezado.
5. Permiso de profesor bloqueado (403). Dueño sin caja cobra directo.
6. Comparación rigurosa con Cobranza explicada: Cobrar lista a todo alumno con saldo pendiente (`saldo['total'] > 0`); Cobranza pestaña DEUDORES filtra a morosos/deudores con plazo vencido para gestión de cobro punitoria. El total adeudado en pesos y la suma de estados coinciden al 100%.
7. Pruebas: 11 pruebas pasando (113 aserciones) en `docs/06-pruebas/PRU-04/evidencia-t29/VerificacionT29GeminiTest.php`. Suite completa 664 tests aprobados.
8. Tablero: `cerrado`, `hizo=Claude`, `verifica=Gemini`. Informe en `docs/06-pruebas/PRU-04/VERIFICACION-T29.md`.

## T28 — Cashflow: vínculo directo por movimiento y columna Val. (segunda vuelta), 12/10/2026

Gemini CAB implementó la segunda vuelta de T28 tras la revisión de Claude y las decisiones de Carlos del 12/10/2026:
1. Migración en base de datos: columna `movimiento_operativo_id` (nullable) en `cashflow_movimientos`. Cada movimiento nuevo generado al validar una caja se vincula de forma directa y unívoca con su movimiento operativo de origen, resolviendo el alumno y quién registró sin mirar la observación ni depender de comparaciones de texto.
2. Tratamiento de movimientos preexistentes: renglones viejos sin ID guardado exigen coincidencia exacta de importe + subrubro + observación con un único movimiento de la caja. Se eliminó el segundo intento por importe y rubro; si no hay certeza exacta, se muestra la observación original y nunca se adivina un alumno.
3. Columna de validación:
   - Escritorio: columna «Val.» (50px) al final de la tabla con las 2 iniciales del administrador que validó (ej. `CB`, `VA`) y tooltip (`title`) con el nombre completo. En movimientos directos sin caja, la celda queda vacía (ni iniciales, ni guión, ni texto).
   - Celular (375px): en la tarjeta, línea inferior con `Registró: <nombre>` a la izquierda y badge compacto `Val: <iniciales>` a la derecha. En directos, solo `Registró: <nombre>`.
   - Cuidado de importes: ancho de Monto fijado en 125px holgados con soporte de decimales (`FormatoImporte::pesos`), evitando desbordes con importes grandes.
   - Desempate de iniciales: si dos administradores activos comparten iniciales (ej. Carlos Bonifacio y Carlos Benítez), desempata la segunda letra con la última del apellido (ej. `CB` y `CZ` o `CO`), según decisión de Carlos.
4. Pruebas: 5 pruebas permanentes en `tests/Feature/CashflowVinculoDirectoYValidadorTest.php` (100% PASS), más `CashflowDetalleAlumnoYUsuarioTest` (5/5 PASS) y `VerificacionT28ClaudeTest` (2/2 PASS con 40 cobros idénticos paginados).
5. Capturas v2 guardadas en `docs/06-pruebas/PRU-04/evidencia-t28/`: `cashflow-v2-escritorio.png`, `cashflow-v2-celular-375.png`, `login-control-v2-escritorio.png`, `login-control-v2-celular-375.png`.
6. Tablero: `a_verificar`, `hizo=Gemini`, `tiene=Claude` para verificación independiente de regla en código y base de datos. Informe en `docs/06-pruebas/PRU-04/IMPLEMENTACION-T28.md`.

## T26 — Historial de caja del dueño cerrado, 12/10/2026

Gemini CAB verificó la implementación de Claude (`f4a030e`) para T26 («El dueño debe ver todo, los límites no son para él», Carlos 11/10/2026).
Comprobaciones aprobadas:
1. El dueño puede ver cualquier fecha histórica con `desde` (arranca en últimos 90 días por defecto); la operativa queda confinada a los 90 días aun forzando fechas en la URL.
2. El dueño ve subrubros exclusivos de ADMIN en la grilla y en el select de filtros; la operativa solo ve los permitidos para su rol.
3. Movimientos cancelados: el dueño los ve marcados con `· Cancelado` y tachados (`line-through`); la operativa no los ve; en ambos roles los totales excluyen lo cancelado.
4. Cobro del dueño sin caja y anulado por camino real (`POST /caja/cobrar/{id}` y `POST /pagos/{id}/anular`): ambos renglones listados con alumna identificada, anulación resta como asiento en contra y total neto da $0,00.
5. Profesora recibe HTTP 403 Forbidden.
6. Filtros (texto, subrubro, tipo, fecha) y paginación plenamente funcionales.
7. Casos borde verificados: caja validada reflejada en cashflow con `REF_CAJA` no duplica filas en el historial; totales recalculan con exactitud ante cualquier filtro aplicado.
Suite de verificación propia `docs/06-pruebas/PRU-04/evidencia-t26/VerificacionT26Test.php` (8 pruebas, 103 aserciones, 100% verde). Suite completa 641/2 aprobadas en `wings_testing_gemini`.
Informe en `docs/06-pruebas/PRU-04/VERIFICACION-T26.md`. Tarea T26 CERRADA en el tablero (`verifica=Gemini`). Sin desplegar al sitio de prueba.

## T24 — aspecto aprobado por Carlos, regla a verificar, 12/10/2026

Codex CAB implementó el componente y el tratamiento común para 16 pantallas/modos,
incluyendo Rectificar de T23. Carlos decidió aceptar coma decimal, mostrar
`48,000.50` y recortar el tercer decimal con texto rojo y aviso de 4 segundos
sin bloquear el valor corregido. Pedidos directos de precisión inválida rechazados
sin escribir; límites, cero, campos protegidos y cálculos conservados.
Suite propia 641 aprobadas/2 omitidas, 6075 aserciones, 448,36 s; 643 pruebas.
Carlos devolvió las capturas del 11/10: revisados adelantado móvil, íconos,
desglose en dos líneas y acciones de Configuración/Liquidación. Aviso de importes
excluido del cierre general para conservar sus cuatro segundos y su reaparición.
Navegador revisado 1351 controles/100 PNG y assets fijos; 18/18 campos previos detectan
regresión sobre dd62c64. [Inventario, visor y límites](../06-pruebas/PRU-04/IMPLEMENTACION-T24.md).
Carlos aprobó la revisión `be5ad9a`: «Ahora OK» (12/10). Claude tiene la
verificación de la regla; T24 sigue a_verificar. Sin cierre ni deploy.
Trabajo ajeno de Historial/permisos fuera del commit propio; el controlador
compartido incorporó las líneas de T24 en 03aaf11 de T23.

## T16 — tercera vuelta aprobada por Codex, 10/10/2026

Corrección `b8ede87` comprobada sobre copia fija `aa33399`: 12 controles originales/225
aserciones, ocho adicionales/269 y ocho cruces propios/220 aprobados. APORTE y RETIRO,
edición/alta, ambos órdenes: espera real comprobada; uno guarda y otro rechaza claramente.
Los 16 pedidos terminan en 0,263–0,943 s; ocho guardados posteriores pasan; sin pares inválidos.
Suite completa 614 aprobadas/2 omitidas, 5.087 aserciones, 637,19 s en wings_testing_codex.
Carlos acotó a guardados desde Wings; cruces con comandos de consola fuera del alcance.
Altas automáticas de sueldo escriben NEGOCIO bajo rubro reservado; API sin rutas registradas.
Nombres nuevos en INGRESO quedan para el admin; DemoSeeder NULL, observación separada.
Tablero cerrado, hizo Claude/verifica Codex/tiene nadie; aspecto aprobado por Carlos.
Sin cambios de Wings, visita al sitio ni despliegue. [Informe](../06-pruebas/PRU-04/VERIFICACION-T16.md).

## T14 — Día 3 recorrido, lunes simulado 12/10, 10/10/2026

Por pantalla: Sandra canceló Patín Principiantes y Fútbol Principiantes 16–17;
admin canceló Patín Intermedias 17–18. Ver → Cancelar → motivo → Cancelar esta;
admin también ofrece Cancelar serie. No se usó. Agenda de hoy: 3 → 1 → 0.
Frena: canceladas del día no aparecen en Clases ni con Cancelada + 12/10,
comprobado como Sandra, admin y Lucía. Profesora no pudo abrir la cancelada de hoy.
Sin asistencia conserva 33; las dos páginas contienen solo fechas hasta 10/10.
En fichas canceladas vistas, Guardar deshabilitado. ADMIN ve Activar; no se pulsó.
Sandra: opción de reactivar sus dos canceladas no comprobada por quedar ocultas.
Inicio/Reportes: ingresos $164.000, deuda $2.010.000, por pagar $0; Cobranza $2.010.000.
Sueldos: Gaitán/Salinas/Quintana $0, Mariela $18.000/6 asistencias.
Molesta: bloqueos de ingreso de Sandra/admin, luego ingresaron; causa no investigada.
Decisión vigente de Carlos: suplencias y oferta de abrir caja en feriado son correctas.
Sin código/base, cobros, apertura, arreglos ni cambio del reloj. T14 sigue abierta;
Claude verifica [Día 3 y capturas](../06-pruebas/PRU-04/DIA-03.md).
[Día 2](../06-pruebas/PRU-04/DIA-02.md) y [Día 1](../06-pruebas/PRU-04/DIA-01.md) históricos.
Conservar cancelaciones, carga, pagos y asistencia para continuar el cuento.

## T15 — implementada, pendiente de verificación por Claude, 10/10/2026

La orden pedía la nueva404 también para `/alumnos/999999` en los cuatro perfiles.
Las rutas actuales exigen sesión y permiso: PROFESOR recibe403 y anónimo ingresa.
Carlos aclaró «Sí, conservar permisos y login»: nueva404 solo cuando corresponde.
Para500/503, Volver usa el acceso existente: al recuperarse el sistema reconoce
la sesión y redirige al inicio del rol; renderizar el error no consulta sesión ni base.
Aspecto solicitado: mismo sistema visual que403; no desplegar, Claude verifica.
Cuatro pantallas nuevas, Inicio en el menú y JSON preservado. Suite594/2,
4966 aserciones; 32 capturas de errores por rol, 134 del menú y cuatro con base
inaccesible/mantenimiento; control de login a360 y primera carga pendiente.
[Entrega y visor](../06-pruebas/PRU-02/IMPLEMENTACION-T15.md). Base del club intacta.

## B10 — verificado y devuelto a Claude, 10/10/2026

Contradicción comprobada por HTTP y filas: la orden pide rechazar alias con espacios;
`CbuOAlias::normalizar()` los elimina antes de validar y guarda otro identificador.
También transforma tabulación/salto; el rechazo de200 caracteres aparece en inglés.
Sin corregir código, vistas/CSS o tests. Pago completo, permisos y privacidad,
primera carga Excel y siete seeders comprobados en base/storage exclusivos de Codex.
Suite581/2,4650aserciones; build correcto. B10 devuelto a Claude;64/74 cerrados.
[Informe y evidencia](../06-pruebas/PRU-02/VERIFICACION-B10.md). Sin despliegue.

## A42 — discrepancia confirmada en fuente, 10/10/2026

La orden indica que falta la consulta inicial. La fuente actual ya ejecuta `actualizar()`
en `resources/js/alumnos-inscripcion.js:28`, desde `11623b6` (22/09), y el bundle también.
Carlos decidió «Sí, verificar y registrar»: comprobar los casos pedidos sin duplicar la
llamada. Sin cambios de aplicación ni JavaScript incrustado. Chrome real:13 controles
aprobados y filas ficticias conservadas; programas/resultados y ochoPNG revisados por otro agente.
Suite completa573 aprobadas/2omitidas,4616aserciones,559,63s correcta. [Entrega y evidencia](../06-pruebas/PRU-02/IMPLEMENTACION-A42.md).

## B12/A23 — plan validado, desarrollo autorizado 09/10/2026

Revisión Alumnos/Sueldos, 09/10: la matrícula solo guarda estado/deporte/nivel
actuales; no acredita activos al cierre de septiembre. La propuesta de Alumnos
separa matrícula de hoy y asistencia del mes por deporte/nivel de la clase;
no transforma clases sin registros en ausencias ni determina bajas automáticas. Consulta probada y propuesta
visual revisada en seis capturas reales; [entrega](../06-pruebas/B12-A23/PROPUESTA-ALUMNOS-2026-10-09.md).
Suite del prototipo543/2,4401aserciones,556,59s correcta. Carlos aprobó el aspecto («Esta OK»); integración habitual comprobada en la entrega posterior.
Sueldos implementado en desarrollo: reparto de cuota neta por asistencias;
comisión sobre cuota cobrada, costo por deporte/profesor/asistencia y evolución.
ADMIN ajusta monto final de comisión ABIERTA o CERRADA sin pagar, con motivo,
auditoría atómica y bloqueo común con pago; pagadas/canceladas intactas.
Pago, recibo, pendientes y resúmenes toman el final sin alterar el cálculo original.
Historia monetaria desde activar; antes, Sin historial. Horarios/asistencias actuales
pueden modificar estimados y reparto; liquidación cerrada conserva importe.
Suite de Sueldos573/2,4615aserciones; controles finales30/185 y revisión independiente26/166.
Catorce HTTP200 y capturas reales completas. Carlos aprobó Sueldos («Esta OK, solo
deberiamos ver desde donde accede en el menu»). Acceso Plata → Reportes aprobado por Carlos el 10/10 («Esta OK el acceso»); aspecto del ajuste aprobado por Carlos el10/10 («Sí, así»). Campo Monto ajustado, total a pagar≥0; diferencia automática con signo; nueve íconos. [Entrega](../06-pruebas/B12-A23/ETIQUETAS-AJUSTE-2026-10-10.md).
[Entrega Sueldos y ajuste final](../06-pruebas/B12-A23/IMPLEMENTACION-SUELDOS-2026-10-09.md). Sin activación ni cambios en base del club.

Acceso Plata → Reportes aplicado tras «Continuar»; [control y capturas](../06-pruebas/B12-A23/ACCESO-REPORTES-2026-10-09.md).
Control de ancho en el escenario: Caja375/scroll465 incluso quitando el enlace nuevo;
contenido fuera del cambio del menú, sin corrección ni reapertura automática de tareas.

Conexión V3, control 09/10: la primera revisión encontró que P1 deja pasar una ruta
nueva que no enumera y que la evolución omite el mes elegido cuando no tiene
movimientos. Casos registrados antes de ajustar el bloqueo y la selección mensual; corregidos y revisados en fuente. Regresiones permanentes P1/mes sin movimientos.

Control HTTP independiente de Inicio, 09/10: el escenario guardado dejaba P1 en
PENDIENTE y el acceso normal redirigía a preparación. Los tests base marcan P1
TERMINADA antes de cada prueba; ese contexto no se conservaba al reconstruir datos.
Corregido solo `ReportesEscenarioSeeder`: P1 TERMINADA en el escenario ficticio,
sin habilitar Deshacer ni retirar la protección del club. Regresión: la prueba fuerza
P1 PENDIENTE antes del seeder. Control HTTP posterior aprobado (Inicio y cuatro listados).

Comprobación de historia, 09/10: descuentos/cambios de plan sobrescriben el importe
original de la cuota; las liquidaciones no conservan fecha de cierre ni deporte
congelado. `afecta_caja` controla admisión operativa, no resultado económico:
Luz/Internet/Intereses son económicos aunque el catálogo lo tiene en falso.
Carlos eligió deuda histórica al cierre: agregar registro fechado desde un corte
explícito, sin certificar meses previos incompletos. Datos ficticios solo en la base
Codex. La clasificación económica debe ser independiente de `afecta_caja`.

Carlos validó los [acuerdos y revisión posterior](../07-evaluacion/ACUERDOS-B12-A23-2026-10-09.md). Motor/historial en desarrollo; Inicio aprobado («Ok, aprobado»), aplicado y verificado por HTTP independiente. Cuatro avisos abren los listados exactos, incluidos pendientes antiguos; permisos conservados. Suite completa: 532 aprobadas/2 omitidas, 4286 aserciones, 280,70 s; posteriormente cuatro regresiones P1 aprobadas (42 aserciones). [Entrega de Inicio](../06-pruebas/B12-A23/IMPLEMENTACION-INICIO-2026-10-09.md) y [visor](../06-pruebas/B12-A23/visor.html).

Carlos rechazó el Reportes original. Después pidió «Hacelo mas visual y de menos lectura», con tres referencias. [Propuesta V3](../06-pruebas/B12-A23/PROPUESTA-VISUAL-2026-10-09.md): tarjetas/tendencias, evolución, cambios por rubro, distribución y detalle plegado; Carlos eligió «Si, mucho mejor». [Aplicada y conectada](../06-pruebas/B12-A23/IMPLEMENTACION-REPORTES-2026-10-09.md) a Inicio, con filtros reales y detalles al corte; revisión independiente de fuente, suite completa537/2,4369 aserciones,476,20s correcta. CuatroHTTP200 y ocho capturas reales; revisión independiente final aprobada para esta fase financiera. Reporte Alumnos aprobado e integrado; Sueldos implementado y aprobado visualmente por Carlos; acceso aprobado el 10/10. Sueldos: costo de clases del mes / cuotas reales del mismo mes netas de bonificaciones y condonación / cada asistencia presente por separado. Atribución proporcional aprobada; historial prospectivo probado en base descartable. Ajuste final ADMIN aplicado también cerrado sin pagar y verificado independientemente. Ver/indicadores de Inicio conectados a la ruta mensual y sus apartados; clasificación de antecedentes pendiente. B12 abierto; A23 cerrado por Claude el 10/10 ([verificación](../06-pruebas/B12-A23/VERIFICACION-A23-INICIO.md)). Sin cambios de base del club por Codex.

## A32 — CERRADO 08/10/2026, verifica Carlos

Carlos aprobó el retoque: «Ok, APROBADO A32». Nombre de la persona en el título; Profesor toma Apellido, Nombre de su ficha vinculada y no repite ese dato abajo. Todas las tarjetas muestran Email y Rol en columnas consistentes. Pedro/Sandra/Victoria son nombres ficticios del ensayo. Se conservan Activo/Tu cuenta/candado y Editar/Nuevo x=224–320 en celular. Seis capturas reales y medidas correctas; controlador, CSS y toggle compartido intactos. Retoque nombres/rol: corrida completa 506 aprobadas, 2 omitidas y 2 fallos documentales durante edición simultánea, 4075 aserciones, 275,69 s; controles documentales repetidos 4/19 verdes, 0,31 s. Ninguna prueba de código falló. CERRADO, verifica Carlos; sin despliegue. [Entrega y capturas](../06-pruebas/PRU-02/IMPLEMENTACION-A32.md).

## A6–A10 — CERRADOS 08/10/2026

A6–A9 verifica Carlos; A10 verifica Claude en segunda revisión, con 432 combinaciones/442 pedidos, 19 pruebas propias del corte final y 45 comprobaciones de navegador sin fallos. Limpiar corregido y pulsado en los cuatro modos. Suite final Codex 508 aprobadas/2 omitidas, 4077 aserciones, 423,14 s; build 38,28 s y capturas finales renovadas. [Entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A6-A10.md), [verificación independiente](../06-pruebas/PRU-02/VERIFICACION-A10.md). A32: candado y retoque de nombres/rol aprobados, entrega separada. Sin despliegue.

## Antecedentes A6–A10, antes del cierre (07/08 de octubre)

### Corrección tras primera revisión independiente, 08/10/2026

Claude devolvió A10: Limpiar escapaba dos veces la dirección y perdía fecha/año/mes en Chrome. El resto de la lógica quedó comprobado con 432 combinaciones y 442 pedidos propios. [Verificación](../06-pruebas/PRU-02/VERIFICACION-A10.md). Corregido el parámetro href de Limpiar; nueva prueba DOM reproduce el rojo antes y pasa después. Diez pruebas A10/110 aserciones verdes; sintaxis y Blade correctos. Suite completa y segunda revisión de Claude en ejecución; A32 sigue esperando elección de Carlos. No desplegado.

### Primera entrega A6–A10, anterior al cierre, 08/10/2026

A6–A9 cerrados con aprobación visual de Carlos (§6a); decisiones y capturas en [entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A6-A10.md). A10 asignado a Claude para verificar lógica; [orden](../06-pruebas/PRU-02/VERIFICAR-A10.md) preparada, revisión todavía no ejecutada. Los cortes anteriores de este apartado son antecedentes.

A10 aprobado por Carlos: «Me gusta, A10 Aprobado». Aplicado localmente Día/Semana/Mes/Año, Nuevo junto al contador y título Nuevo movimiento. Resultado del período = ingresos − egresos; deja de sumar saldo inicial, de acuerdo con la propuesta aprobada y FIN-04. No implementa saldo acumulado. Nueve pruebas de intervalos pasan, 70 aserciones; suite 507 aprobadas/2 omitidas y 24 capturas finales correctas. [Entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A10-PERIODOS.md). Aspecto verificado por Carlos; intervalos/cálculos pendientes de otro agente (§6a), sin deploy. Las notas de maquetas de este apartado conservan el corte anterior a aplicar.

Elecciones conservadas: A6 solo texto, A7 B, A8 A. Capturas A7 renovadas desde Wings con
ADMIN/OPERATIVO en escritorio y marco real de 375×667; el pie muestra los controles.
**Contradicción detectada y corregida por pedido posterior de Carlos:** el pase decía
alineación móvil a derecha aplicada; las primeras capturas mostraron A7 a izquierda.
Carlos pidió corregir únicamente celular. Regla CSS exclusiva de Alumnos bajo 640 px;
12 imágenes posteriores y tres comparaciones geométricas de escritorio correctas.
Build 26,41 s correcto; escenario propio previo 4 pruebas/15 aserciones.
Última suite propia después del ajuste de columnas: 496 aprobadas, 2 omitidas, 1 falla; 3948 aserciones, 367,51 s.
La única falla es el contador documental 491/499, ya registrado en el pase de Claude.
Pedido posterior: Cobrar/Editar alineados entre sí y Ver alineado con Nuevo.
Dos columnas móviles de 96 px y ajuste de margen, sin mover Nuevo; coincidencia exacta
en ADMIN/OPERATIVO y nombre largo. Tres imágenes de escritorio idénticas al antes.
[Antes/después y mediciones actuales](../06-pruebas/PRU-02/evidencia/a6-a10/visor-a7-columnas.html).
A7 aprobado visualmente por Carlos el 08/10; paquete todavía en curso.
A8/A9: 22 imágenes actuales reales; Grupos ADMIN/OPERATIVO, Niveles/Profesores ADMIN,
375×667 y escritorio. Activo/Inactivo y Con/Sin grupos comprobados en filas ficticias.
El pase también sobreestimaba la alineación de estos listados: corregida con tres clases
de pie y CSS exclusivo bajo 640 px; controles a derecha, botones 96×32 en una fila.
Cuatro escritorios idénticos al antes por SHA256; distintivo coloreado de Profesores intacto.
Build 24,21 s, sintaxis y Blade correctos; escenario 4/15 verde, 7,53 s.
Suite posterior: 491 aprobadas/2 omitidas/7 fallas, 3944 aserciones, 293,60 s; base Codex.
Seis fallas HTTP 500 por `$bloqueo` ausente en `operativo/dashboard.blade.php`, modificado
simultáneamente fuera de A6–A10; su controlador entrega `estado`, no `bloqueo` en ese corte.
Una falla documental: contador 491/500, pruebas nuevas todavía locales. No se corrigieron
estos cambios ajenos. [Salida completa](../06-pruebas/PRU-02/evidencia/a6-a10/suite-a8-a9-2026-10-08.txt).
[Grupos y accesos a las otras dos pantallas](../06-pruebas/PRU-02/evidencia/a6-a10/visor-a8-grupos.html).
Carlos abrió el visor actual y respondió «A-8 APROBADO» el 08/10; resultado visual aprobado.
A9: Carlos aprobó si coincide con Nuevo; condición comprobada en las tres pantallas reales
a 375 px: Nuevo/último botón x=224–320; interruptor termina en x=320. Siete capturas nuevas,
sin cambios de aplicación. [Control exacto](../06-pruebas/PRU-02/evidencia/a6-a10/comprobacion-a9-alineacion.json).
Carlos confirmó después «Perfecto, APROBADO A-9 Entonces»; no se vuelve a pedir A9.
A10: 28 capturas reales actuales/propuesta, ADMIN escritorio/375; año/mes/vacío y formulario
completo. Nuevo junto al contador en barra propia, Limpiar en filtros y título Nuevo movimiento
propuestos inicialmente mediante dos variantes Blade; luego aprobados/aplicados, ver entrega A10 arriba.
Filas/período idénticos, seis campos con íconos y acciones móviles visibles; enlaces/sintaxis correctos.
[Cashflow ANTES/propuesta y acceso al formulario](../06-pruebas/PRU-02/evidencia/a6-a10/visor-a10.html).
Sin nueva suite/build por la propuesta documental; entrega/verificación pendientes, sin autocierre/deploy.
 
## A14/A27/A53 — CERRADOS 07/10/2026 (verificados por Gemini, contrastados por Claude)

Corrección de Codex (`3d1808a`) verificada de punta a punta con emulación real de navegador (Chrome Headless controlado vía CDP nativo en Node.js v22 con `verificar_interactivo.mjs` a 375×667):
- **A14 (Formularios largos):** Botones `.mobile-form-actions` visibles al abrir (`top: 610, bottom: 667, height: 57`) y durante todo el scroll (`position: fixed`). Margen libre con el último campo (147.6 a 610 px) sin solapamientos. Resumen dinámico muestra errores en vacío (6 en alumnos, 8 en profesores); al completar un campo desaparece en tiempo real, al vaciarlo reaparece, y al completar todos se oculta. Con DNI repetido (`40111222`), rechazo 422 del servidor muestra mensaje y al modificar el campo pasa a «DNI: dato modificado; se comprueba al guardar». Cartel a 128 px debajo del encabezado sin solaparse.
- **A27 (Movimiento de caja):** Registrar y Cancelar visibles en `y=610` (antes quedaban en `y=740`). Campo Observaciones alcanzable con 131 px libres. Resumen de errores dinámico. Registro real de movimiento completado con OPERATIVO (Egreso, $1.500) persistido en `movimientos_operativos` (id 1) y con redirección exitosa a `/caja`.
- **A53 (Grupos y Cobro):** Grupo largo con 3 tarifas de 7 cifras ($1.250.000, $2.450.000, $3.850.000) y tarjeta de alumna en selector de cobro legibles al 100% sin recortes (`scrollWidth <= clientWidth`) y `document.documentElement.scrollWidth = 375` (cero desborde horizontal).
- **Regresiones:** Salir sin guardar (A4) activo con diálogo `confirm`. En escritorio 1280×900 las 7 vistas conservan layout de escritorio sin barra fija ni cartel móvil. `CspSinCodigoIncrustadoTest` 2/2 PASSED.
- [Informe completo](../06-pruebas/PRU-02/VERIFICACION-A14-A27-A53.md) · Evidencia y 30 capturas en `docs/06-pruebas/PRU-02/evidencia/verificacion-a14-a27-a53/`. Dictamen: APROBADO. Pasa a Claude para control cruzado y cierre definitivo (§6a).

## A15/A16 — CERRADOS 07/10/2026 (verificado por Gemini, contrastado por Claude)

Carlos eligió aviso y confirmación de bloques del reloj y aprobó capturas escritorio/375.
Crear avisa sin escribir; Confirmar permite continuar con los horarios revisados.
Cada día de una serie tiene su horario: seis POST guardan 76 clases de seis grupos,
una serie por grupo; conflicto en cualquier fecha revierte toda la tanda.
18 pruebas nuevas; suite completa final 486 aprobadas/2 omitidas, 3911 aserciones, sin fallas.
Build/PHP/Blade correctos; capturas reales y login de control a 375 comprobados.
[Entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A15-A16.md) · [Contrato V2](../02-contratos/Wings-Contrato-Clases-Asistencias-V2.md).
Solo creación web; no reservas, tarifas, edición masiva ni despliegue. Verifica otro agente.

## Paquete de celular — verificado por Codex CyE 06/10/2026

A20, A28, A33, A36, A40 y A41: CERRADOS, verificado Codex CyE 06/10. A14, A27 y A53: DEVUELTOS a Gemini; T1 también devuelto por cobertura incompleta y recortes.

84 capturas propias, 102 originales inspeccionadas; tres roles y datos ficticios de estrés.
A36 conserva tabla Opción A, no tarjetas; A33 mide 61,61 px por fila.
Suite 489 aprobadas/2 omitidas, 3924 aserciones, 187,17 s, wings_testing_codex.
[Dictámenes, cobertura y límites](../06-pruebas/PRU-02/VERIFICACION-CELULAR-COMPARTIDO.md).
Sin cambios de aplicación ni despliegue.

## A25 CERRADO (verificado por Gemini, contrastado por Claude) — 06/10/2026

Apertura explícita con recibido confirmado, herencia del último cierre del club y motivo
si difiere. Un cajón compartido, un turno abierto. ADMIN configura el medio físico una
vez, guarda aparte sus cobros y cuenta/cierra antes de validar o rechazar.
Cierre con esperado/contado/diferencia, cambio que queda y entrega; permite diferencias
para revisión ADMIN, sin crear asientos de ajuste. Rechazadas conservan conteo/entrega y
actor/fecha originales, sin cambiar la apertura siguiente. Históricos NULL no se rellenan.
26 pruebas permanentes (142 aserciones) pasando en suite compartida, sin fallas.
Carlos aprobó las cinco pantallas escritorio/375; sin CSS ni componentes compartidos tocados.
[Entrega y capturas](../06-pruebas/PRU-02/IMPLEMENTACION-A25.md) · [Contrato V5](../02-contratos/Wings-Contrato-Caja-Cashflow-V5.md).
Verificado por Gemini en una segunda vuelta, con recorrido propio por HTTP y 23 capturas; Claude reprodujo el recorrido y contrastó el informe; CERRADO 06/10 ([Informe](../06-pruebas/PRU-02/VERIFICACION-A25.md)). Sin sesión interactiva de navegador. Base del club y servidor intactos.

## A13/B1/A55 cerrados; A48/A49 asentados — 06/10/2026

Rama a55-inscripcion integrada entera en main `6d3f68a`, conservando ambos logs.
A13/B1/A55 CERRADOS por Codex CyE: cobro ADMIN sin caja, anulación desde ficha con
motivo, historial con períodos originales, contraasientos negativos como egreso y
una inscripción por persona coherente entre selector y ambas fichas.
Regresión A25: esperado operativo $58.000 ($10.000 inicial + $48.000 propios),
sin sumar cobro directo ADMIN de $101.000 ni abrirle caja.
A48/A49 CERRADOS, verificado Claude; búsqueda repetida sobre main coincide.
**41/72 cerrados, 31 abiertos, 0 frenan.** A54 conserva su cierre anterior.
El primer control rechazado queda como antecedente, no como estado actual.
[Informe y capturas](../06-pruebas/PRU-02/VERIFICACION-A13-B1-A55-CIERRE.md). Sin deploy.

## A37 resuelto (Ficha del alumno en celular 375px) — 05/10/2026

- **Defecto A37:** La ficha del alumno (`/alumnos/{id}`) desbordaba horizontalmente en celulares a 375px, cortando los botones de cabecera (`Editar`) y filas de historial (`Recibo`, `Anular`).
- **Solución implementada:**
  - `resources/views/alumnos/show.blade.php`: la barra superior de acciones reemplazó el `justify-content: flex-end` inline por `flex flex-wrap items-center gap-2 mb-4 justify-start sm:justify-end`.
  - Las filas de deudas pendientes e historial de pagos adoptaron `flex flex-wrap sm:flex-nowrap justify-between items-center gap-2 p-2 rounded` con bloque de acciones `w-full sm:w-auto justify-end` para adaptarse en 375px sin desbordes.
  - Botón Volver inferior actualizado a `justify-start sm:justify-end`.
  - Cubierto con `tests/Feature/FichaAlumnoResponsiveA37Test.php` y capturas comparativas en `docs/06-pruebas/PRU-02/capturas-a37/`. Marcado como `HECHO (Gemini), a revisar`.

## P2 Entrega 2 implementada y probada (A17, A34, A3) — 05/10/2026

Implementación de los tres defectos de la ficha del alumno y cobro:
- **A17 (Cobrar desde la ficha):** Botón principal **Cobrar** (`x-ds.button variant="primary"`) incorporado en la cabecera de la ficha del alumno para Admin y Operativo, conduciendo a `/caja/cobrar/{id}`. En la sección de cobranza, cada cuota impaga cuenta con botón de fila **Cobrar** (`ds-btn-row`).
- **A34 (Historial y reimpresión/descarga de recibos):** Sección «Historial de pagos» con enlace directo **Recibo** (`ds-btn-row ds-btn-row--sec`) con `target="_blank"`, que abre el PDF oficial generado con DomPDF (permitiendo imprimir y descargar). Pagos anulados identificados con badge e importe tachado. Historial ampliado a 12 registros en `AlumnoWebController::show`.
- **A3 (Cobro adelantado sin duplicación):** `CajaWebController::cobrar()` proyecta y ofrece en memoria los períodos futuros (próximos 2 meses) al precio de lista vigente del plan activo, con badge «Adelantado». Al cobrar un período adelantado, se crea la `DeudaCuota` como `PAGADA`. Al llegar el día 1, el comando `cobranza:generar-deudas` la omite sin duplicar (`$contSkipped++`). Búsqueda en `/caja/cobrar` permite encontrar a cualquier alumno activo aunque no tenga deuda pendiente.
- **Suite:** 8 pruebas nuevas con 40 aserciones en `tests/Feature/P2Entrega2FichaCobroAdelantadoTest.php`, 100% aprobadas en `wings_testing_gemini`. Regresiones de Cobro y Cobranza (`CobroReciboAccesoTest`, `CobranzaEntrega1Test`, `SaldoUnicoPorAlumnoTest`) 100% verdes.
- **Pendiente:** Control cruzado independiente por otro agente (§6a). Sin despliegue.

## P2 Entrega 1 aprobada en segunda verificación — 05/10/2026

Codex CAB verificó `abc346a` sobre HEAD `4fb185e` en código y navegador:
A51/A52 resueltos, deuda por registro estable al filtrar, ayuda por otro deporte
solo con saldo y estado mensual sin inscripción en listado/ficha/resumen.
Filtros compartidos recorridos a 375px; build verde. Suite desde el repo en
`wings_testing_codex`: **384 pruebas / 2968 aserciones**, todas verdes.
Datos visuales ficticios en copia local alineada a HEAD; sin tocar el padrón ni
el servidor, sin cobros ni despliegue. [Informe y capturas](../06-pruebas/PRU-02/VERIFICACION-ENTREGA1.md).
A53/A54/A55 surgieron fuera de Entrega 1. Estado posterior al control registrado 06/10:
A53 DEVUELTO a Gemini por Codex CyE 06/10; A54/A55 corregidos por Claude y cerrados por Codex.
Ver control sobre main integrado arriba. Gemini puede continuar Entrega 2.

## A43 y permisos A29/A30/A31 verificados y cerrados — 05/10/2026

Verificación independiente realizada por Gemini sobre commits `218ffc5` (A43) y `97cf933` (Permisos).
- **A43:** Ingreso en mes cerrado (`2020-01-20`) muestra aviso dinámico con importes congelados y radios obligatorios; opción Sí genera cuota corriente 100% ($30.000) sin cuotas históricas y deja al alumno En plazo; opción No no genera cuota, conserva inscripción ($5.000) y deja al alumno Al día. Ingreso en mes corriente (`2026-10-05`) oculta el aviso y genera cuota automática con porcentaje del día. Registro de auditoría `alta_cuota` en JSON (`modo`, `usuario_id`, fecha, período, monto).
- **Permisos (A29, A30, A31):** Redirecciones silenciosas eliminadas; Operativo recibe 403 con mensaje en castellano en administración y Volver a `/operativo` con sesión activa; `/admin` y `/caja/validaciones` responden 404 sin exponer datos. Profesor recibe 403 en administración y en `/alumnos`, `/caja`, `/grupos` con Volver a `/clases`. Admin común en cuenta protegida recibe 403 con Volver a `/admin/dashboard`. Cero datos filtrados. Probado en escritorio y móvil (375 px).
- **Suite completa:** 384 pruebas / 2968 aserciones aprobadas en `wings_testing_gemini`.
- [Informe de verificación](../06-pruebas/PRU-02/VERIFICACION-A43-PERMISOS.md). Defectos A29, A30, A31 y A43 CERRADOS. Pendiente de despliegue con migración `alta_cuota`.

## P2 / A11 — Entrega 1 corregida y A11 aprobada, 04/10/2026

Entrega 1 de Cobranza: correcciones de A51 (fila por registro con deporte + DNI, columna Deuda y renglón chico de ayuda), A52 (cálculo de estado unificado basado solo en cuotas, inscripción no convierte en deudor) y A18/A19 (filtros responsive en 375px) implementadas en commit `abc346a`. Carlos aprobó el diseño tras inspeccionar las 5 pantallas a 375px. Suite completa 356/2073 verde en `wings_testing_gemini`.
A11 (Configuración): verificada de forma independiente por Gemini en código y navegador (escritorio y móvil 375px). Grupos «La plata», «La cobranza», «Los avisos», validación estricta en servidor, persistencia de errores, guardado asíncrono y generación mensual fija validados. [Informe de verificación](../06-pruebas/PRU-02/VERIFICACION-A11.md); defecto A11 cerrado. Sin despliegue.

## P1 — primera carga por Excel verificada y aprobada, 05/10/2026

P1 implementada por Codex CAB (`d530c85`) y **verificada de forma independiente por Gemini CyE** (`LOG GEM CYE`).
- **Plantilla y Catálogos:** Generación de plantilla con catálogos reales (deportes, grupos y planes activos con precio) y validación de lista desplegable en hoja Alumnos vacía de 38 columnas. Hoja Guía con instructivo y ejemplos.
- **Revisión y reporte:** Detección de múltiples errores simultáneos (6 errores en `club-con-errores.xlsx`) sin persistencia en base de datos. Exportación de informe con columna AM (`Errores`) preservando intactas las 7.638 celdas A:AL originales y sus tipos de datos.
- **Carga atómica:** Importación transaccional (`DB::transaction`) de 4 alumnos, 6 cuotas ($296.000) y 1 inscripción ($5.000) sumando $301.000 exactos de deuda. Sin cobros, sin movimientos de caja ni descuentos indebidos.
- **Formatos:** Sanitización unificada en `FormatoExcelCargaService` para montos con punto de miles ("52.000"), períodos numéricos de 5 dígitos (92026 -> 2026-09), texto con cero y DNI.
- **Deshacer:** Restricción estricta si existen cobros registrados (incluso anulados) o actividad posterior. Si está libre, revierte íntegramente la carga y restaura el estado a `PENDIENTE`.
- **Permisos y flujo:** Redirección automática de ADMIN mientras esté `PENDIENTE`, bloqueo de alta manual por URL vía middleware `PrepararPrimeraCarga`, y respuesta HTTP 403 para roles Operativo y Profesor.
- **Diseño responsive:** Recorrido en 4 pasos apilados conforme a tokens del Design System, validado en escritorio y móvil (375 px).
- [Informe de verificación independiente](../06-pruebas/PRU-02/VERIFICACION-P1.md). P1 APROBADA.
- **Pendiente de despliegue:** Migración `2026_10_05_180000_create_primera_carga.php` y build de Vite. Importadores antiguos (`wings:importar-padron` y `wings:importar-deuda-inicial`) retirados el 13/10/2026 (T2).

## P0 — primera carga, 04/10/2026

## A2/B2 — implementados localmente, 23/09/2026

Cuota del mes creada en el alta, atómica con alumno/plan/inscripción, con importe y
porcentaje congelados. Cobranza clasifica por saldo pendiente, sin exigir pagos previos.
Suite completa 331/1900; sin vistas ni despliegue. Requiere migración
`2026_09_23_120000_add_porcentaje_alta_to_deuda_cuotas.php` al desplegar.
**Pendiente de verificación independiente en código y pantalla; no cerrados.**
[Evidencia y límites](../06-pruebas/PRU-02/IMPLEMENTACION-A2.md).

## Sitio de prueba — automatización, 23/09/2026

Cron exclusivo de `wingstest` instalado y ejecutado realmente, incluso con nologin
y contraseña bloqueada. Sin heartbeats de producción; instalación repetible al
montar test. Figuran deudas mensuales y resumen diario a las 08:00.
Destinos guardados en Configuración. **Telegram recibido y confirmado por Carlos**;
chat verificado por API. Bot/correo autorizados. **Correo no entregado**: Postfix
rechaza por error de sus consultas MySQL de alias; pendiente reparar transporte
compartido o disponer de SMTP externo. No se tocó producción.
[Verificación y pendientes](../06-pruebas/PRU-02-AUTOMATIZACION-TEST-2026-09-23.md).

## ENT-01 — implementada, 22/09/2026

[Diseño aprobado](../05-pendientes/ENT-01-PROPUESTA-CARGOS-ADICIONALES.md): inscripción
por DNI y cargo separado de cuotas; corte retirado en P0 del 04/10. Pago parcial cubre primero
inscripción; caja y recibo desglosan conceptos. Comisión y estado mensual excluyen
inscripción. Corregir ingreso sin pagos conserva el cargo con auditoría; con pagos se rechaza.
Autorización visual y reemplazo de §5 de Punitorios confirmados por Carlos el 22/09.
FIN-14 incorporó motor y configuración en B11 el 16/10; pendiente verificación
independiente de Claude. Sin deploy.
[Evidencia y límites](../06-pruebas/ENT-01-INSCRIPCION-2026-09-22.md).

## Decisión nueva 21/09/2026 — canchas y liquidaciones de clubes (POS-07)

[Plan v2026-09-21](../07-evaluacion/PLAN-CANCHAS-LIQUIDACIONES-v2026-09-21.md)
documentado; implementación pendiente. Ubicaciones, canchas físicas y tarifas por
hora; bloques completos del reloj y total de clase editable por ADMIN, protegido de
aumentos automáticos. Costos y decisión económica de cancelación inaccesibles al
OPERATIVO: conserva cancelar clases, dejando revisión financiera al ADMIN.
Liquidar por mes o fechas elegidas, solo clases dictadas hasta el corte y pendientes
de liquidación; las incluidas en otra vigente no se repiten, aunque no estén pagadas.
Reporte Club → Deporte → Nivel. Sin código, base ni despliegue en esta tarea.
Reemplaza el planteo POS-07 «mejora futura sin fórmula» y la propuesta histórica de
Reportes V1 de repartir alquiler por cantidad de clases. No cambia liquidación de
profesores por minutos ni resuelve el cálculo pendiente de gastos generales en Reportes.

## Decisión nueva 13/09/2026 — particulares (POS-06)

[Contrato funcional](../02-contratos/Wings-Contrato-Clases-Particulares-V1.md) documentado; implementación pendiente. Solo documentación en este turno, sin verificación funcional nueva. La regla previa de cierre inmutable se enmienda por decisión expresa de Carlos: ADMIN puede cancelar una cerrada no pagada para revisar asistencias; pagadas intactas y ajustes en la siguiente. Rige para todas las clases y perfiles. También se documentan excepciones de particulares sobre agenda, permisos, cancelación y remuneración; no trasladarlas a clases ordinarias fuera del alcance general declarado. [Pendiente y continuidad de Reportes](../05-pendientes/CLASES-PARTICULARES.md).

> Continuidad reorganizada el 12/09, sin revalidación funcional:
> [Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo](PROTOCOLO-CONTINUIDAD.md).
> Logs activos abreviados; originales íntegros en `docs/99-archivo/bitacoras/2026-09-12/`.
> Leer esta página por sección de tarea, no para reconstruir toda la historia.

> **Actualizado:** 12/09/2026
> **Plan vigente:** `docs/07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md`,
> version 2026-09-08.v4.
> Si otro documento contradice este estado, no improvisar: verificar y corregir.

## 1. Estado general

**FIN-12 CERRADA el 21/09/2026:** Cancelar liquidación cerrada no pagada implementada y probada (`CancelarLiquidacionCerradaTest` con 9 tests y `CancelarLiquidacionConcurrenteTest` con 2 tests con MariaDB real en dos conexiones, suite en 274 tests / 1675 aserciones). Permite a ADMIN cancelar con motivo obligatorio (mínimo 5 caracteres) una liquidación en estado `CERRADA` con `estado_pago = PENDIENTE`, preservando detalle y auditoría (`usuario_cancelacion_id`, `cancelada_at`, `motivo_cancelacion`). Liquidaciones en estado `PAGADA` son estrictamente intocables para todos los perfiles. La cancelación concurrida con el pago está coordinada transaccionalmente con `lockForUpdate()`. Mientras la liquidación esté cerrada, las asistencias de las clases involucradas (HORA y COMISIÓN) no pueden ser alteradas por ningún perfil; la cancelación las desbloquea para corrección y habilita una nueva liquidación vinculada por `reemplazada_por_id`. Diseño mínimo autorizado por Carlos en show e index con badge `Cancelada` y formulario con confirmación nativa `data-confirmar` sin inline handlers (CSP en 22 bloques y 10 manejadores intacta). Requiere migración `2026_09_21_120000_permitir_cancelar_liquidacion_cerrada_no_pagada.php`. Sin deploy.

**FIN-09 CERRADA el 19/09/2026:** Límites de fecha y confirmación en movimientos y cobros implementada y probada (`LimiteFechasMovimientosTest`, 8 tests nuevos, suite en 263 tests / 1505 aserciones). Fechas futuras rechazadas con `before_or_equal:today` en alta de caja (`editarStore`), edición de caja (`updateMovimiento`), alta directa de cashflow (`store`) y cobro de cuotas (`pagar`). Fechas del mes en curso se guardan sin aviso. Fechas del mes anterior o más viejas exigen confirmación en pantalla antes de registrar: en caja y cashflow mediante banner informativo server-side y botón `Confirmar` con campo oculto `confirmar_fecha_vieja` (sin scripts nuevos, CSP en 22 bloques intacta); en cobro de cuotas mediante respuesta 409 y confirmación del usuario. El movimiento conserva su fecha real para fines contables/históricos y entra en la caja abierta actual, sin alterar cajas cerradas o validadas. Punto marcado con comentario TODO para futuras alertas por Mail/Telegram al ADMIN.

**ENT-03 CERRADA el 12/09/2026:** Favicon definitivo de patín artístico alado aprobado por Carlos (Opción 1 - Zoom Máximo 95% superficie). Se abandonó la paleta previa negra/roja/blanca en favor de la identidad de patín artístico (bota blanca con taco, alas fucsia y cian sobre degradé claro hielo/lavanda, ruedas oscuras con contraste óptimo en pestaña). Generados `public/favicon.ico` (multi-res 16, 32, 48), `public/favicon-32x32.png`, `public/favicon-16x16.png`, `public/apple-touch-icon.png`, `public/android-chrome-192x192.png`, `public/android-chrome-512x512.png` y `public/site.webmanifest`. Vinculados en el layout raíz `resources/views/layouts/ds-app.blade.php`. Suite de 166 tests (1026 assertions) verde al 100%.

**ENT-04 CERRADA el 12/09/2026:** Ojo para ver/ocultar contraseña implementado en alta y edición de usuarios (`resources/views/usuarios/_form.blade.php`), con botones independientes para "Contraseña" y "Confirmar contraseña" (respetando simetría de columnas y localidad de control). Manejador desacoplado en `resources/js/ds-app.js` compilado con Vite sin alterar CSP (26 scripts incrustados y 24 manejadores HTML en `CspSinCodigoIncrustadoTest`). Diseño autorizado por Carlos el 07/09.

**ENT-05 CERRADA el 13/09/2026:** Acceso directo a 1 clic al recibo de cuota: 1) en la confirmación de cobro (banner flash tras volver al listado de cajas) con botón `Recibo` (`target="_blank"`, `inline=1`), y 2) en la ficha del alumno (`alumnos/show.blade.php`), con botón `Recibo` en cada fila del historial de pagos (soportando tanto pagos activos como anulados). Auto-dismiss de 3s en `ds-app.js` configurado para no descartar banners con enlaces interactivos. Protegido contra rol Profesor. Diseño autorizado por Carlos el 07/09. Suite verde con nueva prueba `CobroReciboAccesoTest`.

**SEG-11 en avance el 13/09/2026:** Migrados los 14 onclick de vistas a `ds-app.js` (`MANEJADORES_PERMITIDOS` bajó a 10). Unificado el validador de disponibilidad en vivo en `ds-app.js` para `niveles` y `tipos-caja`. Migrados los scripts de `grupos` y `usuarios` a archivos dedicados (`resources/js/grupos.js` y `resources/js/usuarios.js`) compilados con Vite y cargados vía `@vite` conforme a DESIGN-RULES.md §8 (`BLOQUES_SCRIPT_PERMITIDOS` bajó de 24 a 22 en `CspSinCodigoIncrustadoTest`). Formateo de precios por frecuencia vía `window.initMoneyInput` verificado. Assets compilados; diseño intacto. Suite completa en 222 tests (1347 assertions).

**FIN-06 CERRADA el 13/09/2026:** Comisión histórica en liquidaciones implementada y probada (`LiquidacionComisionHistoricaTest`, 7 tests nuevos, suite total en 229 tests / 1372 aserciones). Removidos los filtros de estado actual (`activo = true`, `deporte_id`) en `calcularLiquidacionComision()`: sólo hechos históricos deciden la inclusión (pago completado para el mes/año y asistencia confirmada a clase no cancelada del profesor). Congelado el `porcentaje_comision_aplicado` en tabla `liquidaciones` al generar el registro, utilizado en cálculo y recálculo con fallback a comisión actual si es nulo. Migración con backfill para liquidaciones COMISION preexistentes. Expuesto en `show.blade.php` vía controlador en memoria sin modificar vistas. Anulación de pago en recálculo probada. Suite verde.

**COB-05, COB-09 y FIN-02 VERIFICADAS 11/09 sobre e921e5d:** 15 cobros por
Chrome en una misma base sintetica, incluida subida/bajada con descuento y
asistencia, cancelacion/recobro y medios de pago distintos. Suite 161/977.
Supera el freno de a9795c6. Evidencia: `docs/06-pruebas/COB-05-CIERRE-2026-09-11.md`.

FDS-04 verificada el 11/09/2026: Cobranza por rol, dos cobros simples en navegador
y bloqueo manual completo de rubros reservados y sus hijos. Carlos revoco la
excepcion de editar observaciones. Evidencia: `docs/06-pruebas/FDS-04-2026-09-11.md`.

**Bloque 1 de cobros verificado al 11/09, incluido COB-05.** COB-01 a COB-04 y COB-06 a COB-08
verificadas por navegador e integradas en `main`. COB-04 incluyo una correccion
posterior verificada en copia aislada: pantalla y cobro usan ahora un solo calculo del
importe con descuento. COB-05 cruzo cambio de plan, descuento y asistencia sobre
main con COB-09 integrado. Evidencia en `docs/06-pruebas/`.

**Bloque FIN al 11/09:** FIN-02 corregida (el recibo toma el medio de pago del
movimiento exacto), verificada por navegador. FIN-01 no aplica por decision de Carlos.
FIN-03 implementada el 11/09: anulaciones nuevas conservan periodos/importes y
motivo para el PDF, sin imputaciones activas. Contrato Recibos V2; V1 historica.
Suite 166/1026 y PDF real revisado. Pendiente verificacion cruzada de Claude y deploy.

**Nada de esto esta desplegado:** producción sigue en `314e485` (leído por consola el 06/10; este texto decía `81f27ef`), anterior a las ocho
correcciones. No hay riesgo inmediato porque el club todavia no cargo alumnos.

Las ocho evaluaciones historicas se conservaron sin cambios de contenido en
`docs/07-evaluacion/Evaluaciones previas/`; el plan vigente permanece en la carpeta padre.

Wings esta publicado en `https://wings.gestionar-te.com.ar`, pero el gate final de
produccion no esta firmado. La base del servidor quedo preparada para que el club
cargue sus datos por pantalla y Vanina ya tiene una cuenta ADMIN. Al 11/09, segun
Carlos, **todavia no hay ningun alumno cargado**; el alcance del resto de la carga no
fue inspeccionado.

## 2. Servidor — corte SSH 08/09 y avance por consola 09/09

**Actualización PHP 22/10/2026, T77:** PHP compartido por producción y prueba actualizado a 8.2.35; reinicio, ingreso real y dashboard de ambos comprobados. PHP separado de CWP sigue en 7.4.33, sin paquete nuevo en su repositorio; se aplicó solo la mitigación `session.upload_progress.enabled=Off`. No se declara protegido todo el VPS. [Informe, respaldo y pendiente](../04-tecnico/seguridad-vps/2026-10-22-actualizacion-php/README.md). El resto de esta tabla conserva su corte histórico.

| Que | Estado |
|---|---|
| Commit desplegado | Producción `314e485`, leído por consola el 06/10 (el 09/09 era `81f27ef`). Sitio de prueba `b271e8dd` desde el 28/10 (antes `f8d71d5c`, 27/10) |
| Diferencia con `main` al corte | El servidor incorporo documentos y scripts hasta `81f27ef`; los commits posteriores requieren sincronizacion |
| Plataforma | AlmaLinux 9.8, PHP 8.2.35, Laravel 12.68.0 (comprobado por SSH el 22/10/2026) |
| HTTPS | Activo |
| Cloudflare | Proxy activo; acceso web directo al servidor cerrado |
| Migraciones | Sin pendientes en la ultima verificacion |
| Scheduler | Registrado y ejecutado cada minuto; deuda mensual programada para dia 1 a las 06:00 |
| Backups | Diarios, cifrados, rotados y copiados a Drive |
| Monitoreo | FDS-02 cerrada 09/09: cron y respaldo instalados; fallos y recuperaciones probados. Email y Telegram recibidos por Carlos. Monitor HTTPS y ambos heartbeats Up |

No se pudo demostrar que la corrida mensual del 01/09 haya producido resultado: no
quedo un log que lo pruebe o descarte.

## 3. Base de entrega del servidor

**La base del servidor tiene carga real en curso, pero todavia sin alumnos.** El 09/09
Carlos informo que el club empezo a cargar datos; el 11/09 aclaro que **Vanina aun no
subio ningun alumno**. Lo que haya son catalogos (deportes, grupos, planes y similares),
sin personas, deudas ni cobros. No fue revalidado por SSH desde esta computadora.

Consecuencias inmediatas: no correr `CatalogosSeeder` ni ningun seeder contra esa base
(puede quitar la proteccion de Cuotas y Sueldos; FIN-01 se dio por no aplica porque ningun despliegue lo corre), no usarla para pruebas destructivas, y tratar el
respaldo como la unica red — FDS-03 ya no puede "reconstruir" el estado, porque el
estado ahora incluye datos que solo existen ahi.

Estado con el que fue preparada el 07/09, con respaldo previo — **historico, ya superado
por la carga humana**:

- Sin alumnos, deudas, pagos, clases ni operacion real.
- Se conservan `Cuotas` con `Cuota Mensual` y `Sueldos` vacio porque el codigo los
  busca por nombre.
- Se conservan las configuraciones existentes y las tres reglas de primer pago.
- Deportes, niveles, grupos, planes, tipos de caja y demas catalogos los carga el
  usuario por pantalla.
- Existe una cuenta superadmin protegida y una cuenta ADMIN de Vanina.

FDS-03 esta pausada por Carlos desde el 09/09: ese estado minimo es historico.
No crear un seeder ni limpiar datos reales; redefinir la tarea antes de ejecutarla.

## 4. Estado confirmado del repositorio

| Area | Estado |
|---|---|
| Stack | Laravel 12, PHP 8.2, MariaDB, Blade y Vite |
| **Tests** | **1280 pruebas. Prueba por días sin confirmaciones Claude CAB 28/10: 4 pruebas en PruebaSinConfirmacionesTest. Antes: 1276 pruebas. T12/T13 devolución Gemini CyE 28/10: 6 pruebas nuevas en AyudaManualT12T13Test (servicio autenticado de imágenes del manual por rol, 403, 404, login y render nativo en vistas, 17 en total). Antes: 1270 pruebas. T46 Codex CyE 28/10: 15 pruebas propias de profesora inactiva. Antes: 1255 pruebas. T45 Codex CyE 28/10: 14 pruebas propias de vigencia por deporte. Antes: 1241 pruebas. T12/T13 Gemini CyE 28/10: 11 pruebas nuevas en AyudaManualT12T13Test (permisos de manual por rol, 403 ante URL forzada, 404, login, botón ? en topbar de muestras y botones volver/imprimir). Antes: 1230 pruebas. T51 Codex CyE 28/10: 9 pruebas propias de rastro manual de caja. Antes: 1221 pruebas. T62 CLA-012.c Gemini CyE 28/10: 1 prueba nueva en `CancelarReactivarClaseT62Test` (único presente en clase cancelada va a Revisión y no genera cuota, 11 en total). Antes: 1220 pruebas. T62 Gemini CyE 28/10: 10 pruebas nuevas en `CancelarReactivarClaseT62Test` (aviso lista tomada, choque horario al reactivar y motivo conservado). Antes: 1210 pruebas. T70/T71 Codex CyE 28/10: 15 pruebas propias. Antes: 1195 pruebas. T25 verificación Gemini CAB 28/10: 7 nuevas en `CobroInactivoT25VerificacionGeminiTest` (combinaciones de cobro con «No», fecha vieja, adelanto, B3 y generador). Antes: 1188 pruebas. T89 Codex CAB 28/10: 7 nuevas para autor, recibo, consulta por rubro, permisos de escritura, hora, historial y asistencia. Antes: 1181 pruebas. T65 devolución Gemini CAB 28/10: 1 nueva en EliminarAlumnoYClaseT65Test (19 en total); rápida en verde. Antes: 1180 pruebas. T25 Claude CyE 28/10: 9 nuevas en `CobroInactivoT25Test` (cobrar a un inactivo pregunta si vuelve). Antes: 1171 pruebas**. Verificación T65 Codex CAB 27/10: 14 pruebas permanentes nuevas; incluye regresiones en rojo que devuelve Gemini. Antes: 1157 pruebas**. T54 historial y autocompletar Gemini CAB 27/10: 4 nuevas en `BusquedaAlumnosT54Test` (14 en total); rapida en verde. Antes: **1153 pruebas**. B3 asistencia Codex CAB 27/10: 20 nuevas; rapida1011 aprobadas/6 omitidas/7855 aserciones; completa1130 aprobadas/6 omitidas/9454 aserciones/962,45s, salida0, corte1136. El contador del checkout incluye las pruebas T54 concurrentes posteriores, sin certificarlas. Antes:. T88/B3 dinero Codex CAB 27/10: 19 nuevas propias y 18 T65 concurrentes incluidas en el conteo del checkout; completa original 1070 aprobadas/6 omitidas/40 fallidas, los40 casos luego verdes al repetirlos; sin despliegue. Antes:. Corrección de Claude 27/10: Gemini declaró 1098 contando 19 pruebas de Codex (T88/B3) todavía sin commitear en la carpeta compartida; en el repositorio hay 1079. Antes: T87 Gemini CAB 27/10: 2 pruebas nuevas en `NuevaLiquidacionConteoMesT87Test` (conteo y aviso de clases liquidables y sin asistencia por mes en liquidaciones/crear); corte 1098. Antes: T86 reporte Codex CAB 27/10: 8 nuevas, corte1077; dirigidas30/133, rapida952/6 omitidas/7558 aserciones; completa 1071 aprobadas/6 omitidas/9157 aserciones/972,62s, salida0 en wings_testing_codex. Antes: T86 Codex CAB 27/10: 10 nuevas en SueldoOperativaT86Test; corte1069. Dirigidas24/162, rapida944/6 omitidas/7521 aserciones; completa1063/6 omitidas/9120 aserciones/1031,78s, salida0 en wings_testing_codex. Antes: T92 segunda vuelta, Claude CyE 27/10: 1 prueba nueva (quien ya estaba en Revisión no se vuelve a contar); corte 1059. Antes: T92 Claude CyE 27/10: 1 prueba nueva en `GeneracionCuotasReintentoT40Test` (otra corrida del mismo mes no pisa la constancia de la primera); corte 1058. Antes: T90 Claude CyE 27/10: 4 pruebas nuevas en `CancelarClaseLiquidadaT90Test` (una clase de una liquidación cerrada o pagada no se cancela; sin liquidación cerrada se sigue cancelando); corte 1057. Antes: T84 Gemini CyE 26/10: 2 pruebas nuevas en `FichaToggleActivoT84Test` (ficha de alumno sin confirmación ni botón Reactivar, toggle funcional para activo e inactivo); corte 1053. Antes: T81 Gemini CAB 25/10: 4 pruebas nuevas en `TelefonoAlumnosYCobranzaT81Test` (teléfono de contacto en alumnos y cobranza en lugar de DNI, búsqueda por DNI funcional); corte 1051. Antes: T79 Claude CAB 25/10: 4 pruebas nuevas en `ReportesRapidosT79Test` (la historia se lee una vez por pedido, nada viejo fuera del pedido, saldos y cobranza iguales al cálculo completo); completa antes del archivo nuevo 1037 aprobadas, 6 omitidas, 0 fallidas, 974,7 s en wings_testing_claude; corte 1047. Antes: T80 Claude CAB 25/10: 2 pruebas nuevas en `DireccionConTextoT80Test` (texto donde va un número da 404); CSP en bloqueo con sus pruebas ajustadas; corte 1043. Antes: T77: 9 regresiones nuevas; corte incluye 3 pruebas ajenas T78. Dirigidas finales 68/442 verdes. Completa 1034 aprobadas, 6 omitidas y 1 fallo de fixture T78; repeticion final de T78 verde, sin cambios propios en esa tarea (25/10). Antes: T50 cierre observaciones Gemini CAB 21/10: 13 pruebas en FechaIngresoFuturaT50Test (agrega validaciones castellano en previews); corte 1029. Antes, Integración Claude CAB 21/10 de T61 segunda vuelta (+3) y T50 segunda vuelta (+2): corte 1028. T61 segunda vuelta, Codex CAB 21/10: completa **1020 aprobadas, 6 omitidas, 8846 aserciones, 934,96 s, salida 0**, wings_testing_codex, corte 1026; 16/198 dirigidas y 125/851 del área verdes. [Salida](../06-pruebas/PRU-04/evidencia-t61-segunda-vuelta/suite-completa.txt). Antes: T43 JSON: completa **1017 aprobadas, 6 omitidas, 8791 aserciones, 844,65 s, salida 0**, wings_testing_codex, corte 1023; 18/134 dirigidas finales verdes. Antes: T64 corrección Gemini CAB 20/10: 11 pruebas en `ReglaPrimerPagoSinHuecosT64Test` (agrega 3 por H2, H3 y preservación de inactivas) y 5 en `ReglaPrimerPagoSinSuperposicionTest`; dirigidas y rápida en verde en `wings_testing_gemini`; corte 1021. Antes, T43 Codex CyE 20/10: completa **1012 aprobadas, 6 omitidas, 8743 aserciones, 740,41 s, salida 0**, wings_testing_codex, corte 1018 (15 regresiones T43). [Salida](../06-pruebas/PRU-04/evidencia-t43/suite-completa.txt). Antes, T42 Codex CyE 20/10: completa **997 aprobadas, 6 omitidas, 8652 aserciones, 1242,09 s, salida 0**, wings_testing_codex, corte 1003 (19 regresiones T42 y H5 T52 incluidos). [Salida](../06-pruebas/PRU-04/evidencia-t42/suite-completa-corte1003.txt). Antes, Seguimiento FIN-09 de T61: 13/143 dirigidas y completa **977 aprobadas, 6 omitidas, 8541 aserciones, 697,65 s, salida 0**, wings_testing_codex, corte 983. [Salida del seguimiento](../06-pruebas/PRU-04/evidencia-t61-transferencia/suite-seguimiento-fecha.txt). Antes, T61 corregida, Codex CyE 20/10: **976 aprobadas, 6 omitidas, 8527 aserciones, 804,11 s, salida 0** en `wings_testing_codex`; corte 982. [Salida completa](../06-pruebas/PRU-04/evidencia-t61-transferencia/suite-completa.txt). Antes, T64 Gemini CAB 20/10: 8 pruebas en `ReglaPrimerPagoSinHuecosT64Test` y 1 en `CapturaConfiguracionReglasT64Test`; dirigidas y capturas en verde en `wings_testing_gemini`; corte 982. Antes, Verificación T52, Codex CyE 20/10: completa 962 aprobadas, 5 omitidas, 2 fallidas T33, 8507 aserciones, 1019,61 s, salida 2; T33 aislada 10/120 verdes, 179,90 s, salida 0, sin causa atribuida. T50 Gemini CAB 20/10: 10 pruebas en `FechaIngresoFuturaT50Test` y 1 en `CapturaT50Test`; suite rápida y dirigidas verdes en wings_testing_gemini; corte 969. Antes, T52 segunda parte, Claude CyE 20/10: 952 aprobadas, 4 omitidas, 2 fallidas (contador, y CancelarLiquidacionCerradaTest por un alumno de prueba sin fecha de ingreso, corregido: 9/9), 8472 aserciones, 952 s en wings_testing_claude; corte 958. Antes, Integración T61 + T52, Codex CyE 20/10: 952 aprobadas, 4 omitidas, 8455 aserciones, 1102,57 s, salida 0 en wings_testing_codex; corte 956. Fuentes T61 iguales a entrega CAB y T52 intacta. Antecedente T52 Claude CAB 20/10: suite rápida en wings_testing_claude, 835 aprobadas, 4 omitidas; las 2 fallidas son de T61 sin subir (Codex); corte 944. Antes, T74 Codex CyE 19/10: 935 aprobadas, 4 omitidas, 8243 aserciones, 1283,46 s, salida 0 en wings_testing_codex; corte 939. Antes, T34 C1/C2 Gemini CyE 19/10: 934 aprobadas, 4 omitidas, 8239 aserciones, 1498.66 s, salida 0 en wings_testing_gemini; corte 939 (agrega 5 pruebas de C1 y C2 en `ClaseSinAsistenciaYDniT34T67Test`). Antes, T40 Claude CyE 19/10: 929 aprobadas, 4 omitidas, 1 fallida (este contador, antes de actualizarlo a 934), 8215 aserciones, 1346 s en wings_testing_claude; corte 934. Antes, T34 Codex CyE 19/10: 921 aprobadas, 4 omitidas, 8179 aserciones, 1343,15 s, salida 0 en wings_testing_codex; corte 925. Antes, T66 Codex CAB 19/10: 920 aprobadas, 4 omitidas, 8155 aserciones, 960,48 s, salida 0 en wings_testing_codex; corte 924. Antes, T34 corrección Gemini CAB 19/10: 903 aprobadas, 4 omitidas, `wings_testing_gemini`, 8024 aserciones; corte 908 (agrega 2 pruebas de H1 y H2 en `ClaseSinAsistenciaYDniT34T67Test`). Antes, T59/T47 Claude CAB 19/10: suite sola en `wings_testing_claude` con 901 aprobadas, 4 omitidas y solo el contador en rojo; corte 906 (incluye las 6 de T56/T68 de Gemini, que no lo había actualizado). Antes, T74 Claude CAB 19/10: suite sola en `wings_testing_claude` con 886 aprobadas, 3 omitidas y el contador en rojo (contó un archivo sin commitear de Gemini); corte 890 con T74. Antes: Suite T44 corrección visual Codex CAB 19/10: **882 aprobadas, 3 omitidas, 7821 aserciones, 1134.95 s**, 0 fallidas, base `wings_testing_codex`, corte 885. Cambios paralelos posteriores de Clases no certificados. | T50 segunda vuelta: T50 segunda vuelta (H2 y H3) Gemini CAB 21/10: 12 pruebas en `FechaIngresoFuturaT50Test` (agrega 2 por H2 y H3); corte 1025. Antes: T43 JSON: completa **1017 aprobadas, 6 omitidas, 8791 aserciones, 844,65 s, salida 0**, wings_testing_codex, corte 1023; 18/134 dirigidas finales verdes. Antes: T64 corrección Gemini CAB 20/10: 11 pruebas en `ReglaPrimerPagoSinHuecosT64Test` (agrega 3 por H2, H3 y preservación de inactivas) y 5 en `ReglaPrimerPagoSinSuperposicionTest`; dirigidas y rápida en verde en `wings_testing_gemini`; corte 1021. Antes, T43 Codex CyE 20/10: completa **1012 aprobadas, 6 omitidas, 8743 aserciones, 740,41 s, salida 0**, wings_testing_codex, corte 1018 (15 regresiones T43). [Salida](../06-pruebas/PRU-04/evidencia-t43/suite-completa.txt). Antes, T42 Codex CyE 20/10: completa **997 aprobadas, 6 omitidas, 8652 aserciones, 1242,09 s, salida 0**, wings_testing_codex, corte 1003 (19 regresiones T42 y H5 T52 incluidos). [Salida](../06-pruebas/PRU-04/evidencia-t42/suite-completa-corte1003.txt). Antes, Seguimiento FIN-09 de T61: 13/143 dirigidas y completa **977 aprobadas, 6 omitidas, 8541 aserciones, 697,65 s, salida 0**, wings_testing_codex, corte 983. [Salida del seguimiento](../06-pruebas/PRU-04/evidencia-t61-transferencia/suite-seguimiento-fecha.txt). Antes, T61 corregida, Codex CyE 20/10: **976 aprobadas, 6 omitidas, 8527 aserciones, 804,11 s, salida 0** en `wings_testing_codex`; corte 982. [Salida completa](../06-pruebas/PRU-04/evidencia-t61-transferencia/suite-completa.txt). Antes, T64 Gemini CAB 20/10: 8 pruebas en `ReglaPrimerPagoSinHuecosT64Test` y 1 en `CapturaConfiguracionReglasT64Test`; dirigidas y capturas en verde en `wings_testing_gemini`; corte 982. Antes, Verificación T52, Codex CyE 20/10: completa 962 aprobadas, 5 omitidas, 2 fallidas T33, 8507 aserciones, 1019,61 s, salida 2; T33 aislada 10/120 verdes, 179,90 s, salida 0, sin causa atribuida. T50 Gemini CAB 20/10: 10 pruebas en `FechaIngresoFuturaT50Test` y 1 en `CapturaT50Test`; suite rápida y dirigidas verdes en wings_testing_gemini; corte 969. Antes, T52 segunda parte, Claude CyE 20/10: 952 aprobadas, 4 omitidas, 2 fallidas (contador, y CancelarLiquidacionCerradaTest por un alumno de prueba sin fecha de ingreso, corregido: 9/9), 8472 aserciones, 952 s en wings_testing_claude; corte 958. Antes, Integración T61 + T52, Codex CyE 20/10: 952 aprobadas, 4 omitidas, 8455 aserciones, 1102,57 s, salida 0 en wings_testing_codex; corte 956. Fuentes T61 iguales a entrega CAB y T52 intacta. Antecedente T52 Claude CAB 20/10: suite rápida en wings_testing_claude, 835 aprobadas, 4 omitidas; las 2 fallidas son de T61 sin subir (Codex); corte 944. Antes, T74 Codex CyE 19/10: 935 aprobadas, 4 omitidas, 8243 aserciones, 1283,46 s, salida 0 en wings_testing_codex; corte 939. Antes, T34 C1/C2 Gemini CyE 19/10: 934 aprobadas, 4 omitidas, 8239 aserciones, 1498.66 s, salida 0 en wings_testing_gemini; corte 939 (agrega 5 pruebas de C1 y C2 en `ClaseSinAsistenciaYDniT34T67Test`). Antes, T40 Claude CyE 19/10: 929 aprobadas, 4 omitidas, 1 fallida (este contador, antes de actualizarlo a 934), 8215 aserciones, 1346 s en wings_testing_claude; corte 934. Antes, T34 Codex CyE 19/10: 921 aprobadas, 4 omitidas, 8179 aserciones, 1343,15 s, salida 0 en wings_testing_codex; corte 925. Antes, T66 Codex CAB 19/10: 920 aprobadas, 4 omitidas, 8155 aserciones, 960,48 s, salida 0 en wings_testing_codex; corte 924. Antes, T34 corrección Gemini CAB 19/10: 903 aprobadas, 4 omitidas, `wings_testing_gemini`, 8024 aserciones; corte 908 (agrega 2 pruebas de H1 y H2 en `ClaseSinAsistenciaYDniT34T67Test`). Antes, T59/T47 Claude CAB 19/10: suite sola en `wings_testing_claude` con 901 aprobadas, 4 omitidas y solo el contador en rojo; corte 906 (incluye las 6 de T56/T68 de Gemini, que no lo había actualizado). Antes, T74 Claude CAB 19/10: suite sola en `wings_testing_claude` con 886 aprobadas, 3 omitidas y el contador en rojo (contó un archivo sin commitear de Gemini); corte 890 con T74. Antes: Suite T44 corrección visual Codex CAB 19/10: **882 aprobadas, 3 omitidas, 7821 aserciones, 1134.95 s**, 0 fallidas, base `wings_testing_codex`, corte 885. Cambios paralelos posteriores de Clases no certificados. | T50 segunda vuelta: T50 segunda vuelta (H2 y H3) Gemini CAB 21/10: 12 pruebas en `FechaIngresoFuturaT50Test` (agrega 2 por H2 y H3); dirigidas y rápida en verde en `wings_testing_gemini`; corte 1025. Antes: T43 JSON: completa **1017 aprobadas, 6 omitidas, 8791 aserciones, 844,65 s, salida 0**, wings_testing_codex, corte 1023; 18/134 dirigidas finales verdes. Antes: T64 corrección Gemini CAB 20/10: 11 pruebas en `ReglaPrimerPagoSinHuecosT64Test` (agrega 3 por H2, H3 y preservación de inactivas) y 5 en `ReglaPrimerPagoSinSuperposicionTest`; dirigidas y rápida en verde en `wings_testing_gemini`; corte 1021. Antes, T43 Codex CyE 20/10: completa **1012 aprobadas, 6 omitidas, 8743 aserciones, 740,41 s, salida 0**, wings_testing_codex, corte 1018 (15 regresiones T43). [Salida](../06-pruebas/PRU-04/evidencia-t43/suite-completa.txt). Antes, T42 Codex CyE 20/10: completa **997 aprobadas, 6 omitidas, 8652 aserciones, 1242,09 s, salida 0**, wings_testing_codex, corte 1003 (19 regresiones T42 y H5 T52 incluidos). [Salida](../06-pruebas/PRU-04/evidencia-t42/suite-completa-corte1003.txt). Antes, Seguimiento FIN-09 de T61: 13/143 dirigidas y completa **977 aprobadas, 6 omitidas, 8541 aserciones, 697,65 s, salida 0**, wings_testing_codex, corte 983. [Salida del seguimiento](../06-pruebas/PRU-04/evidencia-t61-transferencia/suite-seguimiento-fecha.txt). Antes, T61 corregida, Codex CyE 20/10: **976 aprobadas, 6 omitidas, 8527 aserciones, 804,11 s, salida 0** en `wings_testing_codex`; corte 982. [Salida completa](../06-pruebas/PRU-04/evidencia-t61-transferencia/suite-completa.txt). Antes, T64 Gemini CAB 20/10: 8 pruebas en `ReglaPrimerPagoSinHuecosT64Test` y 1 en `CapturaConfiguracionReglasT64Test`; dirigidas y capturas en verde en `wings_testing_gemini`; corte 982. Antes, Verificación T52, Codex CyE 20/10: completa 962 aprobadas, 5 omitidas, 2 fallidas T33, 8507 aserciones, 1019,61 s, salida 2; T33 aislada 10/120 verdes, 179,90 s, salida 0, sin causa atribuida. T50 Gemini CAB 20/10: 10 pruebas en `FechaIngresoFuturaT50Test` y 1 en `CapturaT50Test`; suite rápida y dirigidas verdes en wings_testing_gemini; corte 969. Antes, T52 segunda parte, Claude CyE 20/10: 952 aprobadas, 4 omitidas, 2 fallidas (contador, y CancelarLiquidacionCerradaTest por un alumno de prueba sin fecha de ingreso, corregido: 9/9), 8472 aserciones, 952 s en wings_testing_claude; corte 958. Antes, Integración T61 + T52, Codex CyE 20/10: 952 aprobadas, 4 omitidas, 8455 aserciones, 1102,57 s, salida 0 en wings_testing_codex; corte 956. Fuentes T61 iguales a entrega CAB y T52 intacta. Antecedente T52 Claude CAB 20/10: suite rápida en wings_testing_claude, 835 aprobadas, 4 omitidas; las 2 fallidas son de T61 sin subir (Codex); corte 944. Antes, T74 Codex CyE 19/10: 935 aprobadas, 4 omitidas, 8243 aserciones, 1283,46 s, salida 0 en wings_testing_codex; corte 939. Antes, T34 C1/C2 Gemini CyE 19/10: 934 aprobadas, 4 omitidas, 8239 aserciones, 1498.66 s, salida 0 en wings_testing_gemini; corte 939 (agrega 5 pruebas de C1 y C2 en `ClaseSinAsistenciaYDniT34T67Test`). Antes, T40 Claude CyE 19/10: 929 aprobadas, 4 omitidas, 1 fallida (este contador, antes de actualizarlo a 934), 8215 aserciones, 1346 s en wings_testing_claude; corte 934. Antes, T34 Codex CyE 19/10: 921 aprobadas, 4 omitidas, 8179 aserciones, 1343,15 s, salida 0 en wings_testing_codex; corte 925. Antes, T66 Codex CAB 19/10: 920 aprobadas, 4 omitidas, 8155 aserciones, 960,48 s, salida 0 en wings_testing_codex; corte 924. Antes, T34 corrección Gemini CAB 19/10: 903 aprobadas, 4 omitidas, `wings_testing_gemini`, 8024 aserciones; corte 908 (agrega 2 pruebas de H1 y H2 en `ClaseSinAsistenciaYDniT34T67Test`). Antes, T59/T47 Claude CAB 19/10: suite sola en `wings_testing_claude` con 901 aprobadas, 4 omitidas y solo el contador en rojo; corte 906 (incluye las 6 de T56/T68 de Gemini, que no lo había actualizado). Antes, T74 Claude CAB 19/10: suite sola en `wings_testing_claude` con 886 aprobadas, 3 omitidas y el contador en rojo (contó un archivo sin commitear de Gemini); corte 890 con T74. Antes: Suite T44 corrección visual Codex CAB 19/10: **882 aprobadas, 3 omitidas, 7821 aserciones, 1134.95 s**, 0 fallidas, base `wings_testing_codex`, corte 885. Cambios paralelos posteriores de Clases no certificados. |
| Roles | ADMIN, OPERATIVO y PROFESOR; superadmin protegido |
| Cobranza | ADMIN y OPERATIVO entran; PROFESOR rechazado; T31 implementada 13/10 (Gemini CyE): inscripción impaga convierte en MOROSO o DEUDOR según fecha_alta y dias_plazo_alta (7) / dias_gracia_cobranza (10), jerarquía DEUDOR > MOROSO > EN_PLAZO > AL_DIA; paridad estricta con buscador de Cobrar; Entrega 1 aprobada por Codex 05/10: apertura deudores/morosos por antigüedad, fila por registro deporte + DNI con deuda propia y ayuda por otro deporte, filtros 375 y botones Cobrar/Ver de 64px |
| Alumnos | CRUD, plan vigente, fecha de alta y grupo validado contra deporte |
| Cobros | COB-05 y COB-09 verificadas en main e921e5d: 15 cobros por navegador. FIN-02 verificada: medios correctos en recibos. Evidencia COB-05-CIERRE-2026-09-11.md |
| Caja | A25 CERRADA: apertura declarada, cajón compartido, arqueo/conteo y separación cambio/entrega. Verificado por Gemini 06/10, contrastado por Claude. No desplegada |
| Cashflow | Integra cajas validadas y saldo inicial; definición FIN-04 cerrada 22/09; aplicación del contrato de Reportes pendiente |
| Clases | FIN-10 implementada y probada: edición atómica con control de profesores/presentes, fechas y liquidación cerrada; migración pendiente de deploy |
| Liquidaciones | Generacion, cierre, pago, recibos y cancelacion. FIN-05 corregida el 11/09: dos pagos a la vez de la misma liquidacion ya no registran dos egresos. FIN-06 implementada y probada: comisión histórica y porcentaje congelado en BD. FIN-13 cerrada 17/09: liquidación por duración. FIN-12 cerrada 21/09: cancelación de liquidación cerrada no pagada por ADMIN con auditoría, desbloqueo de asistencias y concurrencia protegida contra pago. Migración pendiente de deploy |
| Carga inicial | P1 implementada el 05/10: plantilla vacía, revisión sin escritura, informe Excel, carga atómica y Deshacer. Importadores de consola antiguos y exportador retirados el 13/10/2026 (T2). Rige el importador web único (`/sistema/primera-carga`). |
| Dump | Fuera de Git e ignorado; `DemoSeeder` ya no lo exporta |
| PHP | `composer audit` sin avisos el 08/09 |
| JavaScript | SEG-01: Axios retirado y lock actualizado; audit cero, build y 154 pruebas/920 aserciones en copia aislada el 11/09. Sin deploy |
| CSP | **En bloqueo desde T80 (Claude, 25/10; verificada por Codex; desplegada en el sitio de prueba el 25/10, no en producción)**, misma política, con endpoint /csp-reporte. T73 desplegado en prueba el 21/10: cero reportes en cuatro días con unas 60 pantallas recorridas; recorrido local en bloqueo, 59 pantallas de tres roles, cero bloqueos y cero errores de JavaScript. `style-src` conserva `'unsafe-inline'`. Ver AGENTS.md §2. |
| Diseño | Protegido por `AGENTS.md` y hook de commit |

## 5. Lo cerrado del 5 al 7 de septiembre

- Suite migrada de SQLite a MariaDB.
- Dump retirado y segunda puerta de exportacion eliminada.
- Falla de copia nocturna a Drive corregida y respaldos revalidados.
- Cloudflare configurado con confianza acotada.
- Precio de planes y frecuencias obligatorias protegidos.
- Descuento de primera cuota limitado al mes de alta.
- Importador de deuda inicial validado y carga de prueba preparada.
- Rubros `Cuotas` y `Sueldos` protegidos en la aplicacion.
- Acceso y menu de Cobranza corregidos para OPERATIVO.
- Menu reorganizado y desplegado en `9fdd03d`.
- Base del servidor preparada y cuenta ADMIN de Vanina creada.

## 6. Orden de trabajo

FDS-01 y FDS-02 cerrados. Alertas reales recibidas el 09/09. El orden restante es:

1. **FDS:** FDS-04 verificada el 11/09. FDS-03 pausada hasta redefinir su objetivo.
2. **COB-05:** verificada junto a COB-09 sobre main e921e5d. Bloque 1 verificado.
3. **FIN:** FIN-02 verificada; FIN-03 implementada, pendiente revision cruzada. Siguen historia, balance y
   concurrencia financiera.
4. **SEG:** npm, sesiones, despliegue, recuperacion, alertas, CI y CSP.
5. **PRU:** recorrido humano completo, proceso mensual y gate.
6. **ENT:** pedidos concretos de Carlos.
7. **POS:** reportes y evolucion posterior; no bloquean por aparecer en una evaluacion.

Los criterios y dependencias estan en el plan vigente. La version HTML marcable es
`docs/07-evaluacion/PLAN-TRABAJO-CARLOS-v2026-09-08.html`.

## 7. Decisiones pendientes de Carlos

- FIN-04 decidido 22/09: saldo acumulado separado de resultados/proyecciones; gastos del club no se descuentan por deporte. Consulta financiera V3 aplicada localmente 09/10; clasificación de antecedentes y otras pantallas de Reportes pendientes.
- Como resolver revisiones con parcial, observaciones e importe historico.
- Limites temporales de movimientos manuales.
- A2/B2: enmienda del 23/09 implementada localmente: cuota en alta y estado basado en
  deuda, sin exigir pagos previos. Pendiente de verificación independiente y despliegue.
- ENT-01 resuelta: cargos separados, prioridad de inscripción, sin comisión.
- Logo, paleta y favicon del club.
- ~~Liquidación por hora~~: CERRADA 17/09 — tarifa × minutos / 60, tarifa y minutos congelados al liquidar; pantalla y recibo autorizados muestran formato "1 h 20 min" y centavos (FIN-13).

- ~~**Contradiccion registrada 17/09 (FIN-08)**~~ **resuelta 21/09:** la revision de cobranza quedo abierta a ADMIN y OPERATIVO y "Inactivo" no condona desde el 19/09. Condonar sigue solo ADMIN.

## 8. Riesgos y limites conocidos

- El rollback del deploy no revierte migraciones.
- Preflight corre despues de reabrir el sitio.
- La restauracion probada importa SQL; no reconstruye sola archivos y configuracion.
- El control historico del restore compara conteos, no contenido financiero completo.
- Un fallo de Drive conserva la copia local y salida 0; desde el 09/09 produce
  alerta diferenciada por Better Stack y Telegram, probada y recibida.
- `MoneyLockingTest` verifica texto del codigo, no concurrencia real.
- Cambiar contraseña no revoca por si solo sesiones y `remember_token`.
- Los errores de recibos pueden devolver el mensaje tecnico de la excepcion.
- Aritmetica monetaria usa `float` en parte del dominio; el daño no esta demostrado.
- `View::composer('*')` ejecuta una consulta global para el badge de clases.

## 9. Contradicciones abiertas

**A56 — cerrado por Codex CyE 06/10/2026:** signo real del contraasiento y gasto normal,
totales y marco 375 verificados en código, HTTP y navegador. Inicial $10.000, cobro y
devolución compensados, gasto $2.500, balance $7.500. Ambos seguimientos 30/72.
[Informe y límites](../06-pruebas/PRU-02/VERIFICACION-A56.md). Sin nuevas pruebas permanentes ni deploy.

**A4/A5 — cerrados 06/10/2026:** verificados de forma independiente por Gemini en pantalla interactiva y código (aviso superior accesible sin scroll, confirm en menú y Cancelar, beforeunload, y rechazo en servidor de profesores ajenos o inactivos). 11 pruebas pasando. [Informe de verificación](../06-pruebas/PRU-02/VERIFICACION-A4-A5.md). Sin despliegue ni base del club tocada. Pasan a 29 cerrados de 72.


FIN-11, 12/09: reproducido y corregido cancelar/validar en ambos órdenes
(dejaba pago ANULADO y deuda pagada 0 con 10.000 en cashflow). Corregidas también
las esperas tardías de cobrar/cancelar y validar/cobrar. Seis casos pasan, 103
aserciones aisladas. Pausa levantada el 13/09: Codex reejecutó la suite completa
en wings_testing, 187 pruebas / 1206 aserciones, sin fallas. Implementada y probada;
sin deploy. Evidencia: ../06-pruebas/FIN-11-CONCURRENCIA-2026-09-12.md.

FIN-07: pausa resuelta por Carlos el 12/09. Solo ADMIN perdona el saldo pendiente,
conservando original, pagos e imputaciones. Condonación transaccional con bloqueo
de la deuda, compartido con el cobro. Tres casos con conexiones reales y prueba
negativa sin bloqueo. Contrato Wings-Contrato-Condonacion-V1.md. Sin deploy.

FIN-03: freno documental resuelto el 11/09. Carlos autorizo Recibos V2 con
FIN-02 y FIN-03; V1 queda como antecedente. No se reconstruyen periodos ya
borrados en anulaciones antiguas. Migracion probada solo en base descartable.

COB-03 y total (COB-06), verificados el 10/09 en `cob-total` (`5238825`), no mergeada:
ambos casos, septiembre existente/virtual, muestran y registran $29.600 con cartel
70%; septiembre conserva $18.000 pendientes. Sin descuento, $38.000 correctos.
Cadena financiera y PDF verificados; suite de esa rama: 133 pruebas, 726 aserciones.
El defecto sigue en main hasta integrar; no confundir verificacion con despliegue.
Evidencia: `docs/06-pruebas/COB-03-VERIFICACION-2026-09-10.md`.

| Tema | Estado real | Proxima accion |
|---|---|---|
| `dia_generacion_deuda` editable | Confirmado 09/09: existe como fila de configuracion y **ningun codigo la lee**. El scheduler usa dia 1 fijo en `routes/console.php:12` | Definir si gobierna la tarea o se retira de pantalla |
| Alumno sin plan en la corrida mensual | `GenerarDeudasMensualesCommand:77-81` lo saltea: no genera deuda, no entra a revision, solo un `warn` que muere en el cron | Que caiga en la cola de revision con motivo propio |
| Recalcular una liquidacion puede cambiar el total de una ya cerrada | `LiquidacionService::recalcularLiquidacion()` chequea si esta cerrada **afuera** de la transaccion y sin tomar la fila. Si otra pestaña la cierra en ese instante, el recalculo cambia el total igual. `cerrarLiquidacion()` y `eliminarLiquidacion()` tienen el mismo patron, con daño nulo o que requiere tres acciones a la vez. El doble clic lo frena el anti doble envio de `ds-app.js`; queda el caso de dos pestañas o dos personas | Encontrado en el barrido de FIN-05. Corresponde a FIN-11 |
| `pagos.monto_base` se guarda mal | `crearPago()` lo reconstruye dividiendo lo cobrado por el porcentaje. Con una seña en el mes de alta da 10.000 / 0,7 = 14.285; con el mes de alta y otro mes en el mismo cobro divide tambien el que no tenia descuento. **Nadie lo lee hoy**: ni pantallas, ni recibos, ni reportes. Es una trampa para el rediseño del recibo, que querria mostrar el precio sin descuento | Definir que tiene que valer en un cobro con seña o con varios meses antes de que algo lo use |
| Descuento a un alumno de carga inicial cobrado en su propio mes de alta | `calcularReglaPrimerPago()` solo exige que el mes de alta este entre los periodos cobrados. Un alumno importado con deuda inicial de su mes de alta recibe el descuento al pagarla. La prueba existente solo cubre cobrarle **otro** mes | Carlos define si un alumno traido de la carga inicial puede recibir descuento de primer pago alguna vez |
| Wings no tenía arqueo | Relevamiento previo 06/10 confirmó que faltaban tanto inicio como conteo/diferencia; no era sumar a un arqueo existente | A25 implementada después de decisiones de Carlos. [Entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A25.md). CERRADO (verificado por Gemini 06/10); no desplegada |
| Resultado y saldo de Cashflow | A10 muestra resultado del período sin sumar saldo inicial | Aplicado localmente 08/10, aspecto aprobado por Carlos; intervalos/cálculo a verificar. Saldo acumulado financiero aplicado localmente en V3 B12, 09/10; falta clasificación de antecedentes y las demás pantallas POS-01 |
| Estado minimo de entrega | El club ya carga datos reales | FDS-03 pausada por Carlos el 09/09; redefinir, no limpiar |
| Tope de 1200px en guia de diseño | `app.css` no lo implementa | Decidir guia o implementacion; no tocar sin autorizacion |

## 10. Fuentes vigentes

| Necesidad | Ruta |
|---|---|
| Plan para IA | `docs/07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md` |
| Plan marcable para Carlos | `docs/07-evaluacion/PLAN-TRABAJO-CARLOS-v2026-09-08.html` |
| Evaluacion Codex | `docs/07-evaluacion/Evaluacion Codex 8-9-26.md` |
| Evaluacion Claude | `docs/07-evaluacion/Evaluacion Claude 8-9-26.md` |
| Bitacora Codex | `docs/00-estado/LOG-CODEX.md` |
| Bitacora Claude | `docs/00-estado/LOG-CLAUDE.md` |
| Acciones de Carlos | `docs/00-estado/CHECKLIST-CARLOS.md` |
| Contratos | `docs/02-contratos/` |
| Pruebas funcionales | `docs/06-pruebas/` |
