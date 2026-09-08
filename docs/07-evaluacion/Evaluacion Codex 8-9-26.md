# Evaluación Codex — Wings — 8/9/2026

**Solo evaluación · Codex CAB · Sin cambios funcionales**

> Actualizado con el cruce verificado de Claude: ver la ampliación al final. Se conservan 22 fichas originales y se agregan 17 puntos, con 21 correcciones o precisiones.

Wings tiene los circuitos principales, pero todavía hay diferencias entre registrar una operación y garantizar que todos sus saldos, comprobantes y mecanismos de recuperación sean consistentes. La prioridad propuesta es comprobar los casos financieros concretos y la recuperación de acceso y respaldos. Los reportes diferidos y las ampliaciones de producto deben conservar su lugar acordado.

## Alcance y calidad de la evidencia

Evaluación del 8 de septiembre de 2026 sobre el checkout main, commit 97fb840067efae495028f3fcf95f3e064c26b3bb. Participaron tres agentes: sistema, seguridad y usuario, con consolidación de Codex CAB. Se leyeron cuerpos de funciones, rutas, vistas, contratos, scripts y ambas bitácoras. Se consultaron los registros de Composer y npm. No se modificó la aplicación, no se consultó ni escribió la base y no se ejecutaron cobros, restauraciones, despliegues, ataques ni la suite. No hubo recorrido visual actual ni conexión al servidor. Los escenarios inferidos de código se identifican como tales. Los conteos históricos de alumnos/deudas y de pruebas no se certifican como actuales.

La revisión estática encuentra caminos posibles; no mide su frecuencia ni demuestra que hayan sucedido. Las prioridades de este informe son propuestas para decidir, no nuevas órdenes ni modificaciones del plan. «Nuevo» significa señalado por esta revisión sin identificarlo como pendiente conocido en las fuentes consultadas; no garantiza que nadie lo haya observado antes. Las 22 fichas no son 22 fallas reproducidas. No se ejecutó la suite porque este trabajo es de evaluación y se comparte el entorno con otra evaluación; el conteo documentado de 129 pruebas / 705 aserciones conserva su fecha histórica. No se localizaron definiciones de CI en el checkout revisado; eso no certifica la configuración remota de GitHub. Los avisos de dependencias cambian con el tiempo.

## Qué revisaría primero

1. Primer cobro con descuento y parcial: posible pérdida de saldo (01), medio del recibo (02) y base del descuento (03).
2. Recuperación real: sesiones después de cambiar clave (07), restauración y ensayo (08–09), copia externa (10).
3. Revisar avisos JavaScript según su uso real (12), sin confundir clasificación del registro con exposición demostrada.
4. Mantener AUD-018 y AUD-020 antes del 25/9 (04–05), y probar la concurrencia pendiente (06).
5. Cumplir las excepciones de cobro/asistencia acordadas (15–16) y explicar qué configuraciones y etiquetas no significan lo que parecen (19 y decisiones).

## Mapa de las 22 fichas

| ID | Mirada | Situación | Tema |
|---|---|---|---|
| 01 | Sistema | Nuevo | Un primer cobro parcial puede borrar saldo pendiente |
| 02 | Sistema | Nuevo | El recibo puede identificar mal el medio de cobro |
| 03 | Sistema | Nuevo | La base del descuento queda matemáticamente incoherente |
| 04 | Sistema | Conocido | Dos pagos simultáneos pueden duplicar una liquidación |
| 05 | Sistema | Conocido | Una comisión histórica depende del alumno actual |
| 06 | Sistema | Conocido | Cobrar y cancelar a la vez todavía necesita una prueba real |
| 07 | Seguridad | Nuevo | Cambiar la clave no expulsa las sesiones abiertas |
| 08 | Seguridad | Nuevo | Restaurar la base no equivale a reconstruir Wings |
| 09 | Seguridad | Nuevo | El ensayo de respaldo certifica menos de lo que anuncia |
| 10 | Seguridad | Conocido | La copia externa puede fallar y el script terminar bien |
| 11 | Seguridad | Conocido | Volver al código anterior no vuelve atrás la base |
| 12 | Seguridad | Nuevo | Dependencias: PHP sin avisos; JavaScript requiere revisión |
| 13 | Seguridad | Conocido | La CSP observa; todavía no bloquea |
| 14 | Seguridad | Nuevo | Un error de recibo puede mostrar detalles internos |
| 15 | Usuario | Conocido | El parcial no siempre pide el motivo ni avisa al dueño |
| 16 | Usuario | Conocido | La asistencia no muestra las advertencias de cobranza acordadas |
| 17 | Usuario | Diferido | Cobranza todavía no es una lista lista para llamar |
| 18 | Usuario | Diferido | Registra movimientos, pero faltan respuestas para dirigir el club |
| 19 | Usuario | Conocido | Cambiar el día de generación no cambia la programación |
| 20 | Usuario | Conocido | Falta la inscripción configurable del alumno nuevo |
| 21 | Usuario | Conocido | Identidad del recibo y ayuda con contraseñas están incompletas |
| 22 | Sistema | Conocido | El estado escrito vuelve a quedar atrás de las decisiones |

## 01 · Un primer cobro parcial puede borrar saldo pendiente

**Sistema · Nuevo · Revisar primero**

**En criollo.** Al cobrar por primera vez con descuento, el ajuste modifica el monto original de todos los períodos incluidos, también el que se paga parcialmente y no lleva descuento.

**Caso y alcance.** Ejemplo de lectura del código: alta el 20/8; agosto y septiembre de $40.000 cada uno. Se cobran agosto al 70% ($28.000) y $10.000 de septiembre. ajustarDeudas puede reducir septiembre a $10.000 y dejarlo pagado, haciendo desaparecer $30.000 de saldo. No se reprodujo en una base.

**Qué se verificó.** PagoCuotaService aplica el porcentaje solamente al mes de alta, pero ajustarDeudas recorre todos los ítems y actualiza monto_original al importe solicitado. El formulario y el controlador admiten importes parciales.

**Evidencia.** [app/Services/PagoCuotaService.php:45](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:45>) · [app/Services/PagoCuotaService.php:642](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:642>) · [app/Services/PagoCuotaService.php:658](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:658>) · [app/Http/Controllers/CajaWebController.php:745](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CajaWebController.php:745>) · [resources/views/caja/cobrar.blade.php:127](<C:/xampp/htdocs/gestion-wings/resources/views/caja/cobrar.blade.php:127>)

**Qué faltaría demostrar o decidir.** Antes de confiar en esa combinación, reproducirla en una base aislada y exigir que septiembre conserve $30.000 pendientes. La corrección previa del descuento por mes no demuestra esta propiedad.

## 02 · El recibo puede identificar mal el medio de cobro

**Sistema · Nuevo · Revisar primero**

**En criollo.** El comprobante puede decir efectivo cuando el nuevo pago fue por transferencia.

**Caso y alcance.** El mismo alumno tiene dos movimientos del mismo importe y día, uno cancelado en efectivo y otro vigente por transferencia. El recibo busca el primero por coincidencias y puede tomar el anterior. Escenario estático, no reproducido.

**Qué se verificó.** La búsqueda usa descripción, importe y fecha, con first(); no utiliza pago_id ni excluye cancelados. El pago sí guarda ese vínculo. La descarga web hace alcanzable el caso.

**Evidencia.** [app/Services/ReciboService.php:266](<C:/xampp/htdocs/gestion-wings/app/Services/ReciboService.php:266>) · [app/Services/PagoCuotaService.php:100](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:100>) · [app/Services/ReciboService.php:39](<C:/xampp/htdocs/gestion-wings/app/Services/ReciboService.php:39>)

**Qué faltaría demostrar o decidir.** Comprobar dos cobros iguales con medios distintos y una cancelación. Cada recibo debe describir su propio pago, incluso al descargarlo de nuevo.

## 03 · La base del descuento queda matemáticamente incoherente

**Sistema · Nuevo · Revisar primero**

**En criollo.** El dinero cobrado puede estar bien, pero el dato que explica de cuánto se descontó queda mal.

**Caso y alcance.** Agosto $40.000 al 70% más septiembre $40.000: total $68.000 correcto; la fórmula guarda monto_base de $97.142,86 en lugar de $80.000.

**Qué se verificó.** Se divide todo el total por el porcentaje, aunque solamente un período tiene descuento. La prueba existente revisa el total final; no prueba esa base. El PDF actual usa monto final: no afirmamos que muestre un total incorrecto.

**Evidencia.** [app/Services/PagoCuotaService.php:539](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:539>) · [tests/Feature/DescuentoPrimerPagoSoloDelMesDeAltaTest.php:134](<C:/xampp/htdocs/gestion-wings/tests/Feature/DescuentoPrimerPagoSoloDelMesDeAltaTest.php:134>) · [app/Services/ReciboService.php:60](<C:/xampp/htdocs/gestion-wings/app/Services/ReciboService.php:60>)

**Qué faltaría demostrar o decidir.** Validar conjuntamente total, base y descuento para meses mezclados. Evitar construir reportes sobre ese dato sin resolver su significado.

## 04 · Dos pagos simultáneos pueden duplicar una liquidación

**Sistema · Conocido · Antes del cierre de profesores**

**En criollo.** La transacción de cada pago no impide que dos solicitudes aprueben a la vez la misma liquidación.

**Caso y alcance.** Dos administradores confirman antes de que el otro termine. Ambos pueden superar la consulta de existencia. No se ejecutó una prueba concurrente.

**Qué se verificó.** La lectura no bloquea la liquidación y el índice del asiento no impone exclusividad. Ruta ADMIN alcanzable. Es AUD-018, ya previsto en el plan antes del 25/9.

**Evidencia.** [app/Services/LiquidacionPagoService.php:31](<C:/xampp/htdocs/gestion-wings/app/Services/LiquidacionPagoService.php:31>) · [app/Http/Controllers/LiquidacionWebController.php:214](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/LiquidacionWebController.php:214>) · [database/migrations/2026_02_01_100006_create_cashflow_movimientos_table.php:25](<C:/xampp/htdocs/gestion-wings/database/migrations/2026_02_01_100006_create_cashflow_movimientos_table.php:25>) · [docs/00-estado/PLAN-PRODUCCION.md:456](<C:/xampp/htdocs/gestion-wings/docs/00-estado/PLAN-PRODUCCION.md:456>)

**Qué faltaría demostrar o decidir.** Mantener la fecha y decisión acordadas. La aceptación necesita solicitudes realmente simultáneas, no dos pruebas ejecutadas una después de otra.

## 05 · Una comisión histórica depende del alumno actual

**Sistema · Conocido · Antes del cierre de profesores**

**En criollo.** Dar de baja o cambiar de deporte a un alumno hoy puede cambiar qué pagos entran en una liquidación de un mes anterior.

**Caso y alcance.** Un alumno pagó agosto y se desactivó en septiembre. Al generar agosto tarde o recalcularlo, el filtro actual puede excluirlo.

**Qué se verificó.** Generación y recálculo filtran pagos mediante deporte y activo actuales del alumno. AUD-020 sigue abierto; alcanza ambos caminos, no solo el recálculo.

**Evidencia.** [app/Services/LiquidacionService.php:142](<C:/xampp/htdocs/gestion-wings/app/Services/LiquidacionService.php:142>) · [app/Services/LiquidacionService.php:468](<C:/xampp/htdocs/gestion-wings/app/Services/LiquidacionService.php:468>) · [docs/00-estado/PLAN-PRODUCCION.md:455](<C:/xampp/htdocs/gestion-wings/docs/00-estado/PLAN-PRODUCCION.md:455>)

**Qué faltaría demostrar o decidir.** Respetar revisión antes del 25/9. Comparar una liquidación histórica antes y después de cambios administrativos posteriores, con la regla histórica que Carlos acuerde.

## 06 · Cobrar y cancelar a la vez todavía necesita una prueba real

**Sistema · Conocido · Revisar primero**

**En criollo.** Puede quedar un pago registrado que no coincida con el saldo aplicado a la deuda.

**Caso y alcance.** La cancelación lee un monto; otro cobro lo incrementa; la cancelación guarda su cálculo anterior y puede pisar el incremento. Es una intercalación posible, no un incidente observado.

**Qué se verificó.** El cobro usa bloqueos de alumno y deuda. La cancelación lee y guarda sin compartirlos. También debe revisarse el cruce con validar caja. Es un caso concreto del pendiente de concurrencia.

**Evidencia.** [app/Services/PagoCuotaService.php:364](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:364>) · [app/Services/PagoCuotaService.php:718](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:718>) · [app/Services/CajaService.php:199](<C:/xampp/htdocs/gestion-wings/app/Services/CajaService.php:199>)

**Qué faltaría demostrar o decidir.** Mantener las mitigaciones operativas acordadas y comprobar este caso con conexiones simultáneas. No declarar todo el módulo inseguro ni afirmar que validar caja carece de protección.

## 07 · Cambiar la clave no expulsa las sesiones abiertas

**Seguridad · Nuevo · Revisar primero**

**En criollo.** Si alguien ya está adentro, cambiarle la contraseña no implementa por sí solo el cierre de esa sesión.

**Caso y alcance.** Una sesión obtenida indebidamente puede seguir autenticada después de que el administrador cambie la clave. No se ensayó con cuentas reales.

**Qué se verificó.** El controlador guarda el hash sin revocar sesiones. Las rutas no habilitan auth.session; el SessionGuard instalado recupera el usuario por su ID. Desactivar la cuenta sí tiene un control de usuario activo en cada petición.

**Evidencia.** [app/Http/Controllers/UsuarioWebController.php:157](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/UsuarioWebController.php:157>) · [routes/web.php:40](<C:/xampp/htdocs/gestion-wings/routes/web.php:40>) · [bootstrap/app.php:14](<C:/xampp/htdocs/gestion-wings/bootstrap/app.php:14>) · [app/Http/Middleware/EnsureActiveUserWeb.php:14](<C:/xampp/htdocs/gestion-wings/app/Http/Middleware/EnsureActiveUserWeb.php:14>) · [vendor/laravel/framework/src/Illuminate/Auth/SessionGuard.php:171](<C:/xampp/htdocs/gestion-wings/vendor/laravel/framework/src/Illuminate/Auth/SessionGuard.php:171>)

**Qué faltaría demostrar o decidir.** Definir y probar un procedimiento de recuperación de cuenta comprometida. No prometer que cambiar la clave corta todos los accesos mientras no se verifique esa revocación.

## 08 · Restaurar la base no equivale a reconstruir Wings

**Seguridad · Nuevo · Revisar primero**

**En criollo.** El respaldo incluye más cosas que las que repone el comando de restauración.

**Caso y alcance.** Después de perder el servidor, el comando EN-SERIO importa el SQL, pero no repone los archivos ni la configuración. Esos elementos siguen dentro del respaldo: no afirmamos que se hayan perdido.

**Qué se verificó.** respaldar.sh incluye configuración y storage/app; restaurar.sh importa wings.sql. Además indica revisar un archivo temporal que el trap elimina al salir.

**Evidencia.** [scripts/servidor/respaldar.sh:19](<C:/xampp/htdocs/gestion-wings/scripts/servidor/respaldar.sh:19>) · [scripts/servidor/restaurar.sh:21](<C:/xampp/htdocs/gestion-wings/scripts/servidor/restaurar.sh:21>) · [scripts/servidor/restaurar.sh:30](<C:/xampp/htdocs/gestion-wings/scripts/servidor/restaurar.sh:30>)

**Qué faltaría demostrar o decidir.** Un ensayo integral debe reconstruir configuración, archivos y base en un entorno aislado y dejar Wings utilizable. Documentar extracción manual es distinto de afirmar restauración automática completa.

## 09 · El ensayo de respaldo certifica menos de lo que anuncia

**Seguridad · Nuevo · Revisar primero**

**En criollo.** Igual cantidad de filas no demuestra que se haya recuperado la misma información.

**Caso y alcance.** Dos bases con igual cantidad de pagos pero importes distintos pasan esa comparación. Una base viva que recibió nuevas cargas después del backup puede no coincidir aunque el backup sea correcto.

**Qué se verificó.** El script compara conteos de 15 tablas contra la base viva. No compara contenido ni archivos y omite pago_deuda_cuota. El mensaje final dice que se restaura completo.

**Evidencia.** [scripts/servidor/restaurar.sh:41](<C:/xampp/htdocs/gestion-wings/scripts/servidor/restaurar.sh:41>)

**Qué faltaría demostrar o decidir.** El ensayo histórico demuestra importación y esos conteos; no se invalida ese resultado. Para ampliar la garantía faltan controles de integridad financiera, archivos y una referencia del momento respaldado.

## 10 · La copia externa puede fallar y el script terminar bien

**Seguridad · Conocido · Revisar primero**

**En criollo.** Se conserva el respaldo local, pero la supervisión puede interpretar que también quedó a salvo fuera del servidor.

**Caso y alcance.** Si rclone falla, se imprime un aviso y el último mensaje devuelve éxito. No se comprobó el canal de alertas del servidor.

**Qué se verificó.** El fallo externo se tolera deliberadamente para no perder el respaldo local; el detalle del error se descarta. El monitoreo ya figuraba como pendiente.

**Evidencia.** [scripts/servidor/respaldar.sh:54](<C:/xampp/htdocs/gestion-wings/scripts/servidor/respaldar.sh:54>) · [docs/00-estado/LOG-CLAUDE.md:308](<C:/xampp/htdocs/gestion-wings/docs/00-estado/LOG-CLAUDE.md:308>)

**Qué faltaría demostrar o decidir.** Distinguir backup local correcto de copia externa fallida, con aviso recibido y responsable. Verificar el servicio real antes de afirmar que existe o no monitoreo.

## 11 · Volver al código anterior no vuelve atrás la base

**Seguridad · Conocido · Antes de otro despliegue con migraciones**

**En criollo.** El mensaje de rollback completo promete más que lo que hace el procedimiento.

**Caso y alcance.** Una migración incompatible termina bien y falla un paso posterior. El rollback puede dejar código anterior con esquema nuevo.

**Qué se verificó.** El despliegue aplica migraciones; rollback recupera código, dependencias, compilación y cachés, pero no restaura esquema ni datos. La bitácora ya registra un caso aditivo sin daño.

**Evidencia.** [scripts/deploy.sh:100](<C:/xampp/htdocs/gestion-wings/scripts/deploy.sh:100>) · [scripts/deploy.sh:141](<C:/xampp/htdocs/gestion-wings/scripts/deploy.sh:141>) · [scripts/deploy.sh:214](<C:/xampp/htdocs/gestion-wings/scripts/deploy.sh:214>)

**Qué faltaría demostrar o decidir.** Definir compatibilidad y recuperación coordinadas para cada cambio de esquema. No recomendar revertir migraciones destructivas automáticamente.

## 12 · Dependencias: PHP sin avisos; JavaScript requiere revisión

**Seguridad · Nuevo · Revisar primero**

**En criollo.** El control actual no da el mismo resultado para las dos familias de dependencias.

**Caso y alcance.** composer audit --locked terminó sin avisos ni paquetes abandonados. npm audit --package-lock-only informó 11 paquetes afectados: 2 críticos, 7 altos, 1 moderado y 1 bajo. Son categorías del registro, no una medición de explotación de Wings.

**Qué se verificó.** Los dos controles consultaron sus registros el 8/9. JavaScript señala axios, concurrently, esbuild, follow-redirects, form-data, nanoid, picomatch, postcss, rollup, shell-quote y vite. Varios son herramientas de desarrollo. Axios además se importa al cliente; tampoco todos sus avisos de Node aplican al navegador.

**Evidencia.** [package.json:1](<C:/xampp/htdocs/gestion-wings/package.json:1>) · [package-lock.json:1](<C:/xampp/htdocs/gestion-wings/package-lock.json:1>) · [resources/js/bootstrap.js:1](<C:/xampp/htdocs/gestion-wings/resources/js/bootstrap.js:1>)

**Qué faltaría demostrar o decidir.** Revisar versión, uso y condiciones de cada aviso antes de elegir actualizaciones y probarlas. No se instalaron paquetes ni se verificó explotación. El aviso de shell-quote requiere datos controlados por atacante en tokens usados para construir comandos; esa cadena no quedó demostrada en Wings.

## 13 · La CSP observa; todavía no bloquea

**Seguridad · Conocido · Según plan de seguridad**

**En criollo.** Esta capa de defensa informa qué bloquearía, pero aún no impide esos recursos.

**Caso y alcance.** No se demostró un XSS. Tampoco se revisaron los encabezados que agrega hoy el proxy.

**Qué se verificó.** El middleware envía Content-Security-Policy-Report-Only. Los otros cinco encabezados están programados. La decisión exige avanzar gradualmente para preservar las vistas con estilos y scripts inline.

**Evidencia.** [app/Http/Middleware/SecurityHeaders.php:11](<C:/xampp/htdocs/gestion-wings/app/Http/Middleware/SecurityHeaders.php:11>) · [AGENTS.md:1](<C:/xampp/htdocs/gestion-wings/AGENTS.md:1>)

**Qué faltaría demostrar o decidir.** Continuar el plan de reporte y comprobación pantalla por pantalla cuando se autorice. No activar una política estricta de golpe ni presentar la etapa de reporte como protección bloqueante.

## 14 · Un error de recibo puede mostrar detalles internos

**Seguridad · Nuevo · Mejora acotada**

**En criollo.** Ante una falla, un usuario autenticado puede recibir el mensaje técnico de la excepción.

**Caso y alcance.** Podrían aparecer rutas o detalles de procesamiento. No se provocó el error ni se observó una fuga de credenciales.

**Qué se verificó.** Los catch devuelven e->getMessage() con respuesta 500. Cuotas está disponible para ADMIN/OPERATIVO; liquidaciones para ADMIN. No es una entrada anónima ni API pública.

**Evidencia.** [app/Http/Controllers/ReciboController.php:80](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/ReciboController.php:80>) · [app/Http/Controllers/ReciboController.php:147](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/ReciboController.php:147>) · [routes/web.php:1](<C:/xampp/htdocs/gestion-wings/routes/web.php:1>)

**Qué faltaría demostrar o decidir.** Comprobar el manejo de fallas en un entorno aislado y separar el mensaje útil al usuario del detalle de diagnóstico.

## 15 · El parcial no siempre pide el motivo ni avisa al dueño

**Usuario · Conocido · Antes de dar por cerrada la prueba humana**

**En criollo.** El contrato promete registrar la excepción; un parcial del mes sin deuda anterior puede pasar sin explicación ni correo.

**Caso y alcance.** El permiso para pagar parcialmente es correcto. Lo que falta es el acompañamiento acordado, no prohibir el cobro.

**Qué se verificó.** El motivo es opcional y se exige solo cuando se omiten deudas anteriores. El aviso también depende de esa condición. El servicio permite pagar menos que el saldo.

**Evidencia.** [docs/02-contratos/Wings-contrato-estadosAlum-cobranza-asistencia-V1.md:301](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/Wings-contrato-estadosAlum-cobranza-asistencia-V1.md:301>) · [app/Http/Controllers/CajaWebController.php:649](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CajaWebController.php:649>) · [app/Http/Controllers/CajaWebController.php:677](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CajaWebController.php:677>) · [app/Http/Controllers/CajaWebController.php:776](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CajaWebController.php:776>) · [app/Services/PagoCuotaService.php:338](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:338>)

**Qué faltaría demostrar o decidir.** Probar parcial sin deuda anterior y comprobar motivo y aviso según contrato. Existe correo por deuda anterior; no es correcto afirmar que no hay notificaciones. Su entrega real no se verificó.

## 16 · La asistencia no muestra las advertencias de cobranza acordadas

**Usuario · Conocido · Antes de dar por cerrada la prueba humana**

**En criollo.** El profesor puede tomar lista, pero no recibe toda la información prometida sobre excepciones de pago.

**Caso y alcance.** La vista muestra plan y exceso semanal, no el estado de cobranza ni el aviso contractual por tercera clase del nuevo sin pagar.

**Qué se verificó.** El controlador prepara alumnos, asistencias y plan; el motivo que solicita corresponde a corregir una lista previa. No es el motivo por la excepción de cobranza.

**Evidencia.** [docs/02-contratos/Wings-contrato-estadosAlum-cobranza-asistencia-V1.md:303](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/Wings-contrato-estadosAlum-cobranza-asistencia-V1.md:303>) · [docs/02-contratos/Wings-contrato-estadosAlum-cobranza-asistencia-V1.md:428](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/Wings-contrato-estadosAlum-cobranza-asistencia-V1.md:428>) · [app/Http/Controllers/ClaseWebController.php:280](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/ClaseWebController.php:280>) · [app/Http/Controllers/ClaseWebController.php:314](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/ClaseWebController.php:314>) · [resources/views/clases/show.blade.php:283](<C:/xampp/htdocs/gestion-wings/resources/views/clases/show.blade.php:283>)

**Qué faltaría demostrar o decidir.** Mantener la decisión de permitir asistir. Verificar visibilidad de la excepción y registro del motivo sin inventar bloqueos.

## 17 · Cobranza todavía no es una lista lista para llamar

**Usuario · Diferido · Después de la prueba humana**

**En criollo.** Para reclamar hay que abrir fichas; el listado no reúne de una vez teléfono, importe, períodos y antigüedad.

**Caso y alcance.** La pantalla permite ver estados y filtrar por deporte y grupo. Eso no equivale al reporte de cobranza prometido.

**Qué se verificó.** El contrato de reportes incluye los datos para gestionar contactos. La vista actual ofrece alumno, deporte, grupo, estado y Ver. El contrato difiere esta etapa hasta terminar la prueba humana.

**Evidencia.** [docs/02-contratos/Wings-Contrato-Reportes-V1.md:48](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/Wings-Contrato-Reportes-V1.md:48>) · [docs/02-contratos/Wings-Contrato-Reportes-V1.md:239](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/Wings-Contrato-Reportes-V1.md:239>) · [resources/views/cobranza/index.blade.php:113](<C:/xampp/htdocs/gestion-wings/resources/views/cobranza/index.blade.php:113>) · [app/Http/Controllers/CobranzaWebController.php:15](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CobranzaWebController.php:15>)

**Qué faltaría demostrar o decidir.** Mantenerlo visible como entrega posterior acordada, sin convertirlo ahora en un bloqueo nuevo.

## 18 · Registra movimientos, pero faltan respuestas para dirigir el club

**Usuario · Diferido · Después de la prueba humana**

**En criollo.** Cashflow existe; todavía no cubre toda la rentabilidad, comparación y seguimiento que pide el contrato.

**Caso y alcance.** Preguntas como qué grupo rinde mejor, cómo venimos contra el mes anterior o quién dejó de asistir no tienen todos los reportes comprometidos.

**Qué se verificó.** El dashboard calcula contadores y Cashflow presenta movimientos y sumas por período/caja. No se localizaron rutas de los reportes adicionales del contrato en las rutas revisadas.

**Evidencia.** [docs/02-contratos/Wings-Contrato-Reportes-V1.md:67](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/Wings-Contrato-Reportes-V1.md:67>) · [docs/02-contratos/Wings-Contrato-Reportes-V1.md:155](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/Wings-Contrato-Reportes-V1.md:155>) · [app/Http/Controllers/WebController.php:64](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/WebController.php:64>) · [app/Http/Controllers/CashflowWebController.php:16](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CashflowWebController.php:16>)

**Qué faltaría demostrar o decidir.** Planificar y aceptar esos reportes en la etapa diferida. Exportar Excel/PDF está fuera de esta versión y no se reclama como deuda.

## 19 · Cambiar el día de generación no cambia la programación

**Usuario · Conocido · Antes de enseñar Configuración**

**En criollo.** La pantalla deja editar un valor que el proceso mensual no consume.

**Caso y alcance.** El usuario cambia dia_generacion_deuda, pero la programación permanece el día 1 a las 06:00.

**Qué se verificó.** La clave se crea en una migración; el controlador edita configuraciones existentes. La tarea programada usa monthlyOn(1, '06:00') fijo. Ya figura en ESTADO-ACTUAL.

**Evidencia.** [database/migrations/2026_05_29_003356_create_configuraciones_table.php:31](<C:/xampp/htdocs/gestion-wings/database/migrations/2026_05_29_003356_create_configuraciones_table.php:31>) · [app/Http/Controllers/ConfiguracionWebController.php:21](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/ConfiguracionWebController.php:21>) · [routes/console.php:12](<C:/xampp/htdocs/gestion-wings/routes/console.php:12>) · [docs/00-estado/ESTADO-ACTUAL.md:210](<C:/xampp/htdocs/gestion-wings/docs/00-estado/ESTADO-ACTUAL.md:210>)

**Qué faltaría demostrar o decidir.** Decidir cómo alinear pantalla y ejecución. Mientras tanto, no enseñar ese campo como un control efectivo de la fecha.

## 20 · Falta la inscripción configurable del alumno nuevo

**Usuario · Conocido · Pedido pendiente**

**En criollo.** El pedido de cobrar $5.000 de inscripción dentro de la regla del nuevo aún no está implementado.

**Caso y alcance.** La primera cuota calcula porcentajes, pero no incorpora ese componente de inscripción configurable.

**Qué se verificó.** La bitácora del 7/9 registra el pedido. El servicio calcula primera cuota y el controlador solo permite editar claves ya existentes.

**Evidencia.** [docs/00-estado/LOG-CLAUDE.md:60](<C:/xampp/htdocs/gestion-wings/docs/00-estado/LOG-CLAUDE.md:60>) · [app/Services/PagoCuotaService.php:536](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:536>) · [app/Services/PagoCuotaService.php:585](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:585>) · [app/Http/Controllers/ConfiguracionWebController.php:21](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/ConfiguracionWebController.php:21>)

**Qué faltaría demostrar o decidir.** Conservar el alcance pedido: importe configurable y dentro de la regla del nuevo. No improvisar un cobro separado ni un valor fijo en código.

## 21 · Identidad del recibo y ayuda con contraseñas están incompletas

**Usuario · Conocido · Pedido pendiente**

**En criollo.** Quedan detalles de entrega que el usuario ya pidió, con avances parciales que hay que reconocer.

**Caso y alcance.** El recibo conserva WINGS textual y colores actuales; falta la identidad del club. El ojo para ver la contraseña ya existe en login, pero no en el formulario de usuarios.

**Qué se verificó.** El logo y la paleta definitivos requieren definición del dueño. La bitácora también registra el favicon pendiente: ese punto documental no se presenta aquí como una comprobación visual actual.

**Evidencia.** [docs/00-estado/LOG-CLAUDE.md:60](<C:/xampp/htdocs/gestion-wings/docs/00-estado/LOG-CLAUDE.md:60>) · [resources/views/pdfs/recibo-cuota.blade.php:22](<C:/xampp/htdocs/gestion-wings/resources/views/pdfs/recibo-cuota.blade.php:22>) · [resources/views/auth/login.blade.php:60](<C:/xampp/htdocs/gestion-wings/resources/views/auth/login.blade.php:60>) · [resources/views/auth/login.blade.php:104](<C:/xampp/htdocs/gestion-wings/resources/views/auth/login.blade.php:104>) · [resources/views/usuarios/_form.blade.php:43](<C:/xampp/htdocs/gestion-wings/resources/views/usuarios/_form.blade.php:43>)

**Qué faltaría demostrar o decidir.** Recibir identidad del club y completar los pedidos autorizados en otra tarea. No cambiar el diseño ahora ni afirmar que no existe ningún logo.

## 22 · El estado escrito vuelve a quedar atrás de las decisiones

**Sistema · Conocido · Antes de otra orden de trabajo**

**En criollo.** Un documento viejo puede hacer repetir una discusión o frenar una tarea correcta.

**Caso y alcance.** ESTADO-ACTUAL conserva que no hay usuarios del club y un despliegue 7abf327; LOG-CLAUDE del 7/9 registra entrega preparada, cuenta del club y 9fdd03d. Esto es una contradicción documental, no una verificación del servidor actual.

**Qué se verificó.** También hay que distinguir el cierre en código de H-DI-01 y el avance parcial del ojo de contraseña de sus anotaciones históricas. AGENTS conserva un conteo de 33 pruebas que no sirve como evidencia de la suite actual.

**Evidencia.** [docs/00-estado/ESTADO-ACTUAL.md:15](<C:/xampp/htdocs/gestion-wings/docs/00-estado/ESTADO-ACTUAL.md:15>) · [docs/00-estado/LOG-CLAUDE.md:20](<C:/xampp/htdocs/gestion-wings/docs/00-estado/LOG-CLAUDE.md:20>) · [docs/00-estado/LOG-CODEX.md:17](<C:/xampp/htdocs/gestion-wings/docs/00-estado/LOG-CODEX.md:17>) · [AGENTS.md:1](<C:/xampp/htdocs/gestion-wings/AGENTS.md:1>)

**Qué faltaría demostrar o decidir.** Conciliar las fuentes y fechas antes de emitir la próxima orden. Esta evaluación registra la diferencia; no cambia estados, planes ni decisiones mientras Claude evalúa en paralelo.

## Qué le prometemos y todavía no entregamos completo

- **Motivo y aviso por cobro parcial:** diferencia contractual vigente (15).
- **Información y excepciones de cobranza al tomar asistencia:** diferencia contractual vigente (16).
- **Lista para reclamar deuda y reportes para dirigir el club:** pendientes expresamente diferidos hasta después de la prueba humana (17–18).
- **Configuración que gobierna la generación:** el valor editable no controla la fecha actual (19).
- **Inscripción y presentación del recibo:** pedidos del 7/9 abiertos; dependen de implementación y de identidad del club (20–21).
- **Recuperación completa y rollback completo:** los mensajes operativos son más amplios que la garantía comprobada (08–09 y 11).

## Lo que ya está corregido o decidido

### Cobranza para OPERATIVO

H-DI-01 está corregido en rutas y menú actuales. PROFESOR sigue rechazado. Falta distinguir esa comprobación de un nuevo ensayo humano de los tres roles.

[routes/web.php:88](<C:/xampp/htdocs/gestion-wings/routes/web.php:88>) · [resources/views/layouts/ds-app.blade.php:72](<C:/xampp/htdocs/gestion-wings/resources/views/layouts/ds-app.blade.php:72>)

### Alumno sin pagos y sin deuda

El servicio prioriza no tener pagos y puede devolver DEUDOR aun con saldo cero. Es el comportamiento aceptado para la prueba inicial, no un defecto nuevo. Debe explicarse antes de usar esa etiqueta para reclamar; el contrato conserva una enmienda pendiente.

[app/Services/CobranzaEstadoService.php:234](<C:/xampp/htdocs/gestion-wings/app/Services/CobranzaEstadoService.php:234>) · [docs/02-contratos/Wings-contrato-estadosAlum-cobranza-asistencia-V1.md:157](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/Wings-contrato-estadosAlum-cobranza-asistencia-V1.md:157>)

### FIFO y asistencia

Se permite omitir meses con el circuito acordado de aviso, motivo y correo. No imponer FIFO global. También se permite asistir con excepciones de cobranza; el faltante es informar y registrar, no impedir asistir.

[app/Http/Controllers/CajaWebController.php:651](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CajaWebController.php:651>) · [docs/00-estado/LOG-CODEX.md:999](<C:/xampp/htdocs/gestion-wings/docs/00-estado/LOG-CODEX.md:999>)

### Carga inicial humana

No agregar seeders para reemplazar la carga del usuario. El importador valida antes de insertar y usa transacción; la reversión exige deuda intacta y sin imputaciones. Probarla antes de cobrar sigue siendo apropiado. No certificamos reversión concurrente.

[app/Services/CargaDeudaInicialExcelService.php:1](<C:/xampp/htdocs/gestion-wings/app/Services/CargaDeudaInicialExcelService.php:1>) · [docs/00-estado/LOG-CLAUDE.md:25](<C:/xampp/htdocs/gestion-wings/docs/00-estado/LOG-CLAUDE.md:25>)

### Caja y cambio de plan

El cambio de plan y el cobro comparten transacción. Validar caja sí bloquea la caja. Los recibos anulados se regeneran con marca de anulación. No repetir esos defectos antiguos como abiertos.

[app/Http/Controllers/CajaWebController.php:692](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CajaWebController.php:692>) · [app/Services/CajaService.php:199](<C:/xampp/htdocs/gestion-wings/app/Services/CajaService.php:199>) · [app/Services/ReciboService.php:33](<C:/xampp/htdocs/gestion-wings/app/Services/ReciboService.php:33>)

### Permisos y autenticación

Hay limitación de intentos de login, regeneración de sesión y cierre con invalidación. Las claves de usuarios requieren mínimo de ocho caracteres y confirmación. El administrador protegido y las suplencias de profesores son decisiones vigentes.

[routes/web.php:30](<C:/xampp/htdocs/gestion-wings/routes/web.php:30>) · [app/Http/Controllers/WebController.php:23](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/WebController.php:23>) · [app/Http/Controllers/UsuarioWebController.php:157](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/UsuarioWebController.php:157>)

### Límites de esta versión

API REST deshabilitada a propósito; recibo no fiscal aceptado; exportación de reportes Excel/PDF fuera de versión. Ninguno se cuenta como incumplimiento.

[bootstrap/app.php:14](<C:/xampp/htdocs/gestion-wings/bootstrap/app.php:14>) · [docs/02-contratos/Wings-Contrato-Recibos-PDF-V1.md:15](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/Wings-Contrato-Recibos-PDF-V1.md:15>) · [docs/02-contratos/Wings-Contrato-Reportes-V1.md:225](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/Wings-Contrato-Reportes-V1.md:225>)

### Decisiones que no se reabren

Menú agrupado el 7/9, subrubros de sueldos automáticos por persona, tratamiento del historial antiguo aceptado por Carlos y CSP gradual. Esta evaluación no propone cambiar esas decisiones.

[docs/00-estado/LOG-CLAUDE.md:48](<C:/xampp/htdocs/gestion-wings/docs/00-estado/LOG-CLAUDE.md:48>) · [docs/00-estado/LOG-CLAUDE.md:76](<C:/xampp/htdocs/gestion-wings/docs/00-estado/LOG-CLAUDE.md:76>) · [AGENTS.md:1](<C:/xampp/htdocs/gestion-wings/AGENTS.md:1>)

## Qué convendría tener como sistema de gestión, sin inventar compromisos

### Recuperar acceso sin depender de una persona

El flujo revisado depende del ADMIN para cambiar la contraseña. Autoservicio y MFA no se localizaron en las rutas de autenticación. No son promesas contractuales constatadas; decidir si el tamaño del club los justifica.

[routes/web.php:30](<C:/xampp/htdocs/gestion-wings/routes/web.php:30>) · [routes/web.php:262](<C:/xampp/htdocs/gestion-wings/routes/web.php:262>)

### Saber quién cambió una cuenta

El controlador de usuarios no registra un evento propio consultable de quién cambió rol o clave. Sería útil para investigar accesos. No significa que no exista trazabilidad de negocio en pagos y otras áreas.

[app/Http/Controllers/UsuarioWebController.php:157](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/UsuarioWebController.php:157>)

### Enterarse cuando un proceso deja de funcionar

La operación necesita responsables, avisos recibidos y tiempos de recuperación para backup, correo y tareas mensuales. Hay scripts y logs, pero el monitoreo del servidor no fue revalidado. El correo de deuda anterior captura errores en el log; eso no prueba entrega al destinatario.

[app/Http/Controllers/CajaWebController.php:781](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CajaWebController.php:781>) · [routes/console.php:12](<C:/xampp/htdocs/gestion-wings/routes/console.php:12>) · [scripts/servidor/respaldar.sh:54](<C:/xampp/htdocs/gestion-wings/scripts/servidor/respaldar.sh:54>)

### Relacionarse con familias y bancos

Portal de familias, pagos online, WhatsApp y conciliación bancaria automática son posibilidades de evolución, no compromisos encontrados en los contratos revisados. No incorporarlas por comparación genérica con otros productos: primero definir necesidad, costo y responsable.

[docs/02-contratos/Wings-Contrato-Reportes-V1.md:225](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/Wings-Contrato-Reportes-V1.md:225>)

### Una entrega que el usuario pueda aceptar

Hace falta cerrar el recorrido humano completo: cargar catálogos, alumnos y planes; cobrar, cancelar, comprobar recibos, cerrar caja, registrar asistencia y liquidar; comparar pantalla con servicio y base. No se afirma que ningún paso haya sido probado: falta una certificación actual conjunta de esta versión.

[docs/00-estado/ORDEN-CODEX-DEUDA-INICIAL.md:1](<C:/xampp/htdocs/gestion-wings/docs/00-estado/ORDEN-CODEX-DEUDA-INICIAL.md:1>) · [docs/00-estado/PLAN-PRODUCCION.md:1](<C:/xampp/htdocs/gestion-wings/docs/00-estado/PLAN-PRODUCCION.md:1>)

## Límites y siguiente decisión

No hay una afirmación de «listo para producción» ni de «sistema inutilizable». Hay funcionalidades existentes, diferencias concretas y garantías todavía no comprobadas. Conviene cruzar este informe con el de Claude por escenario y evidencia, no sumar títulos repetidos como defectos distintos. Elegir después qué se reproduce, qué se corrige y qué permanece diferido. Este trabajo no autoriza cambios de diseño, reglas, datos ni despliegues.

Los 60 alumnos, las 81 deudas y los $2.997.000 pertenecen al contexto histórico de la prueba inicial: no se consultó la base hoy para volver a afirmarlos. Tampoco se repitió una prueba humana de los tres roles. El estado actual del servidor requiere verificación independiente; las diferencias documentales no permiten adivinarlo.

## Fuentes externas del control de dependencias

Consulta de registros realizada el 8/9/2026 mediante `composer audit --locked --format=json` y `npm audit --package-lock-only --json`, sin instalar ni actualizar. Composer sin avisos; npm con 11 paquetes afectados. Ejemplo de condiciones que deben revisarse: [aviso de shell-quote GHSA-w7jw-789q-3m8p](https://github.com/advisories/GHSA-w7jw-789q-3m8p). La ejecución requiere una cadena de entrada y uso de comandos que no se demostró en Wings.

---

**Firma: Codex CAB — 8 de septiembre de 2026.** Evaluación independiente del informe que prepara Claude. Referencias de líneas correspondientes al checkout indicado; pueden desplazarse con cambios posteriores.



# Cruce con Claude — ampliación verificada del 8/9/2026

Se compararon las versiones Markdown y HTML de Claude en docs/07-evaluacion, publicadas en 4e1674e. Ese commit solo agrega sus dos informes: el código evaluado sigue siendo el de 97fb840. Esta ampliación conserva las 22 fichas originales y agrega 17 puntos, algunos complementos y uno condicional; no deben sumarse como 39 defectos independientes. Se señalan 21 correcciones o precisiones al informe de Claude. Los tres agentes releyeron cuerpos y rutas; no se operó sobre la base ni se reprodujeron cobros. No se modifican los archivos de Claude.

**Corrección de alcance de nuestro informe: la transacción de cambio de plan está implementada, pero el campo no sale desde la pantalla (C02). La marca ANULADO existe, pero el PDF pierde períodos al borrarse sus imputaciones (C06). Nuestro informe inicial no cubría esos dos extremos del flujo. La ficha condicional de grupo sin planes (C12) no se presenta como un fallo actualmente alcanzable sin verificar datos.**

## Omisiones incorporadas después de comprobarlas

### C01 · Monto con separador de miles enviado antes de limpiarlo

**Incorporado · revisar primero**

Claude detectó una omisión importante. El listener inline construye FormData antes de que el listener del módulo quite los puntos. PHP considera numérico «30.000» y lo interpreta como 30. El camino de un solo período permite esa interpretación; no afirmamos que toda combinación termine en éxito, porque otras validaciones pueden rechazarla. Falta reproducción controlada de navegador y base, no un cobro en el entorno de trabajo.

**Evidencia:** [resources/views/caja/cobrar.blade.php:131](<C:/xampp/htdocs/gestion-wings/resources/views/caja/cobrar.blade.php:131>) · [resources/views/caja/cobrar.blade.php:355](<C:/xampp/htdocs/gestion-wings/resources/views/caja/cobrar.blade.php:355>) · [resources/js/ds-app.js:145](<C:/xampp/htdocs/gestion-wings/resources/js/ds-app.js:145>) · [resources/views/layouts/ds-app.blade.php:8](<C:/xampp/htdocs/gestion-wings/resources/views/layouts/ds-app.blade.php:8>) · [app/Http/Controllers/CajaWebController.php:645](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CajaWebController.php:645>)

### C02 · La elección de plan no viaja con el formulario

**Incorporado · revisar primero**

Los radios nuevo_plan_id están antes del formulario y no tienen atributo form; el JavaScript modifica aspecto e importe, pero no agrega ese campo a FormData. El servidor tiene un cambio de plan transaccional, pero el envío de esta pantalla no lo activa. Corrige el alcance de nuestra afirmación anterior: backend atómico no equivale a flujo web completo. El «verificado y cerrado» de Claude no debe interpretarse como arreglado.

**Evidencia:** [resources/views/caja/cobrar.blade.php:54](<C:/xampp/htdocs/gestion-wings/resources/views/caja/cobrar.blade.php:54>) · [resources/views/caja/cobrar.blade.php:83](<C:/xampp/htdocs/gestion-wings/resources/views/caja/cobrar.blade.php:83>) · [resources/views/caja/cobrar.blade.php:383](<C:/xampp/htdocs/gestion-wings/resources/views/caja/cobrar.blade.php:383>) · [app/Http/Controllers/CajaWebController.php:693](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CajaWebController.php:693>)

### C03 · El seeder puede quitar la protección de los rubros

**Incorporado · conocido, sin ejecutarlo**

CatalogosSeeder guarda false por defecto en es_reservado_sistema y Cuotas/Sueldos no definen true a nivel rubro. Reejecutarlo puede desprotegerlos. El controlador se apoya en esa marca. No equivale a afirmar que la base entregada esté desprotegida hoy. No se ejecuta seeder: la carga humana y el estado mínimo de entrega siguen siendo decisiones vigentes.

**Evidencia:** [database/seeders/CatalogosSeeder.php:47](<C:/xampp/htdocs/gestion-wings/database/seeders/CatalogosSeeder.php:47>) · [database/seeders/CatalogosSeeder.php:125](<C:/xampp/htdocs/gestion-wings/database/seeders/CatalogosSeeder.php:125>) · [app/Http/Controllers/RubroWebController.php:64](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/RubroWebController.php:64>)

### C04 · Fechas manuales amplias y caja actual

**Incorporado con alcance acotado**

Los movimientos manuales aceptan fechas válidas sin límite temporal; el movimiento puede pertenecer a la caja abierta de hoy y reflejarse con otra fecha. La consulta de historial acota a 90 días. En cambio, fecha_pago del cobro tiene before_or_equal:today: no se sostiene que un cobro de 2030 pase por el mismo camino. No existe aquí un cierre mensual formal que se haya demostrado vulnerado; falta decidir el alcance permitido de imputaciones retrospectivas.

**Evidencia:** [app/Http/Controllers/CajaWebController.php:314](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CajaWebController.php:314>) · [app/Http/Controllers/CajaWebController.php:371](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CajaWebController.php:371>) · [app/Http/Controllers/CajaWebController.php:646](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CajaWebController.php:646>) · [app/Http/Controllers/CajaWebController.php:94](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CajaWebController.php:94>) · [app/Services/CajaService.php:289](<C:/xampp/htdocs/gestion-wings/app/Services/CajaService.php:289>)

### C05 · Revisión reemplaza observaciones y usa el plan actual

**Incorporado parcialmente**

INACTIVO cambia una deuda pendiente del mes anterior a CONDONADA, incluso si tiene un pago parcial, y reemplaza sus observaciones. CONTINUA toma el plan activo actual para crear la deuda. Son comportamientos que deben contrastarse con la regla temporal y de preservación de historia. No adoptamos «sin motivo»: la ruta exige nota de 5–500 caracteres y guarda nota, usuario y fecha. No es una resolución anónima ni un bypass demostrado de permisos.

**Evidencia:** [app/Services/RevisionCobranzaService.php:27](<C:/xampp/htdocs/gestion-wings/app/Services/RevisionCobranzaService.php:27>) · [app/Services/RevisionCobranzaService.php:36](<C:/xampp/htdocs/gestion-wings/app/Services/RevisionCobranzaService.php:36>) · [app/Services/RevisionCobranzaService.php:53](<C:/xampp/htdocs/gestion-wings/app/Services/RevisionCobranzaService.php:53>) · [app/Http/Controllers/RevisionCobranzaWebController.php:42](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/RevisionCobranzaWebController.php:42>) · [docs/02-contratos/Wings-contrato-estadosAlum-cobranza-asistencia-V1.md:72](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/Wings-contrato-estadosAlum-cobranza-asistencia-V1.md:72>)

### C06 · Anular conserva el pago, pero elimina el detalle de imputaciones

**Incorporado · ampliar ficha 02 y decisiones**

Cancelar borra físicamente las filas pago_deuda_cuota. El PDF anulado se regenera y obtiene sus períodos de esa relación ya vacía. La marca ANULADO sí funciona, pero pierde el reparto estructurado por período. Persisten pago, movimiento, motivo y observaciones en deudas: no desaparece todo el rastro.

**Evidencia:** [app/Services/PagoCuotaService.php:753](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:753>) · [app/Services/PagoCuotaService.php:762](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:762>) · [app/Services/ReciboService.php:33](<C:/xampp/htdocs/gestion-wings/app/Services/ReciboService.php:33>) · [app/Services/ReciboService.php:249](<C:/xampp/htdocs/gestion-wings/app/Services/ReciboService.php:249>)

### C07 · Condonar frente a cobrar: otro caso de concurrencia

**Incorporado · ampliar ficha 06**

La condonación web es alcanzable por ADMIN y no bloquea la deuda antes de leer estado y guardar. Debe ensayarse frente a un cobro simultáneo. AjustarDeuda tampoco bloquea, pero su consumidor identificado es la API deshabilitada: no se incorpora como puerta web actual. La condonación sí guarda autor, motivo y marca temporal en observaciones.

**Evidencia:** [app/Services/PagoCuotaService.php:217](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:217>) · [app/Services/PagoCuotaService.php:253](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:253>) · [app/Services/PagoCuotaService.php:776](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:776>) · [app/Http/Controllers/AlumnoWebController.php:112](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/AlumnoWebController.php:112>) · [app/Http/Controllers/PagoCuotaController.php:124](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/PagoCuotaController.php:124>) · [bootstrap/app.php:14](<C:/xampp/htdocs/gestion-wings/bootstrap/app.php:14>)

### C08 · La prueba de bloqueos no sustituye concurrencia real

**Incorporado con corrección del inventario**

MoneyLockingTest comprueba patrones de texto; es una protección estructural, no una ejecución simultánea. La necesitamos describir por lo que prueba. No se incorpora «cinco servicios con cero pruebas»: SaldoInicialTipoCajaTest paga una liquidación por la ruta y comprueba estado y egreso. Falta cobertura por escenarios, no se puede deducir cobertura cero por no mencionar el nombre del servicio. La ausencia de .github/workflows solo prueba ausencia de ese mecanismo en el checkout.

**Evidencia:** [tests/Unit/MoneyLockingTest.php:11](<C:/xampp/htdocs/gestion-wings/tests/Unit/MoneyLockingTest.php:11>) · [tests/Feature/SaldoInicialTipoCajaTest.php:254](<C:/xampp/htdocs/gestion-wings/tests/Feature/SaldoInicialTipoCajaTest.php:254>)

### C09 · El control de producción se hace después de reabrir

**Incorporado · despliegue**

deploy.sh ejecuta artisan up antes de wings:preflight. Existe una ventana potencial de exposición si quedó una configuración incorrecta. No se comprobó APP_DEBUG activado en servidor ni una filtración; «credenciales en cada error» excede esta evidencia. Mantener el hallazgo como orden de controles y verificarlo en un despliegue de ensayo.

**Evidencia:** [scripts/deploy.sh:220](<C:/xampp/htdocs/gestion-wings/scripts/deploy.sh:220>) · [app/Console/Commands/PreflightCommand.php:31](<C:/xampp/htdocs/gestion-wings/app/Console/Commands/PreflightCommand.php:31>)

### C10 · El balance filtrado no arrastra los movimientos anteriores

**Incorporado · usuario**

La vista suma saldo inicial histórico con ingresos menos egresos del año/mes elegido. Ejemplo algebraico: inicial 100, agosto +50 y septiembre +10; septiembre muestra 110, no el saldo acumulado 160 ni el resultado mensual 10. Debe definirse y rotularse la magnitud. Incluir cajas inactivas no es por sí solo un error: sus saldos históricos no desaparecen al desactivarlas.

**Evidencia:** [app/Http/Controllers/CashflowWebController.php:32](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CashflowWebController.php:32>) · [app/Http/Controllers/CashflowWebController.php:45](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CashflowWebController.php:45>) · [resources/views/cashflow/index.blade.php:10](<C:/xampp/htdocs/gestion-wings/resources/views/cashflow/index.blade.php:10>) · [docs/02-contratos/Wings-Contrato-Caja-Cashflow-V4.md:60](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/Wings-Contrato-Caja-Cashflow-V4.md:60>)

### C11 · Faltan accesos y avisos para cerrar la operación

**Incorporado · usuario**

Tras cobrar se redirige al listado sin enlace al recibo; la ficha muestra ocho pagos sin enlace al PDF. El recibo sí existe en el detalle de caja. ADMIN no recibe el banner de caja vieja de ese listado, que se calcula y muestra solo para OPERATIVO. Su dashboard no reúne pendientes de cajas, revisiones y liquidaciones. No significa que el operativo tenga todos esos dominios: liquidaciones es ADMIN.

**Evidencia:** [app/Http/Controllers/CajaWebController.php:797](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CajaWebController.php:797>) · [app/Http/Controllers/CajaWebController.php:47](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CajaWebController.php:47>) · [app/Http/Controllers/AlumnoWebController.php:93](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/AlumnoWebController.php:93>) · [resources/views/alumnos/show.blade.php:211](<C:/xampp/htdocs/gestion-wings/resources/views/alumnos/show.blade.php:211>) · [resources/views/caja/index.blade.php:39](<C:/xampp/htdocs/gestion-wings/resources/views/caja/index.blade.php:39>) · [app/Http/Controllers/WebController.php:64](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/WebController.php:64>)

### C12 · Grupo sin planes oculta el mensaje del plan

**Condicional · no probado con datos actuales**

El error plan_id está dentro de plan-section y el JavaScript oculta esa sección si el grupo no tiene planes. Puede impedir entender por qué no se guarda. No es un círculo sin salida para toda la aplicación: se pueden completar los planes del grupo por el flujo administrativo y luego volver; la edición además tiene un aviso general de falta de plan. Además, crear/editar grupos exige al menos un plan y eliminar el último está bloqueado. El caso requiere datos heredados o una carga externa; no se verificó que exista hoy un grupo así.

**Evidencia:** [resources/views/alumnos/_form.blade.php:136](<C:/xampp/htdocs/gestion-wings/resources/views/alumnos/_form.blade.php:136>) · [resources/views/alumnos/_form.blade.php:146](<C:/xampp/htdocs/gestion-wings/resources/views/alumnos/_form.blade.php:146>) · [resources/views/alumnos/_form.blade.php:188](<C:/xampp/htdocs/gestion-wings/resources/views/alumnos/_form.blade.php:188>) · [resources/views/alumnos/edit.blade.php:8](<C:/xampp/htdocs/gestion-wings/resources/views/alumnos/edit.blade.php:8>) · [app/Http/Controllers/GrupoWebController.php:55](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/GrupoWebController.php:55>) · [app/Http/Controllers/GrupoWebController.php:279](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/GrupoWebController.php:279>)

### C13 · Editar una clase no repite el control de superposición

**Incorporado · usuario y liquidación**

Crear consulta disponibilidad del profesor; update valida formatos y horarios de inicio/fin, pero guarda fecha y sincroniza profesores sin el mismo control. Puede introducir una superposición. Falta reproducción aislada y verificar qué clases terminarían siendo liquidables: no toda clase editada se paga automáticamente.

**Evidencia:** [app/Http/Controllers/ClaseWebController.php:271](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/ClaseWebController.php:271>) · [app/Http/Controllers/ClaseWebController.php:439](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/ClaseWebController.php:439>) · [docs/02-contratos/Wings-Contrato-Clases-Asistencias-V1.md:84](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/Wings-Contrato-Clases-Asistencias-V1.md:84>)

### C14 · La dependencia mensual de asistencia necesita seguimiento

**Incorporado como operación, no nuevo defecto**

Los antiguos sin asistencia van a revisión, como fue decidido. También se genera deuda a quien tiene alta reciente y pago completado reciente, aunque no haya asistido. Debe controlarse el resultado del proceso y atender revisiones. El reporte de Claude dice que verificó infraestructura por SSH; no se repitió esa consulta ni se adopta «cero servicios» como constatación propia.

**Evidencia:** [app/Console/Commands/GenerarDeudasMensualesCommand.php:94](<C:/xampp/htdocs/gestion-wings/app/Console/Commands/GenerarDeudasMensualesCommand.php:94>) · [app/Console/Commands/GenerarDeudasMensualesCommand.php:105](<C:/xampp/htdocs/gestion-wings/app/Console/Commands/GenerarDeudasMensualesCommand.php:105>) · [docs/02-contratos/Wings-contrato-estadosAlum-cobranza-asistencia-V1.md:39](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/Wings-contrato-estadosAlum-cobranza-asistencia-V1.md:39>)

### C15 · El estado mínimo de entrega necesita un procedimiento reproducible

**Incorporado como documentación operativa**

La bitácora describe la preparación manual del 7/9; migrate --seed agrega catálogos que esa entrega deja al usuario. Falta alinear una guía reproducible de preparación con esa decisión. No se concluye que sea necesario repetir un borrado de memoria: existe la bitácora y respaldo informado. No se propone un nuevo seeder ni ejecutar el existente sin decisión de Carlos.

**Evidencia:** [docs/00-estado/LOG-CLAUDE.md:25](<C:/xampp/htdocs/gestion-wings/docs/00-estado/LOG-CLAUDE.md:25>) · [database/seeders/CatalogosSeeder.php:15](<C:/xampp/htdocs/gestion-wings/database/seeders/CatalogosSeeder.php:15>) · [CLAUDE.md:1](<C:/xampp/htdocs/gestion-wings/CLAUDE.md:1>)

### C16 · Recordarme y política de contraseñas

**Incorporado · ampliar ficha 07**

Cambiar la clave no rota remember_token. El framework instalado puede volver a autenticar mediante ID y token, sin comparar la nueva contraseña. Su duración configurada es 576.000 minutos (400 días), no cinco años; logout, desactivación o borrado de cookie pueden interrumpirla. También hay mínimos distintos: ocho caracteres por web y doce por consola. El límite anónimo de login es por IP, sin contador por email; no equivale a ausencia de protección ni a una exigencia de complejidad contractual.

**Evidencia:** [app/Http/Controllers/UsuarioWebController.php:157](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/UsuarioWebController.php:157>) · [app/Console/Commands/CrearAdminCommand.php:37](<C:/xampp/htdocs/gestion-wings/app/Console/Commands/CrearAdminCommand.php:37>) · [vendor/laravel/framework/src/Illuminate/Auth/SessionGuard.php:62](<C:/xampp/htdocs/gestion-wings/vendor/laravel/framework/src/Illuminate/Auth/SessionGuard.php:62>) · [vendor/laravel/framework/src/Illuminate/Auth/SessionGuard.php:215](<C:/xampp/htdocs/gestion-wings/vendor/laravel/framework/src/Illuminate/Auth/SessionGuard.php:215>) · [vendor/laravel/framework/src/Illuminate/Auth/EloquentUserProvider.php:69](<C:/xampp/htdocs/gestion-wings/vendor/laravel/framework/src/Illuminate/Auth/EloquentUserProvider.php:69>) · [vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php:219](<C:/xampp/htdocs/gestion-wings/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php:219>)

### C17 · Aclarar qué plata todavía no entró en Cashflow

**Incorporado · diferido contractual**

El reflejo de caja requiere VALIDADA, por diseño. Cashflow no consulta ni avisa las cajas pendientes de integración. La advertencia está pedida en el contrato de reportes y sigue en su etapa diferida; no se propone integrar cajas sin validar ni cambiar cómo se calculan los fondos para liquidaciones.

**Evidencia:** [app/Services/CashflowIntegracionCajaService.php:26](<C:/xampp/htdocs/gestion-wings/app/Services/CashflowIntegracionCajaService.php:26>) · [app/Http/Controllers/CashflowWebController.php:16](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CashflowWebController.php:16>) · [docs/02-contratos/Wings-Contrato-Reportes-V1.md:105](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/Wings-Contrato-Reportes-V1.md:105>) · [docs/02-contratos/Wings-Contrato-Reportes-V1.md:239](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/Wings-Contrato-Reportes-V1.md:239>)

## Qué está mal, qué excede la evidencia y qué no corresponde en Claude

Las diferencias se refieren a afirmaciones concretas, no a una descalificación de la evaluación. Cuando una frase contiene un hallazgo válido y una conclusión incorrecta, se conserva lo primero y se acota lo segundo.

### D01 · Seguridad sin vulnerabilidades / dependencias sin avisos

**Resultado: No demostrado / contradicho por el control npm.** La lectura no certifica ausencia universal de XSS, SQLi o ataques anónimos. Composer sin avisos no cubre JavaScript: nuestro control del 8/9 informó 11 paquetes afectados. Son avisos, no 11 explotaciones de Wings. Las rutas de recibos existen y requieren autenticación; «no alcanzables por URL» debe decir «no de acceso público».

**Evidencia:** Ficha 12 · routes/web.php · app/Http/Controllers/ReciboController.php

### D02 · Tres cobros siempre exitosos y mal registrados

**Resultado: Excesivo.** Monto y plan son omisiones válidas (C01–C02), con prueba de pantalla pendiente. En cancelación se pierde la condición de primer pago, pero la deuda original ya descontada permanece y el nuevo cobro usa su saldo: no demuestra cobrar mes entero. El 40% de la regla es lo que se paga, no un descuento del 40%.

**Evidencia:** [app/Services/PagoCuotaService.php:587](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:587>) · [app/Services/PagoCuotaService.php:744](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:744>) · [app/Http/Controllers/CajaWebController.php:600](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CajaWebController.php:600>) · [app/Http/Controllers/CajaWebController.php:748](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CajaWebController.php:748>)

### D03 · Revisión condona sin motivo

**Resultado: Incorrecto en la ruta web.** Se exige nota de 5–500 caracteres y se guardan nota, usuario y fecha. Sí se conservan como hallazgos el reemplazo de observaciones y el tratamiento de la deuda pendiente con parcial (C05).

**Evidencia:** [app/Http/Controllers/RevisionCobranzaWebController.php:42](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/RevisionCobranzaWebController.php:42>) · [app/Services/RevisionCobranzaService.php:27](<C:/xampp/htdocs/gestion-wings/app/Services/RevisionCobranzaService.php:27>)

### D04 · Condonar no deja quién ni cuándo

**Resultado: Incorrecto.** La condonación normal agrega ADMIN ID, motivo y timestamp. La falta de historial específico de cambios de usuarios sigue siendo válida; no extenderla a todo el sistema.

**Evidencia:** [app/Services/PagoCuotaService.php:233](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:233>) · [app/Services/PagoCuotaService.php:776](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:776>)

### D05 · Recordarme durante cinco años

**Resultado: Incorrecto.** El framework instalado establece 400 días. Mantener el riesgo real de token que no se rota al cambiar clave, con sus condiciones (C16).

**Evidencia:** [vendor/laravel/framework/src/Illuminate/Auth/SessionGuard.php:62](<C:/xampp/htdocs/gestion-wings/vendor/laravel/framework/src/Illuminate/Auth/SessionGuard.php:62>)

### D06 · Adelanto sin tope y 2099-99 aceptado

**Resultado: No se sostiene como operación persistida.** El servicio crea deuda por precio de plan y rechaza un importe superior al saldo. 2099-99 pasa la regex inicial, pero Carbon procesa el período antes de guardar. En esta comparación se ejecutó únicamente el parser instalado, sin base: Carbon::parse de 2099-99-01 lanzó InvalidFormatException. No se ejecutó el cobro completo y no hay evidencia de un pago inválido persistido.

**Evidencia:** [app/Services/PagoCuotaService.php:338](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:338>) · [app/Services/PagoCuotaService.php:416](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:416>) · [app/Services/PagoCuotaService.php:436](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:436>)

### D07 · 419 muestra pantalla inglesa

**Resultado: Incorrecto para peticiones HTML.** bootstrap captura 419 y redirige al login con «Tu sesión expiró. Iniciá sesión nuevamente.». La plantilla genérica de Laravel no demuestra el resultado del flujo web; JSON debe revisarse aparte.

**Evidencia:** [bootstrap/app.php:79](<C:/xampp/htdocs/gestion-wings/bootstrap/app.php:79>)

### D08 · Sin asistencia nunca se genera deuda

**Resultado: Falta una excepción vigente.** Alta reciente más pago completado reciente también genera deuda, aunque no haya asistencia. Para antiguos sin asistencia, revisión es la decisión acordada (C14).

**Evidencia:** [app/Console/Commands/GenerarDeudasMensualesCommand.php:94](<C:/xampp/htdocs/gestion-wings/app/Console/Commands/GenerarDeudasMensualesCommand.php:94>) · [docs/02-contratos/Wings-contrato-estadosAlum-cobranza-asistencia-V1.md:39](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/Wings-contrato-estadosAlum-cobranza-asistencia-V1.md:39>)

### D09 · Caja anterior desaparece y no hay dónde verla

**Resultado: Incorrecto como inaccesibilidad.** ADMIN puede cambiar el filtro de mes; OPERATIVO ve últimos 30 días, no solo mes actual. Falta destacar pendientes automáticamente, no recuperar datos perdidos.

**Evidencia:** [app/Http/Controllers/CajaWebController.php:49](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CajaWebController.php:49>) · [app/Http/Controllers/CajaWebController.php:70](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/CajaWebController.php:70>) · [resources/views/caja/index.blade.php:29](<C:/xampp/htdocs/gestion-wings/resources/views/caja/index.blade.php:29>)

### D10 · Dashboard inflado por quienes nunca pagaron

**Resultado: Confunde dos cálculos.** Cobranza prioriza no tener pagos. El dashboard ADMIN cuenta alumnos con deuda PENDIENTE y suma sus saldos; no usa ese criterio. DEUDOR sin saldo fue aceptado para la prueba, y no es «para siempre»: un pago completado posterior cambia la condición.

**Evidencia:** [app/Http/Controllers/WebController.php:68](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/WebController.php:68>) · [app/Services/CobranzaEstadoService.php:234](<C:/xampp/htdocs/gestion-wings/app/Services/CobranzaEstadoService.php:234>)

### D11 · El operativo tiene todo el tablero que falta al ADMIN

**Resultado: Excesivo.** Tiene indicadores operativos, pero no liquidaciones ni saldo global del club. Sigue siendo válida la oportunidad de un tablero administrativo más útil.

**Evidencia:** [app/Http/Controllers/OperativoDashboardController.php:20](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/OperativoDashboardController.php:20>) · [docs/02-contratos/PERMISOS-ROLES.md:50](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/PERMISOS-ROLES.md:50>)

### D12 · Duplicación de liquidación por cualquier refresh

**Resultado: Falta simultaneidad.** La segunda petición secuencial encuentra PAGADA y rechaza. El riesgo que sí se conserva requiere solicitudes que superen concurrentemente las comprobaciones.

**Evidencia:** [app/Services/LiquidacionPagoService.php:31](<C:/xampp/htdocs/gestion-wings/app/Services/LiquidacionPagoService.php:31>)

### D13 · Cinco servicios con cero pruebas / MoneyLocking no prueba nada

**Resultado: Incorrecto / desmedido.** Hay cobertura indirecta por ruta de pago de liquidación y aserción de egreso. MoneyLocking protege una estructura textual; no prueba intercalaciones concurrentes. Evaluar cobertura por casos, no por nombres de clases.

**Evidencia:** [tests/Feature/SaldoInicialTipoCajaTest.php:254](<C:/xampp/htdocs/gestion-wings/tests/Feature/SaldoInicialTipoCajaTest.php:254>) · [tests/Unit/MoneyLockingTest.php:11](<C:/xampp/htdocs/gestion-wings/tests/Unit/MoneyLockingTest.php:11>)

### D14 · Ajustar deuda es un acceso actual sin bloqueo

**Resultado: Fuera del alcance web activo.** El consumidor identificado es PagoCuotaController de API, deshabilitada. Condonar sí es web y se incorpora a la revisión de concurrencia.

**Evidencia:** [app/Http/Controllers/PagoCuotaController.php:124](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/PagoCuotaController.php:124>) · [bootstrap/app.php:14](<C:/xampp/htdocs/gestion-wings/bootstrap/app.php:14>)

### D15 · Exportar nada / profesor sin sueldo / dos deportes como duplicación defectuosa

**Resultado: No corresponden como incumplimientos.** Reportes exportables están fuera de versión; recibos PDF sí existen. El profesor no participa de liquidaciones por contrato. Un registro por deporte para una misma persona está definido expresamente, no fue un borde accidental de la carga inicial.

**Evidencia:** [docs/02-contratos/Wings-Contrato-Reportes-V1.md:225](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/Wings-Contrato-Reportes-V1.md:225>) · [docs/02-contratos/PERMISOS-ROLES.md:76](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/PERMISOS-ROLES.md:76>) · [docs/02-contratos/Wings-contrato-alumno-grupo-deporte-deuda-v3.md:56](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/Wings-contrato-alumno-grupo-deporte-deuda-v3.md:56>)

### D16 · No poder reabrir una liquidación es un defecto

**Resultado: Es una decisión vigente.** La inmutabilidad del cierre está acordada. Evaluar correcciones compensatorias es distinto de prometer editar o borrar historia. No cambiar ese contrato a partir de una comparación genérica.

**Evidencia:** [docs/02-contratos/Wings-Contrato-Recibos-PDF-V1.md:48](<C:/xampp/htdocs/gestion-wings/docs/02-contratos/Wings-Contrato-Recibos-PDF-V1.md:48>) · [app/Models/Liquidacion.php:59](<C:/xampp/htdocs/gestion-wings/app/Models/Liquidacion.php:59>)

### D17 · Columnas decimal descartan riesgo de float

**Resultado: No demuestra el cierre.** Los cálculos vuelven a float y comparan importes. No demostramos un error de centavos, pero almacenamiento decimal y casts tampoco prueban exactitud de toda la aritmética.

**Evidencia:** [app/Models/DeudaCuota.php:62](<C:/xampp/htdocs/gestion-wings/app/Models/DeudaCuota.php:62>) · [app/Services/PagoCuotaService.php:338](<C:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php:338>)

### D18 · Índices hacen imposible cualquier duplicación

**Resultado: Correcto solo dentro de su clave.** Imposibilitan duplicar alumno/período y dos planes con activo=true del mismo alumno en un esquema migrado. No prueban identidad única entre diferentes alumnos, vigencia temporal correcta ni estado actual de una base no consultada.

**Evidencia:** [database/migrations/2026_02_01_000002_create_deuda_cuotas_table.php:30](<C:/xampp/htdocs/gestion-wings/database/migrations/2026_02_01_000002_create_deuda_cuotas_table.php:30>) · [database/migrations/2026_01_12_091959_create_alumno_planes_table.php:24](<C:/xampp/htdocs/gestion-wings/database/migrations/2026_01_12_091959_create_alumno_planes_table.php:24>)

### D19 · Restauración probada / servidor sin monitoreo / Cloudflare correcto

**Resultado: No se revalida aquí la infraestructura.** Atribuir las verificaciones SSH a Claude. Su conclusión general sobre restauración no resuelve los límites de nuestro análisis: SQL sin reconstrucción integral y ensayo por conteos. No convertir reportes de otra sesión en evidencia propia.

**Evidencia:** Fichas 08–11 · [scripts/servidor/restaurar.sh:30](<C:/xampp/htdocs/gestion-wings/scripts/servidor/restaurar.sh:30>)

### D20 · No hay integración continua porque no hay workflows

**Resultado: Conclusión más amplia que la evidencia.** La ausencia de .github/workflows prueba que ese mecanismo no está versionado aquí; no que no exista ningún servicio externo, hook o ejecución remota.

**Evidencia:** Checkout revisado · tests/Unit/MoneyLockingTest.php

### D21 · Grupo sin planes como defecto habitual

**Resultado: Alcanzabilidad pendiente.** La vista puede ocultar el error, pero el alta/edición normal exige un plan y no deja borrar el último. Sin consultar datos heredados no se afirma que el usuario pueda llegar hoy a ese estado (C12).

**Evidencia:** [app/Http/Controllers/GrupoWebController.php:55](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/GrupoWebController.php:55>) · [app/Http/Controllers/GrupoWebController.php:279](<C:/xampp/htdocs/gestion-wings/app/Http/Controllers/GrupoWebController.php:279>)

## Capacidades adicionales para decidir, no obligaciones nuevas

También se incorporan como posibles ampliaciones, no como promesas incumplidas: datos médicos/contacto de emergencia específico (Alumno mantiene tutor y teléfono, no ficha clínica); agrupación familiar y política de descuentos; tarifas versionadas (GrupoWebController actualiza precio, pero las deudas conservan monto original); consulta individual de asistencia de otros meses (la ficha limita el mes, las clases históricas conservan registros); historial de contactos de cobranza y causa de baja. Antes de incorporarlas al producto deben definirse necesidad, acceso y tratamiento contable. No se adopta «saldo inexplicable inevitable» por precio familiar ni «se borró toda historia» por no tener tarifa versionada. Evidencia: app/Models/Alumno.php:18, resources/views/alumnos/_form.blade.php:150, app/Http/Controllers/GrupoWebController.php:179 y app/Http/Controllers/AlumnoWebController.php:99. Multi-sede, torneos, indumentaria y becas siguen siendo propuestas de alcance, no urgencias derivadas de esta auditoría.

## Coincidencias que no duplicamos y documentación pendiente

Se mantienen las fichas originales sobre liquidaciones concurrentes, recibo por coincidencias, sesiones, errores técnicos, reportes diferidos y pobreza del tablero administrativo. Los hallazgos de Codex sobre pérdida de saldo por descuento combinado con parcial, base matemática del descuento, copia externa, ensayo integral y rollback conservan su propia evidencia aunque Claude no los haya incluido.

Coincidimos con Claude en que H-DI-01 está corregido en código y que existe un índice de plan activo; no equivale a haber revalidado hoy la base o los roles en pantalla. La diferencia entre el max-width 1200px escrito en la guía de diseño y .ds-content sin ese límite en app.css también existe: se registra sin tocar CSS ni elegir un nuevo diseño. No se adopta el porcentaje global de avance de PLAN-MAESTRO ni se certifican todos sus UC sin una matriz actualizada caso por caso.

**Siguiente decisión:** reproducir primero C01–C02 y el caso financiero 01 original en una base aislada; cruzar los demás escenarios por alcance, evitando convertir títulos repetidos en más defectos. No se aplica ninguna corrección funcional en esta tarea.

**Firma: Codex CAB — comparación del 8/9/2026.**
