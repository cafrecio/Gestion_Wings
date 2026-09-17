## 2026-09-13 — Claude CAB — SEG-06: la restauracion reponia solo la base

El respaldo nocturno guarda tres piezas —`wings.sql`, `storage.tgz` y `env.txt`—
y `restaurar.sh EN-SERIO` reponia unicamente la primera. `storage.tgz` se
descomprimia en el directorio temporal y el `trap limpiar EXIT` lo borraba: un
servidor perdido se reponia **sin los recibos emitidos**, que se venian
respaldando todas las noches para nada.

El ensayo tampoco cumplia su criterio. Comparaba una lista escrita a mano de 15
tablas sobre 35, sin `pago_deuda_cuota` —donde vive que periodos cubre cada
pago—, ni liquidaciones, ni asistencias, ni `alumno_planes`; y una tabla ausente
en las dos bases devolvia `?` de los dos lados y contaba como coincidencia.
Nunca miraba archivos ni configuracion.

Ahora enumera las tablas desde las dos bases, trata una tabla faltante como
fallo, compara los archivos y exige `APP_KEY` (sin esa linea Laravel no arranca:
restaurar eso no repone un sistema usable). `EN-SERIO` repone los archivos,
aparta los anteriores con fecha y deja el `.env` del respaldo al lado sin pisar
el vivo, que puede tener claves rotadas despues.

Prueba: `tests/Deployment/restaurar_repone_todo_test.sh`, 11 comprobaciones.
Dientes verificados: con el script anterior fallan 5, incluida
`EN-SERIO repone los recibos del respaldo (esperado 2, obtenido 0)`.
**Falta el ensayo real contra un respaldo del servidor**; esto se probo con
paquetes cifrados armados en la maquina. Sin deploy.

