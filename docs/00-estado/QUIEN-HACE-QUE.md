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

Último cambio: 28/10/2026. Avance: **69 defectos cerrados de 70**. Frenan: ninguno.

## Esperan por Carlos

| Tarea | Estado | Próximo paso |
|---|---|---|
| **T10** Privacidad antes de produccion: texto, leyenda de inscripcion y condiciones de servicio | Hecho, espera verificación (hizo Claude, verifica Carlos) | Borradores hechos el 17/10 en docs/01-producto/legal (condiciones de servicio, privacidad, cookies, leyenda de inscripcion). Carlos completa datos entre corchetes, elige moneda, confirma IVA y pais del servidor, inscribe en la AAIP. Enlace desde el ingreso: tarea aparte |
| **B12** No hay reportes | En curso (hizo Codex, verifica Carlos) | Alumnos, Finanzas, Sueldos, acceso y ajuste aprobados por Carlos; completar prueba manual y clasificación de antecedentes |

## Hecho y sin nadie que lo verifique

| Tarea | Estado | Próximo paso |
|---|---|---|
| **T82** IMPORTANTE: ensayo de restauracion integral de Wings en un servidor desde los respaldos | Hecho, espera verificación (hizo Claude, verifica Codex) | Ensayo hecho por Claude CyE 28/10: produccion desde Drive en 52 s y, con datos, el sitio de prueba en 56 s (105 alumnos, 909 asistencias, plata al peso identica). Informe en docs/04-tecnico/seguridad-vps/2026-10-28-ensayo-restauracion. No probado: servidor desde cero. Pendientes: credencial propia de rclone (Carlos, 5 min en la consola de Google), pasar la clave al administrador de contrasenas y sacarla de la carpeta de respaldos, decidir si el repositorio sigue publico. |

## Claude

| Tarea | Estado | Próximo paso |
|---|---|---|
| **T14** Prueba por dias en el sitio de prueba, por pantalla, contada como cuento | En curso (hizo Codex, verifica Claude) | Dia 23 revisado (interno 21/21, seguridad 22/22). Falta probar Revision: nadie podia ir porque los 103 vinieron en octubre (error de planificacion de Claude). El guion de noviembre deja 3 alumnos sin venir todo el mes y un alta tardia, para la generacion del 1/12 (detalle en ESTADO-PRUEBA, dia 23). Cinco liquidaciones abiertas por orden de Carlos. |
| **T70** Reporte de Alumnos: cantidad de altas y de bajas del mes | Hecho, espera verificación (hizo Codex, verifica Claude) | Verificar historial, cantidades del mes y capturas T70/T71; aspecto pendiente de Carlos |
| **T71** Deuda de alumnos inactivos: se muestra aparte y el duenio puede darla por perdida | Hecho, espera verificación (hizo Codex, verifica Claude) | Verificar deuda separada, perdida ADMIN, auditoria y reingreso; aspecto pendiente de Carlos |

## Codex

Nada en este momento.

## Gemini

| Tarea | Estado | Próximo paso |
|---|---|---|
| **T62** Cancelar una clase con lista tomada avisa; no se cancela si esta en una liquidacion cerrada o pagada. Reactivar controla choques y conserva el motivo | En curso (hizo Gemini, verifica Claude) | Devuelta por Claude CyE 28/10: logica aprobada (101/101 del area, 7/7 ataques), falta CLA-012.c: GenerarDeudasMensualesCommand cuenta presentes de clases canceladas. Agregar cancelada=false y prueba roja sin el cambio. Despues, OK visual de Carlos. Ver VERIFICACION-T62-CLAUDE.md |

## Sin empezar (12)

T12 (antes de producción), T13 (antes de producción), T22 (después), T45 (antes de producción), T46 (antes de producción), T51 (antes de producción), T53 (después), T55 (después), T60 (después), T69 (después), T72 (después), T76 (después).

## Mejoras futuras, no se empiezan ahora (11)

MF6 (ahora), MF1, MF2, MF3, MF4, MF5, MF7, MF10, MF11, MF8 (después), MF9 (después).

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
