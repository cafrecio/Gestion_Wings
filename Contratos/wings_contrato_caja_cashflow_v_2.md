# Wings – Contrato Caja–Cashflow V2

## 1. Objetivo

Definir de forma **clara, corta y no ambigua** cómo funciona el manejo de **Caja operativa** y **Cashflow real**, separando responsabilidades, visibilidad y efectos contables.

Este contrato es la **fuente de verdad funcional** para desarrollo y validación.

---

## 2. Principios rectores

1. Caja ≠ Cashflow  
2. El usuario operativo **no conoce la realidad económica total**  
3. El administrador **no necesita abrir ni cerrar caja**  
4. Todo movimiento pertenece a un **rubro y subrubro**  
5. Nada sensible se hardcodea

---

## 3. Roles

### Usuario OPERATIVO
- Opera el día a día
- Abre y cierra la jornada
- Registra ingresos y egresos **permitidos**
- No ve saldos reales
- No ve sueldos, alquileres, utilidades ni aportes

### Usuario ADMIN
- Define rubros y subrubros
- Ve cashflow completo y actual
- Valida cierres de caja
- Registra movimientos económicos reales

---

## 4. Rubros y Subrubros

### Rubro
- Define la **naturaleza contable**
- Tipo: `INGRESO | EGRESO`
- Incluye una observación obligatoria de uso

Ejemplo:
- Rubro: *Sueldos* (EGRESO)
- Observación: Pagos al personal docente y administrativo


### Subrubro
- Es lo que el usuario selecciona
- Hereda el tipo del rubro (no puede contradecirlo)
- Define quién puede usarlo

Ejemplo:
- Subrubro: Sueldo Patín – Romina (ADMIN)
- Subrubro: Limpieza (OPERATIVO)

---

## 5. Caja Operativa

### Definición
La caja representa la **operatoria diaria**, no la realidad económica total.

### Características
- Existe **un cierre de caja por día**, no por tipo de caja
- El cierre es obligatorio si hubo operación
- El usuario operativo registra:
  - Ingresos operativos
  - Egresos menores permitidos

### Exclusiones explícitas
No pasan por caja:
- Sueldos
- Alquileres
- Intereses
- Retiros de utilidades
- Aportes de capital

---

## 6. Cierre de Caja

### Definición
Documento diario que resume **todo lo operado en el día**.

### Contenido mínimo
- Fecha
- Usuario operativo
- Resumen por medio:
  - Ingresos electrónicos (Banco, MP, etc.)
  - Movimientos en efectivo
- Detalle expandible de movimientos

### Flujo
1. El operativo carga movimientos
2. Revisa consistencia
3. Cierra el día
4. El cierre queda **pendiente de validación**

---

## 7. Validación Administrativa

El ADMIN valida el cierre comparando:
- Saldo real previo (cashflow)
- + movimientos del día
- vs saldo real observado (apps, efectivo)

Si coincide → **valida**
Si no coincide → **rechaza / ajusta**

---

## 8. Cashflow

### Definición
Visión **real, global y actual** del dinero.

### Características
- Solo visible para ADMIN
- No depende de cierres de caja
- Refleja la realidad económica

### Incluye
- Sueldos
- Alquileres
- Intereses
- Retiros de utilidades
- Aportes de capital
- Ajustes

---

## 9. Relación Caja ↔ Cashflow

- Los movimientos cargados por el **ADMIN**:
  - No pertenecen a ningún cierre de caja
  - No fuerzan apertura ni cierre operativo
  - Impactan **directamente** en el cashflow

- Los cierres de caja:
  - No modifican el cashflow automáticamente
  - Son insumo de control para el ADMIN

- La caja sirve para **control operativo diario**
- El cashflow sirve para **control económico real**

---

## 10. Estado del contrato

- Versión: V2
- Reemplaza: Wings – Contrato Caja–Cashflow V1
- Estado: **Congelado**
- Cambios futuros → Anexos versionados
