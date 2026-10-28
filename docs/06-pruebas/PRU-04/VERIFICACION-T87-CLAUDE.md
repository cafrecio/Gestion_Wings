# Verificación de T87 (Gemini) — Claude CyE, 27/10/2026

> **Actualización 28/10/2026:** Carlos aprobó el aspecto: «T87 Visualmente OK, asumo que el funcionamiento logico esta bien». Con la lógica ya aprobada por Claude el27/10, T87 queda cerrada en el tablero. [Galería revisada](APROBAR-T87.html). El informe de abajo conserva su fecha y resultados originales.

**Lógica: APROBADA.** Falta el visto bueno de Carlos del aspecto (cambio mínimo). Entrega en
[`IMPLEMENTACION-T87.md`](IMPLEMENTACION-T87.md), commit `b079b0ca`.

- `LiquidacionWebController::create`: el número de clases liquidables y el aviso de clases sin asistencia cuentan
  por mes y año, con las mismas reglas de antes. Qué profesoras se pueden elegir no cambió (sigue el total de todos
  los meses). `LiquidacionService` intacto.
- `liquidaciones-create.js`: al cambiar mes o año actualiza cada fila y el aviso, sin recargar. Sin JavaScript en la vista.
- `NuevaLiquidacionConteoMesT87Test` 2/2 en `wings_testing_claude`. **Con el código anterior la de la pantalla falla**
  y la del cálculo pasa, como corresponde (el cálculo no cambió). Prueba técnica de liquidaciones 5/5.
- Captura de septiembre revisada: Lucía 3 liquidables y 1 sin asistencia, mismo estilo de siempre.

**Aspecto, para Carlos:** cuando el mes elegido tiene 0 clases, la fila muestra «0 clases liquidables» en gris y la
profesora sigue elegible. Antes ese caso no se veía. [Capturas](evidencia-t87/).

**Contador de pruebas corregido:** Gemini declaró 1098 contando 19 pruebas de Codex (T88/B3) que estaban en la
carpeta compartida sin commitear. En el repositorio hay 1079; los tres documentos quedaron en ese número.

**Menor, anterior a T87:** «1 clase sin asistencia — no se liquidarán» mezcla singular y plural.
