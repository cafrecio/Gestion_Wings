# T75 — Jerarquía fija de importes, 18/10/2026

Carlos eligió «Jerarquía fija y consistente». El criterio ya no está pendiente.
Se aplica a los candidatos encontrados en el barrido anterior y al detalle de Liquidación.

## Regla aplicada

| Función | Clase | Tamaño / peso |
|---|---|---|
| Total de página, indicador financiero y monto final | `ds-money--total` | 1.1rem / 800 |
| Resumen agrupado, valor/hora y cierre de tabla | `ds-money--summary` | 0.9rem / 700 |
| Movimiento, detalle individual, deuda de un alumno y anotación | `ds-money--row` | 0.8rem / 600 |

La clase base `ds-money` fija Inter, cifras tabulares, interlínea 1.5 y espaciado normal.
El color conserva el significado del contexto: ingreso, egreso, deuda o estado. Cantidades
secundarias de Cobranza y bloques financieros de Sueldos dejan de superar al dinero principal.
Las cifras de los gráficos y su formato anterior quedan fuera de esta regla.
[Design System](../../03-diseno-ui/design-system/DESIGN-RULES.md).

## Cambios concretos

Ocho vistas: Detalle/Resumen de Caja, Cobranza, Inicio administrativo, Cashflow,
Liquidación, Ingresos y egresos, y Reporte de sueldos. Hay 73 definiciones de presentación
clasificadas; los bucles generan sus importes con el mismo nivel.

En celular, los grupos que no admiten montos largos se apilan: indicadores, totales de caja,
saldos, desglose junto al gráfico y detalles de Reportes. Se mantiene la fuente de cada nivel.
Las columnas de importes miden 176 px en ambos tamaños de pantalla; las tablas conservan
su scroll. Las cantidades quedan en segundo plano dentro de los bloques financieros.

No cambian valores, cálculos, consultas, permisos, rutas, estados ni JavaScript.
Se compararon literalmente las expresiones Blade y los bloques PHP de las ocho vistas
contra `a8ab707`: son iguales. Los cambios son envoltorios de presentación, clases y CSS.

## Evidencia

[Galería enfocada](evidencia-t75-jerarquia/GALERIA.html): **solo las cinco pantallas observadas**,
antes/después a 360, 375 y escritorio. Esta decisión cambia Liquidación dentro de esa selección;
las demás se conservan para revisar el conjunto pedido. Las pantallas financieras adicionales
quedan como evidencia interna y no se incorporan a la galería visible.

- Respuestas literales de Laravel con datos sintéticos y `wings_testing_codex`, sin base del club.
- Recorrido final: 99 casos a tres anchos (297 medidas), más Liquidación por comisión (3);
  cero desbordes. 181 PNG publicados con huella, cinco vistas en la galería.
  [Control de integridad](evidencia-t75-jerarquia/integridad.json).
- 126 controles de jerarquía: 21 variantes de los módulos afectados a 360/375/1366, con detalles abiertos, repetidas con signos positivos y negativos.
  Tamaños y pesos exactos por función; pruebas de maquetación con $9,999,999,999.99 en el DOM,
  sin guardar. Cero desbordes y cero importes fuera de su contenedor.
- [Asignación por expresión](evidencia-t75-jerarquia/asignacion.json), [control de jerarquía](evidencia-t75-jerarquia/jerarquia.json)
  y [fuentes cubiertas](evidencia-t75-jerarquia/fuentes.json), con huellas normalizadas CRLF→LF.
- Build correcto, ocho vistas sin errores de sintaxis y vistas compiladas/limpiadas.
- Suite completa: **858 aprobadas, 3 omitidas, 0 fallidas**, 7704 aserciones, 1921.19 s.
  Incluye una prueba temporal concurrente de T39 (12 aserciones), ya retirada por su autor;
  la base permanente de ese corte era 860 métodos. No certifica cambios posteriores de otros agentes.
- Las ocho vistas finales son idénticas a las que entraron en la suite. Después se ajustaron
  solamente dos reglas móviles de Cashflow: un exceso de 5 px con importe negativo a 360 y
  un padding duplicado de 14 px. Se renovaron build, respuestas Laravel y controles de navegador.
  [Fuentes de la suite](evidencia-t75-jerarquia/fuentes-suite.json) y
  [ajuste CSS posterior](evidencia-t75-jerarquia/ajuste-css-final.json).

## Reproducir y límites

El instrumental de [evidencia-t75-jerarquia](evidencia-t75-jerarquia/) está fuera de la suite permanente.
Usar `APP_ENV=testing`, `DB_DATABASE=wings_testing_codex` y el almacenamiento exclusivo
`storage/app/t75-jerarquia-runtime`, con ruta absoluta. `T75_PREPARAR=1` recrea solamente
esa base descartable. No ejecutar el renderer mientras corre otra suite Codex.

`CapturaT75Test.php` genera respuestas reales. `T75_SOLO_COMISION=1` agrega la variante por comisión.
`servidor.php` sirve HTML y assets reales, `comprobar-jerarquia.mjs` abre detalles y comprueba
fuente/espacio, y `recorrer-local.mjs` fotografía dentro de marcos de ancho real.
`publicar.py` mantiene la selección de cinco vistas y guarda las capturas afectadas internamente.
Los montos de estrés se inyectan solo para medir maquetación; no prueban una escritura financiera.

Sin despliegue ni autocierre. Carlos revisa el aspecto; Claude conserva el control independiente
de la interacción de Revisión entregada en la vuelta anterior. T36 y T37 permanecen cerradas.

Guardianes finales: 7 aprobados/34 aserciones. Al ejecutarlos había 862 métodos en la carpeta
compartida: 860 del corte versionado y dos nuevos de T58 ajenos a esta entrega. El conteo
862 se conservó separado mientras las pruebas de T58 no estaban versionadas. Su autor
las versionó en `c154ac4` durante el commit T75: el ajuste documental posterior deja 862
también en Estado y Resumen, sin incorporar código de T58 a esta entrega.
