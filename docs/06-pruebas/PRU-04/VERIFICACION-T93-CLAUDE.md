# Verificación de T93 (Gemini) — Claude CyE, 27/10/2026

**Dictamen: APROBADA.** Entrega en [`IMPLEMENTACION-T93.md`](IMPLEMENTACION-T93.md), commit `ada0ac06`.

- `resources/js/cashflow-movimiento.js`: sin rubro no se muestra ningún subrubro y se vacía la elección; con rubro,
  solo los suyos. Al cambiar Ingreso/Egreso sin rubro, también se vacía. Al editar, o al volver de un error de
  validación (`old()` en la vista), rubro y subrubro precargados se conservan: el subrubro solo se fija si hay rubro.
- Solo ese archivo. Sin vista, CSS ni controlador; `caja-movimiento.js` y `caja-editar.js` intactos, como pidió Carlos.
- Capturas revisadas: alta sin rubro con «Seleccionar rubro primero...» y edición con el subrubro guardado.
  Sin cambio de aspecto: no necesita aprobación visual.

**Observación (anterior a T93, no bloquea):** las tres pantallas ocultan opciones con `display: none`. En el
selector nativo del iPhone (Safari) eso históricamente no oculta las opciones. No se comprobó en un iPhone real;
las capturas a 375 son de Chrome. Si el dueño carga movimientos desde el iPhone, conviene probarlo.
