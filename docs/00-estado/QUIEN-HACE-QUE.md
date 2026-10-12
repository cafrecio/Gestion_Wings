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

Último cambio: 12/10/2026. Avance: **65 defectos cerrados de 74**. Frenan: ninguno.

## Esperan por Carlos

| Tarea | Estado | Próximo paso |
|---|---|---|
| **T11** Que Wings instalado en el telefono abra en la pantalla correcta | Hecho, espera verificación (hizo Gemini) | Codigo verificado por Claude (12/10). El servidor es Apache, no Nginx: el tipo de archivo se corrige solo al desplegar, sin tocar el servidor a mano. Falta desplegar al sitio de prueba y que Carlos lo instale en su telefono |
| **T13** Ayuda dentro de Wings: entrada en el menu y signo de pregunta en cada pantalla | Sin empezar | Decidido por Carlos el 10/10: Ayuda en el grupo Sistema del menu, un ? por pantalla que abre su pagina, buscador, imprimir, cada rol ve lo suyo, detras del login. Son vistas nuevas: falta que Carlos diga quien lo programa |
| **T14** Prueba por dias en el sitio de prueba, por pantalla, contada como cuento | En curso (hizo Codex, verifica Claude) | Dias 1, 2 y 3 hechos por Codex y verificados por Claude contra la base. Dia 3 (lunes 12, feriado): 3 clases canceladas, plata intacta; salio T21. El reloj del sitio de prueba quedo en el lunes 12. Carlos decide como sigue |
| **B12** No hay reportes | En curso (hizo Codex, verifica Carlos) | Alumnos, Finanzas, Sueldos, acceso y ajuste aprobados por Carlos; completar prueba manual y clasificación de antecedentes |

## Hecho y sin nadie que lo verifique

Nada.

## Claude

| Tarea | Estado | Próximo paso |
|---|---|---|
| **T3** Pasar el seguimiento al tablero unico | En curso | Cuando Codex y Gemini entreguen: sacar el estado de DEFECTOS.md y DEFECTOS.html y agregar la prueba que vigila el tablero |
| **T4** Desplegar main al sitio de prueba, borrar alumnos y deudas y probar la primera carga | En curso | Sitio de prueba en 2e059ff (10/10), con el reloj simulado fijado ese dia en el lunes 12/10. Falta desplegar lo publicado despues: T21, T23, T24 y T26. No desplegar mientras Codex recorre un dia de la prueba |
| **T30** Mostrar los centavos: las pantallas redondean importes que ahora se cargan con decimales | Hecho, espera verificación (hizo Codex) | Todas las vistas aprobadas por Carlos (El resto de las vistas OK y Esta OK, 12/10); Claude verifica solo la regla de importes. Sin cierre ni despliegue |

## Codex

Nada en este momento.

## Gemini

| Tarea | Estado | Próximo paso |
|---|---|---|
| **T29** Buscador de Cobrar: la lista sin buscar muestra solo a los que deben, igual que el numero del encabezado | Hecho, espera verificación (hizo Claude) | Gemini verifica la regla (8a8a6b8): sin buscar, la lista trae solo a los que deben y coincide con el encabezado; a quien no debe se llega buscando. Capturas ya aprobadas por Carlos |

## Sin empezar (20)

B13, MF1, MF2, MF3, T2, T6, T7, T9, T10, T12, T22, T25, T27, B3, B5, B6, B7, B8, B9, B11.

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
