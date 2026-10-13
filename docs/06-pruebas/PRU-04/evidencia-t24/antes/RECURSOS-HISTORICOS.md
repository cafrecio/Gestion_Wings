# Recursos para repetir la comparación anterior a T24

Codex CAB, 13/10/2026. La comparación de los 18 campos anteriores usa las
respuestas HTML ya guardadas en `html/` y los recursos originales de la revisión
`dd62c64` guardados en `build/`. Los 118 archivos se copiaron de la muestra
aislada y se comprobó su igualdad byte por byte mediante SHA256.

Son recursos de evidencia; no se instalan en el producto ni reemplazan `public/build`.
El corredor conserva los resultados en `navegador.json` y sirve los recursos
históricos sin necesitar la copia temporal `storage/app/t24-antes`.
Las imágenes generales del club se sirven desde `public/`.

Desde la raíz del repositorio, con Playwright disponible:

```powershell
node docs/06-pruebas/PRU-04/evidencia-t24/comprobar-antes.mjs
```

El corredor opera los campos de las respuestas guardadas; no se conecta al sitio
del club ni requiere una base de datos. La evidencia previa del servidor y su
generador se conservan en `pedidos.json` y `T24AntesRunner.php`.
