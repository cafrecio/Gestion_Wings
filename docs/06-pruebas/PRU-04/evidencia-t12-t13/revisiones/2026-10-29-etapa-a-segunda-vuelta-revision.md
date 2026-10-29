# Informe de Auditoría y Segunda Vuelta de Revisión Independiente: Manual del Usuario (Etapa A - 20 Páginas Operativas)

> **Fecha:** 29/10/2026  
> **Revisor:** Revisor independiente (Gemini Antigravity)  
> **Protocolo aplicado:** `docs/06-pruebas/PRU-04/REVISOR-MANUAL.md`  
> **Alcance:** 20 páginas operativas de la Etapa A (`resources/manual/operativo/*.md`) y sus correspondientes 20 fichas de fuentes (`docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/operativo-*.md`).  
> **Objetivo:** Auditar exhaustivamente la segunda vuelta de ajustes, verificando las 4 páginas corregidas, las condiciones en código (`if`) y las tablas de rechazos y avisos en las 20 fichas de fuentes.

---

## 1. Verificación automática del manual (`verificar-manual.php`)

Se ejecutó el validador estricto del proyecto:
```bash
php scripts/manual/verificar-manual.php
```

**Resultado:**
```text
22 páginas revisadas, 0 problemas.
```
- **Rutas verificadas:** Todas las rutas declaradas en los frontmatters existen en `routes/web.php` y corresponden a las pantallas operativas autorizadas.
- **Términos literales:** Todos los literales entre comillas latinas «» y negritas existen fielmente en las vistas Blade, controladores, servicios y modelos de Wings.
- **Capturas:** Todas las imágenes referenciadas existen en disco bajo `resources/manual/img/operativo/` con identificadores y hashes válidos.

---

## 2. Auditoría de las 4 páginas modificadas en la segunda vuelta

Se contrastaron línea por línea las 4 páginas operativas modificadas frente al código fuente y las reglas de negocio de Wings:

### 2.1. `tomar-lista-mostrador.md`
- **Exceso del plan (EXTRA / RECUPERA) sin trabar el guardado:**
  - En la línea 34 del manual se describe: *«Si el alumno ya asistió esa semana la cantidad de veces prevista en su plan (por ejemplo, si tiene 2/2 y marcás el presente), se despliega el aviso «Excede el plan — indicar motivo» con un selector que ofrece dos opciones: «Clase adicional (EXTRA)» y «Recupera clase perdida (RECUPERA)». Si no elegís ninguna opción, el sistema asigna por defecto EXTRA y no traba el guardado de la lista.»*.
  - En el código de Wings (`resources/views/clases/show.blade.php:503-514` y `resources/js/clases-show.js:90-95`), el `<select id="motivo-{{ $alumno->id }}">` ofrece por defecto la opción `EXTRA`, y en `app/Http/Controllers/ClaseWebController.php:471-473` el backend asigna `'EXTRA'` si el campo no fue provisto. No bloquea el guardado en ningún momento.
- **Mensajes de B3 para alumno deudor y nuevo con 2 presentes:**
  - En la línea 35 del manual se indica: *«Si el alumno está Deudor o si es un alumno nuevo sin ningún pago de cuota y ya tiene 2 presentes registrados, Wings despliega el campo obligatorio Motivo con el mensaje correspondiente: «Tiene deuda vencida. Indicá el motivo para dejarlo entrar.» o «Todavía no registró un pago de cuota. Indicá el motivo para dejarlo entrar.».»*.
  - En `app/Services/ExcepcionCobranzaService.php:41-48`:
    ```php
    $tipo = $nuevo ? ((int) $previas->get($alumno->id, 0) >= 2 ? 'ASISTENCIA_NUEVO' : null)
        : ($estado === CobranzaEstadoService::ESTADO_DEUDOR ? 'ASISTENCIA_DEUDOR' : null);
    ...
    'message' => $nuevo ? 'Todavía no registró un pago de cuota. Indicá el motivo para dejarlo entrar.'
        : 'Tiene deuda vencida. Indicá el motivo para dejarlo entrar.'
    ```
    Ambos mensajes y condiciones de disparo están rigurosamente conservados y coinciden de manera exacta.

### 2.2. `registrar-gasto-caja.md`
- **Ausencia de etiquetas de trabajo interno («[DU-20]»):**
  - Se verificó el frontmatter, título, notas y cuerpo del texto: no existe ninguna mención a `[DU-20]` ni `DU-20`. El frontmatter declara limpiamente `du: CAJ-002b`.
- **Rechazo ante gasto que supera el disponible en el cajón:**
  - En la línea 54 del manual se describe: *«Wings no permite registrar ni editar un egreso si el monto supera los billetes físicos disponibles en el cajón y te mostrará el mensaje: «El gasto supera el efectivo disponible en el cajón. Primero registrá un aporte de efectivo y después el gasto.». En ese caso, si ingresó dinero para cubrir la compra, registrá primero el aporte de efectivo como un INGRESO en tu caja y luego cargá el egreso.»*.
  - En `app/Services/CajaService.php:614-616`:
    ```php
    if ($disponible - $viejo + $nuevo < 0) {
        throw ValidationException::withMessages([
            'monto' => 'El gasto supera el efectivo disponible en el cajón. Primero registrá un aporte de efectivo y después el gasto.'
        ]);
    }
    ```
    Coincidencia literal exacta al 100%.

### 2.3. `cobrar-pago-parcial.md`
- **Panel de confirmación de cobro parcial y motivo obligatorio vs Observaciones:**
  - En la línea 45 del manual se explica: *«Al detectar que el importe ingresado es menor que el valor de la cuota, Wings abre el panel de confirmación con la advertencia: «Es un cobro parcial: queda parte de la cuota pendiente.» y te solicita: «Indicá el motivo; el dueño lo verá en Inicio y en el resumen diario.». Completá el campo obligatorio Motivo (que es la justificación de la excepción para el dueño, distinta del campo opcional Observaciones del recibo) y tocá Cobrar dentro del panel. Sin motivo no cobra.»*.
  - En la línea 43 se distingue claramente el campo opcional: *«En el formulario podés agregar una nota opcional en Observaciones para el recibo (por ejemplo: "Entrega a cuenta cuota octubre").»*.
  - En `app/Services/ExcepcionCobranzaService.php:93, 100`:
    ```php
    if (isset($tipos['COBRO_PARCIAL'])) $mensajes[] = 'Es un cobro parcial: queda parte de la cuota pendiente.';
    ...
    'message' => implode(' ', $mensajes).' Indicá el motivo; el dueño lo verá en Inicio y en el resumen diario.'
    ```
  - En `resources/views/caja/cobrar.blade.php:305-323` el modal exige el campo de motivo obligatorio cuando se detecta cobro parcial.
- **Ausencia de etiquetas de trabajo interno («DU-27»):**
  - Se verificó todo el documento: el frontmatter declara `du: COB-002` y no existe rastro alguno de `DU-27`.

### 2.4. `cobrar-cuota.md`
- **Sección «Qué hago si…» completa y auditada:**
  1. *Deuda anterior impaga (línea 68):* Explica el panel con «¿Cobrás igual solo los meses elegidos?», solicitando «Indicá el motivo; el dueño lo verá en Inicio y en el resumen diario.». Coincide con `app/Services/ExcepcionCobranzaService.php:96-100`.
  2. *Pago adelantado (línea 71):* Explica la advertencia «Estás cobrando por adelantado» ese período e indicando «El importe queda fijo aunque después cambie la cuota.». Coincide con `app/Http/Controllers/CajaWebController.php:928-929`.
  3. *Fecha de mes cerrado (línea 74):* Explica el aviso «Esta fecha de pago corresponde a un mes anterior ya cerrado. El cobro quedará registrado con esa fecha real, pero entrará en la caja de hoy y modificará el reporte de ese mes.». Coincide con `app/Http/Controllers/CajaWebController.php:900`.
  4. *Alumno inactivo (línea 77):* Explica el panel **Alumno inactivo** con la pregunta «¿Pasa a activo?», remitiendo mediante enlace relativo directo a [Pasar a un alumno a inactivo](pasar-alumno-inactivo.md) y detallando las opciones **Sí**, **No** y **Cancelar**. Coincide con `app/Http/Controllers/CajaWebController.php:936-954`.
- **Ausencia de etiquetas de trabajo interno («DU-27»):**
  - Se verificó todo el documento: el frontmatter declara `du: COB-001` y no existe mención a `DU-27`.

---

## 3. Auditoría de las 20 fichas de fuentes (Punto 3 de la consigna)

Se examinaron las 20 fichas ubicadas en `docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/operativo-*.md`:

### 3.1. Sección «2. Rechazos y avisos de la acción»
Las 20 fichas de fuentes cuentan con la sección `## 2. Rechazos y avisos de la acción` debidamente incorporada, presentando la tabla con las columnas:
- `Rechazo / Aviso`
- `Archivo:Línea`
- `¿Está en la página?`
- `Comentario / Motivo`

En ellas se documentan exhaustivamente los `throw`, `withMessages`, `abort`, `with('error')`, `with('success')` y textos de modales/paneles de confirmación del flujo.

### 3.2. Columna «Condición en el código (`if`)»
Se revisó cada fila de la tabla 1 en las 20 fichas. Se comprobó que la gran mayoría de las filas citan sentencias condicionales reales (`if`), directivas de plantilla (`@if`), reglas del validador de Laravel (`'campo' => 'required|...'` / `Rule::...`) o expresiones ternarias.

Sin embargo, se identificaron casos puntuales donde se consignaron firmas de función, directivas HTML o descripciones en prosa en lugar de una regla o condicional estricto:
1. `operativo-abrir-caja.md:16`: Consigna la firma de método `public function propuestaApertura(): array` en lugar del condicional de asignación de saldo inicial heredado.
2. `operativo-abrir-caja.md:31`: En la tabla 2 se cita `Caja abierta exitosamente.` en `CajaWebController.php:90`. En el código real de Wings (`CajaWebController.php:80`), el mensaje de éxito es `'Caja abierta con el efectivo declarado.'`.
3. `operativo-alta-alumno.md:20`: Consigna markup Blade `<x-ds.button ...>Cancelar</x-ds.button>` para ilustrar la acción de cancelar.
4. `operativo-cambiar-grupo-alumno.md:15, 17`: Consigna la definición de ruta `Route::get(...)` y la firma `public function cambiar(...)` en lugar del condicional de actualización.
5. `operativo-cerrar-caja.md:19, 27`: En la línea 19 consigna una asignación aritmética (`$caja->diferencia = $contado - $esperado;`). En la tabla 2 (línea 27) cita `Solo se puede cerrar una caja abierta o rechazada.` en `CajaService.php:360`, mientras que la excepción real lanzada en `CajaService.php:366` es `throw new \Exception('La caja no está en estado editable.');`.
6. `operativo-cobrar-pago-parcial.md:17`: Consigna un cálculo aritmético (`$saldoRestante = $cuota->saldo_pendiente_centavos - ...;`) para ilustrar el saldo pendiente.
7. `operativo-consultar-clases-operativo.md:15, 19`: Consigna una declaración de ruta y una directiva `@section`.
8. `operativo-consultar-cobranza.md:18`: Consigna el markup del botón Blade `<x-ds.button ...>Cobrar</x-ds.button>`.
9. `operativo-consultar-grupos-planes.md:17, 18`: Consigna el atributo HTML `data-enviar-al-cambiar` y el botón `<x-ds.button ...>Ver</x-ds.button>`.
10. `operativo-consultar-historial-cajas.md:18, 20`: Consigna markup HTML y texto descriptivo (`La vista no cuenta con botones...`).
11. `operativo-consultar-movimientos.md:16, 18, 19`: Consigna una consulta Eloquent y texto explicativo.
12. `operativo-modificar-alumno.md:15, 19, 20`: Consigna declaración de ruta, `return redirect()` y markup de botón.
13. `operativo-pasar-alumno-inactivo.md:16`: Consigna la sentencia de actualización `$alumno->update(...)`.
14. `operativo-rectificar-caja.md:16, 18, 19, 20`: Consigna helper de ruta, prosa explicativa y botón HTML. En la línea 18 afirma que en caja rechazada se toca Cerrar para «realizar nuevamente el conteo», cuando en rigor el conteo físico original es de solo lectura y se conserva.
15. `operativo-registrar-ingreso-caja.md:31`: En la tabla 2 cita `Movimiento registrado exitosamente.` en `CajaWebController.php:360`. En el código real de Wings (`CajaWebController.php:1128`), el mensaje de éxito es `'Movimiento registrado.'` (la línea 360 corresponde al método `resumen()`).
16. `operativo-resolver-revision-cobranza.md:19`: Consigna la llamada estática `AlumnoRevisionCobranza::reactivarAuto(...)`.
17. `operativo-tomar-lista-mostrador.md:17, 18`: Consigna una ruta y un `addEventListener` en JS.

*(Nota de auditoría: Ninguna de estas observaciones de las fichas de fuentes afecta el contenido de las páginas del manual destinadas a los usuarios, las cuales están 100% sincronizadas con el comportamiento real de Wings).*

---

## 4. Clasificación de Hallazgos

Conforme al criterio de `REVISOR-MANUAL.md`:
- **Grave:** El lector no puede hacer lo que se le indica, o hace algo que pierde plata o datos.
- **Media:** Un nombre, un mensaje o un camino distinto del real; el lector se confunde pero sale. Omisión de avisos que la operativa ve a diario.
- **Menor:** Redacción, ajustes de citas técnicas en fichas de fuentes o literales secundarios sin impacto en el flujo operativo.

### Tabla de Hallazgos en las 20 Páginas del Manual del Usuario

| # | Gravedad | Página y línea | Qué dice la página | Qué hace Wings (archivo:línea) | Estado |
|---|---|---|---|---|---|
| - | - | - | *No se detectaron discrepancias funcionales en las 20 páginas operativas.* | *Comportamiento verificado en vistas, controladores y servicios.* | **Cerrado** |

### Tabla de Hallazgos en las Fichas de Fuentes

| # | Gravedad | Ficha de fuentes y línea | Cita en la ficha | Realidad en Wings (archivo:línea) |
|---|---|---|---|---|
| F1 | **Menor** | `fuentes/operativo-abrir-caja.md:31` | `Caja abierta exitosamente.` en `CajaWebController.php:90` | El mensaje real de éxito es `'Caja abierta con el efectivo declarado.'` en `app/Http/Controllers/CajaWebController.php:80`. |
| F2 | **Menor** | `fuentes/operativo-registrar-ingreso-caja.md:31` | `Movimiento registrado exitosamente.` en `CajaWebController.php:360` | El mensaje real de éxito es `'Movimiento registrado.'` en `app/Http/Controllers/CajaWebController.php:1128`. |
| F3 | **Menor** | `fuentes/operativo-cerrar-caja.md:27` | `Solo se puede cerrar una caja abierta o rechazada.` en `CajaService.php:360` | La excepción real lanzada es `throw new \Exception('La caja no está en estado editable.');` en `app/Services/CajaService.php:366`. |
| F4 | **Menor** | Varios archivos (ver §3.2) | Firmas de método, markup HTML o sentencias de actualización en columna «Condición en el código (`if`)» | Corresponden a elementos sintácticos auxiliares de consulta en lugar de condicionales de ramificación (`if` o reglas de validación). |

---

## 5. Verificación de Roles y Permisos (Contratos de Acceso)

Se comprobó que las 20 páginas describen operaciones accesibles y permitidas para el rol **OPERATIVO**:
- **Caja y Cobranza:** El rol operativo puede abrir turno, registrar cobros, registrar ingresos y egresos, anular cobros propios (con caja abierta o rechazada), realizar el arqueo de cierre y consultar movimientos e historial (`PERMISOS-ROLES.md:19`).
- **Alumnos y Familias:** Puede dar de alta alumnos, editar legajos, reasignar grupos y planes, cambiar estado a Activo/Inactivo y resolver casos en Revisión de cobranza. La eliminación física permanente de registros (`DELETE`) está reservada exclusivamente a ADMIN y el manual lo advierte explícitamente.
- **Grupos y Clases:** Puede consultar la nómina deportiva, aranceles y tomar lista en el mostrador. La creación o edición de tarifas y categorías está restringida a ADMIN (`routes/web.php:200-210`), tal como describe fielmente `consultar-grupos-planes.md:54`.

---

## 6. Verificación de Capturas de Pantalla

- Las 53 capturas referenciadas en las 20 páginas operativas se encuentran alojadas en `resources/manual/img/operativo/`.
- Todas corresponden a la vista real de la aplicación en el estado correspondiente al paso, ejecutadas con la sesión operativa de **Sandra Operativa** (sin módulos administrativos como Cashflow o Liquidaciones en el menú lateral).
- No existen imágenes rotas ni referencias a maquetas externas.

---

## 7. Conclusión y Resumen de Hallazgos Abiertos

- **Hallazgos Graves abiertos:** **0**
- **Hallazgos Medios abiertos:** **0**
- **Hallazgos Menores abiertos:** **0** en las páginas del manual (4 observaciones menores de pulido técnico en las fichas de fuentes documentadas en §4).
- **Validación automática:** `php scripts/manual/verificar-manual.php` &rarr; **22 páginas revisadas, 0 problemas**.

**Dictamen:** Las 20 páginas operativas de la Etapa A del Manual de Usuario de Wings se encuentran **auditadas, completamente alineadas con el código fuente y listas para su aprobación final**.
