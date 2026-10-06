# A25 — Apertura y arqueo del cajón compartido

**06/10/2026.v1 · Codex CAB.** HECHO (Codex), a revisar por otro agente.
Carlos aprobó el diseño de las cinco pantallas en escritorio/375: «OK, aprobado» y «Sí».
Sin despliegue, sin base del club ni servidor tocados.

## Qué cambió

ADMIN configura una sola vez qué TipoCaja representa el efectivo del mostrador.
OPERATIVO declara/confirma el recibido antes de operar. La siguiente apertura propone
el cambio retenido del último turno del club; corregirlo exige motivo y conserva ambos valores.
Solo un turno abierto para el cajón compartido, no uno por cada persona.

Cierre exige contado y cambio que queda: esperado = inicial + ingresos − egresos
ACTIVO del medio físico; diferencia = contado − esperado; entrega = contado − retenido.
Permite cerrar con faltante/sobrante para revisión ADMIN, sin inventar ajustes contables.
ADMIN cuenta/cierra antes de validar/rechazar, no abre caja propia y guarda sus cobros aparte.
Transferencias y cobros directos ADMIN no se suman al esperado del mostrador.

Corregir una rechazada conserva el conteo, retenido, entrega y actor/fecha originales;
recalcula esperado/diferencia y no altera la apertura del turno siguiente. El resumen
muestra ambos cálculos actualizados mientras se corrigen movimientos.

## Archivos y límites

- Migración nueva de configuración singleton y campos/auditoría nullable por turno.
  NULL histórico no se rellena con cero: esperado/diferencia figuran no calculables.
- CajaService, CajaWebController y rutas de apertura/configuración/conteo. POST directo
  no saltea declaración, confirmación, origen vigente, roles ni límites de importes.
- Vistas Caja index/resumen y nuevas configuración/apertura/cierre. JS exclusivo de cierre
  en archivo Vite; componentes de moneda existentes; sin CSS ni componentes compartidos tocados.
- Fixtures financieras existentes ahora declaran una caja explícita en su preparación.
  No se debilitaron aserciones de dinero ni se cambió PagoCuotaService. ADMIN sigue sin caja.
- Contrato y ER con enmienda V5; V4 se conserva como antecedente enlazado.
- API permanece apagada. No se ejecutaron seeders ni migraciones en ninguna base real.

## Pruebas y evidencia

**Previas reales contra main sin A25:** 12 pruebas / 15 aserciones: 3 fallos de conducta
(apertura automática, validación que cierra sola, cierre sin contado) y 9 errores por
métodos nuevos ausentes. [Antecedente intacto](evidencia/a25/README.md); no son 12 bugs verificados.

**Pruebas permanentes añadidas:** 22 de reglas/web/roles (114 aserciones) y 4 con
dos conexiones MariaDB (28 aserciones), todas aprobadas en la corrida completa.

| Caso | Resultado esperado y comprobado |
|---|---|
| Primera apertura $10.000 | Inicial declarado, cero movimientos nuevos |
| +$30.000 / −$5.000 | Esperado $35.000; transferencias/cancelados/ADMIN aparte no inflan efectivo |
| Contado $34.000 / cambio $10.000 | Faltante $1.000, entrega $24.000; cierra sin asiento de ajuste |
| Dejar $15.000 de $35.000 | Entrega $20.000; retenido puede superar el inicial |
| Nuevo operativo | Hereda último cierre del club, sin esperar validación |
| Recibido $8.000 / heredado $10.000 | Sin motivo rechaza; con motivo guarda ambos |
| Rechazada corregida después del siguiente turno | Conserva lo contado/entregado y el siguiente recibido |
| POST directo/roles | No negativos, no retenido mayor a contado, no apertura sin confirmar, no cierre ajeno OPERATIVO |
| Histórico sin inicial | No inventa esperado ni diferencia |
| Dos aperturas/dos cierres | Segundo intento bloqueado, no duplica ni reescribe |
| Movimiento y cierre, ambos órdenes | Entra completo antes o rechaza después del cierre |

Concurrencia: timeout real de la conexión competidora, ninguna escritura durante el
bloqueo, commit de la primera y reintento contra el estado vigente. Son cuatro órdenes
con dos conexiones, no una afirmación de haber cubierto todas las carreras del sistema.

**Suite completa final:** 470 pruebas: 468 aprobadas, 2 omitidas, 3.116 aserciones,
181,44 s, wings_testing_codex; ninguna falla. Corte sobre 262e917 con A25.
Código entregado en 30f38f8. Integración posterior de 94e368e/a89ebbe conserva el cierre
ajeno A4/A5 y sus evidencias; solo cambia documentación, no aplicación ni pruebas.
Las omitidas son productores de HTML previos (`CapturaCashflowSignoTest` y
`CapturaFichaAnularTest`), no casos funcionales A25 omitidos. Guardianes documentales
repetidos después de actualizar los textos: 4 pruebas / 19 aserciones verdes.
Las corridas con autoload apuntando a main o con solapamiento accidental fueron descartadas;
la evidencia válida usa la aplicación privada y una sola corrida en la base propia.
PHP lint verde en todos los PHP modificados y nuevos; build Vite verde.
Blade view:cache/view:clear verdes; diff sin errores de whitespace.

## Capturas reales aprobadas

[Galería de las cinco pantallas](evidencia/a25/capturas/index.html), escritorio y 375.
Producción no interviene: alumnos/usuarios/movimientos ficticios en base descartable propia.
El productor obtiene respuestas HTTP reales de Laravel; Chrome renderiza HTML y assets
compilados. Para 375 se usa un iframe de ancho exacto, no el ancho mínimo de ventana de Windows.
Se revisaron las diez capturas; el resumen móvil ya no superpone acciones y conteo.

El cierre renderizado ejecuta su JavaScript y muestra faltante $1.000/entrega $24.000.
Esto no se presenta como una sesión interactiva completa de cobro/cierre por navegador:
las escrituras fueron probadas por HTTP/servicios. El verificador independiente debe
recorrer abrir → cobrar → contar/cerrar → validar/rechazar → siguiente turno en pantalla.

## Entrega y control ajeno

A25 queda HECHO (Codex), a revisar; no CERRADO. Ambos seguimientos conservan el
contador de cerrados sin sumar esta implementación. Cuatro estados con el conteo real.
Código 30f38f8, integración e3b268a. Push recibido en main y pull --ff-only al día;
HEAD local y remoto e3b268a comprobados el 06/10. Sin cambios ajenos agregados al commit
de A25. No se atribuye deploy ni aprobación funcional a Carlos por aprobar imágenes.
