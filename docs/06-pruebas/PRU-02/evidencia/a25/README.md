# A25 — Pruebas previas, 06/10/2026

**Autor:** Codex CAB. **Estado:** preparación; no implementación ni cierre.
Reglas y decisiones: [A25](../../../../05-pendientes/A25-CAMBIO-INICIAL-CAJA.md).

## Entorno y resultado real

- Código actual de main; aplicación sin modificaciones de A25.
- `APP_ENV=testing`, `DB_DATABASE=wings_testing_codex`; MariaDB local descartable.
- Ejecutado directamente sobre `CajaCambioInicialA25Test.php`, fuera de la suite compartida.
- **12 pruebas, 15 aserciones: 3 fallos y 9 errores**, 5,392 s; no se presenta como 12 bugs reproducidos.
- Los 3 fallos comprueban comportamientos existentes: apertura automática sin declarar
  efectivo, validación que cierra una ABIERTA sin conteo y cierre sin importe contado.
- Los 9 errores son `CajaService::configurarMostrador()` inexistente. Preparan las reglas
  nuevas; todavía no ejecutan ni verifican los cálculos futuros.
- No se modificaron base del club, servidor, vistas ni suite permanente.

```powershell
$env:APP_ENV='testing'
$env:DB_DATABASE='wings_testing_codex'
C:/xampp/php/php.exe vendor/phpunit/phpunit/phpunit --configuration phpunit.xml docs/06-pruebas/PRU-02/evidencia/a25/CajaCambioInicialA25Test.php
```

## Siguiente paso

Carlos ya respondió apertura, efectivo, turnos y cierre ADMIN. Aclaró también que al corregir
una caja rechazada se conservan contado/cambio/retiro y no cambia lo recibido por el siguiente
turno. Implementar, ampliar casos web/concurrencia, mover esta prueba a
`tests/Feature`, suite propia completa y capturas reales para aprobación de diseño.

La verificación y el cierre de A25 los realiza otro agente.
