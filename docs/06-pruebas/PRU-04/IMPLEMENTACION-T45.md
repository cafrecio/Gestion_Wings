# T45 — Vigencia mensual de la forma de liquidar

28/10/2026 · Codex CyE · implementación local, pendiente de Claude y aspecto de Carlos. Sin despliegue.

DU-42 / CAT-002. ADMIN programa otro tipo solo sin liquidaciones ABIERTAS del deporte. El servidor enumera número, profesora y período de las que bloquean. Nombre solo sigue permitido, también con una programación pendiente.

Se eligió una tabla de vigencias `deporte_liquidacion_vigencias`, con tipo, primer día del mes siguiente, autor y fecha de registro. Es acumulativa: otra elección para la misma fecha agrega una versión; la última prevalece. Reenviar el mismo tipo no duplica ni posterga. La columna original del deporte conserva la base anterior a la primera vigencia; el acceso público al tipo resuelve el mes actual. No se reconstruyen cambios anteriores sin evidencia.

Liquidación y vista previa resuelven el tipo por mes/año solicitado. La creación y la programación bloquean la fila del deporte para serializarse; después se comprueban abiertas. Las cerradas y pagadas ya guardaban tipo y tarifa propios: se conserva esa protección. Los estimados de Sueldos aplican la misma vigencia sin reemplazar una liquidación existente ni mover el corte anterior.

Falta de porcentaje: aviso con nombres al programar, aviso por profesora en Nueva liquidación y rechazo del servidor al generar/previsualizar. El porcentaje permanece NULL hasta que se carga. El JavaScript del formulario de profesores borraba el valor del tipo no seleccionado; ahora conserva ambos y muestra los dos campos cuando hay un cambio pendiente, permitiendo cargar el próximo antes de su vigencia.

La interfaz usa el período seleccionado, también al cambiarlo sin recargar: octubre sigue Por hora, noviembre pasa a Por comisión y muestra el aviso correspondiente. Sin JavaScript incrustado ni cambios de CSS.

Verificación:

- 14 pruebas propias / 106 aserciones verdes (13/102 funcionales y 1/4 de esquema anterior): bloqueo identificado, vigencia/autor, HORA actual por $5.000, COMISION siguiente sobre cobro real de $10.000 ×20% = $2.000, cerradas/pagadas byte por byte en atributos, nombre solo, ausencia de porcentaje, roles forzados, reenvíos, cambios sucesivos, cambio de año, estimados y carga anticipada conservando tarifa.
- Área existente: Hora por duración, Comisión histórica, Nueva liquidación T87, Sueldos y rendimiento T79 aprobados. La primera corrida conjunta tuvo solo el caso propio de estimado en rojo por la fecha de cobertura del fixture; corregido y 13/102 verdes después.
- Chrome real: 12 PNG a 1366/marco375 de login, deporte programado/bloqueado, Nueva liquidación y formulario de profesores Crear/Editar. Datos sintéticos y respuestas HTTP reales de Laravel en `wings_testing_codex`. [Galería](APROBAR-T45.html).
- Interacción real del navegador: cambio de mes actualiza tipo y aviso; ambos campos visibles y sus valores conservados. Sin desborde de documento ni errores JavaScript. `evidencia-t45/interaccion-cliente.json` y `medidas-chrome.json`.
- Build de Vite, sintaxis PHP de 15 archivos y compilación/limpieza Blade correctos. Rápida definitiva con el caso de esquema: 1113 aprobadas, 6 omitidas, 8523 aserciones, 229,88 s, salida 0. Pantallas finales repetidas después: 13/102 verdes, 15,90 s. Completa definitiva: 1249 aprobadas, 6 omitidas, 10314 aserciones, 1317,09 s, salida 0 en wings_testing_codex.

La migración nueva debe ejecutarse al desplegar. La API sigue deshabilitada. No se tocó el catálogo externo: se propone a Claude cambiar CAT-002 de `no_implementado` a `vigente` y reemplazar «Falta programar: T45» por la referencia a DeporteLiquidacionService después de verificar.

El índice no resolvió los accesores dinámicos y señaló cobertura incompleta; se confirmaron fuentes reales y usos con búsqueda de texto. La tarea requiere revisión independiente; esta entrega no la cierra.

Compatibilidad comprobada: la migración analítica de 09/10 llama al servicio antes de que exista la tabla T45. La contraprueba con profesora previa y DDL real falló por tabla inexistente; la lectura condicionada al esquema la deja verde (1/4, 7,74 s) y luego registra COMISION al entrar la nueva vigencia. Aislamiento de base igual a T27, sin rollback global de migraciones antiguas. La primera completa se interrumpió voluntariamente para corregir este caso; se reiniciaron rápida y completa y ambas terminaron en verde. Se conserva el registro parcial sin presentarlo como una completa.

Corte final: 18 fuentes propias y 629 fuentes PHP/Blade/JS/CSS sin cambios durante la completa; base 92123ff6, que ya contiene las 11 pruebas T12/T13 de Gemini. El incremento propio es 14: contador 1241 → 1255. [Resultados](evidencia-t45/pruebas.txt) · [Contraprueba anterior](evidencia-t45/esquema-antes.txt) · [Esquema corregido](evidencia-t45/esquema-despues.txt) · [Fuentes propias](evidencia-t45/fuentes-sha256.json) · [Corte de fuentes de CyE](evidencia-t45/corte-global-sha256.json). T45 a_verificar, tiene/verifica=Claude; sigue T46.
