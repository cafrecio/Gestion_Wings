# Verificación de T62 (Gemini, `5b0869cd`) — Claude CyE, 28/10/2026

**Resultado: lógica aprobada en lo que hizo; devuelta por un punto que Gemini informó y no corrigió (CLA-012.c).** Falta además el OK visual de Carlos ([galería](APROBAR-T62.html)).

## Lo que se comprobó

- Código leído: `ClaseWebController::toggleCancelada`, `ClaseService::validarSolapamientoProfesor` y `verificarDisponibilidadProfesor`, `clases-show.js` y `clases/show.blade.php`. Sin JavaScript dentro de la vista.
- Pruebas de Gemini y del área (clases, asistencias, liquidación, T90, T66, T52, A15/A16, seguridad del profesor): **101/101** en `wings_testing_claude`.
- Ataques propios, **7/7** ([prueba](evidencia-verificacion-t62-claude/VerificacionT62ClaudeTest.php), fuera de la suite permanente):
  1. Si choca la **segunda** profesora de la clase, también frena y la nombra.
  2. Mismo horario en otro día: reactiva.
  3. Horarios contiguos (18:00 termina, 18:00 empieza): no es choque.
  4. Lista con **solo ausentes**: pide confirmación igual.
  5. `confirmar` sin motivo: rechazado, no cancela.
  6. Profesor forzando `confirmar`: no cancela.
  7. Cancelar de nuevo después de reactivar: el motivo nuevo reemplaza al anterior (el rastro guarda solo el último).

## Por qué vuelve: CLA-012.c

El caso del catálogo dice que, al cancelar una clase con lista, «un alumno cuyo único presente del mes fue en esa clase puede terminar en Revisión». No se cumple: `GenerarDeudasMensualesCommand` (línea 131) cuenta presentes del mes anterior con `whereHas('clase', …fecha…)` **sin** `cancelada = false`. Ese alumno recibe la cuota en vez de ir a Revisión.

Gemini lo encontró y lo informó sin tocarlo, como pedía el prompt. La regla ya es de Carlos (DU-30, caso CLA-012.c), así que **no hace falta preguntarle**: se corrige.

Barrido de otros lugares que cuentan presentes: `ExcepcionCobranzaService`, `OperativoDashboardController` y `ReporteAlumnosService` ya filtran canceladas. La ficha del alumno (`AlumnoWebController`, línea 188) muestra las asistencias del año sin filtrar, pero solo es vista.

**Pedido a Gemini:** agregar `->where('cancelada', false)` dentro del `whereHas('clase')` de `GenerarDeudasMensualesCommand` y una prueba con el caso exacto de CLA-012.c (único presente del mes en una clase cancelada → Revisión), roja sin el cambio. Correr además las pruebas de generación (`GeneracionCuotasReintentoT40Test` y las de Revisión).

## Observaciones menores (no frenan)

- El aviso de choque ahora dice «la profesora …» para todos y arranca en minúscula. En la ficha se lee bien, porque va después de «No se puede reactivar:». En el `ClaseController` de la API sale suelto, en minúscula.
- Sin JavaScript (formulario común), el aviso de lista tomada vuelve con `aviso_confirmacion_cancelar`, pero la vista no lo muestra. La pantalla real usa `fetch`, así que no se llega por ahí.
- `validarSolapamientoProfesor` ahora ignora las clases canceladas también al asignar profesoras. Es coherente: una cancelada no ocupa el horario, y si se la quiere reactivar, el choque la frena.

## Segunda vuelta (`afc4963c`) — aprobada en lógica

- `GenerarDeudasMensualesCommand`: `->where('cancelada', false)` dentro del `whereHas('clase')`. Es exactamente lo pedido.
- La prueba nueva reproduce el caso CLA-012.c: el único presente de octubre fue en una clase cancelada, así que no se genera cuota de noviembre y el alumno va a Revisión. Gemini informa que falla sin el cambio; acá no se repitió esa corrida, para no tocar código en la carpeta compartida.
- T62, generación (T40), Revisión y B3 asistencia: **54/54** en `wings_testing_claude`.
- Falta solo el OK visual de Carlos ([galería](APROBAR-T62.html)).
