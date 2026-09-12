# Wings — Bitácora activa de CLAUDE

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CLAUDE.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).

## 2026-09-11 — extracto documental de Claude CAB — recibos y decisiones

El pase registra aprobación visual del diseño de Gemini por Vanina.
Pendiente decidir por clase o por duración antes de implementar liquidación/recibo.
El instructivo requiere datos que no se obtienen copiando la plantilla sin adaptación.
También pendiente significado de monto_base y despliegue.
Las pausas antiguas COB-09/FIN-02 tienen evidencia posterior en el resumen común.

## 2026-09-12 — Claude CyE — SEG-02 y SEG-03

### SEG-02 · cambiar la contraseña revoca lo anterior

Una clave se cambia justamente cuando se filtro o la tiene quien ya no deberia. Antes,
cambiarla **no echaba a nadie**: la sesion abierta seguia viva —`SESSION_DRIVER=database`,
las filas estan en `sessions`— y si la persona habia tildado "Recordarme", su cookie lo
volvia a autenticar **cinco años** con una contraseña que ya no existe.

Se revocan las dos puertas: se borran las filas de `sessions` de ese usuario y se rota su
`remember_token`. Si alguien se cambia la clave a si mismo, se lo vuelve a autenticar con
sesion nueva — seria absurdo echarlo por cambiar su propia contraseña.

Desactivar la cuenta y bajarle el rol ya funcionaban; el agujero era especifico del cambio
de contraseña.

### SEG-03 · un solo minimo de contraseña

Era **8 por pantalla y 12 por consola**: la regla mas fuerte estaba en el camino que casi
no se usa, asi que un ADMIN creado desde `/usuarios/create` podia quedar con `12345678`.
Unificado en **12** en `UsuarioWebController::MINIMO_CONTRASENA`; `CrearAdminCommand` y
`_form.blade.php` lo toman de ahi, no repiten el numero.

### Pruebas — 8, con dientes comprobados

Quitada la revocacion, las **dos** que cubren SEG-02 se ponen en rojo y las otras seis
siguen verdes: no es un `abort` general.

**Correccion propia durante el trabajo:** las dos pruebas del minimo estaban escritas
contra la constante, asi que verificaban coherencia y **no el valor** — con el minimo en
4 tambien pasaban. Se agrego `test_el_minimo_no_baja_de_doce`, que fija el piso.

**Error de la primera version:** usaban un OPERATIVO sin crear el rubro `Sueldos`, y el
update moria antes de llegar a lo que se probaba. El helper lo crea.

### Lo que rompi, y por que no importa

Corri `migrate:fresh --env=testing` creyendo que apuntaba a la base de pruebas.
**En este proyecto ese parametro no apunta ahi:** no existe `.env.testing`, asi que
Laravel uso el `.env` normal, que apunta a `wings_test`. Se perdieron los 60 alumnos, las
95 deudas y los 60 pagos de apertura de la base **local**.

El servidor no se toco. Carlos confirmo que eran datos de prueba y que la prueba humana
se rehace maniana, asi que no se reconstruye.

**Para que no vuelva a pasar:** el nombre de la base de pruebas vive **solo** en
`phpunit.xml`, que aplica cuando corre PHPUnit y no cuando se ejecuta artisan a mano. La
forma segura es `DB_DATABASE=wings_testing php artisan ...`, explicita.

---

## 2026-09-12 — Claude CyE — los dos tableros ya no pueden divergir

Decision de Carlos: el indice y el avance tienen que ser identicos en los dos tableros;
el detalle puede diferir, porque el de los agentes lleva criterios y evidencia.

`TablerosNoDivergenTest` compara `PLAN-TRABAJO-CARLOS-v2026-09-08.html` contra
`PLAN-TRABAJO-IA-v2026-09-08.md` y pone la suite en rojo si:

1. una tarea existe en un tablero y no en el otro;
2. el plan declara una tarea cerrada y la casilla de Carlos sigue vacia.

`PAUSADA` **no** cuenta como cierre: una tarea pausada sigue abierta y su casilla tiene
que quedar vacia. Es el caso de FDS-03, que la primera version de la prueba marcaba mal.

**Hallazgo al escribirla:** `ENT-09` —la carga del saldo inicial del padron— existia
**solo en el tablero de Carlos**. Un agente que buscara ese ID no lo encontraba. Agregada
al plan de los agentes.

**Dientes comprobados, con los dos errores reales que ya habian pasado:** destildada
SEG-01, la prueba falla nombrandola; borrada ENT-09 del plan, falla nombrandola. Las dos
restauradas, verde.

Suite **171 pruebas, 1074 aserciones**. `DocumentacionNoMienteTest` salto por el cambio
de numero y se actualizaron los tres documentos que lo declaran.

---

## 2026-09-12 — Claude CyE — verificacion de ENT-04 (Gemini) y SEG-01

### ENT-04 — el reporte de Gemini es exacto

Verificado contra el codigo, no contra el reporte. `0d68f2c`:

| Reportado | Verificado |
|---|---|
| Suite 169/1065 | 169 pruebas, 1065 aserciones |
| CSP sin crecer | `BLOQUES_SCRIPT_PERMITIDOS = 26` y `MANEJADORES_PERMITIDOS = 24`, intactas |
| JS externo | Delegacion a nivel `document` en `ds-app.js`; cero `onclick`, cero `<script>` nuevo |
| Un ojo por campo | Dos botones con su propio `data-target` |
| Los dos tableros | `.md` y `.html`, este ultimo con `checked` |
| Hook de diseno | `Diseno-autorizado:` presente en el mensaje |

**Lo que el reporte no menciona y era lo unico que podia fallar en silencio:** que ese
JavaScript llegue a la pantalla. Cadena completa verificada — `app.js` importa
`./ds-app`, el layout carga `app.js`, y `public/build/assets/*.js` contiene
`btn-toggle-password`. El boton funciona, no solo existe en el fuente.

**No verificado:** el clic real en navegador. Es de pantalla y le toca a Codex.

Su decision de poner un ojo por campo en lugar de uno compartido es correcta, y el
motivo que da es bueno: cuando salta "las contraseñas no coinciden" hay que poder ver
las dos para compararlas.

### SEG-01 — el tablero de Carlos habia quedado atras

Estaba VERIFICADA en el plan desde el 11/09 y **sin tildar** en el HTML, con el texto
viejo que todavia pedia revisar los once avisos. Verificado antes de tildarlo:
`npm audit` en cero, `axios` fuera de `package.json` y sin una sola referencia en
`resources/js/`.

**Es la segunda vez en dos dias que el tablero de Carlos queda atras del plan** — el
11/09 me paso a mi con FIN-03 y FIN-05, hoy con SEG-01. Comparados los dos tableros
enteros, SEG-01 era el unico pendiente. FIN-07 la cerro bien en los dos.

Son dos archivos y mantenerlos iguales depende de que cada agente se acuerde. Carlos lo
detecto las dos veces mirando la pantalla, que es el peor lugar para que aparezca.
**Propuesta sin aplicar:** una prueba que compare ambos tableros y ponga la suite en
rojo si divergen, como `DocumentacionNoMienteTest` hace con el numero de pruebas.

---

## 2026-09-12 — Claude CyE — SEG-04: el preflight corre antes de publicar

**Que pasaba.** `scripts/deploy.sh` corria `artisan up` y **despues**
`wings:preflight`. El sitio se abria al publico y recien entonces se controlaba el
entorno: un release con `APP_DEBUG=true` quedaba en internet mostrando credenciales de
base en cada error hasta que el control terminaba y abortaba.

**Cambio:** dos lineas invertidas. El preflight queda despues de `config:cache` —lee la
misma configuracion que va a usar la aplicacion— y antes de `artisan up`. Un preflight en
rojo dispara el rollback con el sitio todavia cerrado.

**Prueba propia:** `tests/Deployment/deploy_preflight_antes_de_publicar_test.sh`. Arma
repositorio y binarios falsos como la de rollback, hace fallar el preflight con codigo 77
y exige que **no exista ningun `artisan up` con la version nueva**. El `php82` falso
registra que version habia en el repositorio en cada `up`, que es lo que permite
distinguir el que publica del que hace el rollback.

**Dientes comprobados:** devuelto el orden viejo, la prueba falla con
"el sitio se publico con la version nueva pese al preflight en rojo". Restaurado, pasa.
La prueba de rollback existente sigue pasando. Suite 166/1026.

**Falta desplegar**, como todo lo de esta semana: el servidor sigue en `81f27ef`.

---

## 2026-09-12 — Claude CyE — FIN-03 verificada y la red de FIN-05 retirada

**Puesta al dia en CyE:** `git pull` a `5e8070e`, `npm install` (audit en cero), migracion
`2026_09_11_180000_add_detalle_anulacion_to_pagos_table` aplicada a `wings_test`, guardia
de diseno instalada. Suite **166 pruebas / 1026 aserciones**, igual al corte documentado.

**Servidor verificado por SSH: sigue en `81f27ef`.** 39 commits sin desplegar, incluidas
las cuatro correcciones de cobros, FIN-03 y FIN-05. La base del servidor es la **real y
definitiva**: cualquier cambio de estructura va antes de que Vanina cargue.

### FIN-03 — revision cruzada hecha, pasa

Leido el cuerpo, no el tablero. El detalle se guarda **antes** de borrar las
imputaciones y en la **misma transaccion** que revierte el cobro; el `save()` del pago
viene despues de asignarlo. El recibo lo lee de ahi.

Tres pruebas propias en `ReciboMedioDePagoTest`, incluida
`test_anulacion_antigua_sin_detalle_no_inventa_periodos`, que cubre el borde correcto.

Limitacion real, no defecto: las anulaciones anteriores al 11/09 quedan sin detalle. En
`wings_test` hay **cero** pagos anulados y en el servidor **cero** pagos. No afecta a
nadie. **Falta desplegar la migracion.**

### FIN-05 — la red de seguridad en la base se retira

Yo mismo la habia recomendado y quedo ofrecida a Carlos en `CHECKLIST-CARLOS.md` como
"esperando tu si". **La verifique antes de que la aprobara y rompe la validacion de
cajas.**

`CashflowIntegracionCajaService::reflejarCajaEnCashflow()` linea 53 crea **un asiento por
cada movimiento** de la caja, todos con el mismo `referencia_tipo = CAJA_OPERATIVA` y el
mismo `referencia_id`. Una caja con diez cobros son diez filas con la misma referencia:
un indice unico sobre ese par haria fallar **toda caja con mas de un movimiento**.

La version correcta exige columna generada mas indice, sobre la tabla de plata de la base
definitiva. **Recomendacion corregida: no hacerlo.** El `lockForUpdate()` ya resuelve el
caso real, probado con dos conexiones reales — no un `grep`, como si lo era
`MoneyLockingTest`.

**Lo que este turno deja aprendido:** verifique las dos cosas y las conte en el chat sin
escribirlas. Carlos lo marco. Un hallazgo que corrige una recomendacion mia y vive solo en
el chat es peor que no tenerlo: el documento sigue invitando a aprobar lo que rompe.

---

## 2026-09-11 — extracto documental de Claude CAB — FIN-05 y SEG-01

Se registra corrección de pago concurrente de liquidación y prueba con dos conexiones.
El barrido deja recálculo frente a cierre para FIN-11; no darlo por resuelto.
Claude registra verificación de SEG-01 de Codex; no es un audit nuevo del 12/09.
Fuente íntegra: encabezados FIN-05 y Verificación de SEG-01 del archivo histórico.
