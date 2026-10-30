# Ficha de fuentes: inicio-dueno

- **Página:** `resources/manual/admin/inicio-dueno.md` · **Rol:** admin · Escrita por Claude CAB el 30/10/2026 (etapa C).
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| Clases sin asistencia quedan afuera de la liquidación hasta cargar la lista | `app/Services/LiquidacionService.php:158`<br>`app/Services/LiquidacionService.php:160` | `->where('cancelada', false)`<br>`$q->where('validada_para_liquidacion', true)` | Leído en el código; no ejecutado en pantalla. |
| Tarjetas del mes | `resources/views/admin/dashboard.blade.php:22` | `@foreach([['Ingresos del mes',$reporte['ingresos'],'success','ingresos'],['Egresos del mes',$reporte['egresos'],'danger','egresos'],['Alumnos por cobrar',$reporte['deuda']['total'],'warning','deuda'],['Profesores por pagar',$reporte['por_pagar']['total'],'info','profesores']] as [$nombre,$valor,$color,$ancla])` | Captura de la pantalla real (CapturasEtapaCTest). |
| Para revisar | `resources/views/admin/dashboard.blade.php:44` | `@foreach([['Cajas por validar','cajas',route('web.caja.index',['pendientes'=>1])],['Liquidaciones pendientes','liquidaciones',route('web.liquidaciones.index',['pendientes'=>1])],['Clases sin asistencia','asistencia',route('web.clases.index',['estado'=>'finalizada'])],['Revisión de alumnos','revision',route('web.revision-cobranza.index',['estado'=>'PENDIENTE'])]] as [$etiqueta,$clave,$enlace])` | Captura de la pantalla real (CapturasEtapaCTest). |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
