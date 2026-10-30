---
titulo: Eliminar un alumno cargado por error
rol: admin
categoria: Alumnos y familias
orden: 47
resumen: Cómo borrar del todo la ficha de un alumno que se cargó por equivocación y nunca tuvo actividad, y qué hacer si ya la tuvo.
pantalla: /alumnos/{alumnoId}
---

# Eliminar un alumno cargado por error

Eliminar sirve para una sola cosa: borrar un alumno que se cargó por error (duplicado, DNI equivocado, una prueba) y que nunca tuvo actividad. Para alguien que deja de venir, lo que corresponde es pasarlo a **Inactivo**: conserva su historia. Solo el dueño puede eliminar.

---

## Pasos para eliminar

1. Entrá a **Alumnos**, buscá al alumno y abrí su ficha con **Ver**.
2. Al pie de la ficha, tocá **Eliminar**.
3. Se abre el aviso «¿Confirmás la eliminación permanente de este alumno?», que explica: «Esta acción eliminará la ficha, planes y deudas sin cobrar asociadas. El DNI quedará liberado.».
4. Tocá **Eliminar** para confirmar, o **Cerrar** para volver sin borrar nada.

Después de eliminarlo, el DNI queda libre para cargar al alumno de nuevo con los datos correctos.

![Aviso de eliminación permanente](/ayuda/img/admin/eliminar-alumno-paso1.png)

---

## Qué hago si…

### ¿Qué hago si en lugar del botón Eliminar aparece «No se puede eliminar:»?
El alumno ya tuvo actividad y Wings lo protege. La ficha dice el motivo, que puede ser uno de estos:
- *Tiene pagos registrados.*
- *Tiene asistencias registradas.*
- *Tiene cuotas con cobro o condonadas.*
- *Tiene movimientos de caja asociados.*
- *Tiene excepciones de cobranza registradas.*
- *Tiene deuda dada por perdida registrada.*
- *Tiene cargos con cobro o condonados.*

En ese caso no se puede borrar. Si el alumno ya no viene, pasalo a **Inactivo** con el interruptor de la ficha.
