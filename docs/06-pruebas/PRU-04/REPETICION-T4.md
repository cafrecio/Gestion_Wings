# PRU-04 — Repetición de Casos Fallados (T4)

- **Fecha de ejecución:** 16/10/2026
- **Entorno:** Sitio de prueba real (`https://test.gestionar-te.com.ar`), commit `d4f02d4`, hora real, 100 alumnos de primera carga.
- **Ejecutor:** Gemini (Antigravity) · CyE (**LOG GEM CYE**)
- **Resultado general:** **21/21 comprobaciones PASA (100% éxito)**. Los 4 defectos reportados el 11/10 están completamente resueltos y verificados en producción/test sin efectos colaterales.

---

## 1. D02-04 · Caso ACC-003 — Varias personas entran una detrás de otra

### Contexto y defecto original
El 11/10 Sandra Vidal (operativa) recibió el error *«Demasiados intentos seguidos»* al intentar ingresar sin haber fallado ninguna clave. La causa fue que el limitador de 5 ingresos por minuto contabilizaba erróneamente los avisos de seguridad que emite la propia pantalla de login. Corregido en `602efcc`.

### Pasos ejecutados y resultados

| Paso | Hora | Resultado | Qué se verificó | Captura |
|---|---|---|---|---|
| **a.1** | 10:14:00 | **PASA** | Se navegó y recargó `/login` 10 veces consecutivas sin ingresar credenciales. No consumió intentos de rate limit. | [01-acc03-recargas-login.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/01-acc03-recargas-login.png) |
| **a.2** | 10:14:05 | **PASA** | Ingreso 1: Dueño (`admin@wings.test`). Ingresó inmediatamente a `/dashboard`. | [02-acc03-login1-duenio.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/02-acc03-login1-duenio.png) |
| **a.3** | 10:14:10 | **PASA** | Ingreso 2: Operativa (`sandra.vidal@wings.test`). Ingresó inmediatamente a `/operativo`. | [03-acc03-login2-operativa.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/03-acc03-login2-operativa.png) |
| **a.4** | 10:14:15 | **PASA** | Ingreso 3: Profesora (`veronica.salinas@wings.test`). Ingresó inmediatamente a `/clases`. | [04-acc03-login3-profesora.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/04-acc03-login3-profesora.png) |
| **a.5** | 10:14:20 | **PASA** | Ingreso 4: Dueño (`admin@wings.test`). Reingreso exitoso a `/dashboard`. Cuatro usuarios legítimos entran sin impedimento. | [05-acc03-login4-duenio-reingreso.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/05-acc03-login4-duenio-reingreso.png) |
| **b** | 10:15:25 | **PASA** | Se lanzaron 6 intentos de login rápidos en <1 min. Al 6.º intento la pantalla mostró bloqueo por throttle: encabezado *«Esperá un momento»*, texto *«Demasiados intentos seguidos · Esperá un momento y probá de nuevo»* y botón *«Volver»*. | [06-acc03-throttle-6to-intento.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/06-acc03-throttle-6to-intento.png) |
| **c** | 10:15:28 | **PASA** | Durante el bloqueo de IP, se intentó ingresar con otra cuenta (`sandra.vidal@wings.test`). Recibió el mismo bloqueo por IP, impidiendo ataques distribuidos por usuario. | [07-acc03-bloqueo-otra-cuenta.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/07-acc03-bloqueo-otra-cuenta.png) |
| **d** | 10:16:38 | **PASA** | Se esperó 65 segundos sin enviar ninguna solicitud. Al reintentar, el ingreso volvió a funcionar normalmente y redirigió a `/dashboard`. | [08-acc03-reingreso-post-minuto.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/08-acc03-reingreso-post-minuto.png) |
| **e** | 10:16:45 | **PASA** | Una sesión previamente iniciada en otra ventana/navegador (Sandra en `/operativo`) continuó activa y operativa sin verse afectada por el bloqueo de IP ni por los reintentos. | [09-acc03-sesion-paralela-intacta.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/09-acc03-sesion-paralela-intacta.png) |

---

## 2. D03-01, D03-02 y D03-03 · Caso CLA-011 — Cancelar una clase de hoy

### Contexto y defectos originales
El 11/10 se detectaron tres fallas críticas en la operativa de clases del día:
- **D03-01:** Sandra canceló una clase de hoy pero la pantalla no mostró cartel de confirmación y la clase desapareció de la lista en vez de quedar marcada como cancelada.
- **D03-02:** El dueño canceló otra clase y la pantalla no dejaba escribir el motivo.
- **D03-03:** La profesora entró a su clase cancelada y el sistema le permitió seguir cargando asistencia.
Todo el flujo fue rediseñado y corregido en T21 (`44d5ba7`).

### Preparación del escenario de prueba (16/10/2026)
Para probar rigurosamente las dos cancelaciones y mantener una clase de control sin tocar, se configuraron tres clases para el día de hoy:
1. **Clase 1 (ID 20):** `16:00 – 17:00` · Patín — Intermedias · Prof. Salinas, Verónica (para que cancele Sandra).
2. **Clase 2 (ID 79):** `17:00 – 18:00` · Fútbol — Avanzadas · Prof. Quintana, Hernán (para que cancele el Dueño).
3. **Clase 3 (ID 80):** `18:00 – 19:00` · Patín — Avanzadas · Prof. Gaitán, Lucía (clase de control activa).

### Pasos ejecutados y resultados

| Paso | Hora | Resultado | Qué se verificó | Captura |
|---|---|---|---|---|
| **0** | 10:10:23 | **PASA** | Las tres clases están visibles y programadas en el contenedor *«CLASES DE HOY — VIERNES 16 DE OCTUBRE · 3 CLASE(S)»*. | [10-cla11-clases-hoy-inicial.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/10-cla11-clases-hoy-inicial.png) |
| **a.1** | 10:10:30 | **PASA** | **Sandra cancela sin motivo:** El sistema rechaza la cancelación en el frontend, no avanza y muestra en rojo: `El motivo es obligatorio.`. | [11-cla11-sandra-sin-motivo.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/11-cla11-sandra-sin-motivo.png) |
| **a.2** | 10:10:33 | **PASA** | **Sandra cancela con motivo:** Ingresó `Lluvia y pista resbaladiza`. La ficha cambió inmediatamente: badge `Cancelada`, banner `Esta clase está cancelada. No se pueden registrar asistencias.` y botón *Guardar* deshabilitado. | [12-cla11-sandra-cancelada-con-motivo.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/12-cla11-sandra-cancelada-con-motivo.png) |
| **a.3** | 10:10:35 | **PASA** | **Sandra vuelve a `/clases`:** La clase 1 sigue en *Clases de hoy*, pero fue desplazada al final del listado, con opacidad reducida (`opacity: 0.6`), badge `Cancelada` y motivo `Motivo: Lluvia y pista resbaladiza` visible. | [13-cla11-sandra-listado-hoy-cancelada.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/13-cla11-sandra-listado-hoy-cancelada.png) |
| **a.4** | 10:10:38 | **PASA** | **Filtro por estado Cancelada:** Al filtrar por `estado=cancelada&fecha=2026-10-16`, la clase aparece listada correctamente. | [14-cla11-sandra-filtro-cancelada.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/14-cla11-sandra-filtro-cancelada.png) |
| **b.1** | 10:10:46 | **PASA** | **Dueño cancela Clase 2 (ID 79):** El modal permite tipear el motivo `Cancha anegada por temporal` sin ninguna traba. La clase queda marcada como `Cancelada`. | [15-cla11-admin-cancelada-con-motivo.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/15-cla11-admin-cancelada-con-motivo.png) |
| **b.2** | 10:10:49 | **PASA** | **Dueño ve `/clases`:** En *Clases de hoy*, las dos canceladas (ID 20 y 79) quedan al fondo, y la clase activa (ID 80) queda al frente. | [16-cla11-admin-listado-hoy-canceladas.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/16-cla11-admin-listado-hoy-canceladas.png) |
| **c** | 10:10:55 | **PASA** | **Profesora Salinas (`veronica.salinas@wings.test`) abre su clase (ID 20):** La ve con badge `Cancelada — Lluvia y pista resbaladiza`, banner rojo `Esta clase está cancelada. No se pueden registrar asistencias.` y el botón *Guardar* no existe (solo botón *Volver*). No puede registrar asistencia. | [17-cla11-profesora-ve-cancelada.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/17-cla11-profesora-ve-cancelada.png) |
| **d.1** | 10:11:02 | **PASA** | **Encabezado de clases de hoy no cuenta canceladas:** Habiendo 3 clases y 2 canceladas, el encabezado muestra exactamente: `CLASES DE HOY — VIERNES 16 DE OCTUBRE · 1 CLASE(S)`. | [18-cla11-encabezado-hoy.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/18-cla11-encabezado-hoy.png) |
| **d.2** | 10:11:02 | **PASA** | **Contador «Sin asistencia» del menú:** El menú lateral se mantiene en 41. Las dos clases canceladas no sumaron ni incrementaron el contador de pendientes. | [19-cla11-menu-sin-asistencia.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/19-cla11-menu-sin-asistencia.png) |
| **d.3** | 10:11:05 | **PASA** | **Inicio de la operativa (`/operativo`):** En *Mostrador Operativo*, la sección *Clases de hoy — 1* muestra exclusivamente la clase activa (ID 80). Las canceladas no figuran. | [20-cla11-inicio-operativo-sin-canceladas.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/20-cla11-inicio-operativo-sin-canceladas.png) |
| **e** | 10:11:07 | **PASA** | **Clase de control (ID 80):** Permanece en estado `Activa`, con listado de asistencia editable y botón *Cancelar* disponible si hiciera falta. | [21-cla11-clase-control-activa.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/21-cla11-clase-control-activa.png) |

---

## 3. Catálogo de Capturas de Evidencia

Todas las capturas fueron tomadas directamente sobre el navegador Chrome Headless (CDP) conectado al sitio `https://test.gestionar-te.com.ar` y residen en `docs/06-pruebas/PRU-04/evidencia-t4/`:

1. [01-acc03-recargas-login.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/01-acc03-recargas-login.png) — 10 recargas de pantalla sin consumir intentos.
2. [02-acc03-login1-duenio.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/02-acc03-login1-duenio.png) — Ingreso 1: Dueño a `/dashboard`.
3. [03-acc03-login2-operativa.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/03-acc03-login2-operativa.png) — Ingreso 2: Operativa a `/operativo`.
4. [04-acc03-login3-profesora.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/04-acc03-login3-profesora.png) — Ingreso 3: Profesora a `/clases`.
5. [05-acc03-login4-duenio-reingreso.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/05-acc03-login4-duenio-reingreso.png) — Ingreso 4: Dueño reingresa normalmente.
6. [06-acc03-throttle-6to-intento.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/06-acc03-throttle-6to-intento.png) — 6.º intento bloqueado con mensaje en castellano.
7. [07-acc03-bloqueo-otra-cuenta.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/07-acc03-bloqueo-otra-cuenta.png) — Bloqueo a nivel de IP afectando a cualquier cuenta desde esa IP.
8. [08-acc03-reingreso-post-minuto.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/08-acc03-reingreso-post-minuto.png) — Reingreso normal tras transcurrir 65 segundos.
9. [09-acc03-sesion-paralela-intacta.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/09-acc03-sesion-paralela-intacta.png) — Sesión paralela de Sandra en `/operativo` activa y funcional.
10. [10-cla11-clases-hoy-inicial.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/10-cla11-clases-hoy-inicial.png) — Estado inicial: 3 clases de hoy.
11. [11-cla11-sandra-sin-motivo.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/11-cla11-sandra-sin-motivo.png) — Validación obligatoria de motivo al intentar cancelar.
12. [12-cla11-sandra-cancelada-con-motivo.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/12-cla11-sandra-cancelada-con-motivo.png) — Ficha de clase cancelada con motivo y asistencia bloqueada.
13. [13-cla11-sandra-listado-hoy-cancelada.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/13-cla11-sandra-listado-hoy-cancelada.png) — Clase cancelada visible en Hoy, al final y apagada.
14. [14-cla11-sandra-filtro-cancelada.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/14-cla11-sandra-filtro-cancelada.png) — Filtro `estado=cancelada` encuentra la clase del día.
15. [15-cla11-admin-cancelada-con-motivo.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/15-cla11-admin-cancelada-con-motivo.png) — Dueño cancela segunda clase con motivo sin trabas.
16. [16-cla11-admin-listado-hoy-canceladas.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/16-cla11-admin-listado-hoy-canceladas.png) — Listado de hoy del Dueño con ambas canceladas al fondo.
17. [17-cla11-profesora-ve-cancelada.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/17-cla11-profesora-ve-cancelada.png) — Profesora ve la clase cancelada y no puede tomar asistencia.
18. [18-cla11-encabezado-hoy.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/18-cla11-encabezado-hoy.png) — Encabezado de hoy descuenta canceladas (muestra `1 CLASE(S)`).
19. [19-cla11-menu-sin-asistencia.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/19-cla11-menu-sin-asistencia.png) — Menú lateral no suma las canceladas a «Sin asistencia».
20. [20-cla11-inicio-operativo-sin-canceladas.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/20-cla11-inicio-operativo-sin-canceladas.png) — Inicio operativo muestra solo la clase activa (no muestra canceladas).
21. [21-cla11-clase-control-activa.png](file:///c:/xampp/htdocs/Gestion_Wings/docs/06-pruebas/PRU-04/evidencia-t4/21-cla11-clase-control-activa.png) — Clase de control permanece activa y operable.
