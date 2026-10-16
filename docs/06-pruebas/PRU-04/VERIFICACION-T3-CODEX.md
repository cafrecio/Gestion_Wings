# T3 — Verificación independiente de Codex

16/10/2026 · Codex CyE · hizo Claude · commit `f4e64368daf2d217bf09cb2a48f5e520976833c4`.

Esta primera parte conserva la revisión de `f4e6436`. La revisión actual de
`318ef2f` está en [Segunda vuelta](#segunda-vuelta).

**Veredicto: DEVUELTA a Claude.** La sincronización funciona en los casos pedidos,
pero confunde la fecha del último cambio con la fecha de cierre. El error pasa el
guardián nuevo. No corregí la implementación ni desplegué nada.

## Motivo de la devolución

En la copia aislada, ejecuté:

```text
php scripts/tablero/tablero.php cambiar A6 paso="prueba"
```

La tarea seguía cerrada. Solo cambié el próximo paso. Sin embargo, tanto el
encabezado Markdown como el sello HTML pasaron de **CERRADO 08/10** a
**CERRADO 16/10**. `TableroAlDiaTest` siguió verde: 3 pruebas/3 aserciones.

- `scripts/tablero/tablero.php:109` actualiza `fecha` en cualquier cambio.
- `scripts/tablero/lib.php:562–564` presenta esa misma fecha como fecha de cierre.
- `tests/Feature/TableroAlDiaTest.php:33–47` solo compara con el mismo generador;
  por eso no detecta este dato falso.

[Antes/después y resultado](evidencia-t3-codex/fecha-cierre.json) ·
[diff real](evidencia-t3-codex/fecha-cierre-diff.txt) ·
[guardián verde ante el error](evidencia-t3-codex/fecha-cierre-guardian.txt) ·
[reproducción](evidencia-t3-codex/probar-fecha-cierre.mjs).

**Qué debe corregir Claude:** conservar la fecha real del cierre al editar otros
campos y añadir una regresión que detecte el caso. No convertir fechas de último
cambio o fechas sin respaldo histórico en fechas de cierre.

## Los nueve controles pedidos

| Nº | Comprobación y resultado propio |
|---|---|
| 1 | `git show f4e6436^:…` contra `f4e6436:…`, defecto por defecto: **70 presentes, cero cambios abierto/cerrado** en Markdown, casilla HTML y etiqueta HTML. Los 70 cuerpos Markdown y los 70 detalles HTML permanecen iguales. [Datos completos](evidencia-t3-codex/migracion.json), [verificador independiente](evidencia-t3-codex/auditar-migracion.mjs). |
| 2 | **56 defectos** tienen alguna diferencia de encabezado: 49 en Markdown, 56 en HTML; la lista íntegra antes/después está en [ENCABEZADOS.md](evidencia-t3-codex/ENCABEZADOS.md). Abajo separo pérdida de información de falsedad comprobada. |
| 3 | Copia desde `git archive f4e6436`, inicializada con Git propio bajo storage/app. El comando exacto B11 paso=prueba escribe los **cinco archivos** (mtime cambia), pero el diff solo muestra tareas.json y TABLERO.html: B11 sigue en «Sin empezar», donde QUIEN-HACE-QUE solo lista IDs; su paso no aparece allí y su estado no cambió. No afirmo que ese comando cambie tres contenidos. Control adicional: B11 estado=en_curso tiene=Codex paso=prueba sí cambia los cinco contenidos. [Resultados](evidencia-t3-codex/pruebas-copia.json), [diff pedido](evidencia-t3-codex/diff-B11-paso.txt), [diff adicional](evidencia-t3-codex/diff-B11-estado-stat.txt). |
| 4 | En esa copia, tres modificaciones separadas, restaurando cada vez: destildar A1 en HTML; cambiar CERRADO por Sin empezar en su encabezado Markdown; cambiar un contador del tablero a 999. **Las tres fallan y nombran el archivo correcto**. [HTML](evidencia-t3-codex/casilla-a-mano.txt), [Markdown](evidencia-t3-codex/encabezado-a-mano.txt), [contador](evidencia-t3-codex/numero-a-mano.txt). Copia sana antes/después: verde. |
| 5 | Verificado leyendo los cuerpos: `tablero_html`, lib.php:289–290 usa la fecha máxima del JSON, no el reloj. Las otras funciones de generación formatean fechas recibidas; no consultan la fecha actual. El JavaScript literal (:438–449) usa new Date al abrir la página; no se ejecuta al generar ni en PHPUnit. `date()` está en cambiar/nueva del CLI, no en generar. **No cambié el reloj del sistema.** |
| 6 | Convertí los cinco archivos de la copia a CRLF: **3 pruebas/3 aserciones verdes**. `sinRetornos`, TableroAlDiaTest.php:62–65 convierte CRLF a LF. Se comprobó Windows/Linux; no se extrapola a archivos con CR sueltos. [Salida](evidencia-t3-codex/cinco-archivos-CRLF.txt). |
| 7 | Leídos los seis documentos indicados y buscadas todas las menciones de defecto/traer/marcar/sincronizar/transición. No quedó una instrucción vigente que mande cambiar estados en DEFECTOS a mano o ejecutar traer. AGENTS.md:410–413 y QUIEN-HACE-QUE.md:97–99 mandan usar el generador. La mención de traer en el paso de T3 dice que fue retirado. El CLI de la copia rechaza traer con salida 1 y no cambia archivos: [salida](evidencia-t3-codex/traer-retirado.txt), [barrido](evidencia-t3-codex/instrucciones-vigentes.txt). |
| 8 | Leídos los cuerpos de los cuatro guardianes y ejecutados juntos: **9 pruebas/31 aserciones verdes**. No corresponde borrar los anteriores como redundantes; ver detalle debajo. [Corrida](evidencia-t3-codex/guardianes.txt). |
| 9 | Suite completa propia en `wings_testing_codex`: **723 aprobadas, 2 omitidas, 6929 aserciones, 887.55 s; salida 0**. Son 725 pruebas. **No se repitió el fallo de Claude:** los diez casos de PerdonConcurrenteT33Test pasaron dentro de la suite completa, incluidas las dos esperas reales con proceso separado. DocumentacionNoMienteTest también pasó. No se necesitó repetir T33 aislada para conseguir verde. [Salida completa](evidencia-t3-codex/suite-completa.txt), [resultado terminal](evidencia-t3-codex/suite-salida.json). |

Las pruebas destructivas de documentos se hicieron exclusivamente en la copia.
Los cinco archivos compartidos conservaron sus SHA durante esos ensayos; la copia
quedó restaurada byte por byte. [Preparación](evidencia-t3-codex/copia.json) y
[ensayo reproducible](evidencia-t3-codex/probar-copia.mjs). Esas pruebas extienden
PHPUnit\Framework\TestCase y no conectan a base; no se cruzaron con la base de la suite.

## Qué información perdió el encabezado

La tabla completa contiene los 56 IDs y los cuatro textos anterior/nuevo. Estos
son los cambios de significado o precisión que requieren distinguirse del simple
cambio «verificado» → «verificó»:

- **A23:** desaparece del encabezado la aprobación visual de Carlos; queda solo
  la verificación de Claude. La aprobación continúa en el cuerpo (:250 y siguientes).
- **B10:** «Codex y Gemini» pasa a «Gemini». El cuerpo sigue explicando las dos
  revisiones (:712 y siguientes). El nuevo texto es incompleto, no prueba que Gemini
  no verificara.
- **A54 y A56:** se pierde CAB/CyE en la identidad de Codex (:568 y :601).
- **A38, A44, A57 y A58:** «aprobado por Carlos» pasa a «verificó Carlos».
  AGENTS.md §6a permite a Carlos verificar el aspecto visual; ese cambio de palabra
  no demuestra por sí solo una atribución falsa.
- **B7 y B13:** la decisión de alcance o de conservar los datos se convierte en
  «verificó Carlos» (:693, :729). Sus cuerpos conservan el cierre por decisión;
  B7 dice expresamente que se cerró sin programar nada. El sello pierde esa
  distinción y no debe presentarse como evidencia de implementación comprobada.
- **B12:** se pierde «En curso 09/10», reemplazado por estado y dueño actuales.
- **B14, B15 y B16:** se pierde «verificado/corregido» sin identidad. B15 recibe
  23/09, que no figuraba en su encabezado anterior; no comprobé la fecha histórica
  real de su cierre. A1 recibe 05/10 como cierre donde antes decía «verificado 05/10».
- **A18, A19, A21, A22, A51 y A52, solo HTML:** «VERIFICADO 05/10» pasa a
  «CERRADO 04/10». Son fechas de conceptos distintos; no prueba un cambio de estado.
- A23, B7, B12 y B13 también incorporan la gravedad que ya existía en tareas.json;
  B3/B11 incorporan su estado abierto. Son adiciones, no cambios abierto/cerrado.

No afirmo que toda pérdida sea un dato falso ni que se borraran los antecedentes:
los cuerpos no cambiaron. **La falsedad reproducida es la fecha de cierre de A6
al cambiar solo su paso.** Las atribuciones históricas no se reauditaron contra
código y capturas de cada una de las 70 tareas: se comparó su migración documental.

## Los guardianes anteriores siguen aportando

- **DefectosNoDivergenTest:** mantiene el control de IDs entre Markdown y HTML.
  Prueba propia adicional: borrar la ficha A1 del HTML deja verde al guardián nuevo
  porque regenera sobre las fichas existentes; el anterior falla y nombra A1.
  [Nuevo verde](evidencia-t3-codex/ficha-html-ausente-guardian-nuevo.txt),
  [anterior rojo](evidencia-t3-codex/ficha-html-ausente-guardian-anterior.txt).
  Sus controles de estados/avance se solapan parcialmente con T3, pero no es
  redundante la clase completa.
- **TablerosNoDivergenTest:** compara los dos planes de septiembre, fuera de los
  cinco archivos de T3. Conserva su propósito y no es redundante con T3.
- **DocumentacionNoMienteTest:** comprueba el conteo de métodos de prueba en tres
  documentos; T3 no cubre ese dato. Sigue siendo útil. Sus límites con data providers
  están declarados en el cuerpo de la prueba; no es un certificado universal del runner.

## Alcance y entrega

`git pull --ff-only`: ya actualizado. AGENTS.md completo, §6a, protocolo, tres logs,
resumen, índice del plan y regla 8 leídos. Codebase-memory reindexado y consultado;
su cobertura figura no disponible/metadata_changed, por lo que se verificaron las
fuentes reales completas de las funciones y de los guardianes relevantes.

Durante la suite entró `c04a728`, que actualiza solo T4 en tareas.json y sus dos
vistas documentales. La comparación de migración y las corrupciones pertenecen a
la copia fija `f4e6436`; no se heredó una suite ajena ni se presenta la carpeta
compartida como inmutable.

Sin arreglos de código, cambios de producto, reloj, despliegue o base del club.
T3 devuelta a Claude con el comando del tablero; esa es la única mutación propia
del tablero compartido en esta entrega. La copia fue restaurada y luego retirada;
quedan los scripts y las salidas para reproducirla. Los números de pruebas de los
documentos siguen en 725: esta verificación no añade ni retira tests de la suite.
Tras devolverla, los cuatro guardianes vuelven a pasar: **9 pruebas/31 aserciones**,
[salida final](evidencia-t3-codex/guardianes-finales.txt). Diff de vistas/CSS de
producto vacío. No se cierra T3.

## Segunda vuelta

16/10/2026 · Codex CyE · corrección examinada `318ef2f` · carpeta al iniciar `3f0999c`.

**Veredicto: DEVUELTA a Claude por un aviso al cerrar un defecto abierto.** El
caso original de A6 está corregido. Las fechas históricas y los textos cerrados
quedaron restaurados; B15 tiene respaldo complementario. No corregí el código.

### Qué falta corregir

En la nueva copia aislada ejecuté:

```text
php scripts/tablero/tablero.php cambiar B11 estado=cerrado hizo=Claude verifica=Codex
```

El cierre se guarda, con salida 0, pero emite **Warning: Undefined array key 2**
en `scripts/tablero/lib.php:660`. El grupo `( checked)?` de :655 es opcional;
una casilla que estaba abierta no tiene `$m[2]`. La condición nueva lo lee justo
cuando se la está cerrando. [Salida real](evidencia-t3-codex/segunda-vuelta/cerrar-B11.txt).

Claude debe evitar leer ese grupo cuando no existe y cubrir el cierre en HTML
sin avisos. Las dos pruebas nuevas no lo cubren: el ciclo de cierre prueba datos
y Markdown, mientras el control HTML solo recorre A6, que ya estaba cerrado.
No afirmo que el aviso impida guardar: comprobé que el guardado termina.

### Los seis controles

| Nº | Resultado propio |
|---|---|
| 1 | Copia fija de `318ef2f`: `cambiar A6 paso="prueba"` cambia su último movimiento, pero conserva `cerrado_el=2026-10-08` y deja idénticos el encabezado Markdown y el renglón HTML. [Antes/después](evidencia-t3-codex/segunda-vuelta/pruebas-copia.json). |
| 2 | B11 se cierra por el CLI con fecha de ese día; otro cambio del CLI la conserva. Además, con el argumento de fecha del helper simulado a 2030-02-05, el último cambio se mueve y el cierre sigue igual en datos y ambos documentos. El CLI lo reabre como devuelto y borra `cerrado_el`, CERRADO y la marca HTML. **El ciclo de datos funciona, con el aviso descrito al cerrar.** No se cambió el reloj del sistema. |
| 3 | Las **67 filas cerradas** fueron cotejadas contra `f4e6436^:DEFECTOS.md`: **66 fechas coinciden**, ninguna difiere y B15 no tenía fecha en aquel encabezado. [Las 67 filas](evidencia-t3-codex/segunda-vuelta/FECHAS-CIERRE.md), [datos](evidencia-t3-codex/segunda-vuelta/historia.json). B15 se resuelve por la evidencia complementaria detallada abajo. |
| 4 | Los **67 encabezados Markdown y 67 renglones HTML cerrados son idénticos** a los anteriores a T3. Esto incluye los 53 cerrados de mi lista anterior de 56; los otros tres eran B3/B11/B12, que ahora muestran su estado y dueño actuales y no tienen fecha de cierre. Se restauraron la aprobación de Carlos en A23, los dos verificadores en B10 y los motivos de B7/B13. Comprobación independiente, sin usar el generador como oráculo. |
| 5 | Las dos pruebas nuevas pasan con `318ef2f`: **2 pruebas/18 aserciones**. Con lib.php y CLI de `f4e6436`, manteniendo las pruebas nuevas, dan **dos errores por función tablero_cambiar inexistente**, no dos fallos de aserción sobre fechas. Además ejecuté el CLI antiguo: vuelve a reproducir A6 08/10 → 16/10. Después restauré la copia y sus cinco pruebas pasaron. [Actual](evidencia-t3-codex/segunda-vuelta/dos-nuevas-actuales.txt), [antiguo](evidencia-t3-codex/segunda-vuelta/dos-nuevas-con-codigo-f4e6436.txt), [CLI antiguo](evidencia-t3-codex/segunda-vuelta/A6-paso-codigo-f4e6436.txt). |
| 6 | Suite completa propia, solo `wings_testing_codex`: **725 aprobadas, 2 omitidas; 727 pruebas, 6947 aserciones, 681.68 s, salida 0**. PerdonConcurrenteT33Test pasa sus diez casos dentro de la suite. [Corrida completa](evidencia-t3-codex/segunda-vuelta/suite-completa.txt), [resultado terminal](evidencia-t3-codex/segunda-vuelta/suite-salida.json). |

La copia de `318ef2f` se creó con git archive, sin `.env` ni vendor, bajo
`storage/app/verificacion-t3-codex-318ef2f`. Los ensayos no usan Laravel ni base;
el autoload es el existente y los tests se cargan desde la copia por ruta absoluta.
Los documentos y el código de esa copia se restauran tras cada variante. SHA de
los cinco documentos y de ambos archivos de código compartidos comprobados iguales
durante el ensayo. [Runner](evidencia-t3-codex/segunda-vuelta/probar-copia.mjs).

### B15: juicio sobre la fecha

**No acepto «último cambio = cierre» como regla. En B15, el 23/09 sí tiene respaldo
documental independiente del campo fecha:**

- `f4e6436^:docs/04-tecnico/SERVIDOR.md:99–112` fecha el incidente y su corrección
  el **23/09/2026**, con el arreglo del registro A explícito.
- `031bdec5a65d1df235acc2441925117a0cb9a8f0`, fechado ese día, ya incluye B15 como
  «Verificado y corregido». Leí el contenido de ese commit, no solo su fecha.
- El encabezado inmediato anterior a T3 sigue sin acreditar un día, por eso la
  tabla lo distingue de las otras 66 coincidencias. No se inventa una coincidencia.

Por esos antecedentes considero aceptable `cerrado_el=2026-09-23` para B15.
No es el motivo de la devolución. [Extractos comprobados](evidencia-t3-codex/segunda-vuelta/B15-RESPALDO.md).
No se revalidó DNS ni se reconstruyó una intervención del servidor en esta revisión.

### Alcance de la segunda vuelta

Los cuatro guardianes actuales pasan juntos: **11 pruebas/49 aserciones**.
[Salida](evidencia-t3-codex/segunda-vuelta/guardianes.txt). Sintaxis correcta en lib.php,
CLI y TableroAlDiaTest.php. Las fuentes se leyeron completas en las funciones
relevantes; el índice se reindexó, pero declaró cobertura no disponible, por lo que
no se tomó como prueba de ausencia de defectos.

Durante la corrida entró `31d805f`, cierre documental de T4 por otro agente. No se
modificó código de T3. La copia examinada siguió fija en `318ef2f`; esa actualización
ajena se conserva y no se atribuye a esta entrega.

Solo verificación, documentación y devolución del tablero. Sin arreglos del
generador, cambios de producto, datos del club, reloj ni despliegue. La primera
vuelta conserva su evidencia; esta revisión tiene su propia carpeta de salidas.
Después de devolver T3, los guardianes vuelven a pasar: **11/49**,
[salida final](evidencia-t3-codex/segunda-vuelta/guardianes-finales.txt). La copia
quedó restaurada y fue retirada. Diff de vistas/CSS de producto vacío.
