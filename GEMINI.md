# Wings — Guía e Instrucciones para Gemini (Antigravity)

Este archivo es la puerta de entrada y contexto propio para **Gemini (Antigravity)**.
No modifica ni interfiere con `AGENTS.md` (Codex) ni con `CLAUDE.md` (Claude Code).

---

## 1. REGLA OBLIGATORIA AL INICIAR

Apenas comiences cualquier sesión de chat o tarea en cualquier computadora (CyE o casa de Carlos):

1. **Leer inmediatamente `docs/00-estado/LOG-GEMINI.md`**:
   Es la memoria compartida entre sesiones. Allí está el estado vivo, los acuerdos tomados con Carlos y Vanina, y los pendientes inmediatos.
2. **Revisar también `docs/00-estado/LOG-CODEX.md` y `docs/00-estado/LOG-CLAUDE.md`**:
   Para estar al tanto de los cambios realizados por los otros agentes en paralelo y no duplicar esfuerzos.
3. **Consultar `docs/00-estado/ESTADO-ACTUAL.md`**:
   Es la fuente de verdad del estado funcional de la aplicación.

---

## 2. IDENTIDAD Y FIRMAS EN LA BITÁCORA

Cada avance, decisión o cambio producido por Gemini debe quedar registrado en `docs/00-estado/LOG-GEMINI.md`.

Identidad obligatoria según la computadora donde estés trabajando:

| Computadora | Firma obligatoria |
|---|---|
| Oficina CyE | **LOG GEM CYE** |
| Casa de Carlos | **LOG GEM CAB** |

- **Entradas más nuevas arriba.**
- Registrar: objetivo de la sesión, cambios reales, decisiones con Carlos/Vanina, pruebas ejecutadas y el próximo paso pendiente.

---

## 3. REGLAS ESPECÍFICAS DE WINGS

### A. El diseño visual no se toca sin autorización
- `resources/views/**` y `resources/css/app.css` están protegidos.
- Solo se tocan si Carlos lo pide de manera explícita y por escrito.
- El hook `.git/hooks/commit-msg` exige que cualquier commit que modifique vistas incluya al final del mensaje:
  ```text
  Diseno-autorizado: <motivo real dado por Carlos>
  ```

### B. Formato de Recibos y Comprobantes (DomPDF)
- Los recibos se renderizan con **DomPDF (CSS 2.1)**.
- **Prohibido Flexbox y CSS Grid:** Usar exclusivamente estructuras de `<table>` con anchos porcentuales y `border-collapse: collapse;`.
- Tipografías soportadas: `'DejaVu Sans', Arial, sans-serif` para texto y `'DejaVu Sans Mono', monospace` para montos.
- Formato de papel: A5 vertical (`148mm × 210mm`, márgenes `10mm 12mm 10mm 12mm`).
- **Los montos e importes nunca van en rojo** (el rojo es exclusivo para la alerta superior de anulación). El color estándar de importes es `#0F172A`.

### C. Antes de cerrar una tarea
```bash
php artisan test                                    # Todos los tests deben pasar
php -l <cada archivo PHP o Blade tocado>            # Sin errores de sintaxis
php artisan view:cache && php artisan view:clear    # Las vistas compilan sin error
git diff --stat -- resources/views resources/css    # Solo lo autorizado
```

### D. Definición de terminado (no dejar documentos mintiendo)
- Si una tarea modifica el estado de un módulo o bug, actualizar `docs/00-estado/ESTADO-ACTUAL.md`.
- Actualizar siempre la bitácora `docs/00-estado/LOG-GEMINI.md` con la firma correspondiente (**LOG GEM CYE** o **LOG GEM CAB**).
