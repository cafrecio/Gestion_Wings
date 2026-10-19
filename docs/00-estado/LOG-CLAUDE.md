# Wings — Bitácora activa de CLAUDE

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CLAUDE.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).
· [Entradas archivadas el 17/09](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE-2.md) · [Entradas archivadas el 21/09](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE-2.md) · [Entradas archivadas el 02/10](../99-archivo/bitacoras/2026-10-02/LOG-CLAUDE.md) · [Entradas archivadas el 10/10](../99-archivo/bitacoras/2026-10-10/LOG-CLAUDE.md) · [Entradas archivadas el 13/10](../99-archivo/bitacoras/2026-10-13/LOG-CLAUDE.md) · [Entradas archivadas el 05/10](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE-2.md) · [Entradas archivadas el 07/10](../99-archivo/bitacoras/2026-10-07/LOG-CLAUDE.md) · [Entradas archivadas el 08/10](../99-archivo/bitacoras/2026-10-08/LOG-CLAUDE.md) · [Entradas archivadas el 12/10](../99-archivo/bitacoras/2026-10-12/LOG-CLAUDE.md) · [Entradas archivadas el 16/10](../99-archivo/bitacoras/2026-10-16/LOG-CLAUDE.md) · [Entradas archivadas el 19/10](../99-archivo/bitacoras/2026-10-19/LOG-CLAUDE.md)

## 2026-10-19 — Claude CyE — Producción y prueba: dos bases que se confundieron

- Carlos: «En test tiene que haber 100». Comprobado por SSH, solo lectura: producción `wings` 0 alumnos;
  prueba `wingstest` 100 activos (muestra 241–245 mirada).
- T66 (Codex): su relevamiento «sitio de prueba» consultó `wings` (producción, vacía). Rehecho en
  `wingstest`: 0 clases sin profesora en 79, filas revisadas. Informe corregido con nota.
- T56 (Gemini): estaba bien, consultó también `wingstest` (0 de 100 sin plan); comprobado de nuevo: 0.
- `SERVIDOR.md` documentaba solo producción: agregada la tabla de los dos sitios.

## 2026-10-19 — Claude CyE — Cierre de sesión: pendientes al día

**Resuelto de la lista de pendientes de CAB (entrada de abajo):** verificadores asignados y tareas
cerradas — T66 (Gemini), T68 (Claude + visual de Carlos), T59 y T47 (Gemini + visual), T74 (Codex,
con corrección visual tras el rechazo de Carlos), T34 (Codex), T40 (Gemini). DU-57 y SIS-012 corregidas.

**Sigue abierto**
- **T73** a verificar en navegador con clics; sin verificador asignado.
- **T40 debe desplegarse antes del 1/11** (incluye migrar `generaciones_cuotas`). Nada desplegado hoy.
- ~~Base de producción~~ cerrado: Gemini solo leyó (0 activos sin plan, para T56) y Carlos aclaró que la base
  de producción no tiene alumnos. Sin riesgo; no queda pregunta pendiente.
- Observación T56 sin decisión: un inactivo sin plan que paga queda inactivo (no alcanzable hoy).

**Aclaraciones de la sesión**
- Carlos: «No puede haber un alumno sin plan». T56 lo impide; la rama «sin plan» del generador es una
  defensa previa a T56, no un caso de uso. El informe de T40 de Gemini describió mal la revisión
  (no va «por plan sin precio») y un «⚠️» que el código no tiene; la aprobación vale.
- Verificaciones de T34 y T40 corrieron solo suite rápida; las cubren suites completas sobre el mismo
  código (Codex en T74, 935 verdes; Claude en T40).
- Bitácora archivada: [entradas hasta T59/T47](../99-archivo/bitacoras/2026-10-19/LOG-CLAUDE.md).

## 2026-10-19 — Claude CyE — T73: cero JavaScript dentro de las vistas

- Pedido de Carlos («Hace T73»); orden del 13/10 (AGENTS §5-bis). Salen 8 bloques y 8 `onsubmit`.
- Ingreso: ojo de `ds-app.js`. Grupos/Profesores: `data-enviar-al-cambiar`. Cinco módulos nuevos
  (caja-editar, clases-show, clases-index, liquidaciones-create, alumnos-index), datos por `data-*`.
  Confirmaciones: `form[data-confirmar-envio]` en `ds-app.js`, en captura, antes del anti doble-submit.
- `CspSinCodigoIncrustadoTest` en 0/0. `AlumnoAutocompleteViewSecurityTest` apunta al módulo.
- Suite rápida `wings_testing_claude`: 819 aprobadas, 4 omitidas, 1 fallida (esa prueba, corregida
  después; las dos en verde). Build, `php -l`, `node --check`, vistas compilan.
- No lo probé en navegador: por indicación de Carlos no tomo trabajo con imágenes. Falta verificador
  con clics reales (lista en IMPLEMENTACION-T73.md). Sin despliegue.

## 2026-10-19 — Claude CyE — T40: las cuotas del día 1 se reintentan solas y avisan

- DU-52 / CBZ-020. `cobranza:generar-deudas --si-falta` todos los días a las 6; día 1 genera; después
  solo si el mes no figura en la tabla nueva `generaciones_cuotas` y el anterior sí. Avisos por
  `AvisoAdminService` (atraso, fallo) y línea en el resumen diario. Contrato de cobranza §2 actualizado.
- 9 pruebas nuevas; relacionadas 63 verdes. Suite completa en `wings_testing_claude`, esperó a que
  terminara la de Gemini: 929 aprobadas, 4 omitidas, 1 fallida (contador de pruebas, corregido a 934).
- En la carpeta quedan 5 pruebas de Gemini sin subir (T34): el contador local da 939; le toca a Gemini.
- Mi entrada de T40 en ESTADO-ACTUAL la subió Gemini dentro de 13e26f3b; el contenido es el mío.
- A verificar, sin verificador asignado. No necesita visual. Sin despliegue: requiere migrar.

## 2026-10-19 — Claude CyE — Suite en dos velocidades: regla para los tres agentes

- Decisión de Carlos (19/10): «Ponelo como regla para todos los agentes».
- 12 archivos marcados `#[Group('lento')]` (concurrencia y reportes: 13 de 16 min en la corrida
  de T66). Ninguna prueba se borró ni cambió de contenido.
- Regla en AGENTS.md §6-ter; §6, GEMINI.md, CLAUDE.md y RESUMEN-ARRANQUE apuntan ahí.
  CLAUDE.md ya no declara «129 tests» (cifra del 06/09).
- Suite rápida en `wings_testing_claude`: 806 aprobadas, 4 omitidas, 0 fallidas, 216 s.
  Guardianes documentales: 7 aprobadas. La suite completa no se corrió: no cambia código.
- Nota propia: las pruebas de T68 de esta sesión las corrí sin `DB_DATABASE`, en `wings_testing`.

## 2026-10-19 — Claude CyE — T68 cerrada; idea de suite en dos velocidades

- T68: verifiqué la lógica de H1 de Codex (02b72588: la ficha del profesor usa `monto_a_pagar`,
  regresión 15.000 → 18.000,50) y las vistas de Gemini (2cfe295d: conservan H1, sin JS nuevo).
  `AlumnoSinPlanT56T68Test` + `CspSinCodigoIncrustadoTest`: 8 aprobadas, 52 aserciones.
  Visual aprobado por Carlos el 19/10 («Las vistas están aprobadas»). Cerrada con el script.
  H2 (`onsubmit` de Eliminar plan, anterior a T68) queda en T73. Sin despliegue.
- Suite de Codex en H1: 2 fallas (T30 recibo, T33 concurrencia) que pasan al repetir. Causa
  probable, no comprobada: Gemini corría T33 en la misma máquina a la vez.
- Propuesta a Carlos, sin decidir: suite rápida por tarea (12 de 144 archivos se llevan 13 de
  los 16 min) y completa antes de desplegar o si se toca plata compartida, un agente a la vez.

## 2026-10-19 — Claude CyE — DU-57 corregida en la tabla de pruebas; T66 pasa a Gemini

- DU-57: respuesta corregida a «Completarlos con datos reales» (aclaración de Carlos del 19/10);
  se conserva la original del 13/10 en `respuesta_original`. Tabla viva y copia del repo.
- SIS-012 reescrito con lo esperado real: próximas clases del grupo o «Sin clases futuras
  programadas»; liquidaciones y clases del profesor o sus avisos. La ficha del profesor es solo ADMIN.
- `DUDAS-RESPONDIDAS-2026-10-13.md` actualizado en la fila de DU-57.
- Carlos decidió: la lógica de T66 la verifica Gemini (ya no Claude); T68 la verifica Codex.
  Prompts entregados a Carlos. Solo documental: sin código ni suite.

## 2026-10-19 — Claude CAB — Pendientes al cerrar la sesión (sin respuesta de Carlos o inconclusos)

Pedido de Carlos: dejar asentado todo lo que quedó sin responder o sin terminar.

**Esperan respuesta de Carlos**
- **Quién verifica T74, T59 y T47.** Las tres están «a verificar» sin verificador. T74 y T47
  tocan pantalla: además falta su visto visual (capturas en `evidencia-t74-claude/` y
  `evidencia-t59-t47-claude/`). Ofrecí armar el prompt para el verificador.
- **DU-57 / T68.** Carlos aclaró que «Próximamente» no se retiraba: se completa con datos
  (profesor: clases asignadas y liquidaciones, o «Sin clases» / «Sin liquidaciones»; grupo:
  clases futuras o «Sin clases futuras»). El error salió de mi pregunta: DU-57 ofrecía solo
  «Quitarlos / Dejarlos». El tablero ya lo actualizó Gemini (T68 en curso con Gemini); la
  tabla de pruebas me toca a mí (ver inconclusos).
- **Base de producción.** El informe de Gemini de T56 dice que contó alumnos en la base `wings`
  del servidor. Se lo avisé a Carlos; no contestó si estaba autorizado.

**Inconclusos míos**
- **Tabla de pruebas, DU-57 y SIS-012:** hecho el 19/10 (ver entrada de arriba).
- **T66 (Codex, 9bb7bd6):** me toca verificar la lógica; Carlos ya aprobó el visual (55ac057).
  No empezado.
- **`VERIFICACION-T56-T68.md`:** con la aclaración de Carlos, la aprobación de T68 no vale; dejé
  la nota al principio del informe. T56 sigue cerrada.

**Observaciones sin decisión (no bloquean)**
- T56: si un inactivo sin plan paga, queda inactivo y la pantalla de cobro no lo dice. Hoy no es
  alcanzable (ningún alumno sin plan).
- Gemini propuso que el generador del día 1 avise al dueño si encuentra un activo sin plan, en
  vez de solo escribirlo en el log. Es decisión de Carlos.

## 2026-10-19 — Claude CAB — T56 verificada y cerrada; T68 espera el visual de Carlos

Lógica de Gemini correcta: al cambiar de grupo el plan es obligatorio y del grupo nuevo; el
cobro a un inactivo solo lo reactiva con plan para el mes, con el criterio del generador. 5
controles propios (incluido el cobro de la operativa, que Gemini no probó, y qué plan queda al
cambiar de grupo con el mes pagado): 5/5. T68 conserva datos y acciones. Proceso: 0414b68 se
llevó cambios míos sin commitear, dejó el contador en 890 con 896 y su informe decía lo
contrario (corregido); según su informe, leyó la base de producción. `VERIFICACION-T56-T68.md`.
