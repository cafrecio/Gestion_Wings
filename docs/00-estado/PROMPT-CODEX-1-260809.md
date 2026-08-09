# Prompt Codex 1 - 260809

## Propósito de este documento

Este archivo resume el ciclo de trabajo realizado por Codex sobre el proyecto Wings para que Claude Code pueda retomarlo sin reconstruir el contexto desde cero.

Fecha del informe: 9 de agosto de 2026.

## Estado de entrega

- Trabajo funcional terminado: **sí**, dentro del alcance descripto abajo.
- Commits funcionales y documentales previos: **15**.
- Suite automatizada al cerrar el ciclo: **33 tests, 107 assertions, sin fallos**.
- Compilación de frontend: **correcta**.
- Caché de vistas y listado de rutas: **correctos**.
- `composer validate --no-check-publish`: **correcto**.
- `database/dump.sql`: **no fue modificado**.
- Cambio local preexistente `.claude/settings.local.json`: **preservado y no incluido en los commits**.

## Qué se resolvió

### 1. Cobranza y generación mensual

- Se corrigió el error 500 de `/cobranza`. El listado de grupos ahora ordena mediante las relaciones reales de deportes y niveles.
- La cuota del mes actual se genera usando la actividad del mes anterior, que es el período ya cerrado.
- Se contempló al alumno recientemente inscripto que ya realizó un pago.
- Se eliminó el criterio anterior que exigía un pago del período previo para generar la cuota.
- Los estados de cobranza quedaron centralizados y reducidos a cuatro:
  - `AL_DIA`
  - `EN_PLAZO`
  - `MOROSO`
  - `DEUDOR`
- `DEUDOR` tiene prioridad cuando existen obligaciones anteriores impagas.
- Los días de gracia se leen desde la configuración `dias_gracia_cobranza`.
- Las pantallas de cobranza consumen una única implementación de estas reglas.

### 2. Cambios de plan

- Una baja de plan se difiere al mes siguiente si el alumno ya registró asistencia durante el mes actual.
- Si no existe asistencia del mes actual, la baja se aplica en el momento.
- Una mejora o subida de plan se aplica en el mes actual.

### 3. Roles, acceso y seguridad web

- El rol profesor quedó bloqueado para operaciones sobre alumnos y dinero: caja, alumnos, movimientos, operativo y grupos.
- El acceso a clases se conservó para profesores.
- Un usuario desactivado ya no puede iniciar sesión.
- Si el usuario es desactivado mientras mantiene una sesión abierta, la sesión se revoca en la siguiente petición.
- El autocomplete dejó de interpretar contenido almacenado como HTML y ahora lo representa como texto, cerrando una vía de XSS.

### 4. Integridad de caja y pagos

- Al cancelar un movimiento, se comprueba que pertenezca a la caja indicada en la solicitud.
- Al editar un movimiento, el subrubro se valida de forma centralizada:
  - no puede ser un subrubro reservado;
  - debe estar permitido para el operador;
  - debe afectar a caja.
- Los movimientos cancelados ya no se reflejan en el flujo de caja.
- Al cancelar un cobro se eliminan también sus imputaciones en `pago_deuda_cuota`, dentro de la misma transacción.
- Se rechazan pagos mayores al saldo pendiente de la deuda.
- La imputación registra el importe realmente aplicado.
- Se agregaron bloqueos de filas para serializar validaciones sensibles sobre caja, alumno y deuda y reducir carreras concurrentes.

### 5. Infraestructura de pruebas

- La lógica que depende de sentencias exclusivas de MariaDB/MySQL ahora evita esas operaciones cuando la suite corre sobre SQLite.
- Se agregaron pruebas de regresión para las correcciones principales.
- Se comprobó la idempotencia secuencial de las operaciones críticas.
- La presencia de bloqueos de concurrencia quedó cubierta estructuralmente.

## Verificaciones ejecutadas

```text
php artisan test
Resultado: 33 tests, 107 assertions, 0 fallos

composer validate --no-check-publish
Resultado: composer.json válido

php artisan view:cache
Resultado: vistas compiladas correctamente

php artisan route:list
Resultado: rutas cargadas correctamente

npm.cmd run build
Resultado: build de producción de Vite correcto

git diff --check
Resultado: sin errores de espacios o formato del diff
```

## Límite conocido de la verificación

La suite automatizada usa SQLite. Los bloqueos `lockForUpdate` fueron revisados mediante pruebas estructurales y la idempotencia fue probada en ejecución secuencial, pero no se simuló una carrera real de dos conexiones simultáneas contra MariaDB. Conviene hacer esa prueba de integración antes de considerar cerrada la validación de concurrencia en producción.

## Fuera de alcance / no implementado

Estos puntos no fueron incluidos en este ciclo y no deben asumirse como resueltos:

- Notificación al administrador cuando se toma asistencia.
- Corte especial para alumnos nuevos a partir de la tercera clase.
- Excepciones por justificaciones.
- Cola de revisión priorizada por delante de los operativos.
- Configuración del scheduler o tarea programada en el VPS.

No hubo acceso al VPS, por lo que no se verificó ni instaló la ejecución programada del scheduler.

## Historial de commits del ciclo

| Commit | Cambio |
|---|---|
| `0965b67` | Permitir que la suite pruebe la lógica en SQLite |
| `714d840` | Ordenar grupos de cobranza por sus relaciones reales |
| `7e2fcc6` | Generar la cuota mensual con actividad del mes cerrado |
| `ee4d1d2` | Unificar los estados de cobranza con gracia configurable |
| `0eea2df` | Diferir la baja de plan cuando ya hubo asistencia |
| `dbcbd35` | Impedir que profesores operen alumnos y dinero |
| `1b74fb5` | Cerrar el acceso web de usuarios desactivados |
| `8cacadb` | Renderizar el autocomplete sin interpretar HTML almacenado |
| `2c7af8f` | Limitar la cancelación al movimiento de la caja indicada |
| `c4409a6` | Validar el subrubro al editar movimientos de caja |
| `fd4d83d` | Excluir movimientos cancelados del reflejo de caja |
| `552bc18` | Eliminar imputaciones al cancelar un cobro |
| `cbdafc9` | Rechazar pagos que superan el saldo de la deuda |
| `742e042` | Serializar validaciones de caja y cobros concurrentes |
| `8e5101e` | Documentar el cierre de cobranza y concurrencia |

## Indicaciones para continuar con Claude Code

1. Leer primero `docs/00-estado/ESTADO-ACTUAL.md` y los contratos aplicables en `docs/02-contratos/`.
2. No usar `README.md` de la raíz como fuente de verdad.
3. No modificar `database/dump.sql` salvo pedido explícito.
4. Preservar el cambio local del usuario en `.claude/settings.local.json`.
5. Antes de modificar permisos, revisar `docs/02-contratos/PERMISOS-ROLES.md`.
6. Antes de tocar Blade o CSS, revisar `docs/03-diseno-ui/wings-design/SKILL.md`.
7. Volver a ejecutar la suite completa después de cada cambio de negocio.
8. Como siguiente validación técnica recomendada, probar pagos simultáneos con dos conexiones reales a MariaDB.

## Resumen de traspaso

El núcleo de cobranza, cambios de plan, permisos e integridad de pagos quedó corregido y cubierto por la suite disponible. El proyecto compila y sus vistas, rutas y dependencias se validaron correctamente. El principal punto técnico pendiente es una prueba real de concurrencia sobre MariaDB; los restantes elementos enumerados como fuera de alcance son nuevas funcionalidades o tareas de infraestructura, no regresiones abiertas de este ciclo.
