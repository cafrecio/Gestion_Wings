# B3 — Excepciones al tomar asistencia

Codex CAB,27/10/2026. Segunda entrega local, después del dinero en17f99d13.
Entrega inicial51d26fb8 devuelta por Claude el27/10 por identificar como nuevos a
los alumnos de primera carga. La corrección está en «Devolución27/10», al final.
Sin despliegue ni uso del sitio de prueba, servidor
o base del club. Solo `wings_testing_codex`.

## Comportamiento

- La lista muestra la condición de cada alumno junto a su nombre. Conserva el
  indicador semanal del plan; ese indicador no es el contador del alumno nuevo.
- Nuevo sin primer pago de cuota: las primeras dos clases presentes tienen margen;
  desde la siguiente se admite con motivo obligatorio. El contador es interno y
  no se envía en HTML ni JSON. Una inscripción sola no sustituye el pago de cuota;
  un pago parcial de cuota ya termina la condición de nuevo, conforme al §4.
  **Corrección27/10:** quien figura en la primera carga terminada tampoco es nuevo,
  aunque nunca haya pagado una cuota en Wings.
- Con historia, DEUDOR necesita motivo y MOROSO entra sin justificar. Los estados
  salen del mismo servicio de cobranza actual; no se introduce otra clasificación.
- El servidor controla el pedido forzado, valida la lista completa antes de
  escribir y guarda asistencia y excepción en una sola transacción. Bloquea clase
  y alumnos en orden estable; mantiene controles de grupo, ingreso, solapamiento,
  cancelación, futuro, corrección de listas y liquidación cerrada.
- Profesora, operativa y ADMIN usan el mismo control. No se cambian rutas ni
  permisos ni se muestran importes de deuda a la profesora.
- Al marcar aparece un panel en la tarjeta. Guardar muestra el resultado y los
  motivos en el lugar. Sin confirmaciones nativas ni JavaScript en Blade; no se
  modifica CSS compartido.

El registro común de la entrega de dinero conserva tipo, motivo, autor, fecha,
alumno y asistencia de origen, con `clase_id` en el contexto. Inicio del dueño y
el resumen diario existente incluyen también estos dos tipos; no se manda un
aviso al marcar cada asistencia.

Un reenvío sin cambiar la marca no representa otra decisión ni duplica el aviso.
Si se cambia a ausente y luego se lo admite otra vez, el motivo y autor nuevos se
conservan como otra decisión, sin reescribir la anterior. La segunda migración
reemplaza la unicidad por origen con un índice de consulta; el flujo de cobro
conserva su protección idempotente. La reversión rechaza restaurar una unicidad
que obligaría a perder decisiones; no borra filas para lograrlo.

El exceso de plan queda fuera de B3 y conserva EXTRA/RECUPERA (DU-34). No se exige
un motivo libre por ese exceso.

## Pruebas y recorrido

| Comprobación | Resultado |
|---|---|
| Primeros15 casos, antes del arreglo | 11 fallidas/4 aprobadas,32 aserciones,14,94s. |
| Primeros15, después del arreglo | 15 aprobadas,68 aserciones,13,72s. |
| Cinco adicionales contra controlador/servicio originales de17f99d13 | 3 fallidas/2 aprobadas,13 aserciones,12,74s. Sin reemplazar archivos del checkout. |
| Área:20 propias, asistencia, T52/T34 y dinero | 71 aprobadas,344 aserciones,20,60s. Incluye rollback tardío, reenvío, readmisión e historial, resumen conjunto, roles, MOROSO, margen y contador interno. |
| Rápida final | 1011 aprobadas,6 omitidas,7855 aserciones,190,04s, salida0. |
| Completa final | 1130 aprobadas,6 omitidas,9454 aserciones,962,45s, salida0. Corte1136 métodos. |
| Tablero y documentación al cierre | 7 aprobadas,34 aserciones,0,55s, salida0. Contador del checkout1146 incluye10 casos T54 posteriores; no se certifican como parte de esta completa. |

La primera corrida posterior al cambio se interrumpió por un nombre incorrecto
del índice en la migración nueva. Se leyó el nombre real y se corrigió; no se
acredita esa corrida interrumpida como verde. Build Vite correcto (36 módulos,
7,62s); archivo `build.txt`. Fuente propia congelada antes de las suites finales.
Los seis archivos de producto/prueba propios mantienen sus SHA256 al terminar
ambas suites. La búsqueda T54 se está modificando en paralelo y sus10 pruebas
nuevas no integran el corte1136 de esta completa. Se conserva su producto y sus
pruebas fuera del commit propio; el tablero compartido conserva el estado T54
movido por Gemini, sin atribuir su verificación a Codex. El contador del checkout
se actualiza y califica por separado.
El grafo se usó para localizar y trazar el flujo. La cobertura guardada informa
`metadata_changed` y una línea Blade parcial; se comprobaron los cuerpos y la
vista directamente, además de las pruebas. No se presenta el índice como una
verificación completa de las fuentes. Sintaxis PHP/JS y compilación Blade correctas.

Navegador local como profesora: marcar Nuevo y Deudor abre los motivos; Guardar
sin completarlos no escribe. Con ambos motivos se guardan cuatro presentes
(Nuevo, Deudor, Moroso y nuevo dentro del margen) y dos ausentes. Se guardan dos
excepciones de asistencia con autora profesora y vínculo a las marcas. Reenviar
no agrega filas. El dueño ve ambas y Ver abre la clase con los motivos persistidos.
[Filas comprobadas](evidencia-b3-asistencia/recorrido-final.json).

El fixture tiene un parcial previo creado para mostrar En plazo: por eso Inicio
muestra tres excepciones en total, dos de asistencia y una de dinero. No se
confunden con tres excepciones causadas por la lista. Se repitió el fixture local
para renovar las capturas después de ajustar el padding del campo Motivo, sin
modificar datos del club. El servidor local se detuvo antes de las suites.

[Galería real1366/375](APROBAR-B3-ASISTENCIA.html), con login de calibración.
Se conservaron DOM y valores observados, se neutralizaron tokens y se adaptaron
assets locales; los marcos solo fijan el ancho. Se corrigió una omisión del
exportador de assets que dejaba el logo sin cargar, y se renderizaron de nuevo
las imágenes definitivas. No se fabricó HTML de Wings ni se editaron imágenes.
La consulta guardada corresponde al dueño; las pantallas de lista y motivos son
de la profesora. Aprobación visual de Carlos pendiente.

## Preguntas registradas, sin crear políticas nuevas

- Tratamiento del aviso histórico si se anula/corrige su origen: mantener,
  identificar como anulado o excluir del resumen. Mismo pendiente que dinero;
  no se borra ni reescribe la decisión original.
- **Primera carga: no era una pregunta abierta.** La regla estaba decidida desde
  el06/09 y los ids ya existían en `primera_carga.detalle.alumnos`. La entrega
  inicial omitió ese dato; se corrige en la devolución, sin cambiar política.
- Las condiciones de cobranza se consultan al realizar la acción; no se
  reconstruye un estado financiero histórico al corregir una clase vieja. El
  contrato no fija ese corte histórico. Las marcas presentes que no se cambian
  no generan justificaciones retroactivas.

Observación fuera de B3: el número semanal del plan ya se refrescaba al reabrir
la ficha, no en la respuesta de Guardar. El contador general de presentes y los
motivos sí se actualizan en el lugar. No se cambia la regla ni se mezcla ese
indicador semanal con el contador interno de alumnos nuevos.

## Entrega

B3 se entrega `a_verificar`, hizoCodex, verificaClaude, tieneClaude. T88 queda en el
mismo estado, con la completa final verde de esta segunda entrega como evidencia
adicional. La tabla de pruebas externa y su copia permanecen intactas. Sin push
manual, despliegue, sitio de prueba ni datos del club.

El auditor `evidencia-b3-asistencia/auditar-entrega.py` comprueba resultados,
fuentes congeladas, estados, enlaces, límites de bitácora, tokens neutralizados,
nueve PNG y alcance. Su resultado y las huellas están en `auditoria.json`.

## Devolución 27/10

Claude devolvió asistencia de51d26fb8 ([verificación](VERIFICACION-T88-B3-CLAUDE.md)).
El dinero de17f99d13 quedó aprobado en lógica y no se modifica. El error era
ignorar la regla ya tomada de §4; no faltaba una decisión de Carlos.

`ExcepcionCobranzaService::condicionesAsistencia` ahora lee una vez la lista
`primera_carga.detalle.alumnos` de la carga TERMINADA. Sus alumnos tienen historia:
AL_DIA, EN_PLAZO y MOROSO entran sin motivo; DEUDOR exige motivo de deuda y guarda
`ASISTENCIA_DEUDOR`, sin el mensaje de falta de primer pago. Las altas que no
figuran en esa lista siguen nuevas hasta su primer pago de cuota y conservan
dos clases de margen. Una carga PENDIENTE, sin detalle o sin lista válida no
exime a nadie ni provoca un error. No se crean pagos ficticios ni se infiere
historia por antigüedad.

La lista deja de mostrar Nuevo a los alumnos cargados. No se tocaron Blade,
JavaScript, CSS, rutas ni permisos: el cambio visible sale del dato corregido.
[Capturas nuevas1366/375 y login de calibración](APROBAR-B3-PRIMERA-CARGA.html),
del HTML devuelto por la aplicación autenticada, renderizado con Chrome. Celular
en marco375 medido, no ventana recortada. La profesora ve cuatro alumnos cargados
con sus estados y el alta posterior identificada como Nuevo. Aspecto pendiente
de Carlos; su aprobación anterior de T86 no se reutiliza.

La prueba roja de Claude fue adaptada a `tests/Feature/B3PrimeraCargaTest.php`,
con datos sintéticos y el mismo recorrido por ruta. Siete casos permanentes:
el original al día en tercera clase; DEUDOR sin/con motivo; MOROSO; EN_PLAZO;
alta posterior; carga PENDIENTE/sin detalle/lista inválida; y etiquetas en HTML.

| Comprobación | Resultado |
|---|---|
| Siete casos antes del arreglo | 5 fallidas/2 aprobadas,33 aserciones,13,12s. |
| B3 primera carga, B3 asistencia, T88 dinero/concurrencia y T52 | 58 aprobadas,292 aserciones,21,41s. |
| Técnica del día21 solicitada | 1 aprobada,5 aserciones,21,20s. |
| HTML real para capturar | 1 aprobada,7 aserciones,21,63s. No cuenta como prueba permanente ni recorrido humano de navegador. |
| Suite rápida | 1028 aprobadas,6 omitidas,7983 aserciones,224,98s, salida0. Contador1153 métodos. |
| Documentación y tablero | 7 aprobadas,34 aserciones,0,15s, salida0. |

Resultados, fuente anterior y datos sintéticos de captura en
`evidencia-b3-devolucion-27-10/`. Solo `wings_testing_codex`, con bootstrap que
comprueba entorno, host, DB_URL y ausencia de caché de configuración. No se
repite completa por esta corrección localizada en la condición de asistencia;
los cuerpos de cobro y guardado común permanecen iguales. La completa anterior
del informe conserva su corte y no acredita el arreglo nuevo.
Sintaxis PHP y compilación Blade correctas; diff de vistas/CSS vacío. El auditor
`evidencia-b3-devolucion-27-10/auditar.py` comprueba resultados, siete casos nuevos
verdes, fuentes congeladas, etiquetas reales, enlaces, tokens y tres PNG. Resultado
PASS en `auditoria.json`. El HTML T34 que ya estaba modificado se conserva byte
por byte y queda fuera del commit propio.

### Barrido de «sin pagos = nuevo»

Usos encontrados en `app/`, `resources/` y `routes/`, con grafo como orientación,
lectura de cuerpos y comprobación de rutas. El índice informa lagunas de cobertura;
no se usa para afirmar una ausencia de código.

| Lugar | Efecto y acción |
|---|---|
| `ExcepcionCobranzaService::condicionesAsistencia` | Defecto alcanzable en lista/guardado. **Corregido** con los ids de carga terminada. |
| `CajaWebController::cobrar` y `PagoCuotaService::calcularReglaPrimerPago` | Pagos completados de cuota para descuento informativo/precio. Está limitado al mes de alta y respeta el porcentaje congelado de la carga. La vista solo consume esa decisión. **No tocados**; no se cambian importes ni el dinero aprobado. |
| `PagoService::registrarPago` y `obtenerProximoPago` | Conservan sin pagos = primer pago, regla por día de alta y estimación. Los llama PagoController por rutas de `routes/api.php`, deshabilitada en `bootstrap/app.php`. **No tocados ni reactivados**; revisar ese supuesto antes de habilitar la API. |
| `Alumno::tienePagos`, `Admin\\AlumnoController::destroy` y `Alumno::motivoNoEliminable` | Existencia de registros para impedir borrado con pagos; no clasifican admisión ni muestran Nuevo. **No tocados**. |
| `GenerarDeudasMensualesCommand` | Alta reciente y pago reciente como criterio positivo para generar deuda; no convierte ausencia de pago en nuevo ni decide admisión. **No tocado**. |

La corrección vuelve a Claude en B3 `a_verificar`; dinero sigue aprobado en lógica
y su aspecto pendiente de Carlos. Sin sitio de prueba, servidor, base del club,
tabla externa/copia, push manual ni despliegue.
