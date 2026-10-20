# Antecedente reemplazado por la aclaración de Carlos del 20/10/2026

La regla vigente está en [IMPLEMENTACION-T61.md](IMPLEMENTACION-T61.md). Este documento conserva la entrega anterior; sus resultados no prueban la nueva transferencia única.

# T61 — Pasar dinero entre medios propios

Codex CAB, 20/10/2026. DU-29 / CSH-018. **Implementada e integrada; recorrido CyE completado. Pendientes visual de Carlos y verificación de Claude.** Véase el [control actual](VERIFICACION-T61-CYE.md).
Solo trabajo local; sin servidor, despliegue ni cambios en datos del club.

## Decisiones de Carlos

- Dos movimientos comunes e independientes: egreso e ingreso. Sin vínculo ni control
  de igualdad de importes o fechas. No se crea una operación nueva de transferencia.
- El catálogo exige nombres únicos de rubro. Consultado antes de implementar;
  Carlos aprobó «Sí, usar Transferencias internas — salidas / entradas».
  Los subrubros son «Salida a otro medio» y «Entrada desde otro medio».
- Cashflow no mostraba saldos acumulados por medio. Consultado en criollo, Carlos
  confirmó «Mostrarlo también en Cashflow». Se agrega la tarjeta con la misma base
  actual de Reportes, sin confundirla con el período del listado.

- Carlos confirmó «Cashflow muestra lo que entró y salió»: sus totales incluyen los traspasos; los indicadores del club en Inicio y Reportes los excluyen. Decisión resuelta.

## Cambios por archivo

| Archivo | Cambio |
|---|---|
| `app/Support/ClasificacionSubrubros.php` | TRASPASO permitido en ambos tipos y etiqueta exacta; catálogo y aplicación transaccional idempotente sin pisar clasificaciones existentes. |
| `database/migrations/2026_10_20_120000_agregar_traspasos_entre_medios.php` | Aplica solo el catálogo T61 en instalaciones existentes. Reversión sin borrar catálogos que podrían tener historia. |
| `database/seeders/CatalogosSeeder.php` | Reutiliza la aplicación del catálogo para instalaciones nuevas. |
| `app/Http/Controllers/CashflowWebController.php` | Origen «Traspaso» y saldos actuales por medio desde ReporteMensualService. |
| `resources/views/cashflow/index.blade.php` | Tarjeta de saldos al día, independiente de filtros, con pendientes sin validar explícitos cuando los hay. |
| `app/Http/Controllers/SubrubroWebController.php` | Mensaje de clasificación obligatoria incluye traspaso; validación utiliza permitidas(). |
| `resources/views/subrubros/_form.blade.php` | Explica que mover entre medios propios no es ingreso, gasto, aporte ni retiro. Opción nueva mediante el catálogo dinámico existente. |
| `resources/views/reportes/index.blade.php` | Aclara que Resultado global también incluye traspasos; sin cambiar su cálculo. |
| `tests/Feature/TraspasosEntreMediosT61Test.php` | 12 pruebas nuevas de HTTP, cálculos, permisos, migración, corrección, snapshots y saldos. |
| `tests/Feature/SaldoInicialTipoCajaTest.php` | Conserva la comprobación de que el capital inicial no entra en el resultado del período; permite verlo en la nueva tarjeta de saldos. |
| `tests/Feature/CatalogosSeederTest.php` | Rubros **12 → 14**, subrubros **19 → 21**. Resto de conteos conservado. |
| Contratos Caja/Cashflow V4 y V5 | Clasificación y regla de T61, cruce de meses, nombres aprobados y saldos en Cashflow. |
| `tabla-de-pruebas/catalogo/CSH.json` | CSH-018 vigente, fuente «Programado en T61, a verificar». La tabla viva la sincroniza Claude. |
| Estado, checklist, plan, log y tablero | Continuidad y contador actualizado con las 12 pruebas nuevas; cierre pendiente. |

No se agrega JavaScript incrustado, entidad de transferencia, conciliación automática
de ambas cargas ni cambios de T60. No se modifica el saldo negativo por medio.

## Consumidores revisados

Exploración inicial con codebase-memory-mcp y confirmación en archivos reales.
La señal de cobertura indicó `coverage_unavailable / metadata_changed`; se usó
lectura de cuerpos y búsqueda de texto como respaldo, sin inferir exhaustividad del índice.

| Consumidor | Resultado de la revisión |
|---|---|
| ReporteMensualService::obtener | Sin cambio: solo NEGOCIO entra en ingresos/gastos; desconocido exige NULL, APORTE/RETIRO filtran sus valores exactos. Cajas suma todos los importes firmados. |
| reportes/index | El desglose filtra NEGOCIO. Aportes/retiros usan los campos del servicio. Solo cambia la explicación de global. |
| WebController::adminDashboard y vista administrativa | Sin cambio: reciben el mismo ReporteMensualService del mes actual. HTTP compara los indicadores antes/después del traspaso. |
| CashflowWebController | Cambia el origen de directos TRASPASO. Los totales del listado siguen sumando movimientos de dinero; no se convierten en resultado económico del club. Saldo actual separado del período. |
| RubroWebController::guardarCambios | Sin cambio: consulta permitidas(), de modo que TRASPASO admite ambos tipos y APORTE/RETIRO conservan su restricción. |
| SubrubroWebController::reglaClasificacion | Sin cambio de regla: consume permitidas(). Rechaza valores ajenos y las combinaciones de dueños incompatibles. Mensaje obligatorio actualizado. |
| rubros/index | Sin cambio: muestra «sin clasificar» solo ante un valor vacío. TRASPASO es no vacío y no requiere añadir otro caso al listado. |
| CashflowCorreccionService | Sin cambio: editar el mismo subrubro conserva la foto y anular copia clasificación/tipo/fecha/medio al contraasiento. Pruebas en ingreso y egreso, incluso tras reclasificar el catálogo. |
| MovimientoReporteObserver | Sin cambio: toma clasificación al crear y la actualiza al cambiar de subrubro; respeta la foto explícita de un contraasiento. |
| ReporteSueldosService y reportes/sueldos | Sin cambio: cuotas, pagos, tarifas, liquidaciones y asistencias; no suman los directos de Cashflow para ingresos o costo. HTTP verifica invariancia con alumno y liquidación presentes. |
| ReporteAlumnosService y reportes/alumnos | Sin cambio: matrícula, clases, asistencias y revisiones. HTTP verifica que el traspaso no cambia el reporte. |
| CajaWebController::cargarRubros / CajaService::validarSubrubroManual | Sin cambio: filtro y control del servidor ya exigen OPERATIVO. Los dos subrubros nuevos ADMIN no aparecen y ambos POST son rechazados. |
| CashflowSaldoService | Sin cambio: suma los movimientos del libro por medio, sin excluir clasificaciones. La prueba concilia sus saldos con Reportes en el escenario confirmado. |
| CashflowIntegracionCajaService | Sin cambio: copia el snapshot de clasificación al validar, sin decidir por su valor. T61 es directo ADMIN, no entra en turno operativo. |
| PagoCuotaService (anulación) | Sin cambio: copia la clasificación del asiento original de cuota al contraasiento. No clasifica directos T61. |
| SubrubroSueldoService | Sin cambio: genera exclusivamente sueldos como NEGOCIO. No se convierte ningún sueldo en TRASPASO. |
| Modelos Subrubro, CashflowMovimiento y MovimientoOperativo | Sin cambio: clasificación string en fillable, sin enumeración cerrada de tres valores. |

### Resultado global y cruce de meses

Se conserva el cálculo vigente: global suma todos los movimientos del mes. Una
salida de $300.000 en septiembre y una entrada en octubre dejan global septiembre
−$300.000 y global octubre +$300.000. El resultado del club no cambia en ninguno.
Si ambas cargas son del mismo mes por igual importe, el efecto global neto es cero.
No se exige que sean iguales: también se probó salida $300.000 / entrada $290.000.

## Pruebas y evidencia

- [Dirigidas finales](evidencia-t61-codex/dirigidas-finales.txt): **26 aprobadas, 376 aserciones, 16,89 s, salida 0**, en `wings_testing_codex`. Incluyen las 12 nuevas, catálogos, T16 y saldo inicial. CSH-018.d comprueba también que el texto para T13 contiene el ejemplo y los dos pasos.
- Escenario no vacío: ingreso del club $12.000, gasto $5.000, aporte $8.000,
  retiro $2.000. El traspaso no modifica esos cuatro indicadores ni el resultado $7.000.
- Se probó también un saldo pendiente de caja sin validar, y que filtrar septiembre
  en Cashflow no convierte el saldo actual de octubre en un saldo del mes filtrado.
- La primera ampliación del fixture comparó septiembre sin capital inicial con octubre
  después de cargarlo: falló esa expectativa. Se fechó el capital ficticio antes de ambos
  meses; la prueba pasó sin cambiar cálculos de producto.
- Primera suite completa iniciada sin procesos de pruebas ajenos. Durante la corrida
  apareció otra suite (`artisan test --exclude-group=lento`), por lo que se detuvo
  exclusivamente la propia. **Resultado interrumpido, no válido para entregar**.
  [Salida parcial](evidencia-t61-codex/suite-interrumpida-por-solapamiento.txt),
  [procesos comprobados](evidencia-t61-codex/procesos-solapados.json) y
  [huellas iniciales](evidencia-t61-codex/fuentes-antes-suite-interrumpida.json).
  Se repite en el worktree administrado `t61-traspasos`, desde 0768710a y copiando solo los archivos propios. Autoload comprobado contra esa raíz; 951 pruebas, sin las cinco nuevas de T52 aparecidas en la carpeta compartida. [Contexto anterior](evidencia-t61-codex/contexto-suite-antes-ajuste.json), [salida anterior](evidencia-t61-codex/suite-antes-ajuste-prueba-saldo-inicial.txt) y [huellas anteriores](evidencia-t61-codex/fuentes-antes-ajuste.json). No se atribuye ninguna falla
  de código a ese solapamiento.
- Suite aislada anterior: **946 aprobadas, 4 omitidas y 1 fallida**, 8401 aserciones, 923,64 s. Sin procesos de pruebas ajenos ni cambios de fuentes durante la corrida. La única falla exigía que el saldo inicial no apareciera en ninguna parte de Cashflow; la tarjeta nueva autorizada ahora debe mostrarlo. Se reprodujo [por separado](evidencia-t61-codex/saldo-inicial-antes.txt) y se acotó la exclusión al bloque de totales del período, conservando la protección del resultado. La dirigida final pasa. Se repite la suite completa con el ajuste.
- [Suite completa final](evidencia-t61-codex/suite-completa.txt): **947 aprobadas, 4 omitidas, 8425 aserciones, 901,60 s, salida 0**, en `wings_testing_codex`. [Contexto](evidencia-t61-codex/contexto-suite-aislada.json), [fuentes sin cambios](evidencia-t61-codex/cambios-durante-suite.json) y [control periódico](evidencia-t61-codex/control-solapamiento-suite-aislada.json): 167 observaciones, ningún proceso ajeno detectado. La filiación comprobada excluye al propio phpunit, nieto de artisan mediante cmd; se conserva el registro bruto del monitor.
- [Sintaxis](evidencia-t61-codex/sintaxis.txt), [build](evidencia-t61-codex/build.txt) y [compilación/limpieza de vistas](evidencia-t61-codex/vistas.txt): correctos.
- Total permanente del corte propio: **939 → 951** métodos de prueba. El checkout está basado en `0768710a`, en rama local `codex/t61-traspasos`; excluye los cinco tests T52 ajenos aparecidos después en la carpeta compartida. No se certifican esos cambios ajenos ni su integración con esta rama.

## Para la ayuda

### Llevar efectivo al banco

Un traspaso mueve plata del club de un medio a otro. No es una venta, un gasto,
un aporte de los dueños ni un retiro. Usalo, por ejemplo, cuando llevás $300.000
del efectivo acumulado para depositarlos en la cuenta del club.

1. En Cashflow, tocá **Nuevo**. Elegí **Transferencias internas — salidas** y
   **Salida a otro medio**, el medio **Efectivo**, $300.000 y la fecha de salida.
   Escribí una descripción y guardá.
2. Volvé a **Nuevo**. Elegí **Transferencias internas — entradas** y
   **Entrada desde otro medio**, la cuenta bancaria de destino, el importe que
   entró y su fecha. Escribí una descripción y guardá.

Efectivo baja y Banco sube. El resultado del club no cambia. Si registrás las dos
cargas en meses diferentes, «Resultado global» muestra la salida en un mes y la
entrada en el otro. Son dos registros independientes: revisá ambos al terminar.

Texto listo para T13; no se creó ninguna pantalla de ayuda.

Las salidas de las dos fallas históricas contienen HTML de pruebas; sus tokens CSRF ficticios se reemplazaron por `[OMITIDO_TOKEN_DE_PRUEBA]`. Los registros de consola normalizan finales de línea y espacios finales. No se alteraron el mensaje de error, los importes ni los resultados.

## Pendientes del corte CAB — conservados como antecedente

- Chrome real contra Wings local en base descartable: cargar ambos movimientos por UI,
  comprobar Cashflow/Reportes/Inicio y entrar como operativa. Capturas 1366 y 375,
  sin HTML guardado ni marcos. **Bloqueado por la herramienta**: informa
  «Local browser use is disabled in Work settings». Carlos mostró el interruptor
  Navegador activado; repetir con sesión CUA nueva devuelve el mismo error. No se
  deduce la causa ni se reemplaza el recorrido por una maqueta.
- Revisión visual de Carlos y comprobación independiente por otro agente.
- Resolver la discrepancia entre el permiso visible y la herramienta. Carlos confirmó «Listo» al pedido de reiniciar la aplicación; después se repitió getState y se reinició también la sesión CUA: el mismo error persistió. El reinicio no resolvió el bloqueo. No queda pendiente pedirle ese mismo paso.
- Carlos indicó el 20/10/2026: «Dejalo asentado que falta recorrido visual y lo haces desde CyE». Se entrega **a_verificar**, con el recorrido Chrome local y capturas 1366/375 a cargo de **Codex desde CyE**. Esta decisión difiere el recorrido; no lo da por realizado ni aprueba el visual de T61.
- Verificación independiente de lógica por otro agente y aprobación visual de Carlos.
- Carlos autorizó después «dejalo registrado y subi todo a github», sin preguntas. Se publica la rama propia, con este pendiente explícito, sin mezclar trabajo ajeno. No se despliega.

## Continuación desde CyE — 20/10/2026

[Control actual y capturas reales](VERIFICACION-T61-CYE.md). Recorrido CyE completado tras recuperar Chrome: capturas reales 1366/375, los cuatro formularios de subrubros, indicadores y acceso de la operativa. [Galería final para Carlos](RECORRIDO-T61.html). Visual de Carlos y verificación cruzada de lógica por Claude pendientes; sin autocierre ni despliegue.
