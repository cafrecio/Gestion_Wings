# Cierre A13/B1/A55 y asiento A48/A49

06/10/2026 — Codex CyE. Control independiente de implementación Claude sobre main
integrado `6d3f68a`. Durante el control Gemini agregó evidencia en `ce58d5a`, sin
cambiar aplicación ni pruebas. Sin despliegue, servidor ni base del club.

## Dictamen individual

| Defecto | Resultado | Verificador |
|---|---|---|
| A13 | CERRADO 06/10: ADMIN cobra sin caja y anula desde ficha con motivo | Codex |
| B1 | CERRADO 06/10: cobro ADMIN separado del cajón OPERATIVO, incluso con A25 | Codex |
| A55 | CERRADO 06/10: inscripción única coherente entre selector y dos fichas | Codex |
| A48 | CERRADO 06/10: búsqueda en main coincide con el control de Claude | Claude |
| A49 | CERRADO 06/10: búsqueda en main coincide con el control de Claude | Claude |

Ambos DEFECTOS y fuente del tablero tareas.json: **35/72 cerrados, 37 abiertos,
0 frenan**. A25/A15/A16 conservan HECHO (Codex), a revisar; este control de regresión
no sustituye su verificación independiente completa.

## Integración y texto autorizado

Rama completa a55-inscripcion: `31194c2`, `459ec42`, `bbe722d`, más redacción
aprobada `4eb3f59`, integrados por `6d3f68a`. Único conflicto LOG-CODEX:
se conservaron entradas de ambos lados y sus históricos. Carlos respondió directamente
«OK» a integrar y publicar; rechazo automático previo resuelto.
Única edición nueva de aplicación: frase aprobada en alumnos/show.blade.php;
deporte dinámico del registro propietario. Sin otra lógica ni CSS corregidos.
Capturas de Fútbol escritorio/375 y login móvil renovadas antes del merge:
[texto exacto, imágenes y método real de marco 375](capturas-a55/README.md).

## Verificado en HTTP, pantalla y filas

[Ensayo independiente](evidencia/cierre-a13-b1-a55/VerificarCierreTest.php), fuera
de la suite permanente: **1 aprobado, 43 aserciones, 6,49 s**. Solo base
wings_testing_codex, datos ficticios. [Filas previas a navegar](evidencia/cierre-a13-b1-a55/estado-ensayo.json).

1. **A55:** una persona en Patín/Fútbol, un DNI y un cargo de $5.000.
   Selector y ficha propietaria Patín $5.000; Fútbol $0 sin repetir deuda y con
   referencia dinámica al propietario. Total pendiente inicial $5.000 y un deudor.
   Pago deja cargo cero; anulación lo repone. [Selector](evidencia/cierre-a13-b1-a55/capturas/selector.jpg),
   [Patín](evidencia/cierre-a13-b1-a55/capturas/patin-inscripcion-unica.jpg),
   [Fútbol](evidencia/cierre-a13-b1-a55/capturas/futbol-inscripcion-unica.jpg).
2. **Historial:** agosto/septiembre cobrados el 06/10; anulaciones de $101.000
   (dos cuotas + inscripción) y $96.000 (dos cuotas sin cargo). Ambas filas
   conservan **Ago 2026, Sep 2026**, fecha 06/10 y Anulado.
   [Pantalla](evidencia/cierre-a13-b1-a55/capturas/historial-dos-anulaciones.jpg).
3. **A56:** contraasientos como **E**, importes negativos $96.000/$5.000/$96.000,
   color rojo; ingresos verdes. [Cashflow](evidencia/cierre-a13-b1-a55/capturas/cashflow-contraasientos.jpg).
4. **A13/B1:** ADMIN accede a Cobrar sin configuración de cajón ni turno;
   cobro $101.000 sin caja/movimiento operativo. Motivo vacío rechazado por
   servidor; válido anula. OPERATIVO sin turno redirige a apertura explícita;
   su cobro $48.000 entra en su caja.
5. **A25:** con turno operativo abierto, cobro/anulación ADMIN de $96.000
   conservan esperado $10.000 inicial. Solo el cobro OPERATIVO $48.000 lo lleva
   a $58.000; no se suma ADMIN ni se le exige otra caja.

También se navegó Wings real en Chrome con servidor local de ensayo, guardas
APP_ENV=testing y base Codex, autenticando únicamente usuarios ficticios. Modal
de ficha rechaza motivo vacío (`required`, «Completa este campo»); con motivo se
anula inscripción ficticia $5.000. Formulario ADMIN cobra nuevamente $101.000;
listado confirma Pago registrado. Resumen OPERATIVO conserva solo su movimiento
$48.000 y esperado $58.000. Las filas posteriores confirman pago 5 completado,
una sola caja operativa y un solo movimiento propio.

- [Motivo obligatorio](evidencia/cierre-a13-b1-a55/capturas/anular-motivo-obligatorio.jpg).
- [Anulado desde ficha](evidencia/cierre-a13-b1-a55/capturas/anulado-desde-ficha.jpg).
- [Cobrar ADMIN](evidencia/cierre-a13-b1-a55/capturas/cobrar-admin-sin-caja.jpg).
- [Pago registrado](evidencia/cierre-a13-b1-a55/capturas/cobro-admin-registrado.jpg).
- [Cajón OPERATIVO](evidencia/cierre-a13-b1-a55/capturas/cajon-operativo-sin-admin.jpg).
- [Filas posteriores](evidencia/cierre-a13-b1-a55/estado-tras-pantalla.json).

Primeras capturas: respuestas HTML devueltas por Laravel en el ensayo, con assets
reales, sin maqueta. Últimas cinco: navegación y formularios reales sobre esa base.
La suite posterior rehace la base; JSON conserva los escenarios, sin usuarios/claves.

## A48/A49: búsqueda sobre main integrado

- `_form.blade.php`: cero bloques script; aviso en resources/js/alumnos-form.js,
  con confirmación de salida y beforeunload.
- caja/resumen.blade.php: cero onsubmit y confirm(. Conserva un script ajeno al
  defecto, sin modificarlo. Otras vistas quedan fuera de A49.
- Verificación original de Claude del 06/10 asentada por instrucción expresa de
  Carlos; búsqueda coincide. No se presenta como cierre propio de Codex.

## Pruebas y límites

| Control | Resultado |
|---|---|
| AdminCobraSinCajaTest + SaldoYAnulacionCoherentesTest | 11 aprobadas, 45 aserciones, 46,94 s |
| Suite completa wings_testing_codex | **489 aprobadas, 2 omitidas, 3924 aserciones, 203,84 s** |
| Pruebas permanentes descubiertas | **491**, tres nuevas de Claude integradas |
| CSP + DEFECTOS + documentos + tableros, repetidos tras actualizar | **8 aprobadas, 30 aserciones, 0,44 s** |
| Sintaxis PHP y Blade | Vista, prueba integrada y cinco herramientas sin errores; view:cache/view:clear correctos |

[Salida completa](evidencia/cierre-a13-b1-a55/suite-final.txt). Las dos omitidas
no se cuentan como aprobadas. Incluye CSP y controles documentales/tableros;
estos controles se repitieron tras el cierre documental antes del commit.

**Verificado:** escenarios, pantallas, filas y búsquedas descriptos arriba.
**No inferido como hecho:** estado del servidor/datos del club, despliegue o cierre
integral de A25. No se volvió a ensayar todo el universo de concurrencia del
[informe anterior](VERIFICACION-A13-A54.md), conservado como antecedente histórico.
