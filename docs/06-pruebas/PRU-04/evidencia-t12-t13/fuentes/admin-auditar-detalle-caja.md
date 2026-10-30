# Ficha de fuentes: Auditar el detalle y arqueo de una caja

- **Página del manual:** `resources/manual/admin/auditar-detalle-caja.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/caja/{id}/detalle`
- **Controlador / Servicio:** `app/Http/Controllers/CajaWebController.php:detalle`
- **Vista Blade:** `resources/views/caja/detalle.blade.php`

---

## 1. Verificación paso a paso y casos de salida

| Paso | Acción en Wings | Resultado en código / pantalla | Captura tomada |
|---|---|---|---|
| 1 | Entrar a `/caja/{id}/detalle` | Muestra panel de datos del turno y barra con botón `Validar` si está CERRADA | `auditar-detalle-caja-paso1.png` |
| 2 | Desplazarse a la tabla de movimientos | Muestra lista cronológica, medios de pago, importes y enlaces a `Recibo` | `auditar-detalle-caja-paso2.png` |

---

## 2. Rechazos y avisos de la acción

| Situación | Qué hace Wings | Texto exacto o código HTTP | ¿Está en la página? | Sección de la página donde está | Motivo si es No |
|---|---|---|---|---|---|
| Deslizar tabla en pantallas chicas | Muestra indicación responsive para móvil | `Deslizá la tabla para ver todas las columnas.` | No | — | Aviso visual secundario visible solo en resolución móvil |
| Caja sin movimientos registrados | Muestra estado vacío | `Esta caja no tiene movimientos.` | No | — | Solo aplica a cajas recién abiertas sin actividad |
| Verificar movimientos seleccionados | Marca auditoría de renglones | `Movimientos verificados exitosamente.` | No | — | Mensaje de feedback tras marcar checkboxes de control |
