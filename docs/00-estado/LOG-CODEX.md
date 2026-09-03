# Wings — Bitácora compartida de Codex

> Memoria operativa entre las computadoras de CyE y CAB.
> No reemplaza `ESTADO-ACTUAL.md` ni el plan vigente: registra qué se hizo y qué sigue.

## Cómo usar esta bitácora

- Leerla antes de comenzar una tarea.
- Agregar una entrada al cerrar cualquier trabajo que produzca cambios.
- Firmar como **Codex CyE** en CyE o **Codex CAB** en la casa de Carlos.
- Registrar hechos verificables y enlazar archivos o commits cuando corresponda.
- No incluir contraseñas, tokens, datos personales ni información sensible.
- Mantener cada entrada corta: objetivo, cambios, decisiones, verificación y siguiente paso.

---

## 2026-09-03 — Codex CAB — indice de pendientes actualizado

Se reemplazo el indice viejo de `ESTADO-ACTUAL.md` por la estructura A-E verificada
en `PENDIENTES-260901.md`: preparar pruebas, probar, cerrar pendientes, entregar y
trabajo posterior. Se conservaron los datos mas nuevos del 03/09: 81 pruebas, 343
aserciones y saldo inicial terminado.

Tambien se separaron cuatro puntos del servidor que no se volvieron a verificar desde
el 01/09. No hubo cambios de codigo ni de base.

---

## 2026-09-03 — Codex CAB — saldo inicial por tipo de caja

Se agregó `saldo_inicial` decimal, predeterminado en cero y validado en servidor
como no negativo. Alta y edición usan el componente existente `x-ds.money-input`;
no se modificaron el componente ni el CSS.

El saldo inicial se incorporó en los cinco cálculos verificados: balance web de
cashflow, selector y control de fondos de liquidaciones, saldo acumulado por fecha
y servicio de saldos de la API apagada. No apareció un sexto cálculo de saldo. El
cashflow muestra el saldo inicial separado de ingresos y egresos porque no es un
movimiento contable.

Regresión: saldo inicial 200.000 sin movimientos; saldo 150.000 después de un
egreso de 50.000; liquidación de 100.000 pagada desde un saldo inicial de 500.000;
tipos existentes con saldo inicial cero; y rechazo del valor negativo. Resultado:
81 pruebas y 343 aserciones aprobadas.

---

## 2026-08-31 — Codex CAB — primera cuota con descuento cerrada

Se corrigió la creación automática de deudas durante el primer cobro: cuando
aplica una regla de descuento, la deuda nace con el monto descontado. El monto
especial se usa solo al crear deudas por primer pago; los pagos parciales comunes
siguen tomando el precio completo del plan.

La corrección cubre los flujos OPERATIVO y ADMIN, incluidos los dos llamados a
`obtenerOcrearDeuda()`: aplicación del pago y validación FIFO. Se agregó una
regresión específica de ADMIN con dos períodos para verificar que FIFO tampoco
cree la primera deuda al precio completo.

Verificación: `CobrarPrimeraCuota` quedó en 3 pruebas y 22 aserciones aprobadas;
la suite completa, en 76 pruebas y 313 aserciones aprobadas. El caso de segunda
quincena queda con deuda 19.600 pagada por 19.600 y estado AL_DIA; el caso sin
descuento conserva 28.000. Sintaxis PHP correcta, Blade compiló y limpió, y el
diff de `resources/views` y `resources/css` quedó vacío.

---

## 2026-08-30 — Codex CAB — condonación web ADMIN implementada y verificada

Se agregó una ruta web protegida por `ensure.admin.web` y una acción en
`AlumnoWebController` que valida el motivo, llama al motor existente y devuelve
los errores de negocio como mensajes de pantalla. `PagoCuotaService` ahora
garantiza centralmente un motivo recortado de 10 a 500 caracteres antes de leer
o modificar la deuda. La API continúa apagada.

La única vista modificada fue `resources/views/alumnos/show.blade.php`, que es
la que lista las deudas concretas. Solo el ADMIN ve `Condonar`. El modal replica
el patrón de cancelar cobro: fondo oscuro, superficie y radio por tokens,
`max-width:440px`, textarea `wings-input`, cierre al hacer clic en el fondo y
botones `Cerrar`/`Condonar`; el destructivo usa `var(--color-danger)`. No se
tocaron CSS, componentes DS, Alpine ni Livewire.

La regresión cubre cinco casos: condonación ADMIN sin movimientos y salida del
listado DEUDOR para un alumno con historial; pedido manual del OPERATIVO sin
cambios persistentes; deuda PAGADA devuelta como mensaje; servicio rechazando
motivos vacíos, cortos y mayores a 500 antes de escribir; y conservación de la
regla contractual de que un alumno sin pagos sigue DEUDOR aunque se condone su
deuda. Resultado: 5 pruebas, 26 aserciones, todas aprobadas.

Verificación manual sobre `wings_test`: se preparó un pago histórico y una deuda
pendiente sintéticos, y la condonación se ejecutó desde el modal con autorización
de Carlos. La deuda quedó CONDONADA con motivo y admin registrados, la pantalla
pasó de DEUDOR a AL_DIA y los conteos de movimientos operativos y cashflow
siguieron en cero. El modal renderizó a 440 px, radio 12 px, overlay semitransparente,
textarea correcto y botón danger.

Sintaxis PHP sin errores; Blade compiló y limpió; el diff de vistas muestra solo
`alumnos/show.blade.php`. La suite completa quedó en 74 pruebas aprobadas y una
falla conocida, perteneciente al trabajo anterior pausado: primera cuota
autocreada con descuento deja saldo pendiente. No se mezcló una corrección de
ese defecto ni se creó commit con el árbol compartido todavía sucio.

---

## 2026-08-30 — Codex CAB — condonación pausada por estado de alumno nuevo

Carlos corrigió la aceptación de permisos: se mantiene `ensure.admin.web` y el
test del operativo debe verificar que la deuda no cambió, sin atarse a 302 o
403. Ese punto ya no contradice la matriz.

Antes de escribir los tests se verificó el cuerpo de
`CobranzaEstadoService::calcularEstadoDesdeDeudas()` contra el contrato de
estados §3. La orden afirma sin condición que al condonar el alumno deja de
figurar como DEUDOR. Eso solo ocurre si tiene al menos un pago previo. El contrato
y el servicio clasifican como DEUDOR a quien nunca pagó, incluso si su única
deuda queda CONDONADA: la deuda sale de `deudas_pendientes`, pero el estado sigue
siendo DEUDOR por falta de historial.

Opciones registradas sin elegir: ejecutar la aceptación con un alumno que tenga
historial de pago; limitar el criterio a que la deuda condonada desaparezca del
listado de pendientes y admitir que un alumno nuevo siga DEUDOR; o modificar el
contrato y el motor de estados. No se implementó todavía ningún archivo propio
de la condonación.

---

## 2026-08-30 — Codex CAB — condonación sigue pausada por respuesta de permisos

Carlos autorizó convertir el motivo de 10 a 500 caracteres en invariante de
`PagoCuotaService::condonarDeuda()` y mantener además la validación web. Antes de
implementarlo se verificó el middleware requerido por el siguiente criterio.

La ruta debe ser ADMIN y el intento manual del operativo debe responder 403,
pero `ensure.admin.web` —el middleware usado por las rutas web exclusivas de
ADMIN— responde 302 y redirige al operativo a Caja. El otro middleware existente,
`ensure.admin`, sí responde 403, pero con cuerpo JSON y está destinado a la API.

Opciones registradas sin elegir: conservar `ensure.admin.web` y cambiar la
aceptación a redirect; usar excepcionalmente `ensure.admin` en esta ruta web;
crear un middleware web adicional que devuelva 403; o cambiar globalmente
`ensure.admin.web`, afectando todas las rutas administrativas. La matriz de
permisos no se modificó y todavía no se implementaron servicio, ruta, controlador,
modal ni tests.

---

## 2026-08-30 — Codex CAB — acceso web para condonar pausado antes de implementar

Se actualizó `main` (`Already up to date`) y se leyeron completos `AGENTS.md`,
el contrato de permisos, las dos reglas del design system, la vista canónica y
el modal de cancelar cobro. La vista que contiene el listado real de deudas es
`resources/views/alumnos/show.blade.php`; no se modificó ninguna vista.

La tarea se frenó por una contradicción comprobada en el cuerpo del servicio.
La orden afirma que `PagoCuotaService::condonarDeuda()` exige motivo obligatorio,
pero el método acepta y registra una cadena vacía. La exigencia actual de
`required|string|min:10|max:500` está en `CondonarDeudaRequest`, que pertenece a
la API apagada, no en el motor.

Opciones registradas sin elegir: validar el motivo solo en la nueva entrada web
y mantener el servicio permisivo, o autorizar que el servicio también rechace
motivos vacíos para convertir la regla declarada en un invariante. No se agregó
ruta, controlador, modal ni test de esta tarea.

---

## 2026-08-30 — Codex CAB — corrección de cobro y precio pausada por contradicción

Se reprodujeron primero los dos defectos con tests web: el alumno nuevo no
aparecía en Caja ni podía pagar sin deuda previa; el precio cero se guardaba y
el negativo era rechazado con el mensaje genérico. Como avance sin cerrar, el
árbol local contiene cambios no commiteados únicamente en los dos controladores
autorizados: Caja lista alumnos activos, presenta el período vigente sin
persistirlo y deja que el servicio autocree la deuda al cobrar; Grupos exige
precio mayor a cero con mensaje en castellano. Los cuatro casos directos pasan.

La tarea se frenó al verificar el criterio completo con las reglas reales de
primer pago. La orden afirma que `PagoCuotaService` está correcto, prohíbe
tocarlo y exige que el alumno nuevo quede al día. Sin embargo,
`ajustarDeudas()` se ejecuta antes que `obtenerOcrearDeuda()`. Para una deuda aún
inexistente y una regla de 70%, el test web comprobó este resultado real: plan y
deuda por $28.000, pago e imputación por $19.600, deuda `PENDIENTE` con $8.400 de
saldo. Por lo tanto, el criterio no puede cerrarse con el alcance indicado.

Opciones registradas sin elegir: autorizar una corrección en
`PagoCuotaService`; hacer que el controlador cree la deuda antes del servicio,
lo que contradice el enfoque pedido; o redefinir el resultado esperado cuando
hay descuento de primer pago. No se ejecutó prueba manual, suite completa,
commit ni cierre de tarea. El test que demuestra la contradicción queda fallando
en `CobrarPrimeraCuotaWebTest`.

---

## 2026-08-30 — Codex CAB — prueba humana finalizada por bloqueo en B2

Se retomó `PRUEBA-HUMANA-V1.md` en A3 y se operó exclusivamente desde el
navegador sobre `wings_test`, sin llamar servicios, escribir directamente en la
base ni corregir código. A3–A8 y A10–A13 pasaron. A9 falló: el editor de planes
aceptó y guardó un precio cero; al escribir un valor negativo eliminó el signo y
guardó el importe como positivo. Los dos registros adicionales quedaron en la
base descartable.

B1 pasó: las siete rutas administrativas probadas con el rol operativo
redirigieron a Caja. La cadena se frenó en B2: Caja no mostró deudas pendientes,
la búsqueda del alumno no devolvió cuotas y la pantalla individual indicó total
pendiente cero con el botón de cobro deshabilitado. No se registró pago,
movimiento ni caja. B3–D2 quedaron `NO SE PUDO` para no fabricar el estado
faltante ni saltear el eslabón roto.

Incidencia del ejecutor: al reutilizar un formulario validado se creó un profesor
adicional con datos de prueba. No se eliminó; está separado del resultado de A5
en el informe para no atribuirlo a Wings y para que un recuento posterior no lo
confunda con duplicación del sistema.

El detalle paso por paso, incluidos mensajes y escrituras observadas, quedó en
`docs/06-pruebas/RESULTADO-PRUEBA-HUMANA-V1.md`. Verificación final: 65 pruebas y
253 aserciones aprobadas; las vistas compilan; `resources/views/**` y
`resources/css/**` no tienen cambios. No se tocó lógica funcional.

---

## 2026-08-30 — Codex CAB — prueba humana pausada en A2

Se inició `PRUEBA-HUMANA-V1.md` exclusivamente desde el navegador sobre
`wings_test`. A1 pasó: se creó `Hockey` por hora y el listado quedó con 3 deportes.
A2 pasó en sus tres variantes: `Patín`, `patín` y `PATIN` devolvieron el mensaje de
validación `Ya existe un deporte con ese nombre.`; ninguna dejó un cuarto registro.

La ejecución se pausó por cambio de cuenta de Codex, no por una falla de Wings. No
se ejecutó A3 ni ningún paso posterior. El resultado detallado y el punto exacto de
continuación quedaron en `docs/06-pruebas/RESULTADO-PRUEBA-HUMANA-V1.md`: retomar
en **A3 · Profesores** sobre la misma base, sin repetir A1/A2.

---

## 2026-08-30 — Codex CAB — despliegue repetible con rollback

**Cambio real:** se agregó `scripts/deploy.sh` con los valores actuales del
servidor (`/home/wings/app`, usuario `wings`, `/usr/bin/php82`). Ejecuta sin
tuberías y comprueba por separado mantenimiento, `git pull --ff-only`, Composer
invocado mediante PHP 8.2, instalación y build de npm, migraciones, las tres
cachés, permisos, salida de mantenimiento y `wings:preflight`. El paso de
migración usa `wings_migrate` mediante variables solo para ese proceso; la clave
se pide sin eco o se recibe por `WINGS_MIGRATE_PASSWORD`, y no se escribe en el
repo ni en `.env`.

**Rollback:** antes de actualizar guarda `HEAD` y una copia de `public/build`. Si
cualquier paso falla, restaura el commit con `git reset --hard`, reinstala las
dependencias y recompila el diseño de ese commit, rehace configuración, rutas y
vistas, restaura permisos y ejecuta `artisan up`. Si Laravel no puede retirar el
modo mantenimiento, elimina únicamente su archivo `storage/framework/down`. El
proceso conserva el código de error original y nunca informa éxito después de un
fallo.

**Límite explícito:** no ejecuta `migrate:rollback` automático. La orden define
la vuelta al commit y una reversión genérica de DDL en MariaDB puede destruir
datos; cualquier migración que no sea compatible hacia atrás necesita su propio
procedimiento de reversión antes de desplegarse.

**Regresión local:** `tests/Deployment/deploy_rollback_test.sh` arma dos commits y
un remoto descartables, hace fallar la migración con código 42 y verifica el
resultado real: vuelve al SHA anterior y a su contenido, recompone las cachés,
sale de mantenimiento, deja el checkout limpio y termina con código 42. La prueba
pasó. No se ejecutó el script contra el servidor.

**Verificación:** sintaxis Bash correcta en ambos archivos; 65 pruebas y 253
aserciones PHP aprobadas; los comandos reales `config:cache`, `route:cache` y
`view:cache` funcionan en el proyecto. Vistas y CSS no fueron modificados.

---

## 2026-08-30 — Codex CAB — CSP medida en modo reporte

**Política aplicada:** el middleware agrega únicamente
`Content-Security-Policy-Report-Only`. `script-src` admite solo `'self'`;
`style-src` conserva `'self' 'unsafe-inline' https:` para no afectar los 1.054
estilos inline conocidos. No existe un header `Content-Security-Policy`
bloqueante y no se modificó ninguna vista ni CSS.

**Recorrido real:** se visitaron 55 pantallas (login y 54 rutas autenticadas) con
un ADMIN local temporal. Para contar sin depender de que la consola automatizada
expusiera su canal interno de seguridad, durante el recorrido se apuntó
temporalmente `report-uri` a un colector local y luego se retiró. El navegador
emitió **85 reportes reales**, todos `script-src-elem` con `blocked-uri: inline`:
55 corresponden al bloque de `layouts/ds-app.blade.php` presente en cada
respuesta y 30 a bloques de las pantallas o parciales. No hubo reportes de
estilos ni de archivos JavaScript externos. El recorrido terminó sin errores de
navegación. Los atributos `on*` no generan reporte hasta ejecutar su evento; se
inventariaron además 45 instancias renderizadas y 38 declaraciones fuente, sin
accionarlas para no provocar operaciones funcionales.

**Inventario por archivo:** `S` es la cantidad estable de bloques `<script>` en
el fuente, `H` la de atributos manejadores inline y `R` los reportes reales
observados en este recorrido. `E` = extracción directa a un archivo; `D` =
extracción con puente de datos Blade (`data-*` o JSON); `L` = reemplazar el
atributo por un listener desde archivo externo.

| Archivo | S | H | R | Resolución |
|---|---:|---:|---:|---|
| `admin/dashboard.blade.php` | 0 | 6 | 0 | L |
| `alumnos/_form.blade.php` | 1 | 0 | 2 | E+D |
| `alumnos/index.blade.php` | 1 | 0 | 1 | E+D |
| `alumnos/show.blade.php` | 0 | 1 | 0 | L |
| `auth/login.blade.php` | 1 | 0 | 1 | E |
| `caja/cobrar.blade.php` | 1 | 0 | 1 | E+D |
| `caja/detalle.blade.php` | 2 | 5 | 0 | E+L |
| `caja/editar.blade.php` | 1 | 0 | 1 | E+D |
| `caja/movimiento.blade.php` | 1 | 0 | 2 | E+D |
| `caja/resumen.blade.php` | 1 | 3 | 0 | E+L |
| `cashflow/index.blade.php` | 0 | 4 | 0 | L |
| `cashflow/movimiento.blade.php` | 1 | 0 | 1 | E+D |
| `clases/create.blade.php` | 1 | 0 | 1 | E |
| `clases/edit.blade.php` | 1 | 0 | 1 | E |
| `clases/index.blade.php` | 1 | 0 | 1 | E |
| `clases/show.blade.php` | 1 | 0 | 1 | E+D |
| `configuraciones/index.blade.php` | 1 | 0 | 1 | E |
| `grupos/_form.blade.php` | 1 | 0 | 2 | E+D |
| `grupos/index.blade.php` | 1 | 0 | 1 | E |
| `grupos/show.blade.php` | 0 | 5 | 0 | L |
| `layouts/ds-app.blade.php` | 1 | 0 | 55 | E |
| `liquidaciones/create.blade.php` | 1 | 2 | 1 | E+D+L |
| `liquidaciones/index.blade.php` | 0 | 1 | 0 | L |
| `liquidaciones/show.blade.php` | 0 | 3 | 0 | L |
| `niveles/_form.blade.php` | 1 | 0 | 2 | E |
| `niveles/index.blade.php` | 0 | 1 | 0 | L |
| `profesores/_form.blade.php` | 2 | 0 | 4 | E+D |
| `profesores/index.blade.php` | 1 | 0 | 1 | E |
| `revision-cobranza/index.blade.php` | 1 | 5 | 1 | E+L |
| `rubros/index.blade.php` | 0 | 2 | 0 | L |
| `tipos-caja/_form.blade.php` | 1 | 0 | 2 | E |
| `usuarios/_form.blade.php` | 1 | 0 | 2 | E |

**Resultado para el bloque siguiente:** hay 26 bloques `<script>` en 24 archivos:
16 se extraen directamente y 10 necesitan separar los datos generados por Blade.
Los 38 manejadores inline de 12 archivos requieren listeners externos. Los dos
scripts de `caja/detalle` y el de `caja/resumen` no se renderizaron con los datos
actuales, pero permanecen en el fuente y deben incluirse en la migración. No se
activó bloqueo; esa decisión sigue pendiente de revisión visual con Carlos.

**Verificación final:** 65 pruebas y 253 aserciones aprobadas; sintaxis PHP
correcta en middleware y test; vistas compiladas; diff de vistas/CSS vacío. Una
respuesta HTTP real contiene el header de reporte con la política indicada y no
contiene CSP bloqueante. La cuenta y el colector locales de auditoría fueron
eliminados al terminar.

---

## 2026-08-30 — Codex CAB — dependencias sin avisos de seguridad

**Actualización:** `composer update` renovó 73 paquetes dentro de los rangos ya
declarados. `barryvdh/laravel-dompdf` pasó de 3.1.1 a 3.1.2 y su motor
`dompdf/dompdf` de 3.1.4 a 3.1.6. `laravel/tinker` permaneció en la rama 2
(2.11.0 a 2.11.1). `composer.json` no se modificó; solo cambió el lock.

**Recibo real:** se regeneró el recibo de cuota 145 con datos existentes y se
abrieron sus dos páginas A5 mediante render PNG. Cabecera, datos, período, total,
medio de cobro, observaciones y firma quedaron legibles, sin cortes,
superposiciones, glifos rotos ni imágenes faltantes. Se comparó contra el recibo 1,
cacheado en julio con Dompdf 3.1.4: ya tenía las mismas dos páginas y distribución,
por lo que no hubo regresión de paginado.

**Verificación:** 65 pruebas y 252 aserciones aprobadas; `composer validate
--strict` correcto; `composer audit --locked` informa **0 advertencias**. No se
tocaron archivos PHP, por lo que no hubo archivos aplicables a `php -l`. Vistas
compiladas y diff de vistas/CSS vacío. Los PNG temporales de inspección se
eliminaron; el PDF regenerado permanece en el storage local no versionado.

---

## 2026-08-30 — Codex CAB — cuenta ADMIN protegida

**Decisión contractual:** se mantuvieron los tres roles. `es_superadmin` es una
marca de integridad sobre una cuenta ADMIN, no un rol ni un permiso adicional; no
cambia rutas, middlewares ni la matriz ADMIN/OPERATIVO/PROFESOR. La excepción quedó
documentada en `PERMISOS-ROLES.md`.

**Cambios reales:** migración booleana con valor predeterminado `false`; el listado
solo muestra cuentas protegidas a sí mismas; `edit`, `update` y `toggleActivo`
responden 403 para cualquier otro usuario antes de validar o escribir. El alta y la
edición web ignoran intentos de establecer la marca. `wings:crear-admin
--superadmin` es la única vía de alta protegida. El preflight conserva su consulta
por rol ADMIN activo y se probó expresamente con una cuenta protegida.

**Regresión:** antes del bloqueo fallaban seis escenarios: visibilidad, acceso a la
edición, cambio de datos, contraseña, rol y estado activo. Después del arreglo pasan,
incluido que la cuenta protegida se vea a sí misma y administre a otro ADMIN.

**Verificación:** 65 pruebas y 252 aserciones aprobadas; siete archivos PHP sin
errores de sintaxis; vistas compiladas; diff de vistas y CSS vacío. La migración se
aplicó en CAB y la columna quedó `tinyint(1)`, predeterminado `0`. No se creó ni se
marcó ninguna cuenta real: Carlos debe ejecutar el comando con su email y una
contraseña que no se registre en el repo.

---

## 2026-08-30 — Codex CAB — D1 nombres de grupo en documentos

**Corregido en el origen.** `Grupo::nombre_completo` ahora carga `deporte` y
`nivel` cuando faltan y nunca devuelve un nombre vacío. Las consultas masivas de
liquidaciones y cobranza cargan ambas relaciones para evitar una consulta por fila;
`PagoService` carga la cadena del plan activo. El uso señalado en
`AlumnoWebController::autocomplete()` ya tenía `grupo.deporte` y `grupo.nivel`, por
lo que D1 no necesitó modificarlo.

**Regresión comprobada antes del arreglo:** el accesor devolvía `" — "` y una
liquidación real generaba `"Clase 02/03/2026 -  —  (Validada manual)"`. Después del
arreglo, el detalle contiene `"Patín — Inicial"`; el test también cubre el acceso a
un grupo recuperado sin relaciones precargadas.

**Verificación:** 53 pruebas y 219 aserciones aprobadas; sintaxis PHP correcta en
los cinco archivos PHP de D1; vistas compiladas. D1 no modificó vistas ni CSS. Al
cerrar apareció trabajo local concurrente, ajeno a este commit, en el formulario y
controlador de alumnos para `fecha_alta`; se preservó sin incluirlo. La incorporación
de `fecha_alta` queda fuera de D1 y requiere su autorización separada.

---

## 2026-08-30 — Codex CAB — cierre C3

**1.9 cerrada por reemplazo contractual, no por ampliar el FIFO.** Se contrastaron
los dos contratos vigentes: `Wings-contrato-cuotas-deudas-pagos-V1.md` §3.e define
FIFO cuando un mismo pago cubre múltiples períodos, y
`Wings-contrato-estadosAlum-cobranza-asistencia-V1.md` §9b define los períodos que
quedan fuera. Son coherentes: `PagoCuotaService::validarFifo()` sigue invocado en los
flujos OPERATIVO y ADMIN y ordena lo incluido en un cobro; A2 avisa antes de guardar,
exige motivo y notifica al administrador si quedan meses anteriores omitidos. B6 deja
de considerarse defecto por decisión del dueño.

**1.12 confirmada.** La consulta a `information_schema.TABLES` sobre la base local
`gestion_wings` devolvió cero filas para `formas_pago`. No quedan referencias de
ejecución en `app/`, `config/`, `routes/` ni `tests/`; las únicas menciones técnicas
están en migraciones históricas que crean y luego eliminan la tabla y su FK.

**Verificación:** 51 pruebas y 211 aserciones aprobadas; vistas compilan; diff de
vistas y CSS vacío. No hubo cambios funcionales en C3.

---

## 2026-08-30 — Codex CAB — lote D1B retomado

**Completado y commiteado:** A1 hizo atómicos el cambio de plan, la reescritura de
deuda y el pago (`fb473ce`); A2 agregó aviso 409, motivo obligatorio, registro en el
pago y notificación por correo a administradores activos cuando se dejan meses
anteriores pendientes (`51b7570`); B1 creó `wings:crear-admin` con contraseña oculta,
control de fuerza y duplicados (`5914cab`); B2 agregó la plantilla de producción y
`wings:preflight` sincronizados (`3a050fa`); B3 agregó los cinco headers sin CSP y sin
reactivar la API (`c6ce1d6`); C1 hizo atómico el guardado de asistencias y rechaza
alumnos ajenos al grupo (`dab369f`); C2 limitó el login a cinco intentos por minuto
(`04a7903`).

**Verificación real:** 51 pruebas y 211 aserciones aprobadas al cerrar C2; sintaxis
PHP correcta; vistas compilan; cada tarea cerró con diff de vistas/CSS vacío salvo
A2, cuyo único archivo visual fue `resources/views/caja/cobrar.blade.php`, autorizado
expresamente. No se agregó CSP, no se tocó `app.css`, el plan ni la evaluación.

**Contradicción verificada — C3 detenido según `AGENTS.md` §6b.** La corrección de
la propia orden y el contrato §9b establecen que cobrar un período dejando otro
anterior impago no se bloquea: se avisa, se exige motivo y se notifica. A2 implementa
y prueba esa regla. Sin embargo C3 todavía exige confirmar que `validarFifo()`
"rechaza el caso de deuda vieja impaga con cobro del período nuevo". Cumplir esa
frase desharía A2 y violaría el contrato.

**Opciones, sin elegir ninguna:** (1) corregir C3 para verificar el FIFO solo entre
los períodos incluidos en un mismo cobro y verificar A2 para los períodos omitidos;
(2) declarar 1.9 reemplazada por §9b y cerrar únicamente contra la regla de aviso;
(3) restaurar el bloqueo fuerte, lo que requiere cambiar el contrato y retirar A2.

**Quedó afuera:** C3 no se cerró y no se afirmó el estado actual de `formas_pago` en
esta retomada. Los archivos locales previos de Carlos permanecieron intactos.

---

## 2026-08-30 — Codex CAB

**Objetivo:** iniciar el lote D1B por A1 (atomicidad del cambio de plan y el pago),
con la regresión obligatoria de deuda vieja impaga y cobro de solo el período nuevo.

**Contradicción verificada — lote detenido según `AGENTS.md` §6b.** La orden afirma
que el FIFO ya implementado debe rechazar ese pedido. En el código actual,
`PagoCuotaService::validarFifo()` retorna sin validar cuando recibe un solo ítem y
solo examina los períodos incluidos en el pedido; no consulta deudas anteriores que
quedaron fuera. Se ejecutó la regresión indicada con deuda de julio impaga y pedido
solo por agosto: el pago fue aceptado y redirigió a caja, en vez de producir el error
FIFO. La prueba temporal se retiró para no dejar la suite rota.

**Opciones, sin elegir ninguna:** (1) ampliar primero el FIFO para consultar y
rechazar deudas anteriores no incluidas, lo que implica que 1.9 no estaba cerrada;
(2) cambiar la aceptación de A1 para forzar el rollback mediante otro error real del
pago; (3) probar el rollback enviando deuda vieja y período nuevo en el mismo pedido,
que sí activa el FIFO actual pero no cumple el escenario literal de la orden.

**Cambios funcionales:** ninguno. **Pendiente:** definición del dueño antes de
implementar A1 o continuar las otras seis tareas.

---

## 2026-08-27 17:27 — Codex CyE

**Tareas:** corrección conjunta 1.3 + 1.4 — fuente única de catálogos y seeders seguros.

**Cambios:** `DatabaseSeeder` llama únicamente a `CatalogosSeeder`; eliminados los cinco seeders viejos ya sin invocaciones; `test@example.com` pasó a un `TestSeeder` explícito; `DemoSeeder` y `TestSeeder` abortan en producción; agregada una migración nueva que elimina únicamente niveles legacy sin grupos y registra por `Log::warning` los que conserva por tener referencias. La migración de abril no se modificó.

**Aceptación literal:** sobre una base SQLite descartable vacía se ejecutó `php artisan migrate:fresh --seed`, sin indicar un seeder manualmente. La segunda ejecución de `php artisan db:seed` mantuvo los mismos conteos:

- Rubros: **8**.
- Subrubros: **15**.
- Tipos de caja: **5**.
- Deportes: **2**.
- Niveles: **3** (`Principiantes`, `Intermedias`, `Avanzadas`).
- Usuarios: **0**.
- Movimientos de cashflow: **0**.
- `Cuota Mensual`: **1**.
- `Sueldos`: **1**.

**Verificación:** 38 pruebas y 130 aserciones aprobadas; archivos PHP sin errores de sintaxis; vistas compilan; diff de vistas y CSS vacío.

**Siguiente paso:** commit y push de la corrección conjunta; después continúa la tarea 1.2.

---

## 2026-08-26 17:24 — Codex CyE

**Tarea:** 1.3 — primera implementación de `CatalogosSeeder`; quedó incompleta y fue corregida junto con 1.4 en la entrada del 27/08.

**Cambios:** creado `database/seeders/CatalogosSeeder.php` con deportes, niveles, rubros, subrubros, tipos de caja y reglas de primer pago. Incluye los nombres literales obligatorios `Cuota Mensual` y `Sueldos`; no incluye subrubros personales de profesores, que se crean al registrar cada profesor. Agregadas dos pruebas de aceptación.

**Verificación:** base SQLite temporal vacía migrada y sembrada correctamente; segunda ejecución correcta; comparación exacta de filas sin modificaciones; `Cuota Mensual=1`, `Sueldos=1`, `Usuarios=0`; 35 pruebas y 120 aserciones aprobadas; vistas compilan; diff de vistas y CSS vacío.

**Commit:** `feat(seed): crear catalogos base sin datos personales`.

**Siguiente paso:** tarea 1.2, sacar `database/dump.sql` del repositorio después de sincronizar.

---

## 2026-08-26 11:41 — Codex CyE

**Objetivo:** eliminar Redis del PHP local porque Wings no lo utiliza.

**Cambio:** deshabilitada la línea `extension=php_redis.dll` en `C:\xampp\php\php.ini`. No se alteró la configuración opcional estándar de Laravel porque está inactiva y no carga la extensión.

**Verificación:** `php -v` y `php --ini` sin advertencias; `php artisan view:cache` y `view:clear` correctos; 33 pruebas y 107 aserciones aprobadas.

**Siguiente paso:** ninguno para Redis. Apache tomará el cambio en su próximo reinicio.

---

## 2026-08-26 11:19 — Codex CyE

**Objetivo:** crear una memoria compartida para continuar el proyecto entre computadoras.

**Punto de partida verificado:**

- Rama: `main`.
- Commit: `06eb669`.
- Plan vigente: `docs/00-estado/PLAN-PRODUCCION.md`.
- Orden vigente de Codex: `docs/00-estado/ORDEN-CODEX-D1.md`.
- El lote D1 figura pendiente salvo las tareas ya marcadas como hechas en el plan.

**Cambios:**

- Creada esta bitácora.
- Agregadas en `AGENTS.md` las identidades **Codex CyE** y **Codex CAB** y la obligación de mantener el log.
- Agregado el log al índice de `docs/README.md` y al mapa documental de `AGENTS.md`.

**Decisión:** los chats siguen siendo locales; la continuidad verificable queda dentro del repositorio.

**Verificación:** cambio exclusivamente documental; no se modificó lógica, vistas ni CSS. `php artisan test`: 33 pruebas y 107 aserciones aprobadas. `php artisan view:cache` quedó bloqueado sin salida y fue interrumpido; además PHP advierte que no puede cargar `php_redis.dll`. Ambos problemas quedan informados, no investigados en esta tarea documental.

**Siguiente paso:** leer el plan y esta bitácora antes de ejecutar la próxima tarea del proyecto.
