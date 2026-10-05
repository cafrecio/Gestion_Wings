# P2 — Verificación independiente de Entrega 1

**Resultado vigente: APROBADA, segunda vuelta del 05/10/2026.** El rechazo del
04/10 se conserva como antecedente; A51/A52 fueron corregidos en `abc346a`.

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

## Segunda vuelta — 05/10/2026 · Codex CAB · APROBADA

Se revisó `abc346a` y el código actual de `main` (`4fb185e`), después de `git pull`
(sin novedades). Esta aprobación usa la decisión nueva: **una fila por registro
deporte + DNI**, no por persona. No se modificó código, vistas, estilos ni pruebas.

### Entorno y evidencia

Suite ejecutada desde el repositorio, con `DB_DATABASE=wings_testing_codex`:
**380 pruebas / 2242 aserciones, todas verdes**, 144,94 s. `npm run build` pasó.
[Salida completa de la suite](evidencia/verificacion-entrega1-v2/suite-completa.txt).
El navegador abrió la aplicación real en `http://127.0.0.1:8772`, usando una copia
ignorada dentro de `storage/logs/a43-entrega` y esa misma base descartable, una vez
terminada la suite. Los archivos versionados de `app`, `resources`, `routes` y
`bootstrap` se alinearon con HEAD; se usó el compilado recién generado.
Los usuarios ADMIN/OPERATIVO y los seis registros son **ficticios**. No se usó ni
modificó el padrón local del club ni el servidor remoto. No se emitieron pagos.
Se consultó el índice de código y se confirmaron las conclusiones leyendo cuerpos
reales; Blade y CSS se verificaron directamente, además del navegador.

### Criterios comprobados

| Criterio | Evidencia verificada y ubicación |
|---|---|
| A51: fila y deuda propias | `CobranzaEstadoService.php:168-200` calcula cada registro; `:234-238` filtra después. `cobranza/index.blade.php:115,208-210` muestra **Deuda**. Sofía Morales, DNI ficticio 40000001, tiene dos filas: Patín $15.000 y Fútbol $12.000. La fila de Fútbol conserva $12.000 al filtrar por deporte y por grupo. Enlaces conservan el ID 844. |
| Ayuda debajo del DNI | Servicio `:203-228`, vista `:150-154`. Las dos filas de Sofía indican la deuda del otro deporte incluso al filtrar. Ana Pérez debe $20.000 en Patín y $0 en Fútbol: la fila de Patín **no** muestra ayuda; la de Fútbol, visible en Todos, sí avisa la deuda de Patín. |
| A52: listado | Servicio `:169-174,192-198` conserva el estado calculado solo con cuotas. Lucía Gaitán, solo inscripción $5.000, aparece **Al día** en Todos y no entra en el filtro inicial de deudores. Luz Gómez, cuota corriente $48.000 más inscripción $5.000, muestra $53.000 y **En plazo**. Comprobado también como OPERATIVO. |
| A52: ficha | `AlumnoWebController.php:115-119` llama a `estadoAlumno`; servicio `:25-37,373-409`, vista `alumnos/show.blade.php:128-144`. Lucía muestra inscripción pendiente $5.000 y estado **Al día**; Luz conserva **En plazo**, con cuota $48.000 y cinco días de gracia. No hay pagos registrados. |
| A52: resumen | Servicio `:289-300` usa el mismo cálculo de cuotas; inscripción suma al importe separadamente (`:326-347`). Vista `cobranza/index.blade.php:11-14`. Los seis registros muestran **2 Al día, 1 En plazo, 0 Morosos, 3 Deudores**. Total $105.000 = $95.000 de cuotas + $10.000 de inscripción; coincide con la suma de las seis filas de Todos. Las tarjetas siguen siendo globales al filtrar. |
| Apertura y acciones | Controlador `CobranzaWebController.php:16-24`; servicio `:241-263`. Apertura: tres registros deudores, agosto antes de septiembre; Todos permite ver los seis. Cobrar y Ver miden ambos **64 × 26 px** (`cobranza/index.blade.php:222-228`) y abren el registro correspondiente. Cobrar con plan válido abrió a Sofía en Fútbol. La cuota corriente que se ve adicionalmente en ese formulario es una previsualización sin guardar (`CajaWebController.php:611-633`), no un cambio del saldo del listado. |
| Colores y CSP | Inspección del diff de `abc346a`: ningún color hexadecimal, RGB/HSL ni JavaScript/handler añadido. El `#fff` anterior del botón fue reemplazado por `var(--color-surface)` (`:225`). La vista completa no contiene `<script>` ni `onclick/onchange/onsubmit`. `CspSinCodigoIncrustadoTest` pasó: **19 bloques / 10 handlers**. El cambio de 20 a 19 corresponde a A11 y está explicado en su comentario (`:40-44`); no lo produjo esta entrega. |
| Filtros a 375 | CSS `app.css:962-981`: apilado y acciones contenidas. Se abrió y capturó Cobranza, Alumnos, Clases, Movimientos, Profesores, Historial de Cajas, Grupos, Caja, Revisión y selector de cobro; también el selector de modo del alta de Clase. Se usaron los filtros de deporte en Cobranza y de tipo en Historial. Selectores/fechas comunes tienen **48 px** de alto y ancho disponible (278–293 px según scrollbar); los botones conservan **96 × 32 px**, separados. Revisión mantiene sus dos controles propios de 37 px; Caja mantiene el campo de mes de 230 px e Historial las fechas de 160 px, todos contenidos. |

Los filtros dejaron de ser cuadrados sin texto. El texto largo del estado inicial
de Cobranza y algunos placeholders conservan elipsis; no se afirma que toda cadena
quepa completa. No se observaron superposiciones en las barras revisadas. La tabla
de Cobranza conserva desplazamiento horizontal: se leen las columnas por separado,
sin comprimir ni superponerlas.

### Capturas de esta vuelta

Todas están en `evidencia/verificacion-entrega1-v2/`:

- [Cobranza en escritorio](evidencia/verificacion-entrega1-v2/cobranza-escritorio.jpg), [Fútbol](evidencia/verificacion-entrega1-v2/cobranza-filtro-deporte.jpg) y [grupo Fútbol](evidencia/verificacion-entrega1-v2/cobranza-filtro-grupo.jpg).
- [Todos: estados, inscripción y total](evidencia/verificacion-entrega1-v2/cobranza-todos-inscripcion.jpg), [ficha Al día](evidencia/verificacion-entrega1-v2/ficha-inscripcion-al-dia.jpg) y [ficha En plazo](evidencia/verificacion-entrega1-v2/ficha-inscripcion-en-plazo-375.jpg).
- [Cobranza 375](evidencia/verificacion-entrega1-v2/cobranza-375.jpg), [filtro usado](evidencia/verificacion-entrega1-v2/cobranza-filtrar-375.jpg), [OPERATIVO](evidencia/verificacion-entrega1-v2/operativo-cobranza-375.jpg) y [destino Cobrar](evidencia/verificacion-entrega1-v2/destino-cobrar.jpg).
- [Alumnos](evidencia/verificacion-entrega1-v2/alumnos-375.jpg), [Clases](evidencia/verificacion-entrega1-v2/clases-375.jpg), [Movimientos](evidencia/verificacion-entrega1-v2/movimientos-375.jpg), [Profesores](evidencia/verificacion-entrega1-v2/profesores-375.jpg), [Historial](evidencia/verificacion-entrega1-v2/historial-cajas-375.jpg), [Grupos](evidencia/verificacion-entrega1-v2/grupos-375.jpg), [Caja](evidencia/verificacion-entrega1-v2/caja-375.jpg), [Revisión](evidencia/verificacion-entrega1-v2/revision-375.jpg), [selector de cobro](evidencia/verificacion-entrega1-v2/seleccionar-cobro-375.jpg) y [alta de Clase](evidencia/verificacion-entrega1-v2/alta-clase-375.jpg).

### Hallazgos nuevos y límites

Se registraron **A53, A54 y A55** en `DEFECTOS.md`, sin corregir: tarjetas de Grupos
y selector de cobro desbordan en celular; el selector cuenta a quien no debe e
ignora la inscripción al mostrar el saldo. Son pantallas ajenas a la implementación
de Entrega 1, encontradas al revisar los consumidores de la barra compartida. Sus
filtros sí quedaron contenidos; estos hallazgos no reabren A51/A52 ni rechazan el
cambio de filtros. `abc346a` no toca esas vistas ni la consulta del selector.

No se comprobó el despliegue remoto ni se alteraron sus datos. Se usó una Sofía
ficticia con ambos deportes y deudas positivas; no se certifican las cifras del
padrón real. El ancho de 375 se simuló en navegador, sin un teléfono físico ni
gestos táctiles reales. No se recorrieron todos los pies de formularios que usan
`filtros-actions`; el control visual cubre las barras de filtros y el alta de Clase.
`CobranzaEntrega2Test` sigue sin existir en la suite actual: no hay dos rojos de
esa clase para reproducir ni se verifica Entrega 2 con este informe.

**Dictamen:** Entrega 1 de Cobranza **aprobada** en código y pantalla; A51/A52
corregidos en `abc346a`, filtros A18/A19 comprobados. Gemini puede continuar
Entrega 2. Los nuevos defectos quedan registrados para asignación; sin despliegue.
