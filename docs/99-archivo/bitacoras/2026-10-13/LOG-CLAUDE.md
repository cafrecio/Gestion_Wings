# Wings — Bitácora de CLAUDE — entradas archivadas el 13/10/2026

Sacadas de `docs/00-estado/LOG-CLAUDE.md` para mantenerla dentro del límite. No se modificaron.

## 2026-10-12 — Claude CAB — T28 cerrado en segunda vuelta; T11 verificado en codigo

**T28:** Carlos decidio que el cobro es de quien lo hizo y que quien valido se muestra aparte.
Gemini lo rehizo en `e1e73d8`: cada renglon de Cashflow guarda de que movimiento viene
(columna y migracion nuevas), se saco el segundo intento por importe y rubro, y hay una
columna «Val.» con iniciales. Verificado por mi: sus 10 pruebas y mis 2 pasan en
`wings_testing_claude`. Cerrado. Observacion que pasa a T22: `User::iniciales()` hace una
consulta por renglon. Las capturas de celular estan a 375 y el telefono de Carlos mide 360.
**T11:** el archivo de instalacion ahora declara el arranque en el login; 3 pruebas verdes.
Gemini informo que el servidor es Nginx y que habia que tocarlo a mano: **es Apache**
(comprobado con `ssh vps`, solo lectura), asi que su regla en `.htaccess` alcanza. La prueba
real es de Carlos en su telefono, despues de desplegar.
Sin suite completa en este paso. Nada de lo de ayer y hoy esta desplegado.

## 2026-10-12 — Claude CAB — T28 verificado, con dos observaciones para Carlos

Gemini lo hizo en `2d215d2` y `1787dfd`; Carlos eligio el diseno sobre capturas (tabla
rebalanceada en escritorio, tarjetas en celular). Verificado por mi: sus 5 pruebas pasan y
agregue dos propias en `evidencia-t28/VerificacionT28ClaudeTest.php` (40 cobros del mismo
importe en una caja, dos paginas y filtro por medio de pago): cada renglon muestra a su alumno.
No lo cerre. **Observaciones:** (1) Cashflow no guarda de que movimiento viene cada renglon;
lo deduce comparando importe, rubro y observacion. Anda porque la observacion del cobro trae
el numero de alumno. Si no coincide hace un segundo intento solo por importe y rubro, que
puede mostrar a otro alumno. (2) `reflejarCajaEnCashflow` ahora guarda a la operativa en
`usuario_admin_id` en las copias nuevas; las viejas tienen al admin que valido. La pantalla
no lo necesita, porque resuelve el nombre al mostrar. Ojo en T27, si «movimientos del dueño»
se decide por ese campo. Sin suite completa: Codex tiene T30 a medio hacer en la carpeta.
**T27** obligatoria antes de produccion (Carlos); **T11** instalar en el telefono, a Gemini.

## 2026-10-12 — Claude CAB — T29 hecho; T24 y T26 cerrados; T28 y T30 repartidos

**T29, mio:** el buscador de Cobrar anunciaba 40 con deuda y listaba 100 (dia 1 de la
prueba). Sin buscar ahora trae solo a los que deben, los mismos del encabezado; a cualquier
otro se llega buscandolo. El que no debe sale con punto verde. `BuscadorCobrarSoloDeudoresTest`,
7 pruebas: 5 fallan con el codigo anterior, 2 son de resguardo. Cambie 3 pruebas viejas que
exigian el comportamiento anterior; una cubria al alumno sin la cuota del mes generada, que
ahora se encuentra buscando. Carlos aprobo las capturas («Ok las imagenes»).
Suite completa en `wings_testing_claude`: 647 aprobadas, 2 omitidas y en rojo solo el
guardian de documentacion, que pedia 650; el numero se corrigio en este commit y la cuenta
da 650. No volvi a correr el guardian: en la carpeta hay 5 pruebas de Gemini sin publicar.
**T24 cerrado:** regla de importes verificada en servidor y navegador, 6 pruebas verdes.
Tres avisos a Carlos: `48.000` termina guardado como 48.00 (decision suya); 45 lugares
muestran importes sin centavos (pasa a T30, Codex); dos formatos en pantalla.
**T26 cerrado por Gemini**, sus 8 pruebas corren verdes en mi base.
**T28** Cashflow con alumno y quien cobro: Gemini. Sin desplegar nada de esto.

## 2026-10-12 — Claude CAB — T23 verificado y cerrado

Gemini lo entrego en `03aaf11`; Carlos aprobo las capturas. Verificado por mi leyendo el
codigo y corriendo sus 8 pruebas en `wings_testing_claude` (41 aserciones, verdes): aviso
solo por encima del 50% y con cierre anterior mayor a cero, confirmacion para seguir,
rectificacion con motivo solo en caja ABIERTA, por el operativo del turno o el ADMIN, y
esperado recalculado. No corri la suite completa hoy; ayer dio 641/2 con T23 adentro.
**Observaciones, no bloquean:** una segunda rectificacion pisa el valor anterior, el motivo y
el autor de la primera, asi que el importe declarado al abrir se pierde; rectificar no avisa
si el nuevo valor es disparatado. Las cuatro cosas que Carlos no habia decidido quedaron
escritas en el contrato V5 como «acordadas» con Gemini: no tengo como comprobar ese acuerdo.
**Regla 10:** ese commit se llevo cambios sin publicar de Codex (T24) en
`CajaWebController.php`, que usan una clase (`ImportePesos`) que recien entro en `f62f1f2`.
Entre los dos commits lo publicado estaba incompleto; no probe que fallaba en ese estado.
Sin desplegar.
