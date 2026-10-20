# Auditoría de uso de todas las pantallas — orden de Carlos, 20/10/2026

Carlos: «Cuando estemos haciendo la prueba con Codex, Gemini va a probar todas esas cosas. No quiero que
el sistema sea una verga.» Origen: en T43 cada validación recargaba la pantalla, volvía arriba y destellaba.

**Quién y cuándo:** Gemini, en paralelo con la prueba manual de Codex en el sitio de prueba.
**Qué:** recorrer en Chrome real, con clics, todas las pantallas de cada rol (dueño, operativa, profesora),
a 1366 y 375, en su base `wings_testing_gemini` con datos sintéticos realistas. No corrige: registra.

Buscar:
1. Acciones que recargan la página cuando alcanzaba con actualizar una parte; vuelta arriba; destellos;
   pérdida de lo escrito en otro lado (DESIGN-RULES §8-bis).
2. Totales, contadores o etiquetas que no se actualizan o dicen algo falso.
3. Botones amontonados, en lugares distintos entre pantallas, fuera de §9, o con más de un verbo.
4. Texto en inglés, mensajes técnicos, errores que no dicen qué hacer.
5. Cortes o desbordes a 375; tablas ilegibles en teléfono.
6. Lentitud de más de 1 segundo al abrir o guardar (contar consultas).
7. Pasos de más; confirmaciones innecesarias.
8. Errores de consola o avisos de la política de seguridad (CSP).

Entrega: `AUDITORIA-USO-GEMINI.md` (un hallazgo por fila: pantalla, rol, ancho, cómo se reproduce, qué
pasa, qué debería pasar, captura, gravedad Frena/Molesta/Detalle) y `AUDITORIA-USO.html` para Carlos con
las capturas, por pantalla y gravedad. Solo lo reproducido con clic es hallazgo. No crear tareas: decide
Carlos. No tocar código, vistas ni estilos. Prueba = `wingstest` (la usa Codex), producción = `wings`.
