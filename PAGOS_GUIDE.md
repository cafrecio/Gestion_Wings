# Guía de Sistema de Pagos - Wings

Esta guía explica cómo utilizar el sistema de pagos implementado en la aplicación Wings.

## Tabla de Contenidos

1. [Conceptos Clave](#conceptos-clave)
2. [Flujo de Primer Pago](#flujo-de-primer-pago)
3. [Flujo de Pagos Siguientes](#flujo-de-pagos-siguientes)
4. [Cambio de Plan](#cambio-de-plan)
5. [Endpoints API](#endpoints-api)
6. [Ejemplos de Uso](#ejemplos-de-uso)

---

## Conceptos Clave

### 1. Inmutabilidad de Pagos
- **Los pagos NO se pueden modificar** una vez creados
- Los montos quedan congelados al momento del registro
- No hay recálculos retroactivos

### 2. Plan Vigente
- El pago siempre usa el **plan activo al momento de registrar el pago**
- Si un alumno cambia de plan, solo afecta pagos futuros
- Los pagos anteriores mantienen el precio del plan original

### 3. Reglas de Primer Pago
- Las reglas vienen desde la tabla `reglas_primer_pago`
- Son **editables** desde la base de datos
- Se aplican según el día de alta del alumno
- Ejemplos:
  - Día 1-15: 100% del precio mensual
  - Día 16-22: 70% del precio mensual
  - Día 23-31: 40% del precio mensual

---

## Flujo de Primer Pago

### Caso A: Regla automática

Cuando el alumno se da de alta y existe UNA regla clara para el día:

```php
// El sistema aplica automáticamente la regla según fecha_alta
$pago = $pagoService->registrarPago(
    alumnoId: 1,
    mes: 1,
    anio: 2026,
    formaPagoId: 1,
    porcentajeManual: null,      // No se envía
    reglaPrimerPagoId: null      // No se envía
);

// Si el alumno se dio de alta el día 10:
// - Busca reglas donde dia_desde <= 10 AND dia_hasta >= 10
// - Aplica automáticamente el porcentaje de esa regla
```

### Caso B: Múltiples reglas o sin regla

Cuando hay conflicto o no existe regla para el día:

```php
try {
    $pago = $pagoService->registrarPago(
        alumnoId: 1,
        mes: 1,
        anio: 2026,
        formaPagoId: 1
    );
} catch (\Exception $e) {
    // Error: "Hay múltiples reglas aplicables..."
    // O: "No hay reglas aplicables..."

    // SOLUCIÓN 1: Seleccionar regla manualmente
    $reglas = $pagoService->obtenerReglasDisponibles($diaAlta);

    $pago = $pagoService->registrarPago(
        alumnoId: 1,
        mes: 1,
        anio: 2026,
        formaPagoId: 1,
        porcentajeManual: null,
        reglaPrimerPagoId: $reglas->first()->id  // Seleccionar una
    );

    // SOLUCIÓN 2: Ingresar porcentaje manual
    $pago = $pagoService->registrarPago(
        alumnoId: 1,
        mes: 1,
        anio: 2026,
        formaPagoId: 1,
        porcentajeManual: 70.00,    // Override manual
        reglaPrimerPagoId: null
    );
}
```

---

## Flujo de Pagos Siguientes

Los pagos después del primero siempre cobran **100% del precio mensual del plan vigente**:

```php
$pago = $pagoService->registrarPago(
    alumnoId: 1,
    mes: 2,
    anio: 2026,
    formaPagoId: 1
);

// Automáticamente:
// - Detecta que NO es primer pago
// - Aplica 100% del precio del plan activo
// - No necesita regla ni porcentaje manual
```

---

## Cambio de Plan

El alumno puede cambiar de grupo o frecuencia semanal antes de generar un pago:

```php
// Alumno actualmente tiene: Plan A (3 clases/semana, $500)
// Quiere cambiar a: Plan B (5 clases/semana, $800)

$nuevoPlan = $pagoService->cambiarPlan(
    alumnoId: 1,
    nuevoPlanId: 5  // ID del GrupoPlan nuevo
);

// Ahora registramos el pago
$pago = $pagoService->registrarPago(
    alumnoId: 1,
    mes: 3,
    anio: 2026,
    formaPagoId: 1
);

// El pago usará el precio del Plan B ($800)
// Los pagos anteriores siguen usando Plan A ($500)
```

### ⚠️ Importante: Sin Retroactividad

```php
// Pagos anteriores NO se recalculan
// Cada pago congela:
// - monto_base (precio del plan al momento del pago)
// - porcentaje_aplicado (100% o lo que corresponda)
// - monto_final (resultado inmutable)
```

---

## Endpoints API

### 1. Registrar Pago

```http
POST /api/pagos
Content-Type: application/json

{
  "alumno_id": 1,
  "mes": 1,
  "anio": 2026,
  "forma_pago_id": 1,
  "porcentaje_manual": 70.00,        // Opcional
  "regla_primer_pago_id": 2          // Opcional
}
```

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "message": "Pago registrado exitosamente.",
  "data": {
    "id": 1,
    "alumno_id": 1,
    "mes": 1,
    "anio": 2026,
    "monto_base": 500.00,
    "porcentaje_aplicado": 70.00,
    "monto_final": 350.00,
    "forma_pago_id": 1,
    "regla_primer_pago_id": 2,
    "estado": "pagado",
    "created_at": "2026-01-25T10:00:00.000000Z"
  }
}
```

**Respuesta con error (422):**
```json
{
  "success": false,
  "message": "Error al registrar el pago.",
  "error": "Ya existe un pago registrado para 1/2026."
}
```

---

### 2. Obtener Pagos de un Alumno

```http
GET /api/alumnos/1/pagos
```

**Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 2,
      "mes": 2,
      "anio": 2026,
      "monto_final": 500.00,
      "forma_pago": {
        "id": 1,
        "nombre": "Efectivo"
      }
    },
    {
      "id": 1,
      "mes": 1,
      "anio": 2026,
      "monto_final": 350.00,
      "regla_primer_pago": {
        "id": 2,
        "nombre": "Segunda quincena",
        "porcentaje": 70.00
      }
    }
  ]
}
```

---

### 3. Obtener Próximo Pago

```http
GET /api/alumnos/1/proximo-pago
```

**Respuesta (primer pago):**
```json
{
  "success": true,
  "data": {
    "mes": 1,
    "anio": 2026,
    "monto_estimado": null,
    "es_primer_pago": true,
    "plan_nombre": "Fútbol Avanzado",
    "clases_por_semana": 3
  }
}
```

**Respuesta (pago siguiente):**
```json
{
  "success": true,
  "data": {
    "mes": 3,
    "anio": 2026,
    "monto_estimado": 500.00,
    "es_primer_pago": false,
    "plan_nombre": "Fútbol Avanzado",
    "clases_por_semana": 3
  }
}
```

---

### 4. Obtener Reglas de Primer Pago

```http
GET /api/reglas-primer-pago/dia/10
```

**Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nombre": "Primera quincena",
      "dia_desde": 1,
      "dia_hasta": 15,
      "porcentaje": 100.00,
      "activo": true
    }
  ],
  "total": 1
}
```

---

### 5. Cambiar Plan

```http
POST /api/alumnos/1/cambiar-plan
Content-Type: application/json

{
  "plan_id": 5
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Plan cambiado exitosamente. Los pagos futuros usarán el nuevo plan.",
  "data": {
    "id": 2,
    "alumno_id": 1,
    "plan_id": 5,
    "fecha_desde": "2026-01-25",
    "fecha_hasta": null,
    "activo": true
  }
}
```

---

### 6. Verificar si Puede Pagar

```http
GET /api/alumnos/1/puede-pagar
```

**Respuesta (puede pagar):**
```json
{
  "success": true,
  "data": {
    "puede_pagar": true,
    "razon": null
  }
}
```

**Respuesta (no puede pagar):**
```json
{
  "success": true,
  "data": {
    "puede_pagar": false,
    "razon": "El alumno no tiene un plan activo asignado."
  }
}
```

---

## Ejemplos de Uso

### Ejemplo 1: Primer Pago Automático

```javascript
// Frontend - Alumno recién inscrito (día 5)
async function registrarPrimerPago(alumnoId) {
  try {
    const response = await fetch('/api/pagos', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        alumno_id: alumnoId,
        mes: 1,
        anio: 2026,
        forma_pago_id: 1
      })
    });

    const data = await response.json();

    if (data.success) {
      console.log('Pago registrado:', data.data);
      // El sistema aplicó automáticamente 100% (día 1-15)
    }
  } catch (error) {
    console.error('Error:', error);
  }
}
```

---

### Ejemplo 2: Primer Pago Manual

```javascript
// Frontend - Alumno con día de alta conflictivo
async function registrarPrimerPagoManual(alumnoId, diaAlta) {
  try {
    // 1. Obtener reglas disponibles
    const reglasResponse = await fetch(`/api/reglas-primer-pago/dia/${diaAlta}`);
    const reglasData = await reglasResponse.json();

    if (reglasData.total === 0) {
      // No hay reglas, ingresar porcentaje manual
      const response = await fetch('/api/pagos', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          alumno_id: alumnoId,
          mes: 1,
          anio: 2026,
          forma_pago_id: 1,
          porcentaje_manual: 50.00  // Decisión manual
        })
      });

      const data = await response.json();
      console.log('Pago con porcentaje manual:', data.data);

    } else if (reglasData.total > 1) {
      // Múltiples reglas, seleccionar una
      const reglaSeleccionada = reglasData.data[0]; // Usuario elige

      const response = await fetch('/api/pagos', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          alumno_id: alumnoId,
          mes: 1,
          anio: 2026,
          forma_pago_id: 1,
          regla_primer_pago_id: reglaSeleccionada.id
        })
      });

      const data = await response.json();
      console.log('Pago con regla seleccionada:', data.data);
    }
  } catch (error) {
    console.error('Error:', error);
  }
}
```

---

### Ejemplo 3: Flujo Completo con Cambio de Plan

```javascript
// Flujo: Alumno paga enero, cambia plan, paga febrero

async function flujoCompleto() {
  const alumnoId = 1;

  // 1. Verificar si puede pagar
  const verificacion = await fetch(`/api/alumnos/${alumnoId}/puede-pagar`);
  const puedeData = await verificacion.json();

  if (!puedeData.data.puede_pagar) {
    console.error('No puede pagar:', puedeData.data.razon);
    return;
  }

  // 2. Obtener próximo pago
  const proximoResponse = await fetch(`/api/alumnos/${alumnoId}/proximo-pago`);
  const proximoData = await proximoResponse.json();

  console.log('Próximo pago:', proximoData.data);
  // { mes: 1, anio: 2026, monto_estimado: null, es_primer_pago: true }

  // 3. Registrar primer pago
  const pago1 = await fetch('/api/pagos', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      alumno_id: alumnoId,
      mes: 1,
      anio: 2026,
      forma_pago_id: 1
    })
  });

  const pago1Data = await pago1.json();
  console.log('Primer pago registrado:', pago1Data.data);
  // monto_final: 350.00 (con descuento)

  // 4. Cambiar plan (de 3 clases/semana a 5 clases/semana)
  const cambio = await fetch(`/api/alumnos/${alumnoId}/cambiar-plan`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      plan_id: 5  // Nuevo plan
    })
  });

  const cambioData = await cambio.json();
  console.log('Plan cambiado:', cambioData.data);

  // 5. Registrar segundo pago (con nuevo plan)
  const pago2 = await fetch('/api/pagos', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      alumno_id: alumnoId,
      mes: 2,
      anio: 2026,
      forma_pago_id: 1
    })
  });

  const pago2Data = await pago2.json();
  console.log('Segundo pago registrado:', pago2Data.data);
  // monto_final: 800.00 (precio del nuevo plan, 100%)

  // 6. Verificar historial
  const historial = await fetch(`/api/alumnos/${alumnoId}/pagos`);
  const historialData = await historial.json();

  console.log('Historial de pagos:', historialData.data);
  // [
  //   { mes: 2, monto_final: 800.00 },  // Nuevo plan
  //   { mes: 1, monto_final: 350.00 }   // Plan anterior (INMUTABLE)
  // ]
}
```

---

## Validaciones Automáticas

### 1. No Duplicar Pagos
```php
// Intento de duplicar
$pagoService->registrarPago(1, 1, 2026, 1);  // OK
$pagoService->registrarPago(1, 1, 2026, 1);  // ❌ Exception: "Ya existe un pago..."
```

### 2. Requiere Plan Activo
```php
// Alumno sin plan activo
$pagoService->registrarPago(1, 1, 2026, 1);
// ❌ Exception: "El alumno no tiene un plan activo asignado."
```

### 3. Inmutabilidad de Montos
```php
$pago = Pago::find(1);
$pago->monto_final = 999.99;
$pago->save();
// ❌ Exception: "Los montos de un pago no pueden modificarse..."
```

---

## Resumen de Reglas de Negocio

✅ **Pagos son INMUTABLES** - No se recalculan nunca
✅ **Primer pago usa reglas editables** - No hardcodeadas
✅ **Plan vigente al momento del pago** - Define el precio
✅ **Sin retroactividad** - Cambios de plan no afectan pagos anteriores
✅ **Validación de duplicados** - No se puede pagar dos veces el mismo mes/año
✅ **Reglas desde BD** - Editables por administradores
✅ **Override manual permitido** - Para casos excepcionales
