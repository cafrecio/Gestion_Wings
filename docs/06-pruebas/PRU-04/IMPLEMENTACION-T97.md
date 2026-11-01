# T97 — Anulación de transferencia completa

Entrega local de Codex CAB, 01/11/2026. **Implementación publicada en `7e72b0d4`; entregada a Claude con T97 `a_verificar`.** La lógica debe revisarla Claude antes del cierre. Sin despliegue ni acceso al sitio de prueba o producción.

## Resultado

Desde cualquiera de las dos filas originales de Cashflow se puede anular la transferencia entera, con motivo obligatorio. Se conservan los originales y se agregan dos inversos vinculados, por el mismo importe, cuenta, fecha y clasificación histórica. La cabecera guarda motivo, administrador y momento. Las cuatro filas quedan solo con Ver; para corregir una transferencia hay que anularla y crear otra.

El circuito está separado en `AnulacionTransferenciaCashflowService`; no se cambiaron `esDirecto()`, `puedeCorregirse()` ni `ClasificacionSubrubros::exigirSinTraspaso`. Tampoco se modificaron CajaService, configuración de Caja, el catálogo de ajustes ni las expectativas de T61/T27. T104 y T106 quedan fuera de esta entrega.

**Aspecto aprobado por Carlos:** «Esta OK ahora», después de señalar la superposición móvil. La propuesta original quedó en `c7903931` y el ajuste en `d5b95d44`. Antes de ese OK no se cambiaron vistas/CSS reales. [Propuesta histórica](APROBAR-T97.html) · [Seis pantallas implementadas a 1366/375](VER-T97.html).

La barra móvil implementada deja el contador arriba y Transferir/Nuevo debajo, alineados a la derecha: 8 px entre filas y botones de 96 × 32, separados por 8 px. El CSS nuevo está limitado a `#cashflow-acciones` bajo 640 px; ese ID aparece solo en Cashflow.

## Ejemplos comprobados, con ambas cuentas

### Transferencia simple y captura real (corte 7e72b0d4, anterior a T106)

| Estado | Efectivo | Banco Nación | Total del club | Tarjetas ingreso/egreso |
|---|---:|---:|---:|---:|
| Antes | $600.000,00 | $100.000,00 | $700.000,00 | $0 / $0 |
| Transferir $300.000 | $300.000,00 | $400.000,00 | $700.000,00 | $300.000 / $300.000 |
| Anular por la otra fila | $600.000,00 | $100.000,00 | $700.000,00 | $0 / $0 |

El generador final ejecutó GET Cashflow → GET confirmación → **POST real de anulación por la otra pata** → GET Cashflow, en `wings_testing_codex`. No preparó el estado anulado a mano. [Prueba de captura](evidencia-t97/final/CapturasFinalesT97Test.php) · [Salida 1/13](evidencia-t97/final/renderizado.txt) · [Origen y hashes](evidencia-t97/final/origen.json).

### Mes anterior con negocio que no debe desaparecer

Reloj de prueba: 15/11/2026. Además del capital anterior hay un ingreso bancario del 10/10 de $12.000,50 y un gasto en Efectivo del 03/11 de $5.000,25. Se transfiere $300.000 con fecha 20/10 y se anula en noviembre.

| Corte después de anular | Efectivo | Banco Nación | Disponible | Ingreso / egreso de negocio | Resultado |
|---|---:|---:|---:|---:|---:|
| Octubre | $600.000,00 | $112.000,50 | $712.000,50 | $12.000,50 / $0 | $12.000,50 |
| Noviembre | $594.999,75 | $112.000,50 | $707.000,25 | $0 / $5.000,25 | −$5.000,25 |

Se compararon las estructuras de ReporteMensual antes de transferir y después de anular: ingresos, egresos, resultado, resultado global, aportes, retiros, disponible, cajas, deuda y por pagar, para ambos meses. Los GET de Reportes e Inicio dieron esos mismos saldos; Inicio usa noviembre. Cashflow verificó los totales y la cantidad de filas con todas las cuentas, solo Efectivo y solo Banco, en ambos meses. Historial verificó entradas/salidas por las dos ventanas de fechas. La prueba adicional con $300.000,75 verifica también los centavos de ambos inversos.

**Diferencia entre el pedido y los filtros existentes:** Historial no tiene filtro de cuenta; Reportes filtra mes/deporte y muestra las cuentas; Inicio usa el mes actual y no ofrece filtro de mes/cuenta. Se usaron sus caminos reales y se filtró por cuenta donde existe, sin agregar filtros. Esta entrega no decidió T106: en el corte 7e72b0d4 una transferencia vigente sumaba ingreso y egreso en Cashflow/Historial; la anulada se compensaba hasta dejar sus tarjetas como antes de existir. Durante la espera, el trabajo T106 cambió en la carpeta la política de totales globales para excluir TRASPASO, conservando el detalle por cuenta; también ajustó dos expectativas de la prueba T97. Esos cambios ajenos se preservan y quedan fuera de esta verificación aislada. Las cifras de esta tabla y las capturas identifican expresamente su corte anterior.

## Reglas y pruebas permanentes

18 métodos nuevos, **31 casos ejecutados con proveedores**, en los dos archivos `AnulacionTransferenciasT97Test.php` y `AnulacionTransferenciasConcurrentesT97Test.php`. Todos verifican cuentas en centavos enteros, incluyendo los rechazos sin escrituras. El soporte crea datos ficticios y prohíbe usar una base no descartable. Todas las ejecuciones de esta entrega se hicieron en `wings_testing_codex`.

| Regla | Prueba (`test_…`) | Contraprueba retirada |
|---|---|---|
| Cualquiera de las patas anula el par; cuenta/importe/fecha originales | `cualquier_pata_anula_el_par_y_conserva_motivo_autor_y_fecha` (dos entradas, mes anterior y centavos) | 08 fecha, 09 cuenta, 10 signo |
| Motivo obligatorio, texto de hasta 500, espacios recortados | `motivo_invalido_rechazado_sin_escribir` (seis entradas), `otro_admin_puede_anular_incluso_si_se_desactivo_una_cuenta` (límite 500), `motivo_de_espacios_unicode_tambien_se_rechaza_en_el_servicio` | 02 obligatorio, 03 máximo |
| Guardar motivo, autor, fecha/hora y marcas originales | `cualquier_pata_anula_el_par_y_conserva_motivo_autor_y_fecha`; `motivo_se_conserva_completo_y_se_muestra_escapado`; `motivo_cero_se_muestra_en_el_listado` | 12 marca, 13 motivo, 14 autor |
| Solo ADMIN activo; no exige ser creador ni reactivar cuentas | `permisos_get_post_y_servicio_sin_escrituras`; `otro_admin_puede_anular_incluso_si_se_desactivo_una_cuenta` | 01 autorización del servicio |
| Cabecera vigente y exactamente dos originales íntegros | `par_inconsistente_se_rechaza_sin_reparar_ni_escribir` (ocho corrupciones: pata ausente/extra, cabecera ausente, importe/fecha/cuenta/referencia/marca alterados) | 05 cantidad, 06 importe, 07 fecha |
| Un directo no entra al circuito; contraasiento no es origen | `un_directo_no_puede_entrar_en_el_circuito_de_transferencias`; `originales_y_contras_no_admiten_correccion_individual_ni_reanular_contra` | 04 referencia del origen |
| No editar patas, ni admitir traspasos sueltos por el circuito común | `patas_vigentes_siguen_fuera_del_circuito_de_directos_y_traspasos_sueltos`; `originales_y_contras_no_admiten_correccion_individual_ni_reanular_contra` | T61/T27 conservadas sin cambiar expectativas |
| Dos inversos y auditoría atómicos | `fallo_en_segundo_inverso_revierte_primer_inverso_marcas_y_cabecera` | 16 transacción |
| Repetición no duplica ni cambia auditoría; reenvío de creación no revive | `repetir_anulacion_por_la_otra_pata_no_duplica_ni_cambia_auditoria`; `reenvio_de_creacion_no_revive_la_transferencia_anulada` | 18 reconocimiento de anulada |
| Dos conexiones reales se serializan antes de escribir | `dos_conexiones_anulando_el_mismo_par_no_duplican_ni_escriben_sin_candado` | 17 candados |
| Referencia propia no se confunde con pagos o sueldos | `referencias_no_se_confunden_con_pago_ni_sueldos`; prueba de ambas patas | 15 referencia del inverso |
| Conservar las tres fotos históricas aun si cambia el catálogo | `inversos_conservan_fotos_aunque_cambie_el_catalogo` | 11 fotos |
| Mes anterior y tarjetas restauradas con filtros reales | `cashflow_historial_reportes_inicio_y_filtros_restauran_mes_original` | 08–11 alteran las dimensiones que sostienen la compensación |
| Originales anulados e inversos muestran motivo y solo Ver | `originales_y_contras_no_admiten_correccion_individual_ni_reanular_contra`; `motivo_cero_se_muestra_en_el_listado` | 19 acciones |

Los pedidos directos cubren GET/POST de anulación, edición y eliminación por los circuitos existentes, usuarios OPERATIVO/PROFESOR, ADMIN inactivo, anónimo, IDs ausentes y origen indebido. El servicio vuelve a exigir ADMIN activo aunque se lo invoque sin pasar por la ruta.

### Contrapruebas

**19/19 alteraciones detectadas**, salida 1 con fallos de aserción; fuentes restauradas byte por byte después de cada caso. [Resultado y hashes](evidencia-t97/mutaciones/resultado.json) · [Ejecutor reproducible](evidencia-t97/probar-mutaciones.py) · [Lote](evidencia-t97/lote-contrapruebas.txt). Se leyeron los 19 resultados: no fueron errores de sintaxis ni pruebas ausentes.

Alcance preciso: quitar el máximo 500 produce un error de columna y 500 en vez del rechazo 422 esperado; retirar los candados llega a INSERT y bloquea allí por el índice único, en vez de detenerse en FOR UPDATE; quitar el reconocimiento de cabecera anulada todavía es frenado por las marcas de las patas, pero devuelve «necesitan revisión» en vez de «ya está anulada». Esos casos detectan pérdida de validación, orden de bloqueo o respuesta correcta; **no demuestran que todos los resguardos restantes permitan duplicar datos**.

### Dos conexiones de verdad

Conexiones MariaDB **15540 y 15541**: primera anulación completa dentro de una transacción todavía sin confirmar; la segunda intenta la otra pata, espera y vence en FOR UPDATE. **Cero escrituras del segundo proceso** y fotografía idéntica al estado visible anterior. Tras confirmar la primera, el reintento de la segunda recibe «La transferencia ya está anulada.», sin cambiar motivo, autor, fecha ni movimientos. Final: dos originales, dos inversos y cuentas $600.000/$100.000. [Evidencia](evidencia-t97/concurrencia.json).

## Lectores y marcador elegido

[Auditoría detallada de los 16 archivos iniciales y productores externos](evidencia-t97/AUDITORIA-LECTORES.md). Se buscó primero en codebase-memory y se contrastaron cuerpos, filtros y productores en archivos reales. El grafo indicó cobertura no disponible o parcial y metadatos cambiados; dos intentos de reindexado encontraron una indexación ajena activa. Se completó el barrido literal de `referencia_tipo`/`contra_origen_id` en app, resources, routes, config, bootstrap, seeders y scripts; no se considera exhaustivo el grafo.

Los inversos usan `ANULACION_TRANSFERENCIA`, ID de cabecera y contraorigen único. Se mantiene `TRANSFERENCIA` en los originales, sin vínculo operativo. No se reciclan las referencias de caja, pago, liquidación o corrección de directo. El observador recibe explícitamente las tres fotos del original. Reportes, Historial, Recibos y Sueldos no necesitaron cambios. La colisión deliberada entre ID de cabecera e ID de Pago dio N/D, no un medio de pago inventado.

## Manual, contrato y migración

Se actualizaron `corregir-movimiento-cashflow`, `transferir-entre-cuentas` y `consultar-cashflow`, con sección de anulación, motivo, estados, rol, fechas, repetición, cuentas desactivadas, rechazo de par inconsistente y captura real. Las tres fichas se regeneraron **solo por sus nombres** con `evidencia-t12-t13/etapa-c/generar-fichas.py`. [Comprobación de condiciones y avisos](evidencia-t97/REVISION-MANUAL.md).

El contrato V5 ya incluye el circuito. La migración `2026_11_01_020000_agregar_anulacion_transferencias_cashflow.php` agrega tres campos anulables en cabecera: `anulada_at`, `anulo_admin_id` (FK a users) y `motivo_anulacion` de 500 caracteres. No transforma transferencias existentes. Su `down()` conserva la auditoría financiera, como la migración de T61; la guía de despliegue lo advierte. No se ejecutó contra la base del club ni contra servidores.

Los contadores de ESTADO/PLAN/CHECKLIST distinguen métodos permanentes de casos con proveedores. Deben reflejar el árbol de cada commit, excluyendo pruebas ajenas todavía sin publicar. No se tocó el catálogo `tabla-de-pruebas/`, que es solo lectura para Codex.

## Carpeta compartida y corte de evidencia

Al iniciar la suite completa, HEAD tenía 1.371 métodos; T97 agrega 18 y había otros 2 de T105 sin publicar. El checkout contaba 1.391 por dos métodos T105 todavía sin publicar. El commit propio 7e72b0d4 y la copia de la completa tuvieron 1.389 métodos, excluyendo esos dos; T102/T105 se publicaron después en a648ec9e. T94 agrega **22**, no 24: los otros dos eran T105. Se corrigió esa atribución al contar cada archivo y el árbol de HEAD por separado.

Durante la completa, T102 agregó una ruta de niveles en `routes/web.php` y doce líneas limitadas a `.niveles-actions` en el CSS compartido. Esos archivos contienen también los cambios propios. Se frenó su preparación para commit y se consultó a Carlos, conforme al pedido de no mezclar cambios ajenos. **Carlos autorizó: «Sí, publicar solo T97 y preservar lo ajeno».** Se preparan solo los cambios propios en el índice, conservando todo en la carpeta. Los avances ajenos de T94/T102 en el tablero se conservan. No se atribuyen ni se incorporan como implementación de T97.

Las capturas y sus ocho hashes corresponden al corte previo a esos agregados T102. La comparación inicial de las ocho fuentes pasó; el control posterior detectó correctamente la diferencia en los compartidos. Los agregados visibles son de Niveles y no cambian los selectores de Cashflow. Las copias propias de CSS/rutas, preparadas sobre HEAD sin tomar T102, coinciden con las huellas de las fuentes capturadas. La integridad de imágenes/enlaces se verifica aparte; no se presenta ese control como una nueva certificación de todo el código compartido. Los resultados de la completa y sus inventarios deben dejar explícito qué fuentes cambiaron durante la ejecución.

## Verificaciones

| Verificación | Resultado y salida |
|---|---|
| T97 + T61 + T27 y sus concurrencias | **65 aprobadas, 774 aserciones, 105,20 s, salida 0** — [salida](evidencia-t97/dirigidas.txt) |
| Mutaciones T97 | **19/19 detectadas**, fuentes restauradas — [resultado](evidencia-t97/mutaciones/resultado.json) |
| Captura real final | **1 aprobada, 13 aserciones, 13,33 s, salida 0** — [salida](evidencia-t97/final/renderizado.txt) |
| Build | **Correcto**, 41,26 s — [salida](evidencia-t97/build.txt) |
| Manual | **Copia aislada: 59 páginas, 0 problemas**, salida 0 — [salida final](evidencia-t97/manual-aislado.txt). Primera lectura en carpeta compartida: 6 problemas solo en administrar-grupos ajena — [salida histórica](evidencia-t97/manual.txt) |
| Suite rápida | **1.266 aprobadas, 6 omitidas, 9.482 aserciones, 363,83 s, salida 0** — [salida](evidencia-t97/suite-rapida.txt); 674 fuentes idénticas antes/después |
| Suite completa | **1.401 aprobadas, 6 omitidas, 11.287 aserciones, 1.300,66 s, salida 0** — [salida](evidencia-t97/suite-completa.txt). Copia aislada del corte propio, 1.389 métodos y **669 fuentes idénticas antes/después** — [inventario final](evidencia-t97/final/despues-completa-aislada.json) |
| Regresión tras T106 | **65 aprobadas, 774 aserciones, 97,36 s, salida 0** — [salida](evidencia-t97/dirigidas-integracion-t106.txt) · [cuatro fuentes restauradas](evidencia-t97/final/integracion-t106.json) |
| Sintaxis y vistas | **11 PHP sin errores**; `view:cache` y `view:clear` correctos — [sintaxis](evidencia-t97/sintaxis.txt) · [vistas](evidencia-t97/vistas.txt) |
| Integridad final | 7 JPEG válidos, 17 enlaces de galería, 8 fuentes coinciden con el commit 7e72b0d4 (normalización CRLF/LF), barra sin superposición y copia del manual idéntica — [resultado por commit](evidencia-t97/final/verificacion-commit.json). Diff, enlaces e integridad revisados para la entrega |

La ejecución inicial corrigió errores del soporte: conflicto entre traits y una expectativa propia de HTTP 422 cuando el formulario existente redirige 302 con error; otra aserción inicial incluía un movimiento de negocio en la lista de acciones de transferencia. Se preservan salidas iniciales para trazabilidad. No se cambiaron las expectativas de T61/T27 para hacerlas pasar.

## Propuestas para la tabla de Carlos y Claude (sin editarla)

Nuevos casos, códigos definitivos a asignar por Claude:

| Propuesta | Pasos / texto para el caso | Resultado esperado |
|---|---|---|
| T97-A — Anular desde ambas patas | Como dueño, transferir $300.000 de Efectivo ($600.000) a Banco ($100.000). Abrir Anular desde cualquiera de las filas, completar motivo y confirmar. Repetir en otro escenario entrando desde la otra fila | Ambas cuentas vuelven a $600.000/$100.000; dos originales y dos inversos vinculados, fecha original y motivo visibles; solo Ver |
| T97-B — Motivo y repetición | Enviar vacío, espacios y 501 caracteres; luego un motivo válido. Confirmar desde otra pestaña | Rechazos sin escrituras; motivo hasta 500; segundo envío no duplica ni cambia auditoría |
| T97-C — Mes anterior | Transferir el 20/10 y anular en noviembre, con ingreso bancario octubre $12.000,50 y gasto noviembre $5.000,25 | Octubre y noviembre iguales al estado sin transferencia; saldos por cuenta y tarjetas de Cashflow/Historial compensados; Reportes/Inicio no pierden negocio |
| T97-D — No corregir filas | Pedir edición/eliminación de originales e inversos y anulación desde un inverso; intentar traspaso suelto | Rechazados; ninguna cuenta cambia |
| T97-E — Dos dueños | Un segundo dueño anula la transferencia del primero; dos pestañas confirman a la vez | Un solo par inverso; autor y momento de quien confirmó; segunda decisión rechazada sin escribir |
| T97-F — Operación inconsistente | Sobre una copia descartable, alterar una pata del par antes de anular | Aviso «Los movimientos de la transferencia necesitan revisión. No se anuló la operación.»; no repara ni modifica datos |

## Comprobado, límites y siguiente paso

**Comprobado:** comportamiento en MariaDB descartable por pedidos reales, cifras, dos conexiones, contrapruebas y renderizado de la aplicación. Login de control y seis capturas reales revisados en Chrome; iframe 375, contenido 360 y sin desborde horizontal; el espacio restante es el scrollbar. Los HTML guardados vienen de Laravel, no de una maqueta. El visor estático escucha solo en 127.0.0.1 y bloquea enviar formularios; no inicia otra aplicación ni conecta bases.

**No certificado:** despliegue, datos del club, condiciones de servidores y verificación independiente de Claude. La aprobación de Carlos corresponde al aspecto; no se usa como aprobación de la lógica. La lectura de fuentes no sustituye la ejecución y el índice no certifica exhaustividad.

**Siguiente:** Claude revisa la entrega, ya en tablero `a_verificar`, `tiene=Claude`. Carlos no necesita aprobar nuevamente el mismo diseño. Claude revisa código, cifras y comportamiento; T97 no se autocierra.

Actualización de exclusividad: el primer arranque aislado comenzó sin otras pruebas (03:37:11), pero T102 lanzó otra a las 03:37:23. Se detuvo únicamente T97, conservando la salida en `suite-completa-interrumpida-por-solapamiento.txt`. Se reintentó después de 120 segundos libres, con vigilancia de procesos y detención de la propia ante cualquier nuevo solapamiento. Ninguna corrida interrumpida se contó como verde. Implementación `7e72b0d4` confirmada en origin/main; no es despliegue.

Corte posterior observado en solo lectura: `a648ec9e` publicó T102/T105. T106 después modificó el cálculo global de tarjetas en `CashflowWebController` y las expectativas previas a anular en `AnulacionTransferenciasT97Test`; Codex no modifica ni incluye ese trabajo. La comprobación completa propia usa la copia aislada anterior (1.389 métodos), cuya implementación coincide con `7e72b0d4`. No se presenta como verificación de T106 ni de T102/T105.

Coordinación por pedido de Carlos: reserva solicitada directamente y confirmada por Gemini y ambas sesiones de Claude; [confirmaciones](evidencia-t97/reserva-confirmaciones.json). Tras corregir un falso positivo del monitor (su propio PHPUnit pasaba por cmd.exe), comenzó la corrida exclusiva final a las 04:11:46. El primer registro automático se conserva con su corrección explícita en `solapamientos.jsonl`; no fue una prueba ajena. El ejecutor reconoce ahora la cadena PHP/cmd y la ruta de configuración propia.

Expectativas posteriores, ajenas a T97: `2145888b` cambió en T61 los totales globales previos a anular de $320.000/$307.000 a $20.000/$7.000, y en una prueba de T97 de $312.000,50/$300.000 a $12.000,50/$0. Lo hizo porque T106 excluye la transferencia vigente del total global. T97 no cambió esas expectativas para aprobar su entrega: sus 65 dirigidas y su completa aislada usan las anteriores. La regresión adicional de T97/T61/T27 con los dos controladores y las expectativas publicadas por T106 pasó 65/774 (97,36 s, salida 0), sin tocar el código compartido ni dar por verificada toda T106. Se restauraron después los cuatro archivos de la copia aislada.

Las tres fichas de T97 se regeneraron otra vez para actualizar las líneas desplazadas por T106; se preservó íntegro el agregado de T106 en `admin-consultar-cashflow.md`. El cambio de cada ficha es solo una línea de referencias de fuente.

**Resultado final de la reserva:** confirmada por las tres sesiones, completa y regresión terminadas, estado LIBERADA en `docs/00-estado/RESERVA-SUITE-T97.md`. Las primeras salidas interrumpidas se conservan como tales; el verde corresponde únicamente a la corrida completa final y a la regresión identificadas arriba. No hay despliegue.
