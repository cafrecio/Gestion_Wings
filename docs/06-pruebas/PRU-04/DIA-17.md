# Día 17 — lunes 26/10/2026

Ejecutado el 26/10/2026 · Codex CyE · TEST / staging / wingstest · código remoto ec1b0c6e.

**7 situaciones: 3 Sí, 3 Parcial y 1 No.** Recorrido entregado; revisión técnica independiente pendiente. No conformidad integral.

Fuente: [guion original](GUION-DIAS-17-18.md). Esperados congelados antes de las operaciones; [registro por caso](evidencia-dia-17/registro-casos-17-18.json). [Galería](EVIDENCIA-DIAS-17-18.html).

## Operaciones y resultados

| Caso / quién / tarea | Esperado del guion | Lo que pasó | ¿Coincide? |
|---|---|---|---|
| 17.1 · Dueño · Rocío no recuerda su clave. En **Usuarios**, edita a Rocío Paz y le pone **Wings-Rocio-2026**, dos veces. Antes prueba con «corta». | «corta» no la acepta: pide al menos 12 caracteres. Con la buena guarda. Rocío entra con **Wings-Rocio-2026** y ve el menú de la operativa (USU-005). | Controlador como ADMIN: rechaza corta con mensaje en inglés de mínimo 12; guarda clave válida. Ingreso real como Rocío y menú operativo comprobados. Cambio por vía técnica solicitado por Carlos; no se acredita envío de clave por formulario. | Parcial |
| 17.2 · Rocío · La mamá de **Santino Romero** trae **$56.000** en efectivo: «lo que puedo ahora». Rocío elige agosto y septiembre. | Antes de confirmar, total $56.000. Quedan **octubre $28.000**. Santino pasa de Deudor a **Moroso** (COB-006). | Total 56000 antes de confirmar. Pago registrado; Santino Moroso con octubre28000. | Sí |
| 17.3 · Rocío · Compra gasas y alcohol: gasto **Botiquín**, $6.500 en efectivo, «Gasas y alcohol». | **Botiquín** aparece en su lista de gastos, creado por el dueño el domingo, y se guarda (CAT-007). | Botiquín disponible como Rocío, egreso6500 con Gasas y alcohol. Resumen caja16: egresos10300, ingresos56000, neto45700. | Sí |
| 17.4 · Dueño · Alta de profesor: **Martín Acosta**, Fútbol, **comisión 30%**. | Queda activo. En Rubros → Sueldos aparece «Fútbol-Martín Acosta» (PRO-001). | Martín Acosta activo, Fútbol30%; subrubro Fútbol-Martín Acosta comprobado. Campos obligatorios ausentes del guion completados con datos ficticios: DNI30600117, nacimiento1988-05-17, Calle de Prueba170, Avellaneda, teléfono1155500017. | Sí |
| 17.5 · Dueño · Le crea a Martín su usuario de **profesor**, vinculado a Martín Acosta, mail martin.acosta@example.com, clave **Wings-Martin-2026**. | Queda activo con rol Profesor. Martín entra y ve solo Clases (USU-003). | Usuario activo y vinculado creado por controlador como ADMIN, a pedido de Carlos por vía técnica. Ingreso web como Martín comprobado: menú solo Clases. No se acredita alta por formulario. | Parcial |
| 17.6 · Hernán · Carga la lista de la **#76**, del sábado 24/10, que quedó sin tomar: presentes **Lucas Gómez** y **Tomás Giménez**, el resto ausente. | **No pide motivo:** la clase nunca tuvo lista, así que es una primera carga. Guarda. «Sin asistencia» baja. | Clase76 guardada por Hernán: TomasGimenez y LucasGomez presentes, otros13 ausentes; recarga conserva2. No pidió motivo de corrección histórica, pero ambos mostraron exceder plan y la opción predeterminada EXTRA, que se conservó. | Parcial |
| 17.7 · Dueño · **Julieta Álvarez** vuelve: la reactiva desde su ficha y le cobra los **$143.000** por Mercado Pago, sin caja. | Vuelve con su deuda tal cual, sin una cuota de octubre nueva. Con el cobro: recibo de cinco meses y **Al día** (ALU-018). | Dos intentos desde cero de Reactivar. Chrome abre confirmación nativa pero accept falla por Emulation.setFocusEmulationEnabled; primera recarga conservó Inactivo y143000. No se hace por consola, no se cobra. Se conserva la diferencia prevista por guion. | No |

## Reloj y rutinas: salidas reales

Reloj 26/10/2026 15:00: una ejecución. Simulación: una ejecución revertida. Rutina real de mañana: una; noche: una. Se ejecutó como wingstest con WINGS_APP=/home/wingstest/app. Auxiliar actualizado y planes en /tmp/wings-dias1718-20261026; no se desplegó código de Wings.

## Cierre observado

| Control | Guion completo | Observado |
|---|---:|---:|
| Deuda | $335.000 | $335.000 |
| Activos / Al día / En plazo / Morosos / Deudores | 105 / 100 / 0 / 2 / 3 | 104 / 99 / 0 / 2 / 3 |
| Ingresos octubre | $2.023.850,75 | $1.880.850,75 |
| Egresos | $251.150 | $251.150 |
| Resultado | $1.772.700,75 | $1.629.700,75 |
| Disponible | $3.525.700,75 | $3.382.700,75 |
| Efectivo / MP / Galicia / Nación | $1.228.600 / $2.031.550,75 / $137.550 / $128.000 | $1.228.600 / $1.888.550,75 / $137.550 / $128.000 |
| Sin asistencia / por validar | 0 / $0 | 0 / $0 |

Caja #16 de Rocío cerrada con esperado y contado $55.700, retuvo $10.000, diferencia $0; tres renglones verificados y validada. Cierre financiero leído en Inicio; Cobranza y stdout concuerdan. Las imágenes 31/32 son del cierre; algunos textos guardados por AX son diferencias contra la lectura anterior y se leen junto con las capturas.

**Diferencia prevista en el propio guion si 17.7 falla:** faltan $143.000 en ingresos, resultado, MP y disponible; falta una alumna activa Al día. Julieta permanece inactiva y sin cobrar; no se cambió por consola. No es una diferencia financiera nueva.

### 06-reloj-y-simulacion.txt

```text
INFO  Configuration cache cleared successfully.

Wings ahora cree que es lunes 26 de octubre de 2026, 15:00
El reloj avanza solo desde esa hora y se detiene a la medianoche de ese día.
Las sesiones abiertas se caen al saltar de día: hay que volver a entrar.
 1. Rocío Paz abre caja con $10.000,00 — Caja abierta con el efectivo declarado.
 2. Rocío Paz Limpieza $3800 por Efectivo — Movimiento registrado.
 3. Lucía Gaitán lista de la #9: 28 alumnos, 27 presentes — Asistencias guardadas.
 4. Hernán Quintana lista de la #55: 23 alumnos, 22 presentes — Asistencias guardadas.
 5. Verónica Salinas lista de la #16: 18 alumnos, 16 presentes — Asistencias guardadas.
Cobranza: $391.000,00 · {"AL_DIA":99,"EN_PLAZO":0,"MOROSO":1,"DEUDOR":4}
SIMULADO: no se guardó nada.
```

### 12-rutina-manana.txt

```text
1. Rocío Paz abre caja con $10.000,00 — Caja abierta con el efectivo declarado.
 2. Rocío Paz Limpieza $3800 por Efectivo — Movimiento registrado.
 3. Lucía Gaitán lista de la #9: 28 alumnos, 27 presentes — Asistencias guardadas.
 4. Hernán Quintana lista de la #55: 23 alumnos, 22 presentes — Asistencias guardadas.
 5. Verónica Salinas lista de la #16: 18 alumnos, 16 presentes — Asistencias guardadas.
Cobranza: $391.000,00 · {"AL_DIA":99,"EN_PLAZO":0,"MOROSO":1,"DEUDOR":4}
GUARDADO.
```

### 30-rutina-noche.txt

```text
1. Rocío Paz cierra la caja #16: esperado $55.700,00, contado $55.700,00 — Caja cerrada.
 2. Admin Prueba valida la caja #16 (3 renglones tildados) — Caja validada y reflejada en cashflow.
Cobranza: $335.000,00 · {"AL_DIA":99,"EN_PLAZO":0,"MOROSO":2,"DEUDOR":3}
GUARDADO.
```

## Límites y continuidad

Cambios de claves y alta del usuario de Martín hechos por el controlador como ADMIN por vía técnica, después de que Carlos pidió que el agente lo hiciera en TEST. Ingreso y menús sí probados en Chrome. No se acredita el envío de esas credenciales por formulario; 17.1 devolvió el mínimo de 12 en inglés en la vía técnica.

17.6 guardó la primera lista histórica #76 sin motivo de corrección; los dos presentes mostraron exceso semanal y conservaron la opción EXTRA propuesta. Por eso se informa parcial frente al literal del guion.

17.7: dos intentos por pantalla; las confirmaciones nativas de Chrome fallaron. Sin consola para reactivar ni cobro a Julieta. [Incidente](evidencia-dia-17/incidente-julieta-chrome.txt).

18.4: error del ejecutor con dos botones Guardar. Envió primero la lista con todos ausentes, confirmado luego por la tarjeta Sin presentes; después guardó al profesor con el botón específico. 18.5 guardó los 14 presentes previstos como Martín y recargó para comprobarlos, pero ya no fue una primera lista limpia. No se atribuye a Wings ni se declara aceptación integral de ambos casos. [Incidente](evidencia-dia-18/incidente-guardar-duplicado.txt).

Dos capturas del día 18 tomaron la pestaña anterior: archivos incidental-pestana-anterior-* excluidos como prueba de Berenice; recapturas 13/14 posteriores correctamente identificadas. Las demás se enlazan con su alcance; imágenes sin composición ni retoque.

La tabla externa y su copia no se modificaron. Sin cambios de aplicación, suite, migraciones ni despliegue. El próximo paso es revisión independiente de Claude, control interno y medición de velocidad. No repetir reloj, rutinas, cobros, altas ni asistencias. Último inicio simulado: 27/10/2026 15:00. T14 sigue en curso.
