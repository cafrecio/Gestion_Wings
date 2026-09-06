# Carga inicial de deuda desde Excel

Este procedimiento carga solamente las cuotas que el club informa como impagas.
No crea alumnos, pagos, imputaciones ni movimientos de caja.

## Archivos entregados

- `PLANTILLA-CARGA-DEUDA-INICIAL.xlsx`: archivo vacío para completar. La primera
  hoja se puede importar directamente; la segunda explica las reglas.
- `EJEMPLO-CARGA-DEUDA-INICIAL.xlsx`: caso válido, una hoja con errores de
  referencia y los resultados esperados de cada corrida. El caso válido usa tres
  alumnos de la primera carga local y se validó sin escribir deudas.

## Formato obligatorio

La primera fila de la hoja a importar es el encabezado exacto:

```text
DNI | deporte | monto | mmYYYY | monto | mmYYYY | ...
```

Cada fila contiene un solo alumno y uno o más pares de monto/período. El período
es texto de seis dígitos, por ejemplo `052025`; el monto es numérico y mayor que
cero. Las filas vacías se ignoran. Una fila incompleta, un período inválido, un
alumno inexistente o una combinación repetida de DNI + deporte rechaza el archivo.

## Validar antes de cargar

```powershell
php artisan wings:importar-deuda-inicial C:\ruta\deuda-inicial.xlsx --solo-validar
```

La validación lee todo el archivo y muestra todos los problemas con sus filas de
Excel. Si informa un error, no escribe nada.

## Cargar

Solo después de revisar la validación aprobada:

```powershell
php artisan wings:importar-deuda-inicial C:\ruta\deuda-inicial.xlsx
```

Cada deuda queda pendiente, con monto pagado en cero. Si ya existe una deuda para
el mismo alumno y período, se rechaza el archivo completo: no sobrescribe ni suma.
Por eso una segunda ejecución del mismo Excel se rechaza de forma explícita.

## Rehacer una carga

Conservar siempre el Excel original. Si se detecta un error luego de cargar:

1. Revisar primero el archivo original con `--solo-validar`.
2. Ejecutar la reversión usando exactamente ese archivo:

   ```powershell
   php artisan wings:importar-deuda-inicial C:\ruta\deuda-inicial.xlsx --revertir
   ```

3. El comando solo retira cada deuda si sigue pendiente, con monto pagado cero,
   sin imputaciones y con el monto original idéntico. Si una sola no cumple, no
   borra ninguna.
4. Volver a ejecutar `--solo-validar`: debe aprobar y mostrar todas las deudas
   del archivo como listas para importar, sin conflictos existentes. Eso confirma
   que para esos alumnos y períodos la base quedó en cero antes del reintento.

No ejecutar `DELETE` manuales. La reversión no toca alumnos, planes, pagos,
imputaciones, movimientos de caja ni usuarios.
