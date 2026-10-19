# Corrección de T34 — Gemini CAB

19/10/2026. Entrega de corrección para verificación independiente de Codex.

## 1. Alcance y antecedentes

En la auditoría independiente realizada por Codex (`docs/06-pruebas/PRU-04/VERIFICACION-T34-T67-CODEX.md`), T67 fue aprobada y cerrada, mientras que T34 fue devuelta con dos hallazgos funcionales:
- **H1:** Una lista tomada con todos ausentes seguía mostrando «Asistencia: Pendiente» en su tarjeta (`resources/views/clases/_card.blade.php`), en contradicción con la ficha (`clases/show`), que reconocía lista tomada.
- **H2:** Una clase que ya integraba una liquidación cerrada (tanto en estado PENDIENTE de pago como PAGADA) podía volver a la cola «Sin presentes» mediante una petición directa `PATCH /clases/{id}/validar`, porque `toggleValidada()` invertía la marca sin comprobar `integraLiquidacionCerrada()`.

## 2. Correcciones implementadas

### H1 — Distinción de estados en la tarjeta de clase (`resources/views/clases/_card.blade.php`)

Se adaptó la evaluación de la etiqueta y color de asistencia en la tarjeta:
- **Sin asistencias registradas:** etiqueta **«Pendiente»** con color `var(--color-text-muted)`.
- **Con asistencias registradas y al menos un presente:** etiqueta **«Cargada»** con color `var(--color-success)`.
- **Con asistencias registradas y todos ausentes:** etiqueta **«Sin presentes»** con color `var(--color-warning, #d97706)`.

Se conservaron intactas las directivas del Design System:
- Sin alterar fuentes, padding ni estructura visual.
- Sin escribir JavaScript incrustado ni manejadores en línea.

### H2 — Protección del servidor en `toggleValidada` (`app/Http/Controllers/ClaseWebController.php`)

Se incorporó la protección estricta en el servidor al intentar alternar la validación:
```php
if ($clase->integraLiquidacionCerrada()) {
    $mensaje = 'No se puede modificar la validación: la clase integra una liquidación cerrada.';
    if (request()->expectsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
        return response()->json(['success' => false, 'message' => $mensaje], 422);
    }
    return back()->with('error', $mensaje);
}
```
Si la clase ya integra una liquidación `CERRADA` (sea por hora o por comisión de sus profesores en el mes), el endpoint rechaza la modificación con 422 (JSON) o redirección con flash de error, impidiendo que pierda la validación o reaparezca en el filtro `sin_presentes`.

## 3. Pruebas y evidencia

### Pruebas automatizadas

En `tests/Feature/ClaseSinAsistenciaYDniT34T67Test.php` se agregaron dos pruebas específicas que cubren ambos hallazgos:
1. `test_tarjeta_muestra_etiqueta_adecuada_segun_asistencia`:
   - Verifica que una clase sin asistencias muestre «Pendiente».
   - Verifica que una clase con lista y todos ausentes muestre «Sin presentes» (y no «Pendiente») en el listado general y en el filtro `sin_presentes`.
   - Verifica que una clase con presentes muestre «Cargada».
2. `test_clase_en_liquidacion_cerrada_no_puede_desmarcar_validacion`:
   - Construye una liquidación mensual cerrada con una clase validada.
   - Intenta ejecutar `PATCH /clases/{id}/toggle-validada`.
   - Verifica el mensaje de error en sesión, que `validada_para_liquidacion` se mantenga en `true`, y que la clase no vuelva a aparecer en `estado=sin_presentes`.

### Verificación de la suite

- `ClaseSinAsistenciaYDniT34T67Test`: 6 pasadas (43 aserciones).
- `CspSinCodigoIncrustadoTest`: 2 pasadas (0 bloques agregados, 0 manejadores agregados).
- `DocumentacionNoMienteTest`: 1 pasada (7 aserciones, sincronizado en 908 pruebas).
- Suite completa en `wings_testing_gemini`: 903 pasadas, 4 omitidas, 0 fallidas (8024 aserciones).

### Evidencia visual en navegador real (Chrome headless)

Generada a partir del HTML real del sistema en `docs/06-pruebas/PRU-04/evidencia-correccion-t34/`:
- `02-filtro-sin-presentes-escritorio-1366.png`: captura a 1366px que muestra la tarjeta con `Asistencia: Sin presentes` en ámbar.
- `02-filtro-sin-presentes-marco-360.png`: captura en marco móvil real de 360px (`<iframe>`), comprobando adaptación responsiva sin desbordes.

## 4. Entrega

Se entrega T34 en el tablero con:
`estado=a_verificar`, `hizo=Gemini`, `tiene=Codex`, `verifica=Codex`.
