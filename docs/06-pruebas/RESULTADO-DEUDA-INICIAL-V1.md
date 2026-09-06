# Resultado de deuda inicial V1

Fecha: 06/09/2026. Responsable: Codex CAB. Base verificada: entorno local,
host 127.0.0.1, `wings_test`. Rama `main`, commit inicial `2145886`.

## Estado: pausado antes de importar

La orden del chat limita la tarea a los pasos 1–4 del importador. La orden
guardada en `docs/00-estado/ORDEN-CODEX-DEUDA-INICIAL.md` exige antes un Paso 0
bloqueante con `CatalogosSeeder`. No se ejecutó ese seeder ni el importador.

Al leer completo `CatalogosSeeder::seedRubrosYSubrubros()` se comprobó que las
definiciones de los rubros Cuotas y Sueldos no incluyen `es_reservado_sistema`.
La línea 120 asigna ese atributo con valor predeterminado `false` y luego guarda
el rubro. En la base ambos tienen actualmente `es_reservado_sistema = 1`:
ejecutar el Paso 0 como está escrito quitaría esa protección. Este efecto se
identificó por lectura del cuerpo del código; no se provocó sobre la base.

Pendiente de decisión: continuar únicamente con los pasos 1–4 del chat dejando
catálogos para otra tarea, o resolver primero el Paso 0 y su seeder. No se
corrigió lógica durante esta prueba.

## Lectura inicial de la base

| Tabla | Filas |
|---|---:|
| alumnos | 60 |
| alumno_planes | 60 |
| deuda_cuotas | 0 |
| pago_deuda_cuota | 0 |
| pagos | 0 |
| users | 7 |
| rubros | 2 |

Las dos filas de rubros se leyeron con `LIMIT 5`: Cuotas, INGRESO, reservado 1;
Sueldos, EGRESO, reservado 1. No se exportaron usuarios ni datos personales.

## Pasos solicitados

| Paso | Resultado | Esperado | Resultado observado |
|---|---|---|---|
| 1. Validar ambos Excel | NO SE PUDO | 81 válidas y ocho filas rechazadas | No ejecutado por la pausa previa |
| 2. Importar | NO SE PUDO | 81 pendientes, $2.997.000, 48 alumnos con deuda | No ejecutado; lectura inicial: cero deudas |
| 3. Repetir importación | NO SE PUDO | Rechazo sin duplicar | No ejecutado |
| 4. Revertir, validar y recargar | NO SE PUDO | 0 → validación 81 → carga 81 | No ejecutado |

No hubo escrituras de carga ni errores del importador: todavía no se lo invocó.
Los archivos Excel y su generador no se modificaron.

## Verificaciones de cierre de la pausa

- `php artisan test`: 119 pruebas aprobadas, 689 aserciones, sobre `wings_testing`.
- `php artisan view:cache` y `php artisan view:clear`: aprobados.
- Diff de vistas y CSS: vacío.
- Sin cambios de código ni commit; solo registro documental del freno.
