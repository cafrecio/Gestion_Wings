# Wings — Bitácora activa de CODEX

## 2026-10-18 — Codex CAB — T75 consistencia móvil aplicada, entrega en verificación

Carlos pidió aplicar tamaño Cobrar, padding común y posiciones estables en todas las vistas móviles.
CSS solo hasta 640 px y clases en once vistas; controles de ícono/interruptores conservados.
99 casos antes/después a 360/375/1366, dos complementos y 80 variantes reales de paneles/tablas.
Cobrar con/sin Condonar, Filtrar con/sin Limpiar y confirmación con/sin serie conservan posición; Volver de Liquidaciones a derecha.
Suite aislada c2db5d4 y fuentes propias: 846 aprobadas/3 omitidas, 7655 aserciones, 1091.37 s, salida 0.
Dos reglas CSS finales tras la suite: PHP idéntico; recorrido completo repetido y guardianes 9/36 aprobados.
[Entrega y galería completa](../06-pruebas/PRU-04/IMPLEMENTACION-T75-CONSISTENCIA.md); nuevas capturas no se heredan como aprobadas.
Reglas/tokens actualizados; T36/T37 y «Destildar solo los movimientos que cambien» conservados.
Sin lógica de negocio, despliegue ni autocierre; Carlos verifica el aspecto. Gemini no implementar.

## 2026-10-18 — Codex CAB — T75 implementada, revisión visual de Carlos

Regla general móvil a derecha y seis vistas con barras propias; escritorio conservado.
101 fuentes revisadas, 99 casos/297 mediciones por fase, 428 PNG reales; 37 casos cambiados.
Cero desbordes 360/375; 99 escritorios y 18 controles de grillas/Detalle sin diferencias.
Solo CSS/clases; sin JavaScript, reglas ni pruebas de negocio. Build/sintaxis/vistas correctos.
Suite completa final propia: 821 aprobadas, 2 omitidas, 0 fallidas, 7593 aserciones, 908.56 s; salida 0. Primera 819/2/2; aislada 2/20, registradas.
[Informe, inventario y galería](../06-pruebas/PRU-04/IMPLEMENTACION-T75.md); Carlos revisa T75 y 19 casos de controles ajenos a tarjetas/barras.
T36/T37 y sus imágenes aprobadas siguen cerradas; «Destildar solo los movimientos que cambien» conservado.
Gemini: no implementar; anotado en tablero, sin chat disponible para mensaje directo. Sin despliegue/autocierre.
Seguimiento 18/10, Carlos señala tamaños y márgenes distintos: pide registrar tamaño Cobrar, padding común y ubicación estable por repetición en el Design System.
Reglas y checklist actualizados en DESIGN-RULES y wings-design; solo documentación, enlaces/diff revisados. Aplicación uniforme pendiente; T75 no aprobada ni cerrada.

## 2026-10-17 — Codex CAB — T36 implementada localmente, entrega a Claude

Tildes persistentes con autor/fecha/huella, solo ADMIN activo; validar exige todos en servicio.
Resumen redirige a Detalle si faltan; vacías e idempotencia previa conservadas.
Carlos: «Destildar solo los movimientos que cambien»; rechazar conserva los demás.
Detalle/Resumen sin JavaScript incrustado; quedan 8 bloques/8 vistas/8 manejadores (T73).
68 dirigidas/449 aserciones y 9 de cancelación/concurrencia real/125 aserciones aprobadas.
Chrome comprobó persistencia, validación, ambos modales, fallo de red y confirmación.
Suite propia: 818/2/1 T38, 7584 aserciones; tras cbc851e de Claude, repetición integrada 50/314 aprobada; [informe y capturas](../06-pruebas/PRU-04/IMPLEMENTACION-T36.md).
Sin despliegue ni autocierre. Reglas para Claude y aspecto para Carlos; T37 ya cerrada por Claude.

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
[Corrección y capturas](../06-pruebas/PRU-04/DETALLE-CAJA-MOBILE.md): Carlos aprobó ambas imágenes a 375 de `4b050ac`: «Bien ambas imagenes!!»; visual verifica Carlos, solo reglas pendientes de Claude. Suite final con carpeta propia: 784/2, 7395 aserciones, 901,37 s, salida 0; historia de Excel conservada en informe.

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

[Entrada archivada al verificar T3 segunda vuelta](../99-archivo/bitacoras/2026-10-16/LOG-CODEX-T3-SEGUNDA.md).

[Entrada archivada en la tercera vuelta T3/T11](../99-archivo/bitacoras/2026-10-16/LOG-CODEX-T3-T11-TERCERA.md).

[Entrada archivada al iniciar B11](../99-archivo/bitacoras/2026-10-16/LOG-CODEX-B11-ARRANQUE.md).

[Entrada archivada al iniciar T37](../99-archivo/bitacoras/2026-10-17/LOG-CODEX-T37-ARRANQUE.md).

[Orden del 13/10 archivada al iniciar T36; reglas vigentes en AGENTS.md](../99-archivo/bitacoras/2026-10-17/LOG-CODEX-T36-ARRANQUE.md).

[Entrada T33 archivada al entregar T75](../99-archivo/bitacoras/2026-10-18/LOG-CODEX-T75-ARRANQUE.md).

[Entrada T7 archivada al entregar consistencia móvil T75](../99-archivo/bitacoras/2026-10-18/LOG-CODEX-T75-CONSISTENCIA.md).
