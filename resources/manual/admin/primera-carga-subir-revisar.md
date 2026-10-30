---
titulo: Subir y revisar el archivo de primera carga
rol: admin
categoria: Primera carga
orden: 22
resumen: Cómo cargar el archivo Excel en Wings y analizar el informe de revisión antes de importar los alumnos.
pantalla: /sistema/primera-carga
---

# Subir y revisar el archivo de primera carga

Esta tarea te explica cómo subir la planilla Excel a Wings para que el sistema audite fila por fila los datos y te avise si hay errores antes de tocar la base de datos.

> [!IMPORTANT]
> *La revisión no guarda nada:* El paso de revisión es 100% seguro. No crea alumnos, no genera cuotas y no modifica nada en el club. Sirve para detectar inconsistencias sintácticas, fechas inválidas o datos faltantes sin riesgo.

---

## Pasos para subir y auditar la planilla

### Paso 1. Seleccionar el archivo completado
1. En **Primera carga**, andá a la sección **3. Revisar el Excel**.
2. En el campo **Excel completado (.xlsx)**, tocá para seleccionar la planilla guardada en tu computadora.
3. Tocá el botón **Revisar**. Wings analizará la hoja Alumnos completa.

![Seleccionar el archivo y presionar Revisar](/ayuda/img/admin/primera-carga-subir-revisar-paso1.png)

---

### Paso 2. Analizar los resultados de la revisión
1. Si el archivo tiene errores, Wings te mostrará el mensaje en rojo: `errores. No se cargó nada.` y listará cada celda observada (fila, columna y mensaje de corrección).
2. Tocá el botón **Descargar** para bajar el Excel marcado: Wings agrega al final de la hoja Alumnos la columna Errores (AM) explicando exactamente qué celda debe arreglarse.
3. Abrí el archivo descargado, corregí las celdas observadas, guardalo nuevamente como `.xlsx` y volvé a subirlo en el paso 1.
4. Cuando el archivo esté impecable, la sección mostrará: `Listo. Archivo revisado sin errores.` y se habilitará el paso 4.

![Informe de errores y botón para descargar el Excel marcado](/ayuda/img/admin/primera-carga-subir-revisar-paso2.png)

---

## Qué hago si…

### ¿Qué hago si Wings rechaza el archivo al subirlo?
Si aparece la alerta `No se cargó el archivo.`, verificá que el archivo tenga formato `.xlsx` (Excel moderno) y que no hayas cambiado los nombres de las hojas originales (`Alumnos`, `Catálogos`, `Guía`).

### ¿Tengo que borrar la columna Errores antes de volver a subir el Excel?
No hace falta borrar la columna `Errores`. Al volver a subir el archivo, Wings la sobrescribe y vuelve a validar todas las filas.
