# Wings — Bitácora activa de CODEX

## 2026-10-19 — Codex CyE — T68, corrección H1 del importe vigente

Alcance propio: usar monto_a_pagar en el historial; conservar cálculo, estilos, estructura y H2.
Regresión permanente: sin ajuste muestra $15,000.00; POST real ajusta a $18,000.50 y ficha/detalle coinciden, sin el anterior en ficha.
Roja antes: 1 fallida/11 aserciones, 11,80 s; después 16 dirigidas/108 aserciones verdes, 14,08 s.
Captura real 1/14 y cuatro controles de navegador; cambios visuales paralelos de otras tareas preservados, fuera del alcance H1.
Suite: 919 aprobadas, 4 omitidas, 2 fallidas, 8172 aserciones, 1963,93 s en wings_testing_codex. Repetición aislada 2/11 verde sin cambios; causa no comprobada. Corte 925 y contadores al día.
Carlos respondió «Sí, autorizo H1 y su publicación»; preparación de Git autorizada, sin incluir cambios visuales ajenos.
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

## 2026-10-18 — Codex CAB — T75, cinco observaciones corregidas y criterio del resto en consulta

Carlos pidió corregir cinco vistas y mostrar solo esas; se entregan en una galería enfocada.
Fecha/hora a ancho completo, doble padding de Cashflow retirado, importes de Liquidación uniformes,
accesos secundarios de Inicio y Revisión con interruptor, nota y Confirmar; sin cambiar servidor/permisos.
99 respuestas reales/297 medidas sin desbordes; pruebas de campos, importes grandes e interruptor correctas.
Suite propia: 857 aprobadas/3 omitidas, 7692 aserciones, 946.92 s, salida 0; build/vistas/sintaxis correctos.
[Entrega, cinco vistas y límites](../06-pruebas/PRU-04/IMPLEMENTACION-T75-OBSERVACIONES.md).
Barrido del resto: candidatos en Caja/Cobranza/Reportes; Carlos elige tamaño único o jerarquía fija.
T75 a_verificar, tiene Carlos; Claude controla interacción de Revisión. T36/T37 conservados cerrados.
Sin despliegue/autocierre; el aspecto nuevo todavía no está aprobado.

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
