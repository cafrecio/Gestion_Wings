# Wings — Reglas de Diseño de Vistas

> Este documento es la fuente de verdad para cualquier vista del panel.
> Antes de entregar código de una vista, un agente revisor debe verificar
> cada punto de esta lista. Si alguno falla, el código NO se entrega.

---

## 1. CARDS

### 1.1 Header del card = SOLO el título
- El `alumno-card-header` (o cualquier header de card) contiene **únicamente**:
  - El dot indicador de color/estado
  - El título/nombre del elemento
- **Prohibido en el header**: botones, badges, observaciones, subtítulos, íconos de acción, fechas.
- La observación/descripción va **debajo** del header como `<p>` separado, con el mismo indentado que el texto del título (`padding-left: 1.5rem`).

### Títulos y acciones siempre separados — Carlos, 19/10/2026

- **Nunca colocar «Nuevo» ni otro botón en el mismo `div`, encabezado o fila que un título**, sea de página, tarjeta, sección o subsección. Rige en celular, tablet y escritorio, tanto al crear como al editar.
- El título tiene su bloque exclusivo. Las acciones tienen otro bloque, separado y alineado a la derecha; cambiar a `flex-wrap` o apilar dentro del mismo encabezado no cumple esta regla.
- Una barra de acciones puede compartir espacio con un conteo o información operativa, nunca con el título. La regla de `stats-bar` de §6 no autoriza a mezclar título y botones.
- En «Precios por frecuencia»: título y explicación de vigencia primero; «Nuevo» en su propia barra debajo de la explicación y antes de las filas. Conserva el borde derecho de Guardar.
- Carlos: «no puede nuevo estar en el mismo div que el titulo. NUNCA. En ninguna de las dos pantallas, ni en celular ni en escritorio».

### Listado de Alumnos — decisión de Carlos, 07/10/2026

- Una tarjeta por fila, también en escritorio. No distribuir alumnos en dos tarjetas por fila.
- Carlos rechazó la opción A de A7 y aprobó B: «A- NUNCA EN LA PUTA VIDA, NO CAMBIAR ESO NUNCA / B- OK espero que nadie le ponga un nombre tan largo al deporte ni al nivel».
- Los datos dentro de la tarjeta pueden usar varias columnas, como en B. Esta regla se refiere a tarjetas por fila.
- Evidencia: [propuesta A6–A10](../../06-pruebas/PRU-02/PROPUESTA-A6-A10.md), [visor A7](../../06-pruebas/PRU-02/evidencia/a6-a10/visor-a7.html).

### 1.2 alumno-info — filas del card

- **NO agregar filas extra** si hay espacio en blanco disponible. Maximizar densidad horizontal antes de añadir una nueva fila.
- Si los datos caben en una sola fila, usar `grid-template-columns: repeat(N, 1fr)` explícito en el `div.alumno-info` (no depender del auto-fit).
- **No usar** `grid-column: 1 / -1` para forzar un dato a fila propia salvo que sea estrictamente necesario (ej: texto muy largo que no cabe en el grid).
- Ejemplo: 3 datos → `style="grid-template-columns: repeat(3, 1fr);"` en el `div.alumno-info`, sin span de columnas.

### 1.3 Acciones del card van abajo
- Las acciones (Editar, Eliminar, etc.) van en `alumno-actions` al pie del card.
- `alumno-actions` tiene `border-top: 1px solid var(--color-border)` como separador visual.
- El botón secundario de "crear hijo" (ej: "+ Subrubro") va alineado a la derecha dentro de `alumno-actions` usando `margin-left: auto`.

---

## 2. BOTONES — Regla absoluta de consistencia de tamaño

Hay **tres objetos de botón** en el sistema. Cada objeto tiene su propio tamaño fijo. Dentro de cada objeto, **todos los botones son idénticos** en dimensiones.

En celular, la decisión de Carlos del 18/10/2026 en §7 tiene prioridad sobre estas diferencias: los botones de acción con texto comparten el tamaño del botón Cobrar aprobado como referencia, también entre zonas de una misma vista.

### Objeto A — Botón primario de página (stats-bar)
- Uso: única acción primaria de la página (ej: "Nuevo").
- Componente: `x-ds.button variant="primary"` → clase `.ds-btn` → `width: 112px; height: 36px`.
- **Solo uno por sección/módulo**, en el `stats-bar`.

### Objeto B — Botón de acción de card (alumno-actions)
- Uso: Editar, Eliminar, y similares al pie del card.
- Tamaño: `width: 96px; height: 32px; font-size: 0.82rem; font-weight: 600`. Ancho FIJO — igual para Editar, Eliminar y + Subrubro.
- Se implementa con `<a>` o `<button>` directo con `display:inline-flex; align-items:center; justify-content:center; width:96px; height:32px`.
- Todos los botones del `alumno-actions` de un mismo card son **idénticos en tamaño**.
- Variantes de color: `secondary` para Editar, `danger` para Eliminar.

### Objeto C — Botón de acción de fila de tabla
- Uso: Editar/Eliminar sobre una fila dentro de una tabla.
- Dos sub-variantes permitidas:
  - **Texto**: `height: 26px; width: 64px; font-size: 0.72rem; font-weight: 600; border: 1px solid; background: none; border-radius: var(--radius-btn)`. Editar = color-btn-secondary, Eliminar = color-btn-danger. **Ambos deben tener el mismo `width` fijo (64px).**
  - **Ícono** (lápiz/tacho): `width: 28px; height: 28px; padding: 0; display:inline-flex; align-items:center; justify-content:center; border-radius: var(--radius-btn); border: 1px solid; background: none`. Todos los íconos del mismo tipo tienen el mismo tamaño entre sí.
- Los botones ícono son un objeto distinto a los botones texto — pueden coexistir en la misma vista siempre que dentro de cada tipo sean consistentes.

### Lo que NO se hace nunca
- Mezclar Objeto B y Objeto C en la misma fila/acción.
- Usar `ds-btn` de 112px para acciones de fila de tabla.
- Dejar que el texto cambie el ancho del botón (sin `width` fijo o `min-width` fijo).

---

## 3. TEXTO DE BOTONES — Sin redundancia

- El texto del botón se escribe en el contexto mínimo necesario.
- Si el usuario ya está en la página de Rubros, el botón es "Editar", no "Editar rubro".
- Si el usuario ya está dentro del card de un Rubro específico, "Eliminar" es suficiente.
- Regla general: si el contexto visual hace obvio el objeto de la acción, **no se repite en el texto**.
- Ejemplos correctos: "Nuevo", "Editar", "Eliminar", "+ Subrubro".
- Ejemplos incorrectos: "Nuevo rubro", "Editar rubro", "Eliminar rubro".

---

## 4. TABLAS

### 4.1 Proporciones de columnas
- **Siempre** usar `table-layout: fixed` en tablas con columnas de ancho controlado.
- Definir `<colgroup>` con `<col style="width:Xpx">` para todas las columnas excepto la columna de contenido principal (Nombre), que toma el espacio restante.
- La columna de acciones tiene ancho fijo suficiente para contener los botones sin que se compriman ni desborden.
- Ejemplo de distribución para tabla de subrubros:
  - Nombre: auto (toma el resto)
  - Permitido para: 140px
  - Caja: 70px
  - Acciones: 80px (íconos) o 150px (texto)

### 4.2 Alineación del borde derecho
- La tabla debe tener el mismo `padding` lateral que el card que la contiene.
- Los botones de la última columna deben terminar en la misma línea vertical que los botones del `alumno-actions` del pie del card.
- Para lograrlo: la tabla ocupa `width: 100%` dentro del card, y el padding del card aplica uniformemente a tabla y a `alumno-actions`.

### 4.3 Overflow de texto en celdas
- Celdas de contenido textual: `overflow: hidden; text-overflow: ellipsis; white-space: nowrap`.

---

## 5. SEPARACIÓN DE DATOS POR TIPO

- Cuando un listado agrupa elementos de tipos opuestos (ej: INGRESO vs EGRESO), se presentan en **secciones separadas**, cada una con su propio `stats-bar`.
- No mezclar tipos en un mismo listado plano.
- Cada sección tiene su propio color semántico (INGRESO = success, EGRESO = danger).

---

## 6. STATS-BAR

- Estructura: `display: flex; justify-content: space-between; align-items: center`.
- Izquierda: conteo/info del módulo (puede incluir badge de tipo si aplica).
- Derecha: único botón primario de la sección (Objeto A). El título siempre queda fuera de esta barra, en un bloque separado (§1.1).
- Un `stats-bar` por sección, no por card individual.
- **Aplica también a subsecciones dentro de una misma vista** (ej: "Reglas de primer cobro" dentro de Configuración). Si una subsección tiene acción primaria (Agregar, Nuevo), va en el `stats-bar` de esa subsección, no debajo de las cards ni flotando solo en la página.
- El botón de acción primaria de una subsección inline (sin página propia) usa `x-ds.button variant="primary"` igual que en vistas completas.

---

## 7. ALINEACIÓN GENERAL

- Todos los elementos interactivos de una misma "zona" (ej: pie de card) terminan en el mismo borde derecho.
- No mezclar elementos con `margin-left: auto` que desalineen el grupo visual de acciones.
- En escritorio, `alumno-actions` es `justify-content: flex-start` con el botón secundario ("+ hijo") en `margin-left: auto`.

---

### Celular — alineación de acciones, decisión de Carlos 07/10/2026

- En celular, los botones y el interruptor del pie de tarjeta se alinean a la derecha, también cuando ocupan más de una fila.
- Carlos: «Dijimos que los botones en el celular se alinean todos a la derecha».
- Este criterio tiene prioridad sobre flex-start en celular; las posiciones de escritorio se conservan. T75 lo aplica como regla general de `.alumno-actions` en `app.css` hasta 640 px; las barras propias reutilizan `.acciones-celular-derecha`. Se conservan las grillas específicas de Alumnos, Grupos, Profesores y Niveles, y el 2 × 2 aprobado de Detalle de caja. No se cambia el tamaño ni el funcionamiento del interruptor.
- El primer relevamiento T75 dejó botones de tabla, ingreso y modales para decisión de Carlos. Su pedido posterior de aplicar la consistencia a las pantallas móviles los incluye: los botones con texto comparten ahora la regla siguiente. [Seguimiento T75](../../06-pruebas/PRU-04/IMPLEMENTACION-T75-CONSISTENCIA.md).

### Celular — consistencia y ubicación predecible, decisión de Carlos 18/10/2026

Carlos: «Que el usuario sepa donde esta el boton sin ver, por repeticion».

- **Un mismo tamaño de botón:** tomar Cobrar, señalado por Carlos en las capturas de la ficha del alumno, como referencia. Mantener el mismo ancho, alto, padding y tipografía para los botones de acción con texto en todas las vistas móviles. No usar un Cobrar grande en la cabecera y otro más chico en la deuda de esa misma ficha.
- **Un mismo padding de contenido y acciones:** todas las vistas móviles comparten los mismos márgenes y padding laterales. Las tarjetas, sus secciones internas y las barras deben respetar esa referencia común; el padding anidado no debe desplazar los botones a un borde distinto.
- **Un mismo borde derecho:** las acciones terminan en la misma línea vertical de referencia del contenido, también al ocupar varias filas. Alinear cada grupo a su propio contenedor no alcanza si sus contenedores tienen márgenes diferentes.
- **Una ubicación estable por acción:** conservar la zona, el orden y la posición de cada acción entre pantallas equivalentes. La presencia o ausencia de otros botones no debe centrarla ni trasladarla a otro lugar. También se cumple cuando queda un solo botón.
- **Objetivo de aceptación:** el usuario encuentra la acción por repetición. No se aprueba una vista móvil solo porque los botones caben o no hay desborde; debe cumplir tamaño, padding, borde y ubicación constantes.

Esta decisión amplía el criterio móvil anterior y prevalece sobre las diferencias de tamaño de los objetos A/B/C para botones con texto. Los controles de ícono y los interruptores conservan su tipo; no se convierten en botones de texto. Las medidas y posiciones de escritorio se conservan.

La implementación móvil T75 usa **96 × 32 px**, padding de botón **0**, tipografía **0.82rem/600**, separación **8 px**, margen exterior de contenido **16 px** y referencia interior de acciones **24 px**. Los bordes y rieles se descuentan del padding para que no sumen un desplazamiento. La acción principal ocupa el extremo derecho; sus complementarias van a la izquierda. Cobrar, Filtrar y las confirmaciones conservan esa columna al desaparecer acciones complementarias. Volver conserva la columna derecha en la ficha de Liquidaciones. Detalle mantiene su distribución aprobada 2 × 2. [Tokens móviles](tokens.md#acciones-en-celular--t75) · [Entrega y capturas reales para revisión de Carlos](../../06-pruebas/PRU-04/IMPLEMENTACION-T75-CONSISTENCIA.md). Implementada localmente no equivale a aprobada ni desplegada.

## 8. DÓNDE VIVE EL JAVASCRIPT

Decidido por Carlos el 13/09/2026, mientras se sacaba el código incrustado de las
vistas para poder pasar la CSP a modo bloqueo (SEG-11).

**Un archivo por pantalla.** El JavaScript que sirve a una sola vista va a su
propio archivo bajo `resources/js/`, compilado por Vite y cargado por esa vista
con `<script src>`:

```
resources/js/grupos.js      lo usa solo grupos/_form
resources/js/usuarios.js    lo usa solo usuarios/_form
```

**`ds-app.js` es solo para lo que comparten varias pantallas.** Ahí van el
comportamiento delegado y los componentes reutilizables: `data-enviar-al-cambiar`,
`data-elevar`, el ojo de contraseña, el cierre de avisos, el validador de nombre
repetido. Nada que sirva a una sola vista.

**Por qué.** `ds-app.js` se carga en todas las pantallas. Amontonar ahí la lógica
de las 24 vistas obliga a cada persona a descargar el código de todas para usar el
de una, y convierte un error en ese archivo en un error de todo el sistema a la
vez. Con un archivo por pantalla, la falla queda encerrada donde nació.

**No cambia el conteo de la CSP.** `CspSinCodigoIncrustadoTest` cuenta los
`<script>` **sin** `src`: un archivo propio cargado con `src` sale del conteo
igual que si se hubiera movido a `ds-app.js`, y la política `script-src 'self'`
lo acepta porque viene del propio dominio.

**La pregunta que decide dónde va cada cosa:** ¿lo usa más de una pantalla? Sí,
`ds-app.js`. No, su propio archivo.

---

## 9. CHECKLIST DEL AGENTE REVISOR

Antes de aprobar cualquier vista, verificar:

- [ ] ¿Todo título de página, tarjeta, sección o subsección tiene su bloque separado de «Nuevo» y del resto de botones, en celular y escritorio?
- [ ] ¿El header de cada card tiene SOLO dot + título?
- [ ] ¿Las acciones del card están en `alumno-actions` al pie, con `border-top`?
- [ ] ¿El botón "hijo" (+ Subrubro, etc.) está en `margin-left:auto` dentro de `alumno-actions`?
- [ ] ¿Todos los botones del mismo objeto (B o C) tienen exactamente el mismo tamaño?
- [ ] ¿En celular, los botones de acción con texto tienen el tamaño y padding del Cobrar de referencia, también entre secciones de la misma vista?
- [ ] ¿Las vistas móviles comparten padding lateral y una misma línea derecha para sus acciones, sin desplazamientos por contenedores anidados?
- [ ] ¿Cada acción conserva su ubicación y orden cuando faltan otros botones o queda sola?
- [ ] ¿Los textos de botones son mínimos y sin redundancia de contexto?
- [ ] ¿Las tablas usan `table-layout:fixed` con `<colgroup>`?
- [ ] ¿La columna Nombre no roba todo el ancho?
- [ ] ¿Los botones de acción de fila tienen `width` fijo (no dependen del texto)?
- [ ] ¿El borde derecho de los botones de tabla y del pie del card están alineados?
- [ ] ¿Los datos de tipos distintos están en secciones separadas?
- [ ] ¿Hay un único `stats-bar` por sección con un único botón primario a la derecha?

## 10. CAMPOS DE IMPORTES EN PESOS — T24, decisión de Carlos 11/10/2026

- Todos usan `x-ds.money-input` y el comportamiento común de `resources/js/money-input.js`,
  cargado por `ds-app.js`. También las filas agregadas desde templates; no generar otro input o parser.
- El punto es decimal, hasta dos decimales. `48000` es cuarenta y ocho mil;
  `48.50` es cuarenta y ocho con cincuenta. La coma tipeada se convierte en punto: `48,50` → `48.50`.
- Al entrar al campo se edita sin separador de miles. Al salir o mostrar lo guardado,
  se presenta con coma de miles, punto decimal y dos decimales: `48,000.50`.
- Si se tipea `48.505`, queda `48.50`, el texto/borde se pone rojo y aparece
  «No se pueden colocar más de dos decimales.» durante 4 segundos. **El valor corregido
  no bloquea continuar ni guardar.** Se recorta, no se redondea. Otro tipeo válido limpia el rojo.
- Negativos, letras y cero donde el flujo no lo admite son inválidos. Se mantienen
  los mínimos, máximos, obligatoriedad y campos de solo lectura de cada operación.
- El aviso se crea desde JavaScript externo; no scripts/eventos incrustados en Blade
  ni colores escritos a mano. Usar `var(--color-danger)` para el error.
- Formularios normales, FormData y guardados por JSON mandan el mismo valor canónico,
  sin separador de miles. En el servidor, `App\Rules\ImportePesos` valida/normaliza
  todos los campos de escritura: un pedido directo con tres decimales **se rechaza
  sin escribir**, no se recorta silenciosamente.
- Porcentajes, días y comisión quedan fuera. Esta regla define los campos de
  carga/edición; los textos de importes siguen ahora la regla T30 siguiente.
- Los lectores de Excel mantienen su formato propio. No aplicar el parser del padrón
  a los importes escritos en una pantalla.

[Inventario, pruebas y capturas de T24](../../06-pruebas/PRU-04/IMPLEMENTACION-T24.md).

## 11. PRESENTACIÓN DE IMPORTES — T30, decisión de Carlos 12/10/2026

- En listados, fichas, saldos, totales, avisos y textos calculados, mostrar pesos
  con coma de miles, punto decimal y **siempre dos decimales**: `$48,000.50`,
  `$48,000.00`. Conservar los centavos guardados, también al sumar filas visibles.
- Una sola política en `App\Support\FormatoImporte`: PHP usa `pesos`/`numero`;
  el navegador recibe su configuración como datos y usa `formato-importe.js`.
  Los campos T24 comparten esa presentación, conservando su parser y validación.
- Los nuevos recibos PDF, mails y mensajes de Telegram usan la misma regla.
  Los PDF ya emitidos se conservan, sin regeneración masiva ni cambio de caché.
- **Gráficos excluidos**, por la última decisión de Carlos: «Los graficos no,
  el resto si». Sus ejes, tooltips y número central del gráfico circular conservan
  el formato anterior. Las tarjetas y los detalles de Reportes sí usan T30.
- Excel conserva celdas numéricas. El catálogo nuevo usa máscara de dos decimales;
  sus separadores dependen de Excel. El informe de errores conserva el formato
  del archivo original.
- No cambiar importes guardados, cálculos ni redondeos de negocio. DNI, porcentajes,
  comisión, días y cantidades no son pesos.

[Inventario, pruebas y capturas de T30](../../06-pruebas/PRU-04/IMPLEMENTACION-T30.md).


### Controles y cifras comparables — observaciones de Carlos, 18/10/2026

- En celular, fecha, hora y mes ocupan todo el ancho disponible de su campo, igual que un selector; no limitar una fecha a 220 px dentro de una columna más ancha.
- El cuerpo y las acciones de una tarjeta comparten borde interior. No sumar padding lateral al cuerpo dentro de una tarjeta que ya lo aporta.
- Usar variantes existentes de botón (`secondary`, nunca `sec` en `ds-btn`); los accesos de Inicio son navegación secundaria agrupada.
- Los importes comparables dentro de un mismo nivel usan el mismo tamaño y peso. Carlos eligió «Jerarquía fija y consistente» el 18/10: total destacado, resumen intermedio y fila menor, según la tabla siguiente. Los conteos usan `ds-count-value` y quedan en un nivel secundario. Separar etiquetas de valores; en celular, un resumen estrecho usa filas con importes a la derecha.
- Revisión elige Activo/Inactivo con `x-ds.toggle`, pero la elección se guarda solo al Confirmar con nota. Activo genera la cuota del período; Inactivo da de baja y conserva deudas anteriores. No conectar ese interruptor a la ruta genérica de activar/desactivar alumnos.
- Mostrar a Carlos únicamente las pantallas observadas en esta revisión. La comprobación interna de piezas compartidas abarca las demás pantallas afectadas.


### Jerarquía fija de importes — decisión de Carlos, 18/10/2026

Carlos: «Jerarquía fija y consistente».

| Uso | Clase | Tamaño | Peso |
|---|---|---|---|
| Total de página, indicador financiero o monto final | `ds-money ds-money--total` | `1.1rem` | `800` |
| Resumen por medio/rubro, cierre de tabla o valor por hora | `ds-money ds-money--summary` | `0.9rem` | `700` |
| Importe individual, detalle, deuda de un alumno o anotación | `ds-money ds-money--row` | `0.8rem` | `600` |

- Misma familia `var(--font-sans)`, interlínea `1.5`, cifras tabulares y espaciado normal en los tres niveles.
- Los tamaños no cambian entre módulos ni por la longitud del valor. Una diferencia de tamaño exige una diferencia de función, no otra regla local.
- El color conserva su significado: ingreso, egreso, deuda o estado. No elegir colores distintos para importes de la misma función y estado.
- Poner la clase en el texto del importe, conservando el formato T30, los valores y los cálculos.
- En celular, apilar los grupos financieros cuando no entren. No recortar cifras ni achicar la fuente para disimular un desborde.
- Las columnas monetarias de las tablas usan `col-importe` (176 px) y conservan el desplazamiento horizontal.
- Cantidades y porcentajes son secundarios dentro de un bloque financiero; gráficos y sus textos centrales conservan su tratamiento y formato propios.
- Aplicado en Caja, Cashflow, Cobranza, Inicio administrativo, Liquidación y Reportes financieros/Sueldos. [Verificación y galería enfocada](../../06-pruebas/PRU-04/IMPLEMENTACION-T75-JERARQUIA.md).
