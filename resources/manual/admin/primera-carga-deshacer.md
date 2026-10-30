---
titulo: Deshacer la primera carga
rol: admin
categoria: Primera carga
orden: 24
resumen: Cómo revertir de forma limpia la importación inicial ante errores masivos, antes de que se registren cobros.
pantalla: /sistema/primera-carga
---

# Deshacer la primera carga

Esta tarea te explica cómo dar marcha atrás a la carga masiva de datos si detectás que importaste una planilla equivocada o incompleta.

> [!IMPORTANT]
> *Condición estricta: cero cobros:* La opción de deshacer la primera carga solo está permitida mientras no se haya registrado ningún cobro en Wings. En el momento en que la operativa o el dueño cobran la primera cuota o emiten un recibo, la posibilidad de deshacer queda bloqueada definitivamente para proteger la integridad contable.

---

## Pasos para deshacer la carga inicial

### Paso 1. Localizar la opción en Primera carga
1. En el menú del administrador, ingresá a **Sistema** y seleccioná **Primera carga**.
2. Desplazate hasta la sección **4. Cargar en Wings**.
3. Verificá que se muestre el botón **Deshacer** junto a la aclaración: `Disponible mientras no haya cobros ni actividad posterior.`

![Botón Deshacer en la sección 4](/ayuda/img/admin/primera-carga-deshacer-paso1.png)

---

### Paso 2. Confirmar la reversión
1. Tocá el botón **Deshacer**.
2. El sistema te pedirá confirmación: `¿Deshacer solamente esta carga? Se conservan los usuarios y catálogos.`
3. Aceptá la confirmación. Wings eliminará de forma limpia y segura los alumnos y deudas creados por esa planilla, manteniendo intactos tus deportes, categorías, grupos y usuarios.
4. El asistente volverá al estado inicial para que puedas corregir tu Excel y volver a importar.

![Confirmación de reversión de la primera carga](/ayuda/img/admin/primera-carga-deshacer-paso2.png)

---

## Qué hago si…

### ¿Qué hago si el botón Deshacer no aparece o está bloqueado?
Si en lugar del botón aparece el texto: `No se puede deshacer: ya hay un cobro registrado. Los alumnos y deudas se conservan.`, al existir cobros reales asentados en caja o Cashflow Wings no permite borrar masivamente los registros para no dejar huérfana la trazabilidad contable. En este caso, cualquier corrección de alumnos debe realizarse de forma individual desde la ficha de cada alumno.
