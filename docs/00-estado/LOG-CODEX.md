# Wings — Bitácora activa de CODEX

## 2026-10-06 — Codex CAB — Decisiones A25 consolidadas en el contrato

Pedido de Carlos: modificar el contrato con sus respuestas y dejar aviso común.
[Contrato Caja/Cashflow V5.v2](../02-contratos/Wings-Contrato-Caja-Cashflow-V5.md): diez decisiones con ejemplos; sin reglas nuevas respecto de V5.v1.
Cajón compartido, herencia confirmada/motivo, conteo, cambio/retiro, diferencias y efectivo ADMIN separado.
Rechazadas conservan lo contado/entregado y el turno siguiente; validación exige cierre contado.
Resumen compartido y relevamiento enlazan la versión vigente para Codex, Claude y Gemini.
Solo documentación: enlaces/diff comprobados; sin suite, aplicación, base ni servidor tocados.
A25 sigue HECHO (Codex), a revisar por otro agente; no cerrada ni desplegada.

## 2026-10-06 — Codex CAB — A25 implementada, diseño aprobado; a revisar

Apertura explícita, recibido/heredado y motivo; un cajón compartido con un turno abierto.
ADMIN configura el medio una vez y guarda sus cobros aparte; cuenta/cierra antes de validar.
Arqueo, contado, diferencia, cambio/entrega; faltantes no bloquean ni crean ajustes contables.
Rechazadas conservan contado/entrega y actor/fecha originales, sin alterar el turno siguiente.
26 pruebas nuevas; suite propia 468 aprobadas/2 omitidas, 3116 aserciones, 181,44 s, sin fallas.
PHP/Blade/build verdes. Carlos aprobó cinco pantallas escritorio/375: «OK, aprobado» y «Sí».
[Entrega, límites y capturas reales](../06-pruebas/PRU-02/IMPLEMENTACION-A25.md); sin CSS ni base real/servidor tocados.
Contrato/ER V5, ambos seguimientos y cuatro estados actualizados. HECHO (Codex), no CERRADO.
Código 30f38f8; integración e3b268a con 94e368e/a89ebbe solo documental. Push recibido en main y pull --ff-only al día: HEAD local/remoto e3b268a comprobados 06/10. Otro agente verifica; sin deploy.

## 2026-10-06 — Codex CAB — A25 relevado, decisiones antes de programar

Verificación previa publicada en ddefe00: HEAD y main remoto iguales; pull al día.
A25: apertura sin importe y cierre sin conteo/comparación; confirmado en cuerpos y migraciones.
Carlos definió separar cambio/retiro, heredar último cierre del club con confirmación,
un cajón compartido y cierre con esperado/contado/diferencia para revisión ADMIN.
Aceptados: primer importe declarado, corrección con motivo, un turno abierto y cambio retenido elegible.
[Decisiones, límites y pruebas previstas](../05-pendientes/A25-CAMBIO-INICIAL-CAJA.md).
Ambos seguimientos: A25 Falta, no implementado. No se inventa arqueo existente.
Sin vistas, CSS, base real, servidor ni suite permanente modificados; cambios ajenos preservados.
ADMIN configura el medio físico una vez, guarda aparte sus cobros y debe contar/cerrar antes de validar.
Previas propias: 12/15, 3 fallos reales + 9 métodos ausentes; [evidencia](../06-pruebas/PRU-02/evidencia/a25/README.md). Carlos conserva conteo/entrega al corregir rechazadas.

## 2026-10-06 — Codex CAB — Verificación A13/B1/A54/A55 documentada

Ensayos reales del 05/10 sobre 4571dbc/8869263; filas propias releídas el 06/10.
A54 pasa y queda CERRADO por el verificador; A55 falla con dos deportes ($0/$5.000).
A13/B1: reversión financiera pasa; historial anulado y dibujo del contraasiento fallan.
Tres hallazgos dentro de esos IDs, sin duplicar números; Claude corrige. Ambos seguimientos 26/71.
Cierre operativo comprobado por servicio, no por pantalla; concurrencia limitada a tres órdenes.
Selección 23/106 y ensayo HTTP 2/9 el 05/10; no suite completa nueva ni aumento permanente.
[Informe y evidencia ordenada](../06-pruebas/PRU-02/VERIFICACION-A13-A54.md); herramientas aisladas bajo docs.
Retirados generador/HTML redundantes y tres capturas con assets incompletos; cambios ajenos preservados.
Sin base del club ni servidor tocados; publicación se comprueba al terminar. Sigue A25, decisiones antes de programar.

## 2026-10-05 — Codex CAB — A4/A5 hechos, a revisar

Carlos aprobó aviso superior y profesores activos del deporte de la clase; pidió Hecho (Cx), a revisar.
A4: resumen visible, datos/plan conservados y advertencia de salida; A5: filtro y rechazo servidor.
JavaScript propio/Vite; sin CSS ni permisos nuevos. Backend inicial A5 entró con 8869263 de Claude.
Previas: 7 rojas/2 verdes, 27 aserciones; específica final: 11/39 verdes.
Suite propia wings_testing_codex: 437 aprobadas, 1 omitida, 2949 aserciones; última vuelta 129,48 s. PHP/Blade/build OK.
Diez capturas reales escritorio/375; Chrome mostró confirm al salir, pero se trabó al cancelar: revisor debe comprobar retención y cierre.
[Entrega y capturas](../06-pruebas/PRU-02/IMPLEMENTACION-A4-A5.md); ambos seguimientos y cuatro estados actualizados.
Sin base del club ni servidor tocados; no desplegado ni cerrado. Sigue control independiente.
Retoque A4: motivo en grilla, rótulo arriba e ícono de Descripción pedido por Carlos; sin CSS. Suite actual (incluye A37): 438 aprobadas, 1 omitida, 2960 aserciones; 134,62 s.
Capturas nuevas en galería/maqueta/tablero/HTML del plan: Carlos señaló la omisión y se corrigió. Carlos aprobó las capturas con ícono el 05/10; autorizado el commit/push; después A13/B1/A54/A55.

## 2026-10-05 — Codex CAB — A4/A5 frenados por instrucción de cierre contradictoria

Main sincronizado en `6ebfe83`; árbol limpio al iniciar y continuidad revisada.
El prompt exige CERRADOS por el autor; AGENTS.md §6a y DEFECTOS.md exigen control ajeno previo.
Consulta: entregar IMPLEMENTADOS pendientes de revisión en ambos seguimientos y cerrar después del control.
Freno registrado en ESTADO-ACTUAL §9; A4/A5 siguen abiertos, sin modificar sus contadores.
MCP probado: transporte cerrado; contraste documental hecho contra archivos reales.
Sin aplicación, vistas, pruebas, datos ni servidor tocados; no se corrió suite por documentación.
Siguiente: Carlos aclara el cierre; después propuesta visual con capturas antes de pedir autorización.

## 2026-10-05 — Codex CAB — P1 implementada, pendiente Gemini

Carlos aprobó maqueta y aclaró producción sin alumnos/deudas/pagos; levantado el freno previo.
Plantilla vacía, listas reales, revisión completa sin escritura y Excel original con AM Errores.
Carga atómica de lo declarado, sin descuento ni cuota automática; Deshacer protegido.
Estado persistente y entrada ADMIN automática; alta individual no saltea el control servidor.
Previas: 16 rojas. Final exclusiva: 404/2815 verdes, wings_testing_codex, 134,16 s; PHP/Blade/build OK.
Navegador normal/375: seis errores, carga 4 alumnos/6 cuotas + inscripción = $301.000, cero caja; Deshacer verificado.
Writer omitía textos vacíos: informe corregido y prueba compara el XLSX guardado por valor/tipo.
[Entrega y capturas](../06-pruebas/PRU-02/P1-IMPLEMENTACION-2026-10-05.md). P2 ajeno preservado fuera del commit.
Sin deploy, limpieza ni base real tocada. Gemini verifica; después retiro de CLI antiguos en commit aparte.

## 2026-10-05 — Codex CAB — P1 autorizada; freno por transición de bases existentes

Main actualizado desde GitHub; HEAD `1503df2`. Leídos decisión, maqueta aprobada y P0.
Revisados importador de padrón, inscripción, rutas y entrada por rol; MCP sin transporte.
No existe estado persistente de primera carga en aplicación/migraciones.
La tarea exige estado pendiente/terminada y bloqueo servidor, sin inferirlo por alumnos.
Falta decidir cómo inicializar ese estado en un club que ya está trabajando con alumnos.
Marcar todas las bases pendientes podría bloquear el alta manual existente; no se implementó.
Consulta a Carlos: conservar esas bases como preparadas y exigir Excel solo a clubes nuevos.
Sin código, migraciones, suite, datos ni servidor tocados; importadores antiguos conservados.
Cambios simultáneos ajenos en Cobranza/cobro y su prueba preservados. P1 no entregada.

## 2026-10-05 — Codex CAB — Entrega 1 de Cobranza verificada y aprobada

Pull al día, HEAD `4fb185e`; revisado `abc346a` y cuerpos reales, sin cambiar código.
A51: fila por deporte + DNI, importe estable en filtros y ayuda solo por saldo ajeno.
A52: inscripción no cambia estado en listado, ficha ni resumen; Al día/En plazo vistos.
Navegador ADMIN/OPERATIVO; copia alineada a HEAD y datos ficticios en base propia.
Build verde y todas las barras compartidas revisadas a 375, con capturas propias.
Suite propia `wings_testing_codex`: **380/2242**, toda verde (144,94 s).
A53/A54/A55 nuevos, fuera de Entrega 1: tarjetas móviles y selector de cobro; sin arreglos.
[Segunda vuelta y límites](../06-pruebas/PRU-02/VERIFICACION-ENTREGA1.md). Sin deploy.
Siguiente: Gemini puede continuar Entrega 2; A43/permisos siguen pendientes de su control.

## 2026-10-04 — Codex CAB — A29/A30/A31 entregados, pendiente Gemini

Commit `97cf933`. Autorización literal de Carlos aplicada a 403; mismo aviso y Volver al inicio propio.
EnsureAdminWeb rechaza sin redirect; cuenta inactiva/anónimo mantienen login.
Pruebas previas 12 rojas/2 verdes; final 380/2242 verde en wings_testing_codex.
Dos pruebas existentes actualizadas para rechazo explícito; Rubros no cambia lógica.
PHP/Blade y diff verificados; sin CSS/script nuevos, CSP conserva 19/10.
Tres roles en navegador normal/375 px; Volver conserva sesión y abre inicio correcto.
[Entrega y capturas](../06-pruebas/PRU-02/IMPLEMENTACION-PERMISOS.md). Sin deploy ni cierre.
A43 en 218ffc5; siguiente paso: Gemini debe verificar ambas entregas.

A43 entregada archivada intacta en [LOG-CODEX-A43-ENTREGA.md](../99-archivo/bitacoras/2026-10-06/LOG-CODEX-A43-ENTREGA.md).

Preparación A43/permisos archivada intacta en [LOG-CODEX-A43-PREPARACION.md](../99-archivo/bitacoras/2026-10-06/LOG-CODEX-A43-PREPARACION.md).

Entrega A11 archivada en [LOG-CODEX-A11-ENTREGA.md](../99-archivo/bitacoras/2026-10-06/LOG-CODEX-A11-ENTREGA.md).

Control de Cobranza/propuesta A11 archivado en [LOG-CODEX-CONTROL-A11.md](../99-archivo/bitacoras/2026-10-06/LOG-CODEX-CONTROL-A11.md).

Entrada de maqueta P1 archivada intacta en [LOG-CODEX-MAQUETA-P1.md](../99-archivo/bitacoras/2026-10-05/LOG-CODEX-MAQUETA-P1.md).

Entrada P0 archivada en [LOG-CODEX-P0.md](../99-archivo/bitacoras/2026-10-05/LOG-CODEX-P0.md); texto original conservado, acceso a evidencia indicado allí.

Entradas anteriores archivadas intactas en [LOG-CODEX-ANTES-A43.md](../99-archivo/bitacoras/2026-10-04/LOG-CODEX-ANTES-A43.md).
