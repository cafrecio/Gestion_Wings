# Acceso a Reportes — 09/10/2026

Codex CyE. Carlos aprobó Sueldos: «Esta OK, solo deberiamos ver desde donde accede
en el menu». Después pidió «Continuar». Se aplicó la ubicación propuesta:
**Plata → Reportes**, debajo de Liquidaciones. Carlos no eligió expresamente una
de las opciones de ubicación; se continuó con la recomendada, informándolo en el chat.

Una entrada abre Ingresos y egresos; sus tres opciones superiores permiten entrar
a Alumnos y Sueldos conservando mes/deporte. Reportes queda destacado en el menú
en cualquiera de las tres pantallas. Sin cambios de reglas de negocio o permisos.

## Evidencia

- Menú dentro del bloque ADMIN existente; rutas siguen ADMIN activo y protección P1.
- Capturas de **56 pantallas** reales de Laravel en escritorio/iframe375:
  **112 PNG**, más **4 PNG** del menú móvil abierto con un clic real.
- Inventario recorre GET autenticados con vista; aliases/redirecciones se registran.
  Excluye respuestas JSON, previews y descargas, que no muestran este menú.
  Las páginas inaccesibles en el estado ficticio quedan identificadas sin fabricar HTML.
- OPERATIVO/PROFESOR: su inicio abre200, sin entrada Reportes; las tres rutas Reportes403.
  Profesor usa identidad activa de prueba en memoria si no existe una en el escenario;
  no se crea ni exporta una cuenta. La base comprobada es solo wings_testing_codex.
- Sintaxis PHP/JavaScript correcta; vistas compiladas/limpias.
- Control independiente aprobado: fuente/documentos y muestra de12 PNG; no se
  certificó cada una de las112 imágenes individualmente. Captura Sueldos rehecha:
  marco375, contenido3702px; imagen900×3804 con cuatro docentes y pie completos.
- Medidas de56 marcos: todos ancho375;55 con scroll375. Caja da scroll465 y conserva
  ese ancho al quitar la entrada nueva del DOM real. No se corrigió ese contenido
  fuera del alcance del menú; no atribuirlo al enlace ni certificar toda Caja responsive.
- Suite completa propia: **573 aprobadas/2 omitidas,4616 aserciones,885,32s**.
  Resultado real en wings_testing_codex; [salida y comprobaciones](EVIDENCIA-ACCESO-2026-10-09.txt).
- Sin cambios en app.css, componentes, rutas, permisos, base del club o servidor.

[Menú y pestañas](menu/visor.html) · [Inventario real](menu/inventario.json).
Reproducible con scripts/reportes/capturar-menu.php y capturar-menu.mjs, sobre el
escenario ficticio Sueldos de la entrega anterior; el generador también tiene --principales.

## Aprobación — 10/10/2026

Carlos: **«Esta OK el acceso»**. Acceso **Plata → Reportes**, debajo de Liquidaciones,
y navegación entre los tres reportes aprobados. Verificador visual: **Carlos**
(AGENTS.md §6a). Las capturas aprobadas se conservan en [el visor del menú](menu/visor.html).

## Pendiente

Aspecto del ajuste final, prueba manual y clasificación de antecedentes de B12.
B12 sigue abierto. A23 ya fue cerrado por Claude el 10/10;
[verificación de Inicio](VERIFICACION-A23-INICIO.md). Esta aprobación no cierra B12.
