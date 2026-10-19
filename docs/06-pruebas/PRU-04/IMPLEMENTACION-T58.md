# T58 — El dueño crea desde la ficha la cuota de un mes pasado que nunca se generó

18/10/2026 · Claude CAB. Decisión de Carlos (DU-23, 13/10): «Que el dueño pueda crear la cuota
de un mes pasado desde la ficha».

## Qué pasaba

Si a un alumno nunca se le generó un mes, no había forma de cobrárselo. El cobro lo rechazaba
con «Debe crearla un administrador», y no existía ninguna pantalla para hacerlo.

## Qué cambió

| Dónde | Cambio |
|---|---|
| `PagoCuotaService::mesesPasadosSinCuota` | Lista los meses desde el de alta hasta el anterior al actual que no tienen cuota y en los que el alumno tenía plan, con el precio de ese plan. |
| `PagoCuotaService::crearCuotaMesPasado` | Crea la cuota con el precio del plan de ese mes, lo mismo que habría hecho el generador del día 1. Solo un dueño activo. Rechaza el mes actual o futuro, meses anteriores al alta, meses que ya tienen cuota y meses sin plan. Anota en la cuota quién la creó y cuándo. |
| `AlumnoWebController::crearCuotaPasada` y ruta `web.alumnos.cuotas-pasadas` | Dentro del grupo `ensure.admin.web`: la operativa recibe 403. |
| Ficha del alumno (`alumnos/show`) | Debajo de «Cuotas condonadas», solo para el dueño y solo si falta algún mes: «Meses sin cuota generada», un selector con mes y precio, y el botón «Crear», que pide confirmación. Sin JavaScript nuevo: la confirmación es la de `ds-app.js`. |
| Mensaje del cobro | «Debe crearla un administrador» pasa a «La crea el dueño desde la ficha del alumno». |

**Lo que no se inventó:**
- **El importe:** es el del plan, igual que el generador. Si es el mes de alta y el alumno nunca
  pagó, el descuento lo aplica el cobro con las reglas de siempre.
- **El historial de reportes:** registra la deuda el día en que se crea, no en el mes pasado.
  Es la regla que ya existe: «un pago retroactivo no demuestra que una deuda recién creada
  existía antes».
- **No se pide motivo:** Carlos no lo pidió. La cuota anota quién la creó y cuándo.

## Pruebas

`tests/Feature/CuotaMesPasadoT58Test.php`, 8 pruebas:
1. Ofrece solo septiembre, el mes que falta.
2. El dueño lo crea al precio del plan, queda anotado y deja de ofrecerse.
3. La operativa recibe 403.
4. Rechazos: mes actual, futuro, anterior al alta, ya existente, texto inválido.
5. Sin plan en ese mes: no la crea.
6. La ficha muestra el bloque al dueño y no a la operativa.
7. Sin meses faltantes, el bloque no aparece.
8. Después se cobra como cualquier cuota.

**En Chrome** (base descartable, dueño sintético; [evidencia-t58-claude/](evidencia-t58-claude/)):
- la ficha con el mes faltante a 1366 y a 375, sin desborde y con «Crear» a la derecha;
- Crear pidió confirmación («¿Crear la cuota del mes elegido? Queda como deuda del alumno.»);
- avisó «Se creó la cuota de septiembre 2026: $12,000.00.»;
- el bloque desapareció.

## Pendiente

El aspecto del bloque nuevo lo aprueba Carlos.
