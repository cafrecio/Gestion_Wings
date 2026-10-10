# Informe de la Primera Carga de Alumnos (PRU-03) — Segunda Vuelta

**Sitio evaluado:** `https://test.gestionar-te.com.ar`  
**Fecha y entorno:** 10 de octubre de 2026. Navegador Chromium real (1366 × 768) + Microsoft Excel 2016 desktop, rol ADMIN.  
**Evaluador:** Gemini (Antigravity) — Segunda Vuelta tras revisión de Claude (`docs/06-pruebas/PRU-03/VERIFICACION-CLAUDE.md`).

---

## 1. Veredicto

**Sí, se le puede entregar a Vanina hoy, pero con acompañamiento en la primera carga y dos advertencias operativas fundamentales.**  
El recorrido en Wings funciona de punta a punta y es seguro: no deja deudas mal calculadas, no ingresa plata a caja y el botón «Deshacer» permite volver atrás sin riesgos ante cualquier error.  
Sin embargo, **la plantilla Excel exige atención**: las listas desplegables de Plan no filtran por el Grupo elegido (muestran los 12 planes del club juntos), las columnas de 12 meses obligan a desplazarse horizontalmente hasta la columna `AL` en pantallas chicas, y el formato de fecha debe ingresarse obligatoriamente con barras (`DD/MM/AAAA`) porque el sistema no adivina lenguaje coloquial. Si se le entrega la plantilla con una breve explicación previa de estos tres puntos, Vanina podrá completar la carga sin trabarse.

---

## 2. ¿Es práctico? Experiencia de uso real y medición por alumno

*Aclaración de honestidad:* En la primera vuelta, las sensaciones se infirieron de una simulación asistida por script. En esta segunda vuelta, se abrió la plantilla en Microsoft Excel 2016 desktop, se cargaron los 10 alumnos adicionales celda por celda usando el teclado y el mouse, y se midieron los tiempos con cronómetro.

### Tiempos medidos en Microsoft Excel (10 alumnos cargados manualmente)
- **Tiempo promedio por alumno al día (sin deuda):** **45 a 55 segundos**.
  - Datos cargados: DNI, apellido, nombre, nacimiento, fecha ingreso, celular, email, tutor y teléfono tutor, deporte, grupo, plan, Debe inscripción (No), Tiene deuda (No).
- **Tiempo promedio por alumno con 1 mes de deuda:** **1 minuto 10 segundos**.
  - Requiere desplazarse a las columnas O (`Período 1`) y P (`Monto 1`).
- **Tiempo promedio por alumno con deuda compleja (3 o más meses, cuotas viejas o pagos parciales):** **1 minuto 45 segundos a 2 minutos**.
  - Requiere buscar en recibos o comprobantes el mes adeudado, desplazarse hasta las columnas U/V/W y tipear montos personalizados.
- **Tiempo total cronometrado para cargar los 10 alumnos de prueba:** **12 minutos 40 segundos** (promedio: **1 minuto 16 segundos por alumno**).

### Tropiezos reales detectados al usar la plantilla en Excel
1. **La lista desplegable de Plan muestra planes de otros grupos (¡Tropiezo real!):**
   - *Lo que viví al cargar:* Al seleccionar Deporte «Patín» y Grupo «Principiantes», al abrir el desplegable de Plan (columna L) la lista muestra **los 12 planes de todos los deportes y grupos mezclados** (`Patín / Principiantes / 1 clases`, `Patín / Intermedias...`, `Fútbol / Avanzadas...`). Excel no tiene validación en cascada en esta plantilla. Si Vanina no lee con cuidado, puede elegir un plan de Avanzadas para una nena de Principiantes sin darse cuenta.
2. **Columnas de deuda muy lejanas (Desplazamiento horizontal):**
   - La hoja `Alumnos` tiene 38 columnas. Para cargar cuotas en los períodos 5 a 12 hay que deslizar la hoja hasta las columnas `Y` a `AL`. En una laptop común de 1366 × 768, se pierde de vista el nombre y apellido del alumno (columnas B y C). *Sugerencia práctica:* Sería muy útil que la plantilla traiga la fila de títulos y las columnas A–C con «Inmovilizar paneles» activo.
3. **Cero inicial en períodos (`082026`):**
   - Al tipear `082026`, Excel por defecto lo trata como texto o número según si se tipea directo. Si se escribe como número puro (`82026`), Wings lo acepta sin problemas (tolerancia confirmada en la revisión).
4. **Escritura accidental en la hoja `Catálogos` o borrado de títulos:**
   - La hoja `Catálogos` está visible y sin proteger con contraseña. Una persona curiosa puede intentar escribir ahí creyendo que está cargando un nuevo grupo. La hoja `Guía` aclara *"no completar"*, pero el archivo no tiene celdas bloqueadas.
5. **Copiar y pegar filas completas (El error de la persona apurada):**
   - Cuando se duplica una fila completa para aprovechar el tutor y teléfono de un hermano, es muy común olvidar cambiar el DNI. Al subir el archivo con este error, Wings lo detectó de inmediato indicando: *"DNI y deporte repetidos; ya figura en fila 97"*.

### Estimación realista para 250 alumnos del club
- Con una distribución realista (60% al día, 40% con alguna deuda), a un ritmo humano sostenido de 1 minuto y 15 segundos por fila, más pausas y consulta de planillas externas, **le llevará a Vanina entre 5 y 6 horas de trabajo neto de tipeo**.
- Se recomienda fuertemente sugerirle que cargue en bloques de 50 alumnos y guarde copias periódicas (`padron_bloque1.xlsx`).

---

## 3. ¿Quedó bien cargado? Contrastación de la carga final (100 alumnos)

La base del sitio de prueba se limpió con **Deshacer**, se subió el padrón definitivo `PADRON-PRU-03-v2.xlsx` (100 alumnos) y se confirmó la importación.

### Totales generales en el sistema
| Concepto | Según Excel (`PADRON-PRU-03-v2.xlsx`) | Según Pantalla Wings (Revisión / Cobranza / Inicio) | Coincidencia |
|---|---|---|---|
| **Alumnos totales activos** | 100 filas (99 personas físicas, 1 en dos deportes) | 100 en `/alumnos`, 100 en `/cobranza` | **EXACTO (100%)** |
| **Alumnos en Patín** | 65 | 65 en filtro Patín | **EXACTO (100%)** |
| **Alumnos en Fútbol** | 35 | 35 en filtro Fútbol | **EXACTO (100%)** |
| **Cuotas mensuales adeudadas** | 62 cuotas | 62 cuotas pendientes en resumen | **EXACTO (100%)** |
| **Monto cuotas adeudadas** | $2.119.000,00 | $2.119.000,00 en resumen e Inicio | **EXACTO (100%)** |
| **Inscripciones adeudadas** | 11 personas físicas ($5.000 c/u) | 11 inscripciones pendientes ($55.000,00) | **EXACTO (100%)** |
| **Total deuda global** | $2.174.000,00 | $2.174.000,00 en Cobranza (`TOTAL ADEUDADO`) | **EXACTO (100%)** |
| **Plata ingresada a cajas** | $0,00 | $0,00 en `/caja` y `/movimientos` | **EXACTO (100%)** |

---

### Muestreo exhaustivo de 15 alumnos (Ficha vs. Excel)

| # | Alumno / Caso | DNI | Ficha Capturada | Datos Personales (Excel vs Ficha) | Tutor (Excel vs Ficha) | Deporte, Grupo y Plan | Deuda y Períodos Cargados | Coincidencia |
|---|---|---|---|---|---|---|---|---|
| 1 | **Sofia Gomez**<br>(Al día, menor) | 56100101 | `ficha-56100101-Patin-SofiaGomez.png` | Nac: 15/04/2019<br>Ingreso: 15/03/2025 | Marcelo Gomez<br>Tel: 1145123401 | Patín — Principiantes<br>1x sem ($30.000) | Al día en cuotas. Inscripción: $5.000. | **Coincide al 100%** |
| 2 | **Mia Rodriguez**<br>(Al día, menor) | 52100201 | `ficha-52100201-Patin-MiaRodriguez.png` | Nac: 22/02/2015<br>Ingreso: 10/04/2024 | Laura Rodriguez<br>Tel: 1145123402 | Patín — Intermedias<br>2x sem ($43.000) | Al día. Sin deuda de cuotas ni inscripción. | **Coincide al 100%** |
| 3 | **Lucas Gomez**<br>(1 mes deuda) | 54100102 | `ficha-54100102-Futbol-LucasGomez.png` | Nac: 10/08/2017<br>Ingreso: 15/03/2025 | Marcelo Gomez<br>Tel: 1145123401 | Fútbol — Principiantes<br>1x sem ($28.000) | En plazo: Oct 2026 ($28.000). Inscripción: $5.000. | **Coincide al 100%** |
| 4 | **Julian Navarro**<br>(Mayor, 1 mes) | 46200806 | `ficha-46200806-Futbol-JulianNavarro.png` | Nac: 25/08/2007 (19 a)<br>Ingreso: 15/03/2024 | Sin tutor (mayor) | Fútbol — Avanzadas<br>2x sem ($48.000) | En plazo: Oct 2026 ($48.000). | **Coincide al 100%** |
| 5 | **Emma Fernandez**<br>(3 meses seguidos) | 48100301 | `ficha-48100301-Patin-EmmaFernandez.png` | Nac: 18/09/2011<br>Ingreso: 01/03/2023 | Carlos Fernandez<br>Tel: 1145123403 | Patín — Avanzadas<br>2x sem ($45.000) | Deudor: Ago ($45.000), Sep ($45.000), Oct ($45.000). | **Coincide al 100%** |
| 6 | **Mateo Rodriguez**<br>(2 meses seguidos) | 49100203 | `ficha-49100203-Futbol-MateoRodriguez.png` | Nac: 05/06/2012<br>Ingreso: 10/04/2024 | Laura Rodriguez<br>Tel: 1145123402 | Fútbol — Avanzadas<br>1x sem ($38.000) | Deudor: Sep ($38.000), Oct ($38.000). | **Coincide al 100%** |
| 7 | **Julieta Alvarez**<br>(5 meses, con 2025) | 52300901 | `ficha-52300901-Patin-JulietaAlvarez.png` | Nac: 14/06/2015<br>Ingreso: 10/03/2024 | Marta Alvarez<br>Tel: 1145123414 | Patín — Intermedias<br>1x sem ($33.000) | Deudor: Nov 2025 ($22k), Dic 2025 ($22k), Ago 2026 ($33k), Sep 2026 ($33k), Oct 2026 ($33k). | **Coincide al 100%** |
| 8 | **Santino Romero**<br>(6 meses, con 2025) | 53300902 | `ficha-53300902-Futbol-SantinoRomero.png` | Nac: 09/01/2016<br>Ingreso: 01/08/2024 | Diego Romero<br>Tel: 1145123415 | Fútbol — Principiantes<br>1x sem ($28.000) | Deudor: Oct 2025 ($18k), Nov 2025 ($18k), Dic 2025 ($18k), Ago 2026 ($28k), Sep 2026 ($28k), Oct 2026 ($28k). | **Coincide al 100%** |
| 9 | **Lucia Ramirez**<br>(Pago parcial) | 51300905 | `ficha-51300905-Patin-LuciaRamirez.png` | Nac: 19/05/2012<br>Ingreso: 10/03/2024 | Silvia Ramirez<br>Tel: 1145123418 | Patín — Avanzadas<br>2x sem ($45.000) | En plazo: Oct 2026 ($25.000 remanente). | **Coincide al 100%** |
| 10 | **Zoe Flores**<br>(Meses salteados) | 56300903 | `ficha-56300903-Patin-ZoeFlores.png` | Nac: 11/12/2018<br>Ingreso: 15/03/2025 | Claudia Flores<br>Tel: 1145123416 | Patín — Principiantes<br>1x sem ($30.000) | Deudor: Ago 2026 ($30.000) y Oct 2026 ($30.000). Sep pagado. | **Coincide al 100%** |
| 11 | **Martina Lopez**<br>(Hermana 1, debe inscrip) | 57100401 | `ficha-57100401-Patin-MartinaLopez.png` | Nac: 12/10/2020<br>Ingreso: 20/08/2026 | Mariana Lopez<br>Tel: 1145123404 | Patín — Principiantes<br>1x sem ($30.000) | En plazo: Oct ($30.000). Inscripción: $5.000. | **Coincide al 100%** |
| 12 | **Valentina Lopez**<br>(Hermana 2, debe inscrip) | 55100402 | `ficha-55100402-Patin-ValentinaLopez.png` | Nac: 03/05/2018<br>Ingreso: 20/08/2026 | Mariana Lopez<br>Tel: 1145123404 | Patín — Principiantes<br>2x sem ($40.000) | En plazo: Oct ($40.000). Inscripción: $5.000. | **Coincide al 100%** |
| 13 | **Florencia Vega**<br>(Mayor de edad al día) | 44100801 | `ficha-44100801-Patin-FlorenciaVega.png` | Nac: 15/01/2005 (21 a)<br>Ingreso: 10/03/2022 | Sin tutor | Patín — Federadas<br>2x sem ($50.000) | Al día. Sin deuda. | **Coincide al 100%** |
| 14 | **Camila Benitez**<br>(Dos deportes: Patín) | 51200700 | `ficha-51200700-Patin-CamilaBenitez.png` | Nac: 11/04/2012<br>Ingreso: 01/03/2023 | Esteban Benitez<br>Tel: 1145123407 | Patín — Avanzadas<br>2x sem ($45.000) | En plazo: Oct ($45.000). Inscripción: $5.000. | **Coincide al 100%** |
| 15 | **Camila Benitez**<br>(Dos deportes: Fútbol) | 51200700 | `ficha-51200700-Futbol-CamilaBenitez.png` | Nac: 11/04/2012<br>Ingreso: 01/03/2023 | Esteban Benitez<br>Tel: 1145123407 | Fútbol — Principiantes<br>1x sem ($28.000) | Al día en fútbol. Cartel: *"La inscripción se cobra una sola vez... consultar en Patín."* | **Coincide al 100%** |

---

## 4. Tabla de los 12 errores probados en la revisión

Se subió `PADRON-PRU-03-v2-con-12-errores.xlsx` conteniendo 12 errores humanos reales (uno por fila):

| # | Fila y Celda | Qué se escribió | ¿Fue detectado? | Mensaje exacto del sistema | ¿Se entiende sin ayuda? |
|---|---|---|---|---|---|
| 1 | Fila 4, `D4`<br>(Nacimiento) | `"15 de mayo del 15"` | **SÍ** | `Nacimiento inválido. Esperado: Fecha real día/mes/año, no futura.` | **Sí**, muy claro. |
| 2 | Fila 9, `H9` e `I9`<br>(Tutor de menor) | Celdas vacías en nena de 6 años | **SÍ** | `Obligatorio para menor de edad. Esperado: Nombre del tutor.` / `Teléfono del tutor.` | **Sí**, indica qué dato falta y por qué. |
| 3 | Fila 13, `L13`<br>(Plan) | Plan de fútbol en alumna de Patín | **SÍ** | `Plan inexistente o ajeno al grupo. Esperado: Elegí un plan activo de ese grupo con precio mayor que cero.` | **Sí**, orienta a elegir de la lista. |
| 4 | Fila 16, `N16`<br>(Tiene deuda) | `"Sí"` pero sin períodos informados | **SÍ** | `Dice Sí pero no informa deuda. Esperado: Al menos un período con su monto.` | **Sí**, explica la inconsistencia. |
| 5 | Fila 22, `Q22`<br>(Período repetido) | `102026` cargado en Período 1 y 2 | **SÍ** | `Período repetido. Esperado: Cada mes una sola vez por alumno y deporte.` | **Sí**, muy claro. |
| 6 | Fila 27, `P27`<br>(Signo peso) | `"$22.000"` escrito con signo `$` | **SÍ** | `Monto ausente o inválido. Esperado: Pendiente mayor que cero: 52000 o 52.000; máximo 99.999.999,99.` | **Sí**, muestra ejemplos de números válidos. |
| 7 | Fila 30, `P30`<br>(Monto cero) | `0` como importe adeudado | **SÍ** | `Monto ausente o inválido. Esperado: Pendiente mayor que cero: 52000 o 52.000; máximo 99.999.999,99.` | **Sí**, aclara que debe ser mayor a cero. |
| 8 | Fila 35, `F35`<br>(Celular ausente) | Celular en blanco | **SÍ** | `Falta el dato o es demasiado largo. Esperado: Texto obligatorio, hasta 255 caracteres.` | **Sí**, indica obligatoriedad. |
| 9 | Fila 40, `J40`<br>(Deporte en minúscula) | `"patin"` en minúscula y sin tilde | **NO REBOTÓ** (Tolerado) | *El sistema lo normalizó internamente y vinculó con «Patín».* | Comportamiento tolerante y positivo. |
| 10 | Fila 45, `O45`<br>(Mes inválido) | `132026` (mes 13) | **SÍ** | `Período ausente o inválido. Esperado: Mes y año: 082026 o 92026; desde 2025.` | **Sí**, define formato mes y año. |
| 11 | Fila 50, `E50`<br>(Fecha de ingreso futura) | `"15/12/2026"` | **NO REBOTÓ** (Permitió futuro) | *No dio error: el validador sólo exige que sea posterior al nacimiento.* | **HALLAZGO (Molesta).** |
| 12 | Fila 102, `A102`<br>(Fila repetida / DNI duplicado) | Fila 102 copiada idéntica de fila 97 | **SÍ** | `DNI y deporte repetidos; ya figura en fila 97. Esperado: Una fila por alumno y deporte.` | **Sí, impecable:** le dice exactamente en qué otra fila ya está ese alumno. |

---

## 5. Hallazgos nuevos y observaciones por gravedad

### Hallazgo 1: Las listas desplegables de Plan no filtran por el Grupo elegido (En Excel) · Molesta
- **Severidad:** Molesta.
- **Detalle:** Al desplegar la columna `Plan` (L), Excel muestra todos los planes de la institución (los de Patín y los de Fútbol juntos). Si bien Wings rechaza en la revisión si se elige un plan cruzado, sería mucho más amigable para Vanina que solo viera las opciones de su grupo.
- **Acción sugerida:** Incluir en la capacitación una indicación explícita: *"Verificá que el nombre del plan empiece con el deporte y grupo que elegiste"*.

### Hallazgo 2: Falta «Inmovilizar paneles» en la plantilla Excel · Molesta
- **Severidad:** Molesta.
- **Detalle:** Al desplazarse hacia la derecha para cargar las cuotas (columnas O a AL), se pierden de vista el nombre y apellido del alumno.
- **Acción sugerida:** Guardar la plantilla descargable con los paneles inmovilizados en la fila 1 y columna C.

### Hallazgo 3: Fecha de ingreso futura no se valida contra el día de hoy · Molesta
- **Severidad:** Molesta (confirmado por Claude y verificado en código).
- **Detalle:** La fecha `15/12/2026` es aceptada sin objeción. En `PrimeraCargaExcelService.php:155`, el control es `$ingreso->lt($nacimiento)` pero falta `$ingreso->isFuture()`.

### Hallazgo 4: El botón del menú dice «Dashboard» pero los avisos dicen «Inicio» · Mejora
- **Severidad:** Mejora menor.
- **Detalle:** El cartel explicativo durante la primera carga pendiente dice: *"Wings abre este recorrido al ingresar... Volver al Inicio"*, pero en el menú lateral de Admin el botón se rotula como `Dashboard`.

---

## 6. Dónde se miró el código y por qué hizo falta (Regla 3)

1. **En la normalización de `"patin"`:** Se consultó `PrimeraCargaExcelService.php` para confirmar por qué no rebotaba la fila 40, descubriendo que la función `normalizar()` utiliza `Str::lower(Str::ascii())`, lo que intencionalmente hace tolerante la coincidencia de catálogo ante minúsculas y falta de acentos.
2. **En la fecha futura:** Se leyó la línea 155 del servicio para constatar que efectivamente la condición omitía `$ingreso->isFuture()`.
3. **En el comportamiento de Deshacer:** Se revisó el método `deshacer()` para comprobar que protegía la operación ante la presencia de cobros reales (`Pago::first()`).

---

## 7. Catálogo Completo de Capturas para el Manual de Usuario

Todas las capturas están guardadas en `docs/06-pruebas/PRU-03/capturas/`:

### A. Capturas de Microsoft Excel
| Archivo | Qué muestra exactamente | Uso en el manual |
|---|---|---|
| `excel-01-plantilla-alumnos.png` | Hoja `Alumnos` vacía recién descargada | Paso 2: Cómo abrir la plantilla |
| `excel-02-hoja-guia.png` | Hoja `Guía` con las instrucciones y ejemplos | Paso 2: Cómo leer la guía de ayuda |
| `excel-03-hoja-catalogos.png` | Hoja `Catálogos` con los deportes, grupos y planes | Paso 2: Dónde consultar los planes válidos |
| `excel-04-desplegable-deporte-grupo-plan.png` | Columnas J a M en la hoja Alumnos | Paso 2: Uso de listas desplegables |
| `excel-05-listas-catalogos-origen.png` | Rangos de origen de las listas | Referencia técnica de datos |
| `excel-07-fila-alumno-al-dia.png` | Fila completa de un alumno al día (cuotas vacías) | Paso 2: Ejemplo de carga de alumno al día |
| `excel-08-fila-con-deuda-varios-meses.png` | Fila completa con deuda de múltiples meses | Paso 2: Ejemplo de carga de alumno con deuda |
| `excel-09-marcado-con-errores.png` | Excel marcado con la columna `Errores` (AM) | Paso 3: Cómo ver qué corregir en Excel |

### B. Capturas del Sistema Wings (Flujo completo y bloqueos reales)
| Archivo | Qué muestra exactamente | Uso en el manual |
|---|---|---|
| `00-login.png` | Pantalla de inicio de sesión de Admin | Paso 0: Ingreso al sistema |
| `01-ingreso.png` | Pantalla inicial de Primera Carga | Paso 1: Pantalla inicial |
| `01b-bloqueo-inicio.png` | Intento de ir a Inicio/Dashboard (permanece en Primera Carga) | Regla: Bloqueo de inicio sin carga |
| `01c-bloqueo-alumnos.png` | Intento de ir a Alumnos (permanece en Primera Carga) | Regla: Bloqueo de altas manuales |
| `01d-bloqueo-reportes.png` | Intento de ir a Reportes (permanece en Primera Carga) | Regla: Bloqueo de reportes sin datos |
| `02-paso1-catalogos.png` | Paso 1 real con el conteo de catálogos y botón Continuar | Paso 1: Verificación de catálogos |
| `03-descargar-plantilla.png` | Paso 2 con botón Descargar plantilla | Paso 2: Descarga de archivo oficial |
| `04-errores-en-pantalla.png` | Paso 3 con los 11 errores y filas detalladas | Paso 3: Pantalla de errores de revisión |
| `05-resumen-antes-de-cargar.png` | Paso 4 con el resumen cuantitativo (100 alumnos, $) | Paso 4: Control del resumen antes de confirmar |
| `06-confirmacion.png` | Diálogo desplegado de confirmación en una sola operación | Paso 4: Confirmación final |
| `07-carga-terminada.png` | Mensaje verde de éxito: Carga terminada | Paso 5: Pantalla de éxito |
| `08-deshacer-pantalla.png` | Botón «Deshacer» activo mientras no haya cobros | Paso 6: Opción para deshacer una carga |
| `09-post-deshacer.png` | Pantalla vuelta a estado pendiente tras Deshacer | Paso 6: Resultado de deshacer |
| `10-carga-final-terminada.png` | Carga definitiva confirmada y activa | Paso 7: Padrón final activo |
| `11-alumnos-listado.png` | Listado `/alumnos` con los 100 alumnos cargados | Paso 8: Cómo ver el padrón cargado |
| `12-cobranza-listado.png` | Pantalla `/cobranza` con $2.174.000 adeudados | Paso 9: Gestión de cobranza inicial |
| `13-dashboard-inicio.png` | Dashboard de inicio con resumen financiero y deudas | Paso 10: Tablero principal del club |
| `14-reportes.png` | Módulo de Reportes habilitado | Paso 11: Consulta de reportes |
| `15-caja.png` | Pantalla de Caja en $0 (sin movimientos de dinero) | Auditoría: Cajas limpias |
| `16-movimientos.png` | Historial de movimientos sin registros | Auditoría: Movimientos contables |

### C. Capturas de las 15 Fichas Auditadas (Limpias, en `capturas/fichas/`)
- `ficha-56100101-Patin-SofiaGomez.png`: Alumna al día (Patín)
- `ficha-52100201-Patin-MiaRodriguez.png`: Alumna al día (Patín)
- `ficha-54100102-Futbol-LucasGomez.png`: Alumno con 1 mes de deuda (Fútbol)
- `ficha-46200806-Futbol-JulianNavarro.png`: Mayor de edad con 1 mes de deuda (Fútbol)
- `ficha-48100301-Patin-EmmaFernandez.png`: Alumna con 3 meses de deuda (Patín)
- `ficha-49100203-Futbol-MateoRodriguez.png`: Alumno con 2 meses de deuda (Fútbol)
- `ficha-52300901-Patin-JulietaAlvarez.png`: Alumna con 5 meses de deuda incluyendo 2025 (Patín)
- `ficha-53300902-Futbol-SantinoRomero.png`: Alumno con 6 meses de deuda incluyendo 2025 (Fútbol)
- `ficha-51300905-Patin-LuciaRamirez.png`: Alumna con pago parcial (Patín)
- `ficha-56300903-Patin-ZoeFlores.png`: Alumna con meses salteados (Patín)
- `ficha-57100401-Patin-MartinaLopez.png`: Hermana 1 con inscripción pendiente (Patín)
- `ficha-55100402-Patin-ValentinaLopez.png`: Hermana 2 con inscripción pendiente (Patín)
- `ficha-44100801-Patin-FlorenciaVega.png`: Mayor de 18 años al día (Patín)
- `ficha-51200700-Patin-CamilaBenitez.png`: Alumna en dos deportes — Ficha Patín (cobra inscripción)
- `ficha-51200700-Futbol-CamilaBenitez.png`: Alumna en dos deportes — Ficha Fútbol (aviso de inscripción única)
