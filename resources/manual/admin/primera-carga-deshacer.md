---
titulo: Deshacer la primera carga
rol: admin
categoria: Primera carga
orden: 24
resumen: Cómo revertir de forma limpia la importación inicial ante errores masivos, antes de que se registren cobros o actividad.
pantalla: /sistema/primera-carga
---

# Deshacer la primera carga

Esta tarea te explica cómo dar marcha atrás a la carga masiva de datos si detectás que importaste una planilla equivocada o con errores.

> [!IMPORTANT]
> *Condición estricta para deshacer:* La opción de deshacer la primera carga solo está permitida mientras no se haya registrado ningún cobro y los alumnos importados no tengan ninguna actividad ni modificaciones posteriores en el sistema.

---

## Pasos para deshacer la carga inicial

### Paso 1. Localizar la opción en Primera carga
1. En el menú del administrador, ingresá a **Sistema** y seleccioná **Primera carga**.
2. Desplazate hasta la sección **4. Cargar en Wings**.
3. Verificá que se muestre el botón **Deshacer** junto a la aclaración: «Disponible mientras no haya cobros ni actividad posterior.»

![Botón Deshacer en la sección 4](/ayuda/img/admin/primera-carga-deshacer-paso1.png)

---

### Paso 2. Confirmar la reversión
1. Tocá el botón **Deshacer**.
2. El sistema te pedirá confirmación: «¿Deshacer solamente esta carga? Se conservan los usuarios y catálogos.»
3. Aceptá la confirmación. Wings eliminará de forma limpia los alumnos y deudas creados por esa planilla, manteniendo intactos tus deportes, categorías, grupos y usuarios.
4. El asistente volverá al estado inicial con el mensaje «Se deshizo solamente la primera carga. Los catálogos y usuarios se conservan.» para que puedas corregir tu archivo y volver a importar.


---

## Qué hago si…

### ¿Qué hago si Wings rechaza deshacer la carga?
Wings rechaza la reversión en cualquiera de estas cuatro situaciones:

1. *Ya se registró un cobro:* Si la operativa o el dueño cobraron al menos una cuota o emitieron un recibo, en la pantalla se muestra el aviso «No se puede deshacer: ya hay un cobro registrado. Los alumnos y deudas se conservan.» y el botón **Deshacer** ni siquiera aparece.
2. *Los alumnos ya tienen actividad posterior:* Si una profesora ya tomó lista de asistencia en una clase donde asisten alumnos de la carga, si entraron en revisión de cobranza, registraron asistencias en exceso o tienen movimientos operativos, Wings muestra: «No se puede deshacer: los alumnos ya tienen actividad posterior a la carga.»
3. *Se agregaron nuevas deudas, cargos o planes:* Si a los alumnos importados se les asignaron nuevos planes, cuotas manuales o cargos posteriores, Wings rechaza con: «No se puede deshacer: hay nuevas deudas, cargos o planes posteriores a la carga.»
4. *No existe una carga terminada:* Si no hay una importación previa registrada para revertir, Wings indica: «No hay una carga para deshacer.»

En cualquiera de estos casos, para no romper la trazabilidad contable ni el historial de clases, cualquier corrección de alumnos debe realizarse de forma individual desde la ficha de cada alumno.
