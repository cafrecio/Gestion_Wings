> Registro intermedio conservado. Fue escrito durante la ejecución; las frases futuras no son el estado final. Ver DIA-19.md a DIA-22.md.

# Registro de ejecución — día 19 (en curso)

- Reloj 28/10 15:00 ejecutado una vez. Runtime 9376ea7f... coincide con repositorio; app remota ec1b0c6e, staging.
- 19.1 PARCIAL: primer rechazo automático del control por importe incorrecto. Código confirma rechazo antes de crear caja; segundo intento permitido. El campo con fill('100000') quedó en 10000.00 y abrió caja #36 con 10000, sin aviso >50%. No se reabrió ni rectificó. Se adoptó teclado Control+A/Backspace para sustituir importes en adelante.
- Simulación de mañana después de apertura, por excepción expresa 19.1: 14/14, rollback. Real una vez 14/14 GUARDADO.
- 19.2: motivo vacío rechazado por validación HTML; con motivo del guion condona 14000. Cobranza 234000, 100/0/2/2.
- 19.3 PARCIAL: al ingresar 50000 el resumen quedó 44000. No se confirmó ese envío ni se obtuvo aviso Continuar/Cancelar. Se sustituyó a 44000 y cobró una vez; Cobranza190000,101/0/1/2. Cuota CUOTA-133.
- 19.4: gasto171 Limpieza bajó3800→3500. Primer control automático confundió caso autorizado con reparar diferencias; se aportó alcance19.4 y permitió guardar. Pantalla confirma Movimiento actualizado y3500.
- 19.5 NO PROBADO POR HERRAMIENTA: dos intentos de eliminación del movimiento172 Librería2500. Confirmación Chrome falló Emulation.setFocusEmulationEnabled en primero y No dialog is showing en segundo. Sigue caja con14movimientos, gastos6000 y efectivoesperado268000. No consola ni tercerintento.
- 19.6: Hernán guardó clase60 con23ausentes; listado cambió Pendiente→Sin presentes. Estado Programada porque reloj15h y clase18h. No se adelantó reloj para simular finalizada.
- 19.7 NO EJECUTADO: formulario de cierre muestra esperado268000, no270500. No se ingresó contado271000, no se cerró ni rectificó.
- Noche ejecutada una vez: falló al buscar caja CERRADA de Sandra; rollback. Caja36 sigue ABIERTA.
- Continuidad: no cambiar importes ni borrar por consola para forzar el guion. Antes de cada día20–22 se ejecutarán su único reloj y su única simulación. Si rechazan apertura, no se ejecutan casos ni rutinas reales de ese día.
