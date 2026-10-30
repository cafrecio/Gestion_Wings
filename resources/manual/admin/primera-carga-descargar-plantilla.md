---
titulo: Descargar la plantilla de primera carga
rol: admin
categoria: Primera carga
orden: 21
resumen: Cómo preparar los catálogos del club y descargar el Excel modelo para la carga inicial de alumnos y deudas.
pantalla: /sistema/primera-carga
---

# Descargar la plantilla de primera carga

Esta tarea te explica cómo preparar los catálogos deportivos y descargar la plantilla oficial de Excel para migrar los alumnos y sus deudas históricas a Wings.

> [!IMPORTANT]
> *La plantilla depende de tus catálogos:* La hoja Excel incluye listas desplegables con los deportes, grupos y planes arancelarios configurados en Wings. Antes de descargar la plantilla, debés tener cargado al menos un deporte con su grupo y su precio mensual activo.

---

## Pasos para preparar y descargar la plantilla

### Paso 1. Preparar los catálogos deportivos
1. Ingresá al sistema con tu usuario administrador. Si el club es nuevo, Wings te mostrará automáticamente la pantalla **Primera carga** en el menú **Sistema**.
2. En la sección **1. Preparar los catálogos**, revisá los contadores de deportes, niveles, grupos y planes activos.
3. Si falta configurar algún grupo o precio, tocá el botón **Preparar** para ir al módulo de Grupos.
4. Cuando el indicador esté en verde y diga «Listo. Podés descargar la plantilla.», tocá el botón **Continuar**.

![Preparar catálogos en el paso 1](/ayuda/img/admin/primera-carga-descargar-plantilla-paso1.png)

---

### Paso 2. Descargar y conocer la plantilla
1. En la sección **2. Completar la plantilla**, tocá el botón **Descargar**. Wings generará el archivo en formato .xlsx listo para completar.
2. Abrí el archivo en tu computadora y completá **solamente la hoja Alumnos**:
   - Una sola fila por cada alumno en un deporte. Si una persona practica dos deportes, usá una fila distinta para cada deporte.
   - Escribí DNI, apellido, nombre, fecha de nacimiento, fecha real de ingreso al club y celular de contacto (para menores, es obligatorio indicar nombre y celular del tutor).
   - Seleccioná deporte, grupo y plan usando las listas desplegables de la planilla.
   - Si debe cuotas anteriores, respondé "Sí" en **Tiene deuda** y completá los pares **Período** (ej: "102026") y **Monto** con lo que efectivamente resta pagar (hasta 12 períodos por fila).

![Descargar la plantilla Excel en el paso 2](/ayuda/img/admin/primera-carga-descargar-plantilla-paso2.png)

---

## Qué hago si…

### ¿Qué hago si al querer continuar me dice que faltan catálogos?
Si Wings muestra el aviso «Prepará al menos un grupo con un plan activo y precio.», entrá a **Grupos**, asegurate de crear al menos un grupo vinculado a un deporte y nivel, y agregale un plan con clases por semana y precio mensual activo. Luego volvé a **Primera carga**.

### ¿Qué hago si en la plantilla necesito agregar un grupo nuevo?
No escribas opciones a mano en la hoja Catálogos de Excel. Andá a Wings, creá el grupo o plan en el sistema y volvé a descargar una plantilla limpia para que tome las nuevas listas.
