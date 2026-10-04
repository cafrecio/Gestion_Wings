# P2 — Verificación independiente de Entrega 1

04/10/2026 · Codex CAB · **NO APROBADA como entrega completa.**

Revisados `1c4e6dc` y `867c295`; este último se creó durante la verificación y
contiene los mismos archivos que estaban modificados al comenzar. Las huellas
SHA256 del servicio y de la vista permanecieron iguales antes/después de la suite.
Se leyó el código real y se abrió `http://gestion-wings` como OPERATIVO y ADMIN,
con la base local `wings_test`. No es una certificación del sitio remoto ni un despliegue.

## Qué se comprobó

| Criterio | Resultado y ubicación actual |
|---|---|
| Abre solo con deuda; orden por antigüedad | Verificado: `CobranzaWebController.php:16-24` selecciona `DEUDORES` por defecto; `CobranzaEstadoService.php:246-269` filtra saldo/estado y ordena por período, apellido y nombre. En pantalla abre con 2 personas; Ambas tienen septiembre como deuda más vieja. El caso julio antes de septiembre pasó en `CobranzaEntrega1Test.php:72-108`. |
| Puede verse al día | Verificado al seleccionar Todos: 63 personas para 65 registros activos. Morales, Sofía aparece una sola vez con Patín y Fútbol y $0. El filtro Al día está disponible (`cobranza/index.blade.php:49-55`). |
| Una fila y deuda por persona | El agrupamiento por DNI normalizado está en `CobranzaEstadoService.php:165-169`; suma cuotas e inscripción en `:184-238`. La prueba de Sofía con dos deudas, $15.000 + $12.000 = $27.000, pasó (`CobranzaEntrega1Test.php:119`). **Falla al filtrar deporte/grupo: A51.** |
| Pesos frente a ficha/resumen | Sin filtros: Parcial, Sofia $23.000 = cuota $21.000 + inscripción $2.000; PostCorte, Mateo $26.000 = cuota $21.000 + inscripción $5.000. Las dos filas suman $49.000, igual al resumen. La ficha de Parcial muestra cuota e inscripción por separado; ficha Morales está al día. Total del resumen: servicio `:304-306`, `:339-360`; pesos de fila: vista `:215-216`. |
| Cobrar y Ver | Se hicieron ambos clics desde Parcial: abren `/caja/cobrar/64` y `/alumnos/64`, con nombre correcto. Los 4 botones medidos en el DOM son 64 × 26 px, iguales (`cobranza/index.blade.php:229-234`). Se leyó `CajaWebController.php:588-633` y `AlumnoWebController.php:109-131`: ambos reciben **un registro**; no ofrecen en esa pantalla el conjunto de deportes de la persona. No se emitió ningún cobro. |
| Código incrustado/CSP | La vista completa no contiene `<script>` ni handlers `on...=`. Los límites permanecen 20 scripts y 10 handlers (`CspSinCodigoIncrustadoTest.php:43,64`), sin cambio de contador en estos commits; ambas pruebas pasaron. **Observación de diseño:** vista `:231` conserva `color:#fff`, fuera de tokens, contrario a AGENTS §1. No se corrigió. |
| 375 × 720 | Las columnas ya no se superponen y el documento mide 375 px sin desbordar; tabla de 960 px dentro de contenedor de 343 px con desplazamiento horizontal. Tras desplazar hasta las acciones se ven completos Cobrar y Ver. **Los tres selectores siguen sin texto legible: A18 queda parcialmente corregido y A19 también afecta a Cobranza.** |
| Suite completa propia | `php artisan test`: **343 pasan / 1955 aserciones**, 126,27 s, base descartable `wings_testing`. Incluye las 5 pruebas de Entrega 1 y las 2 de CSP. Sin suites simultáneas. |

Las rutas están en `routes/web.php:98` (listado), `:123` (ficha) y `:53` (cobro);
los destinos también se comprobaron haciendo clic.

## Hallazgo nuevo: A51

Al filtrar Fútbol, PostCorte, Mateo pasa de **$26.000** a **$5.000**, pierde Patín
y cambia Cobrar/Ver de registro 62 a 63. Sigue diciendo **Total deuda**, mientras
el resumen conserva $49.000 del club. La deuda de la persona no cambió.

**Verificado en código:** `CobranzaEstadoService.php:139-146` filtra registros
antes del agrupamiento por DNI (`:165-169`) y la suma. Por eso solo queda el deporte
seleccionado dentro de lo que se presenta como total de la persona. La misma estructura
se usa con grupo; **reproducido en navegador solo con deporte**.

No se propone ni implementa el arreglo en esta verificación.

## Hallazgo nuevo: A52

El nuevo listado convierte AL_DIA o EN_PLAZO en DEUDOR cuando queda inscripción
pendiente (`CobranzaEstadoService.php:229-230`). El estado individual y el resumen
siguen calculándose solo con cuotas (`:25-37`, `:295-302`). Esto contradice ENT-01,
que excluye inscripción del estado mensual (`ENT-01-INSCRIPCION-Y-PRIMERA-CARGA.md:32-33`).

**Verificado:** la consulta del registro 63 devuelve cero cuotas, AL_DIA individual
y DEUDOR en la fila filtrada, con inscripción $5.000. Una llamada sin filtros con
fecha explícita 05/09 devuelve EN_PLAZO individual y DEUDOR para la persona 62.
Esta segunda llamada demuestra el comportamiento de la función; no reconstruye un
alta real del 05/09, ya que el registro local tiene fecha de ingreso posterior.
Se hicieron solo lecturas, sin cambiar reloj, cuotas ni configuración.
**Inferido del cuerpo:** puede adelantar indebidamente DEUDOR en altas con cuota
corriente dentro de gracia e inscripción impaga. Falta un alta visual de ese caso.

## Alcance y límites

- La Sofía del padrón local está al día en ambos deportes. El caso con deuda positiva
  en los dos deportes se comprobó en la prueba automatizada existente; no se alteró su
  saldo para fabricar un caso visual. No se certifica un cobro conjunto multideporte.
- El formulario de Parcial ofrece también octubre virtual: muestra $53.000 en cobro
  frente a $23.000 de deuda registrada. Se leyó el agregado en memoria de la cuota
  corriente (`CajaWebController.php:616-633`); no es un importe persistido por visitar
  la página. No se modificó este camino ni se tomó como prueba de cobro adelantado.
- **`CobranzaEntrega2Test.php` no existe en este checkout ni está entre los archivos
  versionados.** No es posible reejecutar aquí sus dos rojos mencionados por Carlos.
  No se reconstruyeron ni se corrigieron. La ficha local sigue sin botón Cobrar;
  A17 y A3 pertenecen a Entrega 2.
- No se comprobó el orden visual entre períodos distintos en esta base: sus dos
  pendientes tienen septiembre. La prueba automatizada sí usa julio/septiembre.
- La base local no tiene la columna `deuda_cuotas.monto_condonado`: una consulta
  explícita la rechazó. No se migró. La suite usa un esquema actual y separado;
  esta limitación impide certificar en navegador la condonación parcial.
- No hubo cambios de código, vistas, CSS, datos del club ni servidor. El índice fue
  una pista: las vistas parciales/no parseadas se leyeron directamente.

## Evidencia

- [Listado sin filtros](evidencia/verificacion-entrega1/cobranza-escritorio.png).
- [Ficha con cuota e inscripción](evidencia/verificacion-entrega1/ficha-parcial.png).
- [Ficha Morales](evidencia/verificacion-entrega1/ficha-morales.png).
- [Destino Cobrar](evidencia/verificacion-entrega1/cobro-parcial.png).
- [A51: filtro Fútbol](evidencia/verificacion-entrega1/filtro-futbol-total.png).
- [375 px, filtros](evidencia/verificacion-entrega1/cobranza-375.png) y
  [acciones completas tras desplazarse](evidencia/verificacion-entrega1/cobranza-375-acciones.png).

Resultado: la suite verde no alcanza para aprobar la entrega. A51, A52 y los filtros
ilegibles impiden afirmar que cumple todos los criterios. Se entrega el control
para corrección por su implementador; Configuración A11 avanza solo a maqueta.
