# PROMPT — T97: anular una transferencia entre cuentas — para Codex

> Prompt para Codex, escrito por Claude CAB el 01/11/2026. Carlos lo copia y se lo pasa a Codex. Lo controla Claude.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Traer `main` actualizado y marcar T97 `en_curso, tiene=Codex`.

## El problema

Una transferencia entre cuentas no se puede corregir ni anular. Solo hay rutas para crearla (`routes/web.php:138-139`), la fila de Cashflow muestra **Ver**, y el manual le dice al dueño que haga otra en sentido contrario (`resources/manual/admin/corregir-movimiento-cashflow.md:14`). Esa segunda transferencia deja el saldo bien, pero el error y su arreglo quedan como dos operaciones sueltas, sin nada que las una ni el motivo.

Por qué está así: en T61 se bloqueó corregir cada pata por separado para no romper su igualdad, y el circuito propio quedó afuera de esa tarea (`docs/02-contratos/Wings-Contrato-Caja-Cashflow-V5.md`, «Traspasos entre medios propios»).

## Lo que hay que hacer

**Anular** la transferencia completa desde la fila de Cashflow, con motivo. No se edita: corregirla es anularla y cargar otra.

| Regla | Qué tiene que pasar |
|---|---|
| Quién | Solo el dueño activo. La operativa y la profesora no ven la acción y por pedido directo reciben 403 |
| Motivo | Obligatorio. Queda guardado con quién anuló y cuándo |
| Las dos patas juntas | Una sola transacción: se revierten las dos o ninguna. Nunca queda una pata suelta |
| Original | Se conserva, marcado como anulado. No se borra nada, igual que un directo (`CashflowCorreccionService.php:83-95`) |
| Fecha | Los asientos en contra llevan la fecha de la transferencia y su clasificación congelada: corrigen ese mes, no el actual |
| Plata | Cada cuenta vuelve a como estaba, al peso. El resultado del club, los ingresos, los egresos del club y los aportes y retiros no cambian |
| Repetir | Doble envío no anula dos veces. Una transferencia ya anulada no ofrece la acción y por pedido directo se rechaza |
| Pantalla vieja | Dos dueños a la vez, o una pantalla abierta de antes: se rechaza sin tocar nada |
| Cuenta desactivada después | Tiene que poder anularse igual: es deshacer lo que pasó. Si encontrás un motivo para lo contrario, frená y consultá |
| Saldo en negativo | Si la anulación deja una cuenta en negativo, no se bloquea: hoy una transferencia tampoco lo controla, y eso es T104. Avisalo en el informe |

Cualquier pata de la transferencia lleva a la misma anulación. El contraasiento no se puede editar ni anular, y en el listado se tiene que entender que corresponde a una anulación.

## Cuidado con esto

- `ClasificacionSubrubros::exigirSinTraspaso` rechaza a propósito cualquier traspaso suelto, y `esDirecto()` da falso para una pata. No debilites esa defensa para hacer pasar la anulación: el circuito va aparte, como `TransferenciaCashflowService`. El control tiene que seguir rechazando el traspaso por todos los demás caminos.
- Cualquier lectura que distinga movimientos por `referencia_tipo` o `contra_origen_id` puede tomar mal los contraasientos nuevos. Buscalas todas antes de elegir cómo marcarlos: hay en `ReporteMensualService`, `ReporteSueldosService`, `CajaWebController` y `ReciboService`, y puede haber más.
- Revisá con cifras Cashflow, Historial, Reportes e Inicio, con filtros por mes y por cuenta. Comprobá también una transferencia de un mes anterior: los saldos y el reporte de ese mes tienen que dar bien.
- **T106 no es tuya.** Las tarjetas de Cashflow e Historial hoy suman las transferencias como ingreso y egreso, y Carlos todavía no decidió si se sacan. Lo que sí tiene que cumplirse: anular deja esas tarjetas como si la transferencia no hubiera existido. Si no da así, informalo.

## Primero las capturas: no toques las vistas todavía

**Regla de Carlos:** el diseño no se toca sin su OK. Antes de cambiar `resources/views/**`:

1. Leé `docs/03-diseno-ui/design-system/DESIGN-RULES.md`, `docs/03-diseno-ui/wings-design/SKILL.md` y `resources/views/alumnos/index.blade.php`. Tomá como modelo `resources/views/cashflow/eliminar.blade.php` y el ícono **Anular** que ya existe en `cashflow/_acciones.blade.php`. Sin JavaScript dentro de la vista.
2. Armá la propuesta **aparte, sin cambiar las vistas reales**: el HTML real de cada pantalla con los componentes reales, reemplazando solo lo que cambiaría. Hay un ejemplo en `docs/06-pruebas/PRU-04/evidencia-t96/propuesta/CapturasPropuestaT96Test.php`.
3. Dejá una galería en `docs/06-pruebas/PRU-04/APROBAR-T97.html`, a 1366 y a 375: la fila con **Anular**, la pantalla de anulación y cómo queda en Cashflow una transferencia anulada.
4. **Frená ahí y avisale a Carlos** que la abra con `Start-Process chrome "file:///C:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/APROBAR-T97.html"`. Pasale tus preguntas de diseño, si tenés. Seguí recién con su OK.

La lógica la podés dejar escrita antes, pero no subas nada de vistas sin ese OK. El commit que las toque lleva la línea `Diseno-autorizado:` con lo que Carlos aprobó.

## Documentos

Lo que el cambio vuelve falso se corrige en el mismo commit:

- `corregir-movimiento-cashflow.md:14`, `transferir-entre-cuentas.md` y `consultar-cashflow.md` si corresponde. Una sección nueva sobre cómo anular, condición por condición contra el código (`docs/06-pruebas/PRU-04/REVISOR-MANUAL.md` punto 6), con captura. Actualizá las fichas con `etapa-c/generar-fichas.py` y corré `php scripts/manual/verificar-manual.php`.
- El contrato V5 («Traspasos entre medios propios»), que hoy dice que el circuito de anulación queda afuera.
- `docs/00-estado/ESTADO-ACTUAL.md`, `PLAN-PRODUCCION.md` y `CHECKLIST-CARLOS.md`, con el nuevo número de pruebas (lo controla `DocumentacionNoMienteTest`). Si hay migración, `docs/04-tecnico/DESPLIEGUE-PRODUCCION.md` si lo que dice queda desactualizado.
- La tabla de pruebas no se toca (`AGENTS.md` §5-ter): proponé los casos en el informe y Claude los pasa.

## Pruebas

- Una prueba por cada fila de la tabla de reglas, con los importes de las dos cuentas al peso. Tiene que fallar si se saca la regla: probalo.
- Las de `TraspasosEntreMediosT61Test` y `CashflowCorreccionesT27Test` siguen en verde sin cambiar lo que esperan. Si alguna tiene que cambiar, explicá por qué en el informe.
- Pedidos directos contra cada regla, y dos anulaciones a la vez con dos conexiones reales.
- **Suite completa en `wings_testing_codex`**, porque toca plata compartida. Nunca con otra suite corriendo en la misma máquina. Solo esa base: no uses la base local de trabajo.

## Reglas

- **Sin despliegue:** Cora trabaja en el sitio de prueba. No toques el sitio ni producción.
- Solo T97. Si encontrás otra cosa, se informa y no se arregla. T94 y T104 son de otras tandas, y T96 la está controlando Gemini: no toques `caja/configuracion`, `CajaService` ni el catálogo de ajustes.
- Si algo de este prompt no coincide con el código, confiá en el código y decilo en el informe. Si cambia la regla de plata, frená y consultá a Carlos.

## Cómo entregar

- Informe en `docs/06-pruebas/PRU-04/IMPLEMENTACION-T97.md`: qué se hizo, ejemplo al peso con las dos cuentas, lecturas revisadas, lo comprobado y lo inferido por separado, suite con sus números, casos propuestos para la tabla.
- Tablero: `php scripts/tablero/tablero.php cambiar T97 estado=a_verificar tiene=Claude`, con un paso que diga el resultado.
- Entrada en `docs/00-estado/LOG-CODEX.md`, firmada según la máquina.
- **Commit solo de lo tuyo:** `git status`, mirar archivo por archivo, agregar por nombre con `git add <archivo>`, nunca `git add -A`, `git add .` ni `git commit -a`. Si un archivo tuyo tiene cambios ajenos sin publicar, frenar y avisar a Carlos.
