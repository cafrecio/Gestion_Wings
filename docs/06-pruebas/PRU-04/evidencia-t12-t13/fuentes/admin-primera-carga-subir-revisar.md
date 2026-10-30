# Ficha de fuentes: Subir y revisar el archivo de primera carga

- **Página del manual:** `resources/manual/admin/primera-carga-subir-revisar.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/sistema/primera-carga#paso-3`
- **Controlador / Servicio:** `app/Http/Controllers/PrimeraCargaWebController.php:revisar`, `app/Services/PrimeraCargaExcelService.php:revisar`
- **Vista Blade:** `resources/views/primera-carga/index.blade.php` (sección `#paso-3`)

---

## 1. Verificación paso a paso y casos de salida

| Paso | Acción en Wings | Resultado en código / pantalla | Captura tomada |
|---|---|---|---|
| 1 | Subir archivo `.xlsx` en sección 3 y tocar `Revisar` | Ejecuta `PrimeraCargaExcelService::revisar` sin alterar la base | `primera-carga-subir-revisar-paso1.png` |
| 2 | Ver informe con inconsistencias y botón `Descargar` | Muestra lista de errores por celda y permite bajar el Excel marcado con columna AM | `primera-carga-subir-revisar-paso2.png` |

---

## 2. Rechazos y avisos de la acción

| Situación | Qué hace Wings | Texto exacto o código HTTP | ¿Está en la página? | Sección de la página donde está | Motivo si es No |
|---|---|---|---|---|---|
| Archivo no válido o formato erróneo | Muestra alerta de validación | `No se cargó el archivo.` | Sí | Qué hago si: ¿Qué hago si Wings rechaza el archivo al subirlo? | — |
| Filas con errores de validación | Lista celdas observadas y frena la importación | `errores. No se cargó nada.` | Sí | Paso 2: Analizar los resultados de la revisión | — |
| Archivo sin errores | Confirma revisión exitosa | `Listo. Archivo revisado sin errores.` | Sí | Paso 2: Analizar los resultados de la revisión | — |
| Intentar revisar con primera carga ya terminada | Bloquea la acción | `La primera carga ya está terminada.` | No | — | Regla interna de protección cuando el estado ya es TERMINADA |
