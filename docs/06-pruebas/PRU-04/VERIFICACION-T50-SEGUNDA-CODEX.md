# T50 — Segunda verificación de Codex CAB

21/10/2026. Revisión independiente de la segunda vuelta de Gemini, commit `de35ae06d2dd0bb2b9baa7937702c22025d78e1c`. Sin modificar código de producto.

## Resultado y alcance

**H2 y H3 confirmados por pruebas y navegador real.** La inscripción exigible desde el alta concuerda con la decisión de Carlos del 21/10 («Inscripción a partir de hoy») y ALU-007 c. Se encontró una observación adicional de idioma en las previsualizaciones cuando reciben fechas mal formadas; no es la repetición del error 500 de edición ni del desfase de reloj.

La comprobación empezó en la carpeta principal limpia, en de35ae0. Al aparecer la integración de T61 y una suite ajena, se detuvieron nuevas corridas y se trasladó solo la evidencia propia a la copia aislada `t50-segunda-verificacion` (HEAD separado), desde el mismo de35ae0. La suite ajena terminó antes de continuar. No se resolvieron sus conflictos ni se tocaron archivos de Claude. La completa de esta revisión corresponde al corte aislado **1025**, no certifica por sí misma la integración posterior de T61/T50, cuyo contador en main es 1028.

**Suite completa: 1019 aprobadas, 6 omitidas, 8802 aserciones, 981,62 s, salida 0**, en wings_testing_codex, corte 1025. Los 612 archivos de fuente conservaron sus huellas. Monitor: cero procesos de suites ajenas detectados en 186 observaciones cada 5 segundos; no se afirma cobertura continua entre muestras. [Salida completa](evidencia-verificacion-t50-segunda-codex/suite-completa.txt) · [Resultado y fuentes](evidencia-verificacion-t50-segunda-codex/resultado-suite.json) · [Monitor](evidencia-verificacion-t50-segunda-codex/control-procesos-suite.json). El HTML T34 generado se restauró y no se entrega.

## Contraste de la respuesta de Gemini

| Punto | Evidencia propia y conclusión |
|---|---|
| H1 decidido por Carlos | Leídos LOG-CLAUDE y ALU-007 c, línea 389. Alta HTTP con ingreso 01/11 y reloj 20/10: inscripción $5.000, cuota computable $0, total $5.000, estado EN_PLAZO. Cobrar la incluye; Inicio cuenta 1 en plazo y 0 vencidos; Cobranza coincide. El 01/11 la cuota mensual sí empieza a sumar. No se modifican inscripción, plazos ni reglas de mora. |
| H2 edición | PUT JSON con `no-es-fecha`: 422, «La fecha de alta no es una fecha válida.», alumno intacto. Control 06 anterior pasó. El parseo comparativo está protegido por try/catch; bail evita ejecutar el resto de reglas sobre una fecha inválida. |
| Otras puertas | Matriz adicional: cinco entradas inválidas (texto, día inexistente, array, vacío y null) × cuatro rutas = 20 respuestas 422; ninguna 500. Comparación de huellas de todas las tablas antes/después de cada conjunto de cuatro peticiones sin cambios. Alta/edición responden en castellano; las dos previsualizaciones todavía usan mensajes en inglés para estos casos. |
| H3 noviembre simulado | Servidor real con FECHA_SIMULADA=2026-11-01 10:00:00; data-max/max=2026-12-31 y valor inicial 2026-11-01. 01/12 válido, 01/01 rechazado con «La fecha de ingreso no puede ser posterior al 31/12/2026.» en validación y aviso de cuota después del fetch. 31/12 vuelve a ser válido y el aviso queda oculto. |
| H3 reloj real | Servidor 21/10/2026, sin simulación; data-max/max=2026-11-30, valor inicial 21/10. 15/11 válido, 01/12 rechazado con límite 30/11; 30/11 recupera validez. Se observó también el bloqueo transitorio «Esperá a verificar...» antes de terminar la consulta inicial: no se confunde ese estado con un rechazo definitivo. |
| Edición histórica | Registro ficticio con ingreso 10/05/2027: data-max viene de Wings, pero no se agrega max nativo que bloquee su valor original. Si se cambia a 01/01/2027, el navegador rechaza; Guardar muestra «No se guardó» y el límite castellano. Al restaurar 10/05 se limpia la invalidez. No hubo POST/PUT de alumnos en ese recorrido. |
| Pruebas de Gemini | Repetidas en base propia: **12 aprobadas, 55 aserciones, 16,98 s**, salida 0. Coincide con la cantidad y aserciones informadas. |
| Controles anteriores | Repetidos sin cambiar esperados, salvo nombre de clase/ubicación para no sobrescribir evidencia histórica: **15 de 16 pasan**, 117 aserciones, salida 1. La única falla es el viejo esperado AL_DIA de H1, superado por la decisión de Carlos. No se presenta como defecto pendiente. |
| Controles con regla vigente | Copia fechada con H1 actualizado según la decisión y una matriz adicional: **16 aprobados y 1 fallido**, 214 aserciones, salida 1. El único rojo exige castellano en previsualizaciones mal formadas. Los 20 rechazos y las comparaciones de tablas pasan antes de esa aserción final. |
| Build | `npm run build` pasó, Vite 7.3.6, 9,24 s. El build se hizo antes de la integración ajena; los recursos de T50 coinciden con el corte aislado. El recorrido usó esos archivos compilados, no una maqueta. |

### Precisión sobre el código explicado

El resumen dice que cuotaAltaPreview y store reciben bail y try/catch. El diff real agrega **bail** en los cuatro puntos; el try/catch nuevo está en `validationRules` y en la comparación de `inscripcionPreview`. En store y cuotaAltaPreview la validación precede al parseo o delega a la regla que ya captura errores. La conducta de rechazo está comprobada; no hay un try/catch nuevo en todos los métodos.

Se revisaron cuerpos reales de AlumnoWebController, FechaIngresoRegla, previsualizarCuotaAlta, parseo Excel, saldoDeAlumnos/estaImpaga, AppServiceProvider y RelojSimulado, además de los dos JS y el input Blade. Búsqueda y traza con codebase-memory localizaron los consumidores. El índice devolvió cobertura no disponible/metadatos cambiados y el formulario Blade inutilizable; las conclusiones se apoyan en lectura de fuente y ejecución, no en ausencia de nodos. [Cobertura registrada](evidencia-verificacion-t50-segunda-codex/cobertura-indice.json).

## Observaciones complementarias

1. **Idioma de previsualizaciones, reproducido:** GET autenticado a cuota-alta-preview o inscripcion-preview con fecha_alta=no-es-fecha devuelve 422 con `The fecha alta field must be a valid date.`; con vacío/null, `The fecha alta field is required.`. En alta/edición los equivalentes están en castellano. El código de las previsualizaciones no pasa `validationMessages()` a validate; config fija locale es, pero no existe lang/es/validation.php y se usa el fallback en inglés. No se cambió el producto en una tarea de verificación. El límite de una fecha bien formada pero posterior al permitido sí da el mensaje castellano y H3 está corregido.
2. **Auxiliar de Gemini:** `evidencia-t50/segunda-vuelta/preparar-base.php` llama migrate --force, seed y escribe usuario/primera carga sin comprobar primero base y entorno. No lo ejecuté. Para reproducir usé el arnés propio que exige wings_testing_codex antes de escribir. Es una limitación del auxiliar CLI, no una puerta de producción ni un defecto alcanzable por el usuario web.
3. **Capturas de Gemini:** los archivos son PNG reales de 1366×768 y 375×667. La móvil no muestra ni fecha de alta ni aviso del límite; la de escritorio muestra el valor inválido pero no el mensaje. Por eso no bastan, por sí solas, como evidencia visual del aviso. El recorrido propio lo observó y guarda una captura móvil donde sí se lee, además de los estados DOM y respuestas HTTP. No se aprueba diseño ni se altera el aspecto.

## Entrega y estado

**T50 se devuelve a Gemini con observaciones complementarias**: traducir las validaciones de las dos previsualizaciones y proteger el auxiliar de preparación por base/entorno antes de cualquier escritura. H2 y H3 quedan confirmados; no se reabre H1 ni se pide otra decisión de negocio. No se corrige producto ajeno en esta verificación. La suite permanente queda verde; el control adicional de idioma queda rojo y visible.

La [comparación con main](evidencia-verificacion-t50-segunda-codex/comparacion-main.json) confirma los mismos blobs de los diez archivos materiales de T50 y el mismo cuerpo de previsualizarCuotaAlta. La diferencia de archivo bruto de Blade era solo CRLF/LF. La corrida aislada no se presenta como una completa del main integrado de 1028 pruebas.

## Evidencia y límites

Carpeta [evidencia propia](evidencia-verificacion-t50-segunda-codex/): controles fechados, compatibilidad con los 16 anteriores, estados por caso, recorridos de ambos relojes, peticiones reales con base/fecha/límite, build, suite y control de exclusividad/fuentes. Usuarios, alumnos, DNI, email e importes del arnés son ficticios. No se consultó ni escribió la base del club, wings, wingstest, servidor ni bases de otros agentes. No se ejecutó el auxiliar sin guardia de Gemini. Servidores auxiliares detenidos y viewport restablecido. Se conservan tres capturas útiles, con sus dimensiones exactas en el manifiesto: escritorio 1351×989 del viewport 1366, aviso móvil 375×1000 y edición 360×960 del viewport 375. Dos capturas de octubre quedaron con un encuadre transitorio incorrecto de la herramienta y se descartaron: no se usan para juzgar el diseño. Octubre queda comprobado por DOM, respuestas y recuperación de validez. No se redimensionan imágenes para aparentar un ancho.

El alcance del navegador es Chrome, alta y edición histórica a los anchos registrados. No se afirma recorrido de todos los navegadores ni selección por calendario emergente. El aviso se verificó después de la respuesta asíncrona y la recuperación después de corregir el valor, no por contar atributos en HTML.

### Tabla de Carlos y Claude — propuesta, sin editarla

ALU-007 c ya contiene la decisión correcta: no se propone reabrir DU-06 ni tocar su esperado. En `catalogo/ALU.json`, `fuente` (línea 394), todavía figura «sin tocar Blade» y la devolución anterior. Propuesta para Carlos/Claude: «Segunda vuelta de Gemini de35ae0: fecha mal formada en edición rechazada con 422, input con límite/default del reloj Wings y mensaje específico conservado tras previsualización. Verificación independiente Codex CAB 21/10: H2 y H3 confirmados, inscripción desde el alta conforme a Carlos; ver VERIFICACION-T50-SEGUNDA-CODEX.md y observaciones complementarias». No se cambian la copia ni la tabla viva ni respuestas DU.

Guardianes finales en main integrado: **7 aprobados, 34 aserciones, 0,43 s**, salida 0. Validan tablero generado y contador **1028**, sin cambiar el corte **1025** de la completa aislada. [Salida](evidencia-verificacion-t50-segunda-codex/guardianes-documentales.txt). No se agregaron pruebas permanentes ni se cambió su contador por los controles externos.

Para repetir los controles desde la raíz del repo (solo cuando no haya otra suite):

```powershell
$env:DB_DATABASE='wings_testing_codex'
$env:APP_ENV='testing'
$env:APP_BASE_PATH=(Get-Location).Path
php vendor/phpunit/phpunit/phpunit --configuration phpunit.xml --colors=never --testdox docs/06-pruebas/PRU-04/evidencia-verificacion-t50-segunda-codex/ControlT50SegundaTest.php
```

Salida esperada actual: 1 solo por los mensajes de previsualización en inglés; no convertir ese control en verde quitando la aserción. La completa se reproduce desde el corte de35ae0 con `ejecutar-suite.ps1`; en main integrado cambia el número de pruebas. El auxiliar propio comprueba base/entorno y requiere usuarios/alumnos vacíos; no borra datos para prepararse.
