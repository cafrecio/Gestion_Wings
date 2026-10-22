# Instrucciones para el Ejecutor Humano — Día 4 (Martes 13/10/2026)

**BORRADOR PREPARADO PARA REVISIÓN — NO EJECUTAR HASTA LA AUTORIZACIÓN DE CARLOS**

- **Destinatario:** Codex en el chat **Prueba humana**.
- **Entorno:** Sitio de prueba https://test.gestionar-te.com.ar (base `wingstest`).
- **Guía del recorrido:** [DIA-04.md](DIA-04.md).

---

## 1. El objetivo de tu trabajo en esta jornada

En esta prueba actuás como una persona real que trabaja en el club:
- Interpretás las situaciones cotidianas descritas en [DIA-04.md](DIA-04.md).
- Operás exclusivamente a través de la interfaz visual de Google Chrome (menús, botones, formularios, enlaces).
- Verificás por pantalla lo que una empleada, una profesora o el dueño ven en su trabajo diario: badges de estado, montos, recibos, avisos, redirecciones y listados.
- Completás en [DIA-04.md](DIA-04.md) las columnas **«Lo que pasó»** y **«¿Coincide?»** con lo observado realmente.

---

## 2. Misión especial: Capturas prolijas para el futuro Manual de Uso de Wings

Las capturas de pantalla de esta jornada tienen un propósito fundamental: **serán el material visual con el que Gemini redactará el Manual Oficial de Uso de Wings para el personal del club**.

Por favor, sé especialmente prolija y ordenada al tomarlas:
1. **Carpeta de destino:** Guardar todas las imágenes en:
   `docs/06-pruebas/PRU-04/evidencia-dia-04/`
2. **Nomenclatura estandarizada:** Usar nombres claros que sigan el número de tarea:
   - `dia04-01-caja-apertura.png` (modal o pantalla de apertura con el cambio sugerido de $10.000).
   - `dia04-02a-alumno-alta-formulario.png` (formulario de alta de Mora Benítez completo).
   - `dia04-02b-alumno-listado-redireccion.png` (redirección a Alumnos con mensaje verde «Alumno creado correctamente.»).
   - `dia04-02c-alumno-alta-ficha.png` (ficha creada de Mora con sus $45.000 y badge «En plazo»).
   - `dia04-03-cobranza-nueva-alumna.png` (listado de Cobranza mostrando a Mora con $45.000 en plazo).
   - `dia04-04a-cobro-cuota-renata.png` (pantalla de cobro de cuota completa).
   - `dia04-04b-recibo-renata.png` (recibo oficial emitido en PDF).
   - `dia04-05-cobro-deuda-mercadopago.png` (cobro de septiembre de Emma con MP).
   - `dia04-06-cobro-parcial-dante.png` (cobro a cuenta de $30.000 con desglose FIFO).
   - `dia04-07-cobro-galicia-mateo.png` (cobro con Banco Galicia).
   - `dia04-08-cobro-adelantado-sofia.png` (cobro de cuota de noviembre adelantada).
   - `dia04-09-caja-ingreso-indumentaria.png` (movimiento de venta de remera en subrubro Indumentaria institucional).
   - `dia04-10-caja-egreso-limpieza.png` (movimiento de gasto de limpieza con ticket).
   - `dia04-11a-clase-nomina-principiantes.png` (nómina de Patín Principiantes mostrando a Mora incorporada).
   - `dia04-11b-clase-asistencia-avanzadas.png` (asistencia de la clase 28 de Patín Avanzadas de hoy guardada).
   - `dia04-12-clase-historica-asistencia.png` (asistencia regularizada del sábado 10/10 en Fútbol).
   - `dia04-13-caja-cierre-arqueo.png` (pantalla de cierre de caja con $119.500 contados y diferencia $0).
   - `dia04-14a-caja-detalle-tildes.png` (detalle de caja con todas las líneas tildadas por el dueño).
   - `dia04-14b-caja-validada-redireccion.png` (redirección a Caja con mensaje «Caja validada...» y badge VALIDADA).
   - `dia04-15-reporte-matricula-alumnos.png` (Reportes → Alumnos con matrícula actual de 101 activos).
   - `dia04-16a-inicio-conciliado.png` (pantalla de Inicio del dueño al cierre de jornada).
   - `dia04-16b-reporte-aportes-ingresos.png` (Reporte de ingresos reflejando la remera en el renglón de Aportes).
3. **Calidad:** Asegurarse de que las pantallas se vean completas, legibles y sin popups o menús superpuestos que tapen los datos clave.

---

## 3. Reglas estrictas de operación humana

1. **Navegación 100% por pantalla:**
   - Hacé clic en la barra de navegación lateral o superior, abrí botones, completá formularios.
   - **Prohibido tipear URLs internas directas en el navegador** (por ejemplo, navegá desde Alumnos → Nuevo → Guardar, y luego hacé clic en Ver; no escribas `/alumnos/101` a mano).
   - **Prohibido consultar la base de datos MySQL o correr comandos de consola durante el recorrido.**
2. **Espera de 60 segundos entre cambios de usuario (Anti-Throttle):**
   - El sistema bloquea temporalmente si detecta más de 5 inicios de sesión por minuto.
   - Cada vez que cierres la sesión de un usuario (por ejemplo, al pasar de Sandra a Lucía, o de Hernán al Dueño), **esperá 60 segundos de reloj** antes de ingresar con el siguiente usuario para no provocar el cartel «Demasiados intentos seguidos».

---

## 4. Registro y detención

- En cada una de las 16 tareas de [DIA-04.md](DIA-04.md):
  * Escribí en **«Lo que pasó»** lo que viste en la pantalla (importes, mensajes, nombres, estados, redirecciones).
  * Escribí en **«¿Coincide?»**: `Sí`, `No` o `Parcial`.
- **Condición de detención inmediata:**
  * Si al cerrar la caja en la Tarea 13 hay una diferencia de dinero inexplicable (diferencia distinta de $0 sobre los $119.500 esperados).
  * Si aparece una pantalla de error crítico (HTTP 500) que impida continuar con la jornada.
  * En esos casos, **detené la prueba inmediatamente** y avisá a Carlos.
