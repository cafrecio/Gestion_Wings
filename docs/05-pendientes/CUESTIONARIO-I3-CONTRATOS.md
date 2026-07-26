# Cuestionario — I3.0 Contratos contradictorios

Origen: `docs/07-evaluacion/ANALISIS-INTEGRAL.md` I3.0 — conviven versiones distintas del mismo contrato en `docs/02-contratos/` sin que quede claro cuál es la verdad. Este cuestionario nace de comparar cada documento contra el código y el esquema real de BD (no son preguntas genéricas: cada punto está verificado contra `database/migrations/`, modelos y controllers).

Fecha: 2026-07-26.

---

## A) Contradicciones entre versiones — hay que dirimir cuál gana

> **A1, A2, A3, A5 respondidas y aplicadas (2026-07-26).** A4 y A6 y todo B/C quedan pendientes: se retoman **módulo por módulo** (un tema del índice a la vez, con su propio cuestionario), no en un solo documento.

### A1. `alumno_planes.activo` — ER V2 vs esquema real ✅ RESUELTO
El ER V2 (`Wings-ER-Alumno-Grupo-Deporte-Deuda-V2.md`) dice que `activo` es `bool` NOT NULL con `UNIQUE(alumno_id, activo)`. El esquema real, desde el fix de **D1.0**, tiene `activo` NULLABLE: los planes cerrados pasan a `NULL` (no a `false`) para no chocar entre sí en el índice único.

**Pregunta:** ¿Actualizamos el ER V2 para reflejar el estado real (nullable + planes cerrados en NULL), dejando una nota de por qué (el UNIQUE con NOT NULL rompía el segundo cambio de plan)? ¿O hay alguna razón de negocio para preferir el diseño viejo que no contemplé?

### A2. `pagos.estado` y `forma_pago_id` — ambos V1 (ER y Contrato) vs código real ✅ RESUELTO (confirmado: era legacy)
Los dos documentos de "cuotas-deudas-pagos" V1 describen:
- `pagos.estado` ENUM con 4 valores: `pagado | parcial | adeuda | COMPLETADO`.
- Un campo `forma_pago_id` vigente en `pagos`.
- Un "flujo de pago regular" (con `plan_id`, `forma_pago_id`, `mes/año`) que conviviría con el flujo de cuota (`estado='COMPLETADO'`).

Código real, hoy:
- **D4** eliminó `forma_pago_id` entero (columna, FK y tabla `formas_pago`).
- **D6** redujo el ENUM de `pagos.estado` a solo `COMPLETADO | ANULADO` — los valores `pagado/parcial/adeuda` ya no existen en la BD.
- `PagoService.php` (el servicio del "pago regular", solo alcanzable por la API ya apagada) ya fue actualizado en algún momento para usar `Pago::ESTADO_COMPLETADO`, no los valores viejos.

**Pregunta:** ¿Confirmás que el "flujo de pago regular" descrito en el V1 es un modelo **superado**, reemplazado enteramente por el flujo de cuota vía `DeudaCuota`/`PagoCuotaService`? Si es así, reescribo el contrato para un solo flujo y anoto el viejo como histórico. Si en cambio ese flujo regular sigue vigente para algún caso de uso real que no estoy viendo, decime cuál para no borrarlo del contrato por error.

### A3. `tipo_liquidacion`: "POR_HORA/POR_COMISION" vs "HORA/COMISION" ✅ RESUELTO
`LIQUIDACIONES_CONTRATO_V2.md` usa `POR_HORA`/`POR_COMISION` en todo su texto. El ER V2 de alumno-grupo-deporte-deuda dice `HORA | COMISION` (sin prefijo). El ENUM real en la migración y las constantes del modelo `Deporte` son `HORA`/`COMISION`.

**Pregunta:** ¿Corrijo el texto de `LIQUIDACIONES_CONTRATO_V2.md` para usar los valores reales (`HORA`/`COMISION`), ya que hoy contradice tanto al código como al otro contrato?

### A4. Liquidaciones: el contrato no menciona el pago ni Subrubro
`LIQUIDACIONES_CONTRATO_V2.md` describe en detalle el **cálculo** de la liquidación, pero no menciona en ningún lado `Profesor.subrubro_id` ni la integración con Rubros/Subrubros/Cashflow al **pagar** una liquidación (`LiquidacionPagoService`, tocado en **D3**). El vínculo profesor↔subrubro es justamente lo que rompía D3 (fallback peligroso "cualquier subrubro de Sueldos").

**Pregunta:** ¿Ampliamos este contrato para cubrir también el flujo de pago (ya implementado), o preferís un documento separado ("Pago de Liquidaciones") para no tocar un contrato que ya está "cerrado" en lo suyo?

### A5. `User.rol` en el ER de Caja-Cashflow V4 no incluye PROFESOR ✅ RESUELTO
`Wings-ER-Caja-Cashflow-V4.md` documenta `User.rol (enum: ADMIN | OPERATIVO)`. El modelo real tiene tres roles (`ADMIN | OPERATIVO | PROFESOR`), documentados aparte en `PERMISOS-ROLES.md`.

**Pregunta:** ¿Agrego PROFESOR al ER aclarando que no participa de Caja/Cashflow (por eso quedó afuera), o lo dejamos así porque este ER es específico del caso de uso y no pretende documentar el modelo `User` completo?

### A6 (menor). Encabezado contradictorio en el propio documento
`Wings-contrato-cuotas-deudas-pagos-V1.md` arranca diciendo `Estado: CERRADO (TO-BE implementado y verificado)` y termina diciendo `🔒 CONTRATO CERRADO. Implementación verificada en backend...`. El encabezado suena a pendiente; el cierre suena a ya confirmado.

**Pregunta:** ¿Confirmás que ya está verificado y limpio el encabezado para que no quede la duda? (esto es prolijidad, no bloquea nada)

---

## B) Puntos que ESTÁN en la app pero no tienen contrato que diga cómo deberían funcionar

### B1. Clases/Asistencias — el solapamiento de horarios no se valida en el flujo real
Existe `ClaseService::validarSolapamientoProfesor()` y `validarSolapamientoAlumno()`: en teoría impiden que un profesor o un alumno queden en dos clases que se solapan en fecha+horario. **Pero** el flujo web real (`ClaseWebController::store()`, `actualizarProfesores()`, guardado de asistencias) nunca llama a esos métodos — asigna profesores con `sync()` y guarda asistencia con `updateOrCreate()` directo. En su lugar, cuando un alumno excede su plan semanal, el sistema real usa `AsistenciaExceso` con motivo `EXTRA` o `RECUPERA`: no bloquea, etiqueta.

**Pregunta:** ¿Cuál es la regla que realmente querés?
- (a) Nunca bloquear solapamiento — solo registrar exceso semanal como hace hoy (lo que pasa en los hechos), y `ClaseService::validarSolapamiento*` queda como código muerto para podar después.
- (b) Sí debería bloquear solapamiento de horario (como dice `ClaseService`) y hoy hay un bug real porque el flujo web no lo está llamando.

### B2. Clases recurrentes sin chequeo de solapamiento entre sí
`store()` permite crear una serie completa (mismo horario, varios días de la semana, rango de fechas) sin validar contra clases ya existentes del mismo profesor/grupo.

**Pregunta:** si un admin carga mal una serie y el mismo profesor termina con series solapadas, ¿debería bloquearse, o es aceptable y queda a criterio del admin?

### B3. Catálogos contables — borrar un subrubro con historial
Hoy el único freno para editar/borrar un Rubro/Subrubro es el flag `es_reservado_sistema`. No hay chequeo de si el subrubro tiene `movimientos_operativos` o `cashflow_movimientos` históricos antes de borrarlo.

**Pregunta:** si un admin borra un subrubro con historial, ¿qué debería pasar?
- (a) Bloquear el borrado si tiene movimientos asociados.
- (b) Permitirlo (¿la FK actual explota, o queda huérfana?).
- (c) No "borrar" nunca — solo desactivar (¿existe ya un flag `activo` para esto?).

### B4. Recibos PDF — sin contrato de contenido
`ReciboService` genera hoy el PDF con `numero_recibo`, `fecha_emision`, `fecha_pago`, datos de alumno, etc. (cuota y liquidación). No hay documento que diga qué debe contener oficialmente, cuándo se puede regenerar, ni naming/versionado.

**Pregunta:** ¿lo que genera el service HOY es el contrato definitivo (lo documento tal cual), o falta algo que hoy no muestra (ej. datos fiscales, numeración correlativa real, etc.)?

### B5. ABM Admin — criterio de borrado inconsistente entre módulos
Cada ABM (Deporte, Grupo, Profesor, Rubro, Subrubro, TipoCaja, Nivel) define su propia regla de "no borrar si..." en su controller, sin un criterio único.

**Pregunta:** ¿querés un criterio general documentado (ej. "nunca borrar si tiene hijos activos, solo desactivar") o preferís mantenerlo caso por caso como está hoy?

### B6. Motor de Estados de Cobranza — implementado pero sin contrato
`CobranzaEstadoService` ya calcula `AL_DIA | MOROSO | DEUDOR` con `DIA_GRACIA = 10` (días de gracia antes de considerar moroso). El Topic 10 del índice ("Motor Estados + Cobranza") está 100% sin documentar.

**Pregunta:** ¿la regla real es tal cual está en el código (al día si no debe nada del mes vigente antes del día 10, moroso después, deudor si acumula meses), o hay matices de negocio que el código no refleja bien y conviene ajustar antes de escribir el contrato?

---

## C) Puntos que NO están (todavía) en la app — ¿falta el papel o falta el código?

Según `Wings-Index documentacion final-v1.1.md`, estas piezas están marcadas ⏳ (no existen como documento). Quiero confirmar si además de faltar el documento, falta también la funcionalidad:

- **C1. Camino feliz de Caja+Cashflow** (operativo día a día + validación admin + caja vieja): el código ya lo implementa (bloqueo de caja vieja, validar/rechazar). ¿Solo falta escribirlo, o hay pasos que en la práctica todavía no están resueltos?
- **C2. Decisiones de pantalla ("Front")** para Caja/Cashflow, Alumnos, Cuotas, Clases, Liquidaciones, Catálogos, Recibos, Auth: ninguna existe como documento formal aunque las pantallas ya funcionan. ¿Es solo deuda documental, o hay pantallas que sabés que están incompletas y merecen revisión antes de "cerrar" el contrato?
- **C3. Contrato de Clases + Asistencias** (Topic 4): cero documentación pese a que el código ya implementa solapamientos, recuperación y exceso semanal (ver B1/B2). Una vez resueltas esas dudas, ¿confirmás que el negocio real es "lo que ya hace el código"?
- **C4. Contrato de Auth+Roles** (Topic 8): en rigor ya existe y se usa activamente `docs/02-contratos/PERMISOS-ROLES.md`, pero el índice general no lo menciona y sigue marcando el Topic 8 entero como ⏳. ¿Actualizo el índice para que apunte ahí y cierre ese ítem?
- **C5. Catálogos Contables (Topic 6) y Recibos PDF (Topic 7)**: cero documentación, código ya implementado (ver B3/B4). ¿Prioridad para escribir el contrato ahora, o quedan para después?
- **C6. Motor Estados + Cobranza (Topic 10)**: cero documentación pese a servicio real (ver B6). ¿Prioridad?
- **C7 (menor). `docs/00-estado/ESTADO-ANTERIOR-260526.md`**: por el nombre parece un snapshot histórico correctamente rotulado (no una contradicción real con ESTADO-ACTUAL). ¿Confirmás que está bien así, o preferís moverlo a `docs/99-archivo/` para que quede más claro que es histórico y no algo "vivo"?

---

## Cómo sigue esto

Con tus respuestas puedo: (1) corregir los contratos existentes donde el código ya cambió (A1-A6), (2) escribir los contratos que faltan para lo que ya está en producción (B1-B6, usando tus respuestas como la regla oficial), y (3) actualizar el índice general para que dejen de figurar como ⏳ los ítems que en realidad ya están resueltos (C4) o marcar con fecha los que quedan pendientes de verdad.
