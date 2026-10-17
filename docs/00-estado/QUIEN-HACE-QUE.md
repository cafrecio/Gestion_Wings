# Quién hace qué

Una sola hoja para saber, en cualquier momento, qué tiene cada uno y qué está esperando.

**Desde el 06/10/2026 el bloque de abajo no se escribe a mano.** El estado de cada tarea
vive en un solo archivo, `docs/00-estado/tareas.json`, y de ahí se arman esta hoja y la
pantalla que abre Carlos, [TABLERO.html](TABLERO.html). Antes se llevaba en tres documentos
a mano y se atrasaban: Carlos creyó hecho algo que no lo estaba.

## Cómo se cambia el estado

El agente que termina, recibe o devuelve una tarea lo anota **en el mismo commit**, sin
esperar a Claude:

```
php scripts/tablero/tablero.php ver Codex
php scripts/tablero/tablero.php cambiar A25 estado=a_verificar hizo=Codex tiene=Gemini paso="Recorrer abrir, cobrar, cerrar y validar en pantalla"
php scripts/tablero/tablero.php cambiar A25 estado=cerrado verifica=Gemini
php scripts/tablero/tablero.php nueva T9 "Título" tiene=Gemini estado=en_curso paso="Qué sigue"
```

- **Cinco estados**: `sin_empezar`, `en_curso`, `a_verificar`, `devuelto`, `cerrado`.
- **`tiene`** es quién tiene la pelota ahora: Carlos, Claude, Codex, Gemini o nadie.
  Si algo espera una decisión o un permiso de Carlos, `tiene=Carlos`.
- **`paso`** es el próximo paso en una línea, en castellano llano: lo lee Carlos.
- El comando no guarda si falta el dueño, falta el paso o si el que verifica es el que lo hizo.
- No editar `TABLERO.html` ni el bloque de abajo: se pisan en la próxima generación.

<!-- TABLERO:INICIO — generado por scripts/tablero, no editar a mano -->

Último cambio: 18/10/2026. Avance: **68 defectos cerrados de 70**. Frenan: ninguno.

## Esperan por Carlos

| Tarea | Estado | Próximo paso |
|---|---|---|
| **T10** Privacidad antes de produccion: texto, leyenda de inscripcion y condiciones de servicio | Hecho, espera verificación (hizo Claude, verifica Carlos) | Borradores hechos el 17/10 en docs/01-producto/legal (condiciones de servicio, privacidad, cookies, leyenda de inscripcion). Carlos completa datos entre corchetes, elige moneda, confirma IVA y pais del servidor, inscribe en la AAIP. Enlace desde el ingreso: tarea aparte |
| **T13** Ayuda dentro de Wings: entrada en el menu y signo de pregunta en cada pantalla | Sin empezar | Decidido por Carlos el 10/10: Ayuda en el grupo Sistema del menu, un ? por pantalla que abre su pagina, buscador, imprimir, cada rol ve lo suyo, detras del login. Son vistas nuevas: falta que Carlos diga quien lo programa |
| **T14** Prueba por dias en el sitio de prueba, por pantalla, contada como cuento | En curso (hizo Codex, verifica Claude) | Dias 1, 2 y 3 hechos por Codex y verificados por Claude contra la base. Dia 3 (lunes 12, feriado): 3 clases canceladas, plata intacta; salio T21. El reloj del sitio de prueba quedo en el lunes 12. Carlos decide como sigue |
| **T32** Reloj simulado: que al frenarse no deje a todos sin poder entrar | Hecho, espera verificación (hizo Claude) | Tabla: DU-46 · SIS-009 y prueba D02-04 (caso ACC-003). Hecho por Claude y desplegado en el sitio de prueba. Dos arreglos: (1) c699bc1, herramienta: con el reloj simulado parado el limite de ingresos no se aplica; (2) 602efcc, DEL SISTEMA y llega a produccion: el limite de 5 ingresos por minuto compartia contador con los avisos de seguridad que la pantalla de ingreso manda sola; cada aviso gastaba un intento. Probado en el sitio con ingresos reales: reloj corriendo, entran 5 y el sexto queda afuera, se libera al minuto; reloj parado, entran 8 de 8. Sitio de prueba devuelto a hora real. Falta que otro agente verifique, sobre todo (2): es el limite de seguridad del ingreso. Carlos dice quien |
| **B12** No hay reportes | En curso (hizo Codex, verifica Carlos) | Alumnos, Finanzas, Sueldos, acceso y ajuste aprobados por Carlos; completar prueba manual y clasificación de antecedentes |

## Hecho y sin nadie que lo verifique

Nada.

## Claude

| Tarea | Estado | Próximo paso |
|---|---|---|
| **T37** Rechazar una caja exige motivo | Hecho, espera verificación (hizo Codex, verifica Claude) | VISUAL APROBADO por Carlos el 17/10: Bien ambas imagenes!! (dos botoneras a 375 de 4b050ac). Scroll y Modal OK también aprobados. Verificador visual Carlos según AGENTS 6a; no dejar pendiente ni exigir otra IA para lo visual. A Claude le queda SOLO verificar reglas de rechazo T37. Evidencia: DETALLE-CAJA-MOBILE.md. Suite 784 aprobadas/2 omitidas, 7395 aserciones. Inicio pendiente por decisión de Carlos. |

## Codex

Nada en este momento.

## Gemini

| Tarea | Estado | Próximo paso |
|---|---|---|
| **T35** No se toma asistencia de una clase que todavia no ocurrio | Hecho, espera verificación (hizo Claude, verifica Gemini) | Hecho por Claude en 8f2be1f. Verifica Gemini (Carlos, 17/10). Falta mirar la pantalla en un navegador. Informe: docs/06-pruebas/PRU-04/IMPLEMENTACION-T35.md |

## Sin empezar (41)

T12, T22, T25, T34, T36, T38, T39, T40, T41, T42, T43, T44, T45, T46, T47, T50, T51, T52, T53, T54, T55, T56, T57, T58, T59, T60, T61, T62, T63, T64, T65, T66, T67, T68, T69, T70, T71, T72, T73, T74, B3.

## Mejoras futuras, no se empiezan ahora (9)

MF6 (prioritaria), MF1, MF2, MF3, MF4, MF5, MF7, MF8 (muy a futuro), MF9 (muy a futuro).

## De otros proyectos, no es pendiente de Wings (1)

CAB01 Armar el metodo de trabajo comun para proyectos nuevos.

<!-- TABLERO:FIN -->

## Cómo se evita el desorden

1. **Cada prompt dice a quién va en la primera línea**, y la tarea se pasa a ese agente en el tablero antes de entregarlo.
0. **Lo visual no se aprueba por texto.** Sin capturas en el repositorio no se le pide a Carlos ninguna autorización de diseño (`AGENTS.md` §1).
2. **Lo que hace uno lo verifica el otro**, nunca el autor. Si se cruzó, se dice y se repite.
   Excepción desde el 08/10: lo visual que Carlos aprueba con capturas se cierra con
   `verifica=Carlos`; las reglas y los permisos del mismo cambio siguen yendo a otro agente.
3. **Una tarea por agente a la vez.** Lo que está esperando a Carlos no se adelanta.
4. **Lo que espera una decisión de Carlos vive arriba de todo**, con `tiene=Carlos`.
5. **Todo prompt termina con lo mismo, sin excepción:** dejar asentado qué se hizo en la
   bitácora propia y cambiar el estado en el tablero, más el estado y el plan si cambió el
   número de pruebas. Si el prompt no lo pide, está mal escrito: lo que no queda anotado se
   vuelve a discutir y se paga dos veces.
6. **El que implementa deja `a_verificar`; `cerrado` lo pone el que verifica.** Nadie cierra
   lo suyo. Lo encontró Codex el 05/10 leyendo un prompt mal escrito por Claude que le pedía
   cerrar su propia tarea; Carlos confirmó el criterio. El avance cuenta solo lo cerrado: lo
   hecho y sin revisar todavía no es avance.
7. **Los bloques del plan (P0, P1, P2…) se marcan igual que los defectos.** P1 figuraba sin
   terminar un día después de estar verificada, y Carlos lo vio antes que los tres agentes.
8. **El estado de un defecto no se escribe a mano** (desde el 16/10, T3). El encabezado de cada
   defecto en `DEFECTOS.md` y su tilde en `DEFECTOS.html` salen del tablero al correr el comando.
   `TableroAlDiaTest` pone la suite en rojo si alguien los edita a mano.
9. **Las capturas de celular van en un marco de 375**, no achicando la ventana de Chrome:
   Windows no deja ventanas de menos de 500 píxeles y recorta. El control es capturar el
   login: si sale cortado, el que mide mal es el método. Usar
   `docs/06-pruebas/PRU-02/capturas-cashflow/marco-375.html`.
10. **Los tres agentes comparten la carpeta.** Antes de commitear, mirar que no se esté
    llevando trabajo ajeno sin commitear.
