# B11 — Verificación de Claude · 16/10/2026 · APROBADA

Verificado sobre los cuatro commits locales de Codex (`363346b` a `bfec08d`). El aspecto lo
aprobó Carlos (Cobrar, Configuración y celular). Esto verifica la lógica.

## Qué leí

Todo el cambio de código: `PunitorioService`, el comando `cobranza:aplicar-punitorios` y su
programación diaria (06:15, hora de Wings), la migración, `CobranzaEstadoService`,
`PagoCuotaService`, `ReporteMensualService`, `ReciboService`, `CajaWebController`,
`ConfiguracionWebController`, `AlumnoWebController` y las tres vistas.

## Mis controles, por los caminos que usa la gente

[`evidencia-b11-claude/VerificacionB11ClaudeTest.php`](evidencia-b11-claude/VerificacionB11ClaudeTest.php): 6 pruebas, 48 aserciones, en verde.

| Control | Resultado |
|---|---|
| Porcentaje «12,5» y día «15» cargados por la pantalla de Configuración; el comando los usa | El 15 no aplica, el 16 sí: $3.750 sobre $30.000 (no 12 %) |
| Comando corrido dos veces el mismo día | Un solo recargo |
| Cambiar el porcentaje después | El recargo aplicado no cambia |
| Porcentaje 0 | Ningún recargo y el resumen de Cobranza idéntico |
| D1: cuota de septiembre impaga, porcentaje prendido en octubre | Sin recargo |
| D2: alumno que ingresó el 09/10 | Sin recargo el 11; con recargo el 17, vencido su plazo |
| D3: alumno inactivo | Sin recargo |
| D4: Cobranza, Reportes, Inicio del dueño y buscador de Cobrar | Los cuatro dan $31.500; con la cuota paga y el recargo no, no está al día |
| Cobro por pantalla: entrega menor a la cuota | El recargo queda intacto |
| Cobro del resto | Cuota $10.000 en Cuota Mensual y recargo $1.500 en Punitorio Cuota; `monto_cuota` sin el recargo (no entra en la comisión) |
| Anular ese cobro | El recargo vuelve a pendiente |
| Perdón: la operativa intenta, el dueño perdona | La operativa no puede; perdonado, el alumno sigue sin estar al día por la cuota |

Pruebas de Codex (35) en mi base: verdes. **Suite completa en `wings_testing_claude`: 761
aprobadas, 2 omitidas.**

## Observaciones que no cambian el veredicto

- `CatalogosSeederTest` cambió de 11 a 12 rubros y de 18 a 19 subrubros. El contrato pide no
  tocar pruebas viejas; este cambio es el rubro nuevo que el mismo contrato manda crear.
- Si el programador no corre durante los últimos días de un mes, el recargo de ese mes no se
  recupera al mes siguiente: es la consecuencia de D1 (solo mira el mes en curso).

## Sin verificar

- El comando en el sitio de prueba con un porcentaje mayor que cero: el sitio queda en 0, como
  se entrega. Prenderlo cargaría recargos a los alumnos de la prueba; lo decide Carlos.
