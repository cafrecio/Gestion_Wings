# A56 — Verificación independiente de Codex CyE, 06/10/2026

**APROBADA.** Se cierra A56 en los dos seguimientos. Sin despliegue.

Código revisado en main `26a67c6`: cuerpo de `CashflowWebController::index`,
`CashflowService::registrarMovimientoAdmin` y fila/totales/filtros de
`resources/views/cashflow/index.blade.php`. No se incorporó `a55-inscripcion`.

Ensayo independiente en `wings_testing_codex`, fuera de la suite permanente:
cobro ADMIN real por POST, anulación por POST y gasto normal por POST.

| Comprobación | Resultado |
|---|---|
| Cobro original | I, $48.000, color de ingreso |
| Contraasiento real | E, −$48.000, color de salida |
| Gasto normal | E, −$2.500, color de salida; guardado como −2500 |
| Ingresos netos | $0: el cobro y su devolución se compensan |
| Egresos | $2.500 |
| Saldo inicial | $10.000 |
| Balance | $7.500; coincide con saldo inicial más movimientos |

[Ensayo reproducible](evidencia/verificacion-a56/VerificacionA56Test.php)
· [Filas y cifras comprobadas](evidencia/verificacion-a56/resultado-filas.json).
Primera ejecución correcta: **1 prueba, 24 aserciones**. Preparación opcional
para navegador: **1 prueba, 25 aserciones**. Pruebas existentes de signo,
celular y concordancia documental: **7 aprobadas, 24 aserciones**.
No cambia el contador de pruebas permanentes.

Pantalla observada en Chrome, Wings por HTTP local, con base descartable.
Se reutilizó el marco de `capturas-cashflow/marco-375.html`, ancho 375 real.
Login de control sin corte. Los cuatro filtros quedan en una columna;
los cuatro totales y «3 movimientos» se leen completos. Solo la tabla
se desplaza horizontalmente dentro de su tarjeta.

La aplicación envía `X-Frame-Options: DENY`: el servidor de ensayo solicita
la pantalla a Laravel y coloca **su respuesta real** en `srcdoc` dentro
del marco. No modifica los headers de Wings ni fabrica la pantalla.
[Servidor de ensayo](evidencia/verificacion-a56/servidor-verificacion.php).
La [captura nueva a 375](evidencia/verificacion-a56/cashflow-375.jpg)
se guardó desde Chrome mediante el receptor local del ensayo. Las capturas
originales de Claude se conservan en [capturas-cashflow](capturas-cashflow/).

Alcance: signo del contraasiento, gasto normal, totales y disposición a 375.
El filtro Tipo conserva el criterio de rubro existente; no se cambió ese
criterio ni se afirma que clasifique devoluciones por signo.
No se tocaron vistas, CSS, datos del club ni servidor de producción.
Cambios simultáneos de Gemini preservados fuera de esta entrega.
