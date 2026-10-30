---
titulo: Administrar rubros y subrubros
rol: admin
categoria: El club
orden: 44
resumen: Cómo crear los conceptos con los que se clasifica cada ingreso y gasto, quién puede usarlos y si cuentan para el resultado del club.
pantalla: /rubros
---

# Administrar rubros y subrubros

Todo ingreso o gasto de Wings se anota con un rubro y un subrubro. El rubro es la familia (por ejemplo, "Gastos Operativos") y dice si es de ingreso o de egreso. El subrubro es el concepto concreto ("Limpieza") y define tres cosas: quién lo puede usar, si es plata del club o de los dueños, y si aparece en la caja del mostrador.

Algunos rubros y subrubros los administra Wings (por ejemplo, las cuotas o los sueldos de los profesores): no se pueden editar, desactivar ni eliminar.

---

## Crear un rubro

1. En el menú lateral, sección *El club*, entrá a **Rubros** y tocá **Nuevo**.
2. Completá **Nombre**, elegí **Tipo** (**INGRESO** o **EGRESO**) y, si querés, una **Observación**.
3. Tocá **Guardar**. Wings muestra «Rubro creado correctamente.».

---

## Crear un subrubro

1. En **Rubros**, en la tarjeta del rubro, tocá **Subrubro**.
2. Completá **Nombre**.
3. En **Permitido para** elegí:
   - **Ambos**: la operativa lo usa en su caja y vos en Cashflow.
   - **Solo Admin**: la operativa no lo ve ni lo usa.

   En la lista de rubros, la columna *Permitido para* los muestra como OPERATIVO (Ambos) y ADMIN (Solo Admin).
4. En **Qué es** elegí si es plata **Del club** (cuenta en los ingresos y gastos de Inicio y Reportes) o de los dueños: **Aporte de los dueños** en un rubro de ingreso, **Retiro de los dueños** en uno de egreso. Lo de los dueños se muestra aparte y no cambia el resultado del club.
5. El interruptor **Afecta caja** / **No afecta caja** define si la operativa lo ve en su caja. Para que aparezca en el mostrador tiene que estar en **Ambos** y en **Afecta caja**.
6. Tocá **Guardar**. Wings muestra «Subrubro creado correctamente.».

![Formulario de nuevo subrubro](/ayuda/img/admin/administrar-rubros-paso1.png)

---

## Editar, desactivar o eliminar

- **Editar** en la tarjeta del rubro, o en la fila del subrubro, cambia sus datos.
- Un subrubro no se elimina: se pausa con **Pausar** en su fila y deja de ofrecerse. **Activar** lo vuelve a habilitar.
- **Eliminar** en la tarjeta borra el rubro, solo si no tiene subrubros.

![Rubros con sus subrubros](/ayuda/img/admin/administrar-rubros-paso2.png)

---

## Qué hago si…

### ¿Qué hago si la operativa no ve un subrubro en su caja?
Revisá que el subrubro no figure como *(inactivo)* en la lista, que esté en **Ambos** y en **Afecta caja**. Si falta una de las tres, la operativa no lo ve.

### ¿Qué hago si no me deja cambiar el tipo de un rubro?
Si el rubro tiene un subrubro de **Aporte de los dueños** y lo querés pasar a egreso (o uno de **Retiro de los dueños** y lo querés pasar a ingreso), Wings lo rechaza y nombra el subrubro: *No se puede cambiar el tipo: … tiene una clasificación incompatible con el nuevo tipo*. Cambiá primero **Qué es** en ese subrubro.

### ¿Qué hago si dice «No se puede eliminar: tiene subrubros asociados.»?
Un rubro con subrubros no se borra. Pausá sus subrubros si no se usan más.

### ¿Qué hago si dice «No se puede editar: rubro reservado del sistema.»?
Ese rubro lo administra Wings. No hace falta tocarlo.
