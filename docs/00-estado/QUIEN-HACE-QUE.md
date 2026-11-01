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
| **T113** Validar se ofrece en una clase que todavia no ocurrio y no se puede deshacer | Sin empezar | Decidir: antes o despues de produccion. CLA-024 lo describe desde el 13/10; la clase validada se paga en la proxima liquidacion. Se retrata en 56.8. |
| **T114** Reportes Sueldos no refleja lo pagado cuando cambia el valor hora o hay ajuste | Sin empezar | Decidir: Mariela figura 342.000 y se pago 361.000; Hernan 561.000 y se pago 561.100. Dice Estimado aunque ya este pagado. Se retrata en 55.4. |
| **T115** Nueva liquidacion en enero: cambiar solo el mes genera diciembre del anio nuevo, vacia y sin aviso | Sin empezar | Decidir: propuesta avisar o rechazar un periodo futuro antes del 2/1/2027. No mueve plata y se elimina. Probado en la copia, simulacion/tanda-5/ano.php. |
| **B12** No hay reportes | En curso (hizo Codex, verifica Carlos) | Alumnos, Finanzas, Sueldos, acceso y ajuste aprobados por Carlos; completar prueba manual y clasificación de antecedentes |

## Hecho y sin nadie que lo verifique

Nada.

## Claude

| Tarea | Estado | Próximo paso |
|---|---|---|
| **T14** Prueba por dias en el sitio de prueba, por pantalla, contada como cuento | En curso (hizo Codex, verifica Claude) | 01/11: guion y prompt de los dias 48 a 57 listos, diez dias simulados con plata al peso. Carlos: con estos resultados se despliega en produccion. Falta T100, desplegar en el sitio y probar la rutina; recien ahi Carlos pasa el prompt a Codex. |
| **T82** IMPORTANTE: ensayo de restauracion integral de Wings en un servidor desde los respaldos | Devuelto (hizo Claude, verifica Codex) | Control Codex 01/11: datos/APP_KEY/archivos recuperados; prueba56 tablas y65 archivos identicos,funcional200. Devuelto por liquidaciones/falso0, falta limpieza en fallo, restaurador legacy y rclone.conf reescrito por llamada original. Limpieza final0restos. VERIFICACION-T82-CODEX.md; servidor vacio/volumen real no probados; tres pendientes existentes abiertos. |

## Codex

Nada en este momento.

## Gemini

Nada en este momento.

## Sin empezar (10)

T22 (después), T53 (después), T55 (después), T60 (después), T69 (después), T72 (después), T76 (después), T110 (después), T111 (después), T112 (después).

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
