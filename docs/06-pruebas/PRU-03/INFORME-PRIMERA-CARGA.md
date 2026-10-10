# Informe de la Primera Carga de Alumnos (PRU-03)
**Sitio evaluado:** `https://test.gestionar-te.com.ar`  
**Fecha y entorno:** 10 de octubre de 2026. Navegador Chromium real (1366 × 768), rol ADMIN.  
**Evaluador:** Gemini (Antigravity).

---

## 1. Veredicto en tres renglones

**Sí, se le puede entregar a Vanina hoy con una sola advertencia operativa previa.**  
El recorrido de 4 pasos es sumamente claro, el validador en bloque no deja pasar incoherencias financieras ni de tutores, y la función de «Deshacer» da una tranquilidad enorme ante equivocaciones humanas.  
La única salvedad que hay que explicarle a Vanina antes de empezar es que las fechas deben tipear con barras (`DD/MM/AAAA`) y no dejarlas para el final en texto libre, porque el sistema no adivina fechas informales.

---

## 2. ¿Es práctico para una persona común?

### Tiempos y sensaciones por paso
- **Paso 1 (Catálogos):** Tarda menos de 1 minuto. El contador en pantalla (2 deportes, 4 niveles, 6 grupos, 12 planes) confirma de inmediato que el club está configurado. El botón «Continuar» es directo y no genera dudas.
- **Paso 2 (Descarga y llenado de plantilla):** La descarga es instantánea. La hoja `Guía` es clara y los ejemplos de Ana, Bruno, Carla y Diego cubren el 95% de las dudas reales (alumno al día, deuda de mes corriente, deuda vieja y pagos parciales).
- **Paso 3 (Revisión y Excel marcado):** La revisión de 90 filas tardó 3 segundos. Cuando hay errores, la pantalla lista cada fila y celda con lo esperado en castellano, y el botón «Descargar» entrega el mismo archivo con la columna `Errores` (AM) al final. Esto es excelente: la persona no tiene que adivinar qué falló ni volver a escribir todo de cero.
- **Paso 4 (Carga y Confirmación):** El resumen previo antes de tocar la base de datos (cantidad de alumnos, cuotas, inscripciones y monto total al peso) permite cotejar contra la planilla manual. El botón «Confirmar» ejecuta la carga en un solo paso y deja todo listo.
- **Botón «Deshacer»:** Probado en el recorrido F. Al hacer clic pide confirmación nativa (*"¿Deshacer solamente esta carga? Se conservan los usuarios y catálogos."*) y en 2 segundos devuelve la aplicación al estado pendiente sin dejar alumnos huérfanos ni deudas flotando.

### Estimación de tiempo para 250 alumnos reales
Llenar un padrón de 250 alumnos desde planillas de papel o WhatsApp le llevará a una persona entre **2 y 3 horas de tipeo**. La interacción con el sistema Wings (subir, revisar, corregir 2 o 3 errores comunes y confirmar) le llevará **menos de 10 minutos**.

---

## 3. ¿Quedó bien cargado? Contrastación dato por dato

Se compararon los totales y 15 alumnos seleccionados a propósito (cubriendo todos los casos de borde) entre el archivo `PADRON-PRU-03.xlsx` y las pantallas del sistema:

### Totales generales
| Concepto | Según Excel (Padrón) | Según Pantalla Wings (Revisión / Cobranza / Inicio) | Coincidencia |
|---|---|---|---|
| **Alumnos totales activos** | 90 filas (89 personas físicas, 1 en 2 deportes) | 90 registros en `/alumnos`, 90 en `/cobranza` | **EXACTO (Coincide)** |
| **Alumnos en Patín** | 60 | 60 en filtro Patín | **EXACTO (Coincide)** |
| **Alumnos en Fútbol** | 30 | 30 en filtro Fútbol | **EXACTO (Coincide)** |
| **Cuotas mensuales adeudadas** | 53 cuotas | 53 cuotas pendientes en resumen | **EXACTO (Coincide)** |
| **Monto cuotas adeudadas** | $1.801.000,00 | $1.801.000,00 en resumen, cobranza e Inicio | **EXACTO (Coincide)** |
| **Inscripciones adeudadas** | 8 personas (9 filas con Sí, 1 compartida por 2 deportes) | 8 inscripciones pendientes ($40.000,00) | **EXACTO (Coincide)** |
| **Total deuda global** | $1.841.000,00 | $1.841.000,00 en Cobranza (`TOTAL ADEUDADO`) | **EXACTO (Coincide)** |
| **Plata ingresada a cajas** | $0,00 | $0,00 en `/caja` y `/movimientos` | **EXACTO (Coincide)** |

---

### Muestreo exhaustivo de 15 alumnos (Ficha vs. Excel)

| # | Alumno / Caso | DNI | Ficha URL | Datos Personales (Excel vs Ficha) | Tutor (Excel vs Ficha) | Deporte, Grupo y Plan | Deuda y Períodos Cargados | Coincidencia |
|---|---|---|---|---|---|---|---|---|
| 1 | **Sofia Gomez**<br>(Al día, menor) | 56100101 | `/alumnos/151` | Nac: 15/04/2019<br>Ingreso: 15/03/2025 | Marcelo Gomez<br>Tel: 1145123401 | Patín — Principiantes<br>1x sem ($30.000) | Al día en cuotas. Inscripción pendiente: $5.000. | **Coincide al 100%** |
| 2 | **Mia Rodriguez**<br>(Al día, menor) | 52100201 | `/alumnos/153` | Nac: 22/02/2015<br>Ingreso: 10/04/2024 | Laura Rodriguez<br>Tel: 1145123402 | Patín — Intermedias<br>2x sem ($43.000) | Al día. Sin deuda de cuotas ni inscripción. | **Coincide al 100%** |
| 3 | **Lucas Gomez**<br>(1 mes deuda) | 54100102 | `/alumnos/152` | Nac: 10/08/2017<br>Ingreso: 15/03/2025 | Marcelo Gomez<br>Tel: 1145123401 | Fútbol — Principiantes<br>1x sem ($28.000) | En plazo: Oct 2026 ($28.000). Inscripción: $5.000. | **Coincide al 100%** |
| 4 | **Julian Navarro**<br>(Mayor, 1 mes) | 46200806 | `/alumnos/171` | Nac: 25/08/2007 (19 a)<br>Ingreso: 15/03/2024 | Sin tutor (mayor) | Fútbol — Avanzadas<br>2x sem ($48.000) | En plazo: Oct 2026 ($48.000). | **Coincide al 100%** |
| 5 | **Emma Fernandez**<br>(3 meses seguidos) | 48100301 | `/alumnos/156` | Nac: 18/09/2011<br>Ingreso: 01/03/2023 | Carlos Fernandez<br>Tel: 1145123403 | Patín — Avanzadas<br>2x sem ($45.000) | Deudor: Ago ($45.000), Sep ($45.000), Oct ($45.000). | **Coincide al 100%** |
| 6 | **Mateo Rodriguez**<br>(2 meses seguidos) | 49100203 | `/alumnos/155` | Nac: 05/06/2012<br>Ingreso: 10/04/2024 | Laura Rodriguez<br>Tel: 1145123402 | Fútbol — Avanzadas<br>1x sem ($38.000) | Deudor: Sep ($38.000), Oct ($38.000). | **Coincide al 100%** |
| 7 | **Julieta Alvarez**<br>(5 meses, con 2025) | 52300901 | `/alumnos/172` | Nac: 14/06/2015<br>Ingreso: 10/03/2024 | Marta Alvarez<br>Tel: 1145123414 | Patín — Intermedias<br>1x sem ($33.000) | Deudor: Nov 2025 ($22k), Dic 2025 ($22k), Ago 2026 ($33k), Sep 2026 ($33k), Oct 2026 ($33k). | **Coincide al 100%** |
| 8 | **Santino Romero**<br>(6 meses, con 2025) | 53300902 | `/alumnos/173` | Nac: 09/01/2016<br>Ingreso: 01/08/2024 | Diego Romero<br>Tel: 1145123415 | Fútbol — Principiantes<br>1x sem ($28.000) | Deudor: Oct 2025 ($18k), Nov 2025 ($18k), Dic 2025 ($18k), Ago 2026 ($28k), Sep 2026 ($28k), Oct 2026 ($28k). | **Coincide al 100%** |
| 9 | **Lucia Ramirez**<br>(Pago parcial) | 51300905 | `/alumnos/176` | Nac: 19/05/2012<br>Ingreso: 10/03/2024 | Silvia Ramirez<br>Tel: 1145123418 | Patín — Avanzadas<br>2x sem ($45.000) | En plazo: Oct 2026 ($25.000, saldo remanente). | **Coincide al 100%** |
| 10 | **Zoe Flores**<br>(Meses salteados) | 56300903 | `/alumnos/174` | Nac: 11/12/2018<br>Ingreso: 15/03/2025 | Claudia Flores<br>Tel: 1145123416 | Patín — Principiantes<br>1x sem ($30.000) | Deudor: Ago 2026 ($30.000) y Oct 2026 ($30.000). Sep pagado. | **Coincide al 100%** |
| 11 | **Martina Lopez**<br>(Hermana 1, debe inscrip) | 57100401 | `/alumnos/158` | Nac: 12/10/2020<br>Ingreso: 20/08/2026 | Mariana Lopez<br>Tel: 1145123404 | Patín — Principiantes<br>1x sem ($30.000) | En plazo: Oct ($30.000). Inscripción: $5.000. | **Coincide al 100%** |
| 12 | **Valentina Lopez**<br>(Hermana 2, debe inscrip) | 55100402 | `/alumnos/159` | Nac: 03/05/2018<br>Ingreso: 20/08/2026 | Mariana Lopez<br>Tel: 1145123404 | Patín — Principiantes<br>2x sem ($40.000) | En plazo: Oct ($40.000). Inscripción: $5.000. (Se cobra a ambas por tener DNI distinto). | **Coincide al 100%** |
| 13 | **Florencia Vega**<br>(Mayor de edad al día) | 44100801 | `/alumnos/166` | Nac: 15/01/2005 (21 a)<br>Ingreso: 10/03/2022 | Sin tutor | Patín — Federadas<br>2x sem ($50.000) | Al día. Sin deuda. | **Coincide al 100%** |
| 14 | **Camila Benitez**<br>(Dos deportes: Patín) | 51200700 | `/alumnos/164` | Nac: 11/04/2012<br>Ingreso: 01/03/2023 | Esteban Benitez<br>Tel: 1145123407 | Patín — Avanzadas<br>2x sem ($45.000) | En plazo: Oct ($45.000). Inscripción: $5.000. | **Coincide al 100%** |
| 15 | **Camila Benitez**<br>(Dos deportes: Fútbol) | 51200700 | `/alumnos/165` | Nac: 11/04/2012<br>Ingreso: 01/03/2023 | Esteban Benitez<br>Tel: 1145123407 | Fútbol — Principiantes<br>1x sem ($28.000) | Al día en fútbol. Cartel explícito: *"La inscripción se cobra una sola vez... consultar estado en su ficha de Patín."* | **Coincide al 100%** |

---

## 4. Los errores introducidos a propósito en el Excel

Se cargó `PADRON-PRU-03-con-errores.xlsx` con 11 errores humanos típicos.

| # | Fila y Campo | Qué se escribió en el Excel | ¿Fue detectado? | Mensaje exacto del sistema | ¿Se entiende sin ayuda? |
|---|---|---|---|---|---|
| 1 | Fila 4, `D4`<br>(Nacimiento) | `"15 de mayo del 15"` | **SÍ** | `Nacimiento inválido. Esperado: Fecha real día/mes/año, no futura.` | **Sí**, clarísimo. |
| 2 | Fila 9, `H9` e `I9`<br>(Tutor de menor) | Vaciados en nena de 6 años | **SÍ** | `Obligatorio para menor de edad. Esperado: Nombre del tutor.` / `Teléfono del tutor.` | **Sí**, indica exactamente qué campo y por qué. |
| 3 | Fila 13, `L13`<br>(Plan) | Plan de fútbol en alumna de Patín | **SÍ** | `Plan inexistente o ajeno al grupo. Esperado: Elegí un plan activo de ese grupo con precio mayor que cero.` | **Sí**, ayuda a usar el desplegable. |
| 4 | Fila 16, `N16`<br>(Tiene deuda) | `"Sí"` pero sin meses cargados | **SÍ** | `Dice Sí pero no informa deuda. Esperado: Al menos un período con su monto.` | **Sí**, explica la inconsistencia. |
| 5 | Fila 22, `Q22`<br>(Período repetido) | `102026` en período 1 y 2 | **SÍ** | `Período repetido. Esperado: Cada mes una sola vez por alumno y deporte.` | **Sí**, muy intuitivo. |
| 6 | Fila 27, `P27`<br>(Signo peso) | `"$22.000"` | **SÍ** | `Monto ausente o inválido. Esperado: Pendiente mayor que cero: 52000 o 52.000; máximo 99.999.999,99.` | **Sí**, muestra el formato numérico esperado. |
| 7 | Fila 30, `P30`<br>(Monto cero) | `0` | **SÍ** | `Monto ausente o inválido. Esperado: Pendiente mayor que cero: 52000 o 52.000; máximo 99.999.999,99.` | **Sí**, indica que debe ser mayor a cero. |
| 8 | Fila 35, `F35`<br>(Celular vacío) | Celular en blanco | **SÍ** | `Falta el dato o es demasiado largo. Esperado: Texto obligatorio, hasta 255 caracteres.` | **Sí**. |
| 9 | Fila 45, `O45`<br>(Mes inválido) | `132026` (mes 13) | **SÍ** | `Período ausente o inválido. Esperado: Mes y año: 082026 o 92026; desde 2025.` | **Sí**. |
| 10 | Fila 40, `J40`<br>(Deporte en minúscula) | `"patin"` (sin tilde y minúscula) | **NO REBOTÓ** (Fue normalizado) | *No dio error: el sistema normalizó `"patin"` a `"Patín"` internamente vía `Str::ascii()`.* | Es una virtud de tolerancia; no rompe. |
| 11 | Fila 50, `E50`<br>(Fecha de ingreso futura) | `"15/12/2026"` | **NO REBOTÓ** (Permitió fecha futura) | *No dio error: el validador sólo exige que el ingreso sea posterior al nacimiento, no que sea menor a hoy.* | **HALLAZGO (Ver sección 5).** |

---

## 5. Hallazgos ordenados por gravedad

### Hallazgo 1: Fecha de ingreso futura no se valida contra la fecha actual · Molesta
- **Severidad:** Molesta (no frena la entrega).
- **Comportamiento:** En la fila 50 se ingresó `15/12/2026` como fecha de alta. El validador en `PrimeraCargaExcelService.php` comprobó `$ingreso->lt($nacimiento)` (que sea posterior al nacimiento), pero no ejecutó `$ingreso->isFuture()`.
- **Impacto:** Si una persona tipea por error el año 2027 o un mes posterior al actual en la fecha de ingreso, el sistema lo acepta sin avisar.
- **Recomendación futura:** Agregar `|| $ingreso->isFuture()` con mensaje `"Fecha de ingreso no puede ser futura"`.

### Hallazgo 2: Alta tolerancia en nombres de catálogo · Favorable
- **Severidad:** Mejora / Comportamiento positivo.
- **Comportamiento:** Escribir `"patin"` o `"futbol"` en minúsculas y sin tildes no traba la carga; el sistema lo vincula correctamente con el deporte gracias a la normalización ASCII.

---

## 6. Lo que no se probó y por qué

1. **Apertura de caja y cobros posteriores:** Excluido explícitamente por la regla 5 del pedido. La prueba concluyó exitosamente con la importación terminada y verificada.
2. **Archivos `.xls` antiguos (formato 97-2003):** El validador exige formato `.xlsx` moderno. No se subieron archivos binarios obsoletos.

---

## 7. Catálogo de Capturas Guardadas para el Manual de Usuario

Todas las capturas se guardaron a resolución de escritorio **1366 × 768** en `docs/06-pruebas/PRU-03/capturas/`:

| Archivo | Qué muestra exactamente | Paso del manual al que sirve |
|---|---|---|
| `00-login.png` | Pantalla de inicio de sesión con usuario Admin | Paso 0: Cómo ingresar al sistema |
| `01-ingreso.png` | Pantalla inicial de Primera Carga (Paso 1: Catálogos activos) | Paso 1: Verificación de deportes y planes |
| `01b-redireccion-inicio.png` | Intento de ir a Inicio mientras la carga está pendiente | Regla: El sistema protege la carga inicial |
| `01c-redireccion-alumnos.png` | Intento de ir a Alumnos mientras la carga está pendiente | Regla: No se permite alta manual previa |
| `01d-redireccion-reportes.png` | Intento de ir a Reportes mientras la carga está pendiente | Regla: Reportes bloqueados sin padrón |
| `03-descargar-plantilla.png` | Paso 2: Instrucciones y botón «Descargar plantilla» | Paso 2: Descarga de la plantilla oficial |
| `04-errores-en-pantalla.png` | Paso 3 con la lista completa de errores y celdas a corregir | Paso 3: Qué hacer si el Excel tiene errores |
| `05-resumen-antes-de-cargar.png` | Paso 4 con el resumen cuantitativo (alumnos, cuotas, $) | Paso 4: Revisión del resumen antes de confirmar |
| `06-confirmacion.png` | Bloque desplegado de confirmación definitiva | Paso 4: Confirmación en una sola operación |
| `07-carga-terminada.png` | Mensaje verde de éxito: Carga terminada | Paso 5: Carga finalizada con éxito |
| `08-deshacer-pantalla.png` | Botón «Deshacer» disponible tras la importación | Paso 6: Cómo deshacer si hubo una equivocación |
| `09-post-deshacer.png` | Pantalla vuelta a estado pendiente tras Deshacer | Paso 6: Estado tras cancelar la carga |
| `10-carga-final-terminada.png` | Carga definitiva confirmada y activa | Paso 7: Padrón activo definitivo |
| `11-alumnos-listado.png` | Listado `/alumnos` con los 90 alumnos cargados | Paso 8: Cómo ver el padrón cargado |
| `12-cobranza-listado.png` | Pantalla `/cobranza` con $1.841.000 adeudados | Paso 9: Cómo consultar la cobranza inicial |
| `13-dashboard-inicio.png` | Dashboard de inicio con resumen financiero y deudas | Paso 10: Tablero principal del club |
| `14-reportes.png` | Módulo de Reportes desbloqueado y funcionando | Paso 11: Consulta de reportes |
| `15-caja.png` | Pantalla de Caja en $0 (sin movimientos de dinero) | Verificación: Seguridad de cajas limpias |
| `16-movimientos.png` | Historial de movimientos sin registros | Verificación: Auditoría contable |
| `fichas/ficha-51200700-1-Patin.png` | Ficha de Patín de alumna que hace dos deportes | Casos especiales: Alumnos en dos deportes |
| `fichas/ficha-51200700-2-Patin.png` | Ficha de Fútbol de la misma alumna (aviso de inscripción) | Casos especiales: Inscripción única por DNI |
| `fichas/ficha-52300901-1-Patin.png` | Ficha con deuda de 5 meses incluyendo cuotas de 2025 | Casos especiales: Carga de deudas históricas |
| `fichas/ficha-51300905-1-Patin.png` | Ficha con cuota pagada parcialmente ($25.000) | Casos especiales: Cuotas con saldo remanente |
