# VERIFICACIÓN T26 — Historial de caja del dueño

**Resultado: APROBADO**

Verificación de la regla del Historial de caja para el dueño (`f4a030e` implementado por Claude), basada en la decisión de Carlos del 11/10/2026: *«El dueño debe ver todo, los límites no son para él»*, documentada en `docs/02-contratos/PERMISOS-ROLES.md` (sección «Historial de movimientos»).

Verificación ejecutada por Gemini en base de datos aislada `wings_testing_gemini` sin tocar el sitio de prueba ni su reloj.
Suite de pruebas automatizada e independiente creada en `docs/06-pruebas/PRU-04/evidencia-t26/VerificacionT26Test.php` (8 pruebas, 103 aserciones, 100% verde).

---

## 1. Tabla de comprobaciones

| # | Punto a verificar | Qué se probó | Resultado |
|---|---|---|---|
| **1** | Límite de fechas: >90 días para dueño, operativa acotada | - Movimiento de hace 130 días y movimiento de hace 20 días.<br>- Admin sin `desde`: arranca en últimos 90 días (no ve el de 130 días).<br>- Admin con `desde` (hace 180 días): ve ambos movimientos.<br>- Operativa con `desde` (hace 180 días): acotada estrictamente a 90 días (solo ve el de 20 días).<br>- Operativa forzando rango antiguo (`desde` 200 días, `hasta` 100 días): 0 filas devueltas. | **APROBADO**. El dueño navega cualquier fecha histórica pidiéndola por `desde`, mientras que la operativa queda confinada a la ventana de 90 días sin importar los parámetros enviados en la URL. |
| **2** | Visibilidad de rubros exclusivos del dueño | - Movimiento con subrubro `Retiro de Utilidades Dueño` (`permitido_para = ADMIN`).<br>- Movimiento común con `Artículos de Limpieza` (`permitido_para = OPERATIVO`).<br>- Admin: ve ambos movimientos en la grilla y ambos subrubros en el selector de filtros.<br>- Operativa: no ve el movimiento del dueño en la grilla ni ve el subrubro en el selector de filtros. | **APROBADO**. El dueño ve la totalidad de los subrubros del club; la operativa solo los habilitados para su rol en grilla y filtro. |
| **3** | Movimientos cancelados: visibilidad y totales | - Ingreso activo ($35.000) e ingreso cancelado ($15.000).<br>- Egreso activo ($12.000) y egreso cancelado ($8.000).<br>- Admin: ve los 4 movimientos; los cancelados muestran `· Cancelado` y estilo `text-decoration:line-through;`. `totalIngresos` = $35.000 y `totalEgresos` = $12.000.<br>- Operativa: solo ve los 2 movimientos activos; no ve `· Cancelado` ni tachados. `totalIngresos` = $35.000 y `totalEgresos` = $12.000. | **APROBADO**. El dueño ve los movimientos cancelados identificados visualmente sin distorsionar los totales contables. La operativa no los ve en ningún caso. |
| **4** | Cobro del dueño sin caja y anulado por camino real | - Alumna real (`Gomez, Martina`) con cuota de $48.000 en 2026-10.<br>- Admin cobra por `POST /caja/cobrar/{id}` sin caja abierta (`registrarPagoCuotaAdmin`).<br>- Admin anula cobro por `POST /pagos/{id}/anular` con motivo válido (`anularCobroAdmin`).<br>- Historial de caja: aparecen 2 renglones asociados a la alumna: el cobro (+$48.000, verde) y la anulación (−$48.000, rojo como asiento en contra).<br>- Ingresos netos (`totalIngresos`) para ese cobro: exactamente **$0,00**. | **APROBADO**. El camino real genera el asiento y su contraasiento en cashflow; ambos se listan con la alumna identificada y la anulación resta de los ingresos netos. |
| **5** | Restricción de acceso para la profesora | - Petición autenticada como usuaria con rol `PROFESOR` (`Valeria Profe`) a `GET /caja/historial`. | **APROBADO**. Devuelve HTTP 403 Forbidden interceptado por el middleware `reject.profesor.web`. |
| **6** | Filtros combinados y paginación para ambos roles | - Búsqueda por texto (nombre de alumno, palabra clave en observación).<br>- Filtro por `subrubro_id`.<br>- Filtro por `tipo` (`INGRESO` y `EGRESO`).<br>- Filtro por rango `desde` / `hasta`.<br>- Paginación con 35 movimientos: página 1 muestra 30 filas con `hasPages() = true`; página 2 muestra las 5 filas restantes. | **APROBADO**. Todos los controles de filtro y paginación funcionan coordinadamente para ambos roles. |
| **7** | *Caso borde no cubierto por Claude*: Caja validada en Cashflow | - Operativo abre caja, registra ingreso ($25.000) y egreso ($10.000). Cierra con conteo de efectivo.<br>- Admin valida la caja (`CajaService::validarCaja`), disparando `reflejarCajaEnCashflow` (asientos en `cashflow_movimientos` con `referencia_tipo = REF_CAJA`).<br>- Historial para admin y operativo: exactamente 2 filas (no 4). `totalIngresos` = $25.000, `totalEgresos` = $10.000. | **APROBADO**. Los asientos reflejados de cajas validadas se excluyen adecuadamente (`referencia_tipo != REF_CAJA`), impidiendo la duplicación de movimientos y montos. |
| **8** | *Caso borde no cubierto por Claude*: Recálculo de totales con filtros | - Movimientos de múltiples subrubros y tipos (activos y cancelados).<br>- Con filtro de subrubro: totales suman únicamente los movimientos activos de ese subrubro.<br>- Con filtro `tipo = EGRESO`: `totalIngresos` = $0 y `totalEgresos` refleja los egresos filtrados.<br>- Con búsqueda de texto: totales suman únicamente las filas resultantes de la búsqueda. | **APROBADO**. Los totales de la barra de estadísticas (`stats-bar`) se calculan sobre la colección ya filtrada de movimientos vigentes. |

---

## 2. Datos utilizados en la verificación

- **Usuarios:**
  - Dueño / Admin: `Carlos Admin` (rol `ADMIN`, ID dinámico).
  - Operativa: `Sandra Operativa` (rol `OPERATIVO`, ID dinámico).
  - Profesora: `Valeria Profe` (rol `PROFESOR`, ID dinámico).
- **Tipos de caja / Medios de pago:**
  - `Efectivo` (`tipos_caja`).
- **Rubros y Subrubros:**
  - Rubro Ingresos: `Cuotas` (`tipo = INGRESO`).
  - Subrubro Operativo: `Cuota Mensual` (`permitido_para = OPERATIVO`, `afecta_caja = true`).
  - Rubro Egresos: `Gastos Generales` (`tipo = EGRESO`).
  - Subrubro Operativo Egreso: `Artículos de Limpieza` (`permitido_para = OPERATIVO`, `afecta_caja = true`).
  - Subrubro Admin Solo Egreso: `Retiro de Utilidades Dueño` (`permitido_para = ADMIN`, `afecta_caja = true`).
  - Subrubro Ingreso Adicional: `Inscripción Torneo` (`permitido_para = OPERATIVO`).
- **Alumnos de prueba:**
  - Alumna camino real cobro y anulación: `Gomez, Martina` (DNI 35111222, deporte Tenis, grupo Intermedio, plan 3 clases/semana $48.000).
  - Alumnos para filtros: `Bernal, Lucas` (DNI 40111222) y `Sosa, Camila` (DNI 40333444).
- **Entorno temporal:**
  - Reloj simulado en prueba: `2026-10-12 10:00:00`.

---

## 3. Casos agregados frente a la cobertura de Claude

Claude aportó 4 pruebas iniciales en `tests/Feature/HistorialDuenioSinLimitesTest.php`. En esta verificación independiente se agregaron e implementaron los siguientes casos adicionales:

1. **Camino real HTTP completo de cobro y anulación del dueño:**
   - La prueba de Claude insertaba las filas directamente en la tabla `cashflow_movimientos` con montos positivos y negativos.
   - Aquí se verificó el flujo real de punta a punta: petición `POST route('web.caja.pagar')` que llama a `registrarPagoCuotaAdmin()`, seguida de `POST route('web.pagos.anular')` que llama a `anularCobroAdmin()`. Se confirmó que ambas filas resuelven correctamente el nombre y apellido del alumno vía `$pago->alumno` y que en la vista HTML se imprimen con los signos y estilos respectivos (`+$48.000` en verde y `−$48.000` en rojo).
2. **Caja operativa cerrada y validada por admin (prevención de duplicados):**
   - Cuando una caja operativa se valida, el servicio `CashflowIntegracionCajaService::reflejarCajaEnCashflow` copia cada movimiento operativo a `cashflow_movimientos` con `referencia_tipo = REF_CAJA`.
   - Se verificó que el controlador `CajaWebController::historial` excluya estas referencias (`referencia_tipo != REF_CAJA`) y que ni el admin ni el operativo vean duplicados los movimientos ni los totales monetarios.
3. **Comportamiento exacto de los totales con filtros activos:**
   - Se probó que al filtrar por subrubro, por tipo (INGRESO/EGRESO) o por término de búsqueda, las variables `$totalIngresos` y `$totalEgresos` recalculen sobre el subconjunto de filas activas filtradas, no sobre la base global.
4. **Protección de ruta para rol Profesor:**
   - Se verificó explícitamente que un usuario con rol `PROFESOR` reciba HTTP 403 al intentar acceder a la pantalla.
5. **Comportamiento ante forzado de fechas históricas por la operativa:**
   - Se confirmó que si la operativa envía en el querystring un rango estrictamente histórico (ej. de hace 200 días a hace 100 días), la consulta no devuelve ningún movimiento porque la ventana se acota a los últimos 90 días.
6. **Paginación:**
   - Se comprobó que con más de 30 registros, la paginación divide en páginas de 30 elementos manteniendo los parámetros y mostrando los restantes en la página siguiente.

---

## 4. Qué NO se probó

- Acceso mediante endpoints de API REST (`/api/*`), dado que la API está deshabilitada intencionalmente en `bootstrap/app.php` según AGENTS.md §7.
- Modificaciones directas en el servidor de pruebas en vivo (`https://test.gestionar-te.com.ar`) ni manipulación de su reloj.
- Motores de base de datos diferentes de MariaDB/MySQL (el sistema opera sobre MariaDB/MySQL local en XAMPP).

---

## 5. Veredicto final

**APROBADO**.
El commit `f4a030e` cumple con precisión cada una de las reglas solicitadas por Carlos el 11/10/2026 y asentadas en `docs/02-contratos/PERMISOS-ROLES.md`:
- El dueño ve cualquier fecha pidiéndola por `desde`, arrancando en 90 días por defecto.
- La operativa permanece acotada a los últimos 90 días.
- El dueño ve todos los subrubros (incluidos los exclusivos de admin) en listado y filtros; la operativa solo los permitidos para su rol.
- Los movimientos cancelados se muestran tachados con `· Cancelado` para el dueño, se ocultan para la operativa, y no suman en los totales de ninguno.
- La anulación de cobros del dueño actúa como asiento en contra con monto negativo restando ingresos, logrando un balance neto de $0,00.
- La profesora no tiene acceso (403).
- Las cajas validadas no duplican movimientos y los totales con filtros son exactos.
