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

Último cambio: 27/10/2026. Avance: **68 defectos cerrados de 70**. Frenan: ninguno.

## Esperan por Carlos

| Tarea | Estado | Próximo paso |
|---|---|---|
| **T10** Privacidad antes de produccion: texto, leyenda de inscripcion y condiciones de servicio | Hecho, espera verificación (hizo Claude, verifica Carlos) | Borradores hechos el 17/10 en docs/01-producto/legal (condiciones de servicio, privacidad, cookies, leyenda de inscripcion). Carlos completa datos entre corchetes, elige moneda, confirma IVA y pais del servidor, inscribe en la AAIP. Enlace desde el ingreso: tarea aparte |
| **T13** Ayuda dentro de Wings: entrada en el menu y signo de pregunta en cada pantalla | Sin empezar | [ANTES DE PRODUCCION, Carlos 18/10] Decidido por Carlos el 10/10: Ayuda en el grupo Sistema del menu, un ? por pantalla que abre su pagina, buscador, imprimir, cada rol ve lo suyo, detras del login. Son vistas nuevas: falta que Carlos diga quien lo programa |
| **T32** Reloj simulado: que al frenarse no deje a todos sin poder entrar | Hecho, espera verificación (hizo Claude) | Tabla: DU-46 · SIS-009 y prueba D02-04 (caso ACC-003). Hecho por Claude y desplegado en el sitio de prueba. Dos arreglos: (1) c699bc1, herramienta: con el reloj simulado parado el limite de ingresos no se aplica; (2) 602efcc, DEL SISTEMA y llega a produccion: el limite de 5 ingresos por minuto compartia contador con los avisos de seguridad que la pantalla de ingreso manda sola; cada aviso gastaba un intento. Probado en el sitio con ingresos reales: reloj corriendo, entran 5 y el sexto queda afuera, se libera al minuto; reloj parado, entran 8 de 8. Sitio de prueba devuelto a hora real. Falta que otro agente verifique, sobre todo (2): es el limite de seguridad del ingreso. Carlos dice quien |
| **T86** Pagar el sueldo de una operativa: hoy no hay forma | Sin empezar | Prueba dia 22.5 (27/10): el subrubro Op-Nombre se crea reservado del sistema; Cashflow no lo ofrece y rechaza el envio forzado; liquidaciones son solo de profesores. Decidir como se paga (Cashflow con ese subrubro, recomendado) y asignar. |
| **T87** Nueva liquidacion: el numero de clases mezcla meses | Sin empezar | Prueba dia 22.6: al lado del mes elegido muestra las clases pendientes de todos los meses (Lucia 20 = 17 de octubre + 3 de septiembre validadas a mano). El calculo esta bien (17). Mostrar solo las del mes elegido; asignar. |
| **T88** Rechazos que faltan: gasto mayor que el cajon y pago parcial sin motivo | Sin empezar | Prueba dias 19-22: MOV-004 (el esperado de la caja queda negativo) y COB-009 (ni pantalla ni servidor piden motivo). Hay pruebas que los retratan en prueba-tecnica. Asignar. |
| **T89** Pantallas que no muestran lo que deberian (prueba dias 19-22) | Sin empezar | Movimientos sin autor ni recibo (MOV-011/012); historial de correccion de Cashflow sin enlace (CSH-007); Inicio operativa dice Sin lista con todos ausentes; detalle de caja sin hora (CAJ-017). Visual: Codex o Gemini. |
| **B12** No hay reportes | En curso (hizo Codex, verifica Carlos) | Alumnos, Finanzas, Sueldos, acceso y ajuste aprobados por Carlos; completar prueba manual y clasificación de antecedentes |

## Hecho y sin nadie que lo verifique

Nada.

## Claude

| Tarea | Estado | Próximo paso |
|---|---|---|
| **T14** Prueba por dias en el sitio de prueba, por pantalla, contada como cuento | En curso (hizo Codex, verifica Claude) | Dia 23 revisado (interno 21/21, seguridad 22/22). Falta probar Revision: nadie podia ir porque los 103 vinieron en octubre (error de planificacion de Claude). El guion de noviembre deja 3 alumnos sin venir todo el mes y un alta tardia, para la generacion del 1/12 (detalle en ESTADO-PRUEBA, dia 23). Cinco liquidaciones abiertas por orden de Carlos. |

## Codex

| Tarea | Estado | Próximo paso |
|---|---|---|
| **T90** Una clase que ya esta en una liquidacion cerrada se puede cancelar | Hecho, espera verificación (hizo Claude, verifica Codex) | Reparado por Claude el 27/10 (DU-30, CLA-013): toggleCancelada rechaza cancelar una clase de una liquidacion cerrada o pagada, con texto en el panel de la ficha. CancelarClaseLiquidadaT90Test 4/4 (rojo sin el arreglo); rapida 932/6 omitidas. Codex verifica logica; despues se sube al sitio de prueba. |
| **T92** Una segunda corrida de la generacion de cuotas pisaba la constancia de la primera | Hecho, espera verificación (hizo Claude, verifica Codex) | Corregido por Claude el 27/10 a pedido de Carlos (PRU-04 dia 23: la constancia de noviembre paso de 100 creadas a 0). La primera corrida deja la constancia; otra posterior suma lo que crea o manda a Revision y conserva la hora (terminada_at tiene ON UPDATE en MariaDB). Prueba nueva en GeneracionCuotasReintentoT40Test; rapida 933/6 omitidas. Codex verifica logica. |

## Gemini

Nada en este momento.

## Sin empezar (20)

T12 (antes de producción), T22 (después), T25 (antes de producción), T45 (antes de producción), T46 (antes de producción), T51 (antes de producción), T53 (después), T54 (antes de producción), T55 (después), T60 (después), T62 (antes de producción), T65 (antes de producción), T69 (después), T70 (antes de producción), T71 (antes de producción), T72 (después), T76 (después), T85, B3 (antes de producción), T82 (antes de producción).

## Mejoras futuras, no se empiezan ahora (10)

MF6 (ahora), MF1, MF2, MF3, MF4, MF5, MF7, MF10, MF8 (después), MF9 (después).

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
