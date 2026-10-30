# Ficha de fuentes: Descargar la plantilla de primera carga

- **Página del manual:** `resources/manual/admin/primera-carga-descargar-plantilla.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/sistema/primera-carga` (pasos 1 y 2)
- **Controlador / Servicio:** `app/Http/Controllers/PrimeraCargaWebController.php:index`, `app/Services/PrimeraCargaExcelService.php:catalogos`
- **Vista Blade:** `resources/views/primera-carga/index.blade.php`

---

## 1. Verificación paso a paso y casos de salida

| Paso | Acción en Wings | Resultado en código / pantalla | Captura tomada |
|---|---|---|---|
| 1 | Entrar a `/sistema/primera-carga?paso=1` | Muestra contadores de catálogos y botón `Continuar` (o `Preparar`) | `primera-carga-descargar-plantilla-paso1.png` |
| 2 | Avanzar a paso 2 y tocar `Descargar` | Descarga el archivo Excel generado por `PrimeraCargaExcelService::plantilla` | `primera-carga-descargar-plantilla-paso2.png` |

---

## 2. Rechazos y avisos de la acción

| Situación | Qué hace Wings | Texto exacto o código HTTP | ¿Está en la página? | Sección de la página donde está | Motivo si es No |
|---|---|---|---|---|---|
| Continuar o descargar sin grupos o planes activos con precio | Bloquea la descarga y exige cargar catálogos | `Prepará al menos un grupo con un plan activo y precio.` | Sí | Qué hago si: ¿Qué hago si al querer continuar me dice que faltan catálogos? | — |
| Primera carga ya terminada | Redirige al paso 4 o muestra aviso de carga finalizada | `Carga terminada.` | No | — | La página explica la preparación y descarga inicial, no la carga terminada |
| Intentar descargar plantilla estando terminada | `PrimeraCargaWebController::plantilla` bloquea | `La primera carga ya está terminada.` | No | — | Detalle interno del controlador cuando el estado ya cambió a TERMINADA |
