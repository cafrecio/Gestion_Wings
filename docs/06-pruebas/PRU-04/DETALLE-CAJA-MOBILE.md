# Detalle de caja — corrección para celular

17/10/2026 — Codex CAB. Seguimiento de T37, solicitado por Carlos al mirar la captura:
«Detalle de caja esta desbordada. en Detalle 360 […] La captura que te paso es un desastre visual […] En resumen no esta mal».
Implementación local. Corrección visual aprobada por Carlos el 17/10/2026: «Bien ambas imagenes!!». Sin despliegue.

## Segunda revisión — botones a la derecha, 17/10

Carlos pidió: «Que los botones queden 2 y 2, alineados a la derecha» y
«en celular botones a la derecha». La primera entrega no respetó esa alineación.
La nueva botonera coloca Validar/Rechazar arriba y Resumen/Volver abajo, con ambas
columnas alineadas. Cuando solo existen Resumen/Volver quedan juntos a la derecha.
En escritorio se mantienen en una fila. Solo dos líneas de la vista cambian en esta vuelta.

Carlos aceptó la tabla con desplazamiento: «Detalla con scroll, si no tenemos nada mejor. Ok»
y el modal: «Modal OK». Después de recibir las dos imágenes a 375 de la nueva botonera,
Carlos confirmó: «Bien ambas imagenes!! Registralo asi Claude no lo deja como pendiente».
**Verificador visual: Carlos. Alineación móvil, scroll y modal aprobados.** Las imágenes aprobadas
son `alineacion/admin-cerrada-vacia-375.png` y `alineacion/operativo-abierta-movimientos-375.png`,
entregadas en `4b050ac`. Según AGENTS.md §6a, lo visual aprobado por Carlos no requiere
otra IA. A Claude le queda solo la revisión de las reglas de rechazo de T37; no dejar la
aprobación visual como pendiente ni reabrirla por falta de verificación de otra IA.

Seis visitas reales: ADMIN CERRADA sin movimientos y OPERATIVO ABIERTA con movimiento,
cada una a 360/375/1366. Se comprueban coordenadas de botones: dos filas de dos,
columnas coincidentes y borde derecho igual al interior de la tarjeta en celular;
una sola fila en escritorio. Sin desbordes. Login de control en marco de 360.
Mismo método de captura real descrito abajo. Recursos compilados con `npm run build`.
Vista real leída y contraste de coordenadas en navegador; la reindexación solicitada
devolvió que había otra operación activa, por lo que no se usó el índice para certificar esta modificación.

| Botonera | 360 | 375 | Escritorio |
|---|---|---|---|
| Cuatro botones, 2 × 2 en celular | [Captura](evidencia-detalle-mobile/alineacion/admin-cerrada-vacia-360.png) | [Captura](evidencia-detalle-mobile/alineacion/admin-cerrada-vacia-375.png) | [Captura](evidencia-detalle-mobile/alineacion/admin-cerrada-vacia-1366.png) |
| Solo Resumen/Volver, a la derecha | [Captura](evidencia-detalle-mobile/alineacion/operativo-abierta-movimientos-360.png) | [Captura](evidencia-detalle-mobile/alineacion/operativo-abierta-movimientos-375.png) | [Captura](evidencia-detalle-mobile/alineacion/operativo-abierta-movimientos-1366.png) |

[Mediciones de alineación](evidencia-detalle-mobile/alineacion/resultado.json).
[Inventario de las siete capturas nuevas](evidencia-detalle-mobile/alineacion/capturas.json).
Repetir con el mismo preparador/servidor y `node docs/06-pruebas/PRU-04/evidencia-detalle-mobile/recorrer-local.mjs despues --alineacion`.
Esta opción comprueba la botonera; no repite el modal ya aprobado. Las imágenes de las
secciones siguientes pertenecen al primer ajuste y se conservan como historia.

Suite completa posterior a esta segunda revisión: **784 aprobadas, 2 omitidas,
7395 aserciones, 901,37 s, salida 0** (786 pruebas). Base `wings_testing_codex`
y `LARAVEL_STORAGE_PATH=storage/app/detalle-mobile-botones-runtime`, carpeta exclusiva
creada con los subdirectorios del bloque de reproducción al final del informe.
Sintaxis y compilación de vistas correctas; siete capturas nuevas inspeccionadas y hashes comprobados.
Guardianes finales de documentos, tablero y CSP: **14 aprobadas/57 aserciones**.

## Qué se comprobó y corrigió

La misma vista sirve a los cuatro estados. Su cabecera imponía cuatro columnas aun en
360; Estado se salía en CERRADA, VALIDADA y RECHAZADA. La botonera de ADMIN en CERRADA
también se salía (documento de 391 px en ventana de 360). ABIERTA no desbordó la página
con estos datos sintéticos, pero su tabla comprimía y superponía títulos; la captura lo demuestra.
Se reprodujeron los ocho casos por rol antes de modificar la vista.

Solo cambia `resources/views/caja/detalle.blade.php`:

- Cabecera con la misma grilla adaptable de Resumen (una/dos/cuatro columnas).
- Contador y acciones pueden pasar de fila; los cuatro botones caben completos.
- Tabla conserva columnas e importes; ancho mínimo de 800 y desplazamiento interno,
  aviso en celular y región accesible por teclado. Se pueden consultar las acciones al final.

Sin cambios en Resumen, CSS compartido, JavaScript, permisos o reglas de caja.
Los scripts y eventos Blade anteriores se mantienen para T73; no se añade ninguno.
El aviso del rechazo en Inicio sigue pendiente por decisión previa de Carlos.

## Verificación en navegador

Chrome local con Wings real y datos ficticios en `wings_testing_codex`, sin usar datos del club.
48 visitas después: ADMIN y OPERATIVO propietario × ABIERTA/CERRADA/VALIDADA/RECHAZADA
× sin/con movimiento × 360/375/1366. Todas con ancho de documento igual a ventana,
sin recortes de información o botones. Las tablas se desplazan en celular y caben en escritorio.
La casilla de ADMIN CERRADA actualiza el contador a «1 de 1» en los tres anchos.
Ocho visitas adicionales a 375 comprueban que las acciones de las filas se alcanzan al deslizar.
También se repitió el rechazo con espacios: error visible y caja aún CERRADA; modal dentro de 360.

El celular se captura en iframe de 360/375 dentro de ventana 1366, con el DOM real que
devolvió Wings. La ruta no puede embeberse por `X-Frame-Options: DENY`; se usa `srcdoc`,
sin fabricar la pantalla. Las interacciones y medidas se comprueban en la página viva.
Login de control sin recorte. Capturas representativas inspeccionadas, no se deduce
calidad visual solo de la medida. No incluye otros módulos ni prueba de todas las longitudes posibles.

Resultados completos: [antes](evidencia-detalle-mobile/antes/resultado.json),
[48 visitas posteriores](evidencia-detalle-mobile/despues/resultado.json) y
[acciones a 375](evidencia-detalle-mobile/complemento/resultado.json).
Inventario de las 18 imágenes conservadas: [archivos y SHA-256](evidencia-detalle-mobile/capturas.json).

## Capturas del primer ajuste

| Caso | Antes | Después |
|---|---|---|
| Error señalado, ADMIN CERRADA a 360 | [Antes](evidencia-detalle-mobile/antes/admin-cerrada-error-360.png) | [Después](evidencia-detalle-mobile/despues/admin-cerrada-error-360.png) |
| ABIERTA con movimiento a 360 | [Tabla comprimida](evidencia-detalle-mobile/antes/admin-abierta-movimientos-360.png) | [Tabla deslizable](evidencia-detalle-mobile/despues/admin-abierta-movimientos-360.png) |
| OPERATIVO RECHAZADA con movimiento a 360 | [Antes](evidencia-detalle-mobile/antes/operativo-rechazada-movimientos-360.png) | [Después](evidencia-detalle-mobile/despues/operativo-rechazada-movimientos-360.png) |
| CERRADA con movimiento a 375 | — | [Inicio de tabla](evidencia-detalle-mobile/complemento/admin-cerrada-movimientos-375.png) · [Monto y casilla](evidencia-detalle-mobile/complemento/admin-cerrada-movimientos-375-derecha.png) |
| OPERATIVO ABIERTA a 375 | — | [Inicio de tabla](evidencia-detalle-mobile/complemento/operativo-abierta-movimientos-375.png) · [Editar y Eliminar](evidencia-detalle-mobile/complemento/operativo-abierta-movimientos-375-derecha.png) |
| Escritorio CERRADA | — | [1366](evidencia-detalle-mobile/despues/admin-cerrada-movimientos-1366.png) |
| Escritorio OPERATIVO ABIERTA | — | [1366](evidencia-detalle-mobile/despues/operativo-abierta-movimientos-1366.png) |
| Modal y control del método | — | [Modal a 360](evidencia-detalle-mobile/despues/admin-cerrada-modal-360.png) · [Login](evidencia-detalle-mobile/despues/login-control-360.png) |

## Reproducir

Sin suite simultánea en esta base; las credenciales efímeras quedan en `storage/app`, nunca se versionan.

```powershell
$env:DB_DATABASE='wings_testing_codex'
$env:APP_ENV='testing'
php docs/06-pruebas/PRU-04/evidencia-detalle-mobile/preparar-local.php
$env:SESSION_DRIVER='file'
$env:APP_URL='http://127.0.0.1:8737'
php artisan serve --host=127.0.0.1 --port=8737 --no-reload
# Otra consola, mismo repositorio:
node docs/06-pruebas/PRU-04/evidencia-detalle-mobile/recorrer-local.mjs despues
node docs/06-pruebas/PRU-04/evidencia-detalle-mobile/recorrer-local.mjs despues --complemento
```

La etapa `antes` del recorrido se ejecutó contra `f0e2509` antes de cambiar Detalle.
Las capturas redundantes generadas se retiran; los JSON conservan todas las mediciones.
La suite posterior rehace la base descartable. No se agregan pruebas PHP que solo comparen markup;
la evidencia de adaptación es conductual en navegador.

## Comprobaciones del primer ajuste

Sintaxis PHP y JavaScript correcta, vistas compiladas y caché retirada. Sin cambios en
`resources/css` ni otras vistas. Índice actualizado; enlaces y 18 hashes de capturas correctos.

Suite completa posterior: **786 pruebas, 783 aprobadas, 2 omitidas, 1 fallida;
7388 aserciones, 1039,20 s, salida 1**, base `wings_testing_codex`.
Falló `PrimeraCargaExcelTest::test_carga_web_exige_revision_previa_y_no_admite_payload_inventado`:
`hash_file` no encontró el Excel temporal de `storage/framework/testing/disks/local/primera-carga`.
Los ocho casos T37 pasaron en esa suite.

Repetición dirigida de Primera carga, T37 y guardianes: **40 aprobadas, 2 fallidas,
702 aserciones, 39,51 s**. Ambas fallidas son de Primera carga, por Excel temporal ausente
(líneas 290 y 335). `Storage::fake('local')` en su `setUp` limpia la carpeta de pruebas;
se verificó ese comportamiento en el cuerpo de `Storage::fake` de Laravel.
No se identificó qué proceso retiró los archivos durante estas corridas. No se atribuye
el fallo a Detalle ni se certifica la suite completa verde.

Se repite con `LARAVEL_STORAGE_PATH` en `storage/app/detalle-mobile-runtime`, carpeta
propia y descartable admitida por Laravel, conservando la misma base y el mismo código.
Resultado aislado: **42 aprobadas, 714 aserciones, 52,19 s, salida 0**.
Incluye los 20 casos de Primera carga, ocho T37 y 14 guardianes. Ningún cambio en
código ni en las pruebas de Primera carga. Esto confirma el resultado de esos casos
con almacenamiento propio; no convierte la corrida completa anterior en verde ni
demuestra qué proceso retiró los archivos de la carpeta compartida.
Después de actualizar el estado y el tablero, guardianes finales: **14 aprobadas,
57 aserciones, 0,44 s**. Diff sin errores; solo Detalle modificado entre vistas/CSS.

```powershell
$env:DB_DATABASE='wings_testing_codex'
$detalleRuntime=Join-Path (Get-Location) 'storage/app/detalle-mobile-runtime'
foreach ($carpeta in @('framework/cache/data','framework/views','framework/sessions','framework/testing','logs','app/private','app/public')) { New-Item -ItemType Directory -Force -Path (Join-Path $detalleRuntime $carpeta) | Out-Null }
$env:LARAVEL_STORAGE_PATH=$detalleRuntime
php artisan test --filter='PrimeraCargaExcelTest|CajaRechazoMotivoT37Test|CspSinCodigoIncrustadoTest|TableroAlDiaTest|DocumentacionNoMienteTest|DefectosNoDivergenTest|TablerosNoDivergenTest'
```
