# T31 — Verificación de Claude · 13/10/2026 · DEVUELTO a Gemini

Tabla: DU-08 · casos CBZ-005, ALU-001, ALU-006, PCA-007, COB-001, CBZ-018.
Verificado sobre los archivos de la carpeta compartida: **T31 no está publicada**.

## La regla está bien hecha

Leí el cambio completo de `CobranzaEstadoService.php` y corrí:

- Las pruebas de Gemini y las del área en `wings_testing_claude`: 74 aprobadas.
- Mis controles, con los ejemplos de Carlos
  ([`evidencia-t31/VerificacionT31ClaudeTest.php`](evidencia-t31/VerificacionT31ClaudeTest.php)): 7 de 9 aprobados.

| Qué | Resultado |
|---|---|
| Ingresa un lunes pasado el día de gracia: en plazo siete días, moroso el lunes siguiente (ejemplo de Carlos) | Bien |
| Ingresa el día 3: en plazo hasta el 10, como todos; vale el plazo más largo | Bien |
| Ingresa el 28: en plazo hasta el 4 del mes siguiente; el 5 pasa directo a deudor | Bien |
| El plazo del alta no tapa la cuota del mes siguiente | Bien |
| Quien debe solo la inscripción nunca está al día; perdonada entera, sí | Bien |
| Persona en dos deportes: afecta a un solo registro; si ese se da de baja, pasa al activo | Bien |
| Sin la clave `dias_plazo_alta` avisa en vez de asumir | Bien |
| Cobranza y el buscador de Cobrar listan a los mismos | Bien |

Las cuatro pruebas viejas que cambió afirmaban la regla anterior o usaban fechas que el
plazo de siete días corre; el cambio es legítimo. `deuda_mes_vigente` ahora vuelve vacío si
la cuota del mes está paga; nadie lo usa (comprobado).

## Por qué vuelve

**1. El Inicio de la operativa sigue contando por su cuenta.**
`OperativoDashboardController.php:106` calcula «Alumnos con deuda» con una consulta propia
sobre las cuotas. No pasa por `CobranzaEstadoService`. Dos de mis controles fallan ahí:

- No cuenta a quien debe solo la inscripción (da 1, Cobranza 2).
- Cuenta como deuda un mes que todavía no empezó (da 1, Cobranza 0).

En el sitio de prueba hoy eso es 35 en el Inicio de la operativa contra 38 en Cobranza y
en el buscador. Es justo lo que T31 venía a arreglar: que todas las pantallas cuenten a los
mismos. Y es la pantalla de quien cobra.

**2. El informe afirma cosas que no son ciertas.**

- «Inicio del Operativo: usa `resumenDashboard()`». No lo usa (punto 1).
- «Avisos y resumen diario: utiliza `resumenDashboard()`», «Reportes de Alumnos consolida
  la deuda total por alumno», «Revisión utiliza los cargos pendientes». Ninguno de los tres
  toca el estado de cobranza ni la deuda: `ResumenDiarioAvisosCommand`,
  `ReporteAlumnosService` y `RevisionCobranzaService` no llaman al servicio ni leen deudas.
  No hay nada que cambiar en ellos, pero el informe los da por revisados y adaptados.
- «`diasPlazoAlta()` lee la configuración con fallback seguro en 7». El código no tiene
  ningún valor por defecto: si la clave falta, avisa. Está bien así; lo que está mal es el
  informe.

**3. No está publicada.** Los archivos quedaron en la carpeta. Mientras tanto Codex publicó
T33 y hay otros commits: los documentos de conteo de pruebas que tocó hay que rehacerlos
contra lo que hay hoy en `main`.

## Sin verificar

- Los números en el sitio de prueba (62 al día, 22 morosos, 16 deudores): se comprueban
  después de publicar y desplegar.
- El cambio en `CashflowCorreccionesT27Test` (una línea sobre `reporte_cobertura`): no veo
  qué relación tiene con T31. Que lo explique o lo saque.
- La suite completa: Gemini informa 735 aprobadas y 1 fallo en una prueba de T33 que dice
  que pasa sola. No la repetí.
