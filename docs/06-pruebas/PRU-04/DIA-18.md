# Día 18 — martes 27/10/2026

Ejecutado el 26/10/2026 · Codex CyE · TEST / staging / wingstest · código remoto ec1b0c6e.

**6 situaciones: 4 Sí, 2 Parcial y 0 No.** Recorrido entregado; revisión técnica independiente pendiente. No conformidad integral.

Fuente: [guion original](GUION-DIAS-17-18.md). Esperados congelados antes de las operaciones; [registro por caso](evidencia-dia-17/registro-casos-17-18.json). [Galería](EVIDENCIA-DIAS-17-18.html).

## Operaciones y resultados

| Caso / quién / tarea | Esperado del guion | Lo que pasó | ¿Coincide? |
|---|---|---|---|
| 18.1 · Rocío · Pasa a mediodía a cubrir y quiere **abrir caja**. | «Hay un turno abierto. Cerralo antes de abrir otro.» Muestra el turno de Pablo. No puede cobrar (CAJ-009). | Inicio identifica turno de Pablo. Abrir lleva al aviso Hay un turno abierto de Pablo Ledesma. Debe cerrarse antes de abrir el siguiente. Al intentar Cobrar a Alma no habilita operación propia sin caja. | Sí |
| 18.2 · Pablo · **Alma Navarro** paga todo: **$58.000** por Mercado Pago. | Total $58.000 antes de confirmar. Recibo con sus dos cuotas. Pasa a **Al día**. | Total58000 antes de confirmar, MP; ficha Alma Al día, pago27/10 por Nov2025+Oct2026. PDF CUOTA65 descargado, texto y render comprobados:25000+33000=58000. | Sí |
| 18.3 · Pablo · **Berenice Sánchez** paga **$43.000** en efectivo, una cuota. | Se imputa a la **más vieja, agosto**. Quedan septiembre y octubre, $86.000: sigue **Deudor**. | Agosto43000 cobrado; ficha muestra historial Ago2026 y pendientes Sep/Oct43000cadauno, Deudor86000. Dos capturas anteriores de pestaña equivocada se excluyen; recaptura posterior acreditada. | Sí |
| 18.4 · Dueño · Hernán avisa que no viene: en la clase de hoy **Fútbol Avanzadas 19–20** cambia a Hernán por **Martín Acosta**. | Solo ofrece profesores de Fútbol. **No pide motivo**, porque la clase todavía no pasó. Queda Martín (CLA-009). | Opciones solo Acosta/Quintana y sin motivo; reasignación a Martín persistió. Error del ejecutor: el primer Guardar fue el de asistencias con todos0. Confirmado después por Martín: la tarjeta decía Sin presentes. Luego se ejecutó la lista prevista14presentes como Martín, sin otra reparación. | Parcial |
| 18.5 · Martín · Entra y toma lista de esa clase: **15 alumnos**; ausente **Felipe Sosa**. | La ve en sus clases. **14 presentes**. Guarda (ASI-001). | Martín ve la clase70 a su cargo y guarda14 presentes, Felipeausente, recarga conserva14. Al ingresar ya decía Sin presentes por el envío accidental del ejecutor en18.4: no se acredita primera carga limpia. | Parcial |
| 18.6 · Hernán · Entra y busca la clase de Fútbol Avanzadas de hoy. | Ya no figura a su cargo. | Hernán entra el27/10: Tus clases de hoy0 y No tenés clases programadas para hoy. La70 queda a cargo de Acosta. | Sí |

## Reloj y rutinas: salidas reales

Reloj 27/10/2026 15:00: una ejecución. Rutina de mañana: una; noche: una. No se volvió al día anterior. Se usó el mismo auxiliar temporal como wingstest.

## Cierre observado

| Control | Guion completo | Observado |
|---|---:|---:|
| Deuda | $234.000 | $234.000 |
| Activos / Al día / En plazo / Morosos / Deudores | 105 / 101 / 0 / 2 / 2 | 104 / 100 / 0 / 2 / 2 |
| Ingresos octubre | $2.124.850,75 | $1.981.850,75 |
| Egresos | $251.150 | $251.150 |
| Resultado | $1.873.700,75 | $1.730.700,75 |
| Disponible | $3.626.700,75 | $3.483.700,75 |
| Efectivo / MP / Galicia / Nación | $1.271.600 / $2.089.550,75 / $137.550 / $128.000 | $1.271.600 / $1.946.550,75 / $137.550 / $128.000 |
| Sin asistencia / por validar | 0 / $0 | 0 / $0 |

Caja #17 de Pablo: esperado y contado $53.000, retuvo $10.000, diferencia $0; dos renglones verificados y validada. Inicio, Cobranza, Reportes con detalle de medios y resumen de caja comprobados. Persiste solo la diferencia de Julieta prevista por el guion. Ficha final inactiva con cinco cuotas y $143.000; sin cobro.

**PDF real de Alma:** [CUOTA-65](evidencia-dia-18/recibo-alma-65.pdf), [render revisado](evidencia-dia-18/recibo-alma-65.png): noviembre 2025 $25.000 + octubre 2026 $33.000, total $58.000, Mercado Pago, registrado por Operativo. Ficha Al día. Berenice: ficha con agosto $43.000 pagado, septiembre y octubre pendientes $86.000, Deudor; CUOTA-66 visible, no descargado.

### 01-reloj-y-rutina-manana.txt

```text
INFO  Configuration cache cleared successfully.

Wings ahora cree que es martes 27 de octubre de 2026, 15:00
El reloj avanza solo desde esa hora y se detiene a la medianoche de ese día.
Las sesiones abiertas se caen al saltar de día: hay que volver a entrar.
 1. Pablo Ledesma abre caja con $10.000,00 — Caja abierta con el efectivo declarado.
 2. Lucía Gaitán lista de la #32: 12 alumnos, 12 presentes — Asistencias guardadas.
 3. Mariela Ocampo lista de la #43: 8 alumnos, 7 presentes — Asistencias guardadas.
Cobranza: $335.000,00 · {"AL_DIA":99,"EN_PLAZO":0,"MOROSO":2,"DEUDOR":3}
GUARDADO.
```

### 22-rutina-noche.txt

```text
1. Pablo Ledesma cierra la caja #17: esperado $53.000,00, contado $53.000,00 — Caja cerrada.
 2. Admin Prueba valida la caja #17 (2 renglones tildados) — Caja validada y reflejada en cashflow.
Cobranza: $234.000,00 · {"AL_DIA":100,"EN_PLAZO":0,"MOROSO":2,"DEUDOR":2}
GUARDADO.
```

## Límites y continuidad

Cambios de claves y alta del usuario de Martín hechos por el controlador como ADMIN por vía técnica, después de que Carlos pidió que el agente lo hiciera en TEST. Ingreso y menús sí probados en Chrome. No se acredita el envío de esas credenciales por formulario; 17.1 devolvió el mínimo de 12 en inglés en la vía técnica.

17.6 guardó la primera lista histórica #76 sin motivo de corrección; los dos presentes mostraron exceso semanal y conservaron la opción EXTRA propuesta. Por eso se informa parcial frente al literal del guion.

17.7: dos intentos por pantalla; las confirmaciones nativas de Chrome fallaron. Sin consola para reactivar ni cobro a Julieta. [Incidente](evidencia-dia-17/incidente-julieta-chrome.txt).

18.4: error del ejecutor con dos botones Guardar. Envió primero la lista con todos ausentes, confirmado luego por la tarjeta Sin presentes; después guardó al profesor con el botón específico. 18.5 guardó los 14 presentes previstos como Martín y recargó para comprobarlos, pero ya no fue una primera lista limpia. No se atribuye a Wings ni se declara aceptación integral de ambos casos. [Incidente](evidencia-dia-18/incidente-guardar-duplicado.txt).

Dos capturas del día 18 tomaron la pestaña anterior: archivos incidental-pestana-anterior-* excluidos como prueba de Berenice; recapturas 13/14 posteriores correctamente identificadas. Las demás se enlazan con su alcance; imágenes sin composición ni retoque.

La tabla externa y su copia no se modificaron. Sin cambios de aplicación, suite, migraciones ni despliegue. El próximo paso es revisión independiente de Claude, control interno y medición de velocidad. No repetir reloj, rutinas, cobros, altas ni asistencias. Último inicio simulado: 27/10/2026 15:00. T14 sigue en curso.
