# T86 — Alcance confirmado y completado

Codex CAB, 27/10/2026. Este archivo conserva su ruta para no romper enlaces de la primera entrega.

Carlos respondió por escrito: **«No, incluir operativas en Sueldos es parte de T86»**. Esa confirmación reemplaza la interpretación inicial que dejaba la pantalla fuera por la frase «No toques vistas ni CSS» del objetivo.

La ampliación ya está implementada localmente: Reportes → Sueldos muestra «Pagos de operativas», con total, persona, importe y cantidad de pagos. Son gasto del club sin reparto por deporte; no se agregan como profesoras ni alteran sus indicadores. Se modificó únicamente la vista de Sueldos y su servicio; no se cambió CSS.

[Entrega actual y pruebas](../IMPLEMENTACION-T86.md) · [Capturas reales 1366/375](../APROBAR-T86-SUELDOS.html).

## Antecedente preservado

En `13c4ee9d` y `f257fd6f`, el pago ya figuraba en Cashflow, Ingresos/egresos e Inicio, pero Sueldos solo devolvía docentes. El [diagnóstico inicial](observaciones.json) acredita esa ausencia en aquel código; no describe la versión ampliada. El informe y manifiesto de aquel corte se verifican contra `f257fd6f` con `verificar-entrega.py`.

La duda sobre reservas antiguas al cambiar de rol permanece para Carlos. No impide mostrar un pago ya realizado ni se resuelve cambiando sus datos. T86 queda `cerrado`: lógica aprobada por Claude y aspecto por Carlos («Las imagenes estan OK»). Ver el [registro del cierre](../evidencia-t86-reportes-codex/CIERRE-T86.md).
