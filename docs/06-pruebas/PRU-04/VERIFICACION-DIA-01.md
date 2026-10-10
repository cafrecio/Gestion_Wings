# PRU-04 · Día 1 · Verificación de Claude

10/10/2026 · Claude CyE · contra la base del sitio de prueba (solo lectura) y el código.

## El día ocurrió como dice el informe

| Qué | En la base |
|---|---|
| Cobros | 4 pagos completos: $48.000, $38.000, $45.000 y $33.000 ($28.000 de cuota + $5.000 de inscripción) |
| Caja de Sandra | Validada. Inicial $10.000, esperado y contado $136.000, diferencia $0, retira $126.000 |
| Cashflow | 5 movimientos por $164.000, una sola vez cada uno |

## Hallazgo 1 · Inicio muestra ingresos $0 — es un defecto real y llega a producción

**Causa comprobada.** El 09/10 se agregó a cada subrubro una clasificación que Inicio y
Reportes necesitan para contar un movimiento como ingreso del negocio. La migración
(`2026_10_09_120000_create_historial_reportes.php:35-39`) solo clasificó los subrubros de
sueldos. «Cuota Mensual» e «Inscripción al club» quedaron sin clasificar, así que todo lo
que se cobra aparece como «por clasificar» y no suma (`ReporteMensualService.php:23-26`).

- En el sitio de prueba: 18 subrubros sin clasificar, 7 clasificados.
- Ninguna pantalla permite clasificar un subrubro: no se puede arreglar desde la aplicación.
- Una instalación nueva no lo sufre (`CatalogosSeeder.php:135` sí clasifica). Una
  instalación que ya existía, sí: es el caso del sitio de prueba y va a ser el de producción.

## Hallazgo 2 · La profesora abre clases de otra — no es un defecto

`PERMISOS-ROLES.md:113`: un profesor puede ver y tomar asistencia de cualquier clase, para
cubrir suplencias. No puede crear, editar, cancelar ni reasignar.

## Hallazgo 3 · La deuda de Inicio es menor que la de Cobranza — explicado

Inicio cuenta solo cuotas; Cobranza suma también inscripciones. La diferencia es exacta:
$55.000 al empezar (11 inscripciones) y $50.000 al terminar (10). Falta decidir si Inicio
debe sumarlas o aclarar en el título que son cuotas.

## Sin verificar por mí

Lo que el informe dice de la pantalla Cobrar («Total pendiente» con meses adelantados, «40
con deuda» sobre una lista de 100) y del Cashflow (número de alumno en vez de nombre).
