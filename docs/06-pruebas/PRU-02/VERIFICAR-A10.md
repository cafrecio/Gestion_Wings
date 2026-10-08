# Claude — verificar A10

08/10/2026. Asignada en el tablero; orden preparada por Codex CyE, todavía no ejecutada por Claude.

Carlos aprobó el aspecto: «Me gusta, A10 Aprobado». A6–A9 se cierran con Carlos como verificador visual, según AGENTS §6a. A10 conserva `a_verificar`: falta comprobar la lógica invisible.

Leer el cuerpo actual de `CashflowWebController::index`, las dos vistas Cashflow, `CashflowPeriodosA10Test` y [la entrega](IMPLEMENTACION-A10-PERIODOS.md). No asumir que el informe ni las pruebas del autor demuestran el resultado.

Comprobar con datos ficticios en `wings_testing_claude`, por aplicación real:

- Día y vecinos excluidos; semana lunes–domingo, incluidos cruces de mes/año.
- Mes bisiesto y año completo; enlaces anteriores con año/mes y consulta sin parámetros.
- Filas y totales usan el mismo intervalo inclusive; filtros por caja/tipo y paginación conservan su comportamiento.
- Resultado = ingresos − egresos sin saldo inicial; revisar ajustes con signo negativo y que no cambie el saldo disponible de Caja/Liquidaciones.
- Fecha conservada al alternar modos; fin de mes limitado a fecha válida.
- Parámetros inválidos rechazados sin HTTP 500. Permisos y registro de movimientos intactos.
- Pantalla actual y selección real de período, escritorio/375. [24 capturas del autor](evidencia/a6-a10/visor-a10-aplicado.html) como referencia, no como verificación propia.

No usar `wings_testing_codex`: contiene el escenario para capturas A32. No desplegar ni llevarse cambios ajenos.

Registrar comprobaciones propias y resultado en LOG-CLAUDE; cerrar o devolver A10 mediante tablero.php y mantener DEFECTOS.md/HTML coherentes. Si cambia el número de pruebas, actualizar estado y plan. Lo no comprobado se declara pendiente.
