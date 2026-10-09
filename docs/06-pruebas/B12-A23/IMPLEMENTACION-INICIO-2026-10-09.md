# B12/A23 — Inicio aplicado y Reportes replanteado

09/10/2026 · Codex CyE · desarrollo local, sin despliegue.

**Revisión posterior:** Carlos pidió más visual y menos lectura, con tres referencias.
[Propuesta V3 vigente](PROPUESTA-VISUAL-2026-10-09.md); V2 de este informe se conserva
como antecedente, no aprobación. Carlos aprobó V3 después: «Si, mucho mejor»;
[aplicación y conexión](IMPLEMENTACION-REPORTES-2026-10-09.md). Inicio mantiene el aspecto aprobado.

## Inicio aplicado

Carlos aprobó las capturas de Inicio: **«Ok, aprobado»**. Esa aprobación no incluye
Reportes, cuyo aspecto original rechazó explícitamente.

`WebController::adminDashboard` usa el mismo `ReporteMensualService`, siempre para
el mes actual. La vista habitual muestra ingresos, egresos, cuotas pendientes,
profesores por pagar, resultado aislado y disponible acumulado por tipo de caja.
Se muestran también cajas configuradas con cero; el escenario conserva sus valores.
Sin migración histórica se informa indisponibilidad, conservando los avisos.

Los cuatro avisos abren listados existentes, sin enviar mensajes:

- Cajas: todas las CERRADA, incluso meses anteriores, con `pendientes=1`.
- Liquidaciones: todas CERRADA y pago PENDIENTE, cualquier mes; excluye abiertas y pagadas.
- Clases: filtro existente `estado=finalizada`, que identifica pendientes de lista.
- Revisión: filtro existente `estado=PENDIENTE`.

Se conservan los controles ADMIN, OPERATIVO, PROFESOR y primera carga.
**Conexión posterior V3:** Ver y cuatro cifras llevan al mismo mes y sus detalles.
El estado deshabilitado se conserva en las capturas históricas de esta entrega.
[Entrega conectada](IMPLEMENTACION-REPORTES-2026-10-09.md). A23 sigue en curso junto a B12.

[Inicio escritorio](capturas/inicio-aplicado-escritorio.png) ·
[Inicio marco375](capturas/inicio-aplicado-marco-375.png).
Son respuestas de la ruta habitual autenticada, capturadas en Chrome; tokens FICTICIO.
El marco hace maquetar a 375 reales. CSS y componentes compartidos intactos.

## Verificación real y alcance

- Suite completa antes del ajuste P1 del seeder: **532 aprobadas/2 omitidas,
  4286 aserciones, 280,70 s**, exclusivamente `wings_testing_codex`.
- Después del ajuste P1: cuatro pruebas HTTP, **4/42**, 36,29 s.
- Después: módulo completo **16/108**, 170,51 s; sin fallos.
- Sintaxis de nueve archivos PHP/Blade correcta; compilación y limpieza Blade correctas.
- Build con Chart.js correcto: 22,70 s, módulo de gráfico 153,99 KB (54,17 KB gzip).
- `npm audit`: tres avisos en concurrently/shell-quote/source-map-js; las tres
  versiones son idénticas al lock anterior. No aplicar actualizaciones ajenas a esta tarea.

Un subagente independiente leyó código y realizó solicitudes HTTP con transacción
MySQL de solo lectura, sesiones/cache en memoria y rollback. Inicio respondió 200
y mostró los seis importes esperados; los cuatro enlaces respondieron 200 y cada
listado tuvo un pendiente, igual al contador. ADMIN autorizado, OPERATIVO/PROFESOR
403 en Inicio/liquidaciones, anónimo redirigido a login. PROFESOR se simuló mediante
un User en memoria porque el escenario no tenía uno: no se insertó una cuenta.
Ausencia de esquema histórico simulada en memoria: Inicio 200 con cifras
indisponibles y avisos conservados; este HTTP no usó una base físicamente anterior.
No se atribuye esta verificación a Claude/Gemini ni acredita todas las pantallas B12.

Ese control encontró P1 PENDIENTE en el escenario persistente, antes ocultado por
el TestCase que finaliza P1. Se registró antes de corregirlo; el seeder ficticio ahora
lo finaliza, sin detalle de importación ni Deshacer. El test fuerza P1 PENDIENTE antes
del seeder. El acceso HTTP posterior fue verificado. No se retiró el gate del club.

Salidas locales conservadas en `storage/app/a23-suite.txt`,
`a23-p1-corregido.txt` y `b12-a23-16-final.txt`; no contienen datos del club.
El escenario se restaura después de las suites, únicamente en la base del agente.

## Nueva propuesta financiera, no aplicada

Carlos pidió Reporte Alumnos e Ingresos/egresos separados, con gráficos útiles;
añadió análisis de sueldos por deporte/profesor, gasto/ingreso y gasto/asistencia.
Decisiones: costo de clases del mes, cuotas reales del mismo mes netas de
bonificaciones/condonación, y cada asistencia presente por separado.

La propuesta financiera se renderiza desde Blade de ensayo mediante solicitud
autenticada a Laravel, fuera de las vistas habituales. Usa datos ficticios verificables:
comparación septiembre/agosto, explicación por rubros, seis meses cerrados con
gráfico de líneas y detalle desplegable; octubre aparte. Los contraasientos conservan
su signo. No saltear meses sin clasificar para fabricar comparaciones.

[Finanzas escritorio](capturas/finanzas-v2-escritorio.png) ·
[Finanzas marco375](capturas/finanzas-v2-marco-375.png) · [visor](visor.html).
Los filtros muestran aspecto: **no están conectados**. No se aprobaron estas imágenes
por el hecho de autorizar el desarrollo. Una revisión independiente comprobó gráfico
renderizado, legibilidad, conciliación y contraasientos en fuente; Carlos elige diseño.
El script de captura admite solo `wings_testing_codex` y usa sesión/cache en memoria.

## Pendientes que impiden cerrar B12/A23

Pantalla financiera elegida y conectada, controles en curso. Implementar Reporte Alumnos y análisis Sueldos;
resolver atribución de cuotas a profesores y cobertura histórica de importes/costos;
preparar escenario salarial realista con varias clases/profesores y asistencias;
terminar clasificación de antecedentes;
verificar lógica y permisos de esas entregas por otro agente y aspecto por Carlos.
No cambiar reglas de comisión/pago para construir los indicadores. No presentar
como cero ausencia de historia ni como datos reales el escenario ficticio.

Base del club sin cambios. Sin despliegue. B12/A23 continúan en curso.
