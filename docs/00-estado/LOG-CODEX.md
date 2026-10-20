# Wings — Bitácora activa de CODEX


[Entradas anteriores archivadas al continuar T61 desde CyE](../99-archivo/bitacoras/2026-10-20/LOG-CODEX-CONTINUIDAD-CYE.md).

## 2026-10-20 — Codex CyE — T61, integración y recorrido real en curso

Carlos pidió completar T61. Se combina c9b68d2 con main c2721d8d, conservando T52; 956 métodos.
24 dirigidas/239 aserciones verdes en repetición aislada; completa 952 aprobadas/4 omitidas, 8455 aserciones, 1102,57 s, salida 0, wings_testing_codex. Error propio: primera corrida solapada con otra lanzada al diagnosticarla, no válida para entregar.
Revisión de código por subagente independiente aprobada, sin certificar ejecución ni reemplazar el control cruzado del tablero.
Chrome real con base ficticia separada: salida/entrada de $300.000 por formulario; saldos cambian y resultado del club conserva $7.000. Inicio/Reportes/Cashflow contrastados, capturas escritorio guardadas.
Build y PHP correctos. Después, navegador deshabilitado en Work; Carlos mostró el interruptor encendido; reiniciar conexión CUA y pedir navegador integrado devuelve el mismo rechazo. Carlos reinstala dependencias al quedar guardado el avance.
Integración guardada en 758955ef; servidor ficticio detenido. Publicación rechazada por revisión automática, pendiente autorización con destino/contenido. [Control actual](../06-pruebas/PRU-04/VERIFICACION-T61-CYE.md). Pendientes celular, resto de pantallas/operativa, control cruzado y aprobación visual. Sin cierre ni despliegue.

## 2026-10-20 — Codex CAB — T61, implementada; visual pendiente desde CyE

Carlos aprobó rubros «Transferencias internas — salidas / entradas», saldos en Cashflow y sus totales con entradas/salidas.
TRASPASO en ambos tipos; dos movimientos independientes ADMIN, migración y seeder idempotentes.
Origen y explicación del global actualizados; resultado del club, aportes y retiros conservados.
12 nuevas; dirigidas 26/376 verdes. Completa aislada 947/4 omitidas, 8425 aserciones, 901,60 s; fuente intacta, sin suite ajena detectada. Build/vistas/sintaxis verdes.
Chrome pendiente: herramienta deshabilitada pese al interruptor encendido; reiniciar la app y la sesión CUA no lo resolvió. Carlos: recorrido pendiente desde CyE (Codex).
[Informe y texto para T13](../06-pruebas/PRU-04/IMPLEMENTACION-T61.md). T61 a_verificar; lógica por otro agente, visual por Carlos. Sin servidor ni despliegue.
[Entrada T75 archivada intacta](../99-archivo/bitacoras/2026-10-20/LOG-CODEX-T61-ARRANQUE.md).


## 2026-10-20 — Codex CAB — Higiene T61 y continuidad desde CyE

27 copias viejas retiradas de la carpeta principal, previa verificación de respaldo y entrega remota.
Versión final T61 publicada en codex/t61-traspasos, commit c9b68d2; no integrada en main.
Rama T61: completa 947/4 omitidas, 8425 aserciones. Main conserva sus 944 métodos; sin nueva suite por limpieza.
Carlos: recorrido visual pendiente por Codex desde CyE, Chrome local 1366/375; luego integración y control independiente.
Configuración de notificaciones local conservada e ignorada; sin tocar producto ni cambios ajenos.
[Registro e inventario](HIGIENE-T61-2026-10-20.md). T61 a_verificar; sin autocierre ni despliegue.

## 2026-10-19 — Codex CyE — T34, reverificación C1/C2 aprobada

Verifico c017bac3 de Gemini; sin cambios de producto ni diseño.
C1: 20→20 consultas con 2→16 tarjetas, un COUNT; badge 1→0→1 tras PATCH reales.
C2: HORA/COMISIÓN, UPDATE intentado y rollback tras duplicado del mes vigente; filas intactas.
H1/H2 y permisos conservados; generación válida filtra profesor, fecha, cancelada y presentes.
Dirigidas 27/321 verdes, 18,62 s; rápida 820 aprobadas, 4 omitidas, 6657 aserciones, 404.38 s, salida 0.
Completa verde ya ejecutada con la corrección: 935/4 omitidas, 8243 aserciones, 1283,46 s; no se repite.
[Informe, SQL y capturas](../06-pruebas/PRU-04/VERIFICACION-T34-C1-C2-CODEX.md); 10 combinaciones de navegador sin desborde.
T34 cerrada: hizo Gemini/verifica Codex. Visual previo de Carlos conservado; T67 cerrada. Sin despliegue.
Cierre pedido por Carlos: T34 d4f10dd4 y T74 7abd5539 ya publicados, remoto comprobado en 022020fb.
Se entrega también el HTML regenerado por la prueba original, con tokens de prueba omitidos; sin nueva corrida ni despliegue.

## 2026-10-19 — Codex CyE — T74, lógica aprobada y visual aprobado por Carlos

Toma publicada en 5bbf5047 antes de editar. Lógica de Claude c1b761e8/09409f9f contrastada con fuente y HTTP.
Dirigidas 32/251 verdes; gracia, inscripción sola, parcial, adelanto real e inactiva; consultas 24 con 1 y 12 alumnos.
Alumnos/Resumen: diferencia de 21 a 0 px. Carlos observó medidas distintas; retirados estilos manuales de acciones del cajón.
Seis botones principales y Abrir ahora 96 × 32 en escritorio/celular; capturas reales renovadas, 1/5 verde.
[Informe](../06-pruebas/PRU-04/VERIFICACION-T74-CODEX.md) y [galería](../06-pruebas/PRU-04/APROBAR-T74.html), celular completo en tres tramos legibles.
Suite completa 935 aprobadas/4 omitidas, 8243 aserciones, 1283,46 s, salida 0 en wings_testing_codex; cero pruebas ajenas al comenzar, once fuentes registradas idénticas al terminar.
Carlos aprobó: «Apruebo el visual T74 actualizado». Sin controlador, CSS compartido ni servidor; cambios paralelos preservados.
Cierre T74: hizo Claude/verifica Codex para lógica, visual verifica Carlos; entrega en este commit. Sin despliegue.

## 2026-10-19 — Codex CyE — Verificación de corrección T34

Verifico f2b065f7/c5c0f41f; T67 permanece cerrada y visual T34 aprobado por Carlos en fb88bd3c.
H1 y PATCH H2 pasan: estados coherentes; HORA/COMISIÓN, pendiente/pagada, JSON/formulario; filas intactas.
C1: listado 33→75 consultas con 2→16 tarjetas, por contador compartido del provider.
C2: incluir_hoy valida otra clase aun cuando la generación de septiembre se rechaza por duplicada.
Ambos antecedentes anteriores a la corrección; no se demostró bypass de cerrada por ese otro camino.
Selección final 16 pasan/2 fallan por C1/C2, 259 aserciones, 17,49 s; suite completa 921/4 omitidas, 8179 aserciones, 1343,15 s, verde.
[Informe y reproducciones](../06-pruebas/PRU-04/VERIFICACION-T34-T67-CODEX.md#verificación-de-la-corrección). Solo wings_testing_codex; diez controles visuales sin desbordes.
Sin corregir producto ni desplegar; T34 se entrega con hallazgos de c/f para Gemini.

## 2026-10-19 — Codex CyE — T68, corrección H1 del importe vigente

Alcance propio: usar monto_a_pagar en el historial; conservar cálculo, estilos, estructura y H2.
Regresión permanente: sin ajuste muestra $15,000.00; POST real ajusta a $18,000.50 y ficha/detalle coinciden, sin el anterior en ficha.
Roja antes: 1 fallida/11 aserciones, 11,80 s; después 16 dirigidas/108 aserciones verdes, 14,08 s.
Captura real 1/14 y cuatro controles de navegador; cambios visuales paralelos de otras tareas preservados, fuera del alcance H1.
Suite: 919 aprobadas, 4 omitidas, 2 fallidas, 8172 aserciones, 1963,93 s en wings_testing_codex. Repetición aislada 2/11 verde sin cambios; causa no comprobada. Corte 925 y contadores al día.
Carlos respondió «Sí, autorizo H1 y su publicación»; implementación en 02b72588, sin cambios visuales ajenos. Guardianes documentales 12/55 verdes.
[Corrección y evidencia](../06-pruebas/PRU-04/VERIFICACION-T68-CODEX.md#corrección-de-h1). Claude revisa lógica; Carlos visual con APROBAR-T68.html. Sin servidor ni despliegue.

## 2026-10-19 — Codex CyE — Verificación de lógica T68, con hallazgos

Verificación independiente de e5b4695a, con DU-57 aclarada: se conservan las secciones con datos o avisos vacíos.
H1: ficha del profesor muestra cálculo original, aunque el monto ajustado sea otro; detalle muestra el final.
H2: onsubmit preexistente en grupo, identificado para T73; no se atribuye como agregado de Gemini.
Fechas, orden, roles, estados vacíos y consultas contrastados con fuentes y peticiones reales; sin corrección de producto.
Originales/CSP: 7/40; propias: 7 pasan y 2 fallan (H1/H2), 105 aserciones. Suite: 912 aprobadas/4 omitidas/8 fallidas, 8071 aserciones, 1450,81 s. Ocho por compilado local ausente; build correcto y repetición de clases/guardianes: 36/390 verdes. Solo wings_testing_codex.
[Informe y evidencia](../06-pruebas/PRU-04/VERIFICACION-T68-CODEX.md). T68 devuelta a Gemini; visual corresponde a Carlos.
Sin servidor, despliegue, checkout, stash ni reset; cambios ajenos preservados.

## 2026-10-19 — Codex CAB — T66, profesora obligatoria y clases liquidadas protegidas

Única/serie, edición y reasignación exigen al menos una profesora activa del deporte; serie atómica.
Contradicción registrada antes de implementar; Carlos exige cancelar cerrada antes de modificar y deja pagada intocable. Retirada la excepción de confirmación y actualizado contrato §4.d/§4.i.
Motivos, roles y choques conservados; canceladas protegidas. Desactivar única profesora queda en T46; API sigue apagada.
Lectura descartable/sitio de prueba: cero clases sin profesora en ambos; sin reparación ni datos personales.
16 pruebas nuevas; 65 dirigidas/610 aserciones. Suite completa: 920 aprobadas, 4 omitidas, 8155 aserciones, 960,48 s, salida 0, corte 924, solo wings_testing_codex.
Capturas reales: tres vistas y control de ingreso a 360/375/1366; 12 medidas, 19 PNG sin desbordes. Build, sintaxis y vistas correctos.
[Entrega y galería](../06-pruebas/PRU-04/IMPLEMENTACION-T66.md). Toma bc02cef solo tablero; fuentes de la suite conservadas. Carlos aprobó las tres vistas el 19/10: «Vista aprobadas»; no volver a consultar. Claude verifica solo lógica; sin autocierre ni despliegue.

## 2026-10-19 — Codex CAB — Verificación ampliada T34/T67

Revisados 9fbec9b, ae7c7d9 y c5727f0; fuentes y rutas contrastadas, sin cambios de producto.
T34: lista guardada con ausentes dice Pendiente; PATCH del dueño devuelve una clase cerrada/pagada a Sin presentes. No cambia el total; no se demostró doble pago.
T67: controles de DNI por tres roles, homónimos, ceros, DNI ausente, accesos y formulario correctos; visual de Carlos conservado.
26 pruebas específicas: 23 pasan y 3 reproducen los dos huecos T34; 246 aserciones. Clases compartidas y detalle económico conservado comprobados. Navegador: nueve medidas y tres interacciones sin desbordes.
[Informe, límites y reproducción](../06-pruebas/PRU-04/VERIFICACION-T34-T67-CODEX.md). Suite permanente: 882 aprobadas, 3 omitidas, 7823 aserciones, 1399.67 s, salida 0, corte 885, solo wings_testing_codex.
T34 devuelta a Gemini; T67 cerrada por Codex. Fuentes de Clases intactas; cambios paralelos de otros módulos no certificados. Sin despliegue ni mensaje a otros chats.
