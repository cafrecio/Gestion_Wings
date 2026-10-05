# Wings — Bitácora activa de CLAUDE

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CLAUDE.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).
· [Entradas archivadas el 17/09](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE-2.md) · [Entradas archivadas el 21/09](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE-2.md) · [Entradas archivadas el 02/10](../99-archivo/bitacoras/2026-10-02/LOG-CLAUDE.md) · [Entradas archivadas el 05/10](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE-2.md)

## 2026-10-05 — Claude CyE — verificacion cruzada de la Entrega 2 (A17, A34, A3)

Verificada leyendo el codigo y probando, no el informe. **A17 y A3: correctos.** Las 8
pruebas del autor pasan y agregue tres propias en los bordes que su informe no cubre:
cobrar adelantado a quien debe meses viejos **pide confirmacion y motivo** —la regla vieja
sigue viva—, el adelantado no toca la deuda anterior, y la busqueda nueva del selector no
rompe el contador de deudores que arregle el mismo dia (A54).
**A34 no era exacto:** el enlace Recibo de cada pago ya existia desde ENT-05 (`04e3125`,
13/09) y estaba en la version auditada el 23/09; lo unico que cambio es el historial, de 8
a 12 pagos. Anotado en DEFECTOS para no heredar un hallazgo sin comprobar.
**La entrega estaba sin commitear** y mezclada en la misma carpeta con P1 de Codex: la
commitee yo. Codex subio P1 mientras tanto (`d530c85`).
Suite completa: 415 pruebas / 2862 aserciones, verde. Sin deploy.

## 2026-10-05 — Claude CyE — el acceso al servidor deja de arreglarse a mano

Carlos pidio chequear si produccion tiene alumnos cargados y **no se pudo**: `ssh vps`
rechaza la clave de CyE aunque sigue publicada en su GitHub y el alias esta bien armado
(probadas las dos claves locales). Es la segunda vez: el 22/09 solo entraba CAB. La causa
de fondo es que el acceso vivia en un archivo del servidor escrito a mano, que un deploy o
una restauracion pisa sin que nadie se entere hasta que un agente lo necesita.
Hecho: `scripts/servidor/acceso-agentes.sh` rearma `/root/.ssh/authorized_keys` cada 15
minutos desde `https://github.com/cafrecio.keys`, conservando las claves propias del
servidor, descartando respuestas vacias o invalidas y avisando por Telegram si queda roto.
Probado en seco en local: sincroniza, conserva la clave ajena y `--verificar` detecta la
falta del cron. **Falta un unico paso con root** (comando en CHECKLIST §1.6); desde ahi una
maquina nueva entra solo cargando su clave en GitHub.
Pendiente que sigue: **nadie verifico todavia si produccion tiene alumnos**.
Dato que conviene saber: el repositorio es **publico** — por eso el comando de instalacion
funciona con `curl` sin credenciales. Ver si eso esta bien es decision de Carlos.

## 2026-10-05 — Claude CyE — A54 y A55: un solo calculo de cuanto debe un alumno

Tercera vez que el mismo problema de raiz daba defectos distintos: cada pantalla calculaba
el saldo por su cuenta. Ahora `CobranzaEstadoService::saldoDeAlumnos()` es el unico calculo
—cuotas impagas mas inscripcion pendiente— y lo usan Cobranza, la ficha y el selector de
cobro; `listadoCobranza` quedo encima de ese metodo en vez de repetirlo.
**Colision que encontro la suite, no una lectura:** filtrar el selector por "saldo > 0"
rompia el cobro adelantado de Gemini (A3), porque al que no debe nada no se le podia cobrar
el mes. Criterio final: la lista ofrece a quien tiene **algo por cobrar** —saldo pendiente o
el mes en curso sin generar— y el contador del encabezado cuenta solo a los que deben, que
es lo que decia mal A54. `DeudaCuota::saldo_pendiente` ahora tambien resta lo condonado.
Lo que **no** era: el `monto_condonado` de cuotas no lo escribe nadie (condonar usa el
estado), asi que esa no era la causa; se dejo alineado igual.
`SaldoUnicoPorAlumnoTest` 4 pruebas, las 4 fallan con el codigo anterior. Suite 384/2261.
Pendiente: lo verifica Codex o Gemini. Sin deploy.

## 2026-09-26 — Claude CyE — se cambia el enfoque de la primera carga

Carlos corto la discusion de A43: se estaban gastando horas en una excepcion. **La primera
carga pasa a ser un Excel unico con los alumnos completos y su deuda**; lo que se carga a
mano despues sigue las reglas del sistema, sin excepciones, y **se saca la fecha de corte**.
Los dos importadores actuales se retiran: cargan deuda de alumnos que ya tienen que estar
cargados a mano. El importador no crea catalogos: un grupo o plan inexistente se rechaza,
porque sin precio las cuotas salen mal. El Excel va a venir con errores, asi que: plantilla
generada por el sistema con desplegables, revision que no escribe nada y devuelve todos los
errores juntos, informe que vuelve como Excel marcado, ensayo en test y manual de primera
carga. Decidido y escrito en `docs/05-pendientes/PRIMERA-CARGA-EXCEL.md`; **nada se programa
antes de que Carlos apruebe la maqueta** de la pantalla de cuatro pasos.
Pendiente que Claude senialo como lo mas importante: **no existe forma de cargar una deuda a
mano**, solo condonar; con eso las excepciones se resuelven sin reglas nuevas.
Las 5 pruebas de A43 quedan en rojo y se reescriben con la regla que salga de esto: no son
criterio vigente. El prompt para Codex de A43 **no se paso**.

## 2026-09-23 — Claude CAB — verificacion cruzada de A2/B2 y hallazgo A43

**Que se verifico.** La implementacion de Codex (`b3619bf`) de las dos enmiendas al contrato
de cobranza: la cuota nace en el alta con el importe congelado, y DEUDOR deja de depender de
"nunca pago". Verificado leyendo el codigo, no el informe: la regla de estado quedo solo
sobre deuda anterior impaga y se sacaron tambien las tres consultas de "tiene pagos" que
alimentaban los listados masivos, asi que pantalla y listado deciden igual. El descuento no
se aplica dos veces: si la deuda trae `porcentaje_alta`, el cobro lo respeta y no recalcula.
Suite corrida por mi: **331 pruebas / 1900 aserciones**, verde. Todo subido a GitHub.

**Defecto encontrado en la verificacion — A43.** `crearCuotaAlta` crea la cuota del **mes en
curso** pero calcula el porcentaje con **el dia de la fecha de ingreso**, que puede ser de
hace anios: un alumno que entro el 20/01/2020 y se carga hoy debe el **65% de septiembre**,
por un mes que uso entero. Esta fijado en
`CuotaAltaEstadoTest::test_porcentaje_configurable_dia_de_ingreso_y_solo_mes_actual`, que lo
declara correcto, asi que hay que decidir la regla antes de tocarlo. Propuesta: el
porcentaje sale del dia de ingreso **solo si el ingreso es de este mes**; si es anterior, la
cuota va completa.

**Segundo efecto del mismo cambio.** Cargar a mano un alumno antiguo ahora le crea deuda del
mes en curso, y despues la importacion del padron **rechaza el archivo entero** porque ya
existe deuda de ese periodo. Los dos caminos de carga inicial chocan.

**Estado.** A2 y B2 implementados y verificados; **A43 abierto, esperando decision de
Carlos**. Registrado en `docs/06-pruebas/PRU-02/DEFECTOS.md`.

## 2026-09-23 — Claude CAB — decisiones del club para PRU-02 y visibilidad de movimientos

**Decisiones de Carlos, para no volver a preguntarlas.** El club **abre a las 16**; la
manana es de Vanina sola, desde su casa: no mira la caja en vivo, controla y valida al dia
siguiente. **Vanina tambien es profesora** y puede cargarse costo hora $0; profesores y
usuarios son tablas sin relacion, asi que no hace falta nada nuevo. El efectivo que cobra
ella es **orden fisico del club, no del sistema**: ve su propio registro y ahi no nos
metemos. Las canchas ya estan relevadas en `PLAN-CANCHAS-LIQUIDACIONES-v2026-09-21.md`
(tarifa por hora, bloques completos del reloj): no volver a preguntar horarios ni precios.

**Error repetido, ahora corregido en codigo.** Volvi a plantear como duda algo que el
contrato ya resolvia: **las cuotas cobradas las ven todos los operativos**. `/movimientos`
filtraba por caja propia, asi que si la madre pagaba en el turno de Pablo, Sandra al dia
siguiente no encontraba el cobro y quedaba sin saber que decirle. Ahora filtra por rubro,
como manda `PERMISOS-ROLES.md`: el criterio es el subrubro, nunca quien lo registro. Y la
otra mitad de la regla, dicha por Carlos: **lo que el operativo no puede cargar, tampoco lo
ve**; `permitido_para` gobierna las dos cosas. Cubierto por
`MovimientosVisibilidadPorRubroTest`, rojo contra el codigo anterior. Contrato de cobranza
actualizado: esa fila decia "no deberia" desde hace semanas.

**Pendiente de definicion.** Las clases particulares son habituales en el club y hoy no
tienen forma de gestionarse (POS-06 sin implementar): hay que encontrarles una salida con
lo que existe antes de la semana 1.

**Suite:** 321 pruebas / 1827 aserciones.

## 2026-09-22 — Claude CAB — acceso de CyE al servidor y primer ensayo real de restauracion

**Acceso.** El servidor solo tenia autorizadas las claves de CAB y la contrasena esta
apagada, por eso desde CyE nadie podia entrar. Se autorizo la clave que Carlos cargo en
GitHub (`cafre@CyE-github`, bajada de `github.com/cafrecio.keys`) con su autorizacion
expresa. Me equivoque antes armando un camino de pendrive cuando la clave ya estaba en
GitHub: le hice perder una hora. Esa clave generada en CAB se retiro del servidor y se
borro. `ssh vps` no pide permiso (`.claude/settings.json`) y si en una maquina no entra
lo arregla el agente con `scripts/maquina/instalar-acceso-servidor.ps1`.

**Ensayo de restauracion real (SEG-06/07)**, respaldo del 22/09 03:15: el respaldo se
restaura completo. El ensayo marco dos fallas **del script, no del respaldo**: sumaba
`saldo_pendiente`, que no es columna (lo calcula el modelo), y contaba `sessions`, que
cambia sola. Corregidas; prueba nueva que corre cada consulta del script contra el
esquema real (con la vieja falla con el mismo error que el servidor). Repetido:
**correcto** en 28 tablas, importes, archivos y configuracion. Limite anotado en SEG-07:
cuando el club opere, la comparacion contra la base viva de dia va a diferir por cobros
posteriores; falta guardar un manifiesto en el respaldo. Suite 290/1675.

**SEG-12 registrada** a pedido de Carlos: renovar credenciales del servidor y guardarlas
juntas, cambiar un nombre de usuario (falta que diga cual) y acceso directo al panel,
que es CWP, no cPanel.

## 2026-09-21 — Claude CyE — FIN-09 tambien en el pago de liquidaciones

Carlos aprobo aplicar la regla de fechas al pago de liquidaciones, la unica via de pantalla
que aceptaba cualquier fecha. Futura rechazada; mes anterior pide confirmar con el mismo
aviso de Caja (copiado tal cual, tokens del design system) y avisa al ADMIN. Boton
"Confirmar" solo en ese caso. `PagoLiquidacionFechaTest` 4 pruebas, 3 fallan con el codigo
anterior. La regla no estaba en ningun contrato: escrita en Caja-Cashflow V4 §3.6.
Suite 288/1656. Acceso al servidor desde CyE: `~/.ssh` sin cambios desde 2025 salvo la
llave `id_ed25519_wings_cab` (08/09, comentario de Codex); `known_hosts` solo GitHub.
