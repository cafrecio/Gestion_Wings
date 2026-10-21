# Control integral de Wings

Preparado el 21/10/2026, Codex CyE. **Pendiente de revisión independiente.**

[ABRIR TABLA](CONTROL-INTEGRAL.html) · [Datos y plantilla](control-integral.json) · [Alcance comprobado](RELEVAMIENTO.md)

39 recorridos, 14 controles transversales, 331 casos fuente. Cero ejecuciones registradas.
Esta entrega prepara la prueba: no afirma que esos recorridos funcionen.

## Orden

1. El control define impactos y criterios. Gemini escribe el día con datos ficticios completos y compatibles con el reloj, los grupos y precios reales.
2. Cada acción del cuento identifica INT-xx, caso original y letras de criterio. Completar importes esperados antes de ejecutar; una frase como «se guarda bien» no es aceptación.
3. El chat ejecutor opera por menú, botones y formularios. Aquí se inspeccionan después los efectos en código y registros, sin corregir durante el ensayo.
4. Al cerrar cada día, conciliar el libro esperado con pantallas y filas concretas. Repetir al terminar octubre y al iniciar noviembre.
5. Otro agente verifica la ejecución y deja su evidencia. La tabla preparada también espera esa revisión; no se autocierra T14.

## Dos niveles de cobertura

- Los 39 recorridos organizan caminos felices, preparación, cierre mensual y pruebas aisladas. Son una propuesta de cobertura, no relatos ya ejecutados.
- El catálogo enlaza todos los casos de Carlos/Claude. Cada uno conserva criterios fuente y estado declarado, pero su revisión actual empieza Pendiente. Tener vínculo a un recorrido tampoco prueba que se hayan cubierto todas sus letras o roles.
- Los 141 casos declarados `normal` y `vigente` en la fuente tienen recorrido propuesto. Hay 181 referencias vinculadas en total y 150 sin asignar; estas últimas no están cubiertas por el plan de relatos actual. No confundir esos números con pruebas ejecutadas.
- Seguridad y rechazos esenciales acompañan el camino feliz. Auditoría exhaustiva de T76, volumen, concurrencia y fallos provocados se registran aparte; su alcance antes de producción lo decide Carlos con los resultados a la vista.
- Ante contradicción entre criterio, código y datos: registrar los tres y detener el paso dependiente según AGENTS §6b. No cambiar el esperado para aprobar.
- No se modifica la tabla original ni sus dudas. Si surge una corrección, proponer código, letra, texto actual y propuesto en el informe.

## Qué registra una ejecución

Usar `plantilla_ejecucion` del JSON y agregar una entrada a `ejecuciones`. El ID debe ser único.
Una entrada por caso/variante/rol/día. Guardar la condición previa y el esperado antes de actuar.

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
| Dictamen | Pendiente, En prueba, A verificar, Aprobado, Defecto, Bloqueado o No aplica con justificación; quién hizo y quién verificó |

Una medición no realizada queda vacía/Pendiente. Un agregado no reemplaza mirar al menos cinco filas representativas (o todas si hay menos).
Identificar diferencia funcional sin adjudicar causa o severidad sin reproducir y comprobar alcanzabilidad.

## Controles de dinero

- Saldo de deuda: saldo inicial + cargos nuevos − aplicaciones de pago − perdones; toda anulación/reactivación se explicita por separado.
- Disponible por medio: saldo inicial + entradas − salidas, respetando si son confirmadas o pendientes.
- Transferencia: una salida y una entrada iguales, sin cambiar el resultado económico.
- No equiparar dinero cobrado en octubre con ingreso económico de octubre cuando se paga noviembre por adelantado.
- Inscripción por persona/DNI; cuotas por inscripción deportiva y período. El recibo se vincula al pago exacto.
- Un importe igual en varias pantallas no prueba corrección: cotejar con cálculo propio escrito antes del cobro.

## Límites y preparación

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
