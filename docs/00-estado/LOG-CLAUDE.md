# Wings — Bitácora activa de CLAUDE

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CLAUDE.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).
· [Entradas archivadas el 17/09](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE-2.md) · [Entradas archivadas el 21/09](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE-2.md) · [Entradas archivadas el 02/10](../99-archivo/bitacoras/2026-10-02/LOG-CLAUDE.md) · [Entradas archivadas el 10/10](../99-archivo/bitacoras/2026-10-10/LOG-CLAUDE.md) · [Entradas archivadas el 13/10](../99-archivo/bitacoras/2026-10-13/LOG-CLAUDE.md) · [Entradas archivadas el 05/10](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE-2.md) · [Entradas archivadas el 07/10](../99-archivo/bitacoras/2026-10-07/LOG-CLAUDE.md) · [Entradas archivadas el 08/10](../99-archivo/bitacoras/2026-10-08/LOG-CLAUDE.md) · [Entradas archivadas el 12/10](../99-archivo/bitacoras/2026-10-12/LOG-CLAUDE.md) · [Entradas archivadas el 16/10](../99-archivo/bitacoras/2026-10-16/LOG-CLAUDE.md) · [Entradas archivadas el 19/10](../99-archivo/bitacoras/2026-10-19/LOG-CLAUDE.md) · [Entradas archivadas el 21/10](../99-archivo/bitacoras/2026-10-21/LOG-CLAUDE.md) · [Entradas archivadas el 20/10](../99-archivo/bitacoras/2026-10-20/LOG-CLAUDE.md) · [Entradas archivadas el 27/10](../99-archivo/bitacoras/2026-10-27/LOG-CLAUDE.md)

## 2026-10-29 — Claude CAB — Tanda 1 (días 24 a 28) revisada, con prueba técnica

- Codex (`52c9f368`): cinco cierres iguales a la simulación. Revisión contra guion, código y base del sitio: [ESTADO-PRUEBA](../06-pruebas/PRU-04/ESTADO-PRUEBA.md).
- Defecto nuevo de Wings, **MF12**: el contador de Nueva liquidación no reconoce las liquidaciones por comisión (guardan alumnos, no clases); octubre pagado de Hernán y Martín figura pendiente. Generarlo otra vez se rechaza: sin plata en juego, va como mejora futura.
- **Cuatro errores míos del guion**, todos de no mirar la pantalla en la simulación (llamé a los controladores): Eliminar no aparece cuando no se puede (27.4, 27.6, y el 32.2 de la tanda 2, ya corregido); plazo de Iván 8 días, no 7; $28.000 de Santino en una cifra intermedia; la fecha de baja no está en la ficha. Erratas en el guion.
- Lo que Codex no pudo probar, cerrado acá: recibos LIQ-2 y CUOTA-254 correctos (PDF del servidor); constancia de la generación intacta; resumen del 5/11 corrió una vez (falta que Carlos diga si llegó); rechazos de eliminar forzados.
- Técnica: interno 27/27 (ASI-2 corregido por T62, seis controles nuevos), seguridad 26/26 (tres nuevas), velocidad < 277 ms. Planilla actualizada: 160/334.

## 2026-10-29 — Claude CAB — T12/T13 etapa A devuelta entera

- Control de `61fb5272` en [VERIFICACION-T12-T13-ETAPA-A-CLAUDE.md](../06-pruebas/PRU-04/VERIFICACION-T12-T13-ETAPA-A-CLAUDE.md). Verificador en 0 y 19 pruebas de la Ayuda en verde (corridas por mí). El buscador y el **?** están bien.
- Seis páginas contrastadas con el código: un error grave (`rectificar-caja` permite rectificar una caja Rechazada y Wings solo lo permite con la caja Abierta; la ficha citaba una línea que no era la regla y decía «Comprobado»), uno medio (regla de motivo en la toma de lista inventada) y dos menores. Por la regla del prompt, vuelve la etapa entera.
- `REVISOR-MANUAL.md` punto 6: cada condición se compara contra el `if` que la decide, buscado por el revisor. Prompt de devolución: [PROMPT-GEMINI-T12-T13-ETAPA-A-DEVOLUCION.md](../06-pruebas/PRU-04/PROMPT-GEMINI-T12-T13-ETAPA-A-DEVOLUCION.md). T12 y T13 `devuelto, tiene=Gemini`.

## 2026-10-29 — Claude CAB — T12/T13: corrección de la etapa 1 controlada

- Gemini (`120ae713`): los siete puntos corregidos, comprobados contra el código; verificador en 0; página de la profesora correcta. Dos detalles menores para la primera tanda (Cobranza no abre el buscador; la fecha del turno pide confirmar fecha vieja). El revisor independiente de Gemini dio 0 hallazgos y se le pasaron esos dos. [Control](../06-pruebas/PRU-04/VERIFICACION-T12-T13-ETAPA1-CLAUDE.md). Falta el OK de Carlos para la etapa 2.
- Carlos lo aprobó y pidió dividir lo que falta en tres etapas con freno en cada una. Prompt: [PROMPT-GEMINI-T12-T13-ETAPAS.md](../06-pruebas/PRU-04/PROMPT-GEMINI-T12-T13-ETAPAS.md). La lista de 54 tenía funciones que Wings no tiene (exportar a Excel, color de deporte, cupos, adelantos, sedes): el paso 0 la revisa contra el código.

## 2026-10-29 — Claude CAB — Guion de noviembre (días 24 a 33), simulado entero

- Carlos: 7 días en dos tandas, simulando todos; script para lo rutinario salvo lo que cambió; pensar a futuro. Quedaron 10 días (2 al 11/11) para cruzar el vencimiento: [tanda 1](../06-pruebas/PRU-04/GUION-DIAS-24-28.md), [tanda 2](../06-pruebas/PRU-04/GUION-DIAS-29-33.md), [prompt](../06-pruebas/PRU-04/PROMPT-CODEX-DIAS-24-28.md).
- Método nuevo: copia de wingstest en una base descartable local; cada día (rutina y pantalla, con lo mismo que envía la pantalla) corrido en orden con su fecha. Días 24 y 25 cuadrados además a mano. Errores míos hallados así y corregidos antes de entregar: serie de Martín en días que no da, DNI de Iván repetido con Lara Quiroga, relato de la caja rechazada.
- No había ninguna clase desde el 1/11: la rutina del 24 carga el calendario. `rutina.php` suma `crear_serie` y `motivos` (B3). Confirmaciones nativas: interruptor `PRUEBA_SIN_CONFIRMACIONES` desplegado en el sitio (`899a8079`), completa 1274/6 omitidas.
- Hallazgos para mostrar: avisos en inglés al corregir una caja rechazada y al editar un movimiento sin fecha; «Quedan 1 meses»; un pago de liquidación nunca se rechaza por saldo con los medios actuales.
- Pendiente de Carlos para la tanda 2: prender o no el recargo por mora.

## 2026-10-28 — Claude CAB — Catálogo al día con lo desplegado; el repo manda

- Carlos: los casos no reflejaban lo subido. Catálogo de 332 a 334 (CBZ-023 deuda de inactivos, CBZ-024 deuda perdida) y 37 casos con líneas nuevas o corregidas por T25, T45, T46, T51, T54, T62, T65, T70/T71, T84 a T93 y B3 (incluye los textos de Codex ALU-017.b, ALU-025.b, REP-006, PRO-004.c). 20 ya probados cambiaron: se repiten.
- Comprobado en código: el buscador de Cobrar sigue listando solo activos (COB-015.a lo dice; al inactivo se le cobra desde la ficha).
- Decisión de Carlos: la tabla viva lee el catálogo de `tabla-de-pruebas/catalogo/` publicado con publish_files; si faltan los archivos cae a la base vieja. README y AGENTS §5-ter al día. Planilla «Prueba por días» actualizada.

## 2026-10-28 — Claude CAB — Sitio de prueba desplegado en b271e8dd

- Pedido de Carlos: dejar el sitio de prueba listo para probar lo subido estos días. De `f8d71d5c` a `b271e8dd`: T87, T88, B3, T54, T65, T25, T89, T62, T70/T71, T51, T45 y T46, más la Ayuda de T12/T13 a medias («no molesta», Carlos).
- Completa antes: 1255 aprobadas, 6 omitidas, 15 caídas con 503 (SueldoOperativaT86, SuperadminProtegido, T88B3Dinero): la app local entró en mantenimiento un momento a las 23:51, ajeno a la suite. Repetidas esas tres clases: 38/38.
- Respaldo `/root/respaldos/wingstest-antes-despliegue-2026-10-28.sql.gz`; down → merge --ff-only → npm ci/build → 5 migraciones DONE → cachés → up. /login 200, CSP, reloj simulado intacto (01/11), sin errores nuevos. Producción no se tocó.
- Sigue la prueba corta de 3-4 días; Carlos pidió conversar el guion antes de escribirlo.

## 2026-10-28 — Claude CAB — T12/T13 etapa 1: contenido devuelto

- Carlos: formato y lista de 54 OK. Contenido revisado contra el código: «No tenés una caja abierta» no existe; «Saldo inicial» y «Abrir caja» tampoco; «anular» en Caja es Cancelar y no cubre la caja cerrada sin validar; no hay estado parcial. **Grave:** «reactivá la cuota» después de un cobro en caja validada no se puede (reactivar solo acepta perdonadas).
- Hueco real de Wings, fuera del alcance: un cobro de cuota en una caja validada no se puede revertir en la cuenta del alumno. Planteado a Carlos.
- Nuevo, por pedido de Carlos de rigor técnico: `scripts/manual/verificar-manual.php` (textos «», negritas, imágenes y pantalla contra el código; hoy da 9 problemas) y `REVISOR-MANUAL.md` (revisor independiente, para cualquier agente). Prompt: [PROMPT-GEMINI-T12-T13-CORRECCION.md](../06-pruebas/PRU-04/PROMPT-GEMINI-T12-T13-CORRECCION.md).

## 2026-10-28 — Claude CAB — T46 cerrada; Wings local listo para T12/T13

- T46 (Codex, `c475fa6c`): aprobada en lógica, 276/276 del área y 4/4 ataques propios (clase compartida, clase de hoy terminada, dos meses, validar a mano después de la baja). Las 175 rutas con sesión pasan por `ensure.active.web`. Cerrada con el OK visual que Carlos dio en CyE. [Informe](../06-pruebas/PRU-04/VERIFICACION-T46-CLAUDE.md).
- Corregidos PLAN-PRODUCCION, RESUMEN-ARRANQUE y ESTADO-ACTUAL: seguían con T45, T46, T51 y T70/T71 «a verificar».
- Wings local (base `wings_test`) migrado y compilado: la Ayuda ya se ve en `http://gestion-wings/ayuda`, para el OK de Carlos a la etapa 1 de T12/T13.
- Siguen del traspaso: OK de Carlos a T12/T13; los textos propuestos para la tabla de pruebas (ahora cuatro: ALU-017.b, ALU-025.b, REP-006 y PRO-004.c); pendientes de T82.

## 2026-10-28 — Claude CyE — T51 y T45 cerradas; traspaso a CAB

- T51 (Codex, `983c6bab`): aprobada, 106 del área y 3/3 ataques. Cerrada con OK visual. Al subir a prueba, Carlos mira la Historia de correcciones con movimientos reales (los textos de la base de Codex no sirven para juzgar). [Informe](../06-pruebas/PRU-04/VERIFICACION-T51-CLAUDE.md).
- T45 (Codex, `e3ca5555`): aprobada, 141/141 de liquidaciones, profesores, deportes y sueldos, con ataque propio. Cerrada con OK visual. [Informe](../06-pruebas/PRU-04/VERIFICACION-T45-CLAUDE.md).
- T12/T13 etapa 1: devuelta por capturas repetidas e imágenes rotas en Wings ([devolución](../06-pruebas/PRU-04/DEVOLUCION-GEMINI-T12-T13-ETAPA1.md)). Gemini corrigió (`bbaba512`): las 8 capturas ya son distintas (comprobado por md5) y se sirven desde una ruta de la ayuda con control de rol. Lo demás no se revisó: **espera el OK de Carlos a las muestras y a la lista de 54 páginas**, mirándolo en Wings (pull, migrate, build, menú Sistema → Ayuda).
- **Para retomar en CAB:** 1) verificar T46 (Codex, `c475fa6c`; Carlos ya dio el OK visual); 2) OK de Carlos a la etapa 1 de T12/T13; 3) aplicar en la tabla de pruebas los tres textos que propuso Codex (ALU-017.b, ALU-025.b, REP-006), si Carlos dice que sí; 4) T82: credencial propia de rclone, clave al administrador de contraseñas, decidir si el repositorio pasa a privado.
- Prompts guardados hoy: `PROMPT-CODEX-T51-T45-T46.md` y `PROMPT-GEMINI-T12-T13.md` (PRU-04).

## 2026-10-28 — Claude CyE — T62 aprobada en lógica

- Segunda vuelta de Gemini (`afc4963c`): la generación ya no cuenta presentes de clases canceladas; 54/54 con generación, Revisión y B3. Falta el OK visual de Carlos.

## 2026-10-28 — Claude CyE — T70 y T71 cerradas

- T70/T71 (Codex, `df7442bc`): lógica aprobada, 237/237 del área y 3/3 ataques (cobrar lo perdido al volver, baja desde Revisión, doble envío). Con el OK visual de Carlos, cerradas. [Informe](../06-pruebas/PRU-04/VERIFICACION-T70-T71-CLAUDE.md).

## 2026-10-28 — Claude CyE — T62 devuelta por CLA-012.c

- T62 (Gemini, `5b0869cd`): lógica aprobada; 101/101 del área y 7/7 ataques propios (segunda profesora, contiguos, solo ausentes, forzados).
- Vuelve: la generación del mes cuenta presentes de clases canceladas (Gemini lo informó sin tocarlo). La regla ya es de Carlos (CLA-012.c): se corrige sin preguntar. [Informe](../06-pruebas/PRU-04/VERIFICACION-T62-CLAUDE.md).

## 2026-10-28 — Claude CyE — T82: ensayo de restauración

- Clave de respaldos: Carlos no la tenía; subió a Drive «Credenciales Back-ups» y coincide por huella con la del servidor. Checklist corregido (decía «a salvo» sin comprobar).
- Ensayo en el VPS, aparte de producción: respaldo bajado de Drive, base, código de GitHub, `.env`, storage, build; /login 200 en 52 s. Todo borrado al final. [Informe](../04-tecnico/seguridad-vps/2026-10-28-ensayo-restauracion/README.md); script en `scripts/servidor/ensayo-restauracion.sh`.
- Repetido con datos (idea de Carlos): paquete del sitio de prueba armado como `respaldar.sh`; 105 alumnos, 909 asistencias y la plata al peso idénticas, /login 200, 56 s. No probado: servidor desde cero.
- Pendientes: credencial propia de rclone (la compartida se retira en 2026), clave fuera de la carpeta de respaldos, y el repositorio es público con 33 dumps viejos con `users` de demostración en el historial.
- Prompts guardados: `PROMPT-CODEX-T70-T71.md` y `PROMPT-GEMINI-T62.md`.

## 2026-10-28 — Claude CyE — T71 resuelta: inactivo y baja son dos cosas

- Carlos: «Inactivo y baja son dos cosas distintas. Solo se puede dar de baja un alumno sin movimientos.» Baja = eliminar, ya es T65. Pasar a Inactivo sigue instantáneo, sin motivo ni aviso (T84; coincide con ALU-017.h y ALU-025.a).
- T71 queda: deuda de inactivos aparte en Cobranza y Reportes, y que el dueño la pueda dar por perdida. Tablero y PLAN-PRODUCCION corregidos.
- Interpretado, no preguntado: en T70 las «bajas del mes» son los pases a Inactivo (los eliminados no dejan rastro). Se escribe así en el prompt de Codex.

## 2026-10-28 — Claude CAB — Alcance cerrado hasta producción; T25 y T65 cerradas

- Carlos cerró el alcance (`PLAN-PRODUCCION.md`): Codex T70+T71, T45, T46, T51; Gemini T62, T12, T13; Claude T82. Después: test con T89/T25/T65, prueba de 3-4 días y producción. No entra nada más sin plantearlo.
- T25: OK visual de Carlos, cerrada (`d94147b0`). Las capturas de Gemini eran armadas (panel forzado, cuotas sin tildar); el panel es el real y Carlos aprobó igual.
- T65: en el OK visual salieron los botones del panel del alumno sin formato (`ds-btn-danger` con un guion, clase inexistente). Gemini lo corrigió (`56a220ee`) y Carlos lo aprobó; cerrada (`559239a5`). «Eliminar serie» queda con dos palabras: una sola no describe la acción (Carlos).
- Prompt de T62 para Gemini: [PROMPT-GEMINI-T62.md](../06-pruebas/PRU-04/PROMPT-GEMINI-T62.md).
- Pendiente de Carlos antes del prompt de T71: contradicción con T84 (interruptor sin confirmación vs. «la baja avisa cuánto debe y pide motivo»). Propuesta: instantáneo si no debe; panel con deuda y motivo si debe.
- Señalados fuera del alcance para que decida: T10 privacidad, B12 reportes y MF6 clases particulares (marcada «ahora»).

## 2026-10-28 — Claude CyE — T89 aprobada en lógica

- T89 (Codex, `6da3f3a5`): consulta de otra caja sin escritura, autor y recibo en Movimientos, Historial en Cashflow, «Todos ausentes» en Inicio y hora en Detalle. 7/7 acá. Falta OK visual de Carlos. [Informe](../06-pruebas/PRU-04/VERIFICACION-T89-CLAUDE.md).

## 2026-10-28 — Claude CyE — T25: cobrar a un inactivo pregunta si vuelve

- Carlos: T25 queda solo con la regla 3. La 1 (cobrar de más) pasa a MF11; la 2 la cubrió B3 dinero y la 4, T88.
- Regla decidida hoy: «No» solo cuando se cobra deuda vieja. Si el cobro incluye el mes en curso o uno futuro, solo Sí o Cancelar. Contrato enmendado (estados/cobranza, después de T38).
- Hecho: 409 `requiere_reactivacion` en `CajaWebController::pagar`; panel `modal-reactivar` con Cancelar, No y Sí; `PagoCuotaService` no reactiva con `reactivar=false` y rechaza «No» con el mes. `CobroInactivoT25Test` 9/9. Falta verificación de otro agente y el OK visual de Carlos; no se compiló el front porque Codex servía capturas desde `public`.

## 2026-10-28 — Claude CyE — Rojos de T86: era la prueba

- Codex devolvió T65 a Gemini (`f5d0343c`): recargo impago da 500 al eliminar, Sueldos sigue sumando la cuota borrada, excepción sin origen da 500.
- Los dos rojos de T86 que vio Codex: la prueba fijaba el reloj y la cobertura del historial nace con la hora real. Corregida la prueba (8/8). Producto y sitio de prueba sin efecto. [Detalle](../06-pruebas/PRU-04/VERIFICACION-T86-CLAUDE.md).

## 2026-10-27 — Claude CyE — T88 aprobada en lógica; B3 devuelta

- T88 y B3 dinero (Codex, `17f99d13`): aprobadas en lógica; falta el aspecto de Carlos. B3 asistencia (`51d26fb8`): devuelta a Codex.
- Defecto: todo alumno de la primera carga queda como nuevo y pide motivo desde la 3a clase. En el sitio de prueba son 25 alumnos; en producción sería todo el club. Prueba roja en `evidencia-verificacion-t88-b3-claude`. [Informe](../06-pruebas/PRU-04/VERIFICACION-T88-B3-CLAUDE.md).
- T54 asignada a Gemini y T65 a Codex para verificar (pedido de Carlos).
- B3 segunda vuelta (Codex, `1de85aef`): aprobada en lógica, la primera carga ya no es nueva. T88 y B3 esperan el aspecto de Carlos.
- T88 y B3 cerradas con OK visual de Carlos; no se suben a prueba hasta T65 (Codex) y los OK de T54 y T87.
- T54 segunda vuelta (`a9e7e37b`): aprobada en lógica; falta OK visual de Carlos.
- T54 (Gemini, `c4094222`) devuelta: bien en listado, autocompletar y Cobrar; el Historial de caja sigue buscando exacto. [Informe](../06-pruebas/PRU-04/VERIFICACION-T54-CLAUDE.md).

## 2026-10-27 — Claude CyE — T87 verificada en lógica; contador corregido

- T87 (Gemini, `b079b0ca`): conteos de Nueva liquidación por mes elegido; prueba roja con el código anterior. Falta el OK visual de Carlos. [Informe](../06-pruebas/PRU-04/VERIFICACION-T87-CLAUDE.md).
- Gemini declaró 1098 pruebas contando 19 de Codex (T88/B3) sin commitear en la carpeta compartida. En el repositorio hay 1079; corregido. Codex suma las suyas al commitear.
- T88 y B3 asignadas a Codex con prompt (27/10).

## 2026-10-27 — Claude CyE — T93 verificada y subida; producción leída

- T93 (Gemini, `ada0ac06`) aprobada: sin rubro no hay subrubros; edición y vuelta de error conservan lo elegido. Sitio de prueba en `f8d71d5c`, sin migraciones, /login 200. Observación: `display: none` en opciones puede no ocultarlas en iPhone; no comprobado.
- Producción leída sin tocar (anotado en PLAN-PRODUCCION): `314e485` del 22/09, 7 migraciones pendientes de su propio código + 25 nuevas, 0 medios de pago y 3 subrubros.

## 2026-10-27 — Claude CyE — Verificación de T86 (Codex): lógica aprobada

- La migración, en solo lectura sobre el sitio de prueba, cambiaría exactamente Op-Sandra, Op-Pablo y Op-Rocío. Pruebas de Codex 32/32 en mi base.
- Prueba propia: sin la reserva, la operativa igual no se carga su sueldo desde la caja forzando (la caja exige renglón de operativa que afecte caja). La prueba del hallazgo 22.5 se dio vuelta. Seguridad 23/23.
- Falta el OK visual de Carlos de Reportes → Sueldos (Codex amplió el alcance con su confirmación). T92 cerrada por Codex. Sitio de prueba de `500ce7bc` a `79340268` (T90, T92, T85; T32 ya estaba): respaldo `/root/respaldos/wingstest-antes-despliegue-2026-10-27-0808.sql.gz` (el primer intento salió vacío y se rehizo antes de seguir trabajando; el despliegue no tocó la base), build, 0 migraciones, cachés; /login 200, CSP bloqueando, reloj simulado intacto. Después, con el «Parte visual OK» de Carlos, T86 cerrada y subida (`f5306953`): respaldo `wingstest-antes-t86-2026-10-27-0811.sql.gz`, migración DONE (solo las tres operativas sin reserva), /login 200. [Informe](../06-pruebas/PRU-04/VERIFICACION-T86-CLAUDE.md).

## 2026-10-27 — Claude CyE — Verificación de T85 (Gemini): aprobada

- Código de `ds-app.js` leído; los seis interruptores con `data-url` responden JSON; sin estilo `:disabled` (el aspecto no cambia).
- Chrome real, segundo clic de mouse a los 300 ms con la respuesta retenida 1,5 s: con T85, 1 pedido y sin cuota; con el código anterior, 2 pedidos y cuota de reingreso (caso Agustín). Control: un clic posterior sí se manda. [Informe](../06-pruebas/PRU-04/VERIFICACION-T85-CLAUDE.md).
- Observación menor: con sesión vencida el aviso no dice que hay que volver a entrar. T85 cerrada; se sube a prueba con T92.

## 2026-10-27 — Claude CyE — T92 segunda vuelta: Revisión no se cuenta dos veces

- Codex devolvió T92 (`5c851d38`): al repetir la generación, quien ya estaba en Revisión se volvía a contar (constancia 1, 2, 4 con dos alumnos). Correcto.
- `GenerarDeudasMensualesCommand`: solo cuenta la fila de Revisión si es nueva (`wasRecentlyCreated`). Prueba permanente con el caso de Codex (1, 1, 2), roja sin el arreglo. Corte 1059; rápida 933/6 omitidas (solo el contador en rojo, corregido).
- T90 y T32 cerradas por Codex y Gemini. T92 vuelve a Codex.
- **Error mío:** `6a06cf09` incluyó también la entrega de T85 de Gemini (`ds-app.js`, `IMPLEMENTACION-T85.md`, `evidencia-t85/`, `LOG-GEMINI.md`), que estaba preparada en la misma carpeta. Agregué lo mío por nombre pero hice `git commit` sin limitarlo a esos archivos. La autoría de T85 es de Gemini. Ya estaba subido: no se reescribe.
