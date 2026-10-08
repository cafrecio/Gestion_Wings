# Implementación A12 y A24 — Inicio del Operativo (Opción 2 Variante B)

**Fecha:** 2026-10-08  
**Autor:** Gemini (LOG GEM CYE)  
**Autorización de diseño:** Carlos (`Diseno-autorizado: Aprobada opcion 2 variante B para mostrador operativo en A12 y A24`)  
**Base utilizada:** `wings_testing_gemini`  

---

## 1. Problemas resueltos

- **A24 (Inconsistencia de Apertura):**
  - *Antes:* Al iniciar la jornada sin caja abierta, la tarjeta decía "Sin caja hoy" y ofrecía el botón `Cobrar` (`/caja/cobrar`), el cual rebotaba con HTTP 302 a `/caja/apertura` exigiendo declarar el efectivo inicial.
  - *Ahora:* La pantalla muestra claramente el estado del cajón ("Cajón listo para iniciar") con el botón directo `Abrir` (`/caja/apertura`). Además, si otro compañero ya abrió el cajón en el club, reconoce el turno compartido en curso ("Cajón compartido en curso — Turno de [Operativo]") y habilita los botones `Cobrar`, `Registrar` y `Detalle` sin bloquear al usuario.
- **A12 (Orientación al inicio de jornada y ergonomía de mostrador):**
  - *Antes:* Tres cuadros métricos ocupaban el espacio principal en $0 y la pantalla no orientaba por dónde empezar.
  - *Ahora:* Se implementó la **Opción 2 Variante B** aprobada por Carlos:
    1. **Encabezado y Alertas:** Aviso superior si hay cajas rechazadas por corregir o turnos previos pendientes.
    2. **Estado del Cajón (Primer bloque):** Destacado con badge de estado y acción directa (`Abrir`, `Cobrar`, `Registrar`, `Resumen`).
    3. **Tareas del Mostrador (2 columnas niveladas):** 
       - Columna izquierda: *Clases de hoy* con scroll interno si hay más de 2 clases (`max-height: 165px`), evitando desbalances de altura.
       - Columna derecha: *Atención a alumnos* con tarjetas de altura coincidente para `Con deuda` (enlace directo a `/cobranza`) y `Posibles inactivos` (enlace directo a `/revision-cobranza`).
    4. **Recaudación de hoy (Pie de página):** Los tres cuadros de métricas (`Cobrado hoy`, `Cobros registrados`, `Cajas del turno`) se ubican al pie como datos de soporte y balance previo al arqueo de cierre.

---

## 2. Archivos modificados

1. `app/Http/Controllers/OperativoDashboardController.php`:
   - Agregada consulta de `cajaPropia`, `cajaClub` (cajón compartido abierto por cualquier compañero) y `ultimaCaja`.
   - Pasados dichos modelos a la vista para eliminar dependencias viejas del array formateado.
2. `resources/views/operativo/dashboard.blade.php`:
   - Reestructuración completa según Opción 2 Variante B.
   - Cumple con tokens de diseño (`var(--color-...)`), botones de un solo verbo (`Abrir`, `Cobrar`, `Registrar`, `Resumen`, `Detalle`, `Lista`), sin CSS inline prohibido ni frameworks ajenos.

---

## 3. Verificación y Pruebas

- **Compilación de vistas:** `php artisan view:cache && php artisan view:clear` ejecutado con éxito (0 errores).
- **Pruebas de cobro y apertura:**
  - `CobranzaOperativoTest`: 7 passed (10 assertions).
  - `RevisionCobranzaOperativoTest`: 6 passed (22 assertions).
  - `CajaCambioInicialA25Test`: 22 passed (114 assertions).
  - `CspSinCodigoIncrustadoTest`: 2 passed (2 assertions).
- **Entorno visual comprobado:**
  - Maquetas y variantes capturadas con Chrome headless en `docs/06-pruebas/PRU-02/evidencia/a12-a24/capturas-variantes/`.
  - Visor interactivo disponible en `docs/06-pruebas/PRU-02/evidencia/a12-a24/visor-variantes.html`.

---

## 4. Estado y Pase a Verificación

En cumplimiento de **AGENTS.md §6a** ("lo que hace uno, lo controla otro"), Gemini no cierra los defectos A12 y A24. Quedan listos en estado `a_verificar` para que otro agente (Codex o Claude) audite la implementación en el código y en pantalla.
