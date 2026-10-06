# A25 — Pruebas previas, 06/10/2026

**Autor:** Codex CAB. **Estado de este ensayo:** preparación histórica, no cierre.
Después se implementó A25; [entrega, pruebas finales y capturas](../../IMPLEMENTACION-A25.md).
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

## Continuación realizada

El archivo de 12 pruebas queda intacto como antecedente del rojo. La suite permanente
incluye 22 casos ampliados de reglas/web y 4 de concurrencia en tests/Feature.
Diseño aprobado por Carlos sobre las [capturas reales](capturas/index.html); suite completa
470 pruebas, 468 aprobadas/2 omitidas, 3116 aserciones, base propia. Sin despliegue.

La verificación y el cierre de A25 los realiza otro agente.
