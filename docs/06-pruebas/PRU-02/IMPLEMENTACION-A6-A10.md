# A6–A10 — Entrega verificada

08/10/2026 · Codex CyE. **Los cinco cerrados.** A6–A9 verificados visualmente por Carlos (§6a); A10 también verificado funcionalmente por Claude.

| Tarea | Resultado y aprobación | Evidencia |
|---|---|---|
| A6 | Modificar → Editar, formato original. Carlos: «Dejalo con el mismo formato que tiene originalmente y cambia solo el texto» | [Seis capturas y alcance](PROPUESTA-A6-A10.md) |
| A7 | Una tarjeta por fila; plan/celular. Cobrar/Editar alineados, Ver con Nuevo, solo celular. Carlos: «Muy buen trabajo, me gusta» | [Capturas finales](evidencia/a6-a10/visor-a7-columnas.html) |
| A8 | Estados útiles en Grupos, Niveles y Profesores; distintivo de Profesores conservado. Carlos: «A-8 APROBADO» | [Tres pantallas](evidencia/a6-a10/visor-a8-grupos.html) |
| A9 | Acciones a derecha solo celular; último botón/Nuevo x=224–320, interruptor termina en x=320. Carlos: «Perfecto, APROBADO A-9 Entonces» | [Capturas](evidencia/a6-a10/visor-a9.html) |
| A10 | Día/Semana/Mes/Año, resultado sin saldo inicial, Nuevo y su destino. Carlos: «Me gusta, A10 Aprobado»; lógica verifica Claude | [Aplicado](evidencia/a6-a10/visor-a10-aplicado.html) · [Verificación](VERIFICACION-A10.md) |

## Verificación final

- Suite Codex: **508 aprobadas / 2 omitidas**, 4077 aserciones, 423,14 s; 510 pruebas. [Salida](evidencia/a6-a10/suite-a10-limpiar-2026-10-08.txt).
- A10: diez pruebas, 110 aserciones. Nueva regresión del href de Limpiar: roja antes, verde después.
- Claude: 432 combinaciones/442 pedidos con cálculo propio; segunda corrida 19 pruebas/6819 aserciones y Chrome 45 comprobaciones sin fallos, 18 capturas propias. Primera devolución conservada intacta.
- Sintaxis y compilación Blade correctas; build final 38,28 s. Escenario ficticio Codex 4/15 verde, 8,72 s.
- 65 capturas finales A7/A8/A9/A10 renovadas tras el build; A6 conserva las seis de su cambio de una palabra. Escritorio/375, roles y estados; login de calibración. [Control de integridad](evidencia/a6-a10/control-entrega.json).

## Alcance y entrega

A6 solo texto; A7 listado de Alumnos y carga de su plan, CSS exclusivo. A8/A9 tres listados y sus pies móviles; componentes compartidos conservados. A10 solo consulta de Cashflow y títulos/acciones: no modifica registro, permisos ni saldo disponible. [Detalle A10](IMPLEMENTACION-A10-PERIODOS.md).

El selector, fechas y resultados están verificados. El apartado de saldo acumulado de Reportes continúa fuera de A10 (POS-01).

**A32 en entrega separada:** Carlos aprobó el candado «A-32 OK»; aprobó después la corrección de nombres y Rol, A32 cerrado; [aplicación y controles](IMPLEMENTACION-A32.md). Toggle compartido y permisos intactos.

Entrega de código y evidencia validada; sin despliegue. [Antecedente íntegro de esta entrega](../../99-archivo/pruebas/2026-10-08/IMPLEMENTACION-A6-A10-ANTES-CIERRE.md.txt); conserva los cortes anteriores como texto, con sus rutas relativas originales.
