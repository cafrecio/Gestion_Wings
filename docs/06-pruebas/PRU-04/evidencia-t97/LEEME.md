# Evidencia T97

Codex CAB, 01/11/2026. Datos ficticios; solo base descartable `wings_testing_codex`. No se operó el sitio de prueba ni producción.

- **Propuesta histórica:** `propuesta/`, `capturas/` y [APROBAR-T97.html](../APROBAR-T97.html). Aprobada por Carlos: «Esta OK ahora». El estado anulado de esa etapa era un fixture visual, no una operación implementada. Su verificador comprueba las fuentes anteriores a implementar y no se usa contra el producto ya modificado.
- **Implementación real:** `final/` y [VER-T97.html](../VER-T97.html). El generador recorre las rutas reales y ejecuta el POST de anulación; el estado posterior sale del servicio implementado. `final/verificar-final.py` valida estas fuentes, imágenes, enlaces, mediciones y la copia del manual.
- **Reglas y dinero:** `dirigidas.txt`, `concurrencia.json`, `mutaciones/resultado.json` y sus 19 salidas. Los primeros intentos conservados explican errores del soporte, no fallas del producto entregado.
- **Corte general:** `suite-rapida.txt`, `suite-completa.txt` e inventarios antes/después en `final/`. Los hashes permiten distinguir cambios de fuentes de cambios de HEAD en esta carpeta compartida.
- **Documentación:** `AUDITORIA-LECTORES.md`, `REVISION-MANUAL.md`, `manual.txt`, `sintaxis.txt`, `vistas.txt` y [informe completo](../IMPLEMENTACION-T97.md).

## Reproducir

Revisar primero procesos PHP: ninguna otra suite debe estar activa. Usar PHP de XAMPP y la base indicada; nunca la del club. Los tests conservan defensas adicionales contra bases no descartables.

```powershell
$env:DB_DATABASE='wings_testing_codex'
php artisan test tests/Feature/AnulacionTransferenciasT97Test.php tests/Feature/AnulacionTransferenciasConcurrentesT97Test.php tests/Feature/TraspasosEntreMediosT61Test.php tests/Feature/CashflowCorreccionesT27Test.php tests/Feature/CashflowCorreccionesConcurrentesT27Test.php
php artisan test --exclude-group=lento
php artisan test
```

Las contrapruebas modifican temporalmente solo dos fuentes propias y las restauran en `finally`; ejecutar el lote únicamente sin suites ni otro trabajo sobre esos archivos. Ver `phpunit-mutaciones-t97.php` y `probar-mutaciones.py`. Cada rojo intencional tiene su salida y hash restaurado.

Las capturas de celular usan un iframe de 375 dentro de una ventana grande; el login se capturó como control. No se usó `--window-size=375`. Los siete JPEG finales fueron revisados visualmente. Un primer intento de recorte de la barra devolvió una región incorrecta del navegador y se descartó; no se conserva como evidencia de la pantalla.

El verificador final acepta `--revision <commit>` para contrastar el código publicado sin tomar cambios paralelos de la carpeta, y `--solo-artefactos` para comprobar imágenes/enlaces sin certificar fuentes actuales. Cada modo guarda su resultado por separado. `origen.json` conserva las huellas de bytes del renderizado; `fuentes-lf.json` permite únicamente normalizar CRLF a LF, conforme a `.gitattributes`.

Los HTML guardados son respuestas reales, con referencias a los recursos públicos del build. El servidor estático `propuesta/servir-propuesta.py` solo expone esta evidencia y esos recursos en `127.0.0.1:8797`; impide envíos de formularios y no inicia Laravel ni conecta una base.

La revisión del autor no cierra T97: Claude debe verificar la lógica y el comportamiento de forma independiente. La aprobación visual de Carlos ya está registrada; no hay autorización de despliegue.

Las 19 contrapruebas corresponden al corte 7e72b0d4, anterior a T106. Para repetirlas se debe usar ese corte aislado. No se debe quitar la protección de hashes ni ejecutar el mutador sobre el controlador compartido modificado por otra tarea.
