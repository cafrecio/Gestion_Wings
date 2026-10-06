# Evidencia propia — Codex CyE, 06/10/2026

[Informe y dictámenes](../../VERIFICACION-CELULAR-COMPARTIDO.md) · [Visor](visor.html).
`capturas/`: 84 JPEG del navegador sobre Wings real, no maquetas. Marco CSS 375,
escritorio 1280; algunas respuestas con scrollbar reducen el área útil 15 px.
`mediciones.json` conserva geometría/estado accesible de los puntos recorridos.
Huellas e inspección: `propias-inspeccion.json`, `originales-inspeccion.json`,
`INSPECCION-102.md`; las láminas son solo ayudas internas de lectura.

Repetición desde la raíz, únicamente base descartable:

```powershell
$env:DB_DATABASE='wings_testing_codex'
php artisan test docs/06-pruebas/PRU-02/evidencia/verificacion-celular-compartido/PrepararEscenarioTest.php --filter=test_prepara_pantallas_de_estres
$env:APP_ENV='testing'
$env:SESSION_DRIVER='file'
php -S 127.0.0.1:8792 -t public docs/06-pruebas/PRU-02/evidencia/verificacion-celular-compartido/servidor.php
```

Entradas del laboratorio: `/marco?pagina=/login`,
`/marco?pagina=/clases/1&actor=profesor`,
`/marco?pagina=/caja/movimiento&actor=operativo&alto=667`.
La preparación deja datos ficticios en la base Codex para navegar; la suite completa
los reemplaza. El router deniega cualquier entorno/base distinta.
Los HTML temporales de respuestas con errores se generan al repetir la preparación;
no se versionan porque contienen tokens de sesión descartables.
El servidor del ensayo se detuvo al terminar.
