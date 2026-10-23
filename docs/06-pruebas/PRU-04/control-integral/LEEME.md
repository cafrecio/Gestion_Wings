# Seguimiento de pruebas por caso — versión 5

Corte: 23/10/2026, Codex CyE. Entrega para revisión independiente; no aprobación de producción.

[Dashboard para Carlos](CONTROL-INTEGRAL.html) · [Informe para otras IA](informes-ia/SEGUIMIENTO-CASOS.md) · [Datos por caso](seguimiento-casos.json)

## Qué responde

- Estado general y números por módulo: total, con prueba, sin prueba, OK, Fallo, Reparado, parcial y con plan.
- Línea de tiempo de 60 jornadas, con acceso directo; una página por día.
- Módulos: diez casos por página, código y nombre, estado, intentos, resultados, método, esperado y jornada propuesta.
- Casos: condiciones, criterios, historial, fecha ficticia y real, pantalla, probador/rol, versión/base, esperado, obtenido, límites y evidencia.
- Jornadas: casos ensayados, operaciones vinculadas, comparaciones numéricas, controles técnicos y planes de acción.
- Solución, fecha de implementación, commit, fecha de retest y probador separados; si no existen, pendientes.

La unidad es el **caso**. Las situaciones del cuento no sustituyen casos. El [relato operativo](relatos/DIA-04.html) se conserva como anexo. Las versiones 3/4 ocultaban códigos o mezclaban ambientes en su recuento: este contrato reemplaza esas decisiones.

## Recuento y reglas

331 casos en 20 módulos. Día 4: **15 casos parciales**, **18 asociaciones caso–operación**, 16 operaciones originales, una pendiente de vincular. **316 sin prueba consolidada; cero completos OK, Fallo o Reparado.** Tres planes: discrepancia de Cobrar, presentación del alta y cobertura del pago parcial. No son tres fallas completas demostradas.

Selección inicial de salida: 69 casos, 15 con evidencia parcial en test y cero completos aprobados. La selección espera revisión; no cambia requisitos decididos por Carlos.

- Una asociación única `(caso, ejecución)` cuenta un intento; varias letras no lo multiplican.
- Una operación puede comprobar varios casos. Operaciones e intentos no se suman.
- Los 14 Coincide y 2 Parcial del cuento no son 14 casos completos OK.
- OK exige evidencia de todas las condiciones y consecuencias del caso.
- Reparado exige falla previa, implementación identificada y nueva prueba OK en test, con fecha, probador y evidencia. Plan o commit solos no acreditan reparación.
- Otro chat de la misma cuenta no constituye revisión independiente. Revisor, identidad y evidencia permanecen pendientes.

Colores: verde OK; amarillo atención; azul plan definido; verde oscuro reparación probada; gris sin consolidar. El azul describe un plan, no aprobación ni solución aplicada.

## Ambiente y alcance

Aceptación: `https://test.gestionar-te.com.ar`, `wingstest`, versión `56379295a33782858ce9c986e200f13382d61cc4`; ejecución/contraste 22/10, día ficticio 13/10. Esta reorganización **no reejecutó Wings**, cobros, reloj ni base.

Los 41 métodos del 23/10 en `wings_testing_codex` quedan como apoyo: **no suman casos aprobados ni intentos en test**. El viejo total 21 incluía seis casos con apoyo aislado únicamente: ACC-007/009/010/011 y CAJ-027/029. Los anexos técnicos conservan ese universo mixto con advertencia; para aceptación usar este dashboard.

Sin prueba consolidada significa sin ejecución vinculada aquí, no una conclusión sobre todo el historial. Días 1–3: antecedentes pendientes de conciliar por caso. Días 5–60: sin resultados inventados. Las fechas de la línea de tiempo son calendario ficticio proyectado, no prueba de avance del reloj. La agenda es propuesta, no ejecución ni probador asignado.

D4-T03 y D4-T15 se vincularon retrospectivamente a ALU-002.e (efectos del alta en Cobranza y Reportes), con justificación y fecha. D4-T12, asistencia histórica reciente, espera vínculo justificado o propuesta de nuevo caso a Carlos/Claude; no se fuerza a clase de hoy ni carga extrema. Catálogo y dudas de Carlos: solo lectura.

## Óptimos y mediciones

Cada indicador identifica esperado/óptimo, obtenido y origen: guion, variante o invariante de conciliación posterior. Un invariante posterior no es un saldo inicial reconstruido. Un importe correcto no aprueba los demás criterios.

Rendimiento: llamada CLI de 35,83 ms, 29 consultas/11,37 ms SQL, 101 alumnos. Óptimo comparable no fijado; sin tiempo visible ni carga. ≤1.000 ms visible se propone como criterio de trabajo, pendiente de ratificación y medición; no se compara contra el tiempo CLI.

Faltan seguridad negativa en el sitio, tiempos visibles, volumen/concurrencia, ciclo mensual y liquidaciones. Los controles por caso exponen límites. Ocultar menús no demuestra seguridad.

## Actualización

- `control-integral.json`: evidencia base y criterios fuente con huella.
- `devolucion-humana.json`: explicación de operaciones, enlazada por ID.
- `seguimiento-config.json`: vínculos justificados, metas, planes y agenda propuesta.
- `seguimiento_casos.py`: proyección por caso, ambientes y recuentos.
- `dashboard_casos.py`: entrada, módulos, casos, jornadas y Markdown para IA.
- JSON derivados y HTML se generan; no editarlos a mano.

```text
python docs/06-pruebas/PRU-04/control-integral/generar.py
python docs/06-pruebas/PRU-04/control-integral/validar.py
```

La importación actual está delimitada al Día 4. Para nuevas jornadas extenderla con registros reales de cada fecha, ambiente, rol y versión; nunca reutilizar resultados de otro día. Fijar esperado y datos previos antes de operar. No reemplazar el guion histórico ni inventar capturas previas.

Validación: 331 fuentes intactas, enlaces/fragmentos, recuentos, exclusión de apoyo aislado, colores/reparación, generación determinista y lógica JavaScript real. Sin suite de Wings por reorganizar documentación.

Sin nueva revisión visual ni medición de apertura móvil. Se conserva la restricción previa de observar `file://`; no se elude mediante otro navegador o servidor. La validación lógica no acredita render. El peso se registra en `integridad-entregables.json`; no es latencia medida.

## Producción

Conservar PRU-05 y requisitos del tablero: candidata identificada, operaciones y permisos aplicables comprobados en test, dinero conciliado, ciclo mensual, revisión independiente, suite completa en su ambiente propio antes del despliegue y respaldo/preflight. Los ensayos aislados ayudan a detectar regresiones; no reemplazan la aceptación real.

Sin despliegue. T14 sigue en curso.
