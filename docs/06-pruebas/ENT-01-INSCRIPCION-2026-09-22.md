# ENT-01 — Evidencia de implementación, 22/09/2026

Responsable: Codex CAB. Decisiones y diseño aprobados por Carlos el 22/09.
Implementación conservada en `11623b6`; este cierre completa la evidencia.
Sin despliegue ni migraciones sobre la base del club.

## Alcance verificado

- Una inscripción por DNI entre deportes; ingreso real antes/en/después del corte.
- Importe obligatorio y congelado; corte 23/09/2026 ineditable en uso normal.
- Alta y plan atómicos con cargo; fallo controlado revierte todo; reintento no duplica.
- Cobro parcial primero inscripción, luego cuota; pago desde el segundo deporte.
- Corrección de ingreso sin pagos crea/anula con auditoría; con pago parcial rechaza.
- Cuotas conservan estado mensual; inscripción no suma comisión ni descuento de cuota.
- Pago histórico sin monto_cuota mantiene el significado anterior.
- Anulación completa restituye ambos saldos, cancela movimientos y conserva recibo.
- Dos conexiones MariaDB: altas por mismo DNI y cobros parciales esperan sin escribir;
  después del commit se reintenta y se verifica que no se duplica inscripción ni dinero.

## Pruebas

Primero se escribieron 11 pruebas contra el código anterior: 11 fallidas, 22 aserciones.
La implementación agregó 15 casos funcionales, 2 de concurrencia y 1 de comisión.
También actualizó expectativas del catálogo y del JavaScript extraído de la vista.

La primera suite compartida detectó fallos de compilación Blade y expectativas viejas;
se corrigieron. Una corrida posterior tuvo tablas recreadas durante la ejecución:
resultado descartado, sin usarlo como evidencia de funcionalidad. La verificación final
usa una base descartable exclusiva `wings_testing_ent01_suite` en MariaDB.

**Suite completa: 313 pruebas / 1793 aserciones, todas verdes, 122,92 s.**
Incluye las 5 pruebas de ENT-06 incorporadas por Gemini durante este trabajo.
Comando: `DB_DATABASE=wings_testing_ent01_suite php artisan test` (variable de
entorno equivalente en PowerShell). No se filtraron casos ni se usó SQLite.

Sintaxis PHP, compilación/limpieza Blade, build Vite y diff sin errores verificados.
No se cambió CSS; el script de cobrar pasó a su archivo de pantalla. CSP sigue en reporte.

## Revisión visual real

Navegador local y base descartable `wings_testing_ent01_visual`, con datos ficticios.
Se revisó el aviso persistente de inscripción por $5.000 antes de guardar el alta.
En Cobrar: cuota de septiembre $21.000 (regla vigente del primer mes) más inscripción
$5.000 = $26.000. Al ingresar $3.000 la pantalla muestra inscripción $3.000 y cuota $0.
Al cobrar $26.000 se registraron dos movimientos y el total correcto en la caja.

PDF generado por ReciboService en almacenamiento aislado y renderizado con Poppler:
septiembre $21.000, inscripción por única vez $5.000, total $26.000. Se inspeccionó
la imagen real del documento; [captura con datos ficticios](../../storage/ent01-visual/recibo.png).
La prueba automatizada también genera PDF y verifica los conceptos, incluso anulado.

## Entrega y límites

La migración `2026_09_22_180000_create_cargos_alumno.php` incorpora estructura y
configuración sin generar cargos retroactivos. Al actualizar servidor requiere migraciones
y build de los dos archivos JavaScript nuevos; esa actualización corresponde a Claude.

FIN-14 sigue pendiente: §5 de Punitorios aprobado usa cargos, pero no hay motor de mora
ni se publica configuración que nadie lea. ENT-10 conserva manuales de primera carga.
No se implementó condonación manual de inscripción. Corregir DNI con cargo se rechaza
hasta definir procedimiento; nunca se mueve una deuda a otra persona automáticamente.
Fecha real desconocida: pendiente de definir, no inventar una fecha en carga manual.
