# Control integral de Wings

Actualizado el 23/10/2026, Codex CyE. **Consolidación y corrida posterior pendientes de revisión independiente.**

[ABRIR TABLA](CONTROL-INTEGRAL.html) · [Datos y plantilla](control-integral.json) · [Alcance comprobado](RELEVAMIENTO.md)

## Salida a producción y revisión — versión 3

La entrada muestra **Listo · Frena · Falta para producción** y el dictamen documental
«No habilitado para producción». Los requisitos previos salen del tablero, sin presentar sus
descripciones como fallas nuevas reproducidas. Hoy hay 12 tareas prioritarias previas abiertas.

La selección de trabajo reúne **69 casos únicos**: 42 del camino feliz, 10 de protección y
los vinculados a requisitos explícitos de Carlos (hay superposiciones). Está fundada en los flujos
mínimos del plan funcional y PRU-05; espera revisión y no posterga obligaciones ya decididas.
**20 tienen evidencia parcial; 0 tienen firma independiente registrada aquí.** Es el avance visible
de salida; el resto del catálogo permanece como ampliación. No se exige completar los 331 por
el mero hecho de existir, ni se considera que camino feliz permita omitir permisos o integridad.

Cada registro separa `hizo`, `contraste_tecnico_por` y `revision_independiente`. Los 59 registros
actuales tienen esta última **Pendiente**, sin atribuir a Codex una verificación independiente de
su propia ejecución. Un chat distinto de la misma cuenta no alcanza. Puede revisar Gemini, Claude
u otra cuenta de Codex con identidad/evidencia explícitas. `salida.py` rechaza una firma sin
evidencia, identidad o sin confirmación de cuenta distinta cuando el revisor también sea Codex.
La revisión de salida de un caso exige todas sus letras, no una firma sobre una variante parcial.

El visor conserva un único JSON y genera tres páginas enlazadas, sin descargas automáticas:

- `CONTROL-INTEGRAL.html`: resumen, selección de salida, requisitos y Día 4.
- `CATALOGO.html`: los 331 casos con sus letras y marca Salida/Ampliación.
- `DETALLE-TECNICO.html`: métodos, mediciones, controles y recorridos.

El archivo principal anterior pesaba 1.655.776 bytes; el nuevo ronda 85 KB. La reducción de
peso se comprueba en `integridad-visor-v3.json`; **no es una medición de latencia en teléfono**.
Esta versión tiene validación estática de enlaces/datos; no se afirma revisión visual/móvil nueva.
El navegador rechazó observar `file://`; no se eludió esa restricción.

39 recorridos, 14 controles transversales, 331 casos fuente / 1.438 letras de criterio.
59 registros: 16 situaciones humanas, una medición, una observación y 41 métodos automatizados.

| Qué se puede afirmar | Evidencia incorporada |
|---|---|
| Día 4 | 16 tareas: 14 coinciden, 2 parciales (T2/T6); contraste técnico de dinero, alta y asistencia |
| Casos fuente | 21 con evidencia parcial, 310 sin evidencia incorporada; 85 letras parcialmente ejercitadas |
| Casos fuente completos | 0: faltan condiciones, variantes, roles o consecuencias; no convertir tareas en casos completos |
| Seguridad / validaciones dirigidas, 23/10 | 41 métodos aprobados, 331 aserciones, salida 0; base local `wings_testing_codex`, versión `a15d6095` |
| Performance, 22/10 | Una llamada CLI del reporte: 29 SQL, 11,37 ms SQL, 35,83 ms de llamada, 101 alumnos; sin prueba de carga |
| Abierto | D4-OBS-01: diferencia entre AL_DIA y cuota potencial en Cobrar; resolver regla antes de corregir |

**Por qué faltaba seguimiento:** los informes humanos y técnicos no se trasladaron al registro común.
La revisión del 22/10 fue de lectura y conciliación; no incluyó peticiones negativas. Fue una omisión
de este chat. La corrida del 23/10 agrega evidencia actual, no convierte esa omisión en una prueba anterior.
«Sin evidencia incorporada» no significa que nunca existió una prueba histórica: no se importa como
resultado actual una prueba vieja sin revalidar versión, variante y evidencia.

## Orden

1. El control define impactos y criterios. Gemini escribe el día con datos ficticios completos y compatibles con el reloj, los grupos y precios reales.
2. Vincular técnicamente cada situación con INT-xx, caso y letras sin introducir tablas o servicios en el cuento humano. Completar importes esperados antes de ejecutar; una frase como «se guarda bien» no es aceptación.
3. El chat ejecutor opera por menú, botones y formularios. Aquí se inspeccionan después los efectos en código y registros, sin corregir durante el ensayo.
4. Al cerrar cada día, conciliar el libro esperado con pantallas y filas concretas. Repetir al terminar octubre y al iniciar noviembre.
5. Otro agente verifica la ejecución y deja su evidencia. La tabla preparada también espera esa revisión; no se autocierra T14.

## Dos niveles de cobertura

- Los 39 recorridos organizan caminos felices, preparación, cierre mensual y pruebas aisladas. Son una propuesta de cobertura, no relatos ya ejecutados.
- El catálogo enlaza todos los casos de Carlos/Claude. Conserva criterios fuente y estado declarado; agrega seguimiento separado por letra y enlaces a las ejecuciones. Las letras sin evidencia quedan Pendiente. Tener vínculo a un recorrido tampoco prueba todas sus letras o roles.
- Los 141 casos declarados `normal` y `vigente` en la fuente tienen recorrido propuesto. Hay 181 referencias vinculadas en total y 150 sin asignar; estas últimas no están cubiertas por el plan de relatos actual. No confundir esos números con pruebas ejecutadas.
- Seguridad y rechazos esenciales acompañan el camino feliz. Auditoría exhaustiva de T76, volumen, concurrencia y fallos provocados se registran aparte; su alcance antes de producción lo decide Carlos con los resultados a la vista.
- Ante contradicción entre criterio, código y datos: registrar los tres y detener el paso dependiente según AGENTS §6b. No cambiar el esperado para aprobar.
- No se modifica la tabla original ni sus dudas. Si surge una corrección, proponer código, letra, texto actual y propuesto en el informe.

## Qué registra una ejecución

Usar `plantilla_ejecucion` del JSON y agregar una entrada a `ejecuciones`. El ID debe ser único.
Una entrada por situación/variante/rol/día o por método automatizado, vinculando todas sus letras.
Guardar la condición previa y el esperado antes de actuar. La incorporación retrospectiva del Día 4
enlaza el guion original y declara la ausencia de snapshots previos propios; no los inventa.

| Campo | Evidencia exigida |
|---|---|
| Contexto | Día del relato, fecha/hora Wings y real, versión, ambiente, base y rol |
| Datos previos | IDs ficticios, saldos por concepto/medio, período, estado y parámetros aplicables |
| Resultado esperado | Importe, estado y efecto por pantalla/tabla, vinculados a letras del criterio |
| Resultado observado | Lo que se vio por pantalla y los registros concretos después |
| Funcional / datos | Guardado, cálculos, referencias, auditoría e historia; mostrar filas, no solo COUNT/SUM |
| Seguridad | Permitido/rechazado según rol; petición directa en prueba técnica aislada cuando corresponda; rechazo sin cambios |
| Visual | Capturas reales 1366 y contenido a 375; errores, botones, contadores y estado de fila |
| Performance | Tiempo de apertura y guardado, consultas si se midieron, volumen, dispositivo y condiciones de red |
| Evidencia | Ruta de captura, salida de consulta sanitizada o prueba técnica; nunca claves, hashes ni datos reales |
| Dictamen | Coincide/Parcial para el relato; Aprobado/Falló para el método; observación abierta o medición con límites; quién hizo y verificó. El caso fuente mantiene su cobertura independiente |

Una medición no realizada queda vacía/Pendiente. Un agregado no reemplaza mirar al menos cinco filas representativas (o todas si hay menos).
Identificar diferencia funcional sin adjudicar causa o severidad sin reproducir y comprobar alcanzabilidad.

## Controles de dinero

- Saldo de deuda: saldo inicial + cargos nuevos − aplicaciones de pago − perdones; toda anulación/reactivación se explicita por separado.
- Disponible por medio: saldo inicial + entradas − salidas, respetando si son confirmadas o pendientes.
- Transferencia: una salida y una entrada iguales, sin cambiar el resultado económico.
- No equiparar dinero cobrado en octubre con ingreso económico de octubre cuando se paga noviembre por adelantado.
- Inscripción por persona/DNI; cuotas por inscripción deportiva y período. El recibo se vincula al pago exacto.
- Un importe igual en varias pantallas no prueba corrección: cotejar con cálculo propio escrito antes del cobro.

## Seguridad ejecutada después del Día 4

[Salida](evidencia-seguridad-2026-10-23/salida.txt) · [JUnit por método](evidencia-seguridad-2026-10-23/junit.xml) · [Conexión efectiva](evidencia-seguridad-2026-10-23/base-efectiva.json) · [Contexto](evidencia-seguridad-2026-10-23/LEEME.md).

- 41 métodos existentes en seis archivos, leídos antes de ejecutar; sin cambiar sus aserciones.
- Permisos GET de profesor/operativo, anónimo, cuenta inactiva y cuenta protegida; recibo de cuota rechazado a profesor.
- PATCH de verificación de movimiento: operativo/profesor rechazados y marca intacta; ID de otra caja rechazado; pantalla con huella vieja rechazada.
- Validación bloqueada si falta verificar; motivo de rechazo obligatorio y validado; doble llamada de servicio sin duplicar reflejos; fallo simulado de PDF sin exponer traza.
- HTTP interno de Laravel y servicios con fixtures, no Chrome ni ataque al servidor. El entorno testing no acredita CSRF real.
- Pendientes explícitos: POST validar/rechazar de operativa, cancelar/reactivar/reasignar de profesora, XSS/CSRF, sesiones entre dispositivos y cambio de clave, headers/rate limit, concurrencia y carga. No se hereda cobertura de tests no ejecutados.

## Reproducción y actualización

1. `ejecutar-seguridad.ps1` configura conexión local aislada y exige `testing` + base efectiva `wings_testing_codex` antes de correr PHPUnit. Recrea solo esa base descartable; nunca usarlo con datos de trabajo. Si hay otra suite en marcha, esperar según AGENTS.
2. `incorporar-dia04.py` incorpora los resultados documentados del Día 4 y lee el JUnit ya producido: actualiza únicamente los IDs D4/MED-D4/SEG-20261023. Conserva registros de otros días; no ejecuta Wings ni cambia el catálogo.
3. `generar.py` actualiza marcas de salida/revisión y genera las tres páginas desde el JSON y el tablero. Para nuevos días agregar sus registros al JSON y actualizar cobertura; no crear otra tabla con datos independientes.
4. Buscar por código/tarea/rol o filtrar por estado; abrir cada criterio para ver lo cubierto y lo pendiente. Las dimensiones sin medición permanecen pendientes.

## Antecedente de preparación — 21/10 (no es el estado del 23/10)

- Sitio leído por navegador: `https://test.gestionar-te.com.ar`; commit leído por SSH: `56379295a33782858ce9c986e200f13382d61cc4`.
- Reloj leído: hora real del 21/10/2026, 16:24 Argentina. No se cambió. La continuación del cuento empieza el martes 13/10; reloj lo fija el ejecutor al iniciar cada día.
- No se guardaron operaciones de negocio, no se cambió configuración ni se ejecutó la suite para crear documentación.
- Solo hubo sesión ADMIN disponible para el relevamiento. OPERATIVO, PROFESOR, móvil y los formularios completos quedan para ejecución; no se hereda una aprobación de informes antiguos.
- Primera carga/deshacer, pruebas destructivas, concurrencia y carga masiva van a base descartable propia; jamás limpiar wingstest ni producción para completar una fila.
- Porcentaje de mora y destino de avisos deben releerse/coordinarse antes de sus pruebas. Con 0% no se cubre cálculo positivo. Ningún permiso para mandar avisos se deduce de este archivo.

## Antes de producción

No se habilita por fecha objetivo ni por completar solo filas felices. Según PRU-05 y AGENTS §6-ter:

1. Acordar versión candidata y leer la instalada; si cambia código, repetir el área afectada.
2. Cerrar los controles aplicables de operaciones, permisos e integridad; dejar visibles defectos y cobertura pendiente. Carlos decide cualquier postergación con su alcance real.
3. Completar octubre, generación/revisión de noviembre y liquidaciones con conciliación independiente.
4. Suite completa en verde sobre la candidata, base propia y una sola suite por máquina; documentar omitidas y revisión cruzada.
5. Verificar respaldo recuperable, preflight, migraciones/assets y revisión posterior del servidor antes del despliegue autorizado.

No hay despliegue autorizado ni realizado en esta entrega. «Sin errores encontrados en lo cubierto» expresa el resultado posible; «sin bugs» no es una garantía verificable.
