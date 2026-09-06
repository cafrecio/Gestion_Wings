# Resultado de primera carga V1

Fecha: 06/09/2026. Responsable: **Codex CAB**.
Estado: **ETAPA 1 COMPLETADA: 60 alumnos cargados y verificados**.
Referencia: `PRIMERA-CARGA-V1.md`, leido completo. Checkout `7aa9479`, rama main.
Durante la prueba se incorporaron cambios paralelos: al detenerse, HEAD era
`798bfa3`, con `19c2b7c` resolviendo niveles/superadmin. No se revirtieron ni
se modificaron esos cambios.

## Comprobaciones previas (historial de la primera pausa, ya resuelta)

| Comprobacion | Resultado esperado | Resultado obtenido | Estado |
|---|---|---|---|
| Actualizar repositorio | Trabajar con los commits nuevos | Pull sin novedades; ya presentes `fbe7a55`, `f02cb9e` y `7aa9479` | PASA |
| Identificar ambiente | Base local descartable, no servidor | Conexion efectiva local a `wings_test` en 127.0.0.1 | PASA |
| Identificar admin inicial | Cuenta ADMIN activa indicada por Carlos | Existe; no se modificaron credenciales ni permisos | PASA |
| Identificar superadmin inicial | Disponer de la cuenta protegida que queda al reiniciar | Consulta de filas protegidas devuelve vacio; falta definir la identidad de la cuenta a crear o marcar | NO SE PUDO |
| Contrastar niveles y grupos especificados | Poder crear exactamente los cuatro niveles y los seis grupos con sus nombres indicados | Se pide nivel Avanzadas, pero grupo Futbol/Avanzados. Grupo compone deporte y nivel, sin nombre independiente | NO SE PUDO |
| Identificar rubros minimos | Cuotas, su subrubro reservado y Sueldos | Existen Cuotas, Cuota Mensual reservado y Sueldos. Todavia no se depuraron sus datos ni dependencias | PASA |

En aquella pausa inicial los dos impedimentos eran de preparacion/especificacion,
**no fallos observados en una pantalla de Wings**. Todavia no se habian enviado
altas ni rechazos desde el navegador, ni borrado o cargado datos.

## Decisiones resueltas por Carlos (06/09)

1. Conservar cuatro niveles: Futbol tambien usa **Avanzadas**.
2. **Omitir superadmin** en esta etapa local; se definira para el servidor.

Las comprobaciones de arriba registran la pausa inicial, ya resuelta. No se
realizo ningun borrado durante aquella pausa.

## Intentos ejecutados desde la reanudacion

La preparacion elimino los datos anteriores de wings_test en una transaccion,
con verificaciones de host/base y de todas las tablas antes de confirmar. Se
conservaron admin (id 5), Cuotas y Sueldos (ids 1 y 3), Cuota Mensual (id 1) y
el historial tecnico de migraciones. Sueldos quedo sin hijos. Las demas tablas
quedaron vacias. Sin respaldo nuevo: datos descartables, borrado autorizado.
No se reiniciaron secuencias de ids ni se modifico el esquema.

| ID | Que se intento | Resultado esperado | Resultado obtenido y verificacion | Estado |
|---|---|---|---|---|
| C01 | Alta Patin por pantalla | Activo, liquidacion por hora | Aviso de exito, listado y fila id 4: Patin, HORA, activo | PASA |
| C02 | Alta Futbol por pantalla | Activo, liquidacion por comision | Aviso de exito, listado y fila id 5: Futbol, COMISION, activo | PASA |
| C03 | Alta Principiantes | Nivel con ese nombre | Aviso de exito, listado y fila id 7 | PASA |
| C04 | Alta Intermedias | Nivel con ese nombre | Aviso de exito, listado y fila id 8 | PASA |
| C05 | Alta Avanzadas | Nivel con ese nombre | Aviso de exito, listado y fila id 9 | PASA |
| C06 | Alta Federadas | Nivel con ese nombre | Aviso de exito, listado y fila id 10 | PASA |
| C07 | Alta Patin/Principiantes con frecuencias 1 y 2 | Precios 30000 / 40000 | Exito en listado; grupo 4 y planes 7/8, activos, importes correctos en base | PASA |
| C08 | Alta Patin/Intermedias con frecuencias 1 y 2 | Precios 33000 / 43000 | Exito en listado; grupo 5 y planes 9/10, activos, importes correctos en base | PASA |
| C09 | Alta Patin/Avanzadas con frecuencias 1 y 2 | Precios 35000 / 45000 | Exito en listado; grupo 6 y planes 11/12, activos, importes correctos en base | PASA |
| C10 | Alta Patin/Federadas con frecuencias 1 y 2 | Precios 40000 / 50000 | Exito en listado; grupo 7 y planes 13/14, activos, importes correctos en base | PASA |
| C11 | Alta Futbol/Principiantes con frecuencias 1 y 2 | Precios 28000 / 35000 | Exito en listado; grupo 8 y planes 15/16, activos, importes correctos en base | PASA |
| C12 | Alta Futbol/Avanzadas con frecuencias 1 y 2 | Precios 38000 / 48000 | Exito en listado; grupo 9 y planes 17/18, activos, importes correctos en base | PASA |
| C13 | Alta Efectivo, abreviatura EFE, saldo inicial 0, sin descubierto | Tipo de caja activo creado, sin movimientos | **Pantalla de error 500**, SQLSTATE 42S22: falta saldo_inicial. Tipos_caja sigue vacia: ninguna fila parcial | FALLA |
| C14 | Reintento de alta Efectivo/EFE, saldo inicial 250000 indicado por Carlos, sin descubierto | Crear tipo nuevo con ese saldo, sin editar ninguno | **Pantalla 500**; log de la solicitud informa MissingAppKeyException. Tipos_caja sigue vacia: no hubo fila parcial | FALLA |
| C15 | Nuevo intento de Efectivo/EFE, saldo inicial 250000, sin descubierto, con una sesion web nueva | Crear tipo nuevo con ese saldo, sin editar ninguno | Redireccion al listado; fila id 6 activa, sin descubierto, saldo_inicial 250000.00 y sin movimientos | PASA |
| C16 | Alta Mercado Pago/MP, saldo inicial 1320000, sin descubierto | Crear tipo nuevo con ese saldo, sin editar ninguno | Redireccion al listado; fila id 7 activa, sin descubierto, saldo_inicial 1320000.00 y sin movimientos | PASA |
| C17 | Alta de profesora de Patin, liquidacion por hora 12000 | Profesora activa por hora con ese valor | Aviso de exito; fila id 4 activa, valor_hora 12000.00, sin comision | PASA |
| C18 | Alta de profesora de Patin, liquidacion por hora 15000 | Profesora activa por hora con ese valor | Aviso de exito; fila id 5 activa, valor_hora 15000.00, sin comision | PASA |
| C19 | Alta de profesora de Patin, liquidacion por hora 18000 | Profesora activa por hora con ese valor | Aviso de exito; fila id 6 activa, valor_hora 18000.00, sin comision | PASA |
| C20 | Alta de profesor de Futbol, liquidacion por comision 40% | Profesor activo por comision con ese porcentaje | Aviso de exito; fila id 7 activa, comision 40.00%, sin valor hora | PASA |
| C21 | Alta OPERATIVO ficticio realista | Usuario activo, rol OPERATIVO y sin profesor vinculado | Fila id 6 activa, rol OPERATIVO y profesor_id null | PASA |
| C22 | Alta de segundo OPERATIVO ficticio realista | Usuario activo, rol OPERATIVO y sin profesor vinculado | Fila id 7 activa, rol OPERATIVO y profesor_id null | PASA |
| C23 | Alta de cuenta PROFESOR vinculada a ficha id 4 | Usuario activo, rol PROFESOR y profesor_id 4 | Fila id 8 activa, rol PROFESOR y profesor_id 4; nombre de ficha coincide | PASA |
| C24 | Alta de cuenta PROFESOR vinculada a ficha id 5 | Usuario activo, rol PROFESOR y profesor_id 5 | Fila id 9 activa, rol PROFESOR y profesor_id 5; nombre de ficha coincide | PASA |
| C25 | Alta de cuenta PROFESOR vinculada a ficha id 6 | Usuario activo, rol PROFESOR y profesor_id 6 | Fila id 10 activa, rol PROFESOR y profesor_id 6; nombre de ficha coincide | PASA |
| C26 | Alta de cuenta PROFESOR vinculada a ficha id 7 | Usuario activo, rol PROFESOR y profesor_id 7 | Fila id 11 activa, rol PROFESOR y profesor_id 7; nombre de ficha coincide | PASA |
| C27 | Alta OPERATIVO con correo ya existente | Rechazo de validacion y sin fila parcial | Mensaje “Ya existe un usuario con ese email”; correo existente sigue con una sola fila y nombre de intento no existe | PASA |
| C28 | Alta valida de alumna como ADMIN, Patin/Principiantes, frecuencia semanal 1, fecha de alta anterior | Crear alumna con grupo, plan y fecha correctos y volver al listado | La fila id 6 y su plan activo id 7 quedaron escritos, con fecha de alta 2026-03-01. La redireccion al listado dio pantalla 500 por falta de dias_gracia_cobranza. La fecha se reverifico luego: pertenece al tramo enero–marzo de 2026, por lo que no requiere correccion | FALLA |
| C29 | Restaurar fuera de la aplicacion las dos configuraciones iniciales de la migracion | Recuperar ambas claves con sus valores, tipos y descripciones exactos; listado de alumnos operativo | `dias_gracia_cobranza=10` y `dia_generacion_deuda=1` restauradas en wings_test con guardas local/127.0.0.1; el listado vuelve a abrir | PASA |
| C30 | Alta por pantalla de regla 1 a 15, 100% | Regla activa creada por pantalla | Fila id 4 creada: dias 1–15, porcentaje 100.00, activa. La interfaz no se actualizo de inmediato, pero no hubo error ni fila parcial | PASA |
| C31 | Alta por pantalla de regla 16 a 23, 70% | Regla activa creada por pantalla | Fila id 5 creada: dias 16–23, porcentaje 70.00, activa | PASA |
| C32 | Alta por pantalla de regla 24 a 31, 40% | Regla activa creada por pantalla | Fila id 6 creada: dias 24–31, porcentaje 40.00, activa | PASA |
| C33 | Alta de tramo superpuesto 10 a 20, 50% | Rechazo 422 y ninguna fila parcial | La pantalla informa que se pisa con la regla 1–15; base con exactamente tres reglas y sin fila de prueba | PASA |
| C34 | Alta con DNI ya existente en el mismo deporte | Rechazo de validacion y sin escritura parcial | La pantalla informo “Ya existe un alumno con ese DNI en el mismo deporte”; la base conserva una sola fila con ese DNI en Patin | PASA |
| C35 | Alta con DNI ya existente en otro deporte | Alta aceptada, una inscripcion por deporte | La pantalla confirmo el alta; base con dos filas del DNI: Patin/id 4 y Futbol/id 5, ambas con grupos de su deporte | PASA |
| C36 | Alta con celular vacio | Rechazo de validacion y sin escritura parcial | Mensaje “El celular es obligatorio.”; consulta concreta por DNI no devolvio filas | PASA |
| C37 | Alta de menor sin datos de tutor | Rechazo pidiendo tutor y sin escritura parcial | Mensajes obligatorios para nombre y telefono del tutor; consulta concreta por DNI no devolvio filas | PASA |
| C38 | Alta con fecha de nacimiento futura | Rechazo de validacion y sin escritura parcial | Mensaje “El año ingresado no es válido.”; consulta concreta por DNI no devolvio filas | PASA |
| C39 | Alta con email invalido | Rechazo de validacion y sin escritura parcial | Mensaje “El email no tiene un formato válido.”; consulta concreta por DNI no devolvio filas | PASA |
| C40 | Alta Patin con grupo Futbol, antes de H06 | Rechazo de validacion y sin escritura parcial | **La pantalla confirmo el alta.** Base: alumno id 17 con deporte Patin/id 4 y grupo id 8, cuyo deporte es Futbol/id 5 | FALLA HISTORICA |
| C41 | Repeticion de C40 despues de `d628abd` | Rechazo con mensaje y sin escritura parcial | Mensaje “El grupo seleccionado no pertenece al deporte elegido.”; consulta concreta por DNI no devolvio filas | PASA |
| C42 | Ejecutar `PrimeraCargaAlumnosSeeder` dos veces sobre wings_test | Completar 49 altas sin duplicar y llegar a 60 | Dos ejecuciones sin error. Base final: Patin 40, Futbol 20; tramos 2025=12, enero-marzo=18 y abril-junio=30; sin altas desde julio, sin DNI repetidos en un deporte, sin alumnos sin plan activo, sin deudas ni pagos | PASA |

## H01 — Esquema local desactualizado al crear el tipo de caja

El formulario envio un alta valida, pero `TipoCajaWebController::store()` intenta
insertar `saldo_inicial` y esa columna no existe en `wings_test`. Verificado con
el listado real de columnas y con `php artisan migrate:status`: esta pendiente
`2026_09_03_000001_add_saldo_inicial_to_tipos_caja_table`.
Su `up()` agrega la columna decimal con valor predeterminado 0. No se ejecuto.

Es un error de preparacion del ambiente, no un rechazo de negocio ni evidencia
de que falte la migracion en el repositorio. La limpieza autorizada conservo el
esquema existente; **Codex no reviso las migraciones pendientes antes de iniciar
la carga**. La comprobacion se hizo recien despues de este error y se deja asentado.

Escritura parcial: consulta concreta de `tipos_caja` vacia y cantidad 0 despues
del error, igual que antes del intento. El controlador realiza un unico insert
para esa alta; este fallo antes de crear la fila. No hay profesores ni alumnos
todavia. Se conservan los dos deportes, cuatro niveles, seis grupos y doce planes.

**H01 resuelto en el ambiente:** con la indicacion posterior de Carlos se aplico
unicamente la migracion existente a wings_test, verificando entorno local y host
127.0.0.1 antes de ejecutarla. Se confirmo la existencia de la columna. Se
conservaron catalogos, grupos y planes. No se escribio ni modifico codigo.

## H02 — Nuevo error durante el reintento

C14 se envio con saldo 250000, luego de aplicar la migracion. La respuesta fue
500 y el log a las 11:16:37 registra `MissingAppKeyException`: falta la clave de
cifrado de la aplicacion. Se consulto la base: `tipos_caja` sigue vacia.

En una comprobacion posterior de solo lectura el formulario volvio a abrir con
sesion admin. El proceso de consola identifica entorno local y wings_test,
mientras que la entrada de error del servidor esta marcada production. No se
comprobo la causa de esa diferencia. No hay evidencia suficiente para atribuirla
a otro agente o a un cambio paralelo. No se genero ni reemplazo APP_KEY, no se
modifico .env, no se borraron caches ni se repitio otra vez el alta.

H02 no se reprodujo al abrir una sesion web nueva: el proceso de consola informa
local, clave presente, sin cache de configuracion, host 127.0.0.1 y base
wings_test. No se modifico APP_KEY, .env ni caches. En C15 y C16 ambas altas
se completaron desde la pantalla y las filas se verificaron contra la base. La
causa de la respuesta anterior sigue sin determinar; se conserva como hecho
historico, sin atribuirla a una causa no comprobada.

## H03 — Especificacion incompleta para continuar con usuarios (resuelto)

Carlos autorizo datos ficticios realistas para profesores y definio sus importes:
tres profesoras de Patin por hora (12000, 15000 y 18000) y un profesor de Futbol
por comision (40%). Las cuatro altas se hicieron por pantalla y se verificaron
contra la base.

Carlos autorizo tambien datos ficticios realistas para las seis cuentas. Se
crearon por pantalla dos OPERATIVO y cuatro PROFESOR, estos ultimos vinculados
uno a uno a las fichas de profesor 4, 5, 6 y 7. Antes de las altas se intento
un correo ya usado: la pantalla lo rechazo por validacion y la base confirmo que
no quedo fila parcial. H03 queda resuelto.

## H04 — Configuraciones iniciales eliminadas e imposibles de recrear por pantalla (resuelto en ambiente)

La primera alta valida de alumno persistio correctamente (fila id 6, grupo 4 y
plan 7 activo), pero la redireccion a alumnos devolvio una pantalla 500.
`CobranzaEstadoService` exige `dias_gracia_cobranza` y la tabla
`configuraciones` esta vacia. La pantalla de configuracion solo permite editar
claves existentes, por lo que no ofrece una alta para restaurarla.

La migracion original que creo esa tabla inserta dos valores: `dias_gracia_cobranza`
con 10 y `dia_generacion_deuda` con 1. La orden nueva pide restaurar solo el
primero; ambos quedaron ausentes y ninguno puede crearse por pantalla. Se pausa
antes de insertar fuera de la aplicacion hasta que Carlos defina si se restaura
solo el valor que bloquea ahora o los dos valores originales.

Carlos indico restaurar ambas exactamente como la migracion. Se insertaron fuera
de la aplicacion, con guardas de entorno local, host 127.0.0.1 y base wings_test.
El listado de alumnos vuelve a abrir. El defecto de instalacion queda registrado,
pero H04 ya no bloquea esta ejecucion local.

Hallazgo separado aportado como verificado por Claude: la pantalla permite editar
`dia_generacion_deuda`, pero la generacion mensual conserva el dia 1 escrito en
`routes/console.php`; ese valor guardado no cambia el calendario. No se corrige
en esta carga, pero se debe evitar repetir el patron para punitorios.

## H05 — Las reglas de primer pago no rechazan superposiciones (resuelto)

La orden exige intentar un tramo superpuesto y recibir validacion sin escritura
parcial. Antes de crear ese dato de prueba se verifico el flujo real: el
controlador web valida rango y porcentaje, pero no consulta ni rechaza intervalos
que se crucen; la tabla tampoco impone esa restriccion. Por lo tanto, el intento
superpuesto seria aceptado y dejaria una configuracion ambigua. Se pausa antes de
crear deliberadamente una fila incorrecta. La primera regla valida 1–15/100%
se creo por pantalla y se verifico en la base.

El commit `c5faceb` incorporo la validacion central en alta y edicion. Se
verifico su presencia en ambas rutas y la regresion
`ReglaPrimerPagoSinSuperposicion` aprobo sus cinco casos. En la pantalla se
crearon los tramos 16–23/70% y 24–31/40%; un intento 10–20/50% devolvio el
mensaje de solapamiento y la base conserva solo las tres reglas esperadas.
H05 queda resuelto.

## H06 — Grupo de otro deporte aceptado al crear un alumno (corregido y revalidado)

El caso exigido de grupo ajeno al deporte no se rechazo. Desde el formulario se
envio un alumno con deporte Patin y grupo Futbol — Principiantes; la interfaz
redirecciono al listado con “Alumno creado correctamente”. La verificacion en
la base confirma la incoherencia: la fila id 17 tiene `deporte_id=4` (Patin) y
`grupo_id=8`, mientras que ese grupo tiene `deporte_id=5` (Futbol).

Fue una pantalla que aceptaba datos inconsistentes, no un mensaje de validacion.
Carlos corrigio la regla en `d628abd`, que ata el grupo al deporte elegido en las
reglas compartidas de alta y edicion. Se repitio C40 desde la pantalla: ahora se
recibe el mensaje exacto de validacion y no se crea fila nueva.

Por indicacion de Carlos se elimino la evidencia id 17 y su unico plan asociado.
Antes del borrado se verifico que no tenia pagos, deudas, asistencias ni
movimientos. Despues, la consulta de alumnos cuyo deporte difiere del deporte de
su grupo no devuelve filas.

La pantalla de edicion ya impide seleccionar un grupo de otro deporte: para una
alumna de Patin muestra solo grupos de Patin. Con autorizacion expresa de Carlos
se envio una unica solicitud manual de edicion con grupo de Futbol. El servidor
la rechazo con “El grupo seleccionado no pertenece al deporte elegido.” y la
consulta posterior confirma que la alumna conserva su grupo Patin original.

## Casos de alumnos ejecutados

| Caso | Resultado esperado | Resultado obtenido | Estado |
|---|---|---|---|
| Alta valida como admin | Alumno con grupo, plan y fecha correctos | Fila y plan creados; listado termina en pantalla 500 por H04 | FALLA |
| Alta valida como operativo | Alumno con grupo, plan y fecha correctos | Nueve altas validas adicionales, incluida una por la sesion OPERATIVO; filas, planes y fechas verificadas en base | PASA |
| DNI repetido en el mismo deporte | Rechazo con validacion, sin escritura parcial | Ver C34 | PASA |
| DNI repetido en otro deporte | Alta aceptada, un registro por deporte | Ver C35 | PASA |
| Celular vacio | Rechazo con validacion, sin escritura parcial | Ver C36 | PASA |
| Menor sin tutor | Rechazo pidiendo tutor, sin escritura parcial | Ver C37 | PASA |
| Nacimiento futuro | Rechazo sin escritura parcial | Ver C38 | PASA |
| Email invalido | Rechazo sin escritura parcial | Ver C39 | PASA |
| Grupo ajeno al deporte | Rechazo sin escritura parcial | C40 registra el defecto original; C41 confirma su rechazo tras la correccion | PASA CON DEFECTO HISTORICO |
| Fecha de alta anterior a hoy | Se conserva la fecha ingresada | Las diez altas validas incluyen fechas de 2025, enero–junio de 2026 y ninguna de julio–septiembre; valores verificados en base | PASA |

## Lo que no se pudo probar

Se completo la preparacion de datos y la carga por pantallas de deportes,
niveles, grupos/planes, los dos tipos de caja con sus saldos iniciales, los
cuatro profesores, las seis cuentas y diez alumnos validos. Se ejecutaron los
rechazos de validacion solicitados. H06 fue corregido y revalidado en alta; la
evidencia inconsistente se elimino y no quedan cruces deporte/grupo en la base.
La comprobacion de edicion tambien se completo con autorizacion expresa para una
solicitud manual: fue rechazada y no altero el grupo original. Carlos autorizo
conservar la dupla de Sofía Morales como excepción en Fútbol; por eso el seeder
creó 49 alumnos, no 50. Se ejecutó dos veces y dejó los 60 alumnos requeridos.
No se tocaron vistas, JavaScript, CSS ni headers. No se accedió al servidor.

Verificaciones tecnicas de cierre de la pausa: suite sobre wings_testing,
93 pruebas y 578 aserciones aprobadas; compilacion de vistas y diff --check OK.
Ese resultado automatizado corresponde a la pausa anterior y no sustituye
los intentos por pantalla. No se repitio la suite durante la investigacion de H02.
