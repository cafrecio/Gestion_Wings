# T97 — Comprobación del manual por su autor

Codex CAB, 01/11/2026. No es la revisión independiente requerida para cerrar la tarea: esa corresponde a Claude. Se aplica el punto 6 de REVISOR-MANUAL al texto agregado, buscando las condiciones directamente en servicio, controlador, rutas y vistas antes de contrastar las fichas.

Páginas: `resources/manual/admin/corregir-movimiento-cashflow.md`, `transferir-entre-cuentas.md` y `consultar-cashflow.md`. Fichas regeneradas por nombre, sin regenerar las de otros módulos. Las afirmaciones de creación anteriores se contrastaron con `TransferenciaCashflowService::validarDatos/guardar` y `TransferenciaCashflowWebController::store`.

| Afirmación nueva | Condición contrastada | Ejecución |
|---|---|---|
| Solo dueño activo | Grupo de rutas ADMIN y usuario activo; servicio vuelve a exigir `isAdmin()` e `isActivo()` | GET/POST con ADMIN, OPERATIVO, PROFESOR, inactivo y anónimo; invocación directa del servicio |
| Puede ser de otro dueño | No hay comparación entre creador de cabecera y anulador | Segundo ADMIN anula y queda como autor |
| Desde cualquiera de las dos filas originales | `desdeMovimiento` exige `REF_TRANSFERENCIA` y contraorigen nulo; ambas comparten cabecera | Proveedor ejecuta cada pata; contras y directo reciben 403 |
| Motivo obligatorio, sin solo espacios, máximo 500 | Recorte Unicode y `required|string|max:500`; input required/maxlength | Vacío, espacios, null, array, número, 501 rechazados; 500 aceptado; Unicode rechazado por servicio; `0` visible |
| Volver no confirma | Enlace GET al mes original; Anular es botón submit del POST con CSRF | GET de confirmación no genera inversos; POST genera dos |
| No editar una pata | `esDirecto` y `puedeCorregirse` conservados; no se exime TRASPASO | Pedidos directos de edición/eliminación rechazados para originales y contras; T61/T27 verdes sin cambiar expectativas |
| Operación entera y sin reparación | Cabecera, dos cuentas, exactamente dos patas, cuenta/fecha/monto y marcas íntegros | Ocho corrupciones rechazadas sin cambios; foto de tablas igual |
| Conserva fecha, incluso de otro mes | Inverso toma `fecha` de cada original, no `now()` | Octubre anulado en noviembre; Reportes de ambos meses iguales al estado sin transferencia |
| Cuenta desactivada puede deshacerse | Bloquea y exige existencia de ambas cuentas; no exige su flag activo al anular | Cuenta desactivada, segundo dueño y motivo de 500 aceptados |
| Originales más dos inversos | Dos creaciones dentro de transacción; originales marcados, sin borrarlos | Recuento exacto y rollback al fallar el segundo inverso |
| Motivo y solo Ver en las cuatro filas | Cabecera consultada en lote; Anular solo al original y cabecera vigentes; salida escapada | Acciones exactas `['Ver']`; texto hostil escapado; motivo `0` mostrado |
| Repetir no duplica | Cabecera bloqueada con `anulada_at` no nulo rechaza | Repetición, dos conexiones y reenvío de creación; auditoría idéntica |
| Restituye ambas cuentas y tarjetas | Misma cuenta/subrubro/fotos, signo opuesto | Ejemplo 600.000/100.000, decimales y escenario con ingreso/gasto de negocio |
| Cargas antiguas independientes no se enlazan | UI exige cabecera válida; no hay migración que invente vínculos | Lectura de condición y migración; no se fabricó un historial real antiguo |

## Rechazos y avisos del circuito nuevo

| Mensaje / estado | Ubicación | Tratamiento en las páginas |
|---|---|---|
| «Indicá el motivo de la anulación.» | Validador del servicio | Sí: Transferir → ¿Qué hago si no deja anular?; Corregir → ¿Cómo anulo una transferencia entre cuentas? |
| Máximo 500 / tipo texto | Validador y traducciones genéricas | Sí: Transferir → paso 3 de Anular y ¿Qué hago si no deja anular?; se explica cómo acortar, sin inventar un mensaje literal distinto |
| «La transferencia ya está anulada.» | GET del controlador y servicio | Sí: mismas dos secciones; se explica no volver a cargar |
| «Los movimientos de la transferencia necesitan revisión. No se anuló la operación.» | `inconsistente()` | Sí: Transferir → ¿Qué hago si no deja anular?, indicando no reparar y pedir revisión |
| «Transferencia #… anulada. Los originales y los dos asientos en contra se conservan.» | POST del controlador | Sí en contenido: Transferir → párrafo posterior a captura; la ficha no marca una cita literal completa que no existe |
| Solo se anula desde original / 403 | `desdeMovimiento` | No como texto de uso diario: solo accesible forzando el URL de un directo o contraasiento; la página explica desde qué filas entrar y que un contra no se anula |
| ID inexistente / 404 | `findOrFail` | No como recorrido normal: requiere un enlace inexistente; no es una condición de negocio omitida |

Las fichas conservan «Leído en el código; no ejecutado en pantalla» para los rechazos que no se recorrieron en Chrome. Las pruebas HTTP y de servicio están en la tabla anterior; no se presentan como acciones manuales de navegador.

La captura nueva es la confirmación real con ADMIN Laura Gómez y menú del dueño. Se generó desde el GET de Laravel antes del POST, no de una página inventada; coincide con el paso que ilustra. [Galería real](../VER-T97.html) y [salida del renderizado](final/renderizado.txt).

## Verificador automático

`php scripts/manual/verificar-manual.php`: **59 páginas, 6 problemas**, todos en `administrar-grupos.md`, archivo T94 con cambios ajenos. Las tres páginas propias de T97 no aparecen entre los problemas. [Salida completa](manual.txt). No se arregló ni se incluyó la página ajena.

No se encontraron contradicciones graves o medias en el texto nuevo por esta comprobación del autor. Queda revisión independiente de Claude; esta conclusión no certifica el resto del manual ni cierra T97.

Reverificación final en copia aislada HEAD + T97: **59 páginas, 0 problemas, salida 0**. [Salida](manual-aislado.txt). La página ajena se tomó como estaba publicada en HEAD; Codex no la corrigió.
