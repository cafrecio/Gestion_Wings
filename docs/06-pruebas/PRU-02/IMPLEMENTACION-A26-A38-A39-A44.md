# A26, A38, A39 y A44 — HECHO (Claude), a revisar · más A35 y A32 revisados

07/10/2026, Claude CyE, a pedido directo de Carlos («Hace A38 39 y 26 / A35 y 32 / A38 A39
A26 A44»). No cerrado por el autor; sin despliegue.

## Qué se hizo

| Defecto | Causa comprobada | Cambio |
|---|---|---|
| **A26** celular obligatorio para menores | La validación exigía celular siempre; la columna es NOT NULL | Para un menor el celular es opcional. Si queda vacío se guarda el teléfono del tutor, que para un menor ya era obligatorio. Alta y edición. El asterisco del campo se oculta cuando la fecha de nacimiento es de un menor. Sin migración |
| **A38** paginación en inglés | El idioma del sistema era `en` y no había traducciones | Idioma fijo en castellano (`config/app.php`) y textos en `lang/es`. Alcanza a las 13 listas paginadas, no solo a Clases |
| **A39** Movimientos fuera del menú del operativo | El enlace estaba dentro del bloque que solo ve el ADMIN; la ruta ya lo dejaba entrar | Grupo «Plata» con Movimientos en el menú del operativo, con el mismo ícono y formato que el del admin |
| **A44** correo firmado por Laravel | La plantilla por defecto usa el nombre de la aplicación en la cabecera, el título y el pie; con el valor de fábrica mostraba el logo de Laravel | Plantilla propia en `resources/views/vendor/mail`: cabecera y título «Wings», pie en castellano. No depende de la configuración de cada servidor |

## Los dos que no eran arreglos

- **A35, botón «Historial» redundante: no es un defecto.** La captura del relevamiento
  (`evidencia/audit_admin_cajas_historial_desktop.png`) está mal rotulada: muestra la pantalla
  **Caja** (la lista de cajas del período), y su botón Historial lleva a otra pantalla, el
  historial de movimientos. En `caja/historial.blade.php` no hay ningún botón que apunte a sí
  misma, y esa vista no cambia desde el 03/08. Queda a confirmar por otro agente.
- **A32, interruptor del propio usuario: sigue presente, y es de diseño.** El código ya lo
  marca encendido y deshabilitado para uno mismo, pero `.ds-toggle--disabled` lo dibuja al 45 %
  de opacidad y por eso se ve apagado. Es la misma pieza que A9; conviene resolverlo ahí.

## Lo que no se tocó

- **A44, contenido del correo:** el defecto también decía que era «una sola línea». Hoy el
  resumen ya sale por sección (cajas, clases sin lista, revisiones, liquidaciones), con
  cantidad, lo más viejo y el enlace. No se cambió.
- **API:** `StoreAlumnoRequest` sigue exigiendo celular. La API está apagada.
- **Carbon:** con el idioma en castellano, `cobranza/index` pasa a mostrar el mes abreviado
  en castellano. Era la única fecha que dependía del idioma del sistema.

## Verificación del autor

- `tests/Feature/DefectosMenoresA26A38A39A44Test.php`: 8 pruebas, 28 aserciones.
- Suite completa en `wings_testing_claude`: 499 pruebas, 496 aprobadas y 2 omitidas; la única
  que fallaba era el contador de pruebas declarado en los documentos, actualizado en este commit.
  Se corrió en la carpeta compartida, con cambios de Codex sin subir (A6 a A10) presentes.
- Capturas del sistema, páginas pedidas a Laravel y dibujadas con Chrome con el JS cargado:

| Pantalla | Escritorio | 375 |
|---|---|---|
| A39, menú del operativo | [captura](evidencia/a26-a38-a39-a44/a39-menu-operativo-escritorio.png) | [captura](evidencia/a26-a38-a39-a44/a39-menu-operativo-375.png) |
| A26, alta de un menor: Celular sin asterisco | [captura](evidencia/a26-a38-a39-a44/a26-alta-menor-escritorio.png) | [captura](evidencia/a26-a38-a39-a44/a26-alta-menor-375.png) |
| A26, alta de un mayor: Celular con asterisco | [captura](evidencia/a26-a38-a39-a44/a26-alta-mayor-escritorio.png) | — |

No verificado por el autor: sesión de navegador usada a mano; el cambio del asterisco al
modificar la fecha en vivo (solo se capturó la página ya cargada); el correo en un cliente
real; la captura a 375 del alta no llega a mostrar el campo Celular; no hay captura del ANTES.

[Reproductor de las páginas](evidencia/a26-a38-a39-a44/PaginasA26A39Test.php).
