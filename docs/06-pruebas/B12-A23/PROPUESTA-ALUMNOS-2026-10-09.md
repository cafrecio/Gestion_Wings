# B12 — Alumnos, propuesta visual

09/10/2026 · Codex CyE · Carlos pidió «Segui con alumnos y sueldos».
Propuesta para elegir mirando imágenes reales; todavía no aplicada a rutas habituales.

## Datos y aspecto

Cuatro tarjetas: activos hoy, asistencias del mes, presencia sobre registros cargados
y clases sin registros. Evolución de presentes/ausentes en seis meses cerrados con
clases; distribución actual por deporte y nivel, asistencia mensual por nivel.
Detalles y seguimiento plegados. Conserva estilo V3, layout/tokens Wings.

- Matrícula siempre **actual**, también al elegir septiembre: no existe historial de
  estados/deporte/nivel del alumno que pruebe la matrícula al cierre de ese mes.
- Asistencia por fecha y deporte/nivel del grupo de la clase, sin usar deporte/activo
  actuales del alumno. Canceladas y fechas posteriores al corte excluidas.
- Cada registro presente cuenta una asistencia; alumnos distintos usa `alumno_id`
  único, no deduplicación de personas por DNI o múltiples inscripciones.
- Presencia = presentes / (presentes + ausentes **registrados**). No deduce faltas
  de una clase sin registros ni demuestra que estén cargados todos los alumnos.
- Clase sin ningún registro deja un hueco en la evolución; importes registrados
  siguen en Detalle. Un cero registrado no se sustituye por «Sin datos».
- Activos hoy sin presentes en el mes: información para mirar, no decisión de baja.
  Revisión enlaza el circuito existente. Consulta sin escrituras ni envíos.

## Capturas reales

Solicitudes ADMIN autenticadas al kernel de Laravel con ruta de ensayo registrada
solo dentro del script; tresHTTP200, sesión/cache en memoria y tokens sanitizados.
No se fabricó una maqueta HTML. Filtros procesados en ensayo, no son una ruta pública
instalada; el HTML guardado es una captura, no un servidor de navegación.

- [Octubre escritorio](capturas/alumnos-propuesta-escritorio.png) · [celular](capturas/alumnos-propuesta-marco-375.png).
- [Septiembre escritorio](capturas/alumnos-septiembre-escritorio.png) · [celular](capturas/alumnos-septiembre-marco-375.png).
- [Patín escritorio](capturas/alumnos-deporte-escritorio.png) · [celular](capturas/alumnos-deporte-marco-375.png).

Escritorio1440×2100; marco real de375×3000 dentro de ventana900×3100.
Mismo método calibrado con [login375](capturas/login-marco-375.png).
Octubre ficticio:22activos/2inactivos,80presentes/16ausentes,20registros distintos de
alumnos,83,3% sobre registros,17clases no canceladas,1sinregistros,3activos hoy sin
presentes. Fixture amplía solo asistencia y matrículas ficticias; importes financieros
del escenario original conservados. Base exclusiva `wings_testing_codex`.
Después de la suite: escenario financiero recreado con el script guardado,
seguido de `php artisan db:seed --class=ReportesAsistenciaEscenarioSeeder --force`
con `DB_DATABASE=wings_testing_codex`. Inspeccionadas cinco filas de alumnos
(Prueba, DNI30000001–5) y cinco asistencias. Control de seis importes financieros
conservados y tres respuestas HTML idénticas a las seis imágenes revisadas.

## Control

Módulo inicial:6aprobadas/29aserciones,45,50s. Luego se agregó una aserción para
ausentes históricos aun con serie incompleta. Suite completa final: **543 aprobadas/2
omitidas, 4401 aserciones, 556,59s**, siempre en `wings_testing_codex`. Salida local
`storage/app/reportes-alumnos-suite.txt`.
Build8,89s, sintaxis PHP/JS correctas; vistas habituales compilan/limpian.
Primer render500: directiva PHP abreviada se cruzaba con bloquePHP; corregida
con asignación dentro del bloque, tresHTTP200 posteriores y seis capturas renovadas.
Revisión independiente detectó CSS no cargado por stack inexistente, falta de
cabecera y ausencia histórica omitida en Detalle accesible; los tres corregidos.
Revisión final independiente aprobada: seis imágenes completas, gráficos y cifras
conciliados (septiembre173/19→90,1%). El revisor no ejecutó suite ni consultó
la base. Enlaces235/0faltantes; HTML con CSS/JS reales y Blade procesado. No cierre
B12/A23 ni despliegue.

## Sueldos: decisión y evidencia pendientes

Pregunta enviada a Carlos: repartir la cuota entre profesores proporcionalmente
a asistencias, o mostrar la cuota completa para ambos. No se eligió por él.
Una clase puede tener varios profesores, por lo que también hay que evitar duplicar
cuotas/asistencias al sumar a nivel deporte.

Cuotas netas originales se sobrescriben con descuentos/ajustes; el historial de
saldo pendiente no acredita la base devengada al cierre. Tarifa actual no acredita
tarifa histórica de clases sin liquidación. DetallesHORA congelan importes/minutos;
COMISION vigente calcula sobre pagos completados del período, no todas las cuotas
devengadas. Resolver cobertura y costo analítico de comisión antes de implementar;
no cambiar reglas reales de pago ni inventar historia. Diferencias registradas en
ESTADO-ACTUAL. Esta fase todavía no entrega Sueldos.
