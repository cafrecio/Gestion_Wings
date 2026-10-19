# Wings — Bitácora activa de CODEX

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

## 2026-10-19 — Codex CAB — T44, Nuevo separado del título

Carlos rechazó Nuevo junto al título en Crear/Editar, celular y escritorio.
Separados en _form; Design Rules §1.1/§6/checklist y skill explicitan la regla en todos los anchos.
Tres respuestas reales y nueve medidas 360/375/1366; seis controles de ubicación y agregar fila correctos.
Sin lógica financiera, JS ni CSS. Suite: 882 aprobadas, 3 omitidas, 7821 aserciones, 1134.95 s en wings_testing_codex, salida 0.
[Capturas, solo dos formularios](../06-pruebas/PRU-04/evidencia-t44-titulo/GALERIA.html).
Carlos aprobó ambas pantallas el 19/10: «Ok, pasan» (verifica visual=Carlos). T44 sigue con Claude para reglas/código; sin otra revisión visual de IA ni despliegue.

## 2026-10-18 — Codex CAB — T44, vigencia de precios y cambios de plan

T44 implementada localmente: historial de tarifas y resolución común por alumno/mes.
Bajas al siguiente mes; subidas con saldo pendiente/parcial ahora, con mes saldado al siguiente.
Varios aumentos por mes: existentes desde el siguiente; altas según su día. Cuotas conservadas.
19 casos nuevos; suite 878 aprobadas/3 omitidas, 7792 aserciones, 1102.93 s en wings_testing_codex.
22 fuentes idénticas al corte; build, sintaxis y vistas correctos. 33 medidas y tres interacciones.
[Entrega y decisiones](../06-pruebas/PRU-04/IMPLEMENTACION-T44.md). Verifica Claude; aspecto Carlos.
Corte 881 métodos; cambios paralelos T34/T67 no certificados. Sin despliegue ni base del club.

## 2026-10-18 — Codex CAB — T75, jerarquía fija elegida y aplicada

Carlos eligió «Jerarquía fija y consistente»; la decisión ya no queda pendiente.
Total 1.1rem/800, resumen 0.9rem/700 y fila 0.8rem/600 en ocho vistas financieras; 73 definiciones.
Colores semánticos conservados, conteos secundarios y grupos móviles apilados cuando no entran.
Expresiones Blade y bloques PHP idénticos a a8ab707; sin JavaScript ni reglas de negocio nuevas.
126 controles positivos/negativos correctos; corregidos 5 px de exceso y padding doble en Cashflow.
Suite: 858 aprobadas/3 omitidas, 7704 aserciones (incluye temporal T39); PHP final idéntico, CSS recapturado.
[Entrega y galería de solo las cinco vistas observadas](../06-pruebas/PRU-04/IMPLEMENTACION-T75-JERARQUIA.md).
Carlos aprobó las cinco vistas: «Esas opciones estan muy bien aprobadas»; verifica visual=Carlos.
T36/T37 conservados cerrados. Solo falta control de la interacción de Revisión por Claude.
Sin despliegue ni autocierre.

[Entrada T36 archivada al corregir H1 de T68](../99-archivo/bitacoras/2026-10-19/LOG-CODEX-T68-H1-ARRANQUE.md).

[Entrada T37 archivada al verificar T68](../99-archivo/bitacoras/2026-10-19/LOG-CODEX-T68-ARRANQUE.md).

[Entrada archivada al verificar T3 segunda vuelta](../99-archivo/bitacoras/2026-10-16/LOG-CODEX-T3-SEGUNDA.md).

[Entrada archivada en la tercera vuelta T3/T11](../99-archivo/bitacoras/2026-10-16/LOG-CODEX-T3-T11-TERCERA.md).

[Entrada archivada al iniciar B11](../99-archivo/bitacoras/2026-10-16/LOG-CODEX-B11-ARRANQUE.md).

[Entrada archivada al iniciar T37](../99-archivo/bitacoras/2026-10-17/LOG-CODEX-T37-ARRANQUE.md).

[Orden del 13/10 archivada al iniciar T36; reglas vigentes en AGENTS.md](../99-archivo/bitacoras/2026-10-17/LOG-CODEX-T36-ARRANQUE.md).

[Entrada T33 archivada al entregar T75](../99-archivo/bitacoras/2026-10-18/LOG-CODEX-T75-ARRANQUE.md).

[Entrada T7 archivada al entregar consistencia móvil T75](../99-archivo/bitacoras/2026-10-18/LOG-CODEX-T75-CONSISTENCIA.md).

[Entrada T11 del 13/10 archivada al revisar observaciones T75](../99-archivo/bitacoras/2026-10-18/LOG-CODEX-T75-OBSERVACIONES.md).

[Entrada T11 del 14/10 archivada al aplicar jerarquía T75](../99-archivo/bitacoras/2026-10-18/LOG-CODEX-T75-JERARQUIA.md).

[Entrada T3 archivada al iniciar T44](../99-archivo/bitacoras/2026-10-18/LOG-CODEX-T44-ARRANQUE.md).

[Entrada antigua archivada al corregir título T44](../99-archivo/bitacoras/2026-10-19/LOG-CODEX-T44-TITULO.md).

[Entrada antigua archivada al verificar T34/T67](../99-archivo/bitacoras/2026-10-19/LOG-CODEX-VERIFICACION-T34-T67.md).

[Entrada B11 archivada al entregar T66](../99-archivo/bitacoras/2026-10-19/LOG-CODEX-T66-ARRANQUE.md).

[Entrada T75 archivada al verificar corrección T34](../99-archivo/bitacoras/2026-10-19/LOG-CODEX-T34-REVERIFICACION.md).

[Entrada T75 archivada al verificar T74](../99-archivo/bitacoras/2026-10-19/LOG-CODEX-T74-ARRANQUE.md).

[Entrada T75 archivada al cerrar T34](../99-archivo/bitacoras/2026-10-19/LOG-CODEX-T34-CIERRE.md).
