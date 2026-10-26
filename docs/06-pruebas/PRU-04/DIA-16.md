# Día 16 — domingo 25/10/2026

Sitio: test.gestionar-te.com.ar · staging/wingstest, datos ficticios. Ejecutor: Codex CAB.

Esperados literales congelados antes de operar. En el día 16 el usuario Dueño proviene del encabezado del guion. Carlos confirmó el despliegue y ordenó registrar y saltear dudas o pasos que no se puedan probar, sin detener el recorrido.

| # | Quién | Tarea | Esperado | Lo que pasó | ¿Coincide? |
|---|---|---|---|---|---|
| 16.1 | Dueño | En su Inicio ve el turno de Pablo **abierto desde ayer**. Lo cierra él, contando **$84.000** y dejando $10.000. | Lo deja cerrar. Queda guardado que lo cerró el dueño. Los cobros siguen a nombre de Pablo (CAJ-016). | Dueño cerró caja #14 abierta desde el 24/10. Resumen posterior CERRADA al 25/10 15:02: esperado/contado $84.000, diferencia $0, cambio $10.000 y entrega $74.000. Operativo continúa Pablo y se conservan Berenice $50.000 y torneo $24.000. La pantalla no muestra el nombre del autor del cierre; no se consultó base para afirmarlo como campo guardado. | Parcial |
| 16.2 | Dueño | Valida esa caja. | Validada. | Caja #14 VALIDADA después de verificar 2/2 movimientos y comprobar $74.000 de neto, esperado/contado $84.000 y diferencia cero. Cashflow confirmado muestra Berenice $50.000 registrado por Pablo Ledesma, conservando su autoría. | Sí |
| 16.3 | Dueño | Reportes: comparación con septiembre y evolución. | Septiembre muestra los $30.000 de Zoe del 30/09. La evolución dibuja los meses con datos (REP-012). | Reportes muestra septiembre ingresos $30.000, egresos $0 y resultado $30.000; detalle de evolución muestra el mismo mes. Captura completa revisada: gráfico con punto de ingreso $30.000 y egreso $0 en septiembre; sin comparación al ser el único mes cerrado con ingresos. El pago de Zoe conserva fecha 30/09 en fila y PDF. | Sí |
| 16.4 | Dueño | Reportes de Alumnos: cómo le va a cada grupo. | Cada grupo con sus activos. Fútbol Principiantes cuenta a Valentín y a Mora (REP-015). | Reporte actual: Fútbol Principiantes 23 y Avanzadas 15; Patín Principiantes 28, Avanzadas12, Intermedias18 y Federadas8. Se contrastaron fichas concretas de Valentín y Mora: Activos en Fútbol Principiantes y Al día. Total104 por Julieta inactiva; el reporte agrupa por deporte/nivel. | Sí |
| 16.5 | Dueño | Liquidaciones → **Nueva**, con octubre sin terminar. | La pantalla abre y lista a las profesoras con sus clases. Anotar si deja generar octubre antes de que termine el mes (LIQ-019). **No generar.** | Nueva liquidación abre en octubre2026: lista Vanina1 clase, Lucía16, Mariela14, Hernán21 (avisa1 sin asistencia) y Verónica7. Generar aparece habilitado con octubre sin terminar; no se pulsó ni se creó liquidación, por orden del guion. No se afirma aceptación del servidor sin enviarlo. | Sí |
| 16.6 | Dueño | En Usuarios, intenta cambiarse su propio rol a Operativa y desactivarse. | No lo deja: la cuenta del dueño está protegida (USU-006, USU-009). | Protección visible: cuenta propia no ofrece switch de baja y avisa «No podés desactivar tu propia cuenta». En edición, Operativo/Profesor están deshabilitados y dice «No podés cambiar tu propio rol». Sigue Administrador activo. No se alteraron credenciales ni se forzó solicitud oculta al servidor. | Sí |
| 16.7 | Dueño | En Configuración, días de gracia «-3» y después «abc». | Rechaza los dos. Queda el valor anterior, 10 (CFG-002). | -3 rechazado: «Elegí un día del1 al28». abc no entra en el campo numérico (queda vacío) y muestra «Completá este valor antes de guardar». Tras recargar, días de gracia sigue10. Se volvió a observar el rechazo de -3 para completar la evidencia de la primera respuesta asincrónica; no se guardó configuración nueva ni se forzaron solicitudes ocultas. | Sí |
| 16.8 | Dueño | Crea el grupo **Patín Adultos** con dos planes: 1 vez $32.000 y 2 veces $42.000. | Se guarda, activo y sin alumnos (GRU-001). | Se creó primero el nivel Adultos, necesario y ausente del selector. Grupo Patín — Adultos guardado activo, 0 alumnos, planes1 vez $32.000 y2 veces $42.000. Confirmado en listado de Grupos. | Sí |
| 16.9 | Dueño | Crea el subrubro **Botiquín** en Gastos Operativos, para la operativa. | Aparece para la operativa al cargar un gasto (CAT-007). | Botiquín creado en Gastos Operativos, EGRESO, permitido para Ambos (el formulario aclara operativo en su caja y admin en cashflow), Del club y afecta caja. Listado posterior lo muestra OPERATIVO y ✓. No se ingresó como operativa porque el domingo es solo Dueño; presencia en su formulario de gasto no comprobada. | Parcial |
| 16.10 | Dueño | Cierre del domingo | Igual que el sábado, ahora sin nada por validar: deuda **$391.000**; ingresos **$1.967.850,75**; disponible **$3.480.000,75**: Efectivo $1.182.900, MP $2.031.550,75, Galicia $137.550, Nación $128.000. «Sin asistencia» 0. | Deuda $391.000;104 activos:99 Al día,0 En plazo,1 Moroso,4 Deudores. Octubre ingresos $1.824.850,75, egresos $240.850, resultado $1.584.000,75. Disponible $3.337.000,75: EFE$1.182.900, MP$1.888.550,75, BGA$137.550, BNA$128.000. Por validar$0 y cajas pendientes0. Sin asistencia1: clase #76 del24/10, que no se tomó por dependencia de15.3–15.5. Faltan $143.000 y1 activa por13.11 no completado; no se reparó el saldo para igualar el esperado. | No |

## Consola y rutina

### Reloj: una ejecución

```text

   INFO  Configuration cache cleared successfully.

Wings ahora cree que es domingo 25 de octubre de 2026, 15:00
El reloj avanza solo desde esa hora y se detiene a la medianoche de ese día.
Las sesiones abiertas se caen al saltar de día: hay que volver a entrar.
```

El domingo no lleva rutina de mañana. Solo se operó como Dueño.

## Cierre y diferencias

Los diez casos tienen resultado registrado. Deuda $391.000;104 activos:99 Al día,0 En plazo,1 Moroso y4 Deudores. Octubre ingresos $1.824.850,75; egresos $240.850; resultado $1.584.000,75. Disponible $3.337.000,75: EFE $1.182.900, MP $1.888.550,75, BGA $137.550, BNA $128.000. Por validar $0 y cajas pendientes0. Sin asistencia1, clase#76 del24/10 que no se tomó por la dependencia de Martín.

Diferencias respecto del esperado: una activa/Al día menos y $143.000 menos en ingresos, resultado, MP y disponible por13.11 no completado; una clase sin asistencia por15.3–15.5 no completados. No se repararon datos. Caja#14 quedó validada, el grupo Adultos y Botiquín quedaron creados y días de gracia sigue10. No se generó ninguna liquidación.

16.1: el cierre se ejecutó autenticado como Dueño, pero el autor persistido del cierre no se muestra en la UI; se conserva como parcial.16.9: permiso OPERATIVO comprobado en catálogo; el domingo fue solo Dueño y no se probó su formulario con sesión operativa.

## Evidencia y revisión

Resultado de esta jornada: 2 Parcial · 7 Sí · 1 No. [Índice de evidencia](evidencia-dia-16/LEEME.md) · [Galería de las cuatro jornadas](EVIDENCIA-DIAS-13-16.html#dia-16).

T14 continúa en curso. Esta entrega requiere verificación independiente; no constituye cierre de la tarea ni aprobación de todos los casos. La herramienta de Chrome bloqueó el protocolo file al abrir la galería local: se controlaron estructura y enlaces, sin afirmar render de esa galería. Las capturas de Wings sí provienen del sitio en marcha.
