# Bitácora de Claude — entradas archivadas el 16/10/2026

Sacadas de `docs/00-estado/LOG-CLAUDE.md` para respetar el límite de 150 líneas. Sin cambios.

## 2026-10-12 — Claude CAB — T30 devuelto por sus pruebas; reloj del sitio de prueba en hora real

**Sitio de prueba:** nadie podia entrar. El reloj simulado, fijado el 10/10, se freno en la
medianoche del lunes 12 y el limite de 5 ingresos por minuto dejo de vencer; habia bloqueos
con 19 y 14 intentos acumulados. Por orden de Carlos volvi el reloj a la hora real
(`wings:fecha-simulada --real`) y limpie la cache del sitio de prueba. Sigue en `2e059ff`:
**no se desplego nada**. Defecto de la herramienta, sin tarea todavia: con el reloj frenado
el limite de intentos deja a todos afuera.
**T30 (Codex), vistas aprobadas por Carlos:** lei el formateador unico `FormatoImporte` y
busque restos: lo que queda sin centavos son porcentajes, DNI, cantidades y los graficos,
que Carlos dejo afuera. Sus 5 pruebas pasan, pero **solo en `wings_testing_codex`**: tienen
una condicion que las hace fallar en cualquier otra base. Las corri una vez en esa base para
verlas pasar; en la mia la suite completa da 664 aprobadas, 2 omitidas y esas 5 en rojo.
Devuelto a Codex solo por eso. T24 ya permite las cuatro bases descartables.
**T29** lo verifica Gemini. Carlos instalo Wings en su telefono y abre como aplicacion.

## 2026-10-12 — Claude CAB — ERROR MIO: borre una base local; T30 y T29 cerrados, suite verde

**El error.** Para comprobar que las pruebas de T30 rechazaban la base del club, corri una
con `DB_DATABASE=gestion_wings`. Las pruebas borran todas las tablas antes de ejecutar y el
rechazo corre despues: la base quedo vacia, sin respaldo. Ademas informe mal el dano: dije
que era la base de trabajo y que se perdian 70 alumnos. **La base de trabajo de CAB es
`wings_test` (lo dice `.env`) y esta intacta**; `gestion_wings` era una base vieja que la
aplicacion ya no usa. Carlos confirmo que no habia nada importante. La rearme: migraciones
completas y catalogos; sin usuarios, porque `wings:crear-admin` es interactivo.
**Regla que queda:** la suite se corre solo con `DB_DATABASE=wings_testing_claude`, nunca
contra otra base ni «para comprobar que la rechaza». Antes de decir que se perdio, leer `.env`.
**T30 cerrado.** Con permiso de Carlos corregi la condicion de `tests/Support/ImportesT30.php`:
ahora admite las cuatro bases descartables, como T24. **Suite completa en
`wings_testing_claude` sobre `ef12770`: 669 aprobadas, 2 omitidas, ningun rojo.**
**T29 cerrado por Gemini** (`ef12770`); sus 11 pruebas corren verdes en mi base.
Visto y no tocado: a `wings_test` le faltan 9 migraciones desde el 05/10.

## 2026-10-12 — Claude CAB — T27 verificado y cerrado; sitio de prueba desplegado en b2efc2e

**T27 (Codex), requisito de produccion:** el duenio edita o anula sus movimientos directos
desde Cashflow, con historial y asiento en contra. Lei servicio, rutas y migracion; sus 18
pruebas pasan en `wings_testing_claude`. Cerrado. Codex encontro y corrigio un error mio de
T26: un gasto directo del duenio salia como ingreso en el Historial. Observacion: cualquier
ADMIN corrige el directo de otro ADMIN; queda registrado quien.
**Suite completa sobre `b2efc2e`: 687 aprobadas, 2 omitidas.**
**Despliegue al sitio de prueba, por orden de Carlos:** `2e059ff` -> `b2efc2e`, a mano con el
usuario `wingstest` siguiendo los pasos de `scripts/deploy.sh`. Respaldo previo comprobado:
`/root/respaldos-manuales/wingstest_antes-del-despliegue_2026-10-12_2301.sql.gz` (46 tablas).
Tres migraciones, sin errores; composer y npm sin cambios, solo recompilar el diseno.
**Comprobado despues:** sin migraciones pendientes, fuera de mantenimiento, mismos datos
(100 alumnos, 4 pagos, 1 caja, 5 de cashflow, 7 usuarios), login 200, archivos de diseno
200, archivo de instalacion con arranque en el login y tipo correcto, cero errores en el log.
**No comprobado:** ninguna pantalla con sesion iniciada; no tengo claves del sitio de prueba.
Produccion no se toco.

