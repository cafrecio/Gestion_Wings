# A43 — alta manual con ingreso en mes cerrado

Entrega de Codex CAB, 04/10/2026. **Pendiente de verificación independiente por Gemini. No cerrado ni desplegado.**

Carlos aprobó las dos maquetas y escribió:

`Diseno-autorizado: Carlos aprueba A43 y A29, A30 y A31 según las maquetas presentadas, conservando el diseño Wings.`

Aclaración: **«A43 solo la cuota»**. No evita la cuota mensual; la inscripción sigue siendo única por DNI.

## Comportamiento comprobado

- Ingreso en mes cerrado exige una elección sin valor predeterminado. Sí genera el mes corriente completo; No solo evita cuota. No genera meses históricos.
- Ingreso corriente o futuro conserva cuota automática y porcentaje configurado por día. Un No enviado fuera del caso cerrado no suprime esa cuota.
- El importe se congela al guardar; cobro sin segundo descuento. Se conserva fecha real de ingreso y vigencia inicial del plan.
- `alta_cuota` guarda modo, usuario, fecha, ingreso, período, monto y porcentaje. Alta, plan, inscripción, cuota y auditoría comparten la transacción; excepción controlada revierte todo. Reintento no duplica ni cambia la decisión.
- Si cambió el mes o importe desde el aviso, se rechaza el guardado y se pide revisar. La previsualización no escribe.

## Código

- `app/Services/PagoCuotaService.php:363`: fuente común de previsualización e importe; `:385`: creación según decisión.
- `app/Http/Controllers/AlumnoWebController.php:20`: consulta previa; `:194`: validación del alta, transacción, reintento y auditoría.
- `resources/views/alumnos/_form.blade.php:192`: aviso solo en alta; `resources/js/alumnos-inscripcion.js:31`: consulta y elección obligatoria, sin código incrustado nuevo.
- `database/migrations/2026_10_04_150000_add_alta_cuota_to_alumnos.php`: columna JSON nullable; no backfill ni recálculo de registros existentes.
- `tests/Feature/AltaMesCerradoTest.php`: diez casos nuevos. Cuatro casos históricos de CuotaAltaEstadoTest ajustados a la decisión; fixtures de inscripción/grupo mantienen su finalidad.

## Verificación propia

Primero en rojo: **10 fallos / 18 aserciones** contra la aplicación previa. Después: **366 pruebas / 2131 aserciones**, todas verdes, en `wings_testing_codex`, sobre copia exclusiva de `e5bc981` más fuentes A43. Se integraron los tres casos de Cobranza ya versionados por Gemini. Fuentes de entrega comparadas por SHA256 contra la copia probada.

PHP sin errores, Blade cache/clear y Vite build correctos. Solo la vista de formulario autorizada cambió; sin CSS nuevo ni modificación de CSP. No se corrieron migraciones ni seeders en la base habitual ni en el servidor.

En navegador real local, rol OPERATIVO y datos ficticios: elección vacía bloquea Guardar; Sí crea octubre $48.000 + inscripción $5.000; No no crea cuota y conserva inscripción $5.000. Las fichas conservaron ingreso 20/01/2020; estado En plazo para Sí y Al día para No. A 375 px, ancho de documento 375, sin desborde; botones legibles. Ingreso corriente ocultó radios y quitó su obligatoriedad.

- [Formulario escritorio](evidencia/a43/alta-mes-cerrado-desktop.png)
- [Formulario 375 px](evidencia/a43/alta-mes-cerrado-mobile.png)
- [Ficha después de Sí](evidencia/a43/resultado-si.png)
- [Ficha después de No](evidencia/a43/resultado-no.png)

## Límites y siguiente paso

La verificación propia no reemplaza AGENTS §6a: **Gemini debe abrir formulario y código antes de cerrar A43**. No se verificó ni modificó el sitio de prueba remoto. El despliegue requiere la migración `alta_cuota` y reconstruir assets por el procedimiento habitual. El importador Excel sigue fuera de esta entrega.
