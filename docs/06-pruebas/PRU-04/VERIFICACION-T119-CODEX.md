# T119 — Control independiente de Codex CAB

01/11/2026. Código de Claude `502101f0`, controlado en una copia limpia de `main`
`6be8f76d`, rama `codex/control-t119-t82`. Sin desplegar ni corregir producto.
Base descartable exclusiva: `wings_testing_codex`; catálogos y personas ficticias.
Los cambios sin publicar de la carpeta compartida no integran este corte.

**Resultado: devuelta a Claude.** Las tres defensas del servidor pasan, pero el
caso 5 no se completa desde el formulario de Caja (H1, medio). El barrido adicional
reproduce otro control que sigue solo en pantalla: subrubro desactivado (H2).

## Hallazgos comprobados

### H1 — Medio: Editar de Caja no ofrece el medio actual desactivado

`app/Http/Controllers/CajaWebController.php:530`,
`resources/views/caja/editar.blade.php:84`.

Recorrido ejecutado por HTTP contra la aplicación: operativa registra un gasto de
$100 en un banco activo; el dueño desactiva ese banco mediante su ruta web. La
operativa abre Editar. Responde 200, pero `tiposCaja` excluye el banco actual; el
`select required` queda en «Seleccionar...», sin ninguna opción seleccionada.
No puede corregir solo el importe conservando el medio desde esa pantalla.
Elegir otra cuenta cambia dónde queda registrado el gasto.

El PUT directo con el mismo banco desactivado **sí** permite modificar el importe
a $80; cambiarlo por otro medio inactivo se rechaza. Se ejecutó ese par de PUT
con operativa y dueño. El fallo es la preparación del formulario, no la excepción
del servidor. La misma preparación se utiliza para el dueño (leído; su GET no se
ejecutó en esta prueba). Cashflow sí ofrece y conserva el medio actual inactivo.

[Selector devuelto por Wings, sin sesión ni tokens](evidencia-t119-codex/selector-caja-inactivo.html).
La contraprueba independiente termina roja exactamente en la presencia del banco
en `tiposCaja`. La consulta que omite el medio ya existía en el padre de
`502101f0`: no se atribuye su introducción a T119; impide cumplir el caso pedido.

### H2 — Adicional, medio: un subrubro desactivado se sigue cargando por envío directo

`app/Http/Controllers/CajaWebController.php:1149` lo excluye de los formularios;
`app/Services/CajaService.php:661` valida reserva, permiso y afectación de caja,
pero no su estado activo. La validación web del subrubro es solo existencia
(`CajaWebController.php:474` y `:1118`).

Ejecutado: el dueño desactiva un subrubro manual permitido para OPERATIVO mediante
`PATCH /rubros/{rubro}/subrubros/{id}/toggle-activo`. Los GET de Nuevo movimiento
de la operativa y Agregar del dueño ya no lo ofrecen. Enviar su ID al POST
correspondiente registra igualmente un movimiento de $100 por cada actor.
Se comprobaron las filas nuevas y el subrubro que conservaron. No exige API,
suplantación ni acceso de profesor: llegan operativa y dueño autenticados con
sus rutas normales. Un formulario abierto antes de desactivar conserva ese ID.

Es el control adicional solicitado, no un incumplimiento de `MedioDePagoActivo`:
el medio de pago utilizado estaba activo. No se reparó ni se amplió la regla de
negocio del [contrato de catálogos](../../02-contratos/Wings-Contrato-Catalogos-Contables-V1.md),
que exige filtrar las cargas nuevas. `CajaService.php` no cambió en `502101f0`.

## Siete casos y flujo real

| Caso | Ejecutado y resultado |
|---|---|
| 1. Operativa cancela serie, JSON/formulario | 403 JSON y error de sesión exacto en formulario; ni la clase pedida ni su hermana se cancelan. |
| 2. Operativa una clase / dueño serie | Ambos recorridos positivos pasan; se comprueban las clases afectadas y no afectadas. |
| 3. Medio inactivo | Las siete entradas rechazan sin cambiar pagos, imputaciones, deuda, movimientos, transferencias, liquidación ni caja. Mensaje exacto donde el rol está autorizado. |
| 4. Medio inexistente | Mismas entradas, sin 500. «El valor elegido para medio de pago no existe.»; transferencias usan «cuenta de salida» / «cuenta de destino». |
| 5. Corrección de medio desactivado | PUT de Caja y Cashflow conservan el medio y rechazan otro inactivo. GET Cashflow pasa; GET Caja falla: H1. |
| 6. Cambiar deporte al editar | Operativa y dueño: 422 y mensaje exacto pedido; fila completa del alumno y sus planes sin cambios, incluidos deporte y grupo. |
| 7. Editar normalmente / otra ficha | Operativa y dueño editan el nombre sin cambiar deporte y dan de alta otra ficha del mismo DNI en otro deporte. Ficha original conservada. |

La matriz propia ejecutó **42 envíos**: siete entradas × dos medios inválidos ×
tres roles. El profesor recibe 403 en todas. La operativa recibe 403 en Cashflow,
transferencias y pago de liquidación. El dueño que envía Nuevo movimiento de
operativa es redirigido a Cashflow con «No se registró nada», como exige T59;
no se le atribuye un 422 que su flujo no utiliza. Agregar en caja sí valida el
medio para ambos actores autorizados. Se compararon las filas completas de las
tablas financieras antes y después de cada envío.

**Caja real a mitad del turno:** movimiento cargado por POST con banco activo,
desactivación por PATCH del dueño, cierre por operativa, verificación del
movimiento por el dueño y validación de caja por POST: pasan. La caja termina
VALIDADA y Cashflow recibe una sola fila por −$100 en el mismo banco inactivo.

**Vaciar un medio inactivo:** transferencia rechazada sin escrituras; el dueño
lo reactiva desde Tipos de caja y vuelve a enviar: pasa, deja su saldo en cero.
Hay un paso previo obligatorio; no queda bloqueado sin salida. No puede vaciarlo
mientras conserve el estado inactivo. No se cambió esa decisión.

## Barrido adicional y límites

Grafo `wings-control-t119-t82`: búsquedas de símbolos, trazado y lectura de
cuerpos; `check_index_coverage` informó `coverage_unavailable / metadata_changed`.
Por eso se leyeron las fuentes reales y se contrastaron las rutas y referencias
con búsquedas de texto; el grafo no certifica la ausencia de otros caminos.

El barrido de referencias de medios en controladores y servicios encontró las
ocho ubicaciones protegidas por la regla (nueve usos, contando salida/destino).
También se leyó configuración/apertura del cajón: exige medio activo y el cajón
configurado no se desactiva por su ruta web. Primera carga delega en su servicio;
no se encontró selección de medio para movimientos nuevos en ese recorrido.

Hay escrituras legítimas que conservan un medio histórico desactivado:
integración de caja validada, corrección con el mismo medio y asientos de
anulación de transferencias/cobros. No se los cuenta como eludir la regla de un
movimiento nuevo. La integración quedó ejecutada; las anulaciones se leyeron,
sin repetir aquí cada recorrido ni probar carreras entre desactivar y guardar.

Los cinco FormRequest antiguos con `exists:tipos_caja,id` pertenecen a
controladores de API. `bootstrap/app.php:17` no registra `routes/api.php`;
`route:list --json` ejecutado devuelve **cero rutas `api/*`**. No se reactivó.
El barrido es acotado a estos caminos web, no una auditoría total de permisos.

## Evidencia y ejecución

- [Pruebas originales T119](evidencia-t119-codex/t119-originales.txt): 8 aprobadas,
  58 aserciones, 26,74 s, salida 0.
- [Control propio reproducible](evidencia-t119-codex/ControlT119CodexTest.php) y
  [salida final](evidencia-t119-codex/control-independiente.txt): 7 aprobadas,
  1 fallida por H1, 231 aserciones, 26,12 s, salida 1. La prueba H2 confirma el
  comportamiento observado; su verde no lo declara correcto.
- [Primer intento propio](evidencia-t119-codex/control-intento-inicial.txt):
  además de H1 fallaban dos supuestos del control sobre la ruta del dueño.
  Se corrigió la prueba conforme a T59; no eran dos defectos de Wings.
- [Rutas alcanzables y middleware](evidencia-t119-codex/rutas.json).
- [Primera rápida sin cierre](evidencia-t119-codex/suite-rapida-interrumpida.txt):
  perdió su sesión de ejecución durante el cambio de entorno del chat; no se
  contabiliza como aprobada ni se le inventa código de salida.
- [Suite rápida repetida](evidencia-t119-codex/suite-rapida.txt): **1.351 aprobadas,
  6 omitidas, 9.885 aserciones, 430,10 s, salida 0**. No se ejecutó la completa.
- [Huellas de las ocho fuentes revisadas](evidencia-t119-codex/fuentes.json):
  producto sin cambios respecto del corte limpio. No son huellas de claves.

Sintaxis de las ocho fuentes de T119 y del control propio: sin errores. No se
modificaron código de producto, vistas, CSS, manual ni catálogo. La evidencia HTML de T34
regenerada por la rápida no forma parte de la entrega. Sin revisión visual en
navegador: los formularios de este control fueron respuestas HTTP reales de
Wings. Las 13 mutaciones de Claude fueron leídas, no reejecutadas ni heredadas
como prueba propia.
