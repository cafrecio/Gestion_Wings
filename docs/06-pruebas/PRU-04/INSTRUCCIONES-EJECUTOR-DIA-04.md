# Instrucciones de ejecución — Día 4 (Martes 13/10/2026)

**BORRADOR PARA REVISIÓN — NO EJECUTAR HASTA AUTORIZACIÓN EXPRESA DE CARLOS**

- **Destinatario:** Chat «Prueba humana» (Codex en navegador Chrome).
- **Ambiente:** `https://test.gestionar-te.com.ar` (base `wingstest`).
- **Documento del cuento:** [DIA-04.md](DIA-04.md).
- **Documento de objetivos y control:** [OBJETIVOS-PRUEBA.md](OBJETIVOS-PRUEBA.md).

---

## 1. Condiciones de inicio en el servidor (solo antes de arrancar)

1. Conectar por SSH al servidor VPS:
   ```bash
   ssh vps
   ```
2. Fijar el reloj simulado para el día 4 a las **18:15 hs** (la clase de 17:00 a 18:00 ya habrá concluido):
   ```bash
   cd /home/wingstest/app && sudo -u wingstest /usr/bin/php82 artisan wings:fecha-simulada "2026-10-13 18:15"
   ```
3. Verificar que la salida confirme: `Fecha simulada fijada: 2026-10-13 18:15:00`.
4. **No volver a tocar el reloj durante el resto de la jornada.** El reloj avanza solo a partir de esa hora.

---

## 2. Comprobación previa de condiciones iniciales (Paso 0)

Antes de que Sandra abra la caja en la Tarea 1, ingresar con el usuario **Admin Prueba** y verificar en pantalla los antecedentes certificados en DIA-02 y DIA-03:
- **Cobranza:** Verificar que liste 100 alumnos activos, con la distribución: **65 Al día / 0 En plazo / 22 Morosos / 13 Deudores**, y un total de deuda de **$2.010.000**.
- **Cashflow:** Verificar que el disponible acumulado total sea de **$1.734.000** (Efectivo $376.000, Mercado Pago $1.358.000, Banco Galicia $0).
- **Clases:** Verificar que el contador badge del menú «Sin asistencia» muestre exactamente **33** clases pendientes históricas.

> *Si los valores en pantalla coinciden con lo anterior, consignar conformidad y proceder a la Tarea 1. Si el servidor presentara alguna discrepancia previa, anotarla explícitamente en el reporte antes de realizar cualquier escritura.*

---

## 3. Reglas del método humano

1. **Navegación 100% humana:**
   - Operar exclusivamente desde la ventana de Chrome haciendo clics en menús, botones, enlaces y completando formularios.
   - **Prohibido escribir URLs internas en la barra de direcciones.** El recorrido debe encontrar todo a través de la interfaz gráfica.
   - **Prohibido consultar la base de datos o correr scripts de prueba durante el recorrido.**
2. **Espera entre cambios de usuario (Anti-Throttle):**
   - El sistema tiene un límite de seguridad de 5 inicios de sesión por minuto.
   - **Esperar siempre 60 segundos de reloj** tras cerrar una sesión antes de iniciar con otro usuario para evitar el cartel «Demasiados intentos seguidos».
3. **Mediciones de tiempo sencillas:**
   - Anotar tiempo aproximado de respuesta en segundos para:
     * Operación 1: Apertura de caja (tiempo de respuesta al presionar Abrir).
     * Operación 2: Guardado de alumna nueva (tiempo entre clic en Guardar y render de la ficha).
     * Operación 18: Carga inicial del listado de Cobranza (con 101 alumnos).

---

## 4. Registro de lo observado

Para cada una de las 18 operaciones de [DIA-04.md](DIA-04.md):
- Completar la columna **«Lo que pasó»**: describir qué devolvió la pantalla, importes visibles, carteles y estados.
- Completar la columna **«¿Coincide?»**: `Sí`, `No` o `Parcial`.
- Si se detecta una diferencia visual o texto confuso que no impida seguir:
  - Tomar captura de pantalla en `docs/06-pruebas/PRU-04/evidencia-dia-04/`.
  - Anotarlo como observación y continuar.

---

## 5. Condición estricta de detención

**DETENER LA PRUEBA INMEDIATAMENTE Y AVISAR A CARLOS SI:**
1. Se produce un descuadre de dinero en caja, mostrador o cashflow (diferencia de efectivo distinta de $0 no justificada).
2. Se produce un error fatal (pantalla 500) o un bloqueo del camino feliz que impida continuar con las operaciones siguientes.

---

## 6. Fin de la jornada

Una vez completadas las 18 operaciones:
1. Asegurar que las 18 filas de [DIA-04.md](DIA-04.md) tengan «Lo que pasó» y «¿Coincide?» completados.
2. Añadir la sección de **Hallazgos** al pie de DIA-04.md (clasificados en Frena / Molesta / Detalle) con sus capturas.
3. No alterar datos ni correr migraciones; el verificador independiente controlará la base contra los objetivos de [OBJETIVOS-PRUEBA.md](OBJETIVOS-PRUEBA.md).
