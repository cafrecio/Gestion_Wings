# Índice de evaluación — v02

**Versión:** v02
**Fecha:** 2026-08-03
**Rama:** `main` · **Commit auditado:** `b31f8d7`
**Informe completo:** [ANALISIS-INTEGRAL-v02.md](./ANALISIS-INTEGRAL-v02.md)

---

## Nota sobre el versionado

No existía una convención de versiones previa: los archivos anteriores son `ANALISIS-INTEGRAL.md` e `index.html`, **sin sufijo**. Se los trata como **v01** y **no fueron modificados, renombrados ni eliminados**. Esta es la primera versión numerada.

---

## Alcance

Auditoría técnica, funcional y de seguridad completa, **sin modificar código ni datos**.

| Elemento | Cantidad |
|---|---|
| Controllers | 44 (8 de API deshabilitada) |
| Services | 15 |
| Modelos | 26 |
| Middleware | 4 |
| FormRequests | 32 |
| Migraciones | 68 |
| Vistas Blade | 75 |
| Rutas web | 127 |
| Tablas en BD | 35 |
| Tests | 4 archivos |
| Documentos `.md` | 46 |

**Fuera de alcance:** `routes/api.php` está deshabilitada en `bootstrap/app.php`; se audita solo como riesgo latente.

---

## Resumen de hallazgos por severidad

| Severidad | Cantidad |
|---|---|
| **CRÍTICA** | 7 |
| **ALTA** | 14 |
| **MEDIA** | 14 |
| **BAJA** | 6 |
| **Total** | **41** |

**Por estado de verificación:** 39 CONFIRMADO · 2 PROBABLE · 0 presentados como confirmados sin evidencia.

---

## Principales riesgos

### 1. El sistema dejó de facturar hace tres meses y no avisa

Última deuda emitida: **2026-05**. Hoy es agosto. **Junio, julio y agosto sin facturar**, y los 35 alumnos activos figuran `AL_DIA`.

Tres fallas encadenadas, ninguna produce error visible:

- **C-01** — nada ejecuta `schedule:run`: el comando mensual nunca se dispara.
- **F-02** — aunque corriera, no crearía ninguna deuda: la ventana de elegibilidad está mal calculada.
- **F-03** — sin deuda emitida, el estado cae en `else` → `AL_DIA`. La falta de facturación se reporta como salud.

Agravante: **F-05** — no existe forma de emitir deuda de un período pasado desde la aplicación, así que esos tres meses **no son cobrables** por la vía normal.

### 2. El módulo Cobranza está caído

**A-01** — `/cobranza` devuelve 500 siempre: `ORDER BY nombre` sobre `grupos`, columna que una migración eliminó. Arreglo de 10 minutos.

### 3. El control de acceso por rol es decorativo

- **S-01** — un PROFESOR puede editar alumnos y **registrar cobros reales** escribiendo la URL.
- **S-02/P-01** — un usuario desactivado puede iniciar sesión y operar normalmente.
- **P-02** — un operativo puede anular el cobro de otro operativo.

### 4. No hay red de seguridad

**T-01** — la suite de tests tiene **12 de 14 fallando**; los 2 que pasan son stubs. Cobertura de negocio: **0%**.

### 5. Bugs latentes en el circuito de dinero

Hoy no se ven porque la función que los dispara nunca se usó (**0 movimientos cancelados y 0 pagos anulados en toda la base**). Se activan con el primer uso de "Cancelar":

- **F-01/D-04** — los movimientos cancelados entran igual al cashflow al validar la caja.
- **D-05** — cancelar un cobro no borra sus imputaciones: rompe el invariante de forma permanente.
- **D-01** — sobrepago silencioso: se registra más de lo que se imputa.
- **D-03, D-06** — faltan locks: doble validación y doble cobro concurrente.

---

## Estado general por área

| Área | Estado | Comentario |
|---|---|---|
| Funcionalidad y reglas de negocio | 🔴 Crítico | Facturación detenida sin aviso; liquidación cerrada irreversible |
| Seguridad | 🟠 Alto | Núcleo sano (sin SQLi/XSS/CSRF); el problema es el control de rol |
| Roles y permisos | 🔴 Crítico | Todo lo admin bien protegido; el bloque `auth` sin separar OPERATIVO/PROFESOR |
| Base de datos e integridad | 🟠 Alto | Bien construida; invariantes hoy cuadran; bugs latentes en cancelación |
| Arquitectura y calidad | 🟠 Alto | Una página caída; reglas de negocio duplicadas con criterios divergentes |
| Pruebas | 🔴 Crítico | 0% de cobertura efectiva de negocio |
| Rendimiento | 🟡 Medio | Nada urgente al volumen actual; View Composer global y falta de índices |
| Configuración | 🟠 Alto | Scheduler no corre; `APP_DEBUG=true`; dump con sesiones |
| Documentación | 🟡 Medio | 4 contradicciones concretas; la regla de mora no tiene contrato |

---

## Diferencias principales respecto de la versión anterior

La v01 cerró con **60 hallazgos** (1 crítico abierto, 15 altos, 18 medios, 22 bajos), numerados por área (S, D, B, F, UA, UO, UP, I).

**Qué cambia en v02:**

- **Numeración nueva e independiente.** Los IDs de v02 (S-, P-, D-, F-, A-, T-, R-, C-, DOC-) **no se corresponden** con los de v01. Esta auditoría se hizo desde cero contra el código actual, sin partir de los hallazgos previos.
- **No se verificó el estado de los hallazgos de v01.** Varios figuraban como resueltos en la v01; esta auditoría **no los re-auditó uno por uno** para confirmar que sigan resueltos. Se instruyó a los auditores a no confiar en esas marcas y a reportar lo que encontraran en el código actual — pero la ausencia de un hallazgo de v01 en v02 **no debe leerse como confirmación de que fue corregido**.
- **Hallazgos nuevos de mayor gravedad que cualquiera de v01:** la facturación detenida (F-02/F-03/C-01), el módulo Cobranza caído (A-01) y el estado real de la suite de tests (T-01) no aparecían en la versión anterior.
- **Contradicción detectada con la documentación previa:** `CLAUDE.md` afirma que "los tests base pasan"; la ejecución real da 12 de 14 fallando.

---

## Enlaces

- **Informe completo:** [ANALISIS-INTEGRAL-v02.md](./ANALISIS-INTEGRAL-v02.md)
- Versión anterior (sin modificar): [ANALISIS-INTEGRAL.md](./ANALISIS-INTEGRAL.md) · [index.html](./index.html)
- Reportes temáticos de v01 (sin modificar): [seguridad](./seguridad/REPORTE-SEGURIDAD.md) · [datos](./datos/REPORTE-DATOS.md) · [backend](./backend/REPORTE-BACKEND.md) · [frontend](./frontend/REPORTE-FRONTEND.md) · [admin](./funcionalidad/REPORTE-ADMIN.md) · [operativo](./funcionalidad/REPORTE-OPERATIVO.md) · [profesor](./funcionalidad/REPORTE-PROFESOR.md)

---

## Áreas que no pudieron verificarse

Detalle completo en el anexo 18.3 del informe. Las principales:

- **Explotación en vivo** de los hallazgos de acceso: la auditoría fue estática, sin peticiones autenticadas.
- **Concurrencia real** (D-03, D-06): requiere escrituras, prohibidas en esta auditoría.
- **Impacto de los bugs de cancelación**: no hay datos que los evidencien porque la función nunca se usó.
- **Si `schedule:run` corre por algún mecanismo externo** no detectable desde el repo.
- **Cobertura de código en porcentaje**: requiere Xdebug/PCOV.
