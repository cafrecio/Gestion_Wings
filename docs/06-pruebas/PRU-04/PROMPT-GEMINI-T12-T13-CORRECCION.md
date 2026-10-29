# T12 y T13 · Corrección de la etapa 1 y método obligatorio para la etapa 2

> Prompt para Gemini, escrito por Claude CAB el 28/10/2026. Carlos lo copia y se lo pasa a Gemini.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Volver a leer
`docs/06-pruebas/PRU-04/PROMPT-GEMINI-T12-T13.md`: sigue vigente en todo lo que este prompt no cambia.
Marcar T12 y T13 `en_curso, tiene=Gemini`.

## Qué dijo Carlos

El formato de las páginas y la lista de 54 le parecen bien. **El contenido no se puede aprobar**: Claude
comparó las dos muestras con el código y encontró textos que Wings no tiene y un paso que Wings no
permite hacer. El prompt de la etapa 1 pedía ejecutar cada paso en el sistema antes de escribirlo; eso
no se hizo. Un manual que inventa es peor que no tener manual: la operativa lo sigue y queda trabada.

## 1. Corregir lo encontrado

En `resources/manual/operativo/cobrar-cuota.md`:

1. «No tenés una caja abierta» **no existe**. El mensaje real es «Debés abrir la caja y confirmar el
   efectivo antes de operar.» (`app/Services/CajaService.php:278`).
2. La apertura no tiene «Saldo inicial» ni «Abrir caja»: el campo es **Efectivo recibido**
   (`resources/views/caja/apertura.blade.php:53`) y el botón es **Abrir** (línea 85).
3. Un cobro mal hecho no se «toca para anularlo» en Caja: se **cancela** desde el detalle de la caja,
   con motivo, y solo con la caja abierta o rechazada (`app/Http/Controllers/CajaWebController.php:603`).
   Falta el caso de la caja **cerrada que el dueño todavía no validó**: ahí la operativa no puede
   cancelar y tiene que pedirle al dueño que la rechace.
4. No existe el estado «parcial» (`app/Models/DeudaCuota.php:15-18`). Un pago parcial deja la cuota
   pendiente y muestra cuánto se pagó.
5. El paso 1 dice que se entra por Cobranza o Alumnos, pero la captura es la pantalla «Cobrar cuota» con
   su buscador. Texto y captura tienen que contar el mismo camino.
6. No nombrar medios de pago («Tarjeta de débito», «Tarjeta de crédito»): los configura cada club. Decir
   «los medios habilitados».

En `resources/manual/admin/corregir-movimiento-caja-validada.md`:

7. **Grave.** «Si el error afectó la deuda de un alumno… reactivá la cuota pendiente» **no se puede
   hacer**. Reactivar solo acepta cuotas perdonadas (`app/Services/PagoCuotaService.php:380`). Un cobro
   hecho por caja no se anula desde la ficha (`PagoCuotaService.php:1266`) y la caja validada no permite
   cancelarlo. Hoy Wings **no tiene** forma de devolverle la deuda a ese alumno. La página tiene que
   decir eso, sin inventar un camino, y queda anotado en tu entrega como hueco para que Carlos decida.

## 2. El método, obligatorio para cada página, desde ahora

1. **Ejecutar antes de escribir.** Cada paso se hace en Wings local, con el usuario del rol de la página
   y datos sintéticos, en `wings_testing_gemini` o en la base local. Lo que no pudiste ejecutar no se
   escribe: se marca *pendiente* en la lista.
2. **Ficha de fuentes por página**, en
   `docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/<rol>-<pagina>.md`. Una fila por paso y por cada
   «Qué hago si»: qué se afirma, `archivo:línea` donde Wings lo hace y qué pasó al ejecutarlo.
3. **Textos literales.** La **negrita** es solo para textos de pantalla (menú, botón, campo, título) y
   va copiada tal cual del código. «» es solo para mensajes de Wings, copiados tal cual. Para resaltar
   otra cosa, *cursiva*. Ejemplos que escribe el usuario, entre comillas comunes.
4. **«Qué hago si…» con la regla a la vista.** Antes de escribir una salida, leé el cuerpo de la función
   que la permite o la rechaza: estado, rol, fecha. Si Wings no lo permite, se escribe que no lo permite
   y a quién avisar. Nunca un camino supuesto.
5. **Verificador.** `php scripts/manual/verificar-manual.php` tiene que terminar con **0 problemas**.
   Controla «», negritas, imágenes repetidas o faltantes y la pantalla del encabezado. Que pase no
   alcanza: solo dice que los textos existen, no que el camino sea correcto.
6. **Revisor independiente.** Por cada tanda, otro agente revisa siguiendo
   `docs/06-pruebas/PRU-04/REVISOR-MANUAL.md`: un subagente si tu herramienta lo permite, o una
   conversación nueva **sin el contexto de esta**. No puede ser la misma conversación que escribió las
   páginas. Su informe se guarda en `evidencia-t12-t13/revisiones/`. Corregís todo lo grave y lo medio,
   y el revisor vuelve a mirar hasta que no quede nada grave ni medio.

## 3. Cómo seguir

1. **Primero las dos muestras y la página de asistencia** (`profesor/tomar-asistencia.md`) con el método
   completo: corrección, fichas, verificador en 0 y revisor sin graves ni medios.
2. **Después, la etapa 2** del prompt original (el resto de las páginas, el **?** en cada pantalla y el
   buscador), en tandas de hasta 10 páginas. Cada tanda cierra con el verificador en 0 y el informe del
   revisor. Firmar en `LOG-GEMINI.md` al cerrar cada tanda.
3. Los huecos de Wings que aparezcan (como el punto 7) no se arreglan ni se rodean: se listan en
   `IMPLEMENTACION-T12-T13.md`, en una sección «Huecos para Carlos», con `archivo:línea`.

**Cierre.** Al terminar la etapa 2: T12 y T13 `a_verificar, verifica=Claude`. Claude va a revisar
páginas al azar contra el código, además de las fichas y los informes del revisor.

Pruebas y contador como siempre (`DB_DATABASE=wings_testing_gemini`, suite rápida, contador de
`ESTADO-ACTUAL.md` solo con tus pruebas). Commit: `git status`, agregar por nombre, nunca `git add -A`;
si un archivo propio tiene cambios ajenos, frenar y avisar. Sin despliegue.
