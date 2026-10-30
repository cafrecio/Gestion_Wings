# Verificación por pantalla — MF12, MF13 y observaciones de tanda 2

**Codex CAB, 30/10/2026 — cumplidas las cuatro filas en el alcance pedido.** Chrome sobre test.gestionar-te.com.ar, wingstest ficticia. [Orden ejecutada](PROMPT-CODEX-VERIFICACION-MF12-MF13.md). Reloj fijado **una sola vez** en 12/11/2026 07:58; espera real de **199 segundos** (05:13:23 → 05:16:42 UTC) antes de abrir pantallas. No se corrió el resumen a mano ni se repitieron operaciones de los días 24–33. Queda el reloj en **12/11**, avanzando solo.

| Fila | Resultado | Lo observado | Evidencia |
|---|---|---|---|
| **MF12** · Nueva liquidación | **cumple** | Octubre 2026: Martín y Hernán muestran «0 clases liquidables»; Lucía, Mariela, Verónica y Vanina también 0. El listado confirma octubre Pagada para los dos de comisión. Noviembre conserva sus pendientes: Martín 2, Vanina 0, Lucía 7, Mariela 6, Hernán 8 y «Salinas, Verónica · Inactiva, con clases dictadas pendientes», **2**. No se pulsó Generar. | [Octubre](evidencia-verificacion-mf12-mf13/01-mf12-octubre-cero-comision.jpg), [texto](evidencia-verificacion-mf12-mf13/01-mf12-octubre-cero-comision.txt); [noviembre](evidencia-verificacion-mf12-mf13/01-mf12-noviembre-contadores.jpg), [texto](evidencia-verificacion-mf12-mf13/01-mf12-noviembre-contadores.txt); [pagadas](evidencia-verificacion-mf12-mf13/01-mf12-octubre-pagado-listado.txt). |
| **MF13** · Resumen diario | **cumple** | La captura original entregada por Carlos muestra el mensaje recibido el **30 de octubre a las 02:16 reales**, en la ventana de las 08:00 del 12/11 simulado. **No incluye «Clases sin lista tomada»**. Sí informa las **2 excepciones** de las últimas 24 horas: nuevo sin primer pago 1 y asistencia de deudor 1, coherentes con Iván y Santino del 11/11. Inicio: «Clases sin asistencia 0». Mensaje literal debajo. | [Telegram original](evidencia-verificacion-mf12-mf13/02-telegram-recibido-original.png), [transcripción](evidencia-verificacion-mf12-mf13/02-telegram-recibido-transcripcion.txt), [origen](evidencia-verificacion-mf12-mf13/02-telegram-origen-y-comprobacion.txt); [Inicio](evidencia-verificacion-mf12-mf13/02-inicio-12nov-sin-asistencia-cero.jpg), [texto](evidencia-verificacion-mf12-mf13/02-inicio-12nov-sin-asistencia-cero.txt). |
| **Ficha** · Solo inscripción | **cumple** | Iván Paredes: «HISTORIAL DE PAGOS 10/11/2026 · Inscripción $5,000.00». Florencia Vega conserva «11/11/2026 · Nov 2026 $55,000.00». Se consultaron ambos historiales sin cobrar de nuevo. El prompt dice alta del 3/11; la ficha identificada de Iván muestra **ALTA 02/11/2026**. Se registró la diferencia y se verificó el pago indicado del 10/11. | [Iván](evidencia-verificacion-mf12-mf13/03-ficha-ivan-inscripcion-5000.jpg), [texto](evidencia-verificacion-mf12-mf13/03-ficha-ivan-inscripcion-5000.txt); [Florencia](evidencia-verificacion-mf12-mf13/03-ficha-florencia-conserva-nov55000.jpg), [texto](evidencia-verificacion-mf12-mf13/03-ficha-florencia-conserva-nov55000.txt). |
| **SIS-006** · Alcance pedido por pantalla | **cumple** | Se respetó «Nada que hacer: lo cubre una prueba automática»: no se forzaron formularios ni se ejecutó otra prueba. **No apareció un aviso en inglés en Wings durante estas consultas.** Esto no certifica por sí solo las validaciones del servidor; Claude registra su prueba y suite previas en LOG-CLAUDE del 30/10, no ejecutadas por Codex. | Textos completos de las pantallas anteriores. |

## Texto recibido en Telegram

Transcripción de la imagen original de Carlos, conservada sin editar y con su SHA256 en el manifiesto:

```text
Wings: resumen diario de pendientes

Excepciones justificadas (últimas 24 horas): 2 — Alumno nuevo sin primer pago: 1; Asistencia de alumno deudor: 1
— https://test.gestionar-te.com.ar/admin/dashboard#excepciones

Revisá cada sección ingresando al enlace correspondiente.
```

No se usó un texto generado por el agente como sustituto del mensaje. La primera apertura de Telegram Web fue rechazada por revisión automática por acceso privado; Carlos autorizó explícitamente leer solo este resumen. Telegram Web estaba sin sesión y pedía vinculación. Carlos eligió aportar la imagen: se comprobó esa fuente, sin iniciar sesión, leer otros chats ni enviar mensajes. Se cerró la pestaña propia de Telegram al recibir la evidencia.

## Estado y cierre

- Inicio al 12/11 conserva ingresos **$3.359.300**, egresos **$2.678.280**, deuda **$850.200**, resultado **$681.020** y disponible **$5.585.920,75**; cajas por validar **0**, clases sin asistencia **0**. No se generó, cobró, pagó ni reparó nada.
- MF12 y MF13 se cerraron con los comandos pedidos, **`estado=cerrado`, `verifica=Codex`**. [Salida MF12](evidencia-verificacion-mf12-mf13/04-tablero-mf12-cerrado.txt), [salida MF13](evidencia-verificacion-mf12-mf13/04-tablero-mf13-cerrado.txt).
- El tablero tenía cambios ajenos de T12/T13. Se frenó su escritura hasta que Carlos autorizó actualizar solo MF12/MF13 y preservar lo ajeno. Durante el cierre, el commit concurrente **`c7004dc8`** incorporó las dos filas cerradas y sus documentos generados: comprobado contra el contenido del commit y el tablero actual. No se reescribió ese commit ni se atribuyen a esta verificación los cambios ajenos. El commit de esta entrega añade solo informe, evidencia y continuidad propios.
- Sin despliegue, cambios de aplicación, pruebas destructivas, catálogo ni push. [Índice, huellas y controles](evidencia-verificacion-mf12-mf13/LEEME.md).
