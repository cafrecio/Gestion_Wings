# Resultado de prueba humana V1 — ejecución parcial

**Fecha:** 30/08/2026  
**Ejecutor:** Codex CAB  
**Ambiente:** `http://gestion-wings`, base descartable `wings_test`  
**Estado:** interrumpida por cambio de cuenta de Codex, no por un bloqueo de Wings

## Estado para retomar

La cadena se detuvo después de A2, tal como pidió Carlos. La sesión usada era la
cuenta administradora de prueba y la última pantalla abierta fue **Deportes**.

Estado visible dejado en el ambiente:

- Se creó `Hockey`, tipo de liquidación **Por hora**.
- La pantalla de Deportes muestra exactamente **3** registros: `Fútbol`, `Hockey`
  y `Patín`.
- No se creó ningún profesor, grupo, plan, usuario operativo/profesor, alumno,
  cobro, caja ni clase durante esta ejecución.
- El próximo paso es **A3 · Profesores**, empezando por Jorge Mitre.

## Bloque A — El administrador arma el club

### A1 · Un deporte más — PASA

- Se ingresó a **Deportes → Nuevo** y se cargó `Hockey` con liquidación **Por hora**.
- El sistema volvió al listado con el mensaje `Deporte creado correctamente.`.
- El contador pasó de 2 a 3 y `Hockey` apareció en el listado.
- Escritura observada: sí, únicamente el deporte esperado.

### A2 · Deporte repetido — PASA

Se probaron por pantalla las tres variantes exigidas:

| Nombre ingresado | Resultado |
|---|---|
| `Patín` | Rechazado con `Ya existe un deporte con ese nombre.` |
| `patín` | Rechazado con `Ya existe un deporte con ese nombre.` |
| `PATIN` | Rechazado con `Ya existe un deporte con ese nombre.` |

- Tipo de respuesta: mensaje de validación dentro del formulario; no hubo pantalla
  de error.
- Escritura pese al rechazo: no. Al volver al listado seguían figurando exactamente
  3 deportes: `Fútbol`, `Hockey` y `Patín`.

### A3 · Profesores — NO SE PUDO

No ejecutado por el cambio de cuenta de Codex. Wings no presentó un bloqueo.

### A4 · Profesor con DNI repetido — NO SE PUDO

Depende de A3; no ejecutado.

### A5 · Profesor sin dirección — NO SE PUDO

Depende de A3; no ejecutado.

### A6 · Grupos — NO SE PUDO

Depende de los profesores de A3; no ejecutado.

### A7 · Grupo repetido — NO SE PUDO

Depende de A6; no ejecutado.

### A8 · Planes — NO SE PUDO

Depende de A6. No se llegó a la carga dentro de cada grupo.

### A9 · Precio inválido — NO SE PUDO

Depende de A8; no ejecutado.

### A10 · Usuarios — NO SE PUDO

Depende del profesor P1 de A3; no ejecutado.

### A11 · Email repetido — NO SE PUDO

Depende de A10; no ejecutado.

### A12 · Alumnos — NO SE PUDO

Depende de grupos y planes; no ejecutado.

### A13 · Alumnos que tienen que fallar — NO SE PUDO

Depende de A12; no ejecutado.

## Bloque B — El operativo trabaja

### B1 · Accesos prohibidos — NO SE PUDO

El usuario operativo todavía no fue creado porque A10 no se ejecutó.

### B2 · Cobrar la primera cuota — NO SE PUDO

Depende de A10 y A12; no ejecutado.

### B3 · Cobro dejando deuda anterior — NO SE PUDO

Depende de los datos y cobros anteriores; no ejecutado.

### B4 · Cancelar un cobro — NO SE PUDO

Depende de A12 y de la operación previa; no ejecutado.

### B5 · Cerrar y validar la caja — NO SE PUDO

Depende de B2/B4; no ejecutado.

### B6 · Clases — NO SE PUDO

Depende de profesores, grupos y alumnos; no ejecutado.

## Bloque C — El profesor

### C1 · Lo que ve y lo que no — NO SE PUDO

El usuario profesor todavía no fue creado porque A10 no se ejecutó.

### C2 · Tomar asistencia — NO SE PUDO

Depende de B6; no ejecutado.

### C3 · Corregir la asistencia — NO SE PUDO

Depende de C2; no ejecutado.

### C4 · Alumno de otro grupo — NO SE PUDO

Depende de A12 y B6; no ejecutado.

## Bloque D — Cierre

### D1 · Liquidación del profesor — NO SE PUDO

Depende de toda la cadena anterior; no ejecutado.

### D2 · Recuento final — NO SE PUDO

La carga funcional quedó incompleta, por lo que el recuento final todavía no es
representativo.

## Lo que no se pudo probar y por qué

No se probaron A3–D2 porque la ejecución se interrumpió para continuar desde otra
cuenta de Codex. No hubo un error funcional de Wings ni un eslabón roto. Para
preservar la cadena, no se salteó ningún paso ni se cargaron datos por fuera del
navegador.

La próxima ejecución debe conservar A1 y A2 como terminadas y retomar directamente
en **A3 · Profesores** sobre la misma base `wings_test`.
