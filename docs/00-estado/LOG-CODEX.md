# Wings — Bitácora activa de CODEX

## 2026-10-16 — Codex CyE — T3 devuelta a Claude, fecha de cierre alterada

Verificado f4e6436: 70 defectos, cero cambios abierto/cerrado y cuerpos intactos; 56 encabezados distintos.
Copia aislada: cinco archivos escritos; tres corrupciones detectadas y CRLF verde. Guardianes 9/31 verdes.
Solo cambiar paso de A6 altera CERRADO 08/10 a 16/10; el guardián sigue verde. Devolver, sin arreglar.
Se pierden en encabezados aprobaciones, doble verificador y motivos de cierre; antecedentes siguen en cuerpos.
Suite propia wings_testing_codex: 2 skipped, 723 passed (6929 assertions); salida 0. T33 concurrente pasa sus diez casos en la suite.
[Informe y evidencia](../06-pruebas/PRU-04/VERIFICACION-T3-CODEX.md). Devuelta, tiene Claude: conservar fecha real y agregar regresión.
Sin cambios de producto, reloj, datos del club ni despliegue; ensayos documentales solo en copia restaurada.

## 2026-10-14 - Codex CAB - T11 segunda vuelta: devolucion documental y entorno bloqueado

Examinado f6bea35: PNG reales 1280x720 y 720x1280, proporcion 1,7778; sin datos personales.
Fuente oficial consultada: solo JPEG/PNG; informe dice WebP y enlaza URL incorrecta. Vuelve a Gemini.
Apache y limites de cache corregidos; despliegue separado por entrega, sin medir el sitio hoy.
Leidos cuerpos de PwaInstalacionTest: no controla MIME real, proporcion ni avisos Chrome; sintaxis correcta.
Suite intentada solo wings_testing_codex: PHPUnit rechaza bootstrap antes de ejecutar; no hay verde propio.
Navegador/Computer Use no inicia; intento CDP local rechazado EACCES. Sin evidencia propia de Manifest.
[Informe](../06-pruebas/PRU-04/VERIFICACION-T11-CODEX-SEGUNDA.md). Tablero devuelto a Gemini, no cerrado.
Sigue: corregir informe; Codex repite Chrome local y suite antes del pase a Carlos. Sin despliegue.

## 2026-10-13 — Codex CyE — T11 devuelta a Gemini, verificación independiente

Chrome 155 del sitio: dos avisos por screenshots de instalación ausentes; criterio sin avisos incumplido.
Motor sin errores de instalabilidad; manifest/PNG/MIME/CSP correctos. Apache comprobado, no Nginx.
Arranque real por tres roles correcto; cookie ADMIN vencida vuelve al login, con autorización expresa de Carlos.
Sin SW ni Cache storage en perfil nuevo; no garantiza ausencia de caché HTTP ni frescura universal.
Suite propia: 720 aprobadas, 2 omitidas, 6892 aserciones, 1089.50 s, wings_testing_codex.
Sin instalación física ni ventana desde ícono verificadas; esas pruebas siguen con Carlos.
[Informe y capturas](../06-pruebas/PRU-04/VERIFICACION-T11-CODEX.md). Devuelto a Gemini: avisos e informe.
Sin arreglos, datos del club, reloj ni despliegue. Chrome aislado retirado; T11 no cerrada.

## 2026-10-13 — Codex CyE — T7 verificada, queda el OK visual de Carlos

Verificación independiente de d4f02d4 (Claude): fuente y cascada del menú conservadas.
Chrome del sitio: ADMIN/OPERATIVO/PROFESOR, 1366 y 360, activo Sin asistencia y contador 33.
HTML real y CSS anterior/actual: ocho parejas menú/error idénticas; estilos iguales salvo fondo inexistente del logo.
Renderer 500 del sitio comprobado con conexión a base bloqueada y cero consultas; sin provocar un fallo HTTP.
Suite propia: 722 aprobadas, 2 omitidas, 6910 aserciones, 1106.41 s; carpeta compartida con cambios ajenos de T2.
APROBADO en Chrome medido; navegador sin color-mix y teléfono físico no verificados. Sin arreglo, datos, reloj ni despliegue.
[Informe y capturas](../06-pruebas/PRU-04/VERIFICACION-T7-CODEX.md). Tablero: tiene Carlos, espera OK visual; no cerrado.

## 2026-10-13 — Codex CyE — T33 implementada, entrega a Claude

DU-25/DU-58: ADMIN perdona inscripción y reactiva cuota/inscripción desde ficha, con motivo 10–500.
Original y parciales intactos; historial de las dos decisiones; bloqueos persona, alumno, deuda/cargo.
31 pruebas propias verdes; suite completa local 736 aprobadas/2 omitidas, 7001 aserciones, 895.64 s.
Incluye 18 pruebas T31 sin publicar; primer intento con 3 fallos propios, corregidos solo en los controles.
Capturas reales 1366/marco360 y login: ocho visitas sin fallos; sin CSS compartido ni archivos de Gemini.
Contrato, contadores y evidencia actualizados; 720 pruebas versionadas al publicar solo T33.
Las tres rutas propias entraron en 602efcc de Claude; comprobadas en HEAD, sin repetir el archivo.
[Informe y capturas](../06-pruebas/PRU-04/IMPLEMENTACION-T33.md). Tablero a_verificar, hizo Codex, verifica/tiene Claude.
Sin sitio de prueba, reloj ni despliegue. Carlos revisa capturas; Claude verifica lógica; no se autocierra.
Corrección pedida por Carlos: inscripción ahora a derecha en ambos anchos, 12 px bajo la línea.
Capturas reales renovadas: ocho visitas sin fallos; seis botones a derecha, cuatro separaciones de 12 px.
Suite de revisión: 734 aprobadas/2 omitidas/2 fallidas, 6981 aserciones, 1186.19 s; T33 31 verdes.
Dos fallos de disponible en CashflowCorreccionesT27Test confirmados por separado; causa sin atribuir.

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


[Entrada archivada al verificar T3](../99-archivo/bitacoras/2026-10-16/LOG-CODEX-T3.md).
