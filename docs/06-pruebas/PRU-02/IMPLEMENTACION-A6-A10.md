# A6–A10 — Implementación en preparación

07/10/2026, Codex CyE. **No es entrega definitiva:** A8 A ya está elegida; faltan capturas completas y la propuesta visual restante de A10. Sin commit, push ni despliegue.

| Defecto | Elección de Carlos | Cambio aplicado | Estado |
|---|---|---|---|
| A6 | «Dejalo con el mismo formato que tiene originalmente y cambia solo el texto» | Modificar → Editar; formato, ubicación, condiciones e identificadores originales | Local; seis capturas después en propuesta |
| A7 | «A- NUNCA EN LA PUTA VIDA, NO CAMBIAR ESO NUNCA / B- OK espero que nadie le ponga un nombre tan largo al deporte ni al nivel» | Solo B: una tarjeta por fila; ocho datos, incluidos plan y celular; textos completos. Regla permanente en DESIGN-RULES.md | Local; capturas finales pendientes, opción B previa aprobada |
| A8 | «A8 Opcion A»; conservar distintivo de Profesores | Punto con estado; distintivo original intacto | Local; capturas finales pendientes |
| A9 | «los botones en el celular se alinean todos a la derecha» | Alineación móvil de acciones/interruptor; mismo componente y tamaño | Local; falta revisión visual |
| A10 | Aclarar período; «Mantengamos la consistencia visual» | Período explícito; íconos en movimiento | Local; falta captura completa y propuesta de Nuevo/destino |

## Alcance comprobado

- A6: `resources/views/clases/show.blade.php`, una palabra.
- A7: `resources/views/alumnos/index.blade.php` y 12 líneas de CSS exclusivo en `resources/css/app.css`; contenedor de tarjetas de una columna. Sin selector doble, sin modificar componentes compartidos ni controles de acceso.
- A7 conserva datos previos y muestra deporte, grupo, plan activo (clases/semana), DNI, celular, edad, tutor y teléfono del tutor. Singular «1 clase/semana»; plan/celular vacío con alternativa textual. Carga del plan en lote para la página, sin nueva consulta por tarjeta.
- Regla permanente: [DESIGN-RULES.md](../../03-diseno-ui/design-system/DESIGN-RULES.md). [Decisiones, imágenes y consumidores](PROPUESTA-A6-A10.md).

- A8/A9: Grupos, Niveles y Profesores; A7 Alumnos recibe la misma alineación móvil. Sin cambiar componentes compartidos ni distintivo coloreado de Profesores.
- A10: cashflow/index muestra período seleccionado; cashflow/movimiento recibe íconos en sus seis campos y tipo. Sin alterar controlador, fórmula, botones o JS existente.

## Verificado / pendiente

Verificado por lectura de código: A7 tiene una columna de tarjetas en todos los anchos; cero selectores doble, 12 líneas CSS nuevas subordinadas al listado, componentes de acciones conservados. Sintaxis de Alumnos correcta; build 14,91 s; vistas compilan y caché retirado. Entorno de comandos: APP_ENV=testing, DB_DATABASE=wings_testing_codex.

Verificación visual previa: opción B propuesta, ADMIN y OPERATIVO en escritorio/375, nombres largos, 21 alumnos; PROFESOR sin acceso. Capturas reales miradas por Codex, [visor](evidencia/a6-a10/visor-a7.html). No equivale a la revisión final después de aplicar la elección.

Pendiente: capturas finales A7 posteriores a la elección (Chrome no disponible: «User unavailable», dos consultas); capturas completas A7/A8/A9/A10 (Carlos rechazó las que ocultan controles), propuesta/elección restante de A10; suite completa del paquete final; registro HECHO/a_verificar por cada entrega; autorización de publicación reunida con la propuesta completa y commit/push. No se agregó una prueba permanente por este cambio visual reversible.

El autor no cierra ninguno; el verificador se asigna al entregar. Producción, servidor y base del club intactos.

Control posterior a la revisión: diez respuestas HTTP 200; período anual/mensual y seis etiquetas con íconos comprobados. Sintaxis de seis vistas, compilación Blade y build 10,22 s correctos. [Evidencia HTTP](evidencia/a6-a10/comprobacion-elecciones-http.json). Alineación móvil inferida del CSS generado; pendiente medir y mirar el resultado real en navegador.
