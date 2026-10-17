# T35 — No se toma asistencia de una clase que todavía no ocurrió

17/10/2026 · Claude CAB · decisión de Carlos del 13/10 (DU-35): «No: solo desde el día de la clase».

## Qué pasaba

`ClaseWebController::storeAsistencias` aceptaba presentes de cualquier fecha. Un presente
cargado antes de tiempo hace que la clase cuente como dictada: entra en la comisión de la
profesora y en la cuota del mes siguiente.

## Qué cambió

| Archivo | Cambio |
|---|---|
| `app/Models/Clase.php` | `todaviaNoOcurrio()`: la fecha de la clase es posterior a hoy. Un solo lugar para la regla. |
| `app/Http/Controllers/ClaseWebController.php` | `storeAsistencias` rechaza antes de mirar nada más: 422 con el aviso (JSON) o vuelve con el aviso. `show` le pasa la condición a la vista. |
| `app/Services/ClaseService.php` | `registrarAsistencia` también rechaza. Hoy solo la usa la API, que está apagada (`bootstrap/app.php:17`); se cubre igual para que ninguna puerta quede abierta. |
| `resources/views/clases/show.blade.php` | Clase futura: cartel con el mismo componente que el de clase cancelada, casillas de Presente deshabilitadas y sin botón Guardar. Sin JavaScript nuevo. |
| `docs/02-contratos/Wings-Contrato-Clases-Asistencias-V1.md` | Enmienda del 17/10 en 4.c y regla freeze en 4.i. |

El botón Guardar se saca en vez de deshabilitarse porque usa estilos en línea, no `ds-btn`:
deshabilitado se vería igual que habilitado.

**Límite:** es por fecha, no por hora. Desde las 00:00 del día de la clase, hora de Wings, se puede
tomar; una clase de las 18 se puede cargar a la mañana. Así lo decidió Carlos («desde el día»).

**Lo ya cargado** antes de esta regla en clases futuras no se toca. Una clase futura con lista
ya cargada no se puede corregir hasta su día.

## Pruebas

`tests/Feature/AsistenciaClaseFuturaTest.php`, 8 pruebas, reloj fijo en 17/10/2026 10:00:

1. Clase de mañana: admin y operativa reciben el aviso y no se guarda nada.
2. Sin JavaScript (formulario común): vuelve con el aviso y no guarda.
3. Clase de hoy: se guarda.
4. Clase de ayer sin lista: se guarda sin motivo (primera carga, como antes).
5. Límite: 23:59 del día anterior rechaza; 00:00 del día de la clase acepta.
6. El servicio rechaza con el mismo aviso.
7. Pantalla de una clase futura: cartel visible, casilla deshabilitada en su propia etiqueta, sin Guardar.
8. Pantalla de una clase de hoy: sin cartel, con Guardar y casilla habilitada.

Suite completa en `wings_testing_claude`, 17/10: 762 aprobadas, 2 omitidas, 15 fallidas.
Los 15 fallos no son de T35: 14 eran el diseño compilado viejo de esta computadora (faltaba
`resources/js/alumnos-show.js` en el manifest de Vite) y uno, el contador de pruebas de los
documentos, que se movía mientras Codex y Gemini agregaban las suyas en la misma carpeta. Con
`npm run build` local, los 9 archivos afectados se repitieron: 73 aprobadas, 642 aserciones.
La suite corrió con los cambios sin commitear de T37 y T48/T49 en el árbol.

## Sin verificar

- **La pantalla en un navegador.** No había clases futuras en la base local y no creé datos en
  ella. Lo comprueba quien verifique, en el sitio de prueba o en una base descartable.
- El profesor no tiene prueba propia: la regla corre antes de cualquier distinción de rol en
  `storeAsistencias`, y el profesor usa la misma ruta (contrato 4.f).
