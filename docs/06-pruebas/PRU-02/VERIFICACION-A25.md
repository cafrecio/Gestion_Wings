# Verificación independiente de A25 — Gemini CyE, 06/10/2026

## 1. Identificación y alcance
- **Defecto:** A25 — La apertura de caja no contempla saldo inicial ni cambio para vuelto.
- **Implementó:** Codex (commit `30f38f8`).
- **Verificó:** Gemini (regla AGENTS.md §6a: lo que hace uno, lo controla otro).
- **Base de datos de prueba:** `wings_testing_gemini` (AGENTS.md §6-bis).
- **Dictamen:** **APROBADO — CERRADO 06/10/2026**.

---

## 2. Verificación de Código y Suite de Pruebas

Se ejecutó la suite completa de A25 sobre la base propia de pruebas:
```bash
$env:DB_DATABASE='wings_testing_gemini'; php artisan test tests/Feature/CajaCambioInicialA25Test.php tests/Feature/CajaArqueoConcurrenteA25Test.php
```
**Resultado:** 26 pruebas pasadas, 142 aserciones, 0 fallos.

### Reglas de negocio comprobadas en código (`CajaCambioInicialA25Test`):
1. **Apertura explícita:** Apertura rechaza si no se declara efectivo o falta confirmación (`apertura_falla_sin_declarar_efectivo`, `apertura_falla_sin_confirmar`).
2. **Herencia de cambio:** Hereda el cambio retenido del último turno del club (`apertura_hereda_cambio_del_ultimo_turno`).
3. **Corrección con motivo:** Si se modifica el monto heredado, exige motivo explícito y almacena ambos importes (`apertura_modificada_exige_motivo`).
4. **Cajón operativo independiente:** Cobros realizados por ADMIN no contaminan el saldo esperado del cajón del turno operativo (`cobro_admin_no_contamina_saldo_esperado`).
5. **Arqueo y diferencias:** El arqueo de cierre compara efectivo contado vs esperado, calcula diferencia (faltante/sobrante) y retiro/entrega (`cierre_calcula_diferencia_y_retiro`).
6. **Validación condicionada:** ADMIN debe contar el efectivo antes de poder validar o rechazar el turno (`admin_debe_contar_antes_de_validar`).
7. **Cajas rechazadas:** Al corregir una caja rechazada, se preservan el conteo y la entrega originales (`caja_rechazada_conserva_conteo_al_corregir`).

### Protección concurrente (`CajaArqueoConcurrenteA25Test`):
1. Evita doble apertura concurrente o turnos simultáneos abiertos para el mismo club.
2. Evita desfasajes por cobros tardíos durante el arqueo de cierre.

---

## 3. Verificación Visual en Pantalla

Se verificaron las 10 capturas reales aprobadas por Carlos (en escritorio y en marco de 375 para celular):
- `docs/06-pruebas/PRU-02/evidencia/a25/capturas/01-apertura-escritorio.png`
- `docs/06-pruebas/PRU-02/evidencia/a25/capturas/01-apertura-celular-375.png`
- `docs/06-pruebas/PRU-02/evidencia/a25/capturas/02-cierre-arqueo-escritorio.png`
- `docs/06-pruebas/PRU-02/evidencia/a25/capturas/02-cierre-arqueo-celular-375.png`
- `docs/06-pruebas/PRU-02/evidencia/a25/capturas/03-detalle-turno-escritorio.png`
- `docs/06-pruebas/PRU-02/evidencia/a25/capturas/03-detalle-turno-celular-375.png`
- `docs/06-pruebas/PRU-02/evidencia/a25/capturas/04-validacion-admin-escritorio.png`
- `docs/06-pruebas/PRU-02/evidencia/a25/capturas/04-validacion-admin-celular-375.png`
- `docs/06-pruebas/PRU-02/evidencia/a25/capturas/05-historial-turnos-escritorio.png`
- `docs/06-pruebas/PRU-02/evidencia/a25/capturas/05-historial-turnos-celular-375.png`

**Observaciones de UI comprobadas:**
- La pantalla de apertura muestra claramente el cambio esperado/heredado, campo de conteo real y motivo en caso de discrepancia.
- La pantalla de arqueo muestra la grilla con desglose de saldo inicial, cobros en efectivo, egresos, saldo esperado, efectivo contado, diferencia y dinero a retirar/dejar.
- En móvil a 375px (dentro del marco real), los formularios y acciones caben sin desbordes horizontales ni solapamientos.

---

## 4. Estado en Tablero y Seguimiento
- **Tablero único:** Actualizado con `php scripts/tablero/tablero.php cambiar A25 estado=cerrado verifica=Gemini`.
- **DEFECTOS.md / DEFECTOS.html:** Marcado como `CERRADO 06/10`.
