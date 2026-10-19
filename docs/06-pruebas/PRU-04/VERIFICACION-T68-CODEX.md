# T68 — Verificación independiente de lógica

**Dictamen: CON HALLAZGOS.** 19/10/2026, Codex CyE (`Ventas_CyE`).

Se verifica la implementación de Gemini CAB en `e5b4695a`, con DU-57 aclarada por Carlos el 19/10: conservar las secciones y completarlas con datos reales o sus avisos vacíos. La aprobación anterior de retirar secciones no acredita esta versión. El aspecto lo decide Carlos; aquí solo se comprueban funcionamiento y desbordes.

## Alcance y entorno comprobados

- `git pull`: actualizado, HEAD inicial `629c28b90a186dfcddfc8843b23c153af29f54e9`. Las dos vistas, sus dos controladores y `AlumnoSinPlanT56T68Test.php` coinciden con `e5b4695a`.
- Protocolo de continuidad, implementación §2, SIS-012 y contrato de permisos leídos. Toma registrada con el script del tablero; sin edición manual de sus estados.
- Índice consultado y actualizado mediante `codebase-memory`; la cobertura dio metadatos cambiados/parciales, por eso la evidencia decisiva sale de los archivos reales y de peticiones HTTP a Laravel.
- Todas las pruebas de esta verificación usan `DB_DATABASE=wings_testing_codex`; el instrumento independiente comprueba además el nombre de la conexión. Datos sintéticos, sin consultar el servidor ni su base `wings`.
- Sin correcciones de aplicación, checkout, stash, reset ni despliegue. Cambios ajenos preexistentes y posteriores se preservan.

## H1 — El historial del profesor muestra el cálculo original aunque exista un monto final ajustado

**Ubicación:** `resources/views/profesores/show.blade.php:125`.

La fila imprime `total_calculado`. El importe vigente es `monto_a_pagar`, que devuelve `monto_final ?? total_calculado` (`app/Models/Liquidacion.php:147–150`). El detalle y el pago ya consumen ese importe final (`resources/views/liquidaciones/show.blade.php:59`; `app/Services/LiquidacionPagoService.php:112`). La enmienda 09/10 del contrato de Liquidaciones permite el ajuste y exige conservar el cálculo original separado.

**Reproducción:** entrar como ADMIN; liquidación de comisión de septiembre/2026 con cálculo $15,000.00, CERRADA y PENDIENTE; ajustar el total a $18,000.50 mediante la acción de ajuste. Su detalle muestra $18,000.50. Abrir la ficha de su profesor: el historial sigue mostrando $15,000.00, sin indicar que es el cálculo original. El enlace Ver conduce al detalle con el otro importe.

**Alcance demostrado:** información monetaria contradictoria en una ficha alcanzable por ADMIN. No modifica pagos ni demuestra pérdida de dinero. El control independiente exige $18,000.50 y falla en esta versión. [Respuesta real](evidencia-t68-codex/profesor-ajuste.html) · [Captura a 375](evidencia-t68-codex/profesor-ajuste-375.png).

## H2 — Subsiste JavaScript incrustado en la ficha del grupo

**Ubicación:** `resources/views/grupos/show.blade.php:137–138`.

El formulario Eliminar plan conserva `onsubmit="return confirm(...)"`. Es alcanzable como ADMIN si el grupo tiene planes; aparece en el HTML real guardado. No hay bloques `<script>` ni manejadores nuevos en lo agregado por `e5b4695a`; **este manejador ya existía en su padre**. La ficha del profesor no contiene JavaScript incrustado.

Por eso el requisito g, tomado como ausencia total en ambas fichas, **no se cumple**, pero no se atribuye a Gemini una introducción nueva. `CspSinCodigoIncrustadoTest` pasa porque controla que el total existente no crezca (ocho manejadores), no exige cero. No se corrige aquí; queda identificado para la limpieza pendiente T73.

## Comprobaciones por requisito

| Requisito | Evidencia y resultado |
|---|---|
| a. Clases de grupo/profesor | `GrupoWebController::show` (103–124) y `ProfesorWebController::show` (42–63): excluyen canceladas, `fecha >= today()`, fecha y hora ascendentes. Prueba a las 18:00 del 19/10 incluye también la clase de hoy a las 08:00, como exige el corte por día. Ayer y hoy cancelada quedan fuera. Límites actuales: seis clases de grupo y cinco del profesor. |
| a. Consultas | Carga anticipada de profesores en grupo y de grupo/deporte/nivel en profesor. Medición de ambas rutas completas: **15/15 consultas con una clase y 15/15 con más clases y cuatro grupos adicionales**; no crece por fila en esa muestra. [Registro](evidencia-t68-codex/consultas.json). No se infiere rendimiento de producción a partir de esta muestra. |
| b. Roles | Grupo accesible a ADMIN y OPERATIVO. OPERATIVO no recibe Editar, Eliminar ni + Programar clases. Profesor, listado/detalle de liquidaciones, recibo de liquidación, Reportes/Sueldos y Cashflow rechazan OPERATIVO/PROFESOR con 403. Clase compartida abre sin importe de liquidación. Rutas web y middleware contrastados; API continúa deshabilitada en `bootstrap/app.php:17`. |
| c. Estructura del grupo | `$isAdmin` se define en línea 14. El cierre de línea 196 cierra la grilla abierta en línea 31; línea 194 cierra Cronograma. HTML real con datos y vacío: balance de div cero y ningún cierre sin apertura. |
| d. Profesor | Período `MM/AAAA`, orden año/mes descendente e importe sin ajuste correctos; PAGADA/PENDIENTE coinciden con los registros sintéticos. Sin liquidaciones y sin deporte: HTTP 200, avisos vacíos completos. **Importe ajustado: H1.** |
| e. Próximamente | Ausente en los fuentes completos de ambas fichas y en las respuestas verificadas. Las secciones permanecen y muestran los avisos exigidos por SIS-012. |
| f. Prueba original | El caso T68 de `AlumnoSinPlanT56T68Test.php:268–342` sí crea una Clase y una Liquidación, pide las fichas antes/después y comprueba horario, período, importe y los tres avisos vacíos. No se limita a comprobar títulos. No cubre ajuste final, roles, canceladas, hoy, orden ni N+1, y no afirma el texto PAGADA; esos controles adicionales se realizan aquí. |
| g. JavaScript | H2: un onsubmit preexistente en grupo; ninguno en profesor. No se acepta el verde del contador CSP como prueba de ausencia total. |

## Ejecuciones y evidencia

Instrumento propio fuera de `tests/` para conservar la suite permanente: [T68LogicaCodexTest.php](evidencia-t68-codex/T68LogicaCodexTest.php).

```powershell
$env:DB_DATABASE='wings_testing_codex'
php artisan test --filter='AlumnoSinPlanT56T68Test|CspSinCodigoIncrustadoTest'
php vendor/phpunit/phpunit/phpunit --configuration phpunit.xml docs/06-pruebas/PRU-04/evidencia-t68-codex/T68LogicaCodexTest.php
php artisan test
```

- Originales + CSP: **7 aprobadas, 40 aserciones, 32,46 s**. [Salida](evidencia-t68-codex/dirigidas.txt).
- Independientes iniciales: **7 casos, 5 aprobados y 2 fallidos, 90 aserciones, 22,760 s**, por H1/H2. [Salida](evidencia-t68-codex/independiente.txt). La primera preparación del instrumento omitía campos obligatorios del profesor; se completó el fixture y se repitió. No se atribuye ese error al producto.
- Ampliación independiente final, con archivos ya compilados: **9 casos, 7 aprobados y 2 fallidos, 105 aserciones, 20,356 s; salida 1**. Conserva H1/H2 y agrega aislamiento de grupos/profesores, límite de cinco liquidaciones, ajuste por POST real y muestras de importe largo/ingreso. [Salida final](evidencia-t68-codex/independiente-final.txt).
- **Suite completa: 912 aprobadas, 4 omitidas, 8 fallidas, 8071 aserciones, 1450,81 s; salida 1, corte 924.** [Salida íntegra](evidencia-t68-codex/suite-completa.txt). Ninguna falla pertenece a la prueba permanente T68. Siete fallas traen explícitamente `Unable to locate file in Vite manifest: resources/js/caja-detalle.js`; la octava agrega los dos HTTP 500 de esa misma pantalla en su matriz de importes.
- Preparación local: `resources/js/caja-detalle.js` ya existía y estaba registrado en `vite.config.js:22`, pero faltaba en el manifest compilado local. `npm run build`: **correcto, 3 min 1 s, salida 0**, sin modificar fuentes. [Salida](evidencia-t68-codex/build.txt).
- Repetición de las tres clases afectadas (`CajaRechazoMotivoT37Test`, `CajaVerificacionMovimientosT36Test`, `MostrarImportesT30Test`) y guardianes (`TableroAlDiaTest`, `TablerosNoDivergenTest`, `DefectosNoDivergenTest`): **36 aprobadas, 390 aserciones, 127,58 s, salida 0**. Las ocho fallas anteriores pasan sin modificar lógica, pruebas ni vistas. [Salida](evidencia-t68-codex/repeticion.txt).
- Sintaxis: instrumento propio y ambos controladores correctos; `node --check` del medidor correcto. `php artisan view:cache` y `view:clear` correctos con `VIEW_COMPILED_PATH` exclusivo en `storage/framework/views-t68-codex`, sin tocar el caché compartido.

No se presenta la suite completa como verde sumando resultados de corridas distintas. No se repite completa tras la reconstrucción: se repiten específicamente las clases que fallaron, con las mismas fuentes de aplicación.

Peticiones reales de Laravel guardadas como HTML y abiertas en Chrome mediante iframe de 375 px dentro de ventana grande; también 1366 px. Se midieron ocho respuestas en dos anchos: **16 controles sin desbordes horizontales, sin div desbalanceados y con ancho real de iframe comprobado**, incluido el login. Incluyen fichas con datos, vacías, sin deporte e importe largo. Los valores de CSRF sintéticos se omiten al guardar; los enlaces a archivos compilados e imágenes apuntan a los originales locales. [Mediciones](evidencia-t68-codex/mediciones.json) · [Reproductor](evidencia-t68-codex/medir.mjs) · [Fuentes iniciales SHA-256](evidencia-t68-codex/fuentes.json) · [Dieciséis fuentes finales](evidencia-t68-codex/fuentes-finales.json). Las doce fuentes iniciales siguen idénticas al terminar; la reconstrucción cambió únicamente archivos compilados ignorados. No se fabricó una maqueta de Wings ni se emitió aprobación estética.

## Comprobado y límites

Comprobados: fuentes reales, rutas alcanzables, respuestas HTTP, filas sintéticas y resultados anteriores. No se comprobó el estado del sitio de prueba o producción, un teléfono físico ni una aprobación visual de Carlos en este turno. Los límites cinco/seis son los del código actual; no se presentan como una decisión nueva de Carlos. No se certifica exhaustivamente cualquier futura ruta financiera: los rechazos cubren los caminos nombrados y el dominio de estas fichas.

**Entrega:** T68 devuelta a Gemini por H1, H2 señalado como antecedente T73; Carlos conserva la revisión visual. Tablero actualizado mediante su script; informe, estado y bitácora se entregan juntos. Sin arreglos de producto ni despliegue. La copia SIS-012 se leyó como referencia; la tabla viva privada de Claude no se modificó.

## Corrección de H1

19/10/2026, Codex CyE. Alcance propio: cambiar exclusivamente el argumento del importe del historial, de `$liq->total_calculado` a `$liq->monto_a_pagar`. Este getter ya devuelve `monto_final ?? total_calculado`; no se cambia el modelo, el pago, el cálculo, los permisos, los estilos ni la estructura de la tarjeta. H2 queda para T73.

La regresión permanente `test_ficha_profesor_muestra_importe_vigente_tras_ajuste_t68_h1` se agregó a `tests/Feature/AlumnoSinPlanT56T68Test.php`. Primero comprueba $15,000.00 sin ajuste; realiza el POST ADMIN real a `web.liquidaciones.ajustar`; confirma cálculo original conservado y total vigente $18,000.50; contrasta el detalle y exige que la ficha muestre el final y no $15,000.00.

- **Antes del cambio:** 1 prueba fallida, 11 aserciones, 11,80 s, salida 1; falla en la ficha porque no contiene $18,000.50. [Prueba roja](evidencia-t68-h1/roja.txt).
- **Después:** selección permanente T56/T68 + ajuste final + CSP + contador documental: **16 aprobadas, 108 aserciones, 14,08 s, salida 0**. [Dirigidas](evidencia-t68-h1/dirigidas.txt).
- Captura mediante la misma regresión y respuestas reales: **1 aprobada, 14 aserciones, 24,963 s**. No se agregó una segunda prueba permanente para capturar. [Instrumento](evidencia-t68-h1/CapturaT68H1Test.php) · [Salida](evidencia-t68-h1/captura.txt).
- **Suite completa: 919 aprobadas, 4 omitidas, 2 fallidas, 8172 aserciones, 1963,93 s, salida 1**, únicamente `wings_testing_codex`, corte 925. [Salida íntegra](evidencia-t68-h1/suite-completa.txt). Fallan `MostrarImportesT30Test::test_un_recibo_ya_emitido_se_conserva_sin_regenerarlo` (diferencia entre PDF generado antes/después) y `PerdonConcurrenteT33Test::test_pedido_inscripcion_espera_commit_y_continua_sin_reintento_ni_timeout` (no detectó al proceso esperando). No se atribuye una causa sin comprobarla ni se corrigen esos módulos dentro de H1. **Repetición aislada: 2 aprobadas, 11 aserciones, 38,77 s, salida 0**, sin cambiar código ni pruebas. [Salida](evidencia-t68-h1/repeticion.txt). No se estableció la causa de las dos fallas de la corrida completa.
- En la carpeta compartida aparecieron cambios visuales paralelos en las dos fichas y en el generador de capturas de Gemini. Se preservan y quedan fuera del alcance de esta corrección. [Fuentes al inicio de la suite](evidencia-t68-h1/fuentes-inicio-suite.json). La suite se ejecuta en esa carpeta compartida; no se certifica aquí el diseño de los cambios ajenos.
- **Commit de implementación: `02b72588`.** Tras los rechazos del control automático, Carlos respondió directamente «Sí, autorizo H1 y su publicación». El commit contiene únicamente la sustitución del importe y la regresión, junto con documentación y evidencia propias; los cambios visuales ajenos se conservan fuera del commit. Guardianes documentales tras la actualización del tablero: **12 aprobadas, 55 aserciones, 0,52 s**, en `wings_testing_codex`.

Sintaxis PHP del test, de la vista y del capturador: correcta. Las vistas compilan y se limpian con `VIEW_COMPILED_PATH` exclusivo (`storage/framework/views-t68-h1-codex`), sin alterar el caché de otros agentes. Cuatro controles de navegador, incluyendo login, a 375 px reales en iframe y 1366 px: sin desbordes. [Mediciones](evidencia-t68-h1/mediciones.json) · [Captura del importe a 375](evidencia-t68-h1/profesor-ajuste-375.png) · [Captura de escritorio](evidencia-t68-h1/profesor-ajuste-1366.png) · [Parche propio previsto, una expresión](evidencia-t68-h1/cambio-previsto.patch).

Las cuatro fuentes registradas al inicio de la corrida permanecen idénticas al terminar. [Hashes finales](evidencia-t68-h1/fuentes-fin-suite.json). No se presenta una repetición aislada como si fuera otra suite completa.

La lógica de H1 se entrega a Claude y la revisión visual corresponde a Carlos mediante [APROBAR-T68.html](APROBAR-T68.html). Sin despliegue ni acceso a la base `wings` del servidor.
