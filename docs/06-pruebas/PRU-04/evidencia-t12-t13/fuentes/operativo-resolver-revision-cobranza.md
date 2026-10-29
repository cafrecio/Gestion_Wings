# Ficha de fuentes: Resolver casos en Revisión de cobranza (Operativo)

- **Página del manual:** `resources/manual/operativo/resolver-revision-cobranza.md`
- **Rol:** Operativo
- **Pantalla:** `/revision-cobranza`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_manual_gemini` / `wings_testing_gemini`

---

## 1. Verificación paso a paso y correspondencia con el código

| Paso / Caso | Qué se afirma en el manual | Archivo y línea en Wings | Resultado al ejecutar en el sistema |
|---|---|---|---|
| **Por qué existe** | Si un alumno no tuvo asistencias el mes anterior, Wings no genera deuda automática y lo pone en revisión. | `app/Services/PagoCuotaService.php:90-115`<br>`resources/views/revision-cobranza/index.blade.php:38` | Comprobado: lógica de generación del día 1° deriva a revisión casos sin asistencia previa. |
| **Paso 1: Entrar a Revisión** | En menú lateral hacer clic en **Revisión**. Cartel indica «Estos alumnos no generaron deuda automática porque no tuvieron asistencias el mes anterior. Contactar al tutor y resolver.». | `resources/views/layouts/ds-app.blade.php:147`<br>`resources/views/revision-cobranza/index.blade.php:37-39` | Comprobado: acceso a `/revision-cobranza` despliega el aviso institucional textual. |
| **Paso 2: Contactar y definir** | Interruptor: **Activo** (opción *CONTINUA*, avisa «Se generará la cuota de» al confirmar) o **Inactivo** (opción *INACTIVO*, avisa «Se dará de baja al alumno al confirmar. Sus deudas anteriores se conservan.»). | `resources/views/revision-cobranza/index.blade.php:162`<br>`resources/js/ds-app.js:655-662` | Comprobado: toggle conmuta consecuencias de emisión de cuota vs inactivación sin deuda. |
| **Paso 3: Cargar nota** | Campo obligatorio **Nota (contacto y motivo)** (5 a 500 caracteres) y tocar botón **Confirmar**. | `resources/views/revision-cobranza/index.blade.php:168, 172`<br>`app/Http/Controllers/AlumnoRevisionCobranzaWebController.php:35` | Comprobado: validación exige nota; al confirmar archiva la revisión y actualiza cuenta. |
| **Qué hago si: cobro previo** | Cobrar al alumno antes de resolver puede reactivarlo automáticamente (*Reactivado automáticamente*). | `app/Models/AlumnoRevisionCobranza.php:50`<br>`app/Services/PagoCuotaService.php:510` | Comprobado: método `reactivarAuto` marca la revisión como resuelta por el sistema. |
| **Qué hago si: ver resueltos** | Filtro **Estado** con opción **Resueltos** permite consultar notas y fechas históricas. | `resources/views/revision-cobranza/index.blade.php:51` | Comprobado: selector filtra histórico con fecha y usuario que resolvió. |
