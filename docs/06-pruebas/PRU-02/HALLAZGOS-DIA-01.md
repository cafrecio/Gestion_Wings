# PRU-02 — Hallazgos Día 1 (Configuración y Carga previa a la apertura)

**Fecha de ejecución:** 23/09/2026 (Simulando 24/09/2026 09:00)  
**Entorno:** `https://test.gestionar-te.com.ar` en navegador visible (Chrome)  
**Usuario:** `admin@wings.test` (Vanina / Administrador)

---

### 1. Inconsistencia crítica: 60 Deudores en Cobranza vs 20 en Dashboard
- **Qué falló:** El Dashboard reporta correctamente **20 alumnos con deuda** (conforme al cuaderno de saldos iniciales). Sin embargo, en la pantalla de Cobranza (`/cobranza`), los 60 alumnos figuran como **DEUDORES** (0 Al día, 0 En plazo, 0 Morosos, 60 Deudores).
- **Causa raíz:** En `CobranzaEstadoService.php` (línea 234):
  ```php
  if (!$tienePagos || $impagasAnteriores->isNotEmpty()) {
      $estado = self::ESTADO_DEUDOR;
  }
  ```
  Al iniciar el club con Wings, ningún alumno tiene pagos históricos registrados en el sistema (`!$tienePagos` es verdadero para todos). Esto provoca que los 40 alumnos que arrancan al día sean calificados como deudores.
- **Evidencia:** `evidencia/dia01_09_pantalla_cobranza_deudores.png`.

---

### 2. Aceptación sin aviso de horarios fraccionados (2 horas de cancha)
- **Qué falló:** El sistema permitió crear sin advertencia una clase de 17:30 a 18:30. Para el club esto representa pagar **dos horas de alquiler de cancha** en vez de una, ya que los clubes cobran por bloque de hora reloj.
- **Causa de fondo:** Wings no modela canchas ni franjas horarias de alquiler (POS-07 pendiente).
- **Acción realizada:** Se documentó y luego se canceló la clase desde la pantalla de detalle.
- **Evidencia:** `evidencia/dia01_04_clase_1730_dos_horas_cancha.png` y `evidencia/dia01_04_clase_1730_cancelada.png`.

---

### 3. Falta de datos esenciales en la vista general del Padrón (`/alumnos`)
- **Qué estorba:** El listado de alumnos no exhibe el plan asignado (1x o 2x por semana), ni el celular de contacto del alumno (solo tutor, vacío en adultos), ni la fecha real de ingreso, ni el estado de deuda.
- **Impacto para Vanina:** Para verificar si cada alumno está en el grupo y plan correspondiente, se ve obligada a ingresar individualmente a 60 fichas de alumnos (`/alumnos/{id}`), lo cual resulta inviable en la operativa diaria.
- **Evidencia:** `evidencia/dia01_07_padron_alumnos_listado.png` y `evidencia/dia01_08_ficha_alumno_detalle.png`.

---

### 4. Falla silenciosa ante valores inválidos en Configuración
- **Qué falló:** Al ingresar valores no permitidos en `/configuraciones` (por ejemplo, `-100` o `0` en importe de inscripción), el backend devuelve HTTP 422, pero el JavaScript del frontend no muestra ninguna alerta ni texto de error al usuario, dejando el campo con el valor ingresado sin confirmación visual.
- **Evidencia:** `evidencia/dia01_12_config_valor_invalido_silencioso.png`.

---

### 5. Medios de pago bancarios inactivos por defecto
- **Qué falló:** En la pantalla `/tipos-caja`, las tres cuentas bancarias configuradas (Banco Galicia, Banco Nación y Banco Nación Ahorro) figuraban en estado `(inactivo)`, impidiendo su uso hasta que la administradora las activó manualmente una por una.
- **Evidencia:** `evidencia/dia01_15_tipos_caja_medios_cobro.png` y `evidencia/dia01_16_tipos_caja_todos_activos.png`.

---

### 6. Cargas recurrentes rígidas ante horarios asimétricos
- **Qué estorba:** Para grupos cuyos días de entrenamiento tienen horarios diferentes (ej. Patín Intermedias: lunes 17:00 a 18:00 y viernes 16:00 a 17:00), la pantalla de creación recurrente obliga a realizar cargas independientes en lugar de permitir un cronograma semanal unificado por grupo.

---

### 7. Huecos de negocio detectados
- **Compra de mercadería/indumentaria:** Existe rubro de ingreso por venta de indumentaria, pero no existe rubro de egreso para la compra de la mercadería vendida.
- **Datos bancarios de profesores:** No existen campos en la ficha del profesor para almacenar CBU o Alias donde transferir las liquidaciones.

---

*Se ejecutó la configuración completa previa a la apertura (profesores, 76 clases del cronograma semanal, revisión de padrón, ajustes de configuración, creación de rubros/subrubros y activación de tipos de caja) íntegramente por pantalla en navegador visible.*
