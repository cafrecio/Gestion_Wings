# A14 / A27 / A53 — HECHO (Codex), a revisar

07/10/2026, Codex CyE; código base ed30215 y antecedente documental 1dcf02f.
Diseño aprobado por Carlos al revisar el visor ANTES/DESPUÉS:
«Ahi vi el archivo antes y despues y parece que esta bien. Doy el OK».
Entrega con el commit que contiene este informe y pase separado a verificación.
No cerrado por el autor; sin despliegue.

[Propuesta y rutas exactas](PROPUESTA-A14-A27-A53.md)
· [Comparación](evidencia/a14-a27-a53/visor-comparacion.html).

| Defecto | Causa comprobada | Cambio y comprobación del autor | Estado |
|---|---|---|---|
| A14 | Acciones al final del formulario; errores en desplazamiento normal | Barra fija móvil en alta/edición Alumnos/Profesores; resumen pegado bajo encabezado. Cartel cambia con input/change: ocho errores, siete al completar Nombre, oculto al completar los obligatorios, un error al vaciar Nombre. DNI rechazado queda pendiente si cambia | A verificar; falta asignar verificador |
| A27 | Acciones después de todos los campos; fuera del marco 375×667 | Registrar/Cancelar visibles en ADMIN y OPERATIVO; espacio al pie para alcanzar Observaciones. Resumen móvil de errores reales; Observaciones conserva carácter opcional | A verificar; falta asignar verificador |
| A53 | ds-truncate impone una línea y elipsis | Grupos/selector permiten varias líneas en celular. Tres tarifas millonarias y nombre de grupo largo completos, con tamaño de letra conservado | A verificar; falta asignar verificador |

## Alcance real

Ocho vistas existentes: alumnos/create, alumnos/edit, alumnos/_errores, profesores/create,
profesores/edit, caja/movimiento, grupos/index y caja/cobrar-cuota. Componente nuevo
components/ds/mobile-errors. CSS opt-in en app.css; el pie de escritorio conserva borde y
alineación. JS externo form-errors, importado por app; reemplaza los dos manejadores antiguos
de resumen de alumnos-form y conserva la protección de salida sin guardar.

No cambian reglas globales filtros-actions (40 consumidores), ds-truncate (5) ni ds-btn.
[Inventario contrastado](evidencia/a14-a27-a53/alcance-antes.json). Clases y clases-form sin
cambios; PROFESOR controlado por HTTP y en pantalla, 20 casillas Presente.

El resumen retira errores nativos resueltos y avisos inline viejos del mismo campo. Los
rechazos que requieren servidor cambian a pendiente; no se afirma unicidad ni se consulta
la base en cada tecla. El JavaScript nuevo del cartel no está incrustado en HTML; siguen
existiendo los scripts anteriores de liquidación en profesores/_form, fuera de este cambio.

## Evidencia y límites

34 ANTES y 40 DESPUÉS: marco original de 375 CSS ×667, login primero; JPEG móvil 376×667
por el margen de medio píxel. Escritorio: raster 1280×613, no la altura 900 solicitada al
control de viewport. Se reconstruyó el ANTES con Wings original y se reaplicó el parche local;
se descartaron capturas grises/recortadas y redirecciones por la caja sintética fechada ayer.
Las imágenes finales se inspeccionaron mediante hojas de contacto y originales de detalle.

Dieciséis pares de escritorio inspeccionados: grilla y posición de acciones conservadas;
seis pares idénticos como raster, restantes con diferencias de puntero/representación
tipográfica. [Diferencias de archivo](evidencia/a14-a27-a53/comparacion-desktop.json).
No se certifica identidad píxel a píxel ni comportamiento del teclado virtual de un celular real.

T1: estas imágenes cubren Grupos índice, selector y Movimiento. No cubren los recortes de
Caja índice, cobro individual ni Clases; no se cierra T1. Grupos show no cambió, y su evidencia
anterior sigue en el control independiente del 06/10.

## Verificaciones del autor

- Escenario sintético: 1 aprobada /11 aserciones /6,38 s, fuera de suite permanente.
- Suite completa: 489 aprobadas /2 omitidas, 3924 aserciones, 257,64 s; solo wings_testing_codex.
  [Salida completa](evidencia/a14-a27-a53/suite-completa.txt).
- Build correcto; nueve vistas sin errores de sintaxis; cache/clear de vistas correctos.
- Sin servidor remoto, producción ni datos del club. No se agregan pruebas permanentes.

Siguiente: asignar verificador independiente. Tablero: A14, A27 y A53 en a_verificar,
hizo Codex, tiene nadie. DEFECTOS md/html: HECHO (Codex), a revisar, 07/10. El autor no
cierra ninguno; T1 conserva lo pendiente fuera del alcance de esta entrega.
