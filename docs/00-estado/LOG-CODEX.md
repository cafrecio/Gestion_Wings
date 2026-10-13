# Wings — Bitácora activa de CODEX

## 2026-10-13 — ORDEN DE CARLOS PARA LOS TRES AGENTES — esto no puede volver a pasar

Escrito por Claude CyE en las tres bitácoras por orden de Carlos («ponelo en todas las
bitácoras como algo que no puede pasar»). Regla completa en `AGENTS.md` §5-bis.

**1. Prohibido escribir JavaScript dentro de una vista.** Ni `<script>` sin `src`, ni
`onclick` u otro atributo de evento. Va en un archivo de `resources/js/`. Antes de
escribir, buscar en `ds-app.js` si ya existe. El botón de mostrar la contraseña del
ingreso se escribió adentro de la pantalla el 21/07 y ahí sigue, aunque `ds-app.js` ya lo
resuelve para el formulario de usuarios. Cada pantalla así rompe la política de seguridad
y manda un aviso al servidor en cada carga. Quedan 11 bloques en 10 vistas: los saca T73.

**2. Una limpieza no queda «en avance» sin dueño ni fecha.** Sacar el código de las
vistas empezó el 13/09 y quedó a medias un mes, sin tarea en el tablero. Si un trabajo se
corta, queda una tarea T con lo que falta.

**3. Un número en un documento se corrige cuando cambia.** `ESTADO-ACTUAL` y
`PLAN-PRODUCCION` decían «19 bloques en 17 vistas» y `AGENTS.md` «24», cuando eran 11.
Quien baja el número en `CspSinCodigoIncrustadoTest` lo baja también en los documentos.

## 2026-10-13 — Codex CAB — Limpieza de temporales propios y evidencia portable

Carlos pidió subir lo útil y eliminar lo descartable, solo propio y sin afectar el producto.
Comprobados los commits publicados en origin/main; sin cambios de producto pendientes.
Preservados 118 recursos históricos de T24 en su evidencia, con igualdad SHA256 comprobada.
Comparador de 18 casos usa esos recursos; pasa sin la copia temporal ni base de datos.
Eliminados 77 borradores/listas/salidas propios, 7 montajes duplicados y copia aislada T24.
Retirado solo el enlace temporal a node_modules; paquete real comprobado intacto. Archivos ajenos excluidos.
[Recursos y reproducción](../06-pruebas/PRU-04/evidencia-t24/antes/RECURSOS-HISTORICOS.md). Sin producto, sitio, reloj ni suite.
Publicar evidencia y registro; T27 ya figura cerrada por Claude, esta limpieza no altera su estado.

## 2026-10-12 — Codex CAB — T27, aprobación visual final de Carlos

Carlos aprobó Historial corregido: «Esta OK ahora» y «Sí, aprobado»; el resto ya estaba aprobado.
Registrado en informe, visor, estado y resumen; tablero a_verificar, tiene Claude para las reglas.
No se cierra T27: falta revisión independiente de validaciones, permisos, historial y contraasientos.
Solo documentación; enlaces y diff comprobados, sin suite ni cambios de aplicación.
[Informe y evidencia](../06-pruebas/PRU-04/IMPLEMENTACION-T27.md). Sin sitio de prueba/reloj/despliegue.

## 2026-10-12 — Codex CAB — T27, alineación de Historial solicitada por Carlos

Carlos aprobó el resto de las vistas; señaló $1,250,000.50 desalineado en Historial celular.
La columna de 120 px dejaba el texto 15,6875 px más a la derecha; pasó a 176 px.
Sin reglas, cálculos ni otro cambio visual; se mantienen signos y dos decimales.
Control de geometría rojo antes, verde después: diez importes, escritorio/marco360, login; 4 visitas/0 fallos.
Respuestas reales recapturadas en base propia: 1 prueba/33 aserciones; vistas compilan, sintaxis correcta.
Suite completa propia: 687 aprobadas/2 omitidas, 6588 aserciones, 501,04 s; siguen siendo 689 pruebas.
[Informe y captura corregida](../06-pruebas/PRU-04/IMPLEMENTACION-T27.md). Sin sitio de prueba/reloj/despliegue.
Sigue: Carlos revisa Historial corregido; después otro agente verifica reglas. T27 a_verificar, sin autocierre.

## 2026-10-12 — Codex CAB — T27 etapa 2, implementación y comprobación

Carlos aprobó visual y reglas: editar con historial, eliminar con contraasiento, motivo opcional y cualquier mes.
Reporte del mes original; cajas validadas/liquidaciones pagadas solo consulta; sin pagar, edición existente.
Original, contraasiento e historial atómicos; versión/clave impiden sobrescritura y reenvío duplicado.
Historial corregido: egreso directo ya no aparece como entrada; 13 pruebas rojas antes del cambio.
Propias 16/168 y dos conexiones 2/24 verdes; captura Laravel 1/33; 53 PNG, marco360/login, 36 visitas sin fallos.
Script de Cashflow fuera de Blade; guardián CSP de 13 a 12. Suite propia 687/2, 6588 aserciones, 536,25 s; 689.
[Informe y límites](../06-pruebas/PRU-04/IMPLEMENTACION-T27.md). Solo wings_testing_codex; sin sitio de prueba/reloj/despliegue.
Sigue: Carlos aprueba capturas finales; después otro agente verifica las reglas. No se autocierra.

## 2026-10-12 — Codex CAB — T27, etapa 1: propuesta de entrada única

Relevados directos, cobros/inscripción, aportes/retiros, liquidación y cajas.
Propongo Cashflow: acceso por fila y cajas todavía editables en sección separada.
Carlos pidió íconos de acciones en la tabla, sin botón Corregir; aplicados también a las tarjetas móviles.
VALIDADA no admite rechazar hoy; liquidación pagada protegida. Decisiones pendientes en la propuesta.
Maqueta Blade aislada, controlador/datos de Laravel reales; no rutas ni correcciones nuevas en producción.
Ensayo propio: 1/19, financieros iguales tras GET; 403 de otros roles. Chrome: 20 visitas/0 fallas, 32 PNG.
Escritorio/marco360 y login. Sin base del club, sitio de prueba/reloj/despliegue.
[Propuesta, preguntas y capturas](../06-pruebas/PRU-04/PROPUESTA-T27.md). Etapa 2 no empezada.
Carlos aprobó la propuesta visual (12/10): «Sí, apruebo la propuesta visual».
Edición: «Modificar el registro y conservar historial», anterior/nuevo.
Eliminación: «Conservarlo y crear un asiento en contra». Motivo opcional en ambos casos.
Cualquier mes anterior, sin límite. Caja validada: «Solo consulta».
Pendientes liquidación pagada y mes cerrado/fecha del contraasiento.
T27 en_curso, tiene Carlos: responde las decisiones restantes antes de implementar.

## 2026-10-12 — Codex CAB — T30, porcentaje junto al rubro en celular

Carlos marcó el desborde de la leyenda y pidió porcentaje junto al título.
Nombre/porcentaje arriba, importe debajo a 360; escritorio conserva porcentaje junto al monto.
Misma fórmula y formato; texto del importe móvil a 0,85 rem para que entre completo.
Control nuevo de límites de la leyenda y fuentes cargadas: detectó dos fallos anteriores.
Revisión: 563 controles/0 fallas, 162 PNG y login; respuestas reales en base propia.
Suite propia: 669/2, 6395 aserciones, 530,97 s; 671. Sin sitio de prueba/reloj/despliegue ni cierre.
[Captura de celular](../06-pruebas/PRU-04/evidencia-t30/revision-porcentajes-ultima/capturas/reportes-millones-marco-360.png).
Carlos aprobó todas las vistas: «El resto de las vistas OK» y «Esta OK» (12/10). Solo falta Claude para la regla. [Informe](../06-pruebas/PRU-04/IMPLEMENTACION-T30.md).

## 2026-10-12 — Codex CAB — T30, centavos y formato compartido, a verificar

Inventario completo; $48,000.50 y siempre dos decimales por decisión de Carlos.
Formato común PHP/JS conectado a pantallas, avisos, PDF nuevos, mail/Telegram y Excel numérico.
Gráficos y PDF ya emitidos conservados; cálculos/datos/validación T24 sin cambios.
T28 independiente publicado primero en e1e73d8; T30 después, solo formatos de Cashflow.
Suite propia 669 aprobadas/2 omitidas, 6395 aserciones, 585,49 s; 671 pruebas.
Matriz 39 pantallas, 162 PNG: 563 controles/0 fallas; T24: 1351/0, 100 PNG. Marco360/login y adelantados.
[Inventario, visor y límites](../06-pruebas/PRU-04/IMPLEMENTACION-T30.md). No se cierra ni despliega.
Carlos aprueba capturas; después otro agente verifica regla. Sin sitio de prueba ni reloj.

## 2026-10-12 — Codex CAB — T24, revisión visual aprobada por Carlos

Carlos pidió corregir adelantado desbordado, íconos, desglose y botones móviles a la derecha.
Adelantado arriba del mes a 360; inscripción/cuotas en dos líneas, cuota con dos decimales.
Íconos en movimientos/entregado/configuración; acciones de campos/reglas y liquidación a la derecha.
Repetición real detectó que el cierre general retiraba el aviso T24; ahora respeta su temporizador.
Suite propia 641/2, 6075 aserciones, 448,36 s; 643 sin cambio de cantidad. Navegador 1351/0 fallas, 100 PNG.
Build/sintaxis/vistas correctos; respuestas reales de Laravel y assets fijos, marco360/login. Sin sitio de prueba/reloj/deploy.
[Propuesta, evidencia y límites](../06-pruebas/PRU-04/IMPLEMENTACION-T24.md). Carlos aprobó `be5ad9a`: «Ahora OK» (12/10). Regla a verificar por Claude; T24 sigue a_verificar.
Archivo anterior íntegro: [corte antes de esta revisión](../99-archivo/bitacoras/2026-10-12/LOG-CODEX-ANTES-T24-REVISION.txt).

## 2026-10-11 — Codex CAB — T24 implementada, a verificar por Carlos y después otro agente

16 pantallas/modos con Rectificar T23, filas nuevas de Grupos/adelantados y alta de plan por pedido directo.
Fuentes reales y rutas leídas tras pull actualizado; lector Excel ejecutado aislado, sin base.
Carlos: coma decimal aceptada; mostrar 48,000.50; tercer decimal recortado a dos, rojo y aviso de 4 s sin bloquear.
Componente/parser comunes y regla PHP; guardados directos sin redondear inválidos. Excel, porcentajes y cálculos conservados.
Coordinación T23: campo de rectificación incluido; cambios T24 del controlador compartido entraron en 03aaf11 de Gemini.
Suite completa propia 641/2, 6075 aserciones, 428,69 s; 643 pruebas. Navegador 1292 controles/90 PNG, assets fijados.
18/18 campos anteriores detectan regresión sobre dd62c64; 57 pedidos previos registrados. Sintaxis/build/vistas correctos.
[Inventario, visor y límites](../06-pruebas/PRU-04/IMPLEMENTACION-T24.md). T24 a_verificar, Carlos aprueba imágenes y después otro agente regla; sin cierre ni deploy.
Publicar solo archivos propios; trabajo ajeno de Historial/permisos preservado. Sin sitio de prueba ni reloj por Codex.

## 2026-10-10 — Codex CyE — T14 Día 3 recorrido, a verificar por Claude

Objetivo: feriado simulado lunes 12/10; seis tareas por menú/botones, expectativas antes de actuar.
Sandra cancela las dos de las 16; admin Patín Intermedias 17–18; motivo y Cancelar esta, sin serie.
Agenda 3 → 1 → 0; Sin asistencia 33 antes/después, dos páginas sin fechas del 12.
Frena: canceladas de hoy ocultas en Clases aun con Cancelada + fecha, Sandra/admin/Lucía.
Guardar deshabilitado en fichas canceladas vistas; ADMIN ve Activar, no se pulsó; Sandra sin comprobar.
Inicio/Reportes ingresos $164.000/deuda $2.010.000/por pagar $0; Cobranza $2.010.000, 65/0/22/13.
Sueldos Gaitán/Salinas/Quintana $0; Mariela $18.000/6 asistencias. Bloqueos de ingreso, luego pudieron entrar.
Carlos decidió suplencias y Abrir en feriado correctos; fuera de hallazgos. Sin código/base, arreglos, cobros ni reloj.
Chrome real por clics, integrado falló; cinco capturas de fallas. [Informe único](../06-pruebas/PRU-04/DIA-03.md).
T14 abierta, solo paso actualizado; Claude verifica. Conservar tres cancelaciones; cuenta/navegador propios cerrados.

[Archivo íntegro antes de etapa 2 T27](../99-archivo/bitacoras/2026-10-12/LOG-CODEX-ANTES-T27-ETAPA2.txt).
[Archivo íntegro antes de corregir Historial](../99-archivo/bitacoras/2026-10-12/LOG-CODEX-ANTES-T27-HISTORIAL.txt).

[Archivo íntegro antes de la aprobación final](../99-archivo/bitacoras/2026-10-12/LOG-CODEX-ANTES-T27-APROBACION.txt).
[Archivo íntegro antes de la limpieza](../99-archivo/bitacoras/2026-10-13/LOG-CODEX-ANTES-LIMPIEZA.txt).
