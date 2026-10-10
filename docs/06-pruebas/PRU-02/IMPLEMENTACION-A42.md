# A42 — aviso de inscripción · 10/10/2026

**HECHO (Codex), a revisar.** Tablero: `a_verificar`, hizo Codex, tiene nadie; falta asignar verificador. Sin despliegue.

La causa indicada en la orden no coincide con la fuente actual: `actualizar()` ya se ejecuta al iniciar `resources/js/alumnos-inscripcion.js:28`, desde `11623b6` (22/09). Carlos autorizó «Sí, verificar y registrar». No se añadió una llamada duplicada ni se modificaron archivos de aplicación, vistas, CSS o `alumnos-form.js`.

## Comprobado en Chrome, sobre Wings real

| Caso | Resultado al abrir, sin modificar los campos |
|---|---|
| [Inscripción pendiente](evidencia/a42/edicion-pendiente.png) | Saldo $5.000,00 |
| [Inscripción pagada](evidencia/a42/edicion-pagada.png) | Saldo $0,00 |
| [Sin inscripción](evidencia/a42/edicion-sin_cargo.png) | Editar el ingreso no genera inscripción retroactiva |
| [Alta vacía](evidencia/a42/alta-vacia.png) | Pide DNI/fecha; no consulta inscripción |
| [Alta tras error real del servidor](evidencia/a42/alta-con-error.png) | Consulta automáticamente; conserva DNI, fecha y plan |

También funcionan los cambios de DNI y fecha; la cuota de mes cerrado muestra $30.000 del período actual y la decisión Sí/No. Tras un error conserva Sí; al cambiar la fecha al mes actual limpia la decisión. [Cuota tras error](evidencia/a42/alta-error-cuota.png) · [Cuota de mes cerrado](evidencia/a42/alta-mes-cerrado-cuota.png).

**13 controles aprobados**, sin errores JavaScript. Cada pantalla comprobada tiene cero scripts ejecutables incrustados. Todo JavaScript queda en archivos externos, conforme al pedido de Carlos.

Control negativo: se quitó solamente la llamada inicial del bundle externo, en memoria del navegador. El aviso quedó esperando y no consultó hasta cambiar DNI: la prueba detecta la regresión. Ningún archivo de aplicación fue modificado por ese control.

Tres alumnos ficticios, saldos y fechas concretos comprobados antes/después. El alta inválida no se guardó. La inscripción pagada es un fixture de saldo: esta prueba no valida el circuito de caja. Solo `wings_testing_codex`; sin datos del club.

## Verificaciones y alcance

- Base comprobada: `580f380efff6c27f80001c5de427a1f150d6d90b`, copia aislada sin cambios ajenos B10/A57/A58.
- Build correcto: `npm run build`, 1,63 s.
- Suite completa en verde: **573 aprobadas, 2 omitidas, 4616 aserciones, 559,63 s**, exit0 en la repetición. Primera corrida: 552 aprobadas, 2 omitidas, 21 fallas por tablas/columnas faltantes, 4548 aserciones, 460,71 s. La causa de esa alteración del esquema no quedó demostrada; no se atribuye a otro agente.
- La suite PHP no ejecuta el JavaScript del aviso. La cobertura de carga inicial queda en el [programa externo del navegador](evidencia/a42/verificar.cjs), no en una aserción PHP que pretenda comprobarlo.
- Sintaxis PHP/JavaScript correcta; vistas compiladas y caché de vistas limpiada. Diff de aplicación vacío.
- [Build y suite](evidencia/a42/resultado-controles.json) · [Resultados de navegador](evidencia/a42/resultado-navegador.json) · [Filas ficticias conservadas](evidencia/a42/resultado-datos.json) · [Cómo reproducir](evidencia/a42/README.md).

Control independiente: otro agente leyó ambos programas/resultados y abrió ocho capturas; aprobó ese alcance. No ejecutó navegador, base ni suite. Los recortes son de escritorio; no certifican pantalla completa ni celular. En cambio-DNI/fecha, el listado de consultas se captura después de esperar la respuesta: las aserciones de espera y texto prueban la actualización.

Pendiente: asignar verificador. No se cierra A42 con la revisión del autor.
