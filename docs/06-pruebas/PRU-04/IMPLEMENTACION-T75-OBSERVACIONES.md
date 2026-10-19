# T75 — Correcciones a las cinco observaciones de Carlos, 18/10/2026

**Seguimiento:** Carlos eligió «Jerarquía fija y consistente». Aplicación y galería actual en [Jerarquía T75](IMPLEMENTACION-T75-JERARQUIA.md).

Esta entrega anterior se conserva como evidencia histórica. No reemplaza la aprobación anterior de T36/T37 ni da por aceptada T75.

## Alcance pedido

Carlos observó Eliminar movimiento, Nueva clase, Liquidación, Inicio operativo y Revisión.
Pidió comprobar el mismo problema en el resto y mostrar únicamente esas cinco pantallas.
[Galería enfocada](evidencia-t75-observaciones/GALERIA.html): antes/después, 360/375 y escritorio;
el selector Pie permite revisar el final. Las demás capturas son evidencia interna, sin tarjetas en la galería.

## Cambios realizados

- Eliminar movimiento: el cuerpo sumaba 24 px dentro de una tarjeta que ya tenía padding.
  Se elimina esa suma en móvil; Motivo, divisor y acciones comparten borde. El mismo patrón se corrige
  en las secciones de Cashflow, detalle e historial que lo repiten.
- Fecha: Nueva clase y Editar clase tenían `max-width:220px`. La regla móvil común para fechas,
  horas y mes a ancho completo evita ese tope, respetando el ancho de cada campo.
- Liquidación: importes de encabezado, valor/hora, total, subtotales y pie usan el mismo estilo
  `ds-money-value` (Inter, 0.9rem, 700, color-text, cifras tabulares). El conteo usa 0.85rem/600.
  El resumen angosto usa filas con importes a la derecha; también se adapta el resumen por comisión.
- Inicio: Caja, Cobranza y Alumnos son atajos de navegación existentes. Tenían `ds-btn--sec`, una
  variante inexistente. Usan ahora `secondary`, conservando destinos y agrupación a la derecha.
- Revisión: `x-ds.toggle` elige Activo/Inactivo dentro del formulario. La nota y Confirmar se
  conservan. Cambiar la elección no manda ninguna petición. El texto explica la consecuencia.
  Se conservan servicio, controlador, rutas, permisos y decisiones financieras: CONTINUA crea la cuota
  del período; INACTIVO da de baja sin condonar deudas anteriores.

Reglas añadidas al [Design System](../../03-diseno-ui/design-system/DESIGN-RULES.md).
JavaScript externo en `ds-app.js`; sin código incrustado nuevo.

## Comprobaciones actuales

- 99 respuestas reales de Laravel a 360/375/1366: 297 mediciones, cero desbordes horizontales.
- Comparación anterior de las cinco vistas: 15 mediciones, HTML literal conservado de la entrega aadbb75.
  Las respuestas nuevas se obtienen del código actual; no se inventa una maqueta.
- 285 PNG guardadas con huellas: cinco vistas anteriores y pantallas afectadas posteriores.
- Nueva clase a 375: fecha, grupo y ambas horas de 280 px. Clase única/recurrente y edición:
  seis casos, campos al ancho disponible; importes de Liquidación uniformes y conteos menores.
  Estrés de maquetación con $9,999,999,999.99 en DOM sintético sin guardar: sin recortes ni desborde.
- Interruptor a 360/375/1366: ambos valores, aviso y aria correctos; no envía al cambiar, exige
  nota y envía CONTINUA/INACTIVO al confirmar. Submit interceptado: no es evidencia de escritura.
  Las reglas financieras se comprueban en la suite del servidor.
- Liquidación por comisión: respuesta real adicional y tres mediciones, importes uniformes y sin desbordes. Tablero/documentos después del registro final: 7 pruebas/34 aserciones aprobadas.
- Build y compilación/limpieza de vistas correctos. Suite completa Codex: **857 aprobadas, 3 omitidas, 0 fallidas, 7692 aserciones, 946.92 s**, salida 0. [Salida](evidencia-t75-observaciones/suite-final.txt) y [fuentes cubiertas](evidencia-t75-observaciones/fuentes-suite.json).

## Consulta del barrido — resuelta en el seguimiento

El barrido calculó estilos reales, no solo buscó nombres de clases. Hay 29 candidatos en 20 casos
(incluyen variantes del mismo módulo y roles). [Datos](evidencia-t75-observaciones/auditoria-importes.json).
Un mismo texto con otro estilo puede ser resumen/fila o estados distintos, no demuestra por sí
solo un defecto. Se consultó a Carlos si todos los importes de cada vista deben tener un tamaño
único o si debe haber una jerarquía fija y consistente. No se cambian esas jerarquías por inferencia.
Los colores semánticos de ingresos, egresos, deuda y pagado no se confunden con diferencias arbitrarias.

## Reproducir

Instrumental fuera de `tests/`, guardado en [evidencia-t75-observaciones](evidencia-t75-observaciones/).
Se usa únicamente `APP_ENV=testing`, `DB_DATABASE=wings_testing_codex` y
`LARAVEL_STORAGE_PATH=storage/app/t75-observaciones-runtime` con rutas absolutas al ejecutar.
`CapturaT75Test.php` con T75_PREPARAR=1 recrea exclusivamente esa base descartable.
Nunca correrlo durante otra suite Codex. Los HTML privados quedan en storage/app/html del runtime.
`servidor.php` sirve respuestas literales y assets históricos/finales; `recorrer-local.mjs antes|despues`
usa iframe de ancho real. `comprobar-interruptor.mjs`, `comprobar-campos.mjs` y `publicar.py`
comprueban comportamiento y publican la galería restringida a las cinco vistas.

Sin despliegue ni cierre. Carlos revisa el aspecto; el cambio de interacción de Revisión requiere
control independiente además de esa aprobación.
