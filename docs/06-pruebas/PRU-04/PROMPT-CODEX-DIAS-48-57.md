# PROMPT — Prueba por días, tanda 5, la última: días 48 a 57 (26/11 al 5/12) — para Codex

> Prompt para Codex, escrito por Claude CAB el 01/11/2026. **Carlos no lo pasa hasta que Claude le avise:** antes hay que desplegar `main` en el sitio de prueba y correr ahí `dia-48-manana.json --simular`, y eso espera a que termine el control de T82 y T100.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Leer **entero** `docs/06-pruebas/PRU-04/GUION-DIAS-48-57.md` antes del primer paso. Sigue donde dejaste el sitio: **25/11 simulado, ninguna caja abierta**. No repetir nada de los días 41 a 47.

**Autorización de Carlos, para no volver a preguntar:** todas las operaciones de este guion en test.gestionar-te.com.ar (base `wingstest`, datos ficticios): altas, eliminar un alumno mal cargado, cobros, adelantos, cancelaciones y anulaciones, cuotas de meses pasados, condonaciones de cuota y de inscripción, transferencias y su anulación, ajustes de saldo, gastos, sueldos, alquileres, retiros, cambios de frecuencias, grupos, precios, niveles, deportes, medios de pago, subrubros, configuración e inscripción, usuarios (también un segundo dueño y una profesora), desactivar y reactivar usuarios, cambiar nombre y rol de un usuario, resolver la Revisión, generar, cancelar, ajustar, eliminar y pagar liquidaciones, programar, cancelar y validar clases, reactivar a Lautaro y las rutinas por consola.

## Para qué es esta tanda

**Carlos decidió que con estos resultados se despliega en producción.** Es la última vez que alguien recorre el club entero antes de que lo use la gente del club. Tu entrega es lo que se va a mirar para decidir, así que importa tanto lo que funciona como lo que no se pudo probar.

La pregunta es una sola: **¿un mes completo se puede operar, cobrar, pagar y entender solo con Wings, sin arreglar nada por atrás?** Se contesta con estos objetivos:

| Objetivo | Qué hay que mirar |
|---|---|
| **El cambio de mes** | El 1/12 la generación corre sola, sin que nadie la lance. Cada alumno preparado cae donde dice la tabla del guion. El 3/12 vuelve a correr y no hace nada |
| **La Revisión** | Nunca se recorrió: en noviembre no hubo a quién mandar. Mirá cómo avisa, cómo la ve cada rol, qué pasa con cada resolución y si una persona del club entendería qué le pide la pantalla |
| **Las reparaciones T94 a T109** | Cada una tiene su paso. Es la primera vez que se ven en el sitio. Son la razón principal de esta tanda |
| **Cerrar noviembre** | Las liquidaciones, con todos sus errores posibles, una profesora que ya se fue, sueldos y canchas. Después, si Reportes dice lo que se pagó |
| **Una actividad nueva de punta a punta** | Hockey, desde que el dueño la crea hasta la primera lista de Nora en el teléfono |
| **Lo que nunca se probó** | Unos 70 casos de la planilla, repartidos en los días. Si no se puede llegar a uno, decilo por su nombre |
| **La plata** | Los cierres de los 10 días contra el guion, medio por medio y al peso |

**Mirá con ojos de la persona del club.** Si un paso da lo que dice el guion pero el aviso no se entiende, el botón está escondido o tuviste que adivinar qué hacer, anotalo aunque el paso «cumpla». Eso también sirve para decidir.

## Qué cambió desde la tanda 4

- El sitio tiene `main`: T94 a T109, controladas por otro agente. T103 y T104 no cambiaron código.
- **Vienen cinco defectos anunciados** (T110 a T114; pasos 48.4, 53.4, 55.4 y 56.8). **Se ejecutan igual**, se anotan y no frenan.
- Clara y Thiago se limpian **por pantalla**, en 49.2 y 49.3. No por otro camino.
- El guion marca avisos donde el catálogo está viejo (50.6, 52.1, 52.7 y 55.6): anotalos y seguí.

## Cómo se trabaja

El detalle está en el guion.

1. **Reloj, siempre hacia adelante:** 09:00 del día. Excepciones:

| Día | Reloj |
|---|---|
| 50 | 09:30, después 10:30 y 12:30 |
| 51 | 20:00 |
| 53 | 05:55: **esperar las 06:00 y las 06:15**; después 08:30 y 09:00 |
| 55 | 05:55: **esperar las 06:00 y las 06:15**; después 08:30 y 09:00; Nora a las 16:30; noche a las 21:00 |
| 56 | 09:00, 09:30, 13:00, 14:00, 15:00, 17:00 |
| 57 | 09:00, 13:30, 21:00, 21:30 |

2. **Rutina con `--simular` y, si da bien, sin `--simular`**, en el lugar que marca cada día:
   - **50:** la rutina va **después de 50.2**: Pablo ya abrió por pantalla.
   - **51:** sin rutina.
   - **52:** Hernán y Lucía **no cargan** sus listas. Las de Fútbol Principiantes e Intermedias van el 53. La noche **no valida**: se valida en 53.1.
   - **53:** la rutina va **después de 53.3**.
   - **55:** la noche **no valida**: se valida por pantalla, en 55.10.
   - **56:** cuatro rutinas (`manana`, `mediodia`, `tarde`, `noche`). **La pantalla de 56.0 queda abierta mientras corren las dos primeras.** Rocío abre por pantalla, en 56.1.
   - **57:** Pablo ya se llama **Juan Pablo Ledesma** desde 56.3: la rutina no corre sin ese cambio.
3. **No se repara nada.** Lo que no coincide se anota tal cual, con captura, y se sigue.
4. **Los que no vienen no se marcan presentes.** La lista está en «Punto de partida». Si por error marcás a alguno, avisá: cambia lo que da el 1/12.
5. **Teléfono:** con el método del guion, y dejando `window.innerWidth` en la evidencia. Si no da 375, **no probado**. Para 57.3, cortá la conexión con `Network.emulateNetworkConditions` y `offline: true`.
6. **Decisiones:** no preguntar a Carlos lo que el guion resuelve. Si el guion no alcanza, anotá la duda y seguí con el resto. **Solo se frena un día entero si su rutina falla**, y aun así seguí con lo que no dependa de ese día.
7. Consola solo para el reloj y las rutinas. Ninguna consulta ni cambio directo en la base. Lo marcado **Solo prueba técnica** no se hace.
8. Comandos en el servidor con `/usr/bin/php82`, no con `php`. Claves de los usuarios: las mismas de las tandas anteriores; las de Vanina Socia y Nora Vidal están en el guion. Las clases se buscan por grupo, día y hora. Thiago Ruiz, Camila Benítez, Mora Benítez y, desde 49.4 y 55.7, Mateo y Mia Rodríguez tienen una ficha por deporte.
9. Un servidor de un solo núcleo: si Cloudflare corta (522), reintentá y anotalo.

## Qué entregar

- `DIA-48.md` a `DIA-57.md` en `docs/06-pruebas/PRU-04/`, uno por día: tabla con #, caso, resultado (cumple, no cumple o no probado), lo que mostró la pantalla con sus textos exactos y la evidencia.
- Capturas en `docs/06-pruebas/PRU-04/evidencia-dias-48-57/`, una por paso con resultado, nombradas por número de paso. Si una captura no muestra el texto, conservar el texto aparte.
- Al final de cada día, las cifras de pantalla: Cobranza (deuda y estados), Reportes (ingresos, egresos y disponible por medio) y «Sin asistencia». Además: el 48, las tarjetas de 48.9; el 53, la constancia de la generación y la lista de Revisión; el 55, Reportes → Sueldos; el 57, octubre, noviembre y diciembre en Reportes.
- **`RESULTADO-TANDA-5.md`**, una página, para Carlos. Una fila por cada objetivo de arriba y por cada reparación, con cumple, no cumple o no probado. Después: los defectos nuevos, lo que no se pudo probar con su motivo y lo que a la gente del club le costaría entender. Sin opinar si se despliega: eso lo decide Carlos.
- Firmar en `docs/00-estado/LOG-CODEX.md`. Pasar T14 `a_verificar, tiene=Claude`, con el resultado en el paso. Para el commit: `git status`, agregar por nombre, nunca `git add -A`. Si un archivo propio tiene cambios ajenos, frenar y avisar. No tocar código, vistas ni el catálogo.
