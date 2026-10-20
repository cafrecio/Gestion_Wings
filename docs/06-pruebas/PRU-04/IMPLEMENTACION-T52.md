# T52 — Las clases pasadas muestran también a quien tiene una marca guardada

20/10/2026 · Claude CAB. Decisión de Carlos (DU-09): «Sí, los cambios son hacia adelante».
Casos de la tabla: ALU-013 (punto g) y ASI-009 (puntos d y e).

## Qué pasaba

La lista de una clase se armaba con los alumnos que **hoy** están activos en el grupo. Quien
cambió de grupo o se dio de baja desaparecía de las clases viejas donde había estado presente:
la clase decía «3 presentes» y no se veía quiénes. Y había un problema más, encontrado al leer
el código: corregir esa lista vieja **rechazaba** al que cambió de grupo («El alumno indicado no
pertenece al grupo de la clase»), porque el guardado también miraba el grupo de hoy.

## Qué cambió

| Dónde | Cambio |
|---|---|
| `ClaseWebController::show` | La lista es la del grupo de hoy (activos) **más** quien ya tiene una marca guardada en esa clase, presente o ausente. A esos se les anota «Cambió de grupo» o «De baja». |
| `ClaseWebController::storeAsistencias` | Acepta también a quien ya tiene marca en la clase. A quien no es del grupo ni tiene marca se lo sigue rechazando. |
| `clases/show` | El encabezado dice «N alumnos en el grupo · M ya no están · P presentes» (M solo si hay). En la tarjeta, junto al DNI, «· Cambió de grupo» o «· De baja». Sin JavaScript nuevo. |
| Contrato Clases y asistencias V1 | Enmienda 20/10/2026 (T52, DU-09). |
| Tabla de pruebas | ALU-013 g y ASI-009 d y e reescritos con lo decidido (tabla viva y copia del repo). |

**Hoy** cada uno aparece solo en la clase de su grupo, y el dado de baja en ninguna: la regla
solo agrega a quien ya tiene marca, y en una clase de hoy recién tomada nadie la tiene todavía.

**Lo que no se cambió y queda para Carlos:** quien **entró** al grupo después de una clase sigue
apareciendo en esa clase vieja (y se lo puede marcar), como antes. «Los cambios son hacia
adelante» podría querer decir que tampoco aparezca ahí; no se decidió y no lo toqué.

> **Resuelto el 20/10 — ver «Segunda parte» al final.** Carlos: «Los cambios son siempre para adelante».
> El visual de esta primera parte lo aprobó Carlos el 20/10: «Las vistas están OK».

## Pruebas

`tests/Feature/ClaseListaHaciaAdelanteT52Test.php`, 5 pruebas (30 aserciones), con una clase de
la semana pasada con tres presentes y una ausente, y después un cambio de grupo, una baja y la
ausente también cambiada de grupo:
1. La clase vieja muestra a los cuatro, con «Cambió de grupo» / «De baja», y conserva 3 presentes.
2. Hoy: el grupo viejo muestra solo a quien sigue; el nuevo, a los dos que llegaron; la baja, en ninguno.
3. Corregir la lista vieja (con motivo) acepta a los que ya no están y no pierde marcas: 4 presentes.
4. Quien no es del grupo ni tiene marca sigue rechazado; tampoco se puede cargar hoy en el grupo viejo al que se cambió.
5. La profesora ve la misma lista vieja.

Suite rápida en `wings_testing_claude` (20/10): **835 aprobadas, 4 omitidas, 2 fallidas**, 273 s.
Las dos fallidas no son de T52: `SaldoInicialTipoCajaTest` (pantalla de Cashflow, que Codex está
cambiando en T61 sin commitear; no toqué Cashflow) y `DocumentacionNoMienteTest`, que contó
también las pruebas de T61 todavía sin subir. El contador queda declarado con lo versionado.

## Navegador

Chrome real sobre `wings_testing_claude`, operativa sintética, mismos datos que la prueba
(`evidencia-t52-claude/preparar-local.php`, `recorrer-local.mjs`, `resultado-navegador.json`):

| Captura | Qué muestra |
|---|---|
| `01-clase-vieja-1366.png` | «1 alumnos en el grupo · 3 ya no están · 3 presentes»; las cuatro tarjetas, con «Cambió de grupo» y «De baja». |
| `02-clase-vieja-375.png` | Lo mismo en celular, sin desborde. |
| `03-clase-vieja-corregida-1366.png` | Con clics: se tilda a la ausente, se escribe el motivo, Guardar → «✓ Asistencias guardadas.»; al recargar, 4 presentes. |

Consola del navegador sin errores ni avisos.

## Qué no se verificó

- No se probó en el servidor ni con datos reales.
- Liquidaciones y reportes no cambian (cuentan desde `asistencias`, no desde la lista); no se
  recorrieron de nuevo.

## Segunda parte — quien entró después no aparece (Claude CyE, 20/10/2026)

Carlos: «Los cambios son siempre para adelante» («si yo nací en 1980, ¿puedo haber sido parte de
la revolución de mayo?»).

**Por qué no alcanzaba con los planes:** con el mes ya pagado, el cambio de grupo es inmediato pero
el plan nuevo empieza el mes siguiente (`CambioPlanService::seDifiere`). Con el plan, el alumno
habría aparecido el resto del mes en el grupo viejo y no en el nuevo.

| Dónde | Cambio |
|---|---|
| Migración `2026_10_20_150000_add_grupo_desde_to_alumnos` | Columna `alumnos.grupo_desde`. Para los existentes: el primer plan de su grupo actual, nunca antes del ingreso ni después de hoy. |
| `Alumno` (modelo) | Al crear: `grupo_desde` = fecha de ingreso. Al cambiar de grupo: hoy. Si se corrige el ingreso de quien nunca cambió de grupo, lo acompaña. Cubre alta, edición y carga desde Excel sin tocar cada uno. Alcance `enGrupoElDia(grupo, fecha)`. |
| `ClaseWebController::show` y `storeAsistencias` | Mismo criterio: estaban en el grupo ese día (`enGrupoElDia`), más quien tiene marca en la clase. |

Sin cambios de vista ni de aspecto: la pantalla aprobada sigue igual; solo dejan de aparecer
quienes entraron después.

Base de prueba (`wingstest`, solo lectura, 20/10): los 100 alumnos activos tienen plan vigente de su
grupo y ningún plan empieza después de la primera clase de su grupo.

**Pruebas nuevas** en `ClaseListaHaciaAdelanteT52Test` (7 en total): quien entró después —por alta
posterior o por cambio de grupo hoy— no aparece en la clase vieja, sí en la de hoy, y un pedido
armado a mano para marcarlo se rechaza sin guardar nada; `grupo_desde` se completa solo en alta,
cambio de grupo y corrección del ingreso.

