# Ficha de fuentes: Consultar grupos y planes deportivos (Operativo)

- **Página del manual:** `resources/manual/operativo/consultar-grupos-planes.md`
- **Rol:** Operativo
- **Pantalla:** `/grupos`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_manual_gemini` / `wings_testing_gemini`

---

## 1. Verificación paso a paso y correspondencia con el código

| Paso / Caso | Qué se afirma en el manual | Archivo y línea en Wings | Resultado al ejecutar en el sistema |
|---|---|---|---|
| **Información de mostrador** | Permite revisar actividades activas, alumnos activos por grupo y tarifas arancelarias por frecuencia. | `resources/views/grupos/index.blade.php:60-65`<br>`app/Models/Grupo.php` | Comprobado: listado muestra tarjetas de grupo con sus aranceles vigentes. |
| **Paso 1: Entrar a Grupos** | En menú lateral hacer clic en **Grupos**. Muestra filtro por deporte, barra de total y tarjetas. | `resources/views/layouts/ds-app.blade.php:172`<br>`resources/views/grupos/index.blade.php:10, 35` | Comprobado: acceso a `/grupos` carga catálogo general de grupos. |
| **Paso 2: Filtrar deporte** | Menú desplegable **Deporte** filtra grupos; botón **Limpiar** restablece la nómina completa. | `resources/views/grupos/index.blade.php:14, 27`<br>`app/Http/Controllers/GrupoWebController.php:25-35` | Comprobado: select actualiza la vista mediante `data-enviar-al-cambiar`. |
| **Paso 3: Aranceles y alumnos** | Indicador **Activo**, nombre completo, **Alumnos activos**, **Planes** con precios por clase semanal y botón **Ver**. | `resources/views/grupos/index.blade.php:72, 74, 84, 91, 100, 120` | Comprobado: tarjeta detalla precios por frecuencia (ej: "2x/sem — $ 25.000"); botón **Ver** abre legajo. |
| **Qué hago si: crear/modificar** | Crear grupos y fijar tarifas es exclusivo de administración (**ADMIN**). | `resources/views/grupos/index.blade.php:44, 105`<br>`routes/web.php:215-220` | Comprobado: rol operativo no tiene botones de nuevo grupo ni edición de planes. |
| **Qué hago si: cupos/vacantes** | Grupos no tienen límite rígido de cupo en el sistema; consultar cantidad de alumnos activos con la profesora. | `app/Models/Grupo.php:11-15` (`$fillable` no tiene cupos ni edades) | Comprobado: se informa cantidad de inscriptos reales para coordinar vacantes. |
