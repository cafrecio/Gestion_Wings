# Primer control de invariancia — corrección del verificador propio

`original-produccion.txt` conserva la primera ejecución: el script original completó sus pasos en 90 s y salió 0 pese al error de columna de liquidaciones. La envoltura salió 81 porque mi comparación de invariancia enfrentaba listas leídas de JSON con tuplas recién construidas y marcaba todas las tablas como diferentes. **Esa comparación es inválida: no demuestra cambios en las bases vivas.**

Se corrigió solo `auditar-datos.py` para construir listas desde el inicio. Antes de repetir se comprobó la limpieza: carpetas/bases/usuario/puerto/temporales ausentes. La repetición `original-produccion-control-corregido` es la que acredita o refuta invariancia; no se cambia la salida original. Los scripts de producto no se editaron.

En `independiente-produccion.txt`, la comparación de filas completó (34 tablas iguales y sesiones diferentes del respaldo nocturno), pero la primera suma independiente se cortó por otro error del control propio: MariaDB devolvió el COUNT como cadena y Python lo comparaba con un entero. No se usa esa salida como prueba de los importes. Se convirtió explícitamente ese contador a entero; la corrida `independiente-produccion-datos` completa esa parte sin repetir el arranque/APP_KEY ya comprobados.
