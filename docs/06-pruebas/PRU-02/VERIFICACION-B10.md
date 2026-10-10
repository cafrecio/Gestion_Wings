# B10 — Verificación independiente · DEVUELTO a Claude

10/10/2026 · Codex CyE. Verificado sobre `63ed840d11d572c842ee3c583ad2e56cfe1b12c1` (main), después de `git pull --ff-only`. Incluye B10 del autor `438ee55` y el ajuste de etiquetas `580f380`.

Base exclusiva `wings_testing_codex`, migración B10 aplicada; archivos de sesión, vistas compiladas y PDF aislados en `storage/app/verificacion-b10-runtime`. Chrome real sobre Laravel en `http://127.0.0.1:8010`. Todos los datos de estos programas son ficticios. Sin cambios de aplicación, vistas, CSS o tests; sin despliegue.

**Dictamen:** devolver por dos fallas: acepta y transforma alias con espacios/tabulación/salto de línea; el rechazo de 200 caracteres aparece en inglés. Cuatro casos fallidos de 73 comprobaciones, correspondientes a esas dos causas. Pago, acceso, Excel y siete seeders comprobados. Suite completa: 581 aprobadas, 2 omitidas, 4650 aserciones.

[Pedidos, filas y resultados](evidencia/verificacion-b10/resultado.json) · [Programa navegador](evidencia/verificacion-b10/verificar.cjs) · [Escenarios aislados](evidencia/verificacion-b10/escenarios.php) · [Controles generales](evidencia/verificacion-b10/controles.json).

## 1. Profesor

| Caso | Enviado | HTTP | Antes | CBU/alias guardado | Resultado |
|---|---|---|---|---|---|
| alta-alias | "prof.ficticio.b10" | 302 | sin fila | "prof.ficticio.b10" | APROBADO |
| alta-cbu | "0170099220000067797912" | 302 | sin fila | "0170099220000067797912" | APROBADO |
| alta-cbu-espacios | " 0170 0992 2000 0067 7979 12 " | 302 | sin fila | "0170099220000067797912" | APROBADO |
| alta-sin-dato | "" | 302 | sin fila | null | APROBADO |
| editar-borrar | "" | 302 | "prof.ficticio.b10" | null | APROBADO |
| editar-cambiar | "nuevo.alias.b10" | 302 | null | "nuevo.alias.b10" | APROBADO |


## 2. Validación

| Caso | Enviado | HTTP | BD después | Otros campos | Mensaje/acción | Resultado |
|---|---|---|---|---|---|---|
| alias-5 | "abcde" | 302 | sin fila nueva | 10 campos conservados | Castellano | APROBADO |
| alias-21 | "aaaaaaaaaaaaaaaaaaaaa" | 302 | sin fila nueva | 10 campos conservados | Castellano | APROBADO |
| numeros-21 | "111111111111111111111" | 302 | sin fila nueva | 10 campos conservados | Castellano | APROBADO |
| numeros-23 | "11111111111111111111111" | 302 | sin fila nueva | 10 campos conservados | Castellano | APROBADO |
| 22-con-letra | "1111111111a11111111111" | 302 | sin fila nueva | 10 campos conservados | Castellano | APROBADO |
| alias-espacio | "alias con espacio" | 302 | "aliasconespacio" | No hubo vuelta al formulario | Alta aceptada | FALLA |
| alias-enie | "alias.ñuevo" | 302 | sin fila nueva | 10 campos conservados | Castellano | APROBADO |
| alias-arroba | "alias@prueba" | 302 | sin fila nueva | 10 campos conservados | Castellano | APROBADO |
| alias-guion-bajo | "alias_prueba" | 302 | sin fila nueva | 10 campos conservados | Castellano | APROBADO |
| numerico-corto | "12345678" | 302 | sin fila nueva | 10 campos conservados | Castellano | APROBADO |
| texto-200 | "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa" | 302 | sin fila nueva | 10 campos conservados | CBU o alias  The cbu alias field must not be greater than 60 characters. | FALLA |
| script | "&lt;script&gt;window.__b10xss=true&lt;/script&gt;" | 302 | sin fila nueva | 10 campos conservados | Castellano | APROBADO |
| alias-tab | "alias\tprueba" | 302 | "aliasprueba" | No hubo vuelta al formulario | Alta aceptada | FALLA |
| alias-salto | "alias\nprueba" | 302 | "aliasprueba" | No hubo vuelta al formulario | Alta aceptada | FALLA |
| alias-6 | "abc123" | 302 | "abc123" | Alta válida | Alta aceptada | APROBADO |
| alias-20 | "ABCDEFGHIJKLMNOPQRST" | 302 | "ABCDEFGHIJKLMNOPQRST" | Alta válida | Alta aceptada | APROBADO |
| mayusculas | "PROFESOR.B10" | 302 | "PROFESOR.B10" | Alta válida | Alta aceptada | APROBADO |
| guiones | "profesor-b10" | 302 | "profesor-b10" | Alta válida | Alta aceptada | APROBADO |


Los rechazos conservan nombre, apellido, DNI, nacimiento, dirección, localidad, teléfono, email, deporte y CBU/alias. El `<script>` se rechazó y quedó como valor escapado, sin ejecutarse.

**Falla 1:** `CbuOAlias::normalizar()` elimina `\s+` también de los alias. `alias con espacio` se guarda como `aliasconespacio`; tabulación y salto como `aliasprueba`. La validación opera después de esa transformación. [Captura real](evidencia/verificacion-b10/aceptado-indebido-alias-espacio.png).

**Falla 2:** `ProfesorWebController::validationRules()` aplica `max:60` y sus mensajes no traducen `cbu_alias.max`: el rechazo de 200 caracteres dice `The cbu alias field must not be greater than 60 characters.` La fila no se crea y los demás campos se conservan. [Captura real](evidencia/verificacion-b10/rechazo-texto-200.png).

**Observación para Carlos:** `12345678` se rechaza como exige esta orden, pero el BCRA describe 6–20 caracteres y admite números sin exigir una letra. Infiero de esa gramática que un alias enteramente numérico cumple el formato; no comprobé una cuenta bancaria con ese alias. La regla agrega `!ctype_digit`, excluyéndolo. Requiere una decisión de criterio; no modifiqué la regla. [Norma BCRA, §3.7.2.1, página 3 de la sección / página 9 del PDF](https://www.bcra.gob.ar/Pdfs/Texord/t-snp-spd.pdf).

## 3. Liquidación y pago

| Caso | Comprobación | Resultado |
|---|---|---|
| abierta | ABIERTA/PENDIENTE, $7.500. Sin dato bancario ni formulario de pago. | APROBADO |
| cerrada-con-dato | CERRADA/PENDIENTE. Alias presente antes del formulario de pago. | APROBADO |
| cerrada-sin-dato | Aviso de dato faltante; clic Editar abrió /profesores/1/edit; BD: NULL. | APROBADO |
| convivencia-con-580f380 | Fixture COMISION cerrada: Monto ajustado, ayuda y cinco labels con SVG, junto al alias. No se recalculó esta comisión. | APROBADO |
| pagada | Generar → cerrar → pagar por aplicación. CERRADA/PAGADA, $7.500, exactamente un cashflow −$7.500; alias y formulario de pago ausentes. | APROBADO |


[Cerrada, dato encima del pago](evidencia/verificacion-b10/liquidacion-cerrada-dato.png) · [Convivencia con Monto ajustado](evidencia/verificacion-b10/convivencia-monto-ajustado-banco.png) · [Pagada](evidencia/verificacion-b10/liquidacion-pagada.png). La coexistencia se comprobó en comisión; el pago completo fue de una liquidación por hora, en efectivo.

## 4. Operativo y roles

| Caso | Comprobación | Resultado |
|---|---|---|
| alta-OPERATIVO | POST/BD: rol OPERATIVO; cbu_alias="usuario.ficticio.b10"; usuario activo=True | APROBADO |
| alta-ADMIN | POST/BD: rol ADMIN; cbu_alias=null; usuario activo=True | APROBADO |
| alta-PROFESOR | POST/BD: rol PROFESOR; cbu_alias=null; usuario activo=True | APROBADO |
| cambio-rol-a-ADMIN | POST/BD: rol ADMIN; cbu_alias=null; usuario activo=True | APROBADO |
| cambio-rol-a-OPERATIVO | POST/BD: rol OPERATIVO; cbu_alias=null; usuario activo=True | APROBADO |
| panel-en-vivo-ADMIN | Visibilidad comprobada en navegador: oculto | APROBADO |
| panel-en-vivo-OPERATIVO | Visibilidad comprobada en navegador: visible | APROBADO |
| panel-en-vivo-PROFESOR | Visibilidad comprobada en navegador: oculto | APROBADO |
| panel-en-vivo-OPERATIVO | Visibilidad comprobada en navegador: visible | APROBADO |
| panel-en-vivo-ADMIN | Visibilidad comprobada en navegador: oculto | APROBADO |
| abrir-operativo-existente | Visibilidad comprobada en navegador: visible | APROBADO |
| admin-edita-su-cuenta | POST/BD: rol ADMIN; cbu_alias=null; usuario activo=True | APROBADO |


## 5. Privacidad

| Caso | Comprobación | Resultado |
|---|---|---|
| recibo-admin | HTTP 200; PDF descargado por ADMIN | APROBADO |
| datos-cargados-para-privacidad | Filas concretas profesor 1 y operativo 2 con ambos valores exactos; clase/asistencia/caja/movimiento ficticios vinculados. | APROBADO |
| OPERATIVO restringido /profesores/1 | HTTP 403; ningún dato bancario en respuesta | APROBADO |
| OPERATIVO restringido /profesores/1/edit | HTTP 403; ningún dato bancario en respuesta | APROBADO |
| OPERATIVO restringido /profesores | HTTP 403; ningún dato bancario en respuesta | APROBADO |
| OPERATIVO restringido /usuarios | HTTP 403; ningún dato bancario en respuesta | APROBADO |
| OPERATIVO restringido /usuarios/2/edit | HTTP 403; ningún dato bancario en respuesta | APROBADO |
| OPERATIVO restringido /liquidaciones/1 | HTTP 403; ningún dato bancario en respuesta | APROBADO |
| OPERATIVO restringido /recibos/liquidacion/1 | HTTP 403; ningún dato bancario en respuesta | APROBADO |
| OPERATIVO HTML accesible | 23 pantallas HTML 200; ningún valor exacto encontrado. Lista completa enlazada abajo. | APROBADO |
| PROFESOR restringido /profesores/1 | HTTP 403; ningún dato bancario en respuesta | APROBADO |
| PROFESOR restringido /profesores/1/edit | HTTP 403; ningún dato bancario en respuesta | APROBADO |
| PROFESOR restringido /profesores | HTTP 403; ningún dato bancario en respuesta | APROBADO |
| PROFESOR restringido /usuarios | HTTP 403; ningún dato bancario en respuesta | APROBADO |
| PROFESOR restringido /usuarios/2/edit | HTTP 403; ningún dato bancario en respuesta | APROBADO |
| PROFESOR restringido /liquidaciones/1 | HTTP 403; ningún dato bancario en respuesta | APROBADO |
| PROFESOR restringido /recibos/liquidacion/1 | HTTP 403; ningún dato bancario en respuesta | APROBADO |
| PROFESOR HTML accesible | 2 pantallas HTML 200; ningún valor exacto encontrado. Lista completa enlazada abajo. | APROBADO |
| JSON ADMIN /alumnos/autocomplete?q=FICTICIO | HTTP 200; ningún dato bancario en respuesta | APROBADO |
| JSON ADMIN /grupos/check-disponible?deporte_id=1&amp;nivel_id=1 | HTTP 200; ningún dato bancario en respuesta | APROBADO |
| JSON ADMIN /usuarios/check-email?email=operativo%40b10.ficticio.test | HTTP 200; ningún dato bancario en respuesta | APROBADO |
| JSON ADMIN /alumnos/inscripcion-preview?dni=99101099&amp;fecha_alta=2026-10-10 | HTTP 200; ningún dato bancario en respuesta | APROBADO |
| JSON OPERATIVO /alumnos/autocomplete?q=FICTICIO | HTTP 200; ningún dato bancario en respuesta | APROBADO |
| JSON OPERATIVO /grupos/check-disponible?deporte_id=1&amp;nivel_id=1 | HTTP 200; ningún dato bancario en respuesta | APROBADO |
| JSON OPERATIVO /usuarios/check-email?email=operativo%40b10.ficticio.test | HTTP 403; ningún dato bancario en respuesta | APROBADO |
| JSON OPERATIVO /alumnos/inscripcion-preview?dni=99101099&amp;fecha_alta=2026-10-10 | HTTP 200; ningún dato bancario en respuesta | APROBADO |
| JSON PROFESOR /alumnos/autocomplete?q=FICTICIO | HTTP 403; ningún dato bancario en respuesta | APROBADO |
| JSON PROFESOR /grupos/check-disponible?deporte_id=1&amp;nivel_id=1 | HTTP 403; ningún dato bancario en respuesta | APROBADO |
| JSON PROFESOR /usuarios/check-email?email=operativo%40b10.ficticio.test | HTTP 403; ningún dato bancario en respuesta | APROBADO |
| JSON PROFESOR /alumnos/inscripcion-preview?dni=99101099&amp;fecha_alta=2026-10-10 | HTTP 403; ningún dato bancario en respuesta | APROBADO |
| JSON profesores de clase | HTTP 200; ningún dato bancario en respuesta | APROBADO |


[150 direcciones, códigos, redirecciones y JSON](evidencia/verificacion-b10/privacidad.md). Recorrido de todos los GET web inventariados, sustituyendo parámetros por los IDs ficticios. OPERATIVO: 23 páginas HTML200; PROFESOR: 2. Las siete direcciones sensibles por rol devolvieron403. Los 302 llevan a destinos comprobados; recibo de cuota inexistente devolvió404.

| Caso adicional | Comprobación | Resultado |
|---|---|---|
| Contenido del PDF real | Dos páginas leídas con pypdf: sin CBU/CVU/alias ni valores exactos; total $7.500 en ambas; beneficiario ficticio presente. | APROBADO |
| Destinatario y acceso del recibo | Descarga para ADMIN200; OPERATIVO/PROFESOR403. Documento interno de haberes del profesor. El circuito leído genera/guarda después del commit; no envía email automáticamente. | COMPROBADO EN ESTE CIRCUITO |


[PDF descargado](evidencia/verificacion-b10/recibo-liquidacion.pdf) · [Lectura y huella](evidencia/verificacion-b10/pdf.json). La entrega física o el envío manual al profesor depende del admin y no se realizó.

Fuente leída: `AlumnoWebController::autocomplete` selecciona y mapea campos (no modelos completos); `ClaseWebController::actualizarProfesores` devuelve nombres como string; validaciones en vivo de usuarios/grupos devuelven disponibilidad; el cobro de Caja devuelve confirmaciones y valores de deuda. `ReciboService::generarReciboLiquidacion` arma explícitamente nombre/deporte/tipo/modalidad y excluye CBU. Las rutas de profesores, usuarios y recibos de liquidación tienen control ADMIN. `bootstrap/app.php` mantiene deshabilitada la API; serializaciones de controladores API sin ruta activa no prueban exposición actual. Se leyó `PERMISOS-ROLES.md` antes de los controles.

## 6. Compatibilidad

| Caso | Comprobación | Resultado |
|---|---|---|
| primera-carga-excel | Excel generado con plantilla real; revisar/cargar por HTTP302. P1 TERMINADA; alumno ficticio DNI99101098 creado. Datos bancarios existentes conservados en BD. | APROBADO |


| Caso | Comprobación | Resultado |
|---|---|---|
| PrimeraCargaCompletaSeeder | Ejecutado en base recién migrada con catálogos; filas concretas guardadas, cbu_alias NULL. | APROBADO |
| UserSeeder | Ejecutado en base recién migrada con catálogos; filas concretas guardadas, cbu_alias NULL. | APROBADO |
| TestSeeder | Ejecutado en base recién migrada con catálogos; filas concretas guardadas, cbu_alias NULL. | APROBADO |
| ReportesEscenarioSeeder | Ejecutado en base recién migrada con catálogos; filas concretas guardadas, cbu_alias NULL. | APROBADO |
| ReportesAsistenciaEscenarioSeeder | Ejecutado en base recién migrada con catálogos; filas concretas guardadas, cbu_alias NULL. | APROBADO |
| ReportesSueldosEscenarioSeeder | Ejecutado en base recién migrada con catálogos; filas concretas guardadas, cbu_alias NULL. | APROBADO |
| DemoSeeder | Ejecutado en base recién migrada con catálogos; filas concretas guardadas, cbu_alias NULL. | APROBADO |
| Build | npm run build; salida0, 44,01s. | APROBADO |
| Suite completa | 581 aprobadas / 2 omitidas; 4650 aserciones, 700,07s; wings_testing_codex. | APROBADO |


[Filas y resultados de seeders](evidencia/verificacion-b10/seeders.json). Asistencia requiere el escenario financiero antes; Sueldos lo crea internamente; UserSeeder requiere los profesores1/2, preparados con PrimeraCargaCompletaSeeder. Los intentos previos sin respetar esos prerrequisitos fallaron (escenarios con alumnos previos; UserSeeder sin profesores). No fueron fallas de B10. Control final: cada escenario con sus prerrequisitos y base nueva. Demo crea profesores, no usuarios en estas filas. Sin volcar contraseñas ni hashes.

## No verificado

- No se revalidó el aspecto ni se ejecutó el navegador en celular. Carlos aprobó el aspecto; esta orden lo excluye del juicio. Capturas solo prueban funcionamiento.
- Existencia, titularidad y dígitos verificadores de una cuenta real; transferencias bancarias reales.
- Envío físico/manual del recibo al profesor; correo de terceros y producción. No se desplegó ni se consultó la base del club.
- Todos los estados/filtros posibles de cada página; la privacidad se comprobó con los registros ficticios descritos y la lista explícita de URLs. El PDF de cuota de un pago válido no forma parte del comprobante de liquidación analizado.
- Pago completo de comisión y nueva modificación del monto ajustado: se verificó la coexistencia en pantalla; el pago de punta a punta fue por hora.

## Devolución

B10 vuelve a Claude: rechazar blancos internos en alias sin transformar el identificador; conservar limpieza de espacios para CBU; traducir el rechazo de longitud. No se propone implementación ni se toca el aspecto aprobado. El criterio para alias numéricos queda como observación para Carlos. Tras la corrección deberá verificarse el nuevo commit.

---

## 7. Segunda vuelta — Gemini (commit a33cd44)

Verificación realizada el 10/10/2026 sobre la rama `main` (commit base `2d4936b` que incluye `a33cd44`).
Base de datos: `wings_testing_gemini`.
Suite de comprobación específica: `docs/06-pruebas/PRU-02/evidencia/verificacion-b10/segunda-vuelta/SegundaVueltaB10RunnerTest.php` (19 pruebas, 114 aserciones, 100% aprobado).
Evidencia estructurada: `docs/06-pruebas/PRU-02/evidencia/verificacion-b10/segunda-vuelta/resultado.json`.

### Tabla de casos verificados

| Grupo | Caso | Entidad | Qué se mandó | Qué respondió | Qué quedó en la base | Resultado |
|---|---|---|---|---|---|---|
| A | Alias con espacio interno | Profesor | `alias con espacio` | HTTP 302 a edit; error en sesión: *"Tiene que ser un CBU o CVU de 22 números, o un alias de 6 a 20 letras, números, puntos o guiones."*; old input conservado | Sin cambios (mantiene valor anterior) | APROBADO |
| A | Alias con espacio interno | Operativo | `alias con espacio` | HTTP 302 a edit; error en sesión: *"Tiene que ser un CBU o CVU de 22 números, o un alias de 6 a 20 letras, números, puntos o guiones."*; old input conservado | Sin cambios (mantiene valor anterior) | APROBADO |
| A | Alias con tabulación interna | Profesor | `alias\tcon\ttab` | HTTP 302 a edit; error en sesión: *"Tiene que ser un CBU o CVU de 22 números, o un alias de 6 a 20 letras, números, puntos o guiones."*; old input conservado | Sin cambios | APROBADO |
| A | Alias con tabulación interna | Operativo | `alias\tcon\ttab` | HTTP 302 a edit; error en sesión: *"Tiene que ser un CBU o CVU de 22 números, o un alias de 6 a 20 letras, números, puntos o guiones."*; old input conservado | Sin cambios | APROBADO |
| A | Alias con salto de línea interno | Profesor | `alias\ncon\nsalto` | HTTP 302 a edit; error en sesión: *"Tiene que ser un CBU o CVU de 22 números, o un alias de 6 a 20 letras, números, puntos o guiones."*; old input conservado | Sin cambios | APROBADO |
| A | Alias con salto de línea interno | Operativo | `alias\ncon\nsalto` | HTTP 302 a edit; error en sesión: *"Tiene que ser un CBU o CVU de 22 números, o un alias de 6 a 20 letras, números, puntos o guiones."*; old input conservado | Sin cambios | APROBADO |
| A | Texto de 200 caracteres | Profesor | `str_repeat('a', 200)` | HTTP 302 a edit; error en sesión: *"Tiene que ser un CBU o CVU de 22 números, o un alias de 6 a 20 letras, números, puntos o guiones."* (en castellano); old input conservado | Sin cambios | APROBADO |
| A | Texto de 200 caracteres | Operativo | `str_repeat('a', 200)` | HTTP 302 a edit; error en sesión: *"Tiene que ser un CBU o CVU de 22 números, o un alias de 6 a 20 letras, números, puntos o guiones."* (en castellano); old input conservado | Sin cambios | APROBADO |
| B | CBU pegado con espacios internos | Profesor | ` 0170 0992 2000 0067 7979 12 ` | HTTP 302 a index con éxito | Guardado como `0170099220000067797912` (22 números sin espacios) | APROBADO |
| B | CBU con tabulaciones entre números | Profesor | `0170\t0992\t2000\t0067\t7979\t12` | HTTP 302 a index con éxito | Guardado como `0170099220000067797912` (se limpian las tabulaciones y espacios) | APROBADO |
| B | Alias con espacios de borde | Profesor | ` mi.alias ` | HTTP 302 a index con éxito | Guardado como `mi.alias` (trim en bordes sin tocar interior) | APROBADO |
| B | Alias válido | Profesor | `juan.perez.wings` | HTTP 302 a index con éxito | Guardado exactamente como `juan.perez.wings` | APROBADO |
| B | CBU válido (22 dígitos) | Operativo | `0170099220000067797912` | HTTP 302 a index con éxito | Guardado como `0170099220000067797912` | APROBADO |
| B | Campo vacío | Profesor | `""` (string vacío) | HTTP 302 a index con éxito | Guardado como `NULL` en base de datos | APROBADO |
| C | Arreglo enviado por POST | Profesor | `cbu_alias[] = 'inyeccion'` | HTTP 302 a edit; validación rechaza con mensaje estándar en castellano; sin error 500 (TypeError) | Sin cambios en base de datos | APROBADO |
| C | Mandar solo espacios en blanco | Operativo | `    ` (4 espacios) | HTTP 302 a index con éxito | Guardado como `NULL` en base de datos | APROBADO |
| D | Preservación de mayúsculas/minúsculas | Profesor | `Mi.Alias.Banco` | HTTP 302 a index con éxito | Guardado como `Mi.Alias.Banco` (casing exacto respetado) | APROBADO |
| D | Alias totalmente numérico (ej. 8 dígitos) | Profesor | `12345678` | HTTP 302 a edit; error en sesión: *"Tiene que ser un CBU o CVU de 22 números, o un alias de 6 a 20 letras, números, puntos o guiones."* | Rechazado (evita confusión con DNI o cuenta bancaria) | APROBADO |
| D | Cambio de rol OPERATIVO a ADMIN limpia CBU | Usuario | Cambio de rol a `ADMIN` | HTTP 302 a index con éxito | `cbu_alias` pasa a `NULL` automáticamente | APROBADO |

### Pruebas automatizadas y suite completa
- `tests/Feature/CbuAliasB10Test.php`: 8 tests, 43 aserciones -> **PASS**.
- `docs/06-pruebas/PRU-02/evidencia/verificacion-b10/segunda-vuelta/SegundaVueltaB10RunnerTest.php`: 19 tests, 114 aserciones -> **PASS**.
- Suite completa en `wings_testing_gemini` (`php artisan test`): **581 passed, 2 skipped, 4660 assertions** -> **PASS**.

### Conclusión de la segunda vuelta
Las dos fallas señaladas por Codex quedaron completamente resueltas por Claude:
1. Ningún alias con espacios, tabulaciones o saltos de línea internos es aceptado ni deformado; todos se rechazan con mensaje en castellano claro.
2. Los textos largos (ej. 200 caracteres) se rechazan de inmediato con el mensaje en castellano del validador `CbuOAlias`.
3. Se conservan todas las funcionalidades previas (limpieza de CBU con espacios/tabs, trimming de alias, soporte de NULL, preservación de casing).
4. **B10 queda verificado y aprobado para cierre.**

