# Wings — Bitácora activa de CODEX

## 2026-10-17 — Codex CAB — T37 implementada localmente, entrega a Claude

Motivo de rechazo obligatorio, texto hasta 500 y solo espacios vacío; sin mínimo adicional.
Controlador y servicio protegidos; ambos modales required y error superior visible tras recarga.
Ocho pruebas nuevas; T37/A25 y regresiones: 102 aprobadas/853 aserciones. Suite propia: 784/2, 7395 aserciones, 965,22 s.
Chrome local con ADMIN sintético: cuatro recorridos Detalle/Resumen a 1366/360 correctos.
14 capturas reales inspeccionadas; móvil en marco con DOM de Wings, login de control correcto.
Sintaxis y compilación de vistas correctas; sin JS incrustado nuevo, T36 ni despliegue.
[Entrega y evidencia](../06-pruebas/PRU-04/IMPLEMENTACION-T37.md); a verificar por Claude, sin autocierre.
Carlos por §6b: «Entregar según el prompt; Inicio queda pendiente» (CAJ-029.c).
Seguimiento visual 17/10: desborde corregido (48 visitas y ocho controles); Carlos acepta scroll y modal. Segunda vuelta: botones 2 × 2 a la derecha y pareja Resumen/Volver también; seis visitas 360/375/1366.
[Corrección y capturas](../06-pruebas/PRU-04/DETALLE-CAJA-MOBILE.md): nueva alineación pendiente de Carlos; reglas pendientes de Claude. Suite final con carpeta propia: 784/2, 7395 aserciones, 901,37 s, salida 0; historia de Excel conservada en informe.

## 2026-10-16 — Codex CyE — B11 implementada localmente, a verificar

Motor/configuración/cobro/recibo/perdón/saldos incorporados; 35 pruebas nuevas.
Carlos autorizó «Actualizar los conteos y seguir»: solo CatalogosSeederTest 11/18 → 12/19.
Suite completa wings_testing_codex: 761 aprobadas, 2 omitidas, 7214 aserciones, 1106,83 s; salida 0.
Sintaxis, build y vistas correctos; 31 capturas reales, marcos 360/375 y PDF 0/5 inspeccionados.
Contrato, estados y contadores actualizados; PEN-001 implementado, sin certificar revisión cruzada.
[Entrega y evidencia](../06-pruebas/PRU-04/IMPLEMENTACION-B11.md). Tiene Claude para reglas y pantalla.
Commit local de implementación y evidencia: 363346b; pase posterior registra el hash en el tablero.
Sin publicación ni despliegue: 52e6bdf y descendientes deben permanecer locales.
Carlos: celular «En celular esta bien»; Configuración escritorio «OK, aprobado»; Cobrar escritorio «OK».
Registradas capturas y alcance en la entrega; falta Claude para lógica y resto visual. Solo documentación.

## 2026-10-16 — Codex CyE — T3 tercera vuelta y repetición local T11

T3: arreglo local de Claude comprobado en copia; 6/27 verdes, biblioteca anterior da el aviso esperado.
CLI de cierre B11 solo en copia sin avisos, HTML correcto y ocho hashes del origen intactos. Copia retirada.
Conteo actualizado en cuatro documentos: 728; guardián posterior 1/7 verde, tres guardianes del tablero 11/48.
Suite propia wings_testing_codex: 724 aprobadas, 2 omitidas, 1 fallo de conteo, 6943 aserciones, 1085,72 s.
Descubrió 727 antes de la sexta prueba de T3; no es suite completa verde. c6ef9b6 coincide con lo medido, guardianes 12/55; T3 cerrada.
T11: Manifest local sin errores/avisos, capturas reconocidas, CDP sin errores; tres pruebas/63 aserciones verdes.
Conservado cierre de 512f375 por prueba de Carlos registrada por Claude; sin comprobación física propia.
Corregidos enlace 404 y próximo paso viejo del informe. [T3](../06-pruebas/PRU-04/VERIFICACION-T3-CODEX.md#tercera-vuelta) · [T11](../06-pruebas/PRU-04/VERIFICACION-T11-CODEX-TERCERA.md).
B11 sin empezar: Carlos confirmó mantener T3 → T11 → B11. Sin producto, pruebas existentes, reloj ni despliegue de Codex.

## 2026-10-16 — Codex CyE — T3 segunda vuelta: fechas correctas, aviso al cerrar

318ef2f en copia: A6 conserva cierre; cerrar, editar otro día y reabrir B11 conserva/borra fecha correctamente.
67 encabezados y renglones cerrados restaurados; tres abiertos actuales. 66 fechas coinciden con encabezados.
B15 23/09 aceptable por SERVIDOR.md y registro contemporáneo 031bdec; no por el último cambio solo.
Devuelta a Claude: cerrar B11 emite Undefined array key 2, lib.php:660; guarda, pero no sale limpio.
Pruebas nuevas: 2/18 verdes; en código viejo dos errores por helper ausente, y CLI viejo reproduce fecha falsa.
Suite propia wings_testing_codex: 2 skipped, 725 passed (6947 assertions); salida 0. Sin pruebas en otra base.
[Segunda vuelta y evidencia](../06-pruebas/PRU-04/VERIFICACION-T3-CODEX.md#segunda-vuelta). Tiene Claude: grupo opcional y regresión HTML.
Sin arreglo de código, producto, datos del club, reloj ni despliegue; copia restaurada y retirada. T11 no empezada.

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

[Entrada archivada al verificar T3 segunda vuelta](../99-archivo/bitacoras/2026-10-16/LOG-CODEX-T3-SEGUNDA.md).

[Entrada archivada en la tercera vuelta T3/T11](../99-archivo/bitacoras/2026-10-16/LOG-CODEX-T3-T11-TERCERA.md).

[Entrada archivada al iniciar B11](../99-archivo/bitacoras/2026-10-16/LOG-CODEX-B11-ARRANQUE.md).

[Entrada archivada al iniciar T37](../99-archivo/bitacoras/2026-10-17/LOG-CODEX-T37-ARRANQUE.md).
