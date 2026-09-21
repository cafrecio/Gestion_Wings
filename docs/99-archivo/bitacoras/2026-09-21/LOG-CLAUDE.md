## 2026-09-13 — Claude CAB — la CSP estaba en modo reporte sin recolectar nada

`SecurityHeaders` mandaba `Content-Security-Policy-Report-Only` **sin la
directiva `report-uri`**: el navegador escribia cada aviso en su propia consola,
en la maquina de cada usuario, y ahi se perdia. La politica estaba "escuchando"
sin recibir un solo aviso, y sin esa lista no hay forma de saber que se rompe al
pasarla a modo bloqueo — que es adonde apunta todo SEG-11.

Agregado `report-uri /csp-reporte` y el endpoint que lo recibe. Es publico por
necesidad: el navegador lo llama sin sesion y sin token CSRF, asi funciona el
mecanismo. De ahi los limites: 60 por minuto y por direccion, cuerpo de 8 KB,
y solo se guardan cinco campos. **`script-sample` queda fuera a proposito**:
trae un recorte del codigo de la pantalla, que puede incluir el nombre o el DNI
de un alumno. Se descartan tambien los avisos de extensiones del navegador
(`chrome-extension:` y companiia), que son la mayor fuente de ruido de cualquier
CSP y no se arreglan desde el sistema.

Los avisos van a `storage/logs/csp.log`, en canal aparte: son muchos y repetidos
y en `laravel.log` taparian los errores de verdad.

`scripts/servidor/resumen-csp.sh` agrupa por directiva+archivo+linea, avisa solo
lo que no se aviso antes y usa `enviar_telegram()` de `monitoreo-common.sh` tal
cual: mismo robot, mismo chat, mismos secretos fuera del repo. **No se manda un
mensaje por violacion**: la CSP genera uno por cada carga de pagina y por cada
usuario, y Telegram cortaria por limite de envios.

El resumen queda registrado en el scheduler (`routes/console.php`, lunes 07:00),
que ya corre cada minuto en el servidor: **no se agrega ningun cron nuevo ni
queda un paso manual**. Un cron que hay que instalar a mano es un cron que en
algun servidor no esta.

8 pruebas nuevas, incluida la que comprueba que no se filtre el recorte de la
pantalla. `SecurityHeadersTest` actualizado: fijaba la politica literal.
Suite: 222 pruebas / 1343 aserciones. Sin deploy.

## 2026-09-13 — Claude CAB — SEG-07: el ensayo compara plata, no cantidad de filas

Un volcado puede restaurar la misma cantidad de filas con los importes mal, y el
ensayo de SEG-06 lo daba por correcto. Ahora compara importes entre la base viva
y la copia: pagos por estado, imputaciones, deuda pendiente y cobrada,
movimientos activos, cashflow y liquidaciones con su detalle.

Suma cuatro invariantes —deudas cuyo `monto_pagado` no coincide con la suma de
sus imputaciones, imputaciones huerfanas, asientos de cashflow que apuntan a una
caja inexistente y liquidaciones descuadradas contra su detalle— y aca esta la
decision que importa: **se corren en las dos bases y se comparan entre si**. Si
la base viva ya tiene tres deudas descuadradas y la copia tiene las mismas tres,
el respaldo copio fielmente y el ensayo pasa, avisando que hay un problema de
**datos**. Mezclarlo con el resultado del respaldo haria que un defecto viejo se
reporte como respaldo roto y que nadie confie en la herramienta.

Prueba extendida a 18 comprobaciones. Dientes: con la version de SEG-06, el caso
`mismo conteo con importes truncados` **pasa en verde**.
**Falta el ensayo real contra un respaldo del servidor.** Sin deploy.
