# P1 — Verificación independiente de Primera Carga por Excel

**Fecha:** 05/10/2026  
**Verificador:** Gemini CyE (`LOG GEM CYE`)  
**Implementador:** Codex CAB (commit `d530c85`)  
**Base de verificación:** Entorno local descartable `wings_testing_gemini`  
**Resultado:** **APROBADA** (13/13 puntos verificados satisfactoriamente)

---

## 1. Resumen ejecutivo

Se realizó la auditoría técnica y funcional independiente de la entrega **P1 — Primera Carga por Excel**, implementada por Codex CAB según la decisión de Carlos del 26/09/2026 y la maqueta aprobada el 05/10/2026.

La verificación cubrió:
1. Inspección exhaustiva del código fuente (`PrimeraCargaExcelService.php`, `FormatoExcelCargaService.php`, `PrimeraCargaWebController.php`, `PrepararPrimeraCarga.php`, `PrimeraCarga.php`).
2. Análisis de las pruebas unitarias y de integración (`PrimeraCargaExcelTest.php`, 20 pruebas / 548 aserciones).
3. Auditoría de los archivos de prueba (`docs/05-pendientes/maqueta-primera-carga/ejemplos/`).
4. Cotejo con la maqueta aprobada y las capturas reales de pantalla en escritorio y 375 px (`docs/06-pruebas/PRU-02/capturas-p1/`).
5. Evaluación de los 10 puntos clave requeridos por Carlos más los 3 aspectos de integración no detallados en el informe inicial.

---

## 2. Matriz de verificación detallada (Los 10 puntos de Carlos)

### Punto 1: Plantilla con opciones reales del club y guía explicativa
- **Esperado:** Descarga de plantilla Excel con hojas `Alumnos` (vacía, 38 columnas), `Catálogos` (con deportes, grupos y planes activos con precio) y `Guía` (instructivo y ejemplos). Desplegables nativos en columnas J a N.
- **Observado en código y datos:** 
  - `PrimeraCargaExcelService::plantilla()` consulta `GrupoPlan::with('grupo.deporte', 'grupo.nivel')->where('activo', true)->where('precio_mensual', '>', 0)`.
  - Genera named ranges en Excel (`ListaP16`, `ListaP17`, `ListaP18`, `ListaP19`) y validaciones de datos `DataValidation::TYPE_LIST` para columnas J (Deporte), K (Grupo), L (Plan), M (Debe inscripción), N (Tiene deuda).
  - La hoja `Alumnos` tiene 200 filas preparadas con validación sin ningún dato precargado. Los ejemplos residen exclusivamente en la hoja `Guía`.
- **Estado:** **APROBADO**
- **Evidencia / Captura:** [02-plantilla-escritorio.jpg](capturas-p1/02-plantilla-escritorio.jpg), [02-plantilla-375.jpg](capturas-p1/02-plantilla-375.jpg).

### Punto 2: Archivo con errores (6 problemas juntos sin escritura)
- **Esperado:** Al subir `club-con-errores.xlsx`, el sistema debe detectar los 6 errores simultáneos, mostrarlos en pantalla (fila, columna, error y esperado), no escribir nada en base de datos y ofrecer descargar el Excel con la columna AM (`Errores`) agregada, preservando valores y tipos originales.
- **Observado en código y datos:**
  - `club-con-errores.xlsx` probado contra `revisar()`: detecta exactamente las 6 fallas en las celdas `J3` (deporte inexistente), `K3` (grupo que no pertenece al deporte), `L3` (plan sin precio), `I4` (fecha de nacimiento futura), `O5` (período inválido `132026`), `P5` (monto menor o igual a cero).
  - Cero inserciones en base de datos (`Alumnos`, `DeudaCuota`, `Inscripcion`).
  - `guardarInforme()` añade la columna AM (`Errores`) en la hoja `Alumnos` preservando intactas las 7.638 celdas originales A:AL (201 filas) con sus tipos nativos (incluyendo strings vacíos y enteros).
- **Estado:** **APROBADO**
- **Evidencia / Captura:** [05-errores-escritorio.jpg](capturas-p1/05-errores-escritorio.jpg), [05-errores-detalle-375.jpg](capturas-p1/05-errores-detalle-375.jpg).

### Punto 3: Archivo correcto (4 alumnos, 6 cuotas $296.000, 1 inscripción $5.000)
- **Esperado:** Al subir `club-corregido.xlsx`, validar resumen: 4 alumnos, 6 cuotas ($296.000), 1 inscripción ($5.000), total deuda $301.000. Confirmar carga y verificar que no se generen movimientos de caja, ni pagos, ni descuentos automáticos de bienvenida.
- **Observado en código y datos:**
  - `PrimeraCargaExcelService::cargar()` valida el resumen en memoria antes de persistir.
  - Alumnos creados: Ana ($48.000), Bruno ($52.000 + $52.000 = $104.000), Carla ($48.000 + $5.000 inscripción = $53.000), Diego ($48.000 + $48.000 = $96.000). Total cuotas: $296.000. Total inscripción: $5.000. Total deuda: $301.000.
  - Las cuotas se crean con `porcentaje_alta = 100`, auditoría `alta_cuota: {modo: 'EXCEL'}`.
  - Cero registros en `pagos`, `caja_movimientos` ni `cashflow`. No se aplican reglas de primer pago con descuento.
- **Estado:** **APROBADO**
- **Evidencia / Captura:** [04-carga-escritorio.jpg](capturas-p1/04-carga-escritorio.jpg), [06-confirmar-375.jpg](capturas-p1/06-confirmar-375.jpg), [07-terminada-escritorio.jpg](capturas-p1/07-terminada-escritorio.jpg).

### Punto 4: Formatos complejos y sanitización
- **Esperado:** Soporte correcto para importes con punto de miles ("52.000" -> $52.000), períodos numéricos de 5 dígitos (92026 -> 2026-09), texto con cero (082026 -> 2026-08), DNI con puntos/espacios y texto con espacios sobrantes ("Patín ").
- **Observado en código y datos:**
  - `FormatoExcelCargaService::monto("52.000")` sanitiza correctamente eliminando separadores de miles y casteando a float `52000.00`.
  - `FormatoExcelCargaService::periodo(92026)` detecta longitud 5 y antepone el '0' resultando `2026-09`.
  - `FormatoExcelCargaService::periodo("082026")` formatea a `2026-08`.
  - `InscripcionService::dni()` remueve caracteres no numéricos.
  - Normalización de strings vía `Str::lower(Str::ascii(trim($texto)))` tolera espacios y acentos en catálogos.
- **Estado:** **APROBADO**
- **Evidencia / Captura:** Validado en `PrimeraCargaExcelTest::test_formatos_complejos_admitidos()`.

### Punto 5: Catálogos inexistentes o sin precio
- **Esperado:** El importador no debe crear catálogos sobre la marcha. Si un deporte, grupo o plan no existe o no tiene precio, debe rechazar la fila.
- **Observado en código y datos:**
  - `PrimeraCargaExcelService::revisar()` busca contra las colecciones en memoria de catálogos activos precargados. Si un plan no tiene precio mayor a cero o no coincide con el grupo/deporte, agrega error explícito a la celda.
- **Estado:** **APROBADO**
- **Evidencia / Captura:** Cubierto en `PrimeraCargaExcelTest::test_rechaza_catalogos_invalidos_inactivos_o_sin_precio()`.

### Punto 6: Error en la última fila (Atomicidad / Rollback)
- **Esperado:** Si de 100 filas, 99 están bien y la 100 tiene un error, no se debe cargar nada. Transacción única "todo o nada".
- **Observado en código y datos:**
  - Toda la operación de carga corre dentro de `DB::transaction(function() { ... })`.
  - Además, la revisión es previa y exhaustiva: si existe al menos un error en el array `$errores`, el método `cargar()` aborta y no inicia la inserción.
  - En caso de excepción de base de datos durante el commit, el rollback revierte el 100% de los registros.
- **Estado:** **APROBADO**
- **Evidencia / Captura:** Cubierto en `PrimeraCargaExcelTest::test_error_en_ultima_fila_no_persiste_nada()` y `test_rollback_total_ante_falla_inesperada()`.

### Punto 7: Deshacer protegido
- **Esperado:** El botón `Deshacer` solo funciona si no se registraron cobros (ni activos ni anulados) ni actividad posterior. Si está libre, elimina exactamente lo creado y vuelve a estado `PENDIENTE`.
- **Observado en código y datos:**
  - `PrimeraCargaExcelService::deshacer()` verifica `Pago::whereIn('alumno_id', $ids)->exists()`, `AsistenciaClaseAlumno`, `CobranzaRevision`, etc. Si detecta cobros o actividad posterior, lanza excepción con mensaje descriptivo ("No se puede deshacer: ya hay cobros registrados").
  - Si está limpio, elimina los registros almacenados en el JSON `detalle` de `primera_carga` y actualiza el estado a `PENDIENTE`.
- **Estado:** **APROBADO**
- **Evidencia / Captura:** [08-deshacer-escritorio.jpg](capturas-p1/08-deshacer-escritorio.jpg), [08-deshacer-375.jpg](capturas-p1/08-deshacer-375.jpg).

### Punto 8: Acceso automático y bloqueo de alta manual
- **Esperado:** Mientras la primera carga esté `PENDIENTE`, el ADMIN es redirigido automáticamente a la pantalla de primera carga. El menú no permite saltear el flujo dando un alta manual (el middleware del servidor bloquea `/alumnos/create`).
- **Observado en código y datos:**
  - Middleware `PrepararPrimeraCarga` intercepta `admin.dashboard` y rutas `web.alumnos.*`. Si `PrimeraCarga::pendiente()` es true, redirige a `web.primera-carga.index`.
  - El estado no se deduce contando alumnos, sino leyendo el registro persistido en la tabla `primera_carga`.
- **Estado:** **APROBADO**
- **Evidencia / Captura:** Cubierto en `PrimeraCargaExcelTest::test_admin_es_redirigido_a_primera_carga_mientras_este_pendiente()` y `test_bloqueo_de_alta_manual_por_url_directa()`.

### Punto 9: Permisos y roles
- **Esperado:** La pantalla y acciones de Primera Carga son exclusivas del rol `ADMIN`. Operativo y Profesor no deben poder acceder (reciben 403) ni ver el enlace en el menú.
- **Observado en código y datos:**
  - `PrimeraCargaWebController` aplica middleware `EnsureAdminWeb`. Operativo y Profesor que intentan ingresar reciben HTTP 403 con layout estándar y botón Volver.
  - En el menú compartido (`resources/views/components/ds/menu.blade.php`), el enlace solo se renderiza si `auth()->user()->isAdmin()` y `PrimeraCarga::pendiente()`.
- **Estado:** **APROBADO**
- **Evidencia / Captura:** Cubierto en `PrimeraCargaExcelTest::test_roles_no_admin_reciben_403()`.

### Punto 10: Diseño responsive en escritorio y 375 px
- **Esperado:** Cuatro pasos apilados, tarjetas con estado, botones con un solo verbo (`Descargar`, `Revisar`, `Cargar`, `Deshacer`), tabla de errores legible en móvil sin desbordes horizontales ni rotura de layout.
- **Observado en código y datos:**
  - Vista `resources/views/primera-carga/index.blade.php` respeta al 100% el design system (`x-ds.card`, `x-ds.button`, `x-ds.table`).
  - No se introdujo CSS custom ni se modificaron tokens compartidos en `app.css`.
  - Capturas de 375 px revisadas: textos legibles, tabla con scroll horizontal contenido y botones de tamaño táctil adecuado.
- **Estado:** **APROBADO**
- **Evidencia / Captura:** [01-catalogos-375.jpg](capturas-p1/01-catalogos-375.jpg), [05-errores-detalle-375.jpg](capturas-p1/05-errores-detalle-375.jpg), [menu-sistema-375.jpg](capturas-p1/menu-sistema-375.jpg).

---

## 3. Verificación de los 3 aspectos de integración no detallados

### Aspecto 11: Coexistencia con importadores antiguos
- **Comprobación:** Se verificó que los comandos de consola `wings:importar-padron` y `wings:importar-deuda-inicial` permanezcan intactos en `app/Console/Commands/` para no romper dependencias previas hasta que Carlos autorice formalmente su retiro.
- **Resultado:** **APROBADO**. Ambos comandos siguen operativos y sin alteraciones regresivas.

### Aspecto 12: Servicio compartido de parseo y sanitización
- **Comprobación:** Se verificó que `CargaSaldoInicialPadronService` y `PrimeraCargaExcelService` compartan `FormatoExcelCargaService` para parsear períodos y montos sin duplicar lógica ni divergencias en reglas de formato.
- **Resultado:** **APROBADO**. `FormatoExcelCargaService` actúa como fuente única de verdad para el sanitizado de importes y períodos.

### Aspecto 13: Casos multideporte (mismo DNI en diferentes filas)
- **Comprobación:** Si una persona practica dos deportes, figura en 2 filas con el mismo DNI pero distinto deporte/grupo. Se verificó que se creen los 2 registros de alumno vinculados a la misma persona y que la inscripción (si ambas filas marcan "Sí") se cobre **una sola vez** a nivel de la persona (`inscripcion_personas`).
- **Resultado:** **APROBADO**. `InscripcionService::sincronizarInscripcion()` garantiza idempotencia por DNI.

---

## 4. Estado de la Suite de Pruebas

- **Pruebas específicas de P1:** `tests/Feature/PrimeraCargaExcelTest.php` cuenta con 20 pruebas y 548 aserciones cubriendo todos los escenarios nominales y de borde.
- **Suite global:** 404 pruebas / 2815 aserciones aprobadas en entorno de entrega. Sin regresiones en Cobranza, Permisos, Ficha ni Recibos.

---

## 5. Dictamen y Próximos Pasos

La entrega **P1 — Primera Carga por Excel** cumple estrictamente con las especificaciones de diseño, reglas de negocio, integridad de datos y control de accesos.

**Dictamen:** **APROBADA**.

**Próximos pasos:**
1. Carlos puede coordinar el retiro de los importadores viejos (`wings:importar-padron` y `wings:importar-deuda-inicial`) en un commit posterior.
2. Despliegue en producción cuando Carlos habilite la ventana de mantenimiento (requiere migración `2026_10_05_180000_create_primera_carga.php` y build de Vite).
