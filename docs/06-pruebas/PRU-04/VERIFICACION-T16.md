# T16 — verificación independiente, 10/10/2026

**DEVUELTO a Claude.** Codex CyE verificó `462e8b1` sobre una copia de `1972b48`, excluyendo los cambios simultáneos de T17/T18. Solo `wings_testing_codex`; sitio de prueba únicamente consultado.

Dos fallas reproducidas:

1. **La edición del rubro elude la regla de clasificación.** ADMIN abre Editar y envía `PUT /rubros/{id}`: Indumentaria pasa a EGRESO conservando sus APORTE; Retiros pasa a INGRESO conservando RETIRO. Ambos guardan con HTTP302; los movimientos posteriores capturan esas combinaciones inválidas. Falta el control en [RubroWebController::update](../../../app/Http/Controllers/RubroWebController.php), además del existente en SubrubroWebController.
2. **Nombre nuevo preexistente sin clasificación:** con «Retiro de dueños» y «Pago al organizador» ya creados bajo sus rubros correctos, pero con clasificación NULL, la migración deja ambos NULL. [aplicarCatalogo](../../../app/Support/ClasificacionSubrubros.php) salta cualquier nombre existente antes de completar lo faltante.

| Pedido | Comprobación |
|---|---|
| 1. Catálogo de Carlos | 18 entradas conocidas + los dos nuevos comprobados. Indumentaria: sus tres APORTE; Retiro de dueños: RETIRO; resto: NEGOCIO. |
| 2. Instalación existente | Migración real ejecutada dos veces: preserva clasificaciones y filas desconocidas; no duplica rubros/subrubros. Falla el borde del punto 2 de arriba. |
| 3. Instalación nueva | `migrate:fresh --seed` (DatabaseSeeder llama CatalogosSeeder): 18 subrubros, ninguno NULL; los dos rubros y sus subrubros, una vez cada uno. Filas completas inspeccionadas. |
| 4. Movimientos | Cashflow y operativos: NULL empieza a contar; las filas clasificadas conservan todos sus atributos. También al clasificar desde PUT. Cambiar luego el subrubro conserva la clasificación histórica de sus movimientos. |
| 5. Formulario | POST/PUT manuales rechazan vacío, APORTE en egreso, RETIRO en ingreso, array y valor desconocido, sin guardar. Admiten ambas opciones válidas por tipo. |
| 6. Reservados | Cuota Mensual, Inscripción y sueldo: editar/PUT/toggle, campos falsificados, padre cruzado y DELETE no los alteran. API sin rutas registradas. Altas/ediciones de profesor y operativo ignoran intentos de imponer otra clasificación. |
| 7. Otros caminos | Barrido de app, migraciones, seeders, rutas, scripts y JS; observador y reflejo de caja usan la clasificación guardada. Hallazgo: edición del padre, punto 1. |

**Observación separada:** la fase `DemoSeeder::fase2Profesores` aún crea seis subrubros de sueldo sin clasificación. Se ejecutó esa fase aislada y se miraron las seis filas. DemoSeeder está bloqueado en producción; no se ejecutó completo. PrimeraCargaCompletaSeeder y las altas habituales usan SubrubroSueldoService, que asigna NEGOCIO.

**Pruebas:** suite completa publicada de T16, 598 aprobadas/2 omitidas, 5.003 aserciones, 737,33 s. Controles independientes: **9 aprobados/3 fallidos**, 224 aserciones, 26,39 s ([salida](evidencia-t16/controles-independientes.txt)); las tres fallas corresponden a las dos causas anteriores. Los primeros controles tuvieron dos errores del verificador (fixture incompleto y HTTP esperado), corregidos antes de la entrega.

**Pantalla:** Inicio y Reportes muestran $164.000; Reportes desglosa cuotas $159.000 + inscripción $5.000. No aparece el aviso de sin clasificar. Capturas reales: [Inicio](evidencia-t16/inicio-test.png), [Reportes](evidencia-t16/reportes-test.png).

**No probado:** producción, instalación real del club, despliegue por consola, DemoSeeder completo ni una nueva evaluación del diseño ya aprobado. No se corrigió Wings ni se cobró/modificó el sitio de prueba.

Evidencia: [suite](evidencia-t16/suite-completa.txt), [instalación nueva](evidencia-t16/instalacion-nueva.txt), [filas del catálogo](evidencia-t16/instalacion-nueva.json), [casos reproducidos](evidencia-t16/casos.jsonl), [verificador](evidencia-t16/VerificacionT16Test.php).

Repetir los controles, sobre el código de T16 y sin otra suite de Codex simultánea:

```powershell
$env:DB_DATABASE='wings_testing_codex'
php vendor/bin/phpunit --configuration phpunit.xml --testdox docs/06-pruebas/PRU-04/evidencia-t16/VerificacionT16Test.php
```

Sigue: Claude corrige las dos causas; Codex verifica otra vez. El formulario conserva la aprobación de Carlos («T16 OK»).

## Segunda vuelta — DEVUELTO por concurrencia, 10/10/2026

`fcdc8f2` comprobado sobre copia fija de `d4f18d6`; sus tres archivos de lógica de T16 siguen iguales. **Los 12 controles originales pasan** (225 aserciones). Las dos fallas anteriores están corregidas. Otros **8 controles pasan** (269 aserciones): alta de rubro con hijos/ID falsificados, padre enviado por formulario, intento de mover por ruta cruzada, toggle, hijos inactivos, método falsificado, tipo enviado como array y cambio válido tras reclasificar. API sigue sin rutas registradas.

**Falla nueva reproducida:** dos pedidos de ADMIN simultáneos. A valida cambiar un rubro de INGRESO a EGRESO cuando su subrubro es NEGOCIO; antes de guardar A, B cambia ese subrubro a APORTE, válido todavía en INGRESO. A guarda después: queda **EGRESO + APORTE**. Ambos devuelven 302 sin errores. Ensayo con dos procesos y las rutas HTTP de Wings; una barrera solo ordena los pedidos después de la consulta de validación. No altera la respuesta de la consulta ni el código de la aplicación. [Filas y respuestas](evidencia-t16/casos-v2-concurrencia.jsonl), [prueba fallida](evidencia-t16/concurrencia-v2.txt), [verificador](evidencia-t16/VerificacionT16ConcurrenciaTest.php), [auxiliar](evidencia-t16/pedido-concurrente.php). Falta proteger la comprobación y ambas escrituras frente a ese cruce; no se implementó ningún arreglo.

**Nombres nuevos dentro de INGRESO:** ambos quedan NULL, sin duplicar ni cambiar filas; se ejecutó la migración dos veces y se resolvió luego por los formularios. Me parece correcto dejar pendiente la revisión del admin: evita imponer RETIRO a un ingreso y adivinar si «Pago al organizador» representa realmente el egreso conocido. NEGOCIO sería compatible con ingreso, pero el nombre solo no confirma esa interpretación.

**DemoSeeder:** confirmados los seis sueldos NULL y el rechazo antes de escribir en entorno production simulado, dentro de la base descartable. Recomiendo corregir ese seeder en una tarea separada para que los ensayos no oculten gastos; esta observación no causa la devolución de T16. No se ejecutó DemoSeeder completo.

Suite completa en `wings_testing_codex`; últimas líneas tal como salieron ([salida](evidencia-t16/suite-v2.txt)):

```text
  Tests:    2 skipped, 611 passed (5087 assertions)
  Duration: 785.65s
```

[12 controles](evidencia-t16/controles-v2-originales.txt) · [8 adicionales](evidencia-t16/controles-v2-adicionales.txt) · [casos originales](evidencia-t16/casos-v2-originales.jsonl) · [casos adicionales](evidencia-t16/casos-v2-adicionales.jsonl) · [verificador adicional](evidencia-t16/VerificacionT16SegundaVueltaTest.php). Concurrencia: 1 fallo/10 aserciones. Hubo errores del auxiliar antes de la ejecución válida (cargador de la copia y pedido no inicializado); corregidos, sin cambios de Wings.

Reproducir por separado, sin otra corrida en la misma base: `DB_DATABASE=wings_testing_codex`, `php vendor/bin/phpunit --configuration phpunit.xml docs/06-pruebas/PRU-04/evidencia-t16/VerificacionT16ConcurrenciaTest.php`. Para los otros controles, usar sus respectivas rutas; cada agente conserva su base propia.

**Tablero:** devuelto, tiene Claude, verifica Codex. Sigue corregir el cruce y volver a verificar. Ningún despliegue, visita o modificación del sitio de prueba en esta segunda vuelta; base del club y producción fuera del ensayo. El aspecto conserva la aprobación de Carlos.

## Tercera vuelta — APROBADO por Codex CyE, 10/10/2026

Corrección `b8ede87` comprobada sobre copia fija `aa33399`; los tres archivos de lógica de T16 siguen iguales. **28 controles aprobados:** los 12 originales (225 aserciones), los ocho adicionales (269) y ocho cruces independientes nuevos (220). Solo `wings_testing_codex`.

**Concurrencia:** APORTE y RETIRO, edición y alta de subrubro, en ambos órdenes. Dos procesos despachan pedidos por el núcleo HTTP de Wings. El primero pausa después de tomar el candado del rubro dentro de la transacción; el coordinador comprueba al segundo esperando ese mismo candado en `INNODB_TRX` e `INNODB_LOCK_WAITS` y libera al primero sin esperar a que termine el segundo. Los 16 pedidos terminan con HTTP302: el primero guarda; el segundo recibe «Esa opción no corresponde a este rubro» o «Cambiá primero qué es ese subrubro». Ninguna fila final combina APORTE con EGRESO ni RETIRO con INGRESO. Tiempos observados: 0,263–0,943 s por pedido, límite comprobado de 15 s. Ocho guardados posteriores también pasan y no queda transacción abierta.

**Caminos desde Wings:** cambio de tipo del rubro, alta y edición del subrubro toman el mismo candado antes de validar. El alta de rubro crea una fila nueva, no modifica el ID ni importa hijos enviados; toggle solo cambia activo. La API continúa sin rutas registradas. Las altas automáticas de profesor/operativo escriben NEGOCIO bajo Sueldos reservado; ese servicio no toma este candado, pero tampoco ofrece una clasificación incompatible ni permite cambiar el tipo reservado desde Wings. Carlos delimitó el alcance: «No, verificar los guardados desde Wings»; quedan fuera los cruces con migraciones/seeders de consola. Barrido y cuerpos leídos en fuentes reales; el índice MCP no respondió.

Suite completa; últimas líneas tal como salieron ([salida](evidencia-t16/suite-v3.txt)):

```text
  Tests:    2 skipped, 611 passed (5087 assertions)
  Duration: 637.19s
```

[12 controles](evidencia-t16/controles-v3-originales.txt) · [8 adicionales](evidencia-t16/controles-v3-adicionales.txt) · [8 cruces](evidencia-t16/concurrencia-v3.txt) · [filas/respuestas/esperas](evidencia-t16/casos-v3-concurrencia.jsonl) · [casos originales](evidencia-t16/casos-v3-originales.jsonl) · [casos adicionales](evidencia-t16/casos-v3-adicionales.jsonl) · [verificador propio](evidencia-t16/VerificacionT16TerceraVueltaTest.php) · [auxiliar propio](evidencia-t16/pedido-v3.php).

Preparación corregida antes del resultado válido: el rollback de migraciones fallaba por un índice antiguo y la observación con consultas cada 20 ms no capturaba algunas esperas. El verificador ahora rehace su base antes de cada caso, consulta las tablas de diagnóstico por separado cada 250 ms y exige comprobar cada espera; conserva el límite de 15 s. La aplicación no se cambió. No se leyó ni reutilizó el ensayo de Claude.

Repetir por separado, sin otra suite en la misma base:

```powershell
$env:DB_DATABASE='wings_testing_codex'
$env:T16_V3_EVIDENCIA=Join-Path $PWD 'casos-t16-concurrencia.jsonl'
php vendor/phpunit/phpunit/phpunit --configuration phpunit.xml --testdox docs/06-pruebas/PRU-04/evidencia-t16/VerificacionT16TerceraVueltaTest.php
```

**Tablero:** cerrado, hizo Claude, verifica Codex, tiene nadie. Aspecto ya aprobado por Carlos. Informe, estado y bitácora actualizados; sin arreglos de Wings, visita al sitio de prueba ni despliegue en esta vuelta. Base del club y producción intactas por Codex. La observación anterior sobre DemoSeeder conserva su alcance separado.
