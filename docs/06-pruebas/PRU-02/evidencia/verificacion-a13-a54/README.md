# Evidencia A13 / B1 / A54 / A55

Codex CAB. Prueba realizada el **05/10/2026**, ordenada y documentada el **06/10/2026**.
[Informe con dictamen y límites](../../VERIFICACION-A13-A54.md).
Datos ficticios creados exclusivamente en `wings_testing_codex`. No es un respaldo del club.

## Capturas reales del navegador

| Evidencia | Archivo |
|---|---|
| Selector inicial: 3 positivos, más oferta de adelanto | [selector.png](selector.png) |
| Cuota PENDIENTE cero no contada | [selector-cero-pendiente.png](selector-cero-pendiente.png) |
| Cobranza inicial $151.000 | [cobranza-todos.png](cobranza-todos.png) |
| Ficha ADMIN tras anular seña, conserva segundo pago | [ficha-parcial-anulado.png](ficha-parcial-anulado.png) |
| Ficha tras anular varios meses e inscripción | [ficha-varios-meses-anulado.png](ficha-varios-meses-anulado.png) |
| Noviembre anulado aparece octubre en historial | [historial-mes-equivocado.png](historial-mes-equivocado.png) |
| Contraasiento negativo dibujado positivo | [cashflow-transferencia-anulada.png](cashflow-transferencia-anulada.png) |
| Dos deportes, selector $5.000 / $0 | [selector-dos-deportes.png](selector-dos-deportes.png) |
| Ficha del segundo deporte con inscripción $5.000 | [ficha-segundo-deporte-inscripcion.png](ficha-segundo-deporte-inscripcion.png) |
| Operativo sin acción de anular ADMIN | [operativo-ficha-sin-anular.png](operativo-ficha-sin-anular.png) |
| Cobro OPERATIVO real, caja propia | [operativo-cobro-caja.png](operativo-cobro-caja.png) |
| Resumen Efectivo $40.000, caja ABIERTA | [operativo-resumen.png](operativo-resumen.png) |
| Segundo intento desde pestaña antigua rechazado | [doble-pestana-rechazada.png](doble-pestana-rechazada.png) |

Los distintos conteos corresponden a etapas sucesivas en la misma base, no a errores de conteo.
No hay capturas de una maqueta fabricada. Se retiraron tres tomas iniciales con assets
incompletos (`adelantado-48000-antes`, `admin-cobro-sin-caja`, `anular-desde-ficha`).
También se retiraron `crear_ficha_inline.mjs` y su HTML redundante, no las capturas ajenas.

## Datos y recibos

- [Lectura final de filas del 06/10](estado-descartable-2026-10-06.json): solo datos sintéticos,
  sin `users`, tokens, sesiones o configuración de conexión.
- Recibo 3: [PDF original](recibo-3-anulado.pdf) y [render](recibo-3-anulado.png).
- Recibo 5: [PDF original](recibo-5-anulado.pdf) y [render](recibo-5-anulado.png).

Los PDFs provienen del generador de Wings, no de HTML armado a mano; renders inspeccionados.

## Herramientas de reproducción — NO ejecutar en el club

Se conservaron fuera de `tests/Feature`, junto a la evidencia:

- [verificacion-local.php](herramientas/verificacion-local.php): fixture, servicios, concurrencia,
  inspección de filas, generación de PDFs. Guard CLI/testing/base exacta; fecha congelada al 05/10.
- [router.php](herramientas/router.php): Wings real, login real, solo localhost, `testing`,
  `wings_testing_codex`; archivos y correo aislados. Fecha de ensayo congelada al 05/10.
- [VerificacionA13PermisosTest.php](herramientas/VerificacionA13PermisosTest.php): dos ensayos HTTP
  sobre la fixture existente. No usa RefreshDatabase ni forma parte de la suite permanente.

La fixture necesita esquema vigente y catálogos, **sin alumnos ni pagos** en la base propia.
No ejecutar `fixture` ni `checks` sobre el estado final: crean casos y no son idempotentes.
No se prescribe limpiar automáticamente una base, aunque sea descartable: primero confirmar
que no haya otra sesión de Codex usándola. Nunca sustituir el nombre por `gestion_wings`.

Comandos desde la raíz, en PowerShell (PHP de XAMPP):

```powershell
$env:APP_ENV='testing'
$env:DB_DATABASE='wings_testing_codex'
php docs/06-pruebas/PRU-02/evidencia/verificacion-a13-a54/herramientas/verificacion-local.php inspect
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php docs/06-pruebas/PRU-02/evidencia/verificacion-a13-a54/herramientas/VerificacionA13PermisosTest.php
```

Para reconstruir en una base propia vacía ya preparada: `fixture`, `checks`, `race-setup`.
`race anular <pago> 3` retiene la primera transacción; desde otro proceso se ejecuta
`race cobrar <pago> 0` o `race anular <pago> 0`. Intercambiar orden para el segundo caso.
`race-checks` comprueba el estado de los tres casos inmediatamente después de esas carreras,
antes de cobrar G por pantalla. `cero` fuerza la deuda cero a PENDIENTE; `multi-setup`
crea el segundo deporte de la misma persona. Los IDs corresponden a la fixture vacía.

Servidor opcional de ensayo, no producción:

```powershell
php -S 127.0.0.1:8797 -t public docs/06-pruebas/PRU-02/evidencia/verificacion-a13-a54/herramientas/router.php
```

Los usuarios/clave que crea la fixture son **ficticios y públicos**, solo para esa base
aislada; no reutilizarlos en ninguna instalación real. Cerrar el proceso de ensayo al terminar.
El informe distingue pasos de pantalla, servicios, pruebas HTTP e inferencias.
