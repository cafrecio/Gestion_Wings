# Ejemplos de Uso - Pagos v1 (con fecha_pago)

## Ejemplo 1: Primer Pago con fecha_pago

### Request
```bash
POST /api/pagos
Content-Type: application/json

{
  "alumno_id": 1,
  "mes": 1,
  "anio": 2026,
  "forma_pago_id": 1,
  "fecha_pago": "2026-01-20",
  "observaciones": "Pago inicial - 50% descuento por inscripción tardía"
}
```

### Response (201 Created)
```json
{
  "success": true,
  "message": "Pago registrado exitosamente.",
  "data": {
    "id": 1,
    "alumno_id": 1,
    "plan_id": 2,
    "regla_primer_pago_id": 2,
    "mes": 1,
    "anio": 2026,
    "monto_base": 500.00,
    "porcentaje_aplicado": 70.00,
    "monto_final": 350.00,
    "forma_pago_id": 1,
    "fecha_pago": "2026-01-20",
    "observaciones": "Pago inicial - 50% descuento por inscripción tardía",
    "estado": "pagado",
    "created_at": "2026-01-25T10:30:00.000000Z",
    "updated_at": "2026-01-25T10:30:00.000000Z"
  }
}
```

**Nota:** `fecha_pago` (20/01) es diferente de `created_at` (25/01). El pago se realizó el día 20, pero se registró en el sistema el día 25.

---

## Ejemplo 2: Pago Normal (sin observaciones)

### Request
```bash
POST /api/pagos
Content-Type: application/json

{
  "alumno_id": 1,
  "mes": 2,
  "anio": 2026,
  "forma_pago_id": 2,
  "fecha_pago": "2026-02-15"
}
```

### Response (201 Created)
```json
{
  "success": true,
  "message": "Pago registrado exitosamente.",
  "data": {
    "id": 2,
    "alumno_id": 1,
    "plan_id": 2,
    "regla_primer_pago_id": null,
    "mes": 2,
    "anio": 2026,
    "monto_base": 500.00,
    "porcentaje_aplicado": 100.00,
    "monto_final": 500.00,
    "forma_pago_id": 2,
    "fecha_pago": "2026-02-15",
    "observaciones": null,
    "estado": "pagado",
    "created_at": "2026-02-15T14:20:00.000000Z",
    "updated_at": "2026-02-15T14:20:00.000000Z"
  }
}
```

---

## Ejemplo 3: Pago con Override Manual

### Request
```bash
POST /api/pagos
Content-Type: application/json

{
  "alumno_id": 3,
  "mes": 1,
  "anio": 2026,
  "forma_pago_id": 1,
  "fecha_pago": "2026-01-28",
  "porcentaje_manual": 50.00,
  "observaciones": "Acuerdo especial - 50% por situación familiar"
}
```

### Response (201 Created)
```json
{
  "success": true,
  "message": "Pago registrado exitosamente.",
  "data": {
    "id": 3,
    "alumno_id": 3,
    "plan_id": 1,
    "regla_primer_pago_id": null,
    "mes": 1,
    "anio": 2026,
    "monto_base": 300.00,
    "porcentaje_aplicado": 50.00,
    "monto_final": 150.00,
    "forma_pago_id": 1,
    "fecha_pago": "2026-01-28",
    "observaciones": "Acuerdo especial - 50% por situación familiar",
    "estado": "pagado",
    "created_at": "2026-01-28T16:45:00.000000Z",
    "updated_at": "2026-01-28T16:45:00.000000Z"
  }
}
```

---

## Ejemplo 4: Error - Fecha de pago inválida

### Request (formato incorrecto)
```bash
POST /api/pagos
Content-Type: application/json

{
  "alumno_id": 1,
  "mes": 3,
  "anio": 2026,
  "forma_pago_id": 1,
  "fecha_pago": "25/01/2026"
}
```

### Response (422 Unprocessable Entity)
```json
{
  "message": "The fecha pago field must match the format Y-m-d.",
  "errors": {
    "fecha_pago": [
      "La fecha de pago debe tener el formato Y-m-d (ej: 2026-01-25)."
    ]
  }
}
```

---

## Ejemplo 5: Error - Fecha de pago faltante

### Request (sin fecha_pago)
```bash
POST /api/pagos
Content-Type: application/json

{
  "alumno_id": 1,
  "mes": 3,
  "anio": 2026,
  "forma_pago_id": 1
}
```

### Response (Exitoso - usa fecha actual por defecto)
```json
{
  "success": true,
  "message": "Pago registrado exitosamente.",
  "data": {
    "id": 4,
    "alumno_id": 1,
    "plan_id": 2,
    "regla_primer_pago_id": null,
    "mes": 3,
    "anio": 2026,
    "monto_base": 500.00,
    "porcentaje_aplicado": 100.00,
    "monto_final": 500.00,
    "forma_pago_id": 1,
    "fecha_pago": "2026-01-25",
    "observaciones": null,
    "estado": "pagado",
    "created_at": "2026-01-25T11:00:00.000000Z",
    "updated_at": "2026-01-25T11:00:00.000000Z"
  }
}
```

**Nota:** Si no se envía `fecha_pago`, el sistema usa la fecha actual por defecto.

---

## Ejemplo 6: Caso Real - Pago Atrasado

### Escenario
- El alumno debía pagar el mes de enero
- El pago se realizó el 10 de febrero (con atraso)
- Se registra en el sistema el 10 de febrero

### Request
```bash
POST /api/pagos
Content-Type: application/json

{
  "alumno_id": 2,
  "mes": 1,
  "anio": 2026,
  "forma_pago_id": 3,
  "fecha_pago": "2026-02-10",
  "observaciones": "Pago atrasado de enero, realizado en febrero"
}
```

### Response
```json
{
  "success": true,
  "message": "Pago registrado exitosamente.",
  "data": {
    "id": 5,
    "alumno_id": 2,
    "plan_id": 4,
    "regla_primer_pago_id": null,
    "mes": 1,
    "anio": 2026,
    "monto_base": 500.00,
    "porcentaje_aplicado": 100.00,
    "monto_final": 500.00,
    "forma_pago_id": 3,
    "fecha_pago": "2026-02-10",
    "observaciones": "Pago atrasado de enero, realizado en febrero",
    "estado": "pagado",
    "created_at": "2026-02-10T09:15:00.000000Z",
    "updated_at": "2026-02-10T09:15:00.000000Z"
  }
}
```

**Análisis:**
- `mes: 1, anio: 2026` → Corresponde al mes de enero
- `fecha_pago: "2026-02-10"` → Se pagó en febrero
- `observaciones` explica el atraso
- `created_at` coincide con `fecha_pago` porque se registró el mismo día

---

## Ejemplo 7: Consultar Historial con fecha_pago

### Request
```bash
GET /api/alumnos/1/pagos
```

### Response
```json
{
  "success": true,
  "data": [
    {
      "id": 4,
      "mes": 3,
      "anio": 2026,
      "monto_final": 500.00,
      "fecha_pago": "2026-01-25",
      "observaciones": null,
      "forma_pago": {
        "id": 1,
        "nombre": "Efectivo"
      }
    },
    {
      "id": 2,
      "mes": 2,
      "anio": 2026,
      "monto_final": 500.00,
      "fecha_pago": "2026-02-15",
      "observaciones": null,
      "forma_pago": {
        "id": 2,
        "nombre": "Débito"
      }
    },
    {
      "id": 1,
      "mes": 1,
      "anio": 2026,
      "monto_final": 350.00,
      "fecha_pago": "2026-01-20",
      "observaciones": "Pago inicial - 50% descuento por inscripción tardía",
      "regla_primer_pago": {
        "id": 2,
        "nombre": "Segunda quincena (16-23)",
        "porcentaje": 70.00
      }
    }
  ]
}
```

---

## Resumen de Campos

| Campo | Tipo | Requerido | Ejemplo | Descripción |
|-------|------|-----------|---------|-------------|
| `alumno_id` | integer | ✅ Sí | 1 | ID del alumno |
| `mes` | integer | ✅ Sí | 1 | Mes del pago (1-12) |
| `anio` | integer | ✅ Sí | 2026 | Año del pago |
| `forma_pago_id` | integer | ✅ Sí | 1 | Forma de pago |
| `fecha_pago` | date | ✅ Sí | "2026-01-20" | Fecha real del pago |
| `observaciones` | string | ❌ No | "Pago inicial" | Notas adicionales |
| `porcentaje_manual` | float | ❌ No | 50.00 | Override manual |
| `regla_primer_pago_id` | integer | ❌ No | 2 | Regla específica |

---

## JavaScript/Frontend Examples

### Ejemplo con fetch
```javascript
async function registrarPago(alumnoId, mes, anio, formaPagoId, fechaPago, observaciones = null) {
  const response = await fetch('/api/pagos', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      alumno_id: alumnoId,
      mes: mes,
      anio: anio,
      forma_pago_id: formaPagoId,
      fecha_pago: fechaPago,
      observaciones: observaciones
    })
  });

  return await response.json();
}

// Uso
const resultado = await registrarPago(
  1,
  1,
  2026,
  1,
  '2026-01-20',
  'Pago inicial con descuento'
);

console.log(resultado.data);
```

### Ejemplo con axios
```javascript
import axios from 'axios';

async function registrarPago(data) {
  try {
    const response = await axios.post('/api/pagos', {
      alumno_id: data.alumnoId,
      mes: data.mes,
      anio: data.anio,
      forma_pago_id: data.formaPagoId,
      fecha_pago: data.fechaPago, // Formato: YYYY-MM-DD
      observaciones: data.observaciones || null
    });

    return response.data;
  } catch (error) {
    console.error('Error al registrar pago:', error.response.data);
    throw error;
  }
}

// Uso
const pago = await registrarPago({
  alumnoId: 1,
  mes: 2,
  anio: 2026,
  formaPagoId: 1,
  fechaPago: '2026-02-15',
  observaciones: 'Pago puntual'
});
```

---

## Tips de Uso

### 1. Usar fecha_pago para la lógica de negocio
```javascript
// ❌ Incorrecto
const fechaDelPago = pago.created_at;

// ✅ Correcto
const fechaDelPago = pago.fecha_pago;
```

### 2. Formato de fecha siempre Y-m-d
```javascript
// ❌ Incorrecto
fecha_pago: "25/01/2026"
fecha_pago: "Jan 25, 2026"

// ✅ Correcto
fecha_pago: "2026-01-25"
```

### 3. Observaciones para contexto
```javascript
// Buenas prácticas
observaciones: "Pago atrasado - mora del 10%"
observaciones: "Descuento especial por convenio empresa"
observaciones: "Pago parcial - saldo pendiente"

// Evitar
observaciones: "ok"
observaciones: ""
```

---

**Versión:** Pagos v1
**Última actualización:** 2026-01-25
