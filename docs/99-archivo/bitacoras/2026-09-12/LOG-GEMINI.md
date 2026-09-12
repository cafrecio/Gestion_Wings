# Wings — Bitácora compartida de Gemini (Antigravity)

Este archivo es la memoria compartida entre las distintas sesiones y computadoras de Gemini (Antigravity).
Permite continuar el trabajo sin depender de los chats locales de la aplicación.

---

## Reglas operativas para Gemini (Antigravity)

1. **Lectura obligatoria al iniciar:**
   - Todo agente Gemini (Antigravity), apenas comience un chat o sesión, **debe leer este archivo (`docs/00-estado/LOG-GEMINI.md`) antes de responder o realizar cualquier acción**.
   - También debe revisar `docs/00-estado/LOG-CODEX.md` y `docs/00-estado/LOG-CLAUDE.md` para conocer el estado y coordinación con los otros agentes.
2. **Identidad obligatoria al firmar:**
   - En la oficina CyE: **LOG GEM CYE**
   - En la casa de Carlos: **LOG GEM CAB**
3. **Estructura de las entradas:**
   - **Entradas más nuevas arriba.**
   - Registrar: objetivo, cambios reales realizados, archivos generados o modificados, decisiones acordadas con Carlos/Vanina, pruebas ejecutadas y pendiente inmediato para la próxima sesión.
4. **Reglas de diseño del proyecto (`AGENTS.md`):**
   - `resources/views/**` y `resources/css/app.css` no se modifican sin autorización explícita de Carlos.
   - Cualquier commit que toque vistas requiere incluir la línea al final del mensaje:  
     `Diseno-autorizado: <motivo de Carlos>`

---

## 2026-09-11 — LOG GEM CYE — Pase de ENT-02 (Diseño Recibos PDF) a Gemini CAB

Carlos continuará la jornada desde su casa. Dejo aquí la reseña exhaustiva y el estado exacto de la tarea para que **Gemini CAB** retome el trabajo sin perder ningún detalle.

### 1. Objetivo de la sesión en CyE: Tarea ENT-02 ("SOLO DISEÑO")
- Carlos solicitó diseñar desde cero los comprobantes en PDF tanto para el **Cobro de Cuotas de Alumnos** como para el **Pago de Salarios / Liquidaciones Docentes**, cumpliendo con el motor **DomPDF** (CSS 2.1, tablas HTML, sin flexbox ni grid).
- **Regla aplicada:** Se mantuvo intacto el código productivo en `resources/views/**` (cero modificaciones en producción durante la fase de diseño).

### 2. Feedback y Aprobación de Carlos y Vanina
- Vanina (usuaria final del sistema) dio el **OK formal a la propuesta visual** ("Me gustó, la usuaria dio el OK").
- Carlos especificó las siguientes directivas de diseño:
  1. **Logo institucional:** Fondo oscuro (`#0F172A`) tipo marco con borde fino `#1E293B` para que contraste con nitidez el patinador blanco y los círculos rojos originales (en fondo blanco puro el patinador se perdía).
  2. **Colores de importes:** **Prohibido el color rojo en montos/totales**. El rojo es exclusivo para alertas. Los importes se presentan en slate neutro de alta gama (`#0F172A`).
  3. **Liquidaciones docentes (2 páginas A5):**
     - **Página 1 (Resumen Ejecutivo):** Datos del profesor, período, total clases/alumnos, monto neto liquidado, medio de pago e imputación contable (`Sueldo - Apellido, Nombre`), con firma de conformidad.
     - **Página 2 (Anexo Detallado):**
       - Si es por **Hora**: Detalle cronológico de clases dictadas (fecha, grupo/horario, horas dictadas, estado de validación y subtotal).
       - Si es por **Comisión**: Detalle de alumnos con cobro verificado (alumno, cuota cobrada, % de comisión aplicado y subtotal comisionado).
  4. **Recibo de Cuota Cancelado / Anulado (1 página A5):**
     - Misma estética general en azul pizarra / negro (`#0F172A`).
     - **Únicamente el encabezado superior en rojo:** Banner de alerta `[ ✗ RECIBO ANULADO — EL PAGO FUE CANCELADO ]`.
     - **Trazabilidad completa en Observaciones y encabezado:** Detalle de fecha de emisión, fecha de cobro original, fecha de cancelación, cancelado por (ej. `Vanina - Administradora`) y motivo de reversión.

### 3. Entregables generados y disponibles en el repositorio
Todos los archivos de diseño listos para consultar viven en `docs/03-diseno-ui/`:
- **`PREVIEW-RECIBOS.html`**: Muestrario interactivo navegable con 4 pestañas:
  1. *Cobro Cuota (Alumnos)*
  2. *Cobro Cuota (Anulado con Trazabilidad)*
  3. *Salarios por HORA (Hoja 1 + Hoja 2 Anexo Clases)*
  4. *Salarios por COMISIÓN (Hoja 1 + Hoja 2 Anexo Alumnos)*
- **Muestras PDF reales generadas con DomPDF:**
  - `MUESTRA-RECIBO-CUOTA.pdf` (1 página A5 exacta)
  - `MUESTRA-RECIBO-CUOTA-ANULADO.pdf` (1 página A5 exacta)
  - `MUESTRA-RECIBO-LIQUIDACION-HORA.pdf` (2 páginas A5 exactas)
  - `MUESTRA-RECIBO-LIQUIDACION-COMISION.pdf` (2 páginas A5 exactas)
- **Instructivo de Implementación:**
  - `INSTRUCTIVO-IMPLEMENTACION-RECIBOS.md`: Documento paso a paso para trasladar las plantillas a `resources/views/pdfs/recibo-cuota.blade.php` y `recibo-liquidacion.blade.php`, con código Blade completo listo para copiar, variables de `ReciboService.php`, verificaciones y formato de commit.

### 4. Estado de Git y coordinación con Codex
- Codex CyE completó en paralelo la tarea de backend **FIN-03** (persistencia de datos y snapshot de anulación en `pagos.detalle_anulacion`) en commit `c1bef8a`.
- Los archivos de diseño en `docs/03-diseno-ui/`, `docs/08-Logo/` y este `LOG-GEMINI.md` quedan listos en el repositorio para que Carlos pueda hacer `git pull` en su casa (CAB).

### 5. Guía para Gemini CAB (Próximos pasos en casa de Carlos)
Apenas Carlos abra el chat en su casa:
1. Confirmar que leíste este log y que conocés el estado exacto de **ENT-02**.
2. Si Carlos pide revisar el diseño, invitarlo a abrir [docs/03-diseno-ui/PREVIEW-RECIBOS.html](file:///c:/xampp/htdocs/Gestion_Wings/docs/03-diseno-ui/PREVIEW-RECIBOS.html) o cualquiera de los 4 PDFs.
3. Si Carlos pide implementar en producción:
   - Seguir punto por punto [docs/03-diseno-ui/INSTRUCTIVO-IMPLEMENTACION-RECIBOS.md](file:///c:/xampp/htdocs/Gestion_Wings/docs/03-diseno-ui/INSTRUCTIVO-IMPLEMENTACION-RECIBOS.md).
   - Reemplazar `resources/views/pdfs/recibo-cuota.blade.php` con el Blade de la sección 4.1.
   - Reemplazar `resources/views/pdfs/recibo-liquidacion.blade.php` con el Blade de la sección 4.2.
   - Ajustar `app/Services/ReciboService.php` para suministrar las variables requeridas (datos de auditoría y `$detalles`).
   - Ejecutar la suite de pruebas: `php artisan test`, `php -l ...`, `php artisan view:cache && php artisan view:clear`.
   - Commitear con el tag obligatorio para el hook de diseño:  
     `Diseno-autorizado: Carlos y Vanina aprobaron el rediseno de recibos ENT-02`
   - Registrar el cierre en `docs/00-estado/ESTADO-ACTUAL.md` (cerrando ENT-02).
   - Agregar una nueva entrada en este archivo firmando como **LOG GEM CAB**.
