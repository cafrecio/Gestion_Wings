# Informe de Verificación Independiente — Defectos A15 y A16 (Programación de Clases)

## 1. Identificación y Alcance
- **Defectos verificados:**
  - **A15:** Una clase de 17:30 a 18:30 se acepta sin decir nada (Aviso previo de bloques de reloj ocupados y confirmación obligatoria).
  - **A16:** Cargar el horario obliga a repetir la carga (Programación recurrente con horarios diferenciados por día).
- **Implementó:** Codex CyE (commit [`05dd962`](https://github.com/cafrecio/Gestion_Wings/commit/05dd962)).
- **Commit verificado por Gemini:** `e77feaf669e38739adb7455f89cf197dd4fcd99c` (main al día, sin cambios funcionales posteriores sobre clases ni programación).
- **Verificó:** Gemini CyE (07/10/2026), bajo reglas estrictas de [AGENTS.md](../../../AGENTS.md) §6a ("lo que hace uno, lo controla otro") y §6-bis (ejecución aislada en base propia).
- **Base de datos utilizada:** `wings_testing_gemini` (base MariaDB local descartable, migrada de cero para la prueba; cero uso de la base de producción o el servidor).
- **Metodología de ejecución y captura:**
  - La verificación funcional y de datos se ejecutó de punta a punta con el reproductor [`docs/06-pruebas/PRU-02/evidencia/verificacion-a15-a16/VerificacionA15A16Test.php`](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-02/evidencia/verificacion-a15-a16/VerificacionA15A16Test.php), el cual corre fuera de `tests/` para no alterar el recuento de la suite compartida.
  - El reproductor realizó peticiones HTTP reales a la aplicación Laravel, verificó el estado exacto de tablas (`clases`, `clase_profesor`, `asistencias`) y sesiones, y guardó las respuestas HTML completas emitidas por Laravel en [`docs/06-pruebas/PRU-02/evidencia/verificacion-a15-a16/paginas/`](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-02/evidencia/verificacion-a15-a16/paginas/).
  - Las capturas visuales fueron tomadas con Chrome Headless (`chrome.exe --headless=new`) mediante el script [`capturar.mjs`](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-02/evidencia/verificacion-a15-a16/capturar.mjs), el cual levanta un servidor HTTP local para servir las páginas renderizadas junto con los assets de Vite (`public/build`), y renderiza el viewport de escritorio (1280×900) y de celular dentro del marco iframe de 375 px ([`marco-375.html`](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-02/capturas-cashflow/marco-375.html)), asegurando que no existan distorsiones artificiales de ventana.
  - Total de aserciones del recorrido de verificación: **1 test, 431 aserciones pasadas en verde, 0 fallos, 0 errores**.
- **Dictamen:**
  - **A15:** **APROBADO**
  - **A16:** **APROBADO**
  (Pasan a Claude para contrastar el informe contra el repositorio y proceder al cierre).

---

## 2. Recorrido Detallado: Defecto A15 (Aviso previo y confirmación)

| Paso | Acción realizada | Datos cargados en la solicitud | Qué mostró la pantalla (Laravel / Blade) | Qué quedó en la base de datos | Captura asociada | ¿Aprobado? |
|:---:|---|---|---|---|---|:---:|
| **1** | Cargar horario con fracción de hora y tocar Guardar | Tipo: `unica`<br>Grupo: Patín Principiantes (`grupo_id=1`)<br>Profesor: Lucía Gaitán (`profesor_id=1`)<br>Fecha: `2026-10-15`<br>Horario: `17:30` a `18:30` | Redirige a `/clases/create`. Muestra cartel de advertencia: *"El alquiler se cuenta por bloques del reloj. 17:30–18:30: dura 1 hora, pero ocupa 2 bloques de alquiler: 17:00–18:00, 18:00–19:00."*. Se conservan intactos los inputs (17:30, 18:30, 2026-10-15, grupo 1) y aparece el botón **Confirmar** con campo oculto `confirmar_cancha`. | **0 clases creadas** en tabla `clases`.<br>**0 asignaciones** en `clase_profesor`. Base totalmente intacta. | `02-aviso-cancha-1730-1830-desktop.png`<br>`02-aviso-cancha-1730-1830-375.png` | **SÍ** |
| **2** | Confirmar carga con firma válida | Mismos datos del Paso 1 + `confirmar_cancha` con hash HMAC SHA256 emitido por el sistema | Redirige a `/clases` (HTTP 302). Flash verde de éxito: *"1 clase(s) creada(s)."*. El listado de clases muestra la fila creada con horario 17:30 a 18:30 y profesora Lucía Gaitán. | **Exactamente 1 clase creada** (`id=1`, `fecha=2026-10-15`, `hora_inicio=17:30`, `hora_fin=18:30`, `grupo_id=1`).<br>**1 asignación** en `clase_profesor` (`profesor_id=1`). | `03-clase-creada-index-desktop.png`<br>`03-clase-creada-index-375.png` | **SÍ** |
| **3** | Modificar cada dato tras el aviso e intentar confirmar con firma vieja | Se genera aviso y luego se reenvía la firma vieja alterando:<br>a) `hora_fin` (18:30 → 19:30)<br>b) `hora_inicio` (17:30 → 16:30)<br>c) `fecha` (2026-10-15 → 2026-10-16)<br>d) `grupo_id` (Grupo 1 → Grupo 2)<br>e) `profesores` (Gaitán → Salinas)<br>f) `fecha_hasta` en recurrente (2026-10-19 → 2026-10-26) | En los 6 casos individuales: el sistema invalida la firma obsoleta, no guarda ninguna clase, y vuelve a mostrar el aviso correspondiente a los nuevos datos modificados. | **0 clases creadas**. La base de datos no sufre alteraciones no confirmadas. | Verificado por aserciones de sesión y base en `VerificacionA15A16Test.php` | **SÍ** |
| **4** | Intentos de elusión de confirmación por POST directo | Enviar POST a `/clases` con datos que disparan aviso pero con:<br>a) `confirmar_cancha = "si"` (texto plano)<br>b) `confirmar_cancha = <hash inventado>`<br>c) `confirmar_cancha = <hash de otro usuario>` | HTTP 302 Redirect a `/clases/create`. Ninguna confirmación apócrifa es aceptada. La sesión vuelve a emitir `aviso_cancha`. | **0 clases creadas** en todos los intentos. | Verificado en respuestas HTTP reales (detalladas en Sección 4). | **SÍ** |
| **5** | Cargar horario en punto (sin fracción) | Tipo: `unica`<br>Grupo: Patín Principiantes<br>Profesor: Lucía Gaitán<br>Fecha: `2026-10-15`<br>Horario: `17:00` a `18:00` | Redirige directo a `/clases` (HTTP 302) con mensaje de éxito *"1 clase(s) creada(s)."*. **No aparece ningún aviso** en sesión ni en pantalla. | **1 clase creada** (`hora_inicio=17:00`, `hora_fin=18:00`). | Verificado en base y sesión sin clave `aviso_cancha`. | **SÍ** |
| **6** | Evaluación exhaustiva de bordes según Contrato V2 | Probar límites de inicio, fin y duración:<br>• `17:30–18:00`<br>• `17:00–18:30`<br>• `17:01–18:00`<br>• `17:00–18:01`<br>• `17:00–18:00` | **17:30–18:00:** Avisa (dura 30 min, ocupa 1 bloque: 17:00–18:00).<br>**17:00–18:30:** Avisa (dura 90 min, ocupa 2 bloques: 17:00–18:00, 18:00–19:00).<br>**17:01–18:00:** Avisa (dura 59 min, ocupa 1 bloque: 17:00–18:00).<br>**17:00–18:01:** Avisa (dura 61 min, ocupa 2 bloques: 17:00–18:00, 18:00–19:00).<br>**17:00–18:00:** Sin aviso (0 bloques de exceso). | Los 4 casos fraccionados generan aviso y 0 clases. El caso en punto guarda directo. Cumple a la perfección el contrato. | `02-aviso-cancha-1730-1830-desktop.png` (muestra diseño representativo del aviso) | **SÍ** |

---

## 3. Recorrido Detallado: Defecto A16 (Horarios por día y programación de series)

| Paso | Acción realizada | Datos cargados en la solicitud | Qué mostró la pantalla (Laravel / Blade) | Qué quedó en la base de datos | Captura asociada | ¿Aprobado? |
|:---:|---|---|---|---|---|:---:|
| **1** | Carga recurrente con 3 días y 3 horarios distintos | Tipo: `recurrente`<br>Grupo: Patín Intermedias (`grupo_id=2`)<br>Profesor: Verónica Salinas (`profesor_id=2`)<br>Período: `2026-10-12` a `2026-10-25` (2 semanas)<br>Días y horarios:<br>• Lunes: `16:00` a `17:00`<br>• Miércoles: `17:00` a `18:00`<br>• Viernes: `18:00` a `19:00` | Redirige a `/clases` con mensaje de éxito: *"6 clase(s) creada(s)."*. | **6 clases creadas** en total.<br>Todas comparten **exactamente el mismo `serie_id`** (`1680228d-61e4-4d2a-b3dc-0183d0501c38`).<br>Cada clase tiene el horario estricto de su día (Lunes 16–17, Miércoles 17–18, Viernes 18–19). | `04-form-clases-recurrentes-horarios-por-dia-desktop.png`<br>`04-form-clases-recurrentes-horarios-por-dia-375.png` | **SÍ** |
| **2** | Validación de días desmarcados y campos obligatorios | a) Día marcado (Viernes) pero dejando `hora_inicio` y `hora_fin` vacíos.<br>b) Día con `hora_fin` anterior a `hora_inicio` (Viernes 18:00 a 17:00). | Redirige con errores de validación en sesión (`$errors`):<br>a) Errores en `horarios.5.hora_inicio` y `horarios.5.hora_fin`.<br>b) Error en `horarios.5.hora_fin` (*"The horarios.5.hora fin field must be a date after..."*). | **0 clases creadas**. Validación a nivel de Request y Service completamente efectiva. | Formulario conserva estado y resalta errores en inputs. | **SÍ** |
| **3** | Conflicto de profesor en fecha intermedia (Rollback atómico) | Se crea previamente una clase el Viernes `2026-10-16` de 18:00 a 19:00 con la Prof. Verónica Salinas.<br>Luego se intenta cargar una serie para Salinas (Lunes 16–17 y Viernes 18–19 entre el 12/10 y el 25/10). | Redirige a `/clases/create` con mensaje de error en sesión: *"El profesor Verónica Salinas ya tiene asignada otra clase que se solapa en fecha 2026-10-16 entre 18:00 y 19:00."*. | **Clases antes: 1. Clases después: 1**.<br>El rollback atómico revirtió las clases de la serie que se habrían creado antes del conflicto. Cero basura persistida. | Verificado en base y sesión por test de integración. | **SÍ** |
| **4** | Mantenimiento de reglas previas (fechas pasadas, inactivos, otro deporte) | a) Carga con `fecha_desde` en el pasado.<br>b) Asignación de profesor inactivo.<br>c) Asignación de profesor de otro deporte (Hernán Quintana de Fútbol asignado a clase de Patín). | Rechazado con errores de validación en sesión:<br>a) Error en `fecha_desde`.<br>b) Error en `profesores`.<br>c) Error en `profesores` (*"El profesor seleccionado no pertenece al deporte..."*). | **0 clases creadas** en todos los casos. | Comprobado por peticiones POST en `VerificacionA15A16Test.php`. | **SÍ** |
| **5** | Reproducción del cronograma semanal completo (76 clases en 6 cargas) | Se ejecutaron las 6 cargas exactas correspondientes al archivo `relevamiento-76-clases.json` (período `2026-09-24` al `2026-10-31`, reloj fijado al `2026-09-24`):<br>1. Patín Principiantes: Lun 16–17, Mié 16–17<br>2. Patín Intermedias: Lun 17–18, Vie 16–17<br>3. Patín Avanzadas: Mar 17–18, Jue 17–18<br>4. Patín Federadas: Mar 18–19, Jue 18–19, Sáb 10–11<br>5. Fútbol Principiantes: Lun 16–17, Mié 18–19<br>6. Fútbol Avanzadas: Mar 19–20, Jue 19–20, Sáb 11–12 | Las 6 solicitudes POST devolvieron HTTP 302 Redirect a `/clases` con éxito. Listado final `/clases` muestra todas las tandas cargadas. | **Exactamente 76 clases creadas** agrupadas en **6 `serie_id` distintos**:<br>• Patín Principiantes: 10 clases (`serie_id: 5c0af74e-d124-44e3-9dbf-e53192e6d524`)<br>• Patín Intermedias: 11 clases (`serie_id: c214f2e0-cd2b-4679-85b7-344468901ccc`)<br>• Patín Avanzadas: 11 clases (`serie_id: 06055cb6-f86c-4ed8-9dda-df9ccf786ed9`)<br>• Patín Federadas: 17 clases (`serie_id: cf450210-2196-4433-b320-cc7a9310bf41`)<br>• Fútbol Principiantes: 10 clases (`serie_id: b7076ffd-1780-4eb7-b4de-e7deb21a481c`)<br>• Fútbol Avanzadas: 17 clases (`serie_id: 668d59fc-9e10-4dc7-a7cd-61f2c4e6bbed`) | `07-listado-76-clases-desktop.png`<br>`07-listado-76-clases-375.png` | **SÍ** |

---

## 4. Pruebas de Ataque y Respuestas Reales por POST Directo

Se ejecutaron requests directos por POST contra el endpoint `/clases` simulando evasiones de controles:

1. **Intento de confirmación con texto plano "si":**
   - **Request:** `POST /clases` con datos de horario 17:30–18:30 y `confirmar_cancha = "si"`.
   - **Respuesta real:** HTTP `302 Redirect` a `http://localhost/clases/create`.
   - **Sesión devuelta:** Clave `aviso_cancha` generada nuevamente; sin clave `success`.
   - **Efecto en DB:** 0 clases creadas.

2. **Intento de confirmación con hash inventado / arbitrario:**
   - **Request:** `POST /clases` con datos de horario 17:30–18:30 y `confirmar_cancha = "8f4a21...e3"`.
   - **Respuesta real:** HTTP `302 Redirect` a `http://localhost/clases/create`.
   - **Sesión devuelta:** Clave `aviso_cancha` generada nuevamente; hash inventado descartado por no coincidir con el HMAC SHA256 calculado por el backend.
   - **Efecto en DB:** 0 clases creadas.

3. **Intento de confirmación con firma generada por otro usuario:**
   - **Request:** Admin 2 genera un aviso legítimo para los mismos datos. Luego Admin 1 envía la solicitud reutilizando la firma de Admin 2.
   - **Respuesta real:** HTTP `302 Redirect` a `http://localhost/clases/create`.
   - **Sesión devuelta:** Clave `aviso_cancha` generada; la firma no verifica porque el cálculo incorpora el `user_id` del usuario autenticado en sesión.
   - **Efecto en DB:** 0 clases creadas.

4. **Intento de confirmación con datos alterados respecto al aviso:**
   - **Request:** Se obtiene la firma para 17:30–18:30 y se envía con `hora_fin = "19:30"`.
   - **Respuesta real:** HTTP `302 Redirect` a `http://localhost/clases/create`.
   - **Sesión devuelta:** Clave `aviso_cancha` renovada con los bloques del nuevo horario (17:00–18:00, 18:00–19:00, 19:00–20:00).
   - **Efecto en DB:** 0 clases creadas.

5. **Intento de creación recurrente con día sin horas:**
   - **Request:** `POST /clases` con `dias_semana = [1, 5]` y `horarios[5] = ['hora_inicio' => '', 'hora_fin' => '']`.
   - **Respuesta real:** HTTP `302 Redirect` a `http://localhost/clases/create`.
   - **Sesión devuelta:** Clave `errors` conteniendo fallos para `horarios.5.hora_inicio` y `horarios.5.hora_fin`.
   - **Efecto en DB:** 0 clases creadas.

6. **Intento de creación recurrente con fin anterior a inicio:**
   - **Request:** `POST /clases` con `horarios[5] = ['hora_inicio' => '18:00', 'hora_fin' => '17:00']`.
   - **Respuesta real:** HTTP `302 Redirect` a `http://localhost/clases/create`.
   - **Sesión devuelta:** Clave `errors` conteniendo fallo para `horarios.5.hora_fin`.
   - **Efecto en DB:** 0 clases creadas.

---

## 5. Pruebas de Regresión y Matriz de Permisos por Rol

Se comprobó que las modificaciones en `ClaseWebController` y `ProgramacionClasesService` no rompieron ninguna operación existente del módulo de clases:

### A. Operaciones existentes sobre clases creadas
1. **Tomar asistencia:**
   - En una clase creada con el nuevo formulario (Patín Principiantes), se enviaron asistencias para dos alumnos (`Camila Sosa` presente, `Mateo Benítez` ausente).
   - Resultado: HTTP `302 Redirect` a `/clases/{id}` con mensaje `success`. La base de datos persistió ambas filas en la tabla `asistencias` (`presente = 1` y `presente = 0`).
   - Evidencia visual: `06-clase-detalle-asistencia-desktop.png` y `06-clase-detalle-asistencia-375.png`.
2. **Editar clase existente:**
   - Se editó individualmente una clase existente cambiando su horario a 16:00–17:00.
   - Request: `PUT /clases/{id}`.
   - Resultado: HTTP `302 Redirect` a `/clases/{id}` con mensaje *"Clase actualizada correctamente."*. Clase actualizada en la base.
3. **Cancelar clase existente con motivo:**
   - Request: `PATCH /clases/{id}/cancelar` con `motivo_cancelacion = "Pista en mantenimiento técnico"`.
   - Resultado: HTTP `302 Redirect` con mensaje *"Clase cancelada."*. Campo `cancelada = 1` y motivo guardado en base de datos.

### B. Matriz de Roles y Permisos (PERMISOS-ROLES.md)
| Rol evaluado | Ruta / Acción | Método | Resultado HTTP esperado | Resultado HTTP obtenido | Estado |
|---|---|:---:|:---:|:---:|:---:|
| **OPERATIVO** | `/clases` (Listado general) | `GET` | 200 OK | 200 OK | Aprobado |
| **OPERATIVO** | `/clases/{id}` (Detalle de clase) | `GET` | 200 OK | 200 OK | Aprobado |
| **OPERATIVO** | `/clases/{id}/cancelar` (Cancelar con motivo) | `PATCH` | 302 Redirect (autorizado) | 302 Redirect | Aprobado |
| **OPERATIVO** | `/clases/create` (Formulario nueva clase) | `GET` | 403 Forbidden | 403 Forbidden | Aprobado |
| **OPERATIVO** | `/clases` (Crear clase única/recurrente) | `POST` | 403 Forbidden | 403 Forbidden | Aprobado |
| **OPERATIVO** | `/clases/{id}/edit` (Editar clase) | `GET` | 403 Forbidden | 403 Forbidden | Aprobado |
| **OPERATIVO** | `/clases/{id}` (Actualizar clase) | `PUT` | 403 Forbidden | 403 Forbidden | Aprobado |
| **PROFESOR** | `/clases` (Listado de clases) | `GET` | 200 OK | 200 OK | Aprobado |
| **PROFESOR** | `/clases/{id}` (Detalle de clase) | `GET` | 200 OK | 200 OK | Aprobado |
| **PROFESOR** | `/clases/{id}/asistencias` (Tomar asistencia) | `POST` | 302 Redirect (autorizado) | 302 Redirect | Aprobado |
| **PROFESOR** | `/clases/{id}/cancelar` (Cancelar clase) | `PATCH` | 403 Forbidden | 403 Forbidden | Aprobado |
| **PROFESOR** | `/clases/create` (Formulario nueva clase) | `GET` | 403 Forbidden | 403 Forbidden | Aprobado |
| **PROFESOR** | `/clases` (Crear clase) | `POST` | 403 Forbidden | 403 Forbidden | Aprobado |
| **PROFESOR** | `/clases/{id}/edit` (Editar clase) | `GET` | 403 Forbidden | 403 Forbidden | Aprobado |

---

## 6. Lo que NO se verificó (Exclusiones y Veracidad del Método)

En estricto cumplimiento de las reglas de verificación de Wings (reportar la verdad y distinguir explícitamente lo comprobado de lo inferido):

1. **No hubo sesión interactiva de navegador operada manualmente con ratón y teclado:**
   - Las páginas HTML probadas provienen de las respuestas reales emitidas por el framework Laravel ante peticiones HTTP reales generadas por el script de verificación.
   - Las capturas de pantalla fueron generadas automáticamente por Chrome Headless apuntando a un servidor web local que sirve dichas páginas HTML junto a los assets compilados de Vite y el marco de 375 px.
   - El comportamiento de eventos JavaScript en vivo en el navegador (como el marcado y desmarcado reactivo de checkboxes en el DOM) fue auditado a nivel de código (`resources/js/clases-form.js`) y mediante la validación backend de los payloads resultantes, pero no mediante un driver E2E con ratón físico (p. ej. Playwright/Dusk). La fluidez táctil queda para la revisión manual en el sitio de prueba.
2. **Dispositivos móviles físicos:** No se probó en teléfonos Android o iOS reales; se utilizó la emulación precisa en iframe a 375 px dentro de Chrome en Windows.
3. **Edición masiva de series y alquileres POS-07:** Quedan fuera de alcance según lo estipulado expresamente en el Contrato V2 y el plan de trabajo.

---

## 7. Listado Literal de Archivos de Evidencia

Copiado exactamente de la salida del sistema de archivos (`docs/06-pruebas/PRU-02/evidencia/verificacion-a15-a16`):

```text
capturas
paginas
capturar.mjs
datos-verificacion.json
VerificacionA15A16Test.php
capturas\00-login-control-375.png
capturas\00-login-control-desktop.png
capturas\01-form-clase-unica-vacio-375.png
capturas\01-form-clase-unica-vacio-desktop.png
capturas\02-aviso-cancha-1730-1830-375.png
capturas\02-aviso-cancha-1730-1830-desktop.png
capturas\03-clase-creada-index-375.png
capturas\03-clase-creada-index-desktop.png
capturas\04-form-clases-recurrentes-horarios-por-dia-375.png
capturas\04-form-clases-recurrentes-horarios-por-dia-desktop.png
capturas\05-aviso-cancha-recurrente-375.png
capturas\05-aviso-cancha-recurrente-desktop.png
capturas\06-clase-detalle-asistencia-375.png
capturas\06-clase-detalle-asistencia-desktop.png
capturas\07-listado-76-clases-375.png
capturas\07-listado-76-clases-desktop.png
paginas\00-login-control.html
paginas\01-form-clase-unica-vacio.html
paginas\02-aviso-cancha-1730-1830.html
paginas\03-clase-creada-index.html
paginas\04-form-clases-recurrentes-horarios-por-dia.html
paginas\05-aviso-cancha-recurrente.html
paginas\06-clase-detalle-asistencia.html
paginas\07-listado-76-clases.html
```

---

## 8. Dictamen Final

- **Defecto A15:** **APROBADO**.
  El mecanismo de detección de bloques de reloj fraccionados, la emisión del aviso con persistencia cero, la firma criptográfica HMAC asociada al usuario y parámetros de la carga, la invalidación ante cambios y la confirmación final funcionan de forma exacta según el Contrato V2.
- **Defecto A16:** **APROBADO**.
  La programación de clases recurrentes con horarios individuales por día de la semana permite consolidar series enteras en una sola operación (76 clases en 6 cargas), manteniendo la integridad transaccional, el identificador único `serie_id` por tanda y la validación de solapamientos con rollback.
- **Paso siguiente según protocolo:**
  Gemini **NO** cierra las tareas en el tablero. Se actualiza el tablero asignando las tareas a Claude (`tiene=Claude`) con la instrucción: *"Contrastar el informe de Gemini contra el repositorio y cerrar"*.
