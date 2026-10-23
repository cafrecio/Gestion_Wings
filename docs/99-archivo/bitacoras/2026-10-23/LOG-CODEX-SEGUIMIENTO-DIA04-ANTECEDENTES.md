# Codex - antecedentes archivados intactos el 23/10/2026

## 2026-10-22 - Codex CAB - Pase Casos prueba humana I a CyE

Carlos pidió documentar acuerdos, revisiones y lecciones y publicar para seguir desde CyE.
[Leer primero el pase](../06-pruebas/PRU-04/CONTINUIDAD-CODEX-CASOS-PRUEBA-HUMANA-I.md): este chat nunca ejecuta; otro chat Codex opera por navegador.
Día 4 sigue en borrador. Cuatro observaciones de segunda revisión NO comunicadas aún a Gemini: origen de contadores, cuota adelantada, criterios/implementación y seguimiento temporal.
Se conservan borradores y lecciones de Gemini como antecedentes, sin convalidar sus afirmaciones de corrección completa.
Solo revisión documental/enlaces/diff; sin suite, servidor, reloj ni producto. T14 continúa abierta.
Próximo: Codex CyE lee el pase, comunica devolución y revisa nueva propuesta con Carlos antes de ejecutar.


## 2026-10-22 — Codex CyE — T77, PHP actualizado y CWP pendiente

Carlos pidió actualizar PHP, conservar ambas páginas funcionando y publicar el informe en GitHub en una carpeta clara.
Remi 8.2.35, 11 paquetes; FPM reiniciado. Ingreso/dashboard de ambos sitios y conexiones de base comprobados.
Subagente independiente: proceso 8.2.35, servicios, HTTPS y assets correctos; mitigación CWP efectiva y panel 200.
CWP/default conserva PHP 7.4.33: repositorio sin parche nuevo. Mitigación parcial upload_progress=Off y servicios CWP reiniciados; no equivale al parche completo.
[Informe por fecha en Seguridad VPS](../04-tecnico/seguridad-vps/2026-10-22-actualizacion-php/README.md), enlazado desde docs/README.md. Respaldo privado en VPS; sin cambios de código ni datos de negocio. Capturas temporales descartadas por autocompletado.
T77 permanece abierta con Codex: pendiente parche compatible del proveedor para CWP; no declarar seguro todo el VPS.
