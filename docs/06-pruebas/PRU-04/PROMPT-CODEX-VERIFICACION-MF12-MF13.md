# Verificación por pantalla de lo subido el 30/10: MF12, MF13 y las dos observaciones de la tanda 2

> Prompt para Codex, escrito por Claude CAB el 30/10/2026. Carlos lo copia y se lo pasa a Codex.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Es una verificación corta en
`test.gestionar-te.com.ar` (base `wingstest`, datos ficticios), con las mismas claves de la tanda 2. Carlos
autoriza lo que mueve el reloj y lo que se cobra acá (30/10). **No repetir nada de los días 24 a 33.** El sitio
quedó en el 11/11 simulado, de noche.

## Qué se subió

| Qué | Dónde se ve | Lo esperado |
|---|---|---|
| **MF12** · Nueva liquidación contaba como pendientes las clases de un mes ya liquidado por comisión | Liquidaciones → Nueva, **octubre 2026** | Hernán Quintana y Martín (Fútbol, por comisión, octubre ya pagado) **no aparecen con clases liquidables de octubre**. Lucía, Mariela y Verónica (por hora) siguen igual que el día 28. Con **noviembre** no cambia nada: Verónica inactiva con 2 clases, los demás con las de noviembre |
| **MF13** · El resumen diario contaba como «sin lista tomada» una clase con todos ausentes | Telegram, el resumen de las 08:00 | Reloj a **12/11 07:58** y esperar que pasen las 08:00. El resumen **no trae** la línea «Clases sin lista tomada» (Fútbol Principiantes del 28/10 tiene la lista tomada con todos ausentes; el Inicio dice 0). Sí trae lo demás que corresponda a esa fecha. Pegar el texto que llegó en el informe |
| **Ficha** · Un cobro de solo inscripción decía «Nov 2026» | Alumnos → ficha de **Iván** (alta del 3/11), historial de pagos | El pago de $5.000 del 10/11 dice **«Inscripción»**, no «Nov 2026». Florencia Vega sigue diciendo «Nov 2026» en su cobro de $55.000 |
| **SIS-006** · Avisos del servidor en inglés al saltear el navegador | No se ve por pantalla: Chrome frena antes, en castellano | Nada que hacer: lo cubre una prueba automática. Si en algún formulario aparece un aviso en inglés, anotarlo con la pantalla y el texto |

## Cómo se hace

1. Reloj a **12/11 07:58**: `wings:fecha-simulada "2026-11-12 07:58"`. Esperar hasta pasadas las 08:00 (tres
   minutos reales) y recién después abrir pantallas. No correr el resumen a mano.
2. Las tres pantallas de arriba, con capturas y texto completo, en `docs/06-pruebas/PRU-04/evidencia-verificacion-mf12-mf13/`.
3. Informe corto en `docs/06-pruebas/PRU-04/VERIFICACION-MF12-MF13-CODEX.md`: una tabla con las cuatro filas,
   «cumple / no cumple», lo visto y la evidencia. Sin reparar nada: lo que no cumpla se anota.
4. Dejar el reloj donde quedó (12/11) y no tocar nada más del sitio.

## Cierre

Tablero: `php scripts/tablero/tablero.php cambiar MF12 estado=... ` y lo mismo para MF13 solo si cumplen
(`estado=cerrado`, `verifica=Codex`); si no, `devuelto, tiene=Claude`. Firma en `LOG-CODEX.md`. Commit:
`git status`, agregar por nombre, nunca `git add -A`; si un archivo propio tiene cambios ajenos, frenar y avisar.
Sin despliegue.
