# A15/A16 — Entrega de Codex CyE, 06/10/2026

**HECHO (Codex), a revisar por otro agente. Sin despliegue.**
Carlos eligió aviso y confirmación para A15 y aprobó la propuesta visual:
«Si, bien!!». [Capturas previas aprobadas](PROPUESTA-A15-A16.md).

## Resultado

- A15: Guardar devuelve un aviso con los bloques del reloj, conserva los
  datos y no crea clases ni asignaciones. Confirmar guarda los horarios
  revisados. Cambiar cualquier dato de la carga exige renovar el aviso.
- A16: cada día elegido tiene su inicio/fin. Grupo, profesores y período
  se cargan una vez. Seis POST reales crean las 76 clases del cronograma
  con seis series; antes se necesitaban diez cargas.
- Un conflicto de profesor en el segundo día revierte la serie completa.
  Se mantienen fechas no pasadas y profesores activos del deporte.
- Alcance: creación web de clases. Sin edición masiva, edición de una
  clase existente, precios ni reservas de cancha POS-07.

[Contrato V2](../../02-contratos/Wings-Contrato-Clases-Asistencias-V2.md).

## Verificación del autor

18 pruebas nuevas: nueve de calendario/firma y nueve de integración web.
Selección con A4/A5: **29 aprobadas, 834 aserciones**.
Build de Vite, sintaxis PHP y compilación/limpieza de vistas: correctos.
Suite completa final en `wings_testing_codex`: **486 aprobadas, 2 omitidas,
3911 aserciones**, 342,01 s; sin fallas.

[Pruebas de integración](../../../tests/Feature/ProgramarClasesA15A16Test.php)
· [Lógica](../../../tests/Unit/ProgramacionClasesTest.php)
· [Salida de suite completa](evidencia/a15-a16/suite-final.txt).

Ensayo adicional de captura: **1 prueba, 262 aserciones**, fuera de la
suite permanente. [Reproductor](evidencia/a15-a16/A15A16CapturasTest.php).
Recrea los seis POST, guarda respuestas reales de Laravel, comprueba
que el aviso no agrega clases y que confirmar agrega solo la clase de
prueba. Reloj fijado al 24/09 para reproducir el día de carga histórico.
[Las 76 filas concretas](evidencia/a15-a16/resultado-76-clases.json).
Son datos de ensayo; no certifican ni modifican la base actual del club.

Chrome: cambio de fin 18:30 → 19:30 devuelve Guardar, quita el aviso viejo
y vacía la confirmación. Comprobado en el DOM después de editar el campo.
Desmarcar Viernes oculta y deshabilita sus horas; marcar Domingo muestra
sus horas como obligatorias. Comprobado con el JS compilado en Chrome.

## Capturas finales

Respuesta real de la aplicación, servida por HTTP local, con el mismo
marco 375. Login de control contenido. El formulario conserva desplazamiento
vertical; campos y botones entran horizontalmente. No es HTML inventado.
Se conserva el diseño compartido de Wings; solo se tocaron creación de
clases, su aviso y JS. Los cambios simultáneos de Gemini no entran en esta
entrega; `a55-inscripcion` no se incorporó.

| Pantalla | Escritorio | 375 | Parte inferior 375 |
|---|---|---|---|
| Horarios por día | [Captura](evidencia/a15-a16/clases-final-escritorio.jpg) | [Captura](evidencia/a15-a16/clases-final-375.jpg) | [Captura](evidencia/a15-a16/clases-final-375-inferior.jpg) |
| Aviso previo | [Captura](evidencia/a15-a16/clases-final-aviso-escritorio.jpg) | [Captura](evidencia/a15-a16/clases-final-aviso-375.jpg) | [Confirmar](evidencia/a15-a16/clases-final-aviso-375-inferior.jpg) |
| Login de control | — | [Captura](evidencia/verificacion-a56/login-375.jpg) | — |

Pendiente: verificación independiente en código y pantalla. A15/A16 quedan
a revisar en ambos seguimientos; A56 cerrado por Codex en `ad7f9fb`.
