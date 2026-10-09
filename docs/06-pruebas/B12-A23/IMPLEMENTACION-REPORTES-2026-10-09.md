# B12/A23 — Ingresos y egresos V3 aplicado

09/10/2026 · Codex CyE · desarrollo local, sin despliegue.

Carlos eligió la [propuesta V3](PROPUESTA-VISUAL-2026-10-09.md) mirando las
capturas de escritorio y375: **«Si, mucho mejor»**. Se aplica ese aspecto:
tarjetas, tendencias, evolución, desglose gráfico y detalle plegado.
Alumnos y Sueldos siguen siendo entregas pendientes de B12.

## Conexión real

- Ruta `web.reportes.index`, `/reportes`, solo ADMIN activo y con P1 completada.
- Ver y cuatro cifras de Inicio llevan al mismo mes y a ingresos/egresos/deuda/profesores.
- Filtros GET de mes y deporte conservan selección; meses futuros/formatos incorrectos
  y deportes inexistentes se rechazan. Los deportes inactivos permiten consulta histórica.
- Mes cerrado seleccionado incluido en tarjetas/evolución, incluso si no tiene movimientos.
  Mes actual separado; comparación anterior solo cuando hay clasificación suficiente.
- Ingresos/egresos por fecha real, netos firmados; confirmados y por validar separados
  dentro del detalle, sin duplicación. Aportes/retiros fuera del resultado del negocio.
- Por cobrar: filas al corte, mes/anteriores separados. Por pagar: liquidaciones al corte.
  Nombres actuales son etiquetas de personas; no reconstruyen su estado pasado.
- Disponible conserva alcance de todo el negocio, aunque se filtre deporte. Gastos del
  club aparte, sin descontarlos del deporte. Sin cobertura se informa falta de historial.

El motor `ReporteMensualService` no cambia. No se agrega menú compartido ni se modifica
`app.css`, layouts o las otras pantallas; Inicio activa enlaces ya previstos.
La cifra de Inicio lleva a su apartado; deuda/profesores se despliegan mediante su ancla.
No cambia liquidaciones,
comisiones, cuotas ni movimientos. Sin migración de Reportes, la ruta informa503.

## Verificaciones

Revisión independiente de fuente confirmó permisos, validaciones, conciliación de
filas y etiquetas. Encontró dos casos y se corrigieron: guard P1 no enumeraba la
ruta nueva, y meses sin movimientos quedaban fuera de las tarjetas.
Regresiones permanentes para ambos. Capturas revisadas: filtros completos y gráficos legibles, sin recorte horizontal.
Se amplió escritorio a2100 para incluir el pie; el filtro sin gastos informa
«Sin egresos registrados». Después de ese texto: **2 pruebas/34 aserciones,17,55s**.
Chrome desplegó deuda/profesores desde sus anclas sobre la respuesta real guardada
(`storage/app/reportes-aplicado-anclas.txt`).
El primer ensayo también encontró que el fixture
no creaba una cuenta PROFESOR; corregido con usuario de prueba específico.

Módulo: **21 aprobadas, 191 aserciones, 121,41s** en `wings_testing_codex`.
Suite completa: **537 aprobadas/2 omitidas, 4369 aserciones, 476,20s**, misma base.
Salidas locales `storage/app/reportes-aplicado-modulo.txt` y `reportes-aplicado-suite.txt`.
PHP/JS sin errores; vistas compiladas/limpiadas. Build13,07s, JS visual5,08KB,
gzip2,26KB; assets anteriores conservados para que sus capturas sigan abriendo.

Revisor independiente de esta conversación aprobó la fase financiera tras abrir
las capturas finales, HTML, fuentes y registros. No ejecutó suite ni consultó la base.
No se atribuye esa revisión a Claude/Gemini ni se cierra B12/A23.

## Capturas finales

Solicitudes autenticadas a las rutas habituales, escenario ficticio en base de Codex,
sesión/cache en memoria; HTML sanitizado (CSRF sustituido por FICTICIO).
Cuatro respuestasHTTP200; escenario restaurado después de la suite y controles finales.
Escritorio1440 y celular dentro de un iframe375 real; login de calibración conservado.

- [Finanzas escritorio](capturas/finanzas-aplicado-escritorio.png) · [celular](capturas/finanzas-aplicado-marco-375.png).
- [Septiembre escritorio](capturas/finanzas-septiembre-escritorio.png) · [celular](capturas/finanzas-septiembre-marco-375.png).
- [Deporte escritorio](capturas/finanzas-deporte-escritorio.png) · [celular](capturas/finanzas-deporte-marco-375.png).
- [Inicio conectado escritorio](capturas/inicio-con-reportes-escritorio.png) · [celular](capturas/inicio-con-reportes-marco-375.png).
- [Visor](visor.html). Propuestas anteriores y aprobación preservadas.

No cerrar B12/A23 por esta fase: faltan Alumnos, análisis Sueldos, atribución de
cuotas a profesores, historia analítica, clasificación de antecedentes y sus controles.
Base del club intacta. Sin despliegue.
