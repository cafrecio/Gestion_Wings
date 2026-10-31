# Wings — Bitácora activa de CLAUDE

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CLAUDE.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).
· [Entradas archivadas el 17/09](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE-2.md) · [Entradas archivadas el 21/09](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE-2.md) · [Entradas archivadas el 02/10](../99-archivo/bitacoras/2026-10-02/LOG-CLAUDE.md) · [Entradas archivadas el 10/10](../99-archivo/bitacoras/2026-10-10/LOG-CLAUDE.md) · [Entradas archivadas el 13/10](../99-archivo/bitacoras/2026-10-13/LOG-CLAUDE.md) · [Entradas archivadas el 05/10](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE-2.md) · [Entradas archivadas el 07/10](../99-archivo/bitacoras/2026-10-07/LOG-CLAUDE.md) · [Entradas archivadas el 08/10](../99-archivo/bitacoras/2026-10-08/LOG-CLAUDE.md) · [Entradas archivadas el 12/10](../99-archivo/bitacoras/2026-10-12/LOG-CLAUDE.md) · [Entradas archivadas el 16/10](../99-archivo/bitacoras/2026-10-16/LOG-CLAUDE.md) · [Entradas archivadas el 19/10](../99-archivo/bitacoras/2026-10-19/LOG-CLAUDE.md) · [Entradas archivadas el 21/10](../99-archivo/bitacoras/2026-10-21/LOG-CLAUDE.md) · [Entradas archivadas el 20/10](../99-archivo/bitacoras/2026-10-20/LOG-CLAUDE.md) · [Entradas archivadas el 27/10](../99-archivo/bitacoras/2026-10-27/LOG-CLAUDE.md) · [Entradas archivadas el 31/10](../99-archivo/bitacoras/2026-10-31/LOG-CLAUDE.md)

## 2026-10-31 — Claude CAB — T96: botón Configurar en Caja

- **Hecho:** en Caja, el dueño ve **Configurar** al lado de **Abrir**; con una caja abierta queda inhabilitado. La regla ya estaba (`CajaService::configurarMostrador` rechaza el cambio): faltaba el acceso. Texto de la fila acortado a «Cajón del mostrador» para que no ocupe 5 renglones en celular.
- **Aspecto:** Carlos dio el OK con capturas (`APROBAR-T96.html`). Manual `configurar-mostrador` al día (ya no dice «consultá a soporte»). 4 pruebas nuevas (1320). Suite rápida 1177 aprobadas, 6 omitidas, 1 fallida (contador de `CHECKLIST-CARLOS.md`); corregido y repetida solo esa prueba.
- **Errores míos:** toqué la vista antes de mostrar capturas, y Carlos me frenó. Mis pruebas buscaban `>Configurar` pegado; la del operativo pasaba sin controlar nada. Corregidas. T95 había dejado la ficha de profesores diciendo que guardaba sin tarifa: corregida, y el generador de fichas vuelve a correr.
- **Base local de CAB (`wings_test`):** pedido de Carlos, al único usuario le cambié correo y clave para que entre.
- **Sigue:** control de Gemini, [prompt](../06-pruebas/PRU-04/PROMPT-GEMINI-T96.md). Sin desplegar. Carlos: los huecos se ven uno a uno; terminar y controlar cada uno antes del siguiente.

## 2026-10-30 — Claude CyE — Cierre: huecos de la etapa C, qué quedó hecho y qué sigue

- Carlos pidió resolver los huecos del manual uno por uno, sin respuestas largas y sin desplegar: Cora trabaja en el sitio.
- **Hecho:** T98 cerrada (sin control de otra IA, decisión de Carlos). T99 cerrada con su OK por pantalla; además, aviso real en el formulario de grupo, que se va al corregir, y Nuevo arriba con línea divisoria. T103 cerrada sin cambios (decisión de Carlos). T95 hecha, `a_verificar`: falta que Carlos la pruebe por pantalla.
- **Base local de CyE** (`gestion_wings`): estaba atrasada y el Inicio daba error; apliqué las migraciones pendientes en mantenimiento, con respaldo previo en el scratchpad de la sesión. No se tocó el servidor.
- **Errores míos:** en T99 probé el aviso en la sesión y no en pantalla; Carlos vio otro texto. Un commit subió con una prueba en rojo porque el comando no se frenaba ante el fallo (`9111aca3`, corregido en `610a089e`). En T95 dije que por hora la liquidación salía en $0 sin aviso; es falso.
- **Suite:** tras T95, rápida 1173 aprobadas, 6 omitidas, 1 fallida (`ImportesPesosT24Test`, regla vieja del cero). Corregida; repetidas solo las afectadas (12 en verde), no la suite entera.
- **Sigue, en este orden de Carlos:** T96 (la hago yo, prueba Gemita); prompt de T94 para Gemita, controlo yo; T104 «sin descubierto» (leer los contratos y explicarle si el problema es real); prompt de T97 para Codex, controlo yo; T102 (inactivar nivel) la hago yo al final. Un tema por mensaje: termina uno y recién ahí el siguiente; nada de texto de más.
- **También pendiente, sin pedido (mi función en TEST):** Codex entregó la tanda 4 (días 41 a 47): falta mi revisión, la prueba técnica y la planilla. No la empecé. T95 sigue esperando la prueba de Carlos.

## 2026-10-30 — Claude CyE — T95: tarifa del profesor obligatoria de verdad

- `ProfesorWebController` exige la tarifa que corresponde al deporte (hoy y, si hay cambio programado, la del mes que viene), mayor a cero, al crear y al editar. 6 pruebas nuevas (1316); manual de profesores al día. **Error mío:** le dije a Carlos que por hora la liquidación salía en $0 sin aviso; `LiquidacionService:435-438` la frena con «El profesor no tiene valor por hora configurado.». Leí el cálculo y no la validación previa. Sin desplegar.

## 2026-10-30 — Claude CyE — T99: frecuencia repetida al crear un grupo

- `GrupoWebController::store` rechaza dos filas con la misma frecuencia con un aviso (antes, error del servidor por el índice único). Nombres de campo en `lang/es/validation.php`: frecuencia, precio, importe, motivo de la fecha de ingreso. 2 pruebas nuevas (1310) y una entrada en el manual de grupos. La controla Carlos por pantalla. Sin desplegar: Cora trabaja en el sitio.

## 2026-10-30 — Claude CyE — T98: aviso de eliminar alumno

- «Puede darlo de baja cambiando su estado a inactivo.» pasó a «Puede pasarlo a Inactivo.» en `AlumnoWebController::destroy` y `alumnos/show.blade.php`. `EliminarAlumnoYClaseT65Test`: 19 en verde. Carlos pidió que no lo controle otra IA.

## 2026-10-30 — Claude CyE — Guion de la tanda 4 (días 41 a 47), simulación rehecha

- Carlos rechazó hacer el guion sin pensar el conjunto. La primera simulación no preparaba todas las salidas del generador y sumaba 3 movimientos por turno. Rehecha: 58 casos nuevos, todos los perfiles por pantalla, errores humanos cada día, 87 movimientos de caja y una persona por cada camino del generador.
- Plata calculada a mano antes de correr: coincide al peso los siete días; deuda con una diferencia explicada ($8.000 de 46.4). Ensayo del 1/12: 84/7/23, lo calculado.
- Defectos nuevos: T100 (adelanto con un cero de más) y T101 (gasto del dueño a nombre de la operativa, leído en el código). `rutina.php`: los ausentes con dos fichas se buscan en el grupo de la clase.
- Sitio de prueba de `d488b3f5` a `937d99cc` (Ayuda etapa C y T98): respaldo `wingstest-antes-despliegue-2026-10-30-tanda4.sql.gz`, merge --ff-only, build, 0 migraciones, cachés; /login 200, reloj intacto (18/11). Sin `artisan down`: falló por usar `php` 7.4 y el merge siguió; pasos de artisan rehechos con php82. `dia-41-manana.json --simular` bien, nada guardado. [Prompt](../06-pruebas/PRU-04/PROMPT-CODEX-DIAS-41-47.md).
- Corregido al escribir el prompt: el día 43 repetía la lista de Federadas (rutina y pantalla) y había números de paso mal citados. Carlos: T94 a T101 se prueban en la tanda 5; agregados al guion. No desplegar hasta que Codex termine.

## 2026-10-30 — Claude CAB — Tanda 4 simulada (días 41 a 47); se sigue en CyE

- Diseñada con el método escrito (días seguidos con rutina, lo nuevo por pantalla, datos para cada salida del 1/12) y simulada entera desde la base del sitio al 18/11: 0 no esperados. Rutinas `dia-41` a `dia-47` en el repositorio; simulación portátil en `PRU-04/simulacion/tanda-4/` (`correr.sh`, `LEEME.md` con el plan, cierres y hallazgos).
- Encontrado al simular: error del servidor con frecuencia repetida al crear un grupo y avisos con nombres internos (T99); el recargo de Emma corre el 24/11, no el 25 (corregí el guion de la tanda 3 y ESTADO-PRUEBA); el ejecutor de rutinas daba por creada una serie que pedía confirmar el aviso de cancha (corregido en `rutina.php`).
- Falta, en CyE: `GUION-DIAS-41-47.md`, `PROMPT-CODEX-DIAS-41-47.md`, merge en `/home/wingstest/app` y `dia-41-manana.json --simular` ahí. El día 46 el reloj va a las 06:13 y se espera a las 06:15.

## 2026-10-30 — Claude CAB — Tanda 3 revisada y prueba técnica de los días 34 a 40

- Codex entregó los días 34 a 40 (commit `940c53b5`). Cierres iguales al guion. Nuevo en la revisión: bajé la base del sitio y la comparé con la copia simulada por claves naturales (`prueba-tecnica/comparar-con-simulacion.php`): 0 diferencias en 11 tablas.
- Las dos «no cumple» (39.1 y 40.2) eran errores de mi guion, comprobados en el código; erratas en el guion.
- Controles internos: ocho nuevos y tres puestos al día (describían el día 33). Uno mío estaba mal (Joaquín Rodríguez en vez de Joaquín Vega). Probados contra una copia con cinco datos rotos: se pusieron en rojo.
- Seguridad: seis pruebas nuevas. Aprendido: el cobro responde 302 con «Pago registrado…» y el rechazo 302 con error; las pruebas miran el aviso y la plata, no el código.
- Velocidad: por primera vez se mide Cobrar, abriendo un turno dentro de la transacción que se revierte.

## 2026-10-30 — Claude CAB — T12/T13 etapa C hecha por Claude (Gemini sin crédito)

- Carlos: «Gemini se quedó sin crédito… hacé vos la tarea». 22 páginas nuevas (17 dueño, 1 operativa, 4 profesora); el **?** en 39 vistas más: solo quedan sin ? login, errores, layouts y Ayuda. Detalle en `IMPLEMENTACION-T12-T13.md` §15.
- `AyudaBotonPorRolTest` (3 pruebas, 1308): recorre cada pantalla con cada rol y sigue el ?; la de la profesora en Clases falla sin el arreglo (comprobado).
- Capturas reales con `etapa-c/CapturasEtapaCTest.php` (HTML que devuelve Wings, sin texto agregado) y 24 caminos prohibidos ejecutados (`caminos-prohibidos.json`). Fichas generadas por script con la línea literal.
- Revisor independiente (subagente) en cinco vueltas: 3 graves, 15 medios y 9 menores en la primera; desde la tercera, 0 graves y lo abierto era de las fichas. Lo que marcó la quinta (citas de fichas) quedó corregido sin una sexta vuelta: el generador ahora falla si una cita es ambigua. Lo controla Codex. Huecos para Carlos: 9 (tarifa del profesor «obligatoria» que no lo es, sin acceso para reconfigurar el mostrador, sacar una frecuencia que tuvo alumnos da error, transferencias sin corrección, entre otros).
- **Errores míos:** escribí que en Clases la profesora recibía 403 (leí una línea con grep, no el `@if`); corregí mal la regla de nombres repetidos (sí ignora acentos); en mi control de la etapa A dije que la regla del plan no existía, cuando sí existe el aviso de exceso (no obligatorio). También: la captura del paso 2 de deshacer la primera carga (etapa B) tenía un cartel inventado por el script de Gemini; se sacó.
- Control final: Gemini con otro modelo (Carlos 30/10; Claude no se controla a sí mismo). Prompt: PROMPT-GEMINI-T12-T13-CONTROL-ETAPA-C.md. Sin desplegar.
