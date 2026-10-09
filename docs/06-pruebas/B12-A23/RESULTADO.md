# B12/A23 — motor e historial, ensayo 09/10/2026

Autor: Codex CyE. [24 decisiones de Carlos](../../07-evaluacion/ACUERDOS-B12-A23-2026-10-09.md).
En desarrollo: pantallas habituales y rutas de Reportes aún no reemplazadas.

## Datos ficticios y cálculos

`ReportesEscenarioSeeder` admite solo `wings_testing_codex` sin alumnos.
24 alumnos en Patín/Fútbol y dos niveles; cuotas de abril a octubre; tres gastos
comunes mensuales; saldo inicial en cuatro cajas; cajas validada/pendiente;
aporte/retiro del dueño, cobro tardío, deuda anterior, liquidaciones abierta/cerrada,
clase sin asistencia y Revisión pendiente. No se cargaron estos datos en la base del club.

Todos los importes del servicio se devuelven en centavos enteros.

| Octubre, corte al día 9 | Importe esperado |
|---|---:|
| Ingresos del negocio, confirmado + pendiente | $660.000 |
| Egresos del negocio | $75.000 |
| Resultado sin arrastre/aportes/retiros | $585.000 |
| Disponible acumulado, incluido pendiente | $4.360.000 |
| Aporte / retiro del dueño | $90.000 / $40.000 |
| Cuotas por cobrar: octubre / anteriores | $120.000 / $570.000 |
| Profesores por pagar, solo cerradas | $90.000 |
| Cada uno de los cuatro avisos | 1 |

Cierre agosto: deuda $600.000; cierre septiembre: $570.000. El pago de septiembre
no borra la deuda de agosto. El ingreso de agosto cargado en octubre permanece en
agosto. Validar dos veces la misma caja conserva resultado/disponible y elimina el
pendiente, sin asiento repetido. Gastos del club no se descuentan en cada deporte.

## Historia y cobertura

Migración nueva, no ejecutada en la base del club: registra un saldo conocido con
fecha de activación, sin retrofecharlo. Eventos atómicos junto al cambio de cuota,
liquidación o saldo inicial. Conserva importes anteriores, condonación, ajustes,
anulación, cierres/pagos y fecha real de cobros retroactivos; FK limpia los eventos
cuando Deshacer borra las filas importadas. El ensayo acredita cobertura ficticia
desde abril; esto no acredita esa cobertura en otra base.

Antes del corte conocido: deuda/disponible se devuelven como indisponibles, no cero.
Si una cuota se crea recién al cobrar, no se inventa que existía antes de su creación.
Alumnos activos son los de hoy; no representan un padrón histórico.
Confirmación es la validación actual del movimiento, aplicada a su fecha real,
de acuerdo con FIN-04; no equivale al estado de validación que tenía al cerrar ese mes.

`afecta_caja` conserva su función operativa. `clasificacion_resultado` distingue
NEGOCIO/APORTE/RETIRO; se congela junto con deporte y naturaleza ingreso/egreso.
Metadatos originales acompañan el espejo validado y la anulación. Cambiar el tipo
del rubro después no cambia el signo anterior; editar el subrubro de un movimiento
editable actualiza su clasificación. Sueldos identificados por FK son NEGOCIO.
Los movimientos viejos sin clasificación no se adivinan: resultado queda indisponible
y se informa cantidad sin clasificar. Falta terminar el circuito ADMIN para clasificarlos.

## Verificación y siguiente paso

Doce pruebas permanentes cubren conciliación, cierres, fechas retroactivas,
descuentos, condonación, anulación, validación idempotente, sueldos existentes/nuevos,
cambio de deporte/rubro/subrubro, Deshacer y rollback. Suite completa: 528 aprobadas,
2 omitidas, 4244 aserciones, 276,58 s; 09/10 en `wings_testing_codex`.
Ensayo físico del esquema anterior: cuota, cashflow y pago de sueldo correctos;
migración con sueldo/cuota existentes correcta. Script guardado en
`scripts/reportes/comprobar-base-anterior.php`, con bloqueo de cualquier otra base.
Escenario ficticio completo restaurado después de los controles.
Sintaxis de archivos tocados y compilación Blade correctas; sin cambios en vistas
habituales ni CSS. Revisión independiente encontró casos adicionales y se corrigieron;
segunda comprobación de código aprobada: creación/actualización, FK, descuentos
retroactivos, sueldo nuevo/existente, deporte/naturaleza congelados y edición manual.
El revisor comprobó código/sintaxis, incluido el script del esquema anterior;
no ejecutó la suite ni tocó la base. Su aprobación corresponde al código revisado.
Falta verificación dinámica independiente de permisos/pantallas finales; no cerrar B12/A23.

[Cinco capturas guardadas y visor](visor.html): Inicio/Reportes escritorio y marco375,
más control login. HTML producido por solicitudes reales a Laravel autenticado;
propuestas Blade fuera de `resources/views`, con tokens/componentes existentes.
Sin maqueta HTML que simule respuestas de aplicación. Credenciales/tokens no publicados.

Siguiente: Carlos elige primero el aspecto de Inicio; terminar conexión de filtros,
detalle, clasificación histórica y accesos a listados; verificar lógica/permisos con
otro agente antes de cerrar. Sin despliegue.

## Repetir el escenario

No correr al mismo tiempo que la suite de Codex. El script verifica la base antes
de borrarla y volver a crearla; ninguna otra base está admitida.

```powershell
$env:DB_DATABASE='wings_testing_codex'
php scripts/reportes/preparar-escenario.php
```

Para renovar HTML real, usar `WINGS_CAPTURAS=1` y la prueba
`seis_meses_y_mes_actual_concilian_sin_arrastre_en_el_resultado`, luego capturar
escritorio o marco375 en Chrome. No cambiar la base del club para mirar estas imágenes.
