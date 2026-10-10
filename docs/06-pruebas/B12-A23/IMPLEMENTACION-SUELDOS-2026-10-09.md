# Sueldos y monto final — entrega en desarrollo, 09/10/2026

Codex CyE. Implementación solicitada por Carlos; **sin despliegue ni cambios en base del club**.
Alumnos aprobado («Esta OK») e integrado. Sueldos aprobado por Carlos:
«Esta OK, solo deberiamos ver desde donde accede en el menu». Ajuste final por revisar.

## Resultado

- `/reportes/sueldos` ADMIN/P1, filtro mes/deporte y navegación conservando contexto.
- Costos del mes por deporte y profesor, cuotas reales atribuidas por asistencias,
  porcentaje costo/cuotas, costo por cada asistencia y evolución de seis meses cerrados.
- Comisión sobre cuotas cobradas del período, incluidas parciales. Reparto analítico
  no divide ni cambia la comisión completa de cada profesor.
- Alumnos con pagos registrados (personas únicas) y Asistencias sin pago de cuota
  (cada presencia). Parcial no equivale a cuota totalmente cancelada.
- ADMIN activo ajusta monto final de comisión ABIERTA o CERRADA sin pagar, con motivo.
  Cálculo/detalles conservados. Auditoría anterior/nuevo/calculado/actor/fecha/motivo.
- Ajuste y pago bloquean la misma fila. Formulario anterior rechaza importe cambiado.
  Pagada/cancelada/HORA sin ajuste. Fallo de historial revierte importe y auditoría.
- Pago, recibo, pendientes y resúmenes usan monto final. Recibo distingue original,
  ajuste y monto final; egreso/idempotencia coherentes. Sin esquema, guardia amigable.

## Historia y límites

Estados monetarios conocidos al corte desde activar; **Sin historial** antes.
Cuotas netas reales, tarifa/modalidad, cobros y liquidaciones conservados; no inferir
historia real anterior. Activación exige mantenimiento y detener escrituras externas,
CLI y workers. No se activó en el club.

Horarios y asistencias se leen actuales. Corregirlos cambia estimados/reparto;
la liquidación cerrada conserva importe. No es matrícula histórica ni ganancia neta.
Dos docentes en una clase: presencia global una vez; incidencia por docente para
repartir cuota y analizar su costo. Cambio de deporte no mueve clases anteriores.

## Verificaciones

- Historial **9 pruebas/71 aserciones**,108,70s, base wings_testing_codex.
- Sueldos/ajuste **17/95**, incluido el control de asistencias por deporte; incluye Hora90min, codocentes, cambio de deporte,
  falta de historia, cuotas faltantes, parciales, permisos/P1 y consultas sin escritura.
- Revisor independiente `verificar_continuidad`: fuente y **26/166**,15,62s en
  wings_testing_claude. Hora90min/falta cobertura, deporte/filtro y recibo negativo
  comprobados realmente. El fixture de las9 pruebas de historia se hizo mínimo y
  portable: no abre la guardia del seeder de capturas ni obliga a compartir bases.
- Concurrencia real en dos procesos/conexiones, solo wings_testing_claude:
  ajuste primero ⇒ pago espera3,098s y egreso-30000; pago primero ⇒ ajuste espera2,551s,
  rechazado, cero auditorías y un egreso. Transacciones de lectura adicional revertidas.
- Sintaxis de los33 archivos PHP nuevos/modificados correcta; vistas compiladas/limpias;
  recursos finales construidos34,40s.
- Suite completa: **573 aprobadas/2 omitidas,4615aserciones,997,45s**.
  Después del fixture portable, módulos y guardianes **30/185**,14,02s verdes.
  Retoques posteriores exclusivamente visuales: distribución de tarjetas/COMISION,
  texto Cálculo original y porcentaje congelado aplicado; compilación y HTTP/capturas correctos.
- Catorce rutas autenticadas HTTP200, **30 capturas web** y dos páginas de recibo
  real DomPDF. Marco375/scroll375 en6 contextos; inspección detectó recorte interno
  COMISION y se corrigió con distribución1/3; guardias de ancho solas no lo detectaban.
- Reloj del escenario/captura9Oct18:00: costo202000 =90000+52000+42000+18000;
  cuotas atribuidas600000,80presencias,33,7%,2525por asistencia. PDF18000, original12000, ajuste6000.
- Control visual independiente de15 PNG: gráficos y contenidos completos; formularios
  solo donde corresponde. Septiembre302000/720000=41,9%; Patín224000/360000=62,2%.
  El PDF conserva su presentación previa al borde: no hay caracteres fuera de página.
  No se modificaron sus márgenes. Sueldos aprobado por Carlos; ajuste final por revisar.
- Publicación GitHub comprobada: main remoto a132bfd6975dcff88ac728388addfab73623940d.

## Capturas reproducibles

`ReportesSueldosEscenarioSeeder` admite solo wings_testing_codex vacía de alumnos.
Construye historia **ficticia** desde abril, tarifas ficticias conocidas desde ese corte,
clases/presencias y dos comisiones cerradas: una ajustada pendiente y otra ajustada pagada.
No copiar este historial a la base del club.

Preparar: APP_ENV=testing y DB_DATABASE=wings_testing_codex, ejecutar
`scripts/reportes/preparar-sueldos.php` (guarda DB antes de cualquier DDL),
`scripts/reportes/capturar-aplicado.php`, generador de imágenes --sueldos y
`scripts/reportes/capturar-ajuste-abierto.mjs` (Playwright del runtime, NODE_PATH).

Capturas pedidas a Laravel autenticado, recursos reales; escritorio1440 y iframe375
dentro de ventana900, con control login375 previo. [Visor](visor.html). [Resumen de salidas reales](EVIDENCIA-SUELDOS-2026-10-09.txt).
Motor/servicio solo lectura; pruebas y escenario no exportan users ni dump.

## Siguiente

Acceso anterior: Inicio → Ver → enlace Sueldos al pie de Ingresos y egresos.
Tras «Continuar», acceso **Plata → Reportes** aplicado y tres opciones arriba;
[capturas y alcance](ACCESO-REPORTES-2026-10-09.md). Acceso aprobado por Carlos el 10/10: «Esta OK el acceso».
Después, revisión visual del ajuste final, prueba manual integral de B12/A23 y
clasificación de antecedentes. B12 sigue abierto; A23 cerrado por Claude el 10/10
([verificación](VERIFICACION-A23-INICIO.md)). La aprobación del acceso no incluye el ajuste final.
