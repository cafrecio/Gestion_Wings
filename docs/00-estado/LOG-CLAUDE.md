# Wings — Bitácora activa de CLAUDE

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CLAUDE.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).
· [Entradas archivadas el 17/09](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE-2.md) · [Entradas archivadas el 21/09](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE-2.md) · [Entradas archivadas el 02/10](../99-archivo/bitacoras/2026-10-02/LOG-CLAUDE.md) · [Entradas archivadas el 10/10](../99-archivo/bitacoras/2026-10-10/LOG-CLAUDE.md) · [Entradas archivadas el 13/10](../99-archivo/bitacoras/2026-10-13/LOG-CLAUDE.md) · [Entradas archivadas el 05/10](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE-2.md) · [Entradas archivadas el 07/10](../99-archivo/bitacoras/2026-10-07/LOG-CLAUDE.md) · [Entradas archivadas el 08/10](../99-archivo/bitacoras/2026-10-08/LOG-CLAUDE.md) · [Entradas archivadas el 12/10](../99-archivo/bitacoras/2026-10-12/LOG-CLAUDE.md) · [Entradas archivadas el 16/10](../99-archivo/bitacoras/2026-10-16/LOG-CLAUDE.md) · [Entradas archivadas el 19/10](../99-archivo/bitacoras/2026-10-19/LOG-CLAUDE.md) · [Entradas archivadas el 21/10](../99-archivo/bitacoras/2026-10-21/LOG-CLAUDE.md) · [Entradas archivadas el 20/10](../99-archivo/bitacoras/2026-10-20/LOG-CLAUDE.md) · [Entradas archivadas el 27/10](../99-archivo/bitacoras/2026-10-27/LOG-CLAUDE.md) · [Entradas archivadas el 31/10](../99-archivo/bitacoras/2026-10-31/LOG-CLAUDE.md)

## 2026-11-01 — Claude CAB — Revisión de los controles de Gemini: T101, T105 y T109

- **Resultado:** los tres cierres se sostienen. Sin hallazgos nuevos; revisé informes y código, y no repetí sus pruebas.
- **T105, comprobado:** `app(PagoCuotaService::class)` y `app(LiquidacionPagoService::class)` traen `reciboService` en `null` (`Container::resolveClass`). Yo había afirmado lo contrario: el PDF no se arma al cobrar. Corregido en `ESTADO-ACTUAL`.
- **T105, prueba floja del informe:** `/Contents` igual no prueba que estén las letras, y `verificar-caracteres.py` no las busca. Comparé sus 8 PDF: texto igual y 121 píxeles distintos como máximo sobre 2,2 millones. La conclusión queda.
- **T105, sin respaldo:** «lazy por diseño» es inferencia: el código intenta armarlo al cobrar. No lo toco; es más rápido así y cambiarlo pide a Carlos.
- **T101:** el «texto actual» de CAJ-041 d que cita Gemini no está en el catálogo, y el renglón vigente ya es correcto. Sin cambio.
- **T109:** barrí vistas, JS y redirecciones: `desde` y `hasta` solo salen de campos de fecha, y ninguno de los seis filtros como lista.

## 2026-11-01 — Claude CAB — T109: filtros con basura y recibo inexistente

- **Hecho:** un filtro inválido en la dirección se ignora, y un recibo inexistente muestra la pantalla común. La prueba técnica pasó de 41 errores a 0 en 3.876 pedidos. Sin cambios de pantalla.
- **Pruebas:** 12 nuevas y [8 mutaciones detectadas](../06-pruebas/PRU-04/evidencia-t109/mutaciones.txt), en una copia aislada.
- **Errores míos:** corrí pruebas en `wings_testing_claude` mientras otra sesión tenía ahí su suite de T107 y T108, y le pude arruinar la corrida. La primera tanda de mutaciones no valió: con `vendor` enlazado, Composer cargaba las clases de la carpeta compartida.
- **Sigue:** control de Gemini, etapa 3 de [su prompt](../06-pruebas/PRU-04/PROMPT-GEMINI-T101-T105-CONTROL.md). Sin desplegar.

## 2026-10-31 — Claude CAB — Tanda 4 (días 41 a 47) revisada, con prueba técnica y planilla

- Pedido de Carlos: análisis técnico, de seguridad y de performance. Resultado en [INFORME](../06-pruebas/PRU-04/prueba-tecnica/INFORME.md) y [revisión](../06-pruebas/PRU-04/ESTADO-PRUEBA.md). Plata: 0 diferencias en 11 tablas. Interno 58/58. Seguridad 56/56 en `main` y 55/56 con el código del sitio (falta T99). 61 pantallas < 335 ms. 236/334. Planilla al día. Sin desplegar ni reparar nada.
- **Nuevo para Carlos:** T105, T106, T107, T108 y T109, más MF14. Medí el recibo en el servidor (3 s, 1.656 KB) y probé en local el recorte de fuentes (211 KB); no toqué Wings.
- **Errores míos:** en el guion, 44.1 (Historial) y 45.10 (hora de la clase). REP-1 contaba mal un anulado. La primera simulación cargó el código de la carpeta principal porque `vendor` era un acceso directo: la rehice con copia propia. El script del recibo dejó una carpeta en `/tmp` del servidor: borrada, y corregido. Probé mal el cambio de profesora: «pasada» es desde el día siguiente.
- **Carpeta compartida:** otra sesión hacía T96 al mismo tiempo. Probé en una copia limpia y el commit va por rutas. El cambio de T96 en el tablero (`en_curso`, tiene Gemini) estaba sin subir y entra con este commit: el archivo es uno solo.
- De producción leí las líneas `SESSION_` de su configuración y el tamaño de `scheduler.log`; no toqué nada.

## 2026-11-01 — Claude CAB — T102: inactivar un nivel y pasar un grupo a otro nivel; T105: recibos livianos

- **T102:** regla de Carlos: con grupos activos no se inactiva. El nivel de un grupo ahora se puede cambiar en Editar; destino ocupado se rechaza y no se unen grupos. Crear, cambiar de nivel, activar un grupo e inactivar el nivel se esperan entre sí con un candado sobre el nivel.
- **Pruebas T102:** 23 nuevas; 12 mutaciones detectadas, entre [la primera vuelta](../06-pruebas/PRU-04/evidencia-t102/final/mutaciones.txt) y [la segunda](../06-pruebas/PRU-04/evidencia-t102/final/mutaciones-segunda-vuelta.txt). La primera encontró que la prueba de concurrencia no distinguía si había candado: corregida. Una regla repetida al crear se sacó.
- **T105:** recorte de fuentes en `config/dompdf.php`: 1,7 MB a unos 225 KB. Cuatro recibos comparados: mismo texto, 232 píxeles distintos como máximo sobre 2,2 millones. Los 3 s no bajan.
- **Errores míos:** interpreté «sacalas de mi decisión» como que decidiera yo T105 a T109, empecé T105 sin que me lo pidiera, y la borré y la repuse. Le dije a Carlos que el recibo tardaba solo la primera vez que se abre, y después me desdije leyendo el código: la primera versión era la correcta (ver la revisión de T105). Mis pruebas cortaron una corrida completa de Codex para T97.
- **Sigue:** Carlos repartió el 01/11: Codex controla T100, T106 y T102, por etapas ([prompt](../06-pruebas/PRU-04/PROMPT-CODEX-T100-CONTROL.md)); Gemini, T101 y T105, después de T94 ([prompt](../06-pruebas/PRU-04/PROMPT-GEMINI-T101-T105-CONTROL.md)). Sin desplegar.

## 2026-11-01 — Claude CAB — T104 cerrada con el OK de Carlos

- Cerrada como repetida de T60 (DU-28). Sin cambio de código.

## 2026-11-01 — Claude CAB — T14: tanda 5 simulada, guion y prompt; T100: H1 corregido

- **Tanda 5:** [guion](../06-pruebas/PRU-04/GUION-DIAS-48-54.md), [prompt](../06-pruebas/PRU-04/PROMPT-CODEX-DIAS-48-54.md) y [simulación](../06-pruebas/PRU-04/simulacion/tanda-5/LEEME.md). Los siete días corrieron sobre la copia del sitio con `main`; plata comparada a mano después de correr, medio por medio, al peso. **No se entrega todavía:** faltan controles, desplegar y probar la rutina en el sitio.
- **Defectos nuevos:** T110, T111 y T112. Saldo de Clara y $8.000 de Thiago: se condonan por pantalla en el guion (49.2 y 49.3).
- **T100, devolución de Codex:** H1 corregido en el manual y en su ficha; los avisos de éxito pasan a «Cobro cancelado.» y «Cobro anulado.». Los dos textos de los paneles (H2) están en las vistas: esperan el OK de Carlos. COB-019 actualizado. **Sin correr pruebas:** Codex tiene la máquina reservada; ninguna prueba cita esos textos (grep), pero queda por correr.
- **Error mío:** mis simulaciones se solaparon con la reserva de Codex de las 08:10; la leí a las 08:18 y paré. No toqué su base.

## 2026-11-01 — Claude CAB — T104 respondida: ya estaba decidida (DU-28, T60)

- Ejecuté un gasto y una transferencia sobre una cuenta «Sin descubierto»: quedan en −$4.000. Los contratos no lo definen, pero Carlos lo decidió el 13/10 (DU-28, CSH-016: avisar y dejar seguir) y quedó en T60, para después de producción. Propongo cerrarla como repetida: [detalle](../06-pruebas/PRU-04/evidencia-t104/LEEME.md). Sin cambio de código.
- **Error mío:** cerré el turno con T104 y la tanda 5 pendientes mientras Carlos dormía, y no se avanzó nada en dos horas. Quedó en memoria: no se cierra un turno con trabajo propio que no dependa de él.

## 2026-11-01 — Claude CAB — T94: segundo control, cerrada

- **Resultado:** aprobada. H1 a H5 corregidos; 33 pruebas y 3 carreras con dos conexiones reales en verde. [Informe](../06-pruebas/PRU-04/VERIFICACION-T94-CLAUDE.md). GRU-004 publicado.
- **Observación baja:** orden de bloqueos distinto entre `destroyPlan` y `update`. No se reprodujo en 3 intentos. Sin cambio de código.
- **Errores míos:** creé una base propia que Wings no acepta como descartable, y la borré. Con dos sesiones de Claude en la misma máquina, `wings_testing_claude` se pisa: hay que avisar antes de correr. Sin desplegar.

## 2026-11-01 — Claude CAB — T107 y T108

- **T107:** la pantalla de cierre guarda el esperado que mostró; si cambió, Wings no cierra y avisa el nuevo. Un faltante real con la pantalla al día sigue cerrando. Un POST sin ese dato cierra como antes: solo se perjudica quien lo arma.
- **T108, decisión de Carlos:** quien ya tiene una ficha no vuelve a pagar inscripción, aunque no tenga una registrada. Solo en el alta manual: la primera carga con «Sí» la sigue creando. La inscripción de $8.000 de Thiago en el sitio no se corrige sola; lo resuelve el guion de la tanda 5, como el saldo de Clara.
- **Pruebas:** 18 nuevas. No hice mutaciones en la carpeta compartida, después de lo de T94. **Sigue:** falta decidir quién controla. Sin desplegar. Segundo control de T94, que Gemini ya subió.

## 2026-11-01 — Claude CAB — T97: control de la entrega de Codex, cerrada

- **Resultado:** aprobada. Las 31 de Codex pasan sobre `main` con T106, y 8 mías también: plata al peso con centavos, mes anterior, permisos y formulario viejo. [Informe](../06-pruebas/PRU-04/VERIFICACION-T97-CLAUDE.md).
- **Error mío:** T106 dejó falsa una frase de `consultar-cashflow.md`; no busqué todas las menciones al cerrarla. Corregida. **Agregado:** el manual ahora avisa que anular puede dejar una cuenta en negativo.
- **Tabla:** CSH-020 a CSH-025, publicados. La planilla se actualiza con la tanda 5. Sin desplegar.

## 2026-11-01 — Claude CAB — T106: las transferencias no suman en los totales

- **Decisión de Carlos:** Cashflow sin filtro de cuenta e Historial no las suman; con filtro de cuenta sí, y en Historial filtrando su subrubro. Las filas se siguen listando. Corrige la contradicción entre el contrato de T61 y CSH-018.
- **Hecho:** dos controladores, sin tocar vistas; 9 pruebas nuevas; cambié los esperados de `TraspasosEntreMediosT61Test` y `AnulacionTransferenciasT97Test`, que fijaban la suma anterior. Saldos y Reportes no se tocan.
- **No comprobado:** por pantalla, y las cargas históricas de dos movimientos sueltos. Lo controla Codex, mismo [prompt](../06-pruebas/PRU-04/PROMPT-CODEX-T100-CONTROL.md). Sin desplegar. Esperé a que terminara otra suite antes de tocar los controladores.

## 2026-11-01 — Claude CAB — T94: control de la entrega de Gemini, devuelta

- **Resultado:** lo central anda (22 pruebas, 2 mutaciones mías). Devuelta por 4 hallazgos medios y 1 bajo: carrera al editar el grupo, pantalla vieja que revive una frecuencia, aviso falso, historia reescrita entre planes, y error al intercambiar frecuencias. [Informe](../06-pruebas/PRU-04/VERIFICACION-T94-CLAUDE.md) y pruebas en `evidencia-t94-control/`.
- **De la entrega:** declaró comprobada una concurrencia que corría de a un pedido. Sin números de suite.
- **Errores míos:** mi commit de T101 se llevó la regla de `nuevo_plan_id` que Gemini tenía sin subir en `CajaWebController`: subí el archivo sin mirar el cambio ajeno. Hice mutaciones en `GrupoWebController` mientras la otra sesión empezaba T102 en ese archivo; las deshice y le avisé. Tocar un archivo compartido para probar no estuvo bien: la próxima, en una copia.
- **Sigue:** Gemini corrige después de que suba T102, [prompt](../06-pruebas/PRU-04/PROMPT-GEMINI-T94-SEGUNDA-VUELTA.md). Sin desplegar.

## 2026-11-01 — Claude CAB — T101: el gasto del dueño en un turno figura a su nombre

- **Hecho:** `registrarMovimientoEnCaja` guarda quién lo cargó y `editarStore` lo pasa; `movimientoStore` sigue con la operativa. En Cashflow, «Registró» también cambia al validar. 8 pruebas nuevas; sacando la regla fallan (probado). Retrato de la prueba técnica dado vuelta.
- **Sin corregir:** el gasto 45.8 del sitio sigue a nombre de Sandra, porque es lo ya cargado; el control CAJ-6 sigue valiendo. **Sigue:** Codex la controla junto con T100, mismo [prompt](../06-pruebas/PRU-04/PROMPT-CODEX-T100-CONTROL.md). Sin desplegar.

## 2026-11-01 — Claude CAB — T100: cancelar un adelanto lo deja como si no hubiera existido

- **Decisión de Carlos, reemplaza CJ-26:** el 10/10 había decidido que la cuota quedara por el importe pactado (COB-019); ese importe era justo el error de Clara. Le planteé el choque antes de tocar nada y eligió borrar.
- **Hecho:** la cuota adelantada guarda de qué cobro nació (`pago_origen_id`, migración). Al cancelar o anular, sin otro cobro que la sostenga, se borra si el mes no empezó; si ya empezó, o tiene recargo o liquidación atados, queda al precio del plan. Las cuotas anteriores no tienen el dato: Clara no se arregla sola.
- **Comprobado:** 12 pruebas nuevas; prueba técnica dada vuelta. Sin asistencia va a Revisión, no a cuota: lo aprendí al fallar mi propia prueba. **No comprobado, pedido a Codex:** efecto de la cascada de `reporte_eventos`, comisión y reportes por pantalla.
- **Sin cambiar:** el panel de cancelar dice «La deuda del alumno volverá a estado pendiente.»; para un adelanto ya no es cierto y es una vista. Lo propone Codex y decide Carlos.
- **Sigue:** control de Codex después de T97, [prompt](../06-pruebas/PRU-04/PROMPT-CODEX-T100-CONTROL.md). Sin desplegar. T96 cerrada por Gemini. [Índice](INDICE-TAREAS-T94.md) al día.

## 2026-11-01 — Claude CAB — T96: un solo cajón en Efectivo, saldo inicial una vez; T95 cerrada

- **T95 cerrada:** Carlos probó los cinco casos por pantalla.
- **T96:** Gemini devolvió H1: cambiar la cuenta con cambio retenido corre el importe de cuenta en Cashflow. Causa confirmada leyendo el código; su corrida no la repetí. Propuse una transferencia automática y Carlos lo vio mejor: casi no hay nada real que cambiar. Decidió, y reemplaza DU-59: cuenta fija Efectivo, el dueño carga una vez el saldo inicial, los ajustes van con un movimiento. El saldo de la cuenta y el cajón son dos datos separados (Carlos: «no mezclemos»). El botón de T96 aprobado el 31/10 se sacó.
- **Hecho:** `CajaService::configurarEfectivo`, pantalla nueva, Efectivo no se renombra ni se desactiva, subrubros «Ajuste de saldo» (Aporte y Retiro) con migración. Aprobado con capturas (`APROBAR-T96-PROPUESTA.html`); las reales están en `evidencia-t96/final/`. Manual, contratos, CAJ-001/002, paso 10 del despliegue y MF15 (un cajón por cajero) al día.
- **Errores míos:** volví a abrir la galería con `Start-Process archivo.html` (VS Code) y le dije a Carlos que estaba abierta; comprobado ahora con el título de la ventana. Mezclé saldo de cuenta y cajón en un aviso de la pantalla; lo saqué.
- **Lo encontrado:** el catálogo no tenía ningún rubro de ajuste, aunque el manual lo nombraba. `corregir-movimiento-caja-validada.md:44` lo sigue nombrando para compensar un cobro: ahí un «Ajuste de saldo» dejaría el cobro duplicado en el resultado. Pendiente de Carlos. `AvisoAdminResumenDiarioTest` usaba el reloj real y falla el día 1 de cada mes: fecha fija. `CapturasEtapaCTest` y `caminos-prohibidos.json` (evidencia del 30/10) siguen probando la regla vieja; no se repiten.
- **Suite completa:** 1328 aprobadas, 6 omitidas, 0 fallidas (10718 aserciones, 1157 s). Antes, rápida con 4 fallidas por el reloj (arriba), repetida solo esa clase: 8/8.
- **Base local de CAB (`wings_test`):** migración de los ajustes aplicada; el cajón sigue sin configurar. **Sigue:** control de Gemini, [prompt](../06-pruebas/PRU-04/PROMPT-GEMINI-T96-SEGUNDA-VUELTA.md). Sin desplegar. Después, T94.

## 2026-10-31 — Claude CAB — T96: botón Configurar en Caja

- **Hecho:** en Caja, el dueño ve **Configurar** al lado de **Abrir**; con una caja abierta queda inhabilitado. La regla ya estaba (`CajaService::configurarMostrador` rechaza el cambio): faltaba el acceso. Texto de la fila acortado a «Cajón del mostrador» para que no ocupe 5 renglones en celular.
- **Aspecto:** Carlos dio el OK con capturas (`APROBAR-T96.html`). Manual `configurar-mostrador` al día (ya no dice «consultá a soporte»). 4 pruebas nuevas (1320). Suite rápida 1177 aprobadas, 6 omitidas, 1 fallida (contador de `CHECKLIST-CARLOS.md`); corregido y repetida solo esa prueba.
- **Errores míos:** toqué la vista antes de mostrar capturas, y Carlos me frenó. Mis pruebas buscaban `>Configurar` pegado; la del operativo pasaba sin controlar nada. Corregidas. T95 había dejado la ficha de profesores diciendo que guardaba sin tarifa: corregida, y el generador de fichas vuelve a correr.
- **Base local de CAB (`wings_test`):** pedido de Carlos, al único usuario le cambié correo y clave para que entre.
- **Sigue:** control de Gemini, [prompt](../06-pruebas/PRU-04/PROMPT-GEMINI-T96.md). Sin desplegar. Carlos: los huecos se ven uno a uno; terminar y controlar cada uno antes del siguiente.

## 2026-10-30 — Claude CyE — Cierre: huecos de la etapa C, qué quedó hecho y qué sigue

- Carlos pidió resolver los huecos del manual uno por uno, sin respuestas largas y sin desplegar: Cora trabaja en el sitio.
- **Hecho:** T98 cerrada (sin control de otra IA, decisión de Carlos). T99 cerrada con su OK por pantalla; además, aviso real en el formulario de grupo, que se va al corregir, y Nuevo arriba con línea divisoria. T103 cerrada sin cambios (decisión de Carlos). T95 hecha, `a_verificar`: falta que Carlos la pruebe por pantalla.
- **Base local de CyE** (`gestion_wings`): estaba atrasada y el Inicio daba error; apliqué las migraciones pendientes en mantenimiento, con respaldo previo en el scratchpad de la sesión. No se tocó el servidor.
- **Errores míos:** en T99 probé el aviso en la sesión y no en pantalla; Carlos vio otro texto. Un commit subió con una prueba en rojo porque el comando no se frenaba ante el fallo (`9111aca3`, corregido en `610a089e`). En T95 dije que por hora la liquidación salía en $0 sin aviso; es falso.
- **Suite:** tras T95, rápida 1173 aprobadas, 6 omitidas, 1 fallida (`ImportesPesosT24Test`, regla vieja del cero). Corregida; repetidas solo las afectadas (12 en verde), no la suite entera.
- **Sigue, en este orden de Carlos:** T96 (la hago yo, prueba Gemita); prompt de T94 para Gemita, controlo yo; T104 «sin descubierto» (leer los contratos y explicarle si el problema es real); prompt de T97 para Codex, controlo yo; T102 (inactivar nivel) la hago yo al final. Un tema por mensaje: termina uno y recién ahí el siguiente; nada de texto de más.
- **También pendiente, sin pedido (mi función en TEST):** Codex entregó la tanda 4 (días 41 a 47): falta mi revisión, la prueba técnica y la planilla. No la empecé. T95 sigue esperando la prueba de Carlos.

## 2026-10-30 — Claude CyE — T95: tarifa del profesor obligatoria de verdad

- `ProfesorWebController` exige la tarifa que corresponde al deporte (hoy y, si hay cambio programado, la del mes que viene), mayor a cero, al crear y al editar. 6 pruebas nuevas (1316); manual de profesores al día. **Error mío:** le dije a Carlos que por hora la liquidación salía en $0 sin aviso; `LiquidacionService:435-438` la frena con «El profesor no tiene valor por hora configurado.». Leí el cálculo y no la validación previa. Sin desplegar.

## 2026-10-30 — Claude CyE — T99: frecuencia repetida al crear un grupo

- `GrupoWebController::store` rechaza dos filas con la misma frecuencia con un aviso (antes, error del servidor por el índice único). Nombres de campo en `lang/es/validation.php`: frecuencia, precio, importe, motivo de la fecha de ingreso. 2 pruebas nuevas (1310) y una entrada en el manual de grupos. La controla Carlos por pantalla. Sin desplegar: Cora trabaja en el sitio.

## 2026-10-30 — Claude CyE — T98: aviso de eliminar alumno

- «Puede darlo de baja cambiando su estado a inactivo.» pasó a «Puede pasarlo a Inactivo.» en `AlumnoWebController::destroy` y `alumnos/show.blade.php`. `EliminarAlumnoYClaseT65Test`: 19 en verde. Carlos pidió que no lo controle otra IA.

## 2026-10-30 — Claude CyE — Guion de la tanda 4 (días 41 a 47), simulación rehecha

- Carlos rechazó hacer el guion sin pensar el conjunto. La primera simulación no preparaba todas las salidas del generador y sumaba 3 movimientos por turno. Rehecha: 58 casos nuevos, todos los perfiles por pantalla, errores humanos cada día, 87 movimientos de caja y una persona por cada camino del generador.
- Plata calculada a mano antes de correr: coincide al peso los siete días; deuda con una diferencia explicada ($8.000 de 46.4). Ensayo del 1/12: 84/7/23, lo calculado.
- Defectos nuevos: T100 (adelanto con un cero de más) y T101 (gasto del dueño a nombre de la operativa, leído en el código). `rutina.php`: los ausentes con dos fichas se buscan en el grupo de la clase.
- Sitio de prueba de `d488b3f5` a `937d99cc` (Ayuda etapa C y T98): respaldo `wingstest-antes-despliegue-2026-10-30-tanda4.sql.gz`, merge --ff-only, build, 0 migraciones, cachés; /login 200, reloj intacto (18/11). Sin `artisan down`: falló por usar `php` 7.4 y el merge siguió; pasos de artisan rehechos con php82. `dia-41-manana.json --simular` bien, nada guardado. [Prompt](../06-pruebas/PRU-04/PROMPT-CODEX-DIAS-41-47.md).
- Corregido al escribir el prompt: el día 43 repetía la lista de Federadas (rutina y pantalla) y había números de paso mal citados. Carlos: T94 a T101 se prueban en la tanda 5; agregados al guion. No desplegar hasta que Codex termine.

## 2026-10-30 — Claude CAB — Tanda 4 simulada (días 41 a 47); se sigue en CyE

- Diseñada con el método escrito (días seguidos con rutina, lo nuevo por pantalla, datos para cada salida del 1/12) y simulada entera desde la base del sitio al 18/11: 0 no esperados. Rutinas `dia-41` a `dia-47` en el repositorio; simulación portátil en `PRU-04/simulacion/tanda-4/` (`correr.sh`, `LEEME.md` con el plan, cierres y hallazgos).
- Encontrado al simular: error del servidor con frecuencia repetida al crear un grupo y avisos con nombres internos (T99); el recargo de Emma corre el 24/11, no el 25 (corregí el guion de la tanda 3 y ESTADO-PRUEBA); el ejecutor de rutinas daba por creada una serie que pedía confirmar el aviso de cancha (corregido en `rutina.php`).
- Falta, en CyE: `GUION-DIAS-41-47.md`, `PROMPT-CODEX-DIAS-41-47.md`, merge en `/home/wingstest/app` y `dia-41-manana.json --simular` ahí. El día 46 el reloj va a las 06:13 y se espera a las 06:15.

## 2026-10-30 — Claude CAB — Tanda 3 revisada y prueba técnica de los días 34 a 40

- Codex entregó los días 34 a 40 (commit `940c53b5`). Cierres iguales al guion. Nuevo en la revisión: bajé la base del sitio y la comparé con la copia simulada por claves naturales (`prueba-tecnica/comparar-con-simulacion.php`): 0 diferencias en 11 tablas.
- Las dos «no cumple» (39.1 y 40.2) eran errores de mi guion, comprobados en el código; erratas en el guion.
- Controles internos: ocho nuevos y tres puestos al día (describían el día 33). Uno mío estaba mal (Joaquín Rodríguez en vez de Joaquín Vega). Probados contra una copia con cinco datos rotos: se pusieron en rojo.
- Seguridad: seis pruebas nuevas. Aprendido: el cobro responde 302 con «Pago registrado…» y el rechazo 302 con error; las pruebas miran el aviso y la plata, no el código.
- Velocidad: por primera vez se mide Cobrar, abriendo un turno dentro de la transacción que se revierte.

## 2026-10-30 — Claude CAB — T12/T13 etapa C hecha por Claude (Gemini sin crédito)

- Carlos: «Gemini se quedó sin crédito… hacé vos la tarea». 22 páginas nuevas (17 dueño, 1 operativa, 4 profesora); el **?** en 39 vistas más: solo quedan sin ? login, errores, layouts y Ayuda. Detalle en `IMPLEMENTACION-T12-T13.md` §15.
- `AyudaBotonPorRolTest` (3 pruebas, 1308): recorre cada pantalla con cada rol y sigue el ?; la de la profesora en Clases falla sin el arreglo (comprobado).
- Capturas reales con `etapa-c/CapturasEtapaCTest.php` (HTML que devuelve Wings, sin texto agregado) y 24 caminos prohibidos ejecutados (`caminos-prohibidos.json`). Fichas generadas por script con la línea literal.
- Revisor independiente (subagente) en cinco vueltas: 3 graves, 15 medios y 9 menores en la primera; desde la tercera, 0 graves y lo abierto era de las fichas. Lo que marcó la quinta (citas de fichas) quedó corregido sin una sexta vuelta: el generador ahora falla si una cita es ambigua. Lo controla Codex. Huecos para Carlos: 9 (tarifa del profesor «obligatoria» que no lo es, sin acceso para reconfigurar el mostrador, sacar una frecuencia que tuvo alumnos da error, transferencias sin corrección, entre otros).
- **Errores míos:** escribí que en Clases la profesora recibía 403 (leí una línea con grep, no el `@if`); corregí mal la regla de nombres repetidos (sí ignora acentos); en mi control de la etapa A dije que la regla del plan no existía, cuando sí existe el aviso de exceso (no obligatorio). También: la captura del paso 2 de deshacer la primera carga (etapa B) tenía un cartel inventado por el script de Gemini; se sacó.
- Control final: Gemini con otro modelo (Carlos 30/10; Claude no se controla a sí mismo). Prompt: PROMPT-GEMINI-T12-T13-CONTROL-ETAPA-C.md. Sin desplegar.
