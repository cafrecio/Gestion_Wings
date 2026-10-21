# LOG-CLAUDE — entradas archivadas el 21/10/2026

Movidas intactas desde `docs/00-estado/LOG-CLAUDE.md` por Claude CAB para respetar el límite del protocolo (150 líneas, 12.000 caracteres). Cubren del 19/10 al 20/10/2026. Las tareas que nombran están cerradas o siguen en el tablero; ninguna decisión vigente vive solo acá.

## 2026-10-20 — Claude CyE — T64 verificada: devuelta a Gemini

- Validador y lote bien (transacción, solo ADMIN, cambio de tramos en un envío). 13/13 de Gemini.
- H1 alcanzable: Cancelar no repone los valores y el próximo guardado manda la edición abandonada
  (leído en configuraciones.js, no reproducido en navegador). H2 id repetido → días 1–15 sin cubrir,
  200 (reproducido). H3 id inexistente → 404 en inglés. Los dos últimos solo con pedido armado.
- Pruebas independientes fuera de tests/: 5, 1 fallida (H2). Informe VERIFICACION-T64-CLAUDE.md.

## 2026-10-20 — Claude CyE — T52 H5: el cambio de grupo no adelanta un ingreso futuro

- Codex reverificó d148a241: H1–H4 bien, H5 nuevo (cambio de grupo antes de ingreso futuro → asistencia
  anticipada). `enGrupoElDia` exige siempre el ingreso. 12 pruebas T52; dirigidas 77/77.
- Sin suite completa: carpeta con T42 de Codex sin subir. Commit solo con lo mío; contador 984.

## 2026-10-20 — Claude CyE — T52 tercera vuelta: H1–H4 de Codex corregidos

- Codex devolvió la segunda parte con 4 hallazgos reales. Causa común: suposiciones sin probar.
- H1 guardado exige activo como la pantalla; H2/H3 `grupo_desde` vacío mientras no cambia de grupo
  (vale el ingreso, aunque se corrija o sea futuro; la migración solo completa a quien muestra cambio);
  H4 `ClaseService::contarAsistenciasSemanaDeVarios`, dos consultas para toda la lista.
- Antes de entregar busqué yo los casos que lo rompen: mismo grupo guardado como texto (no cuenta como
  cambio), Excel guarda después del alta sin tocar el grupo. 11 pruebas T52.
- Suite completa: 965 aprobadas, 3 fallidas ajenas (T64 de Gemini sin subir). Commit solo con lo mío,
  sobre las versiones publicadas de los documentos compartidos. Carlos, con razón, molesto por la calidad.

## 2026-10-20 — Claude CyE — Tablero: las cuatro respuestas de la encuesta como etiquetas

- Carlos vio 10 tareas «sin categorizar»: eran «Antes de producción» del 18/10, que el tablero no podía
  mostrar (solo tenía Prioritaria / Muy a futuro). Carlos: «Es un tablero... tiene que ser útil».
- `scripts/tablero/lib.php`: prioridad = Ahora, Antes del 1/11, Antes de producción, Después o Sin decidir,
  con color propio; «Sin empezar» separado por categoría, en ese orden.
- Datos migrados desde la etiqueta del 18/10 en cada tarea; sin etiqueta, alta→Ahora y baja→Después.
  T12 pasa a Antes de producción (respuesta del 18/10 sobre la marca del 10/10); T50 recupera la suya.
- Pruebas del tablero: 9 verdes. Solo herramienta: Wings no cambia.

## 2026-10-20 — Claude CyE — T52 segunda parte: quien entró después no aparece

- Carlos: «Los cambios son siempre para adelante» (DU-09). Visual de T52 aprobado: «Las vistas están OK».
- El plan no sirve para la fecha en el grupo (cambio inmediato, plan diferido al mes siguiente):
  columna nueva `alumnos.grupo_desde`, completada por el modelo `Alumno` en alta, cambio de grupo y
  corrección del ingreso; migración que la completa para los existentes. `enGrupoElDia` en show y guardado.
- `wingstest` (solo lectura): a los 100 les quedaría su fecha de ingreso; nadie desaparece.
- 2 pruebas nuevas (7 en T52). Suite completa `wings_testing_claude`: 952 aprobadas, 4 omitidas,
  2 fallidas: contador (corregido a 958) y `CancelarLiquidacionCerradaTest`, cuyo alumno de prueba no
  tenía ingreso anterior a la clase (fixture corregido, 9/9).
- Esperé a que Codex terminara su fusión de T61 antes de tocar nada. Requiere migrar al desplegar.

## 2026-10-19 — Claude CyE — Producción y prueba: dos bases que se confundieron

- Carlos: «En test tiene que haber 100». Comprobado por SSH, solo lectura: producción `wings` 0 alumnos;
  prueba `wingstest` 100 activos (muestra 241–245 mirada).
- T66 (Codex): su relevamiento «sitio de prueba» consultó `wings` (producción, vacía). Rehecho en
  `wingstest`: 0 clases sin profesora en 79, filas revisadas. Informe corregido con nota.
- T56 (Gemini): estaba bien, consultó también `wingstest` (0 de 100 sin plan); comprobado de nuevo: 0.
- `SERVIDOR.md` documentaba solo producción: agregada la tabla de los dos sitios.
