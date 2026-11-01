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

Último cambio: 01/11/2026. Avance: **69 defectos cerrados de 70**. Frenan: ninguno.

## Esperan por Carlos

| Tarea | Estado | Próximo paso |
|---|---|---|
| **T10** Privacidad antes de produccion: texto, leyenda de inscripcion y condiciones de servicio | Hecho, espera verificación (hizo Claude, verifica Carlos) | Borradores hechos el 17/10 en docs/01-producto/legal (condiciones de servicio, privacidad, cookies, leyenda de inscripcion). Carlos completa datos entre corchetes, elige moneda, confirma IVA y pais del servidor, inscribe en la AAIP. Enlace desde el ingreso: tarea aparte |
| **T12** Manual del usuario: contenido por tarea y por rol | Hecho, espera verificación (hizo Claude, verifica Gemini) | Etapa C controlada por Gemini: OK de Carlos |
| **T13** Ayuda dentro de Wings: entrada en el menu y signo de pregunta en cada pantalla | Hecho, espera verificación (hizo Claude, verifica Gemini) | Etapa C controlada por Gemini: OK de Carlos |
| **T112** Un presente no saca a un alumno de Revision | Sin empezar | Ejecutado 01/11 (53.4, Tobias); anotado en el catalogo el 13/10 sin tarea. CBZ-016 a pide que salga, por el contrato de estados. Para decidir: se arregla o se corrige el catalogo. Hoy se resuelve a mano en Revision. |
| **B12** No hay reportes | En curso (hizo Codex, verifica Carlos) | Alumnos, Finanzas, Sueldos, acceso y ajuste aprobados por Carlos; completar prueba manual y clasificación de antecedentes |

## Hecho y sin nadie que lo verifique

| Tarea | Estado | Próximo paso |
|---|---|---|
| **T82** IMPORTANTE: ensayo de restauracion integral de Wings en un servidor desde los respaldos | Hecho, espera verificación (hizo Claude, verifica Codex) | Ensayo hecho por Claude CyE 28/10: produccion desde Drive en 52 s y, con datos, el sitio de prueba en 56 s (105 alumnos, 909 asistencias, plata al peso identica). Informe en docs/04-tecnico/seguridad-vps/2026-10-28-ensayo-restauracion. No probado: servidor desde cero. Pendientes: credencial propia de rclone (Carlos, 5 min en la consola de Google), pasar la clave al administrador de contrasenas y sacarla de la carpeta de respaldos, decidir si el repositorio sigue publico. |

## Claude

| Tarea | Estado | Próximo paso |
|---|---|---|
| **T14** Prueba por dias en el sitio de prueba, por pantalla, contada como cuento | En curso (hizo Codex, verifica Claude) | 01/11: guion y prompt de la tanda 5 listos, siete dias simulados sobre copia con main y plata comparada a mano al peso. No se entrega hasta cerrar controles, desplegar y probar la rutina en el sitio. Defectos nuevos T110, T111 y T112. |

## Codex

| Tarea | Estado | Próximo paso |
|---|---|---|
| **T100** Adelanto cobrado con importe equivocado y cancelado deja la cuota en ese importe | Hecho, espera verificación (hizo Claude, verifica Codex) | H1 corregido por Claude 01/11 en manual y ficha; avisos de exito: Cobro cancelado. y Cobro anulado. Los dos textos de los paneles (H2, en vistas) esperan el OK de Carlos. Sin correr pruebas: maquina reservada por Codex. Falta control de lo corregido. |
| **T102** Nivel: poder inactivarlo y pasar un grupo a otro nivel | En curso (hizo Claude, verifica Codex) | Codex controla etapa 3, niveles y grupos con importes historicos. Solo wings_testing_codex; completa unica reservada al final. |

## Gemini

| Tarea | Estado | Próximo paso |
|---|---|---|
| **T107** Cerrar la caja con la pantalla vieja no avisa que el esperado cambio | Hecho, espera verificación (hizo Claude, verifica Gemini) | Hecha por Claude 01/11: si el esperado cambió, no cierra y avisa el nuevo. La controla Gemini: PROMPT-GEMINI-T107-T108-CONTROL.md, cuando termine T105 y T109. Sin desplegar. |
| **T108** Segunda ficha de quien vino de la primera carga: cobra inscripcion | Hecho, espera verificación (hizo Claude, verifica Gemini) | Hecha por Claude 01/11, decisión de Carlos: quien ya tiene ficha no vuelve a pagar inscripción. La de Thiago en el sitio se resuelve en la tanda 5. La controla Gemini: PROMPT-GEMINI-T107-T108-CONTROL.md. Sin desplegar. |

## Sin empezar (9)

T22 (después), T53 (después), T55 (después), T60 (después), T69 (después), T72 (después), T76 (después), T110, T111.

## Mejoras futuras, no se empiezan ahora (13)

MF6 (ahora), MF1, MF2, MF3, MF4, MF5, MF7, MF10, MF11, MF8 (después), MF9 (después), MF14 (después), MF15 (después).

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
