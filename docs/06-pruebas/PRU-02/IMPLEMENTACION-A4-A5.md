# A4/A5 — Entrega de Codex CAB — 05/10/2026

**Hecho (Cx), a revisar por otro agente. No cerrado ni desplegado.**

## Decisiones y alcance

- Carlos resolvió el cierre: «Si, ponelo como hecho (Cx), a revisar».
- Después de ver imágenes aprobó el aviso y «solo los profesores del deporte de la clase, profesores activos».
- [Propuesta visual conservada](../../05-pendientes/maqueta-a4-a5/index.html) y [capturas reales de entrega](capturas-a4-a5/index.html).
- Solo base descartable `wings_testing_codex`, con datos sintéticos. Sin base del club ni servidor tocados.
- Sin CSS ni componentes compartidos modificados; no cambia el rol que puede administrar clases.

## Cambios reales

**A4:** alta y edición de alumno comparten un resumen de errores antes de los campos, enfocado y visible sin buscarlo al pie. Enlaces al dato a corregir. Se conserva la entrada rechazada y se restaura el plan antes de consultar inscripción/cuota. También se muestran arriba los errores de validación del navegador. Menú y Cancelar preguntan si hay cambios sin guardar; cerrar/recargar usa la advertencia nativa, como Configuración. Guardar no activa una advertencia de salida cuando el envío es válido. No cambió la obligatoriedad del tutor.

**A5:** alta filtra por el deporte del grupo y desmarca/deshabilita selecciones incompatibles al cambiarlo. Edición y selector de la ficha reciben solo profesores activos de ese deporte. El servidor valida alta única/serie, edición hoy/futuro y reasignación; rechaza un formulario adulterado sin cambios parciales. No borra asignaciones históricas por desactivar un profesor ni altera las reglas retroactivas/liquidaciones.

JavaScript en `resources/js/alumnos-form.js` y `clases-form.js`, cargados por Vite. Se extrajeron los tres bloques inline existentes; presupuesto CSP 19 → 16, sin cambiar la política ni los 10 atributos de evento tolerados.

**Coordinación:** el backend inicial de A5, el borrador de pruebas y la maqueta entraron en `8869263` durante el trabajo concurrente de Claude en A13/B1. Esta entrega completa A4/A5, incorpora la condición de activo, mueve las pruebas a la suite y aporta las vistas/módulos/capturas. No atribuye a Codex los cambios A13/B1 ni los revierte.

## Pruebas automatizadas

- Antes de corregir: 9 pruebas de regresión, **7 fallaban y 2 pasaban (27 aserciones)**; el primer armado tuvo errores de fixture, descartados antes de medir la regresión.
- Final específica: **11 aprobadas / 39 aserciones**. Cubre resumen de alta/edición, conservación tras rechazo, profesor ajeno/inactivo, rollback de serie/edición/reasignación y asignación válida.
- Suite completa en `wings_testing_codex`: **437 aprobadas, 1 omitida, 2949 aserciones; 129,48 s**, repetida después de la última corrección documental. Total: 438. La omitida es `CapturaFichaAnularTest`, que requiere `WINGS_CAPTURAS=1` y no es una falla.
- PHP: controlador, prueba y fixture sin errores de sintaxis. Módulos JS válidos; build de Vite correcto. Blade compila y se limpia en directorio de caché propio.

## Recorrido real de navegador

- Alta y edición: menor sin tutor devuelve ambos errores arriba y conserva los valores. Plan restaurado. A 375, el aviso de edición ocupa aproximadamente y=237–410, dentro del primer viewport de 812; sin desborde horizontal.
- Alta de clase: sin grupo no ofrece casillas; Patín ofrece Ana, Fútbol ofrece Luis; ambos ficticios y activos. Cambiar Patín → Fútbol desmarca y deshabilita Ana. El inactivo no se ofrece.
- Edición y ficha: selector de profesores solo del deporte correspondiente, conservando la selección compatible. Capturas en escritorio y a 375 de las cinco pantallas: diez archivos JPG.
- La captura móvil de alta de alumno muestra el formulario antes del envío; la de edición de clase quedó con el menú abierto. Se conserva como observación real, no como aprobación de la pantalla completa. La edición de alumno a 375 sí muestra el aviso sin menú superpuesto. El revisor debe repetir alta/edición de clase a 375 con el menú cerrado.
- **Límite de comprobación:** Chrome expuso un `confirm` al modificar el nombre y pulsar Clases. El control del navegador se bloqueó al resolver Cancelar; no se certifica aquí la retención tras cancelar ni cerrar/recargar. El aviso está implementado; el revisor debe repetir esos recorridos y comprobar que aceptar sale sin doble pregunta, cancelar conserva los datos y salir sin cambios no pregunta.

## Pase para verificación independiente

Otro agente revisa código y pantalla: guardar inválido/válido, conservar plan/datos, aviso de menú/Cancelar/cierre, profesor ajeno/inactivo mediante formulario adulterado y profesor compatible en alta, edición y ficha. Repetir escritorio y 375. Solo después puede marcar A4/A5 CERRADOS en ambos seguimientos. No desplegar ni probar contra `gestion_wings`.

El fixture `a4-a5-fixture.php` se niega a correr fuera de CLI / testing / wings_testing_codex. Es ayuda local, no seeder ni herramienta de producción.
