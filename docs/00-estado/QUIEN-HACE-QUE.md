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

Último cambio: 10/10/2026. Avance: **65 defectos cerrados de 74**. Frenan: ninguno.

## Esperan por Carlos

| Tarea | Estado | Próximo paso |
|---|---|---|
| **T1** Completar las capturas del cambio en app.css | Devuelto (hizo Gemini, verifica Codex) | Carlos reviso desde su celular el 10/10: las vistas estan bien. Solo falta Cobrar a un alumno, que necesita alumnos cargados (despues de la primera carga) |
| **T11** Que Wings instalado en el telefono abra en la pantalla correcta | Sin empezar | Carlos lo instala desde Chrome en su telefono y dice que le ofrece y donde abre. Al archivo de instalacion le falta indicar la pantalla de arranque |
| **T13** Ayuda dentro de Wings: entrada en el menu y signo de pregunta en cada pantalla | Sin empezar | Decidido por Carlos el 10/10: Ayuda en el grupo Sistema del menu, un ? por pantalla que abre su pagina, buscador, imprimir, cada rol ve lo suyo, detras del login. Son vistas nuevas: falta que Carlos diga quien lo programa |
| **T14** Prueba por dias en el sitio de prueba, por pantalla, contada como cuento | En curso (hizo Codex, verifica Claude) | Dia 1 hecho y verificado. Dia 2 = domingo 11/10, sin actividad: si hay clases se cancelan. Dia 3 = lunes 12/10, feriado, sin actividad. La prueba avanza con el calendario real (Carlos, 10/10) |
| **T18** Cobro adelantado: selector de mes y anio, importe editable y aviso antes de confirmar | Hecho, espera verificación (hizo Claude) | Pantalla aprobada por Carlos (10/10, «La parte de cobro anticipado OK»). Desplegado en el sitio de prueba. Falta que otro agente verifique la logica de cobro; Carlos dice quien. Nadie hizo todavia un cobro adelantado real en el sitio de prueba |
| **B12** No hay reportes | En curso (hizo Codex, verifica Carlos) | Alumnos, Finanzas, Sueldos, acceso y ajuste aprobados por Carlos; completar prueba manual y clasificación de antecedentes |

## Hecho y sin nadie que lo verifique

Nada.

## Claude

| Tarea | Estado | Próximo paso |
|---|---|---|
| **T16** Inicio y Reportes muestran ingresos 0: Cuota Mensual e Inscripcion quedaron sin clasificar | Devuelto (hizo Claude, verifica Codex) | Codex verifico 462e8b1: cambiar el tipo del rubro admite APORTE en egreso y RETIRO en ingreso; la migracion deja NULL los dos nombres nuevos si ya existian. Corregir y devolver a Codex. Formulario aprobado por Carlos (T16 OK) |
| **T3** Pasar el seguimiento al tablero unico | En curso | Cuando Codex y Gemini entreguen: sacar el estado de DEFECTOS.md y DEFECTOS.html y agregar la prueba que vigila el tablero |
| **T4** Desplegar main al sitio de prueba, borrar alumnos y deudas y probar la primera carga | En curso | Sitio de prueba en d0c85c1 (10/10) con 100 alumnos, el dia 1 de PRU-04, T16, T18 y el boton Cobrar del admin en Caja |
| **T17** Inicio tiene que contar cuotas mas inscripciones en la deuda | Hecho, espera verificación (hizo Gemini, verifica Claude) | Verificar en Inicio (/admin/dashboard) que Alumnos por cobrar coincida al peso con Cobranza (cuotas + inscripciones) contra la base |

## Codex

Nada en este momento.

## Gemini

Nada en este momento.

## Sin empezar (14)

B13, T2, T6, T7, T9, T10, T12, B3, B5, B6, B7, B8, B9, B11.

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
8. **Mientras dure la transición, `DEFECTOS.md` y `DEFECTOS.html` se siguen marcando** en el
   mismo commit, como hasta ahora: `DefectosNoDivergenTest` los sigue vigilando. Claude trae
   esos cierres al tablero con `tablero.php traer`. Cuando Codex y Gemini entreguen lo que
   tienen en curso, el estado sale de esos dos archivos y queda solo en el tablero.
9. **Las capturas de celular van en un marco de 375**, no achicando la ventana de Chrome:
   Windows no deja ventanas de menos de 500 píxeles y recorta. El control es capturar el
   login: si sale cortado, el que mide mal es el método. Usar
   `docs/06-pruebas/PRU-02/capturas-cashflow/marco-375.html`.
10. **Los tres agentes comparten la carpeta.** Antes de commitear, mirar que no se esté
    llevando trabajo ajeno sin commitear.
