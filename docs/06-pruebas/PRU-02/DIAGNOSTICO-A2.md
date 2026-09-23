# A2/B2 — 60 DEUDOR frente a 20 con deuda

23/09/2026. Diagnóstico sobre `bbbfd01`, después de `git pull` (sin novedades). Sin implementación; pendiente de revisión independiente.

**Seguimiento:** Carlos aprobó después la eliminación global del criterio "nunca pagó",
acompañada por cuota creada en el alta. El diagnóstico y sus líneas se conservan como
evidencia del estado anterior; implementación y pruebas en [IMPLEMENTACION-A2.md](IMPLEMENTACION-A2.md).

**Conclusión:** para el padrón descrito por Carlos, **20 con deuda es correcto**. Los 40 que arrancaron al día quedan mal clasificados por Cobranza. La causa está verificada en el código local; los totales del servidor proceden del pedido, no de una consulta nueva.

**Qué cuenta cada pantalla — verificado en código.** Inicio cuenta alumnos activos distintos con alguna cuota cuyo `monto_pagado < monto_original`, excluyendo PAGADA y CONDONADA: `app/Http/Controllers/OperativoDashboardController.php:57–61`; lo muestra `resources/views/operativo/dashboard.blade.php:189–191`. Cobranza cuenta activos clasificados como DEUDOR: `app/Http/Controllers/CobranzaWebController.php:32` → `app/Services/CobranzaEstadoService.php:135–166` → `resources/views/cobranza/index.blade.php:13,35–36`. El resumen es global; los filtros se aplican al listado, no a esas tarjetas. Ambos cuentan registros de alumno, no personas por DNI.

**Causa — verificada.** `CobranzaEstadoService.php:30–32,96–99,141–144` busca un Pago COMPLETADO con componente de cuota (`app/Models/Pago.php:71–73`). Aunque reconoce correctamente que una deuda cero/PAGADA no está impaga (`CobranzaEstadoService.php:208–212`), después ejecuta, en líneas 234–235:

```php
if (!$tienePagos || $impagasAnteriores->isNotEmpty()) {
    $estado = self::ESTADO_DEUDOR;
```

La ausencia de pagos basta, aunque no quede nada pendiente. El importador efectivamente crea el cierre con ambos importes en cero y estado PAGADA, sin crear pagos (`app/Services/CargaSaldoInicialPadronService.php:145–178`). Coincide con el procedimiento documental de `../CARGA-PADRON-SALDO-INICIAL.md`: declarar saldo inicial no representa dinero ingresado. No se encontró un error de importación que justifique inventar cobros. Además, un cierre del mes puede coexistir con deuda anterior (líneas 129–135): **tener un cierre cero no basta para declarar al alumno al día**.

**Alcance — grafo y archivos confirmados.** Afecta tarjetas, etiquetas y filtro por estado de Cobranza (`CobranzaWebController.php:27,32`; servicio:119–125,158–194), y listado/ficha de Alumnos (`app/Http/Controllers/AlumnoWebController.php:78,114`; vistas `alumnos/index.blade.php:101–115`, `alumnos/show.blade.php:128–143`). También lo consume `app/Http/Controllers/CobranzaController.php:27,52,72`, pero esa API está deshabilitada (`bootstrap/app.php:14`). No se propaga por esta clasificación a Revisión: consulta su propia cola (`app/Http/Controllers/RevisionCobranzaWebController.php:19–27`; `app/Services/RevisionCobranzaService.php:27–70`); tampoco al resumen de avisos, que lee esa cola (`app/Services/AvisoAdminService.php:82–86`). El generador mensual decide por deuda existente, plan, asistencia y alta/pago reciente, no por DEUDOR (`app/Console/Commands/GenerarDeudasMensualesCommand.php:65–123`). Corregir la etiqueta no cambia esos criterios.

**Qué cambiar — propuesta, no implementada.**

- Recomiendo corregir el cálculo compartido para reconocer el arranque explícitamente conciliado sin exigir un pago ficticio, conservando la evaluación de saldos anteriores y del mes. Mantener los cierres importados. Definir una señal fiable de ese origen; hoy se registra en `observaciones`, y depender de texto libre sería frágil.
- Alternativa: eliminar globalmente `!$tienePagos`. Es más simple, pero también cambia a AL_DIA al alta nueva sin pagos ni deudas; Carlos debe decidir esa regla antes. Tampoco debe suponerse que “con deuda” equivale siempre a DEUDOR: incluye cuotas EN_PLAZO o MOROSO.
- No recomiendo crear pagos para arreglar la etiqueta ni eliminar los cierres: falsearía el historial de cobros o permitiría generar otra vez el mes cerrado (generador:65–71). Verificar la futura corrección con cero inicial, deuda anterior más cierre cero y alta nueva sin historial.

**No comprobado:** contenido actual de las 60 fichas en la base de prueba, versión desplegada y reproducción ejecutada. No se consultó ni modificó el servidor ni se corrieron pruebas. El grafo omitió algunos consumidores encontrados por búsqueda textual; todas las referencias citadas se confirmaron leyendo el código. A2/B2 permanecen abiertos para decisión de Carlos y verificación por otro agente.
