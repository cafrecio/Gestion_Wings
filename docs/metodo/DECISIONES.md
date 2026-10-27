# Método de trabajo para proyectos nuevos: propuestas por tema

Origen: debate en [Gestion_CAB#1](https://github.com/cafrecio/Gestion_CAB/issues/1), hasta el 27/10/2026.
Rama: `metodo/decisiones`. **Ninguna idea está descartada.** Las concesiones que hubo durante el debate
quedan anotadas, pero no reemplazan la decisión de Carlos.

Cada tema tiene esta estructura:
1. **En criollo:** qué significa para un proyecto nuevo.
2. **Propuesta de cada agente.**
3. **Choques:** dónde chocan las propuestas.
4. **Decisión de Carlos:** pendiente hasta que la tome.

Cada agente corrige **solo su propia fila** si quedó mal resumida.

---

## 1. Qué lee un agente al empezar a trabajar

**En criollo:** cada vez que un agente arranca, lee instrucciones. En Wings eran unos 170.000 caracteres
(medido por Codex), y se pagan en crédito y en atención en cada sesión.

| Agente | Propuesta | A favor | En contra |
|---|---|---|---|
| Gemini | Un programa propio (`project-cli`) que le entrega al agente solo su tarea, sus contratos y los archivos que puede tocar. | Lectura mínima y exacta. | Hay que construirlo y mantenerlo. |
| Claude | Instrucciones de 100 líneas como máximo, iguales para los tres agentes, más un atajo de inicio (`/arranque`) que lee solo la tarea asignada. | Sin software nuevo; se puede usar desde el día 1. | Depende de que alguien respete el límite. |
| Codex | Núcleo breve con límite en caracteres, no solo en líneas. Un atajo de inicio que además compruebe el entorno. Probar algo chico antes de construir un programa propio. | Mide lo que realmente cuesta y evita sobreconstruir. | — |

**Choque:** programa propio desde el inicio (Gemini) o empezar con texto corto más atajos (Claude y Codex).
**Decisión de Carlos:** _pendiente_

## 2. Cómo se hacen cumplir las reglas

**En criollo:** en Wings casi todas las reglas eran texto ("no hagas X"). El texto se olvida; un control
automático no.

| Agente | Propuesta | A favor | En contra |
|---|---|---|---|
| Gemini | Guardianes en las pruebas: fallan si aparece JavaScript dentro de una vista; candado que impide tocar el diseño sin autorización. | Probado en Wings, funciona. | Cubre solo algunas reglas. |
| Claude | Toda regla que se pueda controlar se vuelve control (prueba, bloqueo o revisor automático). Cada regla lleva fecha y motivo, y una vez por mes se borra lo que ya está automatizado. | Las instrucciones no crecen sin límite. | Requiere disciplina de poda. |
| Codex | Automatizar lo que se puede comprobar. Las reglas que requieren criterio humano se escriben breves y con ejemplos. Cada regla responde: qué evita, cuánto cuesta y cómo se comprueba que sirve. | Reconoce que no todo se automatiza. | — |

**Choque:** no hay choque de fondo; se complementan.
**Decisión de Carlos:** _pendiente_

## 3. Dónde viven las tareas y su estado (el panel de Carlos)

**En criollo:** dónde se anota qué hay que hacer, quién lo tiene y cómo va. Carlos pidió un panel HTML
siempre al día y visible desde el celular.

| Agente | Propuesta | A favor | En contra |
|---|---|---|---|
| Gemini | Un archivo único de tareas (JSON) más el historial de cambios de git. | Ya funciona en Wings. | Hay que publicar el panel en algún lugar. |
| Claude | Las tareas como issues de GitHub; el estado se deduce de los issues, las pruebas automáticas y el último despliegue. Panel publicado en el servidor con contraseña. | GitHub ya tiene panel, celular y avisos sin construir nada. | Carlos depende de la interfaz de GitHub; panel propio aparte. |
| Codex | Una única autoridad por dato: tareas en un registro, decisiones en contratos, resultados de pruebas en su lugar, versión desplegada en otro. Lo demás enlaza o se genera. | Termina con las contradicciones entre documentos. | — |

**Choque (real):** **issues de GitHub** (Claude) o **archivo JSON propio** (Gemini, y Codex partiendo del
tablero existente). Las dos opciones funcionan; tener ambas a la vez repite el problema de Wings.
**Decisión de Carlos:** _pendiente_

## 4. El recorrido de una tarea

**En criollo:** los pasos desde que se pide algo hasta que está terminado.

| Agente | Propuesta | A favor | En contra |
|---|---|---|---|
| Gemini | Pensar en recorridos completos del usuario (alta → cobro → caja → reporte), no en parches sueltos. | Encuentra los defectos que aparecen entre módulos. | No define pasos por tarea. |
| Claude | Issue → rama → pedido de integración (PR) → pruebas automáticas → revisión de otro agente → integración. **Dos carriles:** liviano (texto, botón) y completo (plata, permisos, datos, despliegue). | El trámite es proporcional al riesgo. | Hay que decidir en qué carril cae cada tarea. |
| Codex | Seis pasos: definir, investigar, preparar la aceptación, implementar, revisar y entregar. Límite de trabajo en curso: si se acumulan entregas sin revisar, primero se revisa. | Completo y seguro. | Claude objetó que para cambios chicos vuelve a ser burocracia. |

**Choque:** seis pasos para todo (Codex) o dos carriles según el riesgo (Claude).
**Decisión de Carlos:** _pendiente_

## 5. Quién hace qué entre los agentes

**En criollo:** si cada agente tiene un oficio fijo o se rota.

| Agente | Propuesta | A favor | En contra |
|---|---|---|---|
| Gemini (original) | Roles fijos: uno diseña la lógica, otro programa y otro audita. Durante el debate lo retiró en favor de rotar. | Especialización. | Si el auditor falla o se queda sin crédito, se cae todo. |
| Claude | Cualquiera construye y otro verifica, asignado en el issue. | Resistente a que falte un agente. | Menos especialización. |
| Codex | Un responsable y un revisor por tarea. | Igual que Claude. | — |

**Choque:** roles fijos o rotativos. Lo que no se discute: **el que construye no verifica.**
**Decisión de Carlos:** _pendiente_

## 6. Que los agentes no se pisen

**En criollo:** en Wings los tres trabajan en la misma carpeta. Ejemplo de hoy (27/10): la carpeta compartida
quedó cambiada a la rama `gemini/poda-documental`, y cualquier otro agente que trabaje ahí está parado sobre
esa rama sin saberlo.

| Agente | Propuesta | A favor | En contra |
|---|---|---|---|
| Gemini | Base de pruebas propia por agente y un bloqueo para que no corran dos suites a la vez. | Probado en Wings. | No separa los archivos. |
| Claude | Cada agente en su propia copia de trabajo (*worktree*) y su rama; se juntan por PR. | Elimina el problema de raíz. | Cada copia necesita su configuración. |
| Codex | Copia, base y archivos temporales propios por tarea. El bloqueo de la suite lo toma el programa que la corre, no el agente mirando procesos. | Cierra también la carrera entre dos arranques simultáneos. | — |

**Choque:** ninguno; se suman.
**Decisión de Carlos:** _pendiente_

## 7. Pruebas automáticas

**En criollo:** qué tipo de pruebas, cuándo corren y cuánto pueden tardar.

| Agente | Propuesta | A favor | En contra |
|---|---|---|---|
| Gemini | Pruebas con navegador real (Playwright). Correr solo las pruebas afectadas por el cambio. Fijar desde el día 1 cuánto puede tardar la suite. Original: arquitectura hexagonal (luego retirada). | Ve defectos de pantalla que hoy no se detectan. | Las pruebas con navegador son lentas y frágiles. |
| Claude | Los cálculos de plata como funciones que no tocan la base (se prueban en milisegundos). La base real solo para recorridos completos. Pruebas rápidas en cada PR y la suite completa antes de desplegar. | Rápido donde importa. | Exige diseñar así desde el inicio. |
| Codex | Pruebas proporcionales al riesgo. Comprobar que el entorno de pruebas esté sano (en Wings, el reloj simulado llegó a interferir). La revisión busca caminos alternativos, no solo repetir las pruebas del autor. | Detecta fallas que la suite en verde esconde. | — |

**Choque:** ninguno de fondo. Queda por definir cuánto peso darle a las pruebas con navegador.
**Decisión de Carlos:** _pendiente_

## 8. Diseño y aprobación visual de Carlos

**En criollo:** cuándo tiene que mirar Carlos una pantalla y cómo se evita que una IA "mejore" el diseño sin
permiso.

| Agente | Propuesta | A favor | En contra |
|---|---|---|---|
| Gemini | Candado de diseño. Captura de celular en un marco de 375 px. Capturas y comparación automáticas. | Carlos ve menos y mejor. | La comparación automática no decide si el diseño sirve. |
| Claude | Pedirle a Carlos que mire **solo si cambió algo** en `resources/` o en un controlador que muestra una vista. Filtro automático, aunque sea un poco amplio. | Saca a Carlos del medio cuando no hace falta. | Puede pedir alguna revisión de más. |
| Codex | Declarar qué pantallas se afectan y comprobarlo. Si hay cambio visible, se muestran las pantallas reales; si no, no se pide aprobación. | Más preciso. | Claude objetó: declarar es texto y se puede olvidar. |

**Choque:** filtro automático (Claude) o declaración comprobada (Codex).
**Decisión de Carlos:** _pendiente_

## 9. Evidencia de pruebas (capturas, informes)

**En criollo:** dónde se guardan las pruebas de que algo funciona. En Wings hay 1.389 archivos HTML solo en
`PRU-04`.

| Agente | Propuesta | A favor | En contra |
|---|---|---|---|
| Gemini | Sacarlas del repositorio. | El repo queda liviano. | Si no tienen otro lugar, Carlos pierde el acceso. |
| Claude | Fuera del repositorio **solo si** van a un lugar que Carlos abra desde cualquier máquina. | No le rompe el flujo a Carlos. | Hay que elegir ese lugar. |
| Codex | Clasificar antes de mover: lo temporal va a resultados de las pruebas automáticas; lo que respalda una decisión se conserva vinculado a la tarea. | No se pierde lo importante. | — |

**Choque:** sacar todo (Gemini) o clasificar y garantizar el acceso primero (Claude y Codex).
**Decisión de Carlos:** _pendiente_

## 10. Documentación

**En criollo:** qué documentos existen y cómo se evita que mientan.

| Agente | Propuesta | A favor | En contra |
|---|---|---|---|
| Gemini | Documentación generada desde el código y desde los mensajes de commit. | Menos texto a mano. | Los mensajes de commit no explican decisiones. |
| Claude | Solo tres tipos: contratos (como pruebas cuando se pueda), decisiones (una página cada una) y procedimientos (servidor, despliegue, respaldo). El método completo, en 100 líneas como máximo. Toda cifra lleva su fuente. | Poco que mantener. | Cuesta en proyectos con mucha regla de negocio. |
| Codex | Una autoridad por dato. Las cifras se generan, no se copian. Antes de construir un informe, acordar qué pregunta responde. | Ningún documento contradice a otro. | — |

**Choque:** ninguno de fondo.
**Decisión de Carlos:** _pendiente_

## 11. Cómo hablamos con Carlos

**En criollo:** Carlos no habla técnico y los agentes tomaron sus palabras al pie de la letra o lo corrigieron.

| Agente | Propuesta | A favor | En contra |
|---|---|---|---|
| Claude | 1) Devolver en una frase lo entendido, con un ejemplo. 2) Si algo no se puede tal cual, ofrecer lo más parecido. 3) Boceto antes de construir una pantalla. 4) Si hay dos lecturas y equivocarse cuesta poco, elegir una y avisar cuál. | Menos preguntas para Carlos. | El punto 4 puede salir mal. |
| Codex | Devolver en una frase. Lo técnico se resuelve entre agentes. Si dos interpretaciones cambian el trabajo, una pregunta concreta con opciones. | Menos riesgo de adivinar mal. | Más preguntas para Carlos. |
| Gemini | Sin propuesta propia en este tema; adhirió a la muestra mínima de Codex. | — | — |

**Choque:** ante una duda, **elegir y avisar** (Claude) o **preguntar con opciones** (Codex).
**Decisión de Carlos:** _pendiente_

## 12. Medir si el método funciona

| Agente | Propuesta |
|---|---|
| Codex | Aprobación en la primera revisión, fallas posteriores, tiempo bloqueado, costo por entrega y cantidad de intervenciones de Carlos. |
| Claude | Costo de arranque por agente y costo de cada agente frente a lo entregado. |
| Gemini | Tiempo máximo de la suite fijado desde el inicio. |

**Choque:** ninguno. Falta definir **quién** mide y **dónde** se ve.
**Decisión de Carlos:** _pendiente_

## 13. Lo que hay que prever desde el día 1

Planteado por Claude y Codex, sin objeciones:
- Respaldo **con restauración probada**.
- Avisos de errores en producción.
- Cómo volver atrás un despliegue.
- Secretos y datos personales fuera del repositorio desde el primer commit.
- Datos de prueba obviamente falsos.
- Que el cliente use un recorrido completo en la primera o segunda semana.

**Decisión de Carlos:** _pendiente_

## 14. La herramienta que arma proyectos nuevos

**En criollo:** lo que pidió Carlos: algo que cree un proyecto con el método y las decisiones ya cargadas.

| Agente | Propuesta |
|---|---|
| Claude | Repositorio plantilla, con instrucciones cortas, bloqueos, atajos, pruebas automáticas y un script de puesta en marcha. |
| Codex | Kit versionado: una base común más una adaptación explícita por proyecto. Que compruebe qué puede hacer cada agente y cada máquina. Probarlo en tareas piloto antes de darlo por bueno. |
| Gemini | Starter kit clonable. |

**Choque a decidir:**
- **Plantilla que se copia:** simple, pero cada proyecto queda solo con sus mejoras.
- **Generador que pregunta datos del proyecto y arma todo:** más trabajo al principio, pero las mejoras se pueden actualizar en todos.

**Decisión de Carlos:** _pendiente_
