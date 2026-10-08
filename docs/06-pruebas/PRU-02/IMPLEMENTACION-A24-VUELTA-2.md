# Wings — Implementación Definitiva A24 (Segunda Vuelta)

**Fecha:** 08/10/2026  
**Agente:** Gemini (Antigravity)  
**Entorno:** CyE (`wings_testing_gemini`)  
**Commit base:** `d577cb7`  
**Referencia:** Devolución de Claude en `docs/06-pruebas/PRU-02/VERIFICACION-A12-A24.md` y regla de negocio ratificada por Carlos el 08/10/2026.

---

## 1. Contexto y Decisión de Carlos

### La regla de negocio (Carlos, 08/10/2026)
> *«Las cajas de los operativos son individuales. Lo compartido es el cajón, o sea el efectivo. Va una caja después de la otra: no se abre una mientras hay otra abierta.»* (Contrato Caja-Cashflow V5).

### Elección de redacción de Carlos (08/10/2026)
Presentadas las propuestas en el visor interactivo (`visor.html`), Carlos eligió explícitamente:
> **Opción 1: «Caja abierta de [Nombre]»**  
> - **Kicker:** `TURNO EN CURSO` (punto ámbar)  
> - **Título:** `Caja abierta de [Nombre Operativo]`  
> - **Bajada:** *«Abierta [hoy a las 10:00 / ayer a las 18:30]. Para atender tu turno, [Nombre] debe cerrar su caja.»*  
> - **Botón:** `Caja` (estilo `ds-btn ds-btn--sec`, lleva a `/caja`).

---

## 2. Qué cambió y qué se corrigió

### A. Backend: `app/Http/Controllers/OperativoDashboardController.php`
- **Consulta unificada de caja abierta:** Se eliminó la restricción que buscaba solo cajas del día de hoy. Ahora utiliza la misma regla que `CajaService` y `CajaWebController`:
  `CajaOperativa::where('estado', 'ABIERTA')->with('usuarioOperativo')->first()`.
- **Discriminación clara de dominios:**
  - Si la caja abierta pertenece a otro usuario -> `$cajaCompanero`.
  - Si la caja abierta pertenece al usuario autenticado -> `$cajaPropia`.
- **Formateo temporal amigable:** Provee `$aperturaCompaneroTexto` y `$aperturaPropiaTexto` formateados como *"hoy a las 10:00"*, *"ayer a las 18:30"* o *"el dd/mm a las HH:mm"*.
- **Compatibilidad con última caja:** Si no hay ninguna caja abierta en el club, busca la `$ultimaCaja` propia del día para informar el estado del turno cerrado.

### B. Vista: `resources/views/operativo/dashboard.blade.php`
- **Eliminación del concepto erróneo de "Cajón compartido":** Se retiraron los textos confusos que invitaban a operar sobre la caja del compañero.
- **Eliminación de botones trampa:** Se removieron los botones `Cobrar` y `Registrar` (que rebotaban a la apertura) y `Detalle` (que devolvía 403 Forbidden). La tarjeta ahora ofrece únicamente el botón secundario `Caja`.
- **Situación 6 (Compañero dejó abierta ayer):** Detectada como caja de compañero; ya no ofrece el botón "Abrir" que rebotaba en el backend.
- **Situación 7 (Propia de ayer):** Resuelta la contradicción con *"Cajón listo para iniciar"*. La tarjeta muestra directamente:
  - Kicker: `TURNO PENDIENTE DE CIERRE`
  - Título: `Tu caja sigue abierta`
  - Bajada: *«Abierta ayer a las 18:30. Cerrá este turno para poder comenzar el día.»*
  - Botón: `Cerrar` (hacia `/caja/{id}/cierre`).
  - La alerta superior de bloqueo se oculta cuando `$cajaPropia` ya está atendida en la tarjeta, evitando tarjetas amarillas duplicadas pegadas.
- **Botones con verbo canónico:** `Cobrar`, `Registrar`, `Resumen`, `Caja`, `Cerrar`, `Abrir` (cumplimiento estricto de `SKILL.md` y `DESIGN-RULES.md`).

---

## 3. Pruebas permanentes en la suite

Se creó el archivo permanente de pruebas:
`tests/Feature/InicioOperativoTest.php` (8 tests, 99 aserciones).

Cubre exhaustivamente las 8 situaciones:
1. `test_situacion_1_recien_llega_nadie_abrio`: Cajón listo para iniciar, botón `Abrir`.
2. `test_situacion_2_turno_propio_abierto_hoy`: Mostrador activo, botones `Cobrar`, `Registrar`, `Resumen`.
3. `test_situacion_3_companero_abrio_hoy`: Turno en curso, Caja abierta de Marcos Peña, botón `Caja`. Sin botones trampa.
4. `test_situacion_4_sandra_cerro_y_espera_validacion`: Turno cerrado, última caja informada, botón `Abrir`. Y 4b cuando entra Marcos (Cajón listo para iniciar, botón `Abrir`).
5. `test_situacion_5_caja_rechazada`: Alerta de caja rechazada, botón `Abrir`.
6. `test_situacion_6_companero_dejo_abierta_ayer`: Turno en curso, no ofrece Abrir, botón `Caja`.
7. `test_situacion_7_propia_abierta_ayer`: Turno pendiente de cierre, Tu caja sigue abierta, botón `Cerrar`. No contradice con "Cajón listo para iniciar".
8. `test_situacion_8_de_noche`: Mostrador activo nocturno, botones `Cobrar`, `Registrar`, `Resumen`.

### Verificación dinámica contra botones muertos
Cada test ejecuta `verificarBotonesTarjeta()`, que:
1. Extrae todos los `<a href="...">` dentro de `#tarjeta-cajon`.
2. Ejecuta un GET real con el usuario autenticado a cada URL de acción.
3. Asegura `$res->getStatusCode() === 200` y `$this->assertFalse($res->isRedirect())`.
Si a futuro alguien agrega un botón que rebote, dé 403 o falle, la suite fallará automáticamente.

---

## 4. Resultado de la suite completa

Corrida sobre base `wings_testing_gemini`:
- **518 pruebas** (516 aprobadas, 2 omitidas).
- **4.172 aserciones**.
- `DocumentacionNoMienteTest` en VERDE: `ESTADO-ACTUAL.md`, `CHECKLIST-CARLOS.md` y `PLAN-PRODUCCION.md` sincronizados en 518 pruebas.

---

## 5. Evidencia generada

- **Carpeta de evidencia:** `docs/06-pruebas/PRU-02/evidencia/a24-vuelta-2/`
- **Visor interactivo de propuestas:** `docs/06-pruebas/PRU-02/evidencia/a24-vuelta-2/visor.html`
- **Capturas finales de las 8 situaciones:**
  - `docs/06-pruebas/PRU-02/evidencia/a24-vuelta-2/finales/situacion-1-desktop.png` y `...-mobile-375.png`
  - `docs/06-pruebas/PRU-02/evidencia/a24-vuelta-2/finales/situacion-2-desktop.png` y `...-mobile-375.png`
  - `docs/06-pruebas/PRU-02/evidencia/a24-vuelta-2/finales/situacion-3-desktop.png` y `...-mobile-375.png`
  - `docs/06-pruebas/PRU-02/evidencia/a24-vuelta-2/finales/situacion-4-desktop.png` y `...-mobile-375.png`
  - `docs/06-pruebas/PRU-02/evidencia/a24-vuelta-2/finales/situacion-5-desktop.png` y `...-mobile-375.png`
  - `docs/06-pruebas/PRU-02/evidencia/a24-vuelta-2/finales/situacion-6-desktop.png` y `...-mobile-375.png`
  - `docs/06-pruebas/PRU-02/evidencia/a24-vuelta-2/finales/situacion-7-desktop.png` y `...-mobile-375.png`
  - `docs/06-pruebas/PRU-02/evidencia/a24-vuelta-2/finales/situacion-8-desktop.png` y `...-mobile-375.png`

---

## 6. Lo que NO se verificó

1. **Despliegue a servidor / producción:** No se desplegó a producción ni al servidor (fuera del alcance del requerimiento).
2. **Acceso de roles ajenos al mostrador:** El tablero operativo `/operativo` está destinado a `OPERATIVO` (y `ADMIN`). No se ensayaron pantallas de administración global fuera del flujo de mostrador.
