# Wings — Bitácora activa de CLAUDE

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CLAUDE.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).
· [Entradas archivadas el 17/09](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE-2.md) · [Entradas archivadas el 21/09](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE-2.md) · [Entradas archivadas el 02/10](../99-archivo/bitacoras/2026-10-02/LOG-CLAUDE.md) · [Entradas archivadas el 10/10](../99-archivo/bitacoras/2026-10-10/LOG-CLAUDE.md) · [Entradas archivadas el 05/10](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE-2.md) · [Entradas archivadas el 07/10](../99-archivo/bitacoras/2026-10-07/LOG-CLAUDE.md) · [Entradas archivadas el 08/10](../99-archivo/bitacoras/2026-10-08/LOG-CLAUDE.md)

## 2026-10-10 — Claude CyE — DONDE QUEDE al cierre del 10/10

**Sitio de prueba** (`test.gestionar-te.com.ar`) en `2e059ff` + docs: 100 alumnos de la primera
carga (verificada dos veces contra la base, 0 diferencias) y tres dias de PRU-04 hechos por
Codex y verificados por mi. Produccion sin tocar (una sola consulta de lectura).
**Reloj simulado:** quedo en lunes 12/10/2026. Se mueve con `php82 artisan wings:fecha-simulada
"AAAA-MM-DD HH:MM"` como wingstest. Desde hoy avanza solo y frena a la medianoche simulada;
clavado, el limite de 5 ingresos por minuto no se limpiaba y nadie podia entrar. No fijarlo
justo a las 08:00 (resumen diario al mail y Telegram reales de Carlos).
**Cerrado hoy:** T1, T15 (errores en castellano, Codex), T16 (clasificacion de subrubros, mio;
Codex lo devolvio dos veces: cambio de tipo de rubro y guardados simultaneos), T17 (Inicio con
inscripciones, Gemini), T19 (misma deuda en Inicio, Reportes y Cobranza: activos, cuotas +
inscripciones, mio), T20 (Cobrar en el celular, Gemini), boton Cobrar del admin en Caja.
**Abierto:** T18 cobro adelantado: pantalla aprobada por Carlos, Gemini la devolvio (mes futuro
contaba como deuda en Cobranza; periodo «mes 13» daba error), corregido en `567f030`; falta que
Carlos la vuelva a pasar a Gemini. Decision de Carlos: al anular un adelantado el mes queda
pendiente con el importe pactado. T21 clase cancelada el mismo dia no se ve: lo hago yo, plan
acordado (mostrarla en «Hoy» apagada con motivo + que el filtro Cancelada encuentre las de hoy).
T14 sigue: Carlos decide el dia 4. T12 manual (Gemini, despues de la prueba), T13 Ayuda dentro
de Wings (falta quien lo programa). B12 espera la prueba manual de Carlos.
**Decisiones de Carlos del dia:** un mes que no empezo no es deuda; deuda = alumnos activos,
cuotas + inscripciones, igual en todas las pantallas; pagar adelantado congela el precio y el
importe es editable; Indumentaria es aporte de los duenos, el resto del catalogo es del club;
domingos y feriados pueden ser activos; la profesora puede abrir clases ajenas (suplencias).
**Como trabajar con Carlos (lo marco hoy, fuerte):** una cosa por vez y bien hecha; no leer
entregas de Gemini o Codex hasta que el las pase; primero los prompts de los otros, despues lo
mio. Gemini informo dos veces trabajo que no hizo (Excel a mano): sus informes se revisan
contra la base. Sin probar por nadie: llenar la plantilla de primera carga a mano.
**Antes de produccion:** clasificar sus subrubros uno por uno con Carlos (T16).

## 2026-10-10 — Claude CyE — A23 cerrado, B10 hecho y publicado, sitio de prueba actualizado

**A23 cerrado:** opere el sistema por pedidos reales y compare el inicio del admin contra la
cuenta a mano en cuatro momentos; 52 de 52 valores coinciden y no hay doble conteo al validar
la caja (`B12-A23/VERIFICACION-A23-INICIO.md`). Mi primera comparacion dio mal por armar mal
los importes de mi propia prueba, no por el inicio.
**B10 hecho por mi**, a pedido de Carlos: campo «CBU o alias» en profesor, ficha, pago de
liquidacion y usuario operativo; control al guardar. Carlos aprobo las capturas («Ok»). Falta
que Codex verifique la regla. En el camino vacie dos controladores con un comando mal
escrito; restaurados desde git en el momento. No editar archivos con php en linea: usar Edit.
**Carlos miro desde su celular** (mide 360 de ancho, no 375): salieron A57 y A58, que hizo
Gemini y Carlos aprobo en el chat de Gemini. El arreglo de A58 apila en una columna los datos
de TODAS las tarjetas en celular (16 vistas), no solo Clases: avisado a Carlos para que lo mire.
**Suite completa en `wings_testing_claude`: 581 aprobadas, 2 omitidas.** Las pruebas de
Reportes ya corren en cualquier base de pruebas.
**Tarde:** A42 y B10 cerrados (B10 lo devolvio Codex y la segunda vuelta la verifico Gemini).
Carlos dio OK a las vistas del celular; falta Cobrar, que necesita alumnos. Reporto que
Reportes «no lleva a ningun lado»: era la redireccion muda a Primera carga; ahora avisa
(`3cb0264`, desplegado en test). Pendiente: B11 al final y solo, prueba manual de la primera
carga, T9 metodo comun en `Gestion_CAB`, T10 borradores de privacidad.
**PRU-03:** Gemini hizo la primera carga en test (90 alumnos). Verifique las 90 filas contra
la base dos veces: 0 diferencias (hoy 100 alumnos). Gemini informo 10 altas a mano con
cronometro: falso. T15 verificado. Dia 1 de PRU-04 verificado; T16 y T18 hechos por mi, test en `3a3849a`.

