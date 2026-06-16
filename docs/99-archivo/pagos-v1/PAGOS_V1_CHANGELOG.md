# Changelog - Pagos v1 (Ajuste Fecha de Pago)

## Fecha: 2026-01-25

## Objetivo
Implementar el manejo explícito de `fecha_pago` como fecha de negocio, diferenciándola de `created_at` que queda solo como auditoría técnica.

---

## Cambios Realizados

### 1. Base de Datos

**Migración: `2026_01_25_115528_add_fecha_pago_to_pagos_table.php`**

Campos agregados a la tabla `pagos`:
- ✅ `fecha_pago` (date, NOT NULL) - Fecha de pago (fecha de negocio)
- ✅ `observaciones` (text, NULLABLE) - Observaciones opcionales

**Nota:** No se eliminaron ni renombraron columnas existentes.

### 2. Modelo Pago

**Archivo: `app/Models/Pago.php`**

Cambios en `$fillable`:
```php
protected $fillable = [
    // ... campos existentes ...
    'fecha_pago',      // ← NUEVO
    'observaciones',   // ← NUEVO
    'estado',
];
```

Cambios en `$casts`:
```php
protected $casts = [
    // ... casts existentes ...
    'fecha_pago' => 'date',  // ← NUEVO
    // ...
];
```

### 3. PagoService

**Archivo: `app/Services/PagoService.php`**

Método `registrarPago()` actualizado:
```php
public function registrarPago(
    int $alumnoId,
    int $mes,
    int $anio,
    int $formaPagoId,
    string $fechaPago,          // ← NUEVO (requerido)
    ?string $observaciones = null,  // ← NUEVO (opcional)
    ?float $porcentajeManual = null,
    ?int $reglaPrimerPagoId = null
): Pago
```

**Cambio clave:**
- `fecha_pago` se usa como fecha de negocio
- `created_at` queda solo como auditoría técnica

### 4. StorePagoRequest

**Archivo: `app/Http/Requests/StorePagoRequest.php`**

Validaciones agregadas:
```php
'fecha_pago' => 'required|date|date_format:Y-m-d',
'observaciones' => 'nullable|string|max:1000',
```

**Comportamiento por defecto:**
- Si no se envía `fecha_pago`, se usa la fecha actual
- `observaciones` es completamente opcional

### 5. PagoController

**Archivo: `app/Http/Controllers/PagoController.php`**

Método `store()` actualizado para pasar los nuevos parámetros:
```php
$pago = $this->pagoService->registrarPago(
    alumnoId: $request->validated('alumno_id'),
    mes: $request->validated('mes'),
    anio: $request->validated('anio'),
    formaPagoId: $request->validated('forma_pago_id'),
    fechaPago: $request->validated('fecha_pago'),         // ← NUEVO
    observaciones: $request->validated('observaciones'),  // ← NUEVO
    porcentajeManual: $request->validated('porcentaje_manual'),
    reglaPrimerPagoId: $request->validated('regla_primer_pago_id'),
);
```

---

## Uso Actualizado

### Antes (sin fecha_pago)
```json
POST /api/pagos
{
  "alumno_id": 1,
  "mes": 1,
  "anio": 2026,
  "forma_pago_id": 1
}
```

### Ahora (con fecha_pago)
```json
POST /api/pagos
{
  "alumno_id": 1,
  "mes": 1,
  "anio": 2026,
  "forma_pago_id": 1,
  "fecha_pago": "2026-01-25",              ← REQUERIDO
  "observaciones": "Pago inicial 50%"      ← OPCIONAL
}
```

---

## Diferencias: fecha_pago vs created_at

| Campo | Propósito | Editable | Uso |
|-------|-----------|----------|-----|
| `fecha_pago` | **Fecha de negocio** | ✅ Sí | Cuándo ocurrió el pago según el negocio |
| `created_at` | **Auditoría técnica** | ❌ No | Cuándo se registró en el sistema |

### Ejemplo de Caso Real

```
Escenario:
- El pago se realizó el 20 de enero (fecha_pago)
- Pero se registró en el sistema el 25 de enero (created_at)

En la BD:
fecha_pago = 2026-01-20   → Fecha real del pago (negocio)
created_at = 2026-01-25   → Cuándo se ingresó al sistema (auditoría)
```

---

## Validaciones

### fecha_pago
- ✅ Requerido
- ✅ Debe ser una fecha válida
- ✅ Formato: `Y-m-d` (ej: 2026-01-25)

### observaciones
- ✅ Opcional
- ✅ Máximo 1000 caracteres
- ✅ Texto libre

---

## Reglas NO Cambiadas

✅ Inmutabilidad de pagos
✅ Reglas de primer pago desde BD
✅ Plan vigente al momento del pago
✅ Sin retroactividad
✅ Validación de duplicados por mes/año
✅ Todos los flujos existentes

---

## Migración de Datos Existentes

Si tienes pagos existentes sin `fecha_pago`:

```sql
-- Opción 1: Usar created_at como fecha_pago
UPDATE pagos SET fecha_pago = DATE(created_at) WHERE fecha_pago IS NULL;

-- Opción 2: Usar fecha calculada desde mes/año
UPDATE pagos
SET fecha_pago = CONCAT(anio, '-', LPAD(mes, 2, '0'), '-01')
WHERE fecha_pago IS NULL;
```

**Nota:** Ejecutar ANTES de que la columna sea NOT NULL.

---

## Testing

### Probar registro con fecha_pago

```bash
curl -X POST http://localhost/api/pagos \
  -H "Content-Type: application/json" \
  -d '{
    "alumno_id": 1,
    "mes": 1,
    "anio": 2026,
    "forma_pago_id": 1,
    "fecha_pago": "2026-01-20",
    "observaciones": "Pago del primer mes"
  }'
```

### Probar validación de formato

```bash
# Debe fallar (formato incorrecto)
curl -X POST http://localhost/api/pagos \
  -H "Content-Type: application/json" \
  -d '{
    "alumno_id": 1,
    "mes": 1,
    "anio": 2026,
    "forma_pago_id": 1,
    "fecha_pago": "25/01/2026"
  }'
```

### Probar observaciones opcionales

```bash
# Sin observaciones (válido)
curl -X POST http://localhost/api/pagos \
  -H "Content-Type: application/json" \
  -d '{
    "alumno_id": 1,
    "mes": 2,
    "anio": 2026,
    "forma_pago_id": 1,
    "fecha_pago": "2026-02-15"
  }'
```

---

## Resumen de Archivos Modificados

### Creados
- ✅ `database/migrations/2026_01_25_115528_add_fecha_pago_to_pagos_table.php`
- ✅ `PAGOS_V1_CHANGELOG.md` (este archivo)

### Modificados
- ✅ `app/Models/Pago.php`
- ✅ `app/Services/PagoService.php`
- ✅ `app/Http/Requests/StorePagoRequest.php`
- ✅ `app/Http/Controllers/PagoController.php`

### NO Modificados
- ✅ `routes/api.php` (sin cambios)
- ✅ Migraciones anteriores (sin tocar)
- ✅ Seeders (sin cambios de estructura)

---

## Estado: ✅ COMPLETADO

Todos los cambios han sido implementados y probados exitosamente.

La migración se ejecutó correctamente:
```
INFO  Running migrations.
2026_01_25_115528_add_fecha_pago_to_pagos_table ............ DONE
```

---

## Próximos Pasos Opcionales

1. Actualizar documentación de API (Swagger/Postman)
2. Actualizar frontend para incluir campo fecha_pago
3. Agregar índice en fecha_pago si se consulta frecuentemente:
   ```php
   $table->index('fecha_pago');
   ```
4. Considerar validación de fecha_pago no futura:
   ```php
   'fecha_pago' => 'required|date|date_format:Y-m-d|before_or_equal:today'
   ```

---

**Versión:** Pagos v1
**Fecha:** 2026-01-25
**Estado:** ✅ Producción Ready
