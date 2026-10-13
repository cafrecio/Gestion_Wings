# T33 — Verificación de Claude · 13/10/2026 · lógica APROBADA, con una observación

Tabla: DU-25 y DU-58 · casos CBZ-010 y CBZ-009.
Verificado sobre `f403d36`, publicado y desplegado en el sitio de prueba.

## Qué comprobé

- Leí el cambio completo: `InscripcionService` (condonar y reactivar), `PagoCuotaService`
  (reactivar cuota), `AlumnoWebController`, las cuatro rutas y la ficha.
- Pruebas de Codex y del área en `wings_testing_claude`: 30 aprobadas.
- Mis controles ([`evidencia-t33/VerificacionT33ClaudeTest.php`](evidencia-t33/VerificacionT33ClaudeTest.php)):
  4 de 5 aprobados.
- En el sitio de prueba, con Chrome a 1366 y a 360: la ficha carga sin errores, el botón
  Condonar de la inscripción abre el aviso y se cierra. No confirmé nada: 0 perdones y 0
  eventos en la base después de mirar.

| Qué | Resultado |
|---|---|
| El becado deja de ser deudor al perdonarle la inscripción; si se reactiva, vuelve a serlo | Bien (con T31 en la carpeta) |
| Cobranza, Reportes e Inicio dan el mismo total antes, después de perdonar y después de reactivar | Bien |
| Perdonar y reactivar no mueven plata: ni pagos ni cashflow | Bien |
| Quedan las dos decisiones en el historial del cargo y en las observaciones de la cuota | Bien |
| Solo el dueño: operativa y profesor no ven los botones y el pedido armado a mano se rechaza | Bien |
| Una cuota perdonada no se cobra; reactivada, sí, y queda paga | Bien |
| Perdonar dos veces no duplica | Bien |
| Rutas dentro del grupo `ensure.admin.web` | Bien |

## Observación (no frena)

**Un pedido sobre una cuota o inscripción que ya no existe responde en inglés.**
El aviso dice `No query results for model [App\Models\CargoAlumno] 999999`. Pasa en las
cuatro rutas (`web.deudas.condonar` ya lo hacía antes de T33). Solo se llega con una
pestaña vieja o un pedido armado a mano, y no escribe nada. Tiene que mostrar la pantalla
«no existe» en castellano. Lo sumo a T47, que es la tarea de avisos en inglés.

## Aparte de T33: la suite publicada está en rojo desde hoy

`CashflowCorreccionesT27Test` falla en dos pruebas con lo que hay en `main`, sin T31 ni
T33 de por medio: `disponible` da 0 en vez de 47.925,25 y 48.124,25. Es una prueba atada a
la fecha: fija su reloj en el 12/10 y la cobertura de Reportes nace el día real en que se
arma la base, que desde hoy es posterior. Codex lo anotó en su informe sin diagnosticarlo.
Gemini lo corrigió en su copia sin publicar de T31 (una línea), que era el cambio que no
supe explicar al verificar T31. Mientras T31 no se publique, `main` queda en rojo.

## Sin verificar

- La suite completa: no la repetí. Codex informa las 31 de T33 aprobadas.
- El aspecto: lo revisa Carlos. Pidió la corrección de alineación y autorizó publicarla.
- Los controles de dos pedidos simultáneos de Codex (`PerdonConcurrenteT33Test`): no los
  corrí; Gemini informó un fallo por tiempo en una corrida completa y que sola pasa.
- La carpeta de evidencia pesa 3 MB y trae una copia entera de los archivos compilados
  (154 archivos, fuentes incluidas). No hace falta para leer la entrega.
