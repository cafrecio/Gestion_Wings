# Wings — Bitácora activa de CODEX

## 2026-10-04 — Codex CAB — Control de Cobranza y propuesta A11

Revisión independiente de Entrega 1 sobre `1c4e6dc` y `867c295`, código y navegador local.
No aprobada completa: A51 total por persona cambia con filtros; A52 inscripción altera estado.
A18 parcial; filtros a 375 px siguen ilegibles (A19). No se corrigió código ajeno ni datos.
Suite propia verde: 343/1955 en wings_testing, sin corridas simultáneas; Entrega2Test ausente.
[Informe y capturas](../06-pruebas/PRU-02/VERIFICACION-ENTREGA1.md); base local sin monto_condonado.
A11: [maqueta estática](../05-pendientes/maqueta-configuracion/README.md), CSS Wings existente,
grupos, etiquetas, errores y generación mensual fija. Abierta en navegador para Carlos.
Carlos aprueba maqueta («Ok, aprobada»); falta su línea Diseno-autorizado. No implementada.
Pruebas A11 preparadas localmente: 7 rojas / 1 verde, 58 aserciones. Sin commit de los tests.


## 2026-10-04 — Codex CAB — Maqueta P1 en navegador, espera aprobación

P0 en `ad24769`, suite 338/1932 verde en ese corte; pendiente de Gemini.
P1 solo maqueta estática: cuatro pasos, errores por fila/columna y Excel marcado.
Carlos aprobó una fila con datos, dos Sí/No y 12 pares Período/Monto; reemplaza hojas separadas.
Solo Alumnos se completa; Catálogos alimenta listas y Guía reproduce ejemplos visuales.
Entrada automática obligatoria pedida; estado pendiente/terminada y bloqueo de alta son propuesta.
Instructivo y colores Wings revisados en escritorio/390 px, sin desborde; descarga comprobada.
XLSX: 38 columnas, tipos/valores originales conservados en marcado; ejemplo total $301.000.
Carga bloqueada con errores y Deshacer tras cobro simulado; Excel nativo no comprobado.
[Maqueta y evidencia](../05-pendientes/maqueta-primera-carga/README.md). Sin importador ni servidor tocados.
Carlos debe aprobar maqueta completa antes de programar. No cerrado; Gemini verifica.

## 2026-10-04 — Codex CAB — P0 entregado; P1 requiere maqueta aprobada

A43 reescrito: cuota del mes real de ingreso al porcentaje del día, sin corte ni pregunta.
Plan inicial vigente desde ingreso; cobro conserva importe congelado. Inscripción manual
única por DNI, sin corte; corrección auditada no crea/anula cargos por fecha.
Migración retira solo el parámetro legado; ambos importadores anteriores se conservan.
Pruebas antes: 9 fallos/20 correctas; final MariaDB wings_testing: 338/1932, todo verde.
Sintaxis PHP, compilación Blade y diff verificados; campo de Configuración retirado con
autorización de P0, sin CSS nuevo ni deploy. Documentos vigentes y tableros actualizados.
[Evidencia](../06-pruebas/PRU-02/P0-CARGA-INICIAL-2026-10-04.md). Pendiente Gemini, no cerrado.
Siguiente: maqueta P1 fuera de Wings; Carlos aprueba en navegador antes de programar.

## 2026-09-23 — Codex CAB — A2/B2 implementados; pendiente verificación independiente

Commit `b3619bf`: cuota del mes al alta, transacción con alumno/plan/inscripción.
Importe y porcentaje guardados; preview y cobro no vuelven a descontar esa cuota.
DEUDOR solo por mes cerrado impago, coherente en cálculo individual y masivo.
Pruebas iniciales rojas (7 fallos/1 correcta); final MariaDB wings_testing: 331/1900.
Rollback tras insertar cuota, reintento, borde 30/1, padrón y recobro cubiertos.
PHP y compilación Blade correctos; vistas/CSS intactos. Sin deploy ni base real.
[Evidencia](../06-pruebas/PRU-02/IMPLEMENTACION-A2.md). A2/B2 pendientes de otro agente.
Al desplegar requiere migración porcentaje_alta; tarea no cerrada por el implementador.

## 2026-09-23 — Codex CAB — Telegram de test recibido; correo rechazado

Carlos autorizó bot/correo. Chat identificado con getChat y comunicado antes de guardar.
Destinatarios en Configuración de wingstest; token privado, sin tocar producción.
Resumen real con revisión temporal revertida; Telegram HTTP 200 y Carlos confirma recepción.
Correo sin MessageSent: Postfix rechaza por consultas MySQL de alias/vacaciones fallidas.
No tomar el éxito de Artisan como entrega. Pendiente transporte compartido o SMTP externo.
[Evidencia y texto recibido](../06-pruebas/PRU-02-AUTOMATIZACION-TEST-2026-09-23.md).
Solo configuración del test y documentación; suite previa 315/1804, sin repetir por docs.
[Entrada antigua preservada](../99-archivo/bitacoras/2026-09-23-LOG-CODEX-TEST-AVISOS.md).

## 2026-09-23 — Codex CAB — PRU-02 cron de test probado; avisos pendientes

Solo wingstest; cron preexistente reemplazado por ejecutor aislado de producción.
Ejecuciones reales 03:28, 03:29 y 03:30 UTC correctas; nologin y password bloqueada.
Instalación repetible integrada a montar-test; sin heartbeats, destinos CSP propios.
Email destino guardado en Configuración; Telegram sin chat verificado, sin envío.
Auto-review bloqueó token/transporte: autorización pendiente; Carlos debe iniciar bot.
Suite aislada 315/1804, 93,45 s; bash -n correcto, sin cambios visuales ni producción.
[Evidencia](../06-pruebas/PRU-02-AUTOMATIZACION-TEST-2026-09-23.md). Siguiente: probar recepción real de ambos avisos.
[Entrada antigua preservada](../99-archivo/bitacoras/2026-09-23-LOG-CODEX-TEST-CRON.md).

## 2026-09-22 — Codex CAB — ENT-01 implementada y verificada

Carlos aprobó DNI por persona, inscripción primero, sin comisión y edición auditada.
Alta/cargo atómicos, reintentos y concurrencia; caja, estado de cuenta y PDF desglosados.
§5 de Punitorios usa cargos; FIN-14 y manuales ENT-10 siguen pendientes. Sin deploy.
Pruebas iniciales rojas; suite final aislada: **313 / 1793**, 122,92 s, MariaDB.
Aviso previo, cobro parcial/completo y PDF vistos con datos ficticios; build y Blade correctos.
[Evidencia y límites](../06-pruebas/ENT-01-INSCRIPCION-2026-09-22.md); código guardado en 11623b6 por sesión paralela.
Siguiente: revisión cruzada de Claude y actualización del servidor para PRU-02.
[Entrada antigua preservada](../99-archivo/bitacoras/2026-09-22-LOG-CODEX-ENT01-CIERRE.md).

## 2026-09-22 — Codex CAB — ENT-01 propuesta previa a implementación

Pull inicial sin novedades; corte confirmado 23/09/2026, por fecha real de ingreso.
[Propuesta revisable](../05-pendientes/ENT-01-PROPUESTA-CARGOS-ADICIONALES.md): cargos comunes,
imputaciones separadas, un pago/recibo y movimientos por concepto, sin duplicar caja.
Contraste de fuentes: punitorios §5 requiere enmienda; pagos afectan estado y comisión.
Decisiones abiertas: parciales, identidad por persona/deporte, fecha y permiso visual.
Índice actualizado y cuerpos reales leídos; enlaces/diff revisados, sin suite ni base.
Sin código ni deploy. Siguiente: revisión de Carlos antes de pruebas en rojo e implementación.
[Entrada antigua archivada](../99-archivo/bitacoras/2026-09-22-LOG-CODEX-ENT01-PROPUESTA.md).

## 2026-09-22 — Codex CAB — FIN-04 definición funcional cerrada

Carlos confirma gastos del club separados, sin descontarlos del resultado por deporte.
Sí se descuentan del total del negocio; enmienda en el contrato de Reportes V1.
Saldo acumulado, resultado del período y proyecciones separados; confirmados/sin confirmar.
Ambos tableros, checklist, estado, plan de producción, resumen y encuesta actualizados.
Cierre documental de definición; POS-01 sigue pendiente, sin código ni despliegue.
Verificación de enlaces, coherencia de estado, diff y ausencia de cambios de diseño.
[Entrada anterior archivada](../99-archivo/bitacoras/2026-09-22-LOG-CODEX-FIN04.md).
Siguiente: continuar diseño e implementación de Reportes según pedido de Carlos.

## 2026-09-22 — Codex CAB — MUY IMPORTANTE: inscripción por fecha real de ingreso

Carlos decide carga manual por usuarios; no modo temporal ni pregunta nuevo/antiguo.
Ingreso anterior al corte fijo no genera inscripción; desde el corte sí, una sola vez.
Valor obligatorio configurable inicial $5.000; deuda al alta, cobro con primera cuota.
[ENT-01 y ENT-10](../05-pendientes/ENT-01-INSCRIPCION-Y-PRIMERA-CARGA.md): decisión y manuales pendientes.
Manuales deben destacar ingreso real versus fecha de carga, con ejemplos claros.
Falta confirmar fecha concreta de inicio; no inventarla. Propuesta técnica, sin código.
Tableros y resumen enlazados; verificación documental, sin suite ni base.
[Entrada antigua conservada](../99-archivo/bitacoras/2026-09-22-LOG-CODEX.md).

[Entradas anteriores preservadas](../99-archivo/bitacoras/2026-10-04-LOG-CODEX.md).

[Entrada POS-07 archivada](../99-archivo/bitacoras/2026-10-04-LOG-CODEX-POS07.md).
