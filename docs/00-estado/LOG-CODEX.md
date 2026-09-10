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

## 2026-09-10 — Codex CyE — COB-08 verificada por Chrome

Sobre d61cf42, cuatro cobros en base nueva: parcial con deuda y virtual quedan
42.000/10.000/PENDIENTE; segundo cobro 32.000 cancela; tramo 40% queda
24.000/10.000/PENDIENTE. Totales anunciados coinciden con pagos registrados.
Campo inicial descontado 42.000 o 24.000; carteles visibles al primer pago.
Segundo cobro sin nuevo descuento. Reloj sintetico 25/08 solo en copia aislada.
Suite aparte: 147 pruebas, 805 aserciones, verde; vistas compilan.
Reporte COB-08-VERIFICACION-2026-09-10.md. Sin datos reales ni deploy.
No se declara barrido adicional de cambio de plan; solo los cuatro casos pedidos.

Firma: **Codex CyE**.

---

## 2026-09-10 — Codex CyE — COB-02 verificada; descuento separado por Carlos

Completado rollback por Chrome en 5be4970: fallo de catalogo sintetico dentro
del servicio revierte plan y deuda. Conserva plan 40.000, deuda pendiente
40.000 sin pagos ni movimientos. Catalogo de prueba restaurado.
Con subida, bajada diferida y capturas iguales ya comprobadas, COB-02 verificada.
Carlos asigna a Claude la correccion del parcial con descuento; no se toca.
Suite aislada 5be4970: 140 pruebas, 753 aserciones, verde; vistas compilan.
Reporte COB-02-07 del 10/09 actualizado. Sin cambios de codigo ni datos reales.

Firma: **Codex CyE**.

---

## 2026-09-10 — Codex CyE — COB-02/07: parcial con descuento obliga a frenar

Chrome sobre 5be4970: subida anuncia y registra 60.000; bajada con asistencia
20.000 parcial sobre deuda 40.000 y plan desde octubre, limite conocido correcto.
Comparacion visual del form COB-02: capturas identicas y posiciones iguales.
Nuevo hallazgo en caso sintetico: plan 60.000, regla 70%, parcial enviado 10.000;
cobra 7.000 y reemplaza toda la deuda por 7.000 PAGADA, sin saldo.
Freno 6b, sin correccion. Pendientes rollback, deuda previamente pagada y suite.
Reporte: docs/06-pruebas/COB-02-07-VERIFICACION-2026-09-10.md.
Solo bases descartables. Sin deploy ni publicacion acreditada.

Firma: **Codex CyE**.

---

## 2026-09-10 — Codex CyE — COB-03 y total verificados en cob-total

Repeticion sobre `5238825` sin merge ni checkout del arbol compartido. Ambos casos
con descuento (septiembre existente/virtual) muestran cartel 70% y total 29.600;
septiembre conserva original 28.000, pagado 10.000, pendiente 18.000. Caso sin
descuento: total 38.000, correcto. Campos enteros deliberados, sin doble descuento.
Pago, imputaciones, movimiento, resumen por medio y tres PDF coinciden. Resumen
acumulado 97.200. En main `3a8b856`, los dos casos reproducen la perdida de 18.000.
Suite cob-total aislada: 133 pruebas, 726 aserciones, verde; sintaxis y vistas OK.
Solo bases sinteticas nuevas; no se tocaron datos reales ni se desplego. COB-02
no se declara verificado: no se cambio de plan durante este recorrido.
Reporte: `docs/06-pruebas/COB-03-VERIFICACION-2026-09-10.md`.
Se actualizan estado y planes de main indicando expresamente que la correccion
esta en otra rama. Siguiente: integrar solo con autorizacion. Publicacion pendiente.

Firma: **Codex CyE**.

---

## 2026-09-10 — Codex CyE — COB-03: freno por total visible distinto

Leidos plan y bitacora de cob-03, fijada en `02a5b5b`; main en `3a8b856`.
Copias aisladas y dos bases descartables nuevas. Caso 1 por Chrome en septiembre:
la pantalla anuncia $38.000 y luego registra $29.600. Agosto queda 19.600 PAGADA;
septiembre 28.000, pagado 10.000, PENDIENTE. Pago, imputaciones y movimiento correctos.
Freno §6b por la diferencia visible, sin modificar vistas ni logica ni mergear.
Caso 2, comparacion del cobro en main, resumen/PDF y suite pendientes.
Detalle: `docs/06-pruebas/COB-03-VERIFICACION-2026-09-10.md`.
Esperar decision: separar defecto visual o autorizar su correccion. Datos reales
intactos; servidor local detenido. Esta entrada no acredita subida a GitHub.

Firma: **Codex CyE**.

---

## 2026-09-10 — Codex CyE — COB-01 verificada: alcance aclarado

Carlos transmitio la aclaracion de Claude: «arqueo» queria decir resumen por medio
de pago. Ese resumen ya fue probado el 09/09; se levanta el freno sin implementar
funcionalidad adicional. COB-01 queda VERIFICADA, no se afirma despliegue.
Actualizados reporte, estado y ambos planes. Se conserva la evidencia de `022ddcd`
y la correccion `caa4976`. No se repitieron cobros ni suite por esta aclaracion
documental. La publicacion habia sido bloqueada por el control automatico y sigue
pendiente de autorizacion especifica; no afirmar que este avance esta en GitHub.

Firma: **Codex CyE**.

---

## 2026-09-09 — Codex CyE — COB-01 verificada por navegador; aclarar arqueo

Comparados `caa4976^` y `caa4976` en copias y bases MariaDB descartables separadas.
Chrome envio literalmente `28.000`: antes guardo 28 en deuda, pago, imputacion y
movimiento. `1.500.000` antes dio 422. Despues ambos importes quedan correctos;
resumen y detalle suman $1.528.000. PDF revisados visualmente, importes correctos.
Captura real por CDP Network, sin fabricar el payload. Reporte completo:
`docs/06-pruebas/COB-01-VERIFICACION-2026-09-09.md`.

Freno segun §6b: pedido dice arqueo; codigo ofrece resumen por medio y cierre sin
importe contado ni diferencia. Falta confirmar a que circuito refiere. No se
modifico funcionalidad, no se probo ni desplego contra datos reales, no se repitio
la suite completa. COB-01 no se marca cerrada. FDS-03 pausada por orden de Carlos.
Entornos y evidencia sintetica conservados localmente fuera de Git.

Firma: **Codex CyE**.

---

## 2026-09-09 — Codex CyE — FDS-02 cerrada: alertas recibidas

Instalados cron del scheduler y respaldo operativo con su helper. Configuracion
fuera de Git: directorio root:wings 750, archivo 640. Se conservaron copias del
cron y respaldo anteriores. Scripts implementados en `a3ddd7f`, desplegados dentro
de `81f27ef`; antecedentes: `3470114`, `d859c6e`, `306fa19`, `4e1674e`.

Verificacion real del 09/09: preflight 12 controles correctos, migraciones Ran;
scheduler real correcto. Fallo aislado usando PHP_BIN=/bin/false: salida 1,
incidente 11:27 GMT-3 y recuperacion automatica en 53 segundos. Fallo de copia a
un remoto inexistente: backup cifrado conservado en directorio temporal aislado,
alerta 11:28. Respaldo normal posterior: copia a Drive correcta 14:29:33 UTC.
Ambos heartbeats recuperados (Up); monitor HTTPS de /login tambien Up.
Carlos confirmo recepcion por email (captura) y Telegram: «Telegram tambien».

No se alteraron datos del club. El fallo de Drive conserva salida 0 cuando el
respaldo local funciona; ahora lo distingue la alerta, no el codigo de salida.
No se simulo caida del sitio ni se revalido el contenido minimo de la base:
esa evidencia sigue siendo historica. La corrida mensual del 01/09 no se pudo
demostrar. No confundir este cierre con el gate completo de produccion.

Ultima suite previa al cierre documental: 129 pruebas, 705 aserciones, verde;
vistas compiladas y diff de vistas/CSS vacio. Aparecio un cambio ajeno en
CobrarPrimeraCuotaWebTest.php (regresion de miles): se preserva y NO se incluye
en este cierre ni se afirma verde esa nueva prueba.
Actualizados estado, planes y checklist. Siguiente: FDS-03.

Firma: **Codex CyE**.

---

## 2026-09-09 — Codex CyE — acceso recuperado; FDS-02 incompleta

La extension correcta (ChatGPT para Chrome) permitio conectar Personal y abrir la
consola Hostinger. Servidor actualizado por fast-forward de `9fdd03d` a `81f27ef`;
solo cambiaron documentos y scripts de monitoreo. Prueba aislada en servidor:
`Monitoreo del scheduler: prueba correcta`. Log del respaldo del 09/09 03:15 UTC:
respaldo local correcto y copia Drive correcta.

Se creo `/etc/wings-monitor/alertas.env` fuera de Git con token Telegram y URLs de
los dos heartbeats, solicitando directorio root:wings 750 y archivo 640. Telegram
getUpdates identifico un unico chat privado. Se invoco el scheduler real y luego
preflight/migrate:status; la captura disponible muestra migraciones Ran, pero la
salida previa del scheduler/preflight quedo fuera de pantalla: NO se da por validada.
Tampoco se verificaron aun los permisos por lectura posterior.

No se reemplazo el cron de wings ni `/root/wings-backup/respaldar.sh`; siguen los
anteriores. No se provocaron fallos reales ni se verifico recepcion de alertas.
Better Stack mostraba ambos heartbeats Pending antes de la invocacion manual.
FDS-02 sigue ABIERTA. La siguiente consulta de consola fue rechazada por la revision
automatica: workspace sin creditos. No se intento evadir ese bloqueo.

Retomar: verificar configuracion sin mostrar secretos, salida de scheduler y
preflight; comparar e instalar respaldo con su helper; probar fallo/recuperacion y
recepcion email/Telegram; instalar cron definitivo y verificarlo. No repetir el
problema de perfiles: Personal ya fue conectado correctamente.

Firma: **Codex CyE**.

---

## 2026-09-09 — Codex CyE — estado real y continuidad pendiente

Carlos reporto que no encontro en su casa el avance esperado. Verificacion directa
con `git ls-remote origin refs/heads/main`: GitHub apunta a
`a3ddd7faa27f157be64af978d6c38bd99729ef49`, igual que HEAD local. Ese commit contiene
los scripts de monitoreo preparados; no demuestra instalacion en el servidor.

El indice HTML portable v4 y sus cambios en AGENTS, CLAUDE, estado, checklist y plan
siguen SIN COMMIT y SIN PUSH al momento de esta comprobacion. La entrada del 08/09
sobre el HTML describe trabajo local, no una entrega publicada. Tambien hay movimientos
preexistentes de evaluaciones hacia `Evaluaciones previas/` pendientes de versionar.
El intento de Git del 08/09 fue bloqueado por permisos y por limite de uso en la
revision automatica. No se completo la sincronizacion solicitada.

FDS-02 sigue ABIERTA. Hoy se verifico la identidad SSH del servidor, pero las dos
claves disponibles fueron rechazadas. La consola web antigua devolvio 403. El
navegador conectado expone solo el perfil Comercializacion; Carlos indico que la
cuenta de Hostinger esta en Personal, aun no disponible para esta sesion.
No se instalaron scripts, no se modificaron cron ni datos del servidor y no se
probaron alertas reales hoy. Better Stack figura configurado en el registro del
08/09; su estado actual no fue revalidado.

Siguiente: recuperar acceso desde Personal, instalar configuracion y scripts,
probar fallo/recuperacion de scheduler y copia externa, verificar recepcion de
alertas y cerrar con evidencia. Resolver tambien la entrega Git pendiente del HTML
y documentos. Solo afirmar push completado despues de verificar el hash remoto.

Firma: **Codex CyE** (Carlos identifica esta sesion como la de trabajo, fuera de casa).

---

## 2026-09-08 — Codex CAB — índice HTML portable v4

El índice para Carlos conserva las marcas en `localStorage`, pero ahora también las
codifica en el enlace (`#estado=...`) y permite exportarlas/importarlas como JSON.
Así el avance se puede trasladar a otra computadora sin depender de su navegador;
el enlace requiere abrir el mismo HTML publicado y el JSON funciona como respaldo.
Se migran marcas de las versiones v2 y v3. Se actualizaron las referencias al plan
vigente a `2026-09-08.v4`.

Verificación: sintaxis JavaScript del HTML correcta con Node, `git diff --check`
limpio y `git diff --stat -- resources/views resources/css` vacío.

Firma: **Codex CAB**.

---

## 2026-09-08 — Codex CAB — FDS-02 monitoreo preparado; despliegue pendiente

Se activo en Better Stack el control HTTPS externo con aviso por email y se crearon
heartbeats separados para scheduler y backup. En el repositorio se agregaron wrappers
que informan exito/fallo y envian Telegram ante fallos explicitos; los tokens, chat IDs
y URLs secretas quedan fuera de Git. Si Drive falla, la copia local se conserva y la
alerta distingue ambos resultados.

La prueba aislada de scheduler aprobo exito, fallo, codigo de salida, heartbeat `/fail`
y Telegram sin usar red ni credenciales. Suite completa: 129 pruebas, 705 aserciones;
vistas y CSS sin cambios. FDS-02 sigue abierta: esta computadora no tiene configurado
un acceso SSH cuya clave de host pueda verificarse, por lo que no se desplego ni se
simularon fallos reales. La evidencia historica queda ligada a `3470114`, `d859c6e`,
`306fa19` y `4e1674e`; el commit de monitoreo se agrega al cierre definitivo.

Firma: **Codex CAB**.

## 2026-09-08 — Codex CAB — FDS-01 cerrado

Se sinceraron `ESTADO-ACTUAL.md`, `PLAN-PRODUCCION.md` y `CHECKLIST-CARLOS.md` contra
las evaluaciones cruzadas y lo verificado el fin de semana. El plan de produccion de
agosto quedo como contexto corto, no como orden vigente; el checklist ahora contiene
solo acciones o decisiones reales de Carlos.

Los tres documentos reflejan servidor en `9fdd03d`, Vanina ADMIN, base minima, suite
MariaDB 129/705, dump retirado, Cloudflare y Cobranza para OPERATIVO. Quedan visibles
npm, monitoreo, limites de recuperacion, cobros prioritarios y reproducibilidad de la
entrega. El plan sube a `2026-09-08.v2` y FDS-01 queda marcado cerrado, pendiente de
verificacion por Claude. Sin cambios de aplicacion, base, vistas ni CSS.

---

## 2026-09-08 — Codex CAB — plan cruzado de trabajo v1

Se consolidaron las evaluaciones de Codex y Claude del 08/09 con los commits y
bitacoras del fin de semana. Se creo un plan tecnico para agentes y un HTML marcable
para Carlos, ambos con los mismos IDs y corte `2026-09-08.v1`.

El plan separa hallazgos reproducidos, caminos verificados solo en codigo, decisiones
de Carlos y mejoras posteriores. Primero ordena revalidar el fin de semana; despues
los tres defectos principales del cobro, integridad financiera, seguridad/recuperacion,
prueba integral y pedidos de entrega. Las afirmaciones corregidas en el cruce no se
convirtieron en tareas.

`AGENTS.md`, `CLAUDE.md` y `ESTADO-ACTUAL.md` apuntan al nuevo plan. Sin cambios de
aplicacion, base, vistas ni CSS. Los movimientos locales preexistentes de evaluaciones
anteriores se preservaron y no pertenecen a este trabajo.

---

## 2026-09-08 — Codex CAB — ubicación de los informes corregida

A pedido de Carlos, ambos informes Codex se movieron a docs/07-evaluacion, junto
al informe de Claude. Se corrigieron el enlace relativo y la referencia de esta
bitácora; se eliminó la carpeta EVALUACION creada por error, una vez vacía.
Sin cambios de aplicación ni datos.

## 2026-09-08 — Codex CAB — cruce verificado con la evaluación de Claude

Se leyeron ambos formatos del informe Claude, incorporados en `4e1674e` sin cambios
de aplicación respecto de `97fb840`. Tres agentes revalidaron cuerpos, rutas y contratos.
Se ampliaron ambos informes Codex con 17 puntos (incluidos complementos y un caso
condicional) y 21 correcciones o precisiones al informe de Claude. Se reconoce la
omisión propia del envío de monto/plan en la pantalla y del detalle perdido al anular.
Se distinguen errores de Claude de decisiones vigentes: nota de revisión obligatoria,
condonación con autor/fecha, tope del servicio, 419 localizado, datos por deporte y
exportaciones excluidas. Sin cambios de aplicación ni base; sin suite compartida.
Validación documental, sintaxis JavaScript del HTML y diff visual vacío. Los archivos
de Claude permanecen intactos. Pendiente: decidir reproducciones aisladas antes de
corregir; no se convierte la evaluación en autorización de implementación.

## 2026-09-08 — Codex CAB — evaluación de sistema, seguridad y usuario

Evaluación solicitada por Carlos, con tres agentes de lectura y consolidación en
`docs/07-evaluacion/Evaluacion Codex 8-9-26.md` y su versión `.HTML` navegable. Referencia:
commit `97fb840`. Son 22 fichas que separan escenarios nuevos, conocidos y diferidos;
no son 22 defectos reproducidos. Se leyeron cuerpos y rutas, y se contrastaron contratos
y decisiones. Composer audit sin avisos; npm audit informa 11 paquetes afectados,
sin equivaler a explotación demostrada en Wings. Sin cambios funcionales, consultas
o escrituras de base, pruebas de cobro, despliegues ni ejecución de suite compartida.
Se preservaron las decisiones sobre FIFO, asistencia, API, carga humana, CSP y reportes.
La documentación de estado contradictoria se señala en la evaluación, sin modificar
planes en esta tarea de solo análisis. Pendiente: cruzar con Claude y decidir qué
escenarios reproducir en entorno aislado antes de autorizar correcciones.

## 2026-09-06 — Codex CAB — paso 5 detenido por acceso de OPERATIVO

Se leyó el cuerpo de CobranzaEstadoService y se calcularon los 60 estados
individuales: todos DEUDOR. Pantalla ADMIN: 0/0/0/60; sus 60 filas coinciden,
incluidos los 12 sin deuda. Períodos y montos de los 60 comparados con Excel,
sin diferencias. Sesión OPERATIVO autenticada: /cobranza redirige a /caja.
H-DI-01 registrado: contradice el acceso completo exigido; ruta bajo
ensure.admin.web y cuerpo del middleware explican el resultado observado.
Se frenó sin corregir código; PROFESOR no probado y paso 6 no iniciado.
Base intacta: 81 deudas por $2.997.000, cero pagos/imputaciones; huellas de
alumnos, planes, users y las tres tablas financieras iguales antes/después.
Informe y ESTADO-ACTUAL actualizados. Suite 121 pruebas, 694 aserciones;
vistas compiladas/limpiadas y diff visual vacío. Pendiente: decisión de Carlos
sobre corrección y revalidación de acceso. Firma: Codex CAB.

---

## 2026-09-06 — Codex CAB — deuda inicial cargada; pasos 1–4 aprobados

Carlos resolvió la pausa: solo importador, sin seeders ni pasos 5 en adelante.
Git pull confirmó main actualizado en eae0ff6. En wings_test se validaron ambos
Excel: 81 cuotas válidas y ocho filas rechazadas juntas, sin escrituras.
La carga dejó 81 PENDIENTE por $2.997.000, 48 alumnos con deuda y 12 sin ella.
La segunda corrida rechazó duplicados conservando el contenido completo de las
81 filas. Reversión a cero, revalidación sin escrituras y recarga a 81 aprobadas.
Alumnos (60), planes (60), usuarios (7) y pagos (0) conservaron sus huellas de
contenido en cada momento; imputaciones cero. Excel y generador intactos.
Se completó RESULTADO-DEUDA-INICIAL-V1.md y se cerró la nota de pausa en
ESTADO-ACTUAL.md. Suite por paso: 121 pruebas, 694 aserciones. Vistas compiladas
y limpiadas; diff de vistas/CSS vacío. Sin cambios de código ni commit.
Pendiente: indicación de Carlos para catálogos por pantalla y pasos posteriores.
Firma: Codex CAB.

---

## 2026-09-06 — Codex CAB — pausa previa a la prueba de deuda inicial

Se verificó main en 2145886 y conexión local/127.0.0.1/wings_test: 60 alumnos,
60 planes, 7 usuarios y cero deudas, pagos e imputaciones. La orden del chat
pide pasos 1–4; la orden guardada exige antes CatalogosSeeder. Leer su cuerpo
reveló que asigna false a es_reservado_sistema en Cuotas y Sueldos, actualmente
en 1 en ambas filas. No se ejecutó seeder ni importador, ni se corrigió código.
Resultado y alternativas registrados en RESULTADO-DEUDA-INICIAL-V1.md: Carlos
debe decidir entre probar solo el importador o resolver primero el Paso 0.
Suite: 119 pruebas y 689 aserciones; vistas compiladas y limpiadas; diff de
vistas/CSS vacío. Firma: Codex CAB.

---

## 2026-09-06 — Codex CAB — importador inicial de deuda Excel

Se agrego el comando `wings:importar-deuda-inicial` con lector
`phpoffice/phpspreadsheet` 5.9.0 para planillas `.xlsx`. Valida el archivo completo
antes de escribir: encabezado, pares monto/período, monto positivo, período desde
2025, alumno por DNI+deporte, filas duplicadas y deudas ya existentes. Si hay un
error informa todas las filas y no inserta ninguna deuda. Una reversión con el mismo
Excel solo elimina deudas pendientes e intactas, sin tocar alumnos ni pagos.

Se documentaron las decisiones y el procedimiento de rehacer una carga, más una
plantilla vacía y un ejemplo. El ejemplo válido se verificó con `--solo-validar`
contra los 60 alumnos locales sin crear deudas. `composer audit --locked` no reportó
avisos. Suite final: 111 pruebas y 663 aserciones; vistas compiladas, lint de los
archivos PHP y diff de vistas/CSS sin cambios. Pendiente: correr la carga real solo
cuando Vanina entregue el Excel definitivo. Firma: Codex CAB.

---

## 2026-09-06 — Codex CAB — seeder de primera carga ejecutado e idempotente

Carlos autorizo la excepcion: Sofía Morales cuenta como una mujer en Futbol, por
lo que el seeder completa 49 alumnos nuevos. Se agrego PrimeraCargaAlumnosSeeder
con datos fijos y realistas, validacion de la base manual previa y protecciones
contra ejecucion en produccion. Una regresion prepara la base inicial, lo corre
dos veces y confirma idempotencia, planes, fechas, distribucion y ausencia de
deudas y pagos.

Tras verificar entorno local/127.0.0.1/wings_test, el seeder se ejecuto dos
veces. Resultado real: 60 alumnos, Patin 40/Futbol 20, tramos 12/18/30, ningun
DNI repetido dentro de un deporte, ningun alumno sin plan activo y cero deudas
y pagos. No se tocaron usuarios, configuraciones, catalogos, vistas, CSS ni JS.
La suite final aprobo 108 pruebas y 642 aserciones; vistas compiladas, lint de
los dos archivos PHP nuevos y diff de vistas/CSS sin cambios. Se actualizaron los
documentos que declaran la cantidad de pruebas. Firma: Codex CAB.

---

## 2026-09-06 — Codex CAB — dupla conservada; pausa por contradiccion de genero en el seeder

Carlos eligio conservar la fila C35 y convertirla en la misma persona de Patin:
se actualizo desde la pantalla el nombre y apellido de la inscripcion de Futbol,
manteniendo su DNI, grupo y plan. La base confirma que las dos filas ahora tienen
el mismo DNI y nombre. Por eso el seeder debe crear 49, no 50, para llegar a 60.

La dupla es una mujer tambien en Futbol, mientras que PRIMERA-CARGA-V1 exige 20
varones en Futbol. Si se agregan 49 conforme a ese genero, el resultado seria
19 varones y 1 mujer en Futbol. Se frena antes de escribir el seeder: Carlos
debe confirmar que la dupla es una excepcion o cambiar la definicion. Firma:
Codex CAB.

---

## 2026-09-06 — Codex CAB — seeder pausado: la base tiene once alumnos, no diez

Antes de escribir el seeder se leyo la especificacion y se verifico la base.
La prueba C35, que acepta el mismo DNI en otro deporte, dejo una fila valida
adicional. El estado real es once alumnos: seis de Patin y cinco de Futbol; por
fecha son dos de 2025, uno de enero-marzo y ocho de abril-junio. La orden cuenta
diez y por eso indica crear cincuenta; hacerlo asi llevaria a 61 alumnos y
alteraria tambien el reparto de fechas.

Se frena antes de crear codigo o escribir datos. Pendiente de Carlos: decidir
si se borra la fila C35 y se crean 50, o si se conserva y se crean 49, definiendo
ademas si representa la persona inscripta en ambos deportes. Firma: Codex CAB.

---

## 2026-09-06 — Codex CAB — H06 cerrado; etapa manual de primera carga completa

Carlos autorizo una unica solicitud manual de edicion porque la pantalla ya
oculta los grupos de otro deporte. El servidor rechazo el grupo incompatible
con el mensaje esperado y la alumna mantuvo su grupo original. Junto con el
rechazo desde la pantalla al crear, H06 queda revalidado en ambas puertas.

La etapa manual de primera carga queda completa: diez alumnos validos, altas y
rechazos documentados en RESULTADO-PRIMERA-CARGA-V1.md, sin filas parciales en
los rechazos. No se inicio el seeder de cincuenta alumnos; se espera el aviso de
Carlos antes de hacerlo. Firma: Codex CAB.

---

## 2026-09-06 — Codex CAB — H06 revalidado en alta; prueba de edicion bloqueada por la propia interfaz

Se actualizo a `d628abd` y se repitio el alta Patin con grupo de Futbol. La
pantalla muestra el mensaje esperado y la consulta concreta confirma que no se
creo fila. Por indicacion de Carlos se elimino el alumno inconsistente id 17 y
su unico plan; antes no tenia dependencias operativas y despues no quedan
alumnos con deporte distinto al de su grupo.

La edicion no permite ejecutar el caso desde pantalla: al editar una alumna de
Patin, el selector contiene solo grupos de Patin. No se envio una solicitud
manual ni se modifico la interfaz, porque la prueba de primera carga exige usar
pantallas. Pendiente de Carlos: autorizar esa solicitud manual o aceptar como
evidencia el bloqueo de interfaz junto con las regresiones del commit. No se
inicio el seeder. Firma: Codex CAB.

---

## 2026-09-06 — Codex CAB — primera carga detenida por grupo incompatible aceptado

Se completaron las diez altas validas de alumnos por pantalla, las reglas de
primer pago y los rechazos solicitados: DNI repetido en el mismo deporte,
celular vacio, menor sin tutor, nacimiento futuro y email invalido fueron
validados sin filas parciales. El mismo DNI en otro deporte se acepto y la base
confirma una fila por deporte, conforme al contrato.

Al ejecutar el rechazo de grupo ajeno al deporte, la pantalla creo el alumno en
lugar de rechazarlo. La base confirma una fila con deporte Patin y grupo cuyo
deporte es Futbol. Se registro como H06 en RESULTADO-PRIMERA-CARGA-V1.md y se
detuvo la prueba sin modificar codigo ni borrar esa evidencia. No se escribio
el seeder ni se tocaron vistas, CSS, JavaScript o headers. Pendiente: decision
de Carlos sobre la correccion de H06 y el tratamiento de la fila inconsistente.
Firma: Codex CAB.

---

## 2026-09-06 — Codex CAB — H05 verificado y reglas de primer pago completas

Se verifico `c5faceb`: llamadas a reglaSuperpuesta en alta y edicion, y cinco
pruebas de ReglaPrimerPagoSinSuperposicion aprobadas. Desde la pantalla se
crearon los tramos 16–23/70% y 24–31/40%. El intento 10–20/50% fue rechazado
con mensaje de solapamiento; la base conserva exactamente las tres reglas
esperadas y ninguna fila de prueba. H05 queda resuelto. Se continua por los
alumnos manuales, incluyendo al menos dos altas de 2025. Firma: Codex CAB.

---

## 2026-09-06 — Codex CAB — fechas de alta actualizadas; H05 sigue bloqueando alumnos

Se incorporo la especificacion de fechas: los diez manuales deben incluir al
menos dos altas de 2025 y ninguna alta puede ser de julio, agosto o septiembre
de 2026. La alumna ya creada se verifico en wings_test con fecha 2026-03-01,
dentro del tramo enero–marzo, por lo que no se edito. La carga no continua hasta
resolver H05: hoy no existe rechazo de reglas de primer pago superpuestas. Firma:
Codex CAB.

---

## 2026-09-06 — Codex CAB — primera carga: H04 resuelto; pausa H05 por reglas superpuestas

Por indicacion de Carlos se restauraron fuera de la aplicacion ambas configuraciones
de la migracion, con guardas de local, 127.0.0.1 y wings_test: dias_gracia_cobranza
10 y dia_generacion_deuda 1, con tipos y descripciones originales. El listado de
alumnos volvio a abrir y la alumna ya persistida conserva grupo, plan y fecha.

Se deja registrado el hallazgo aportado como verificado por Claude: editar
dia_generacion_deuda en la pantalla no modifica el calendario mensual, cuyo dia
sigue escrito en routes/console.php. No se corrige en esta carga; debe evitarse
el mismo patron en las futuras configuraciones de punitorios.

La regla de primer pago 1–15/100% se creo por pantalla y la base confirma la fila
activa. Se freno antes del intento superpuesto: el controlador web y la tabla no
validan ni impiden cruces de rangos, por lo que seria aceptado y dejaria datos
ambiguos, contradiciendo el criterio de aceptacion. Pendiente: decidir e
implementar esa validacion antes de los dos tramos restantes y los alumnos. Sin
cambios de codigo, vistas, CSS, JS o headers. Firma: Codex CAB.

---

## 2026-09-06 — Codex CAB — primera carga detenida por configuraciones ausentes

La primera alta valida de alumno por pantalla se persistio con su grupo, plan y
fecha de alta, pero la redireccion al listado termino en pantalla 500:
CobranzaEstadoService requiere dias_gracia_cobranza y configuraciones esta vacia.
La pantalla de configuracion solo edita claves existentes, por lo que no puede
restaurarla. La migracion que creo la tabla inserta dos claves iniciales:
dias_gracia_cobranza=10 y dia_generacion_deuda=1. La orden nueva pide restaurar
solo la primera, aunque ambas estan ausentes y ninguna se crea por interfaz.

Se freno antes de cualquier escritura fuera de la aplicacion. Pendiente de Carlos:
indicar si restaurar solo dias_gracia_cobranza o los dos valores originales. El
resultado y ESTADO-ACTUAL se actualizaron con H04. Sin cambios de codigo, vistas,
CSS, JS ni headers. Firma: Codex CAB.

---

## 2026-09-06 — Codex CAB — primera carga: usuarios ficticios autorizados y verificados

Carlos autorizo datos ficticios realistas para las seis cuentas. Desde la
pantalla se crearon dos usuarios OPERATIVO y cuatro PROFESOR; las cuatro cuentas
de profesor quedaron vinculadas, respectivamente, a las fichas 4, 5, 6 y 7.
La lectura de wings_test confirma seis filas activas con esos roles y vinculos.
Antes se envio un alta con correo ya usado: la interfaz devolvio validacion y la
base confirma una sola fila con ese correo y ninguna fila del intento. No se
registran claves ni datos personales en esta bitacora.

H03 queda resuelto. Pendiente siguiente: diez alumnos, todos por pantalla,
intercalando sesion ADMIN y OPERATIVO, con resultados y ausencia de escrituras
parciales documentados en RESULTADO-PRIMERA-CARGA-V1.md. No se escribio seeder
ni se modifico codigo, vistas, CSS, JS o headers. Firma: Codex CAB.

---

## 2026-09-06 — Codex CAB — primera carga: profesores creados con datos ficticios autorizados

Carlos autorizo datos ficticios realistas y definio tres valores por hora para
Patin (12000, 15000 y 18000) y una comision de Futbol de 40%. Se crearon los
cuatro profesores desde la pantalla y se verificaron contra wings_test: las
tres profesoras de Patin quedaron activas, por hora y sin comision; el profesor
de Futbol quedo activo, por comision y sin valor hora. No hubo codigo ni cambios
en vistas, CSS, JS ni headers.

La pausa H03 queda limitada a los usuarios: aun faltan la identidad y los datos
de dos operativos y de una cuenta de profesor por cada profesor. Pendiente:
Carlos los define o autoriza datos ficticios para esas cuentas. Resultado
actualizado en RESULTADO-PRIMERA-CARGA-V1.md. Firma: Codex CAB.

---

## 2026-09-06 — Codex CAB — primera carga: cajas verificadas; pausa por datos no especificados

Se revalido el proceso local sin exponer secretos: entorno local, clave presente,
sin cache de configuracion, host 127.0.0.1 y base wings_test. Con una sesion web
nueva, Efectivo/EFE se creo por pantalla con saldo inicial 250000; Mercado Pago/MP
se creo por pantalla con saldo inicial 1320000. Las dos filas estan activas, sin
descubierto ni movimientos; sus saldos se comprobaron contra la base. No se cambio
APP_KEY, .env, caches, codigo, vistas, CSS, JS ni headers. El 500 anterior queda
registrado como no reproducido, no explicado.

La carga se detuvo antes de profesores/usuarios: PRIMERA-CARGA exige modalidades
y cantidades, pero no provee nombres, valores hora, porcentaje de comision ni
datos de las seis cuentas. No se inventaron datos de una carga que el documento
define como real. Pendiente: Carlos debe proveerlos o autorizar datos de prueba.
Resultado actualizado en RESULTADO-PRIMERA-CARGA-V1.md y contradiccion en
ESTADO-ACTUAL.md. Firma: Codex CAB.

---

## 2026-09-06 — Codex CAB — migracion aplicada; reintento detenido por H02

Estrategia de modelos acordada con Carlos: la carga manual y su verificacion se
haran con Terra medium, priorizando lectura cuidadosa de pantallas, validaciones,
errores y comprobacion de base. El seeder de los cincuenta restantes se evaluara
con Luna cuando los diez casos manuales hayan terminado y pasado sin sorpresas,
tal como exige PRIMERA-CARGA. El cambio sera explicito por fase; no se asume
enrutamiento automatico del modelo. Cualquier error debe detener la cadena,
registrarse en RESULTADO-PRIMERA-CARGA-V1 y reflejarse en el informe, sin
continuar ni corregir por consola.

Puesta al dia solicitada: se relevaron checkout, estado, ambas bitacoras y el
resultado de primera carga. El estado sigue siendo H02: `wings_test` conserva
2 deportes, 4 niveles, 6 grupos y 12 planes; tipos de caja permanece vacia y
la migracion de saldo inicial esta aplicada. No se cambiaron datos ni codigo
durante este relevo. Para continuar la prueba se recomienda el modelo de mayor
capacidad disponible, GPT-6 Astra, con razonamiento alto; la seleccion queda
registrada como orientacion operativa, no como cambio del proyecto.

Carlos indico cargar saldos iniciales al crear los tipos nuevos: Efectivo 250000
y Mercado Pago 1320000. Aplicada solo la migracion de saldo_inicial en wings_test,
con guardas de ambiente local, host127.0.0.1 y base efectiva; columna verificada.
No se alteraron los datos ya cargados ni se ejecuto ninguna otra migracion.

C14 por navegador: alta nueva Efectivo/EFE con saldo250000. Pantalla500;
log11:16:37 informa MissingAppKeyException bajo entorno production. La consola
posterior identifica local/wings_test; tabla tipos_caja vacia, sin escritura
parcial. Un GET posterior del formulario responde con sesion admin, pero la causa
del error no se comprobo. No se atribuye al trabajo paralelo sin evidencia.

Se freno segun la premisa. No se genero ni cambio clave de aplicacion, no se
modifico .env, no se limpiaron caches ni se repitio nuevamente el alta.
Saldos indicados documentados; resultado y estado actual actualizados.
Ambas cajas, profesores, usuarios adicionales y alumnos siguen pendientes.
Sin cambios de codigo, vistas, CSS, JS o headers. No se toco el servidor.

---

## 2026-09-06 — Codex CAB — primera carga detenida en C13, esquema local pendiente

Tras resolver niveles y omitir superadmin, se limpio solo wings_test con guardas
de ambiente/host/base, tablas InnoDB verificadas, transaccion y verificacion antes
del commit. Se conservaron el admin, Cuotas, Cuota Mensual reservado, Sueldos sin
hijos y migrations (historial tecnico). Se eliminaron los datos anteriores de
prueba, sin respaldo nuevo por ser descartables. No se altero el esquema ni se
reiniciaron ids. No se toco servidor ni wings_testing para esta limpieza.

Por navegador, sesion admin: 12 altas aprobadas con verificacion de filas reales:
2 deportes, 4 niveles, 6 grupos con 12 planes a los precios de PRIMERA-CARGA.
C13 (Efectivo/EFE, saldo inicial 0, sin descubierto) devolvio pantalla 500,
SQLSTATE 42S22: columna saldo_inicial inexistente. No es mensaje de validacion.
Se verifico tipos_caja vacia tras el rechazo: no hubo fila parcial.

Causa comprobada: migracion 2026_09_03_000001_add_saldo_inicial_to_tipos_caja_table
pendiente en wings_test. El archivo existe y su up agrega la columna con default 0.
Error de preparacion propio: se conservo el esquema existente al limpiar y no se
consulto migrate:status antes de cargar. Se registro sin atribuirlo a una
validacion funcional rota. No se ejecuto la migracion ni se arreglo codigo.

Resultado y estado actual actualizados. No se iniciaron profesores, usuarios
adicionales ni alumnos; no se escribio el seeder de cincuenta. No se tocaron
vistas, CSS, JS ni headers. Pendiente: Carlos autorice aplicar la migracion local
existente y repetir C13, o prepare el ambiente por su cuenta. No repetir C01-C12.

Verificacion al pausar: suite en wings_testing **93 pruebas, 578 aserciones**
aprobadas (incluye cambios paralelos incorporados durante la carga); compilacion
y limpieza de vistas OK, diff --check OK, diff de vistas/CSS/JS/middleware vacio.
No se editaron archivos PHP, por lo que no hay sintaxis PHP modificada a verificar.
La suite usa otra base y no invalida el hallazgo del esquema de wings_test.

---

## 2026-09-06 — Codex CAB — primera carga: definiciones recibidas, se retoma

Carlos confirmo Futbol/Avanzadas, compartiendo el nivel y sin agregar un quinto.
Tambien indico omitir el superadmin en esta etapa local y dejarlo para el servidor.
PRIMERA-CARGA ya fue actualizada en paralelo con ambas decisiones; se releyo
completa y se preservo esa edicion. Se retira el bloqueo del estado actual.
Las comprobaciones e intentos se registran en RESULTADO-PRIMERA-CARGA-V1.

---

## 2026-09-06 — Codex CAB — primera carga pausada antes de borrar datos

Leidos AGENTS completo, PRIMERA-CARGA completo, guia, ambas bitacoras, estado,
checklist y permisos. Pull sin novedades: los tres commits anunciados ya estan
en main; HEAD 7aa9479. Solo habia un cambio local de configuracion de Claude,
que se preservo. La frecuencia obligatoria ya esta en f02cb9e.

Verificacion de preparacion exclusivamente de lectura: ambiente local,
127.0.0.1, wings_test; admin indicado existente y activo; consulta de cuentas
protegidas vacia. Existen Cuotas, Cuota Mensual reservado y Sueldos.

Contradiccion: la especificacion fija cuatro niveles, incluido Avanzadas, pero
exige Futbol/Avanzados. Leido el cuerpo del accesor de Grupo y el alta: el nombre
se compone de deporte y nivel, sin campo de nombre independiente. Opciones sin
elegir: compartir Avanzadas tambien en Futbol o autorizar un quinto nivel Avanzados.
Ademas falta identificar que cuenta sera el superadmin (existente a marcar o nueva
a crear por consola). No se invento una identidad ni se cambiaron permisos.

Se registraron el bloqueo en ESTADO-ACTUAL y las comprobaciones en
RESULTADO-PRIMERA-CARGA-V1. Los diez casos de alumnos quedan explicitamente NO SE
PUDO, aun no intentados. No hubo borrados ni escrituras en wings_test, cargas por
pantalla, seeders ni cambios de aplicacion, vistas, CSS, JS o headers.
Siguiente paso: recibir esas dos definiciones antes de reiniciar la base.

---

## 2026-09-06 — Codex CAB — frecuencia obligatoria; aceptacion completada

Objetivo: impedir que alta, edicion o eliminacion individual dejen un grupo sin
frecuencias. Pull sin novedades. Leidos AGENTS, guia extendida, ambas bitacoras,
regla de PRIMERA-CARGA y contrato Alumno-Grupo-Deporte-Deuda V3; inspeccionados los
cuerpos de las tres acciones, modelos, rutas y vistas (solo lectura).

Cambios: planes required/array/min:1 en alta y edicion; rechazo de la ultima
frecuencia con motivo explicito. Los errores se muestran por el aviso general
que ya existe en el layout, porque la vista de grupos no muestra la clave planes.
Alta transaccional; edicion y eliminacion serializadas con bloqueo del grupo.
Se valida que los ids enviados pertenezcan al grupo y se vuelven a comprobar
despues del bloqueo: una lista con un id inexistente no cuenta como frecuencia
y no puede vaciar el grupo. Se conserva la proteccion de planes con alumnos.
No se afirma haber ejecutado una prueba de concurrencia con dos conexiones.

Regresion: antes del cambio fallaron los tres rechazos y paso el flujo valido.
Despues se agrego cobertura de id inexistente: cinco pruebas nuevas. Suite completa
en MariaDB wings_testing: **91 pruebas, 574 aserciones aprobadas**. Sintaxis de
controlador y test correcta; view:cache/view:clear y diff --check correctos.

Aceptacion local en navegador (gestion-wings, base efectiva wings_test):
- Alta Hockey/Avanzadas sin frecuencias: rechazada con mensaje en castellano.
- Edicion Futbol/Intermedias quitando su unica frecuencia: rechazada con mensaje.
- Eliminar esa ultima frecuencia: PASA. Inicialmente el clic fue interrumpido por
  "¿Eliminar este plan?" y se solicito autorizacion al usuario; no se ejecuto
  ninguna aceptacion del dialogo. Al recuperar la pestaña, el navegador fallo
  dos veces con timeout de Emulation.setFocusEmulationEnabled. No se pudo
  verificar el estado visual final desde la herramienta. Carlos aporto luego
  una captura del mismo grupo con el aviso: "No se puede eliminar: el grupo
  quedaria sin frecuencias y no se podrian cargar alumnos", y la frecuencia
  todavia visible. La evidencia visual final es esa captura del usuario;
  la regresion HTTP tambien pasa.

Tras cada uno de los dos rechazos ejecutados se compararon todas las filas de
grupos, grupo_planes y alumno_planes con la huella anterior: sin ningun cambio.
Se conservan 3 grupos y 6 planes. Grupos sin frecuencias: **0**; sin frecuencias
activas: **0**. No se limpiaron datos ni se inventaron planes.

Documentacion actualizada: regla en PRIMERA-CARGA, estado y cantidades de pruebas
en estado/plan/checklist/guia. No se tocaron vistas, CSS, JS ni headers. El diff
visual previo de tipos-caja/_form sigue intacto y pertenece a saldo inicial, no
a esta tarea; se preservaron tambien los otros cambios locales anteriores.

Aceptacion cerrada tras la captura: se volvio a consultar wings_test y la huella
de las tres tablas coincide exactamente con la anterior a todos los intentos.
El grupo 3 conserva el plan 4, activo, de dos clases por semana y precio 30000.
No hubo escrituras parciales en los tres casos. No se repitio la eliminacion.
Pendiente de integrar en Git; no se hizo despliegue ni push.

---

## 2026-09-06 — Codex CAB — cuenta ADMIN local solicitada

Se creo la cuenta solicitada por Carlos exclusivamente en wings_test, conexion
127.0.0.1 y entorno local verificados. Rol ADMIN, activa, sin marca de superadmin.
La contraseña se guardo mediante el cast hashed del modelo; se verificaron el
hash persistido y las credenciales con el proveedor de autenticacion de Laravel.
No se registran correo, contraseña ni hash en esta bitacora. No se modificaron
cuentas existentes, codigo, vistas ni la base del servidor.

---

## 2026-09-05 — Codex CAB — dump cerrado por las dos puertas

Objetivo: retirar database/dump.sql, impedir la exportacion automatica desde el
seeder e invalidar sesiones y tokens locales. El mensaje anunciaba dos tareas,
pero solo incluia la primera: no se infirio una segunda.

`git pull --ff-only`: sin novedades; B2 ya estaba en el checkout. Se verificaron
AGENTS, ambas bitacoras, estado, plan, permisos y el cuerpo de DemoSeeder.
Cambios: dump retirado del indice y del directorio local, regla explicita en
.gitignore y eliminacion de la llamada y del metodo fase10Dump del seeder.
Se agrego DatabaseExportSafetyTest para impedir exportadores en database/ y
mantener la regla de ignore. Tambien se actualizaron la guia vigente, estado,
plan y checklist; en los scripts manuales solo comentarios y mensajes que
indicaban versionar el dump o recrear cuentas mediante UserSeeder.

Invalidacion: conexion efectiva verificada como mysql, 127.0.0.1, wings_test,
entorno local. Se eliminaron 4 filas de sessions y 0 de personal_access_tokens,
dentro de una transaccion; ambas tablas quedaron en 0. No se tocaron passwords,
roles ni cuentas. No se hizo ninguna operacion contra el servidor.

Aceptacion real: se creo una base MariaDB separada wings_dump_audit_20260905,
se migraron tablas, se cargaron catalogos y dos usuarios sinteticos (ADMIN y
OPERATIVO), y DemoSeeder corrio completo con salida 0. database/dump.sql no existia
antes y siguio ausente despues. La base de auditoria se elimino al terminar.
No se corrio DemoSeeder sobre wings_test ni wings_testing.

Incidencia de preparacion, no ocultada: el primer intento uso una conexion nueva
para la auditoria, pero el constructor Schema conservaba la conexion mysql local.
La primera migracion intento CREATE TABLE users sobre wings_test y fue rechazada
porque la tabla ya existia; no llego a ejecutar el seeder. Se corrigio la prueba
cambiando la configuracion mysql solo en ese proceso, purgando su conexion y
verificando SELECT DATABASE() antes de migrar con --database explicito. El segundo
intento fue el que completo la aceptacion en la base aislada.

Verificaciones: git ls-files database/dump.sql vacio; git check-ignore lo reconoce;
busqueda mysqldump en database/ vacia; suite completa en MariaDB wings_testing:
**86 pruebas, 537 aserciones aprobadas**. php -l de DemoSeeder y del test: OK;
bash -n de los dos scripts: OK; view:cache y view:clear: OK; diff --check: OK.

No se editaron vistas, CSS ni JavaScript. El diff visual que ya existia al empezar
pertenece al saldo inicial de la tarea anterior y queda fuera de este commit,
junto con los cambios previos de TipoCajaWebController, NombreUnico y la
configuracion local de Claude. La suite completa incluye esos cambios locales;
este commit no los entrega ni cierra su revision visual.

Alcance pendiente: las versiones anteriores del dump siguen en el historial; no
se purgo Git, no se roto ninguna contraseña ni se revocaron sesiones de otras
bases/servidor. Las 4 sesiones invalidadas requieren iniciar sesion nuevamente.
Para seguir falta recibir el texto de la segunda tarea.

---

## 2026-09-05 — Codex CAB — saldo inicial implementado; pausa por cambio paralelo del motor de tests

Carlos autorizo reemplazar `LOWER(CONVERT(nombre USING utf8mb4))` por
`LOWER(nombre) = ?` en `NombreUnico`. Se aplico sin modificar la normalizacion de
entrada y se corrigio el comentario que negaba la equivalencia de acentos de la
colacion. **Limitacion:** SQLite no reproduce la insensibilidad a acentos de
`utf8mb4_unicode_ci`; el caso de acentos queda pendiente de verificacion en MariaDB
(B2), no se escribio un test que lo declare verde en SQLite. La comprobacion previa
de equivalencia sobre MariaDB fue aportada por Carlos, no ejecutada por Codex.

Se agrego regresion HTTP para duplicados ASCII por mayusculas (incluye la consulta
de disponibilidad y la exclusion del propio registro). El test de alta ahora envia
un POST valido, en vez de insertar mediante el modelo; el POST negativo incluye
nombre para atravesar la validacion real.

`TipoCajaWebController::update()` ahora excluye saldo_inicial antes de validarlo si
existe un movimiento operativo o de cashflow. La comprobacion se repite al guardar
dentro de una transaccion con bloqueo de la fila del tipo de caja. Sin movimientos
sigue permitiendo corregir un saldo no negativo. `edit()` usa el mismo criterio.
Un movimiento operativo cancelado tambien cuenta. No se modificaron los servicios
de movimientos ni se afirma haber probado concurrencia real.

Unica vista modificada: `tipos-caja/_form.blade.php`, autorizada por Carlos. Reutiliza
las clases del control monetario; con historial muestra el saldo persistido en
lectura, sin nombre de campo enviable ni valor proveniente de old(), y explica que
se ajusta con un movimiento. No se tocaron CSS, componentes ni otras vistas.

Regresion antes del arreglo del saldo, ya corregido NombreUnico: **2 fallas y 6
aprobadas sobre SQLite**. Ambas fallas mostraban en la base el saldo cambiado de
200000 a 999999 pese a existir movimientos. Despues del arreglo, la corrida dio
**7 aprobadas y 1 falla**, pero se detecto que **phpunit.xml habia cambiado en
paralelo**, sin intervencion de Codex: ahora configura mysql y la base wings_testing.
La falla es el helper previo `PDO::sqliteCreateFunction('YEAR', ...)`, exclusivo de
SQLite. Las pruebas de saldo y mayusculas aprobaron en esa corrida.

Se frenaron las corridas para coordinar con quien este ejecutando B2 y evitar
interferir sobre su base descartable. No se revirtio ni edito phpunit.xml. Pendientes:
coordinar el uso de wings_testing, adaptar el helper YEAR al motor, suite completa,
compilacion de vistas, revision visual y commit con Diseno-autorizado. Hay 85 metodos
de test; todavia no se declara la suite completa en verde. PHP lint de los cuatro
archivos PHP/Blade modificados: OK; diff de vistas/CSS: solo la vista autorizada;
diff --check: OK. No se hizo commit ni push. Se preservo settings.local.json.

---

## 2026-09-05 — Codex CAB — saldo inicial: freno antes de implementar

Objetivo: impedir editar el saldo inicial cuando exista cualquier movimiento
operativo o de cashflow, con la excepcion sin movimientos del contrato Caja-Cashflow
V4 §4.3. El pedido es coherente con el contrato. §4.4 queda fuera de implementacion:
los movimientos no economicos afectan saldo, pero deben excluirse del resultado
cuando se implementen esos reportes.

`git pull --ff-only`: sin novedades. Se leyeron AGENTS, CLAUDE, ambas bitacoras,
contrato, estado, checklist, evaluacion y reglas de diseno. Se instalo el hook de
diseno. No se modificaron controlador, modelo, vistas, CSS, tests ni datos locales.
Se preservo `.claude/settings.local.json`, que ya estaba modificado.

Obstaculo verificado para la regresion por la ruta web: `store()` y `update()` de
`TipoCajaWebController` usan `NombreUnico`. Su metodo `existe()` ejecuta
`LOWER(CONVERT(nombre USING utf8mb4))`, mientras `phpunit.xml` configura SQLite en
memoria. La reproduccion aislada de esa expresion en SQLite devuelve
`SQLSTATE[HY000]: General error: 1 near "USING": syntax error`. No se atribuye ese
error a MariaDB ni al cambio pedido, que todavia no se implemento.

La suite existente pasa: **81 pruebas, 343 aserciones**. Al leer el test de alta
actual se comprobo que crea el tipo mediante el modelo y que su POST negativo omite
`nombre`: no cubre un alta web valida que atraviese esa regla de unicidad.

Opciones pendientes de Carlos, sin elegir: adaptar `NombreUnico` para poder ejecutar
la regresion HTTP en SQLite, verificando que conserve la unicidad en MariaDB; o
preparar una base MariaDB exclusivamente descartable y una ejecucion de regresion
separada, sin usar ni limpiar `wings_test`. No se sustituira la validacion por un
mock para declarar cumplida la aceptacion. Se registro tambien en ESTADO-ACTUAL.
`git diff --stat -- resources/views resources/css`: vacio. Tarea sin cerrar;
pendientes implementacion y los dos escenarios de aceptacion.

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
