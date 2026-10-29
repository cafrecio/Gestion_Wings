# Prueba humana — días 24 a 28

Codex CAB · ejecución real 29/10/2026 · sitio de prueba `test.gestionar-te.com.ar` · datos ficticios wingstest.

Recorrido terminado hasta el 6/11 simulado. **Tanda 2 no ejecutada; requiere revisión de Claude. Caja #53 de Rocío abierta a propósito.** No repetir relojes, cobros ni rutinas de esta tanda.

Informes: [día 24](../DIA-24.md), [25](../DIA-25.md), [26](../DIA-26.md), [27](../DIA-27.md), [28](../DIA-28.md). Incluyen los 42 pasos de tabla, desglosados cuando un paso tuvo partes con distinto resultado, y el resumen diario del 27. Las 50 filas resultantes son 39 cumple, 4 no cumple y 7 no probado; no representan 50 casos independientes del catálogo.

| Día | Deuda | Activos: al día / en plazo / deudor | Ingresos | Egresos | Disponible | Sin asistencia |
|---|---:|---|---:|---:|---:|---:|
| 24 | $3.525.000 | 104: 11 / 93 / 0 | $401.000 | $243.500 | $5.014.400,75 | 0 |
| 25 | $3.155.000 | 106: 22 / 84 / 0 | $855.000 | $2.657.780 | $3.054.120,75 | 0 |
| 26 | $2.849.000 | 107: 30 / 76 / 1 | $1.283.000 | $2.661.280 | $3.478.620,75 | 0 |
| 27 | $2.456.000 | 107: 39 / 67 / 1 | $1.676.000 | $2.661.280 | $3.919.620,75 | 0 |
| 28 | $2.119.000 | 106: 47 / 58 / 1 | $1.980.000 | $2.667.780 | $4.217.120,75 | 0 |

Los cinco cierres financieros coinciden con el guion. El último disponible incluye $297.500 sin validar: EFE $145.500 y MP $152.000 de la caja abierta. Morosos 0 todos los días.

## Diferencias conservadas para Claude

- **24.6:** ficha de Iván muestra 8 días de gracia frente a 7; no se observó aviso explícito de cuota de noviembre al 100% antes del alta.
- **26.8:** cifras intermedias del guion $233.200 → $235.500 frente a $261.200 → $263.500 reales. Diferencia $28.000, importe de Santino ya cobrado. La noche del mismo guion sí espera $263.500 y coincide.
- **28.1:** Nueva liquidación en octubre ofrece Hernán 19 y Martín 1 clases liquidables frente a ninguna esperada. Confirmado dos veces por pantalla; no se generó otra liquidación ni se investigó causa en código/base.
- **28.2:** Candela queda inactiva correctamente, pero su ficha no muestra fecha de inactivación. Cobranza y Reportes sí muestran 06/11/2026.

## No probado o pendiente

- **24.4 y 27.7:** contenido de recibo #2 y recibo de Agustina #254. Los enlaces se tocaron, pero las pestañas efímeras desaparecieron antes de poder inspeccionarlas. No se afirma PDF correcto.
- **24.8:** constancia interna mensual tras segunda generación, a revisar por Claude sin repetir la generación ni cobrar de nuevo.
- **27.0:** recepción única de resumen por mail y Telegram en el teléfono de Carlos y contenido exacto. Se respetó la espera; no se forzó un envío.
- **27.3:** aceptación efectiva de una nueva alta con el DNI liberado en Patín; no se volvió a crear otra ficha para probarlo. Alta errónea eliminada y ficha Fútbol conservada por pantalla.
- **27.4 y 27.6:** los rechazos al eliminar Victoria y clase con asistencias no se pudieron provocar: el botón Eliminar estaba ausente incluso para el dueño. No se usaron rutas forzadas. La cancelación del 3/11 sí se ejecutó y conserva 12 marcas.

No se probaron tampoco las exclusiones que el guion declara: pago de liquidación con saldo insuficiente, ASI-013 e ni adulteraciones/rutas técnicas; corresponden a la revisión de Claude.

## Evidencia y límites

145 capturas JPEG originales del navegador, 170 TXT, 2 JSON y un registro MD intermedio: 318 archivos de evidencia, más este índice y los controles. Los TXT conservan accesibilidad o consola. La consola de 24.8 tiene salida TXT, sin captura de terminal. Las capturas son de viewport y algunas no muestran todo el texto; el TXT complementa lo que no entra. No se fabricaron pantallas ni se editaron imágenes.

[Manifiesto SHA-256](MANIFIESTO-SHA256.csv) · [Control de enlaces y decodificación](CONTROL-ENTREGA.json). El control acredita integridad y cobertura documental; no sustituye resultados de negocio. `continuidad.json` y `observaciones.md` son registros intermedios preservados; el estado final es el informe del día 28.

Todo el negocio se operó desde menús/formularios de Chrome. Consola remota solo para reloj, rutinas y 24.8. No se consultó ni modificó directamente la base. No se certificó el SHA desplegado del servidor; el guion declara su propia base de preparación. Git local cambió por trabajo concurrente ajeno durante el recorrido; no se incorporó ese trabajo a esta entrega. Sin cambios propios de producto, vistas, CSS, catálogo o tablero.

Siguiente paso: Claude revisa estas diferencias y pendientes antes de autorizar días 29–33. No se pide ni se declara cerrado ningún caso global del catálogo con esta entrega humana.
