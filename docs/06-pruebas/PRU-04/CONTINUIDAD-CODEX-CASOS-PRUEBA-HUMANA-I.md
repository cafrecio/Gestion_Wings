# Casos prueba humana I — continuidad y lecciones aprendidas

22/10/2026 — Codex CAB. Pedido de Carlos: conservar los acuerdos y las dos revisiones del cuento, publicar y continuar desde CyE. Es una entrega de continuidad, no una aprobación ni ejecución del día 4.

## Codex CyE: leer primero y retomar por acá

Aplicar el [protocolo](../../00-estado/PROTOCOLO-CONTINUIDAD.md), leer este archivo completo y después los tres borradores enlazados abajo. **La segunda devolución de Codex todavía NO fue enviada a Gemini por Carlos.** El siguiente paso es comunicar las cuatro observaciones de la sección 5 y revisar su nueva entrega con Carlos. No ejecutar el día, corregir producto ni cerrar T14.

Este chat se llama **Casos prueba humana I**. Carlos ordenó expresamente que este chat **nunca ejecuta la prueba**. La ejecuta Codex en otro chat, **Prueba humana**, una vez revisado el cuento y autorizado el inicio. Esa separación sigue vigente al cambiar de computadora.

## 1. Acuerdos del chat

- Pensar y revisar antes de actuar. El agente debe preguntar ante dudas y proponer mejoras, no limitarse a obedecer mecánicamente.
- Gemini escribe una historia que reproduce un día de trabajo del club: alumnos, cuotas, otros ingresos, conceptos no catalogados, gastos, asistencias y cierre. Las operaciones responden a objetivos de prueba, pero deben tener sentido como jornada humana.
- Codex en **Prueba humana** opera por navegador, menús, botones y formularios; registra lo observado y no arregla durante el ensayo. No escribe direcciones internas ni consulta base/código durante el recorrido.
- **Este chat** revisa la propuesta, analiza resultados y criterios cubiertos, identifica defectos y organiza correcciones y nuevas pruebas. La comprobación independiente posterior de base/código debe tener responsable explícito; no se presume realizada.
- Comenzar con días chicos para evaluar el método: máximo orientativo de **20–30 operaciones**, sin obligación de alcanzar ese número. Varias pueden ser iguales. Los borradores actuales numeran 18 filas, que incluyen consultas e intentos: no equivalen automáticamente a 18 escrituras.
- Los usuarios son nuevos en el uso de Wings; se usan las cuentas existentes. También se evalúan claridad, mensajes, navegación y necesidad de adivinar.
- Por ahora se evalúan todas las operaciones del día. Más adelante una operación repetida puede alimentar el sistema sin repetir toda su medición; debe distinguirse ejecución de cobertura nueva.
- Conservar datos y acumular actividad e historia para evaluar rendimiento con crecimiento. Medir apertura/guardado bajo condiciones identificadas; pocos datos no acreditan buen rendimiento con volumen.
- Los resultados se escriben en el día correspondiente y se enlazan a caso/letra/rol/evidencia. Un defecto que requiera desarrollo debe vincularse al tablero; corregir no equivale a verificar.
- No modificar tabla Carlos/Claude ni dudas. No desplegar ni modificar producción por esta preparación.

## 2. No arrancamos de cero

Leer [día 1](DIA-01.md), [día 2](DIA-02.md), [día 3](DIA-03.md) y [verificación del día 1](VERIFICACION-DIA-01.md), incluidas las verificaciones y decisiones posteriores dentro de cada archivo.

| Día simulado | Antecedente registrado |
|---|---|
| 10/10 | Cuatro pagos por $164.000, caja de Sandra validada, 6 presentes y 2 ausentes de Mariela. |
| 11/10 | Sin operaciones nuevas; deuda $2.010.000; estados registrados 65 Al día, 0 En plazo, 22 Morosos, 13 Deudores. |
| 12/10 | Tres clases canceladas por feriado; mismos pagos y caja; 33 clases históricas pendientes de asistencia. |

Son antecedentes fechados, no certificación del estado actual del servidor. Hubo modificaciones posteriores del producto. Antes del día 4 hay que comprobar condiciones iniciales y explicar diferencias, sin restaurar ni ajustar datos para hacerlos coincidir.

Las suplencias están permitidas: abrir una clase de otro docente no es por sí solo defecto. Abrir caja en domingo/feriado también fue aceptado por Carlos. No repetir como defectos los hallazgos históricos revocados por esas decisiones.

## 3. Qué significa el control integral

La [tabla integral](control-integral/CONTROL-INTEGRAL.html) organiza 39 recorridos y 14 controles; el corte contiene 331 referencias al catálogo fuente. No son 331 pruebas aprobadas ni garantía de todas las posibilidades del sistema.

El [alcance preparado](control-integral/LEEME.md) declara 181 casos vinculados y 150 sin recorrido asignado. Aun los vinculados requieren cobertura por letra, variante y rol. El control nuevo no incorporó automáticamente los resultados de los días 1–3: primero reconciliar su evidencia. **INT-01 es ingreso/salida por los tres roles, no el día 1.** Cero ejecuciones registradas en el control nuevo no significa que nunca hubo prueba humana.

## 4. Primera propuesta y devolución

El primer prompt de Codex estaba demasiado centrado en controles y no representaba completamente la jornada cotidiana pedida. Carlos precisó los roles, el crecimiento de datos, la usabilidad y el límite de operaciones. Se redactó un segundo prompt: conservar continuidad, escribir esperado antes de actuar, usar datos comprobados, enlazar criterios exactos y entregar cuento, objetivos, seguimiento temporal e instrucción al ejecutor, todos para revisión previa.

Gemini produjo una historia de 18 filas: apertura, alta de Mora, cinco cobros (incluido noviembre), indumentaria, limpieza, alquiler sin categoría y alta del subrubro por el dueño, asistencia actual/histórica, roles, cierre, validación y conciliación.

Primera revisión de Codex:

1. Disponible final $368.500 omitía saldos iniciales del club. Con antecedente $1.734.000 y operaciones netas +$204.500, el esperado condicional es $1.938.500, previa comprobación del saldo inicial.
2. Contadores ignoraban que Emma y Dante pasan de Deudor a Moroso al saldar septiembre, mientras Renata y Mateo pasan de Moroso a Al día.
3. Casos mal vinculados: ASI-007 trata exceso de clases del plan, no asistencia histórica; COB-005 trata primer cobro con inscripción, no FIFO general.
4. El cuento pedía URLs internas y continuar ante cualquier diferencia. Se pidió navegación por menús y detener el paso ante descuadre de dinero o bloqueo del flujo.
5. Comenzar a las 17:00 con una clase de 17–18 contradecía la preparación solicitada de clase ya terminada. Faltaban seguimiento temporal, mediciones e instrucción separada.

La segunda versión cambió a las 18:15, quitó URLs internas, agregó detención, mediciones y el archivo del ejecutor, corrigió el disponible total y describió las transiciones individuales. **Eso no aprobó toda la propuesta.**

## 5. Segunda devolución — PENDIENTE de comunicar a Gemini

Carlos confirmó que esta última devolución no se la pasó a Gemini. Retomar desde estas cuatro observaciones, conservando la historia y sin agregar operaciones:

### 5.1 Contadores iniciales sin procedencia acreditada por Codex

La segunda versión parte de 62 Al día / 0 En plazo / 15 Morosos / 23 Deudores y espera 64/1/15/21. Los días anteriores registraban 65/0/22/13. No elegir una cifra por conveniencia: pedir evidencia fechada, versión, reloj, origen y explicación de diferencias, con alumnos concretos representativos. Codex CAB no consultó el servidor en estas revisiones y no certifica una auditoría que Gemini declara haber hecho.

### 5.2 Adelanto descrito como pago a cuenta sin cuota

OBJETIVOS-PRUEBA.md sigue diciendo «deuda_cuotas: no crea deuda previa; cobro registrado a cuenta». El cuerpo de `PagoCuotaService::registrarPagoCuotaOperativo` llama a `aplicarPagoADeudas`, luego relaciona pago e imputaciones con las cuotas. El esperado debe describir la cuota de noviembre que se crea si no existe, su pago/imputación y la no duplicación futura; no afirmar un saldo libre a cuenta. Confirmar el cuerpo pertinente en la nueva revisión.

### 5.3 Cobertura y condición implementada siguen mezcladas

- CBZ-004 evalúa pagar el mes nuevo dejando pendiente el viejo; no acredita pagar septiembre y conservar octubre pendiente. Revisar cada código y letra contra su texto, no solo el título.
- COB-009 pide intentar un parcial sin motivo y rechazarlo. La copia lo declara `no_implementado`; T25 está `sin_empezar` en el corte leído. La propuesta lo presenta como motivo obligatorio ya persistido y manda escribir «Pago a cuenta». Revalidar comportamiento actual sin asumir que el requisito existe. Registrar la condición pendiente como tal; no inventar campo ni adjudicar cobertura por escribir un motivo voluntario.
- Las rutas/servicios de la matriz también necesitan confirmación: por ejemplo, el cobro operativo está en `POST /caja/cobrar/{alumnoId}` hacia `CajaWebController::pagar`, no en el `POST /cobrar` de la matriz. No tratar esos rótulos como auditoría realizada.

### 5.4 Seguimiento temporal todavía ausente

Agregar matriz con persona, condición, fecha/hora del siguiente control, caso/letra, esperado y evidencia requerida. Incluir plazo de Mora, adelanto de Sofía, asistencia y generación/revisión de noviembre. Hoy se prepara el futuro; no se aprueba anticipadamente.

## 6. Tiempo: reglas para todos los cuentos siguientes

- Separar fecha real (22/10 al preparar este pase) de fecha Wings del cuento (13/10). No aplicar límites según el reloj de la computadora por comodidad.
- El cambio general a Moroso ya se observó el 11/10; no inventar otra transición el 13. Altas/reingresos tienen plazos particulares. El límite exacto y el texto «vence» deben contrastarse con configuración/contrato: no confundir último día en plazo con primer día moroso.
- El 13/10 permite preparar un adelanto, no acreditar generación del 1/11 o comisión futura. Conservar alumno/período/importe para revisitar.
- CBZ-017 requiere alta y pago recientes dentro de 15 días al generar noviembre: reservar la preparación para fines de octubre. Un alta del 13 no prueba esa ventana.
- Horarios leídos en `routes/console.php`: 06:00 generación con `--si-falta`, 06:15 punitorios y 08:00 resumen. Saltar directamente a las 18:15 no demuestra esas ejecuciones. Planificarlas aparte sin alterar el reloj durante el recorrido ni sustituir automatismo por ejecución manual.
- Revisar porcentaje de mora: con 0% no se acredita cálculo positivo. No cambiarlo ni enviar avisos reales por deducción; coordinar condiciones antes.
- No provocar fallo del día 1 ni borrar constancias en el sitio acumulado para probar reintentos. Las pruebas aisladas requieren preparación y alcance propios.

## 7. Archivos y advertencia sobre el documento de Gemini

- [Cuento DIA-04](DIA-04.md): segunda versión, pendiente de corrección/revisión.
- [Objetivos](OBJETIVOS-PRUEBA.md): segunda versión, pendiente de corrección/revisión.
- [Instrucción al ejecutor](INSTRUCCIONES-EJECUTOR-DIA-04.md): borrador, no ejecutar.
- [Lecciones de Gemini](LECCIONES-PRUEBA-HUMANA-I.md): antecedente de otro agente, conservado sin modificar.

**El documento de Gemini afirma «Todas fueron investigadas y corregidas», «contadores finales auditados» y «casos fuente exactos». Codex no convalida esas afirmaciones.** La sección 5 de este pase conserva las diferencias observadas y es el punto de partida de la revisión pendiente. No heredar sus afirmaciones como verificaciones independientes.

## 8. Lecciones que debe conservar el método

1. Un cuento convincente puede tener números, rutas y referencias incorrectos: comprobarlos antes de operar.
2. Cuadrar la suma total no acredita los estados individuales ni el saldo por medio.
3. Requisito decidido, requisito implementado y prueba aprobada son estados distintos.
4. Ver menú no prueba rechazo de peticiones directas; esa cobertura de seguridad queda aparte.
5. Preparar un caso temporal hoy no demuestra su efecto el mes próximo.
6. Conservar las objeciones abiertas al escribir «lecciones aprendidas»; una segunda versión no convierte todo en resuelto.
7. El agente debe aportar criterio y señalar dudas antes de actuar. Carlos: «Siempre prefiero pensar antes de actuar».

## 9. Alcance de este pase

Solo documentación y continuidad. No ejecución humana, suite, migraciones, cambios de reloj, consultas al servidor, correcciones de producto ni despliegue por Codex CAB en este chat. La publicación conserva los borradores para continuar la revisión desde CyE; **no autoriza su ejecución ni cierra T14**. Otro agente debe revisar este pase y las correcciones antes de dar por validada la preparación.
