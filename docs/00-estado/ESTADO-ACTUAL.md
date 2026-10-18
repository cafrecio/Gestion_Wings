# Wings — Estado actual

## T75 — Acciones móviles a derecha, entrega local 18/10/2026

CSS/clases únicamente: regla general de tarjeta y barras propias; escritorio conservado.
101 fuentes revisadas (94 renderizadas más paginación Laravel), 99 casos a 360/375/1366,
297 mediciones por fase y 428 PNG; 37 casos móviles cambiados, cero desbordes finales.
Las cuatro grillas y Detalle de caja aprobado permanecen sin diferencias.
Suite final propia: 821 aprobadas, 2 omitidas, 0 fallidas, 7593 aserciones, 908.56 s, salida 0. Primera corrida con dos fallos y repetición aislada 2/20 conservadas en informe.
Carlos verifica T75; 19 casos fuera de tarjeta/barra conservan su disposición para su decisión.
[Informe, inventario y galería](../06-pruebas/PRU-04/IMPLEMENTACION-T75.md). Sin despliegue ni autocierre.

## T38 — Reactivar genera la cuota del mes, cerrada por Gemini 18/10/2026

DU-12 y decisión de Carlos del 17/10 (plazo de alta para el reingreso). Reactivar desde la
ficha o el listado crea la cuota del mes en curso con el porcentaje de reingreso del día, salvo
que ya exista; sin plan para el mes no reactiva. La cuota guarda `fecha_reingreso` y tiene el
plazo de un alta en estados y en la mora. El cobro que reactiva a un inactivo completa lo mismo.
18 pruebas nuevas; verificada de forma independiente por Gemini CAB sobre `wings_testing_gemini`:
código, puertas de entrada, importes, plazos, mora, cobro a inactivos, Chrome a 1366 y marco 360px
(12 capturas) y suite completa (**821 aprobadas, 2 omitidas, 0 fallidas, 7593 aserciones, 852,91 s**).
[Implementación](../06-pruebas/PRU-04/IMPLEMENTACION-T38.md) · [Verificación Gemini](../06-pruebas/PRU-04/VERIFICACION-T38-GEMINI.md).

## T36 — Revisión persistente de caja, cerrada por Claude 18/10/2026

Claude verificó reglas y registró aprobación visual de Carlos («visualmente están bien»).
Las cifras siguientes conservan el corte de implementación del 17/10.

El tilde se guarda con autor, fecha y huella del movimiento. Solo ADMIN activo revisa
una caja CERRADA con conteo; validar controla todos los renglones de Detalle en el
servicio, también desde Resumen. Si faltan, informa cuántos y lleva a Detalle.
Caja vacía e idempotencia de las ya VALIDADA conservadas; cancelados no suman en Cashflow.
Carlos: «Destildar solo los movimientos que cambien». Rechazar conserva marcas vigentes;
editar o cancelar invalida las afectadas, incluso en actualizaciones masivas.
Las dos vistas sin JavaScript incrustado: quedan 8 bloques en 8 vistas y 8 manejadores.
68 pruebas dirigidas/449 aserciones y 9 de cancelación/concurrencia real/125 aserciones,
aprobadas. Chrome comprobó persistencia, ambas puertas de validación, modales, error
de red y confirmación de eliminar. Suite completa: 818 aprobadas, 2 omitidas y 1 fallo
en `ReingresoCuotaT38Test`, trabajo paralelo de Claude en esa corrida: esperaba 4000.00 y obtuvo 7000.00.
7584 aserciones, 1332,77 s, salida 1. Tras cbc851e de Claude, 50 pruebas integradas aprobadas/314 aserciones (34,81 s), incluido ese caso. Sin reparación propia de T38 ni certificación de suite completa verde.
[Informe y capturas](../06-pruebas/PRU-04/IMPLEMENTACION-T36.md). Sin despliegue ni autocierre.

## T37 — Motivo obligatorio de rechazo, cerrada por Claude 17/10/2026

Las pruebas siguientes conservan el corte de implementación; el aspecto lo verificó Carlos.

Controlador web y servicio exigen texto no vacío de hasta 500 caracteres; solo
espacios es vacío. Sin mínimo adicional ni cambios a rechazos históricos.
Los dos modales requieren motivo y muestran errores al inicio de la pantalla.
Ocho pruebas nuevas; T37/A25 y regresiones: 102 aprobadas/853 aserciones. Cuatro recorridos Chrome
con dueño sintético, Detalle/Resumen y anchos 1366/360, pasaron; 14 capturas reales.
Suite completa propia: 784 aprobadas/2 omitidas, 7395 aserciones, 965,22 s; [informe y límites](../06-pruebas/PRU-04/IMPLEMENTACION-T37.md).
Seguimiento visual pedido por Carlos el 17/10: corregido localmente el desborde de Detalle.
Información adaptable como Resumen; contador y botones envuelven; tabla con desplazamiento
interno en celular. 48 variantes comprobadas (ADMIN/OPERATIVO, cuatro estados, con/sin
movimientos, 360/375/1366); ocho comprobaciones adicionales de acciones al deslizar a 375.
[Capturas y alcance](../06-pruebas/PRU-04/DETALLE-CAJA-MOBILE.md). Visual aprobado por Carlos, 17/10: «Bien ambas imagenes!!».
Tabla con scroll y «Modal OK» ya aceptados; también aprobadas las dos imágenes a 375
de botones 2 × 2 a la derecha y Resumen/Volver a la derecha, entregadas en `4b050ac`.
Verificador visual: Carlos (§6a); no requiere otra IA. Seis visitas 360/375/1366 documentadas.
Suite posterior a la alineación: 784 aprobadas/2 omitidas, 7395 aserciones, 901,37 s,
base y archivos temporales exclusivos de Codex; salida 0.
Suite del primer ajuste: 783 aprobadas, 2 omitidas, 1 fallida por Excel temporal ausente
en PrimeraCargaExcelTest; T37 aprobada. Repetición dirigida: 40 aprobadas, 2 fallidas por
archivos temporales de Primera carga. Esa corrida no fue verde; se conserva en el informe.
Con carpeta temporal exclusiva, repetición de los mismos casos: 42 aprobadas/714 aserciones,
52,19 s. Sin modificar código. El fallo de la corrida completa sigue registrado.
Claude verificó y cerró T37 en `870cbb4`; visual aprobado por Carlos. Sin despliegue.
Alcance resuelto por Carlos (17/10): «Entregar según el prompt; Inicio queda pendiente». CAJ-029.c exige el motivo en Inicio,
pero el prompt de T37 pide verlo en Resumen y solo autoriza las dos vistas de caja.
`OperativoDashboardController` pasa un conteo de rechazadas y `operativo/dashboard`
muestra ese aviso, sin motivos. Inicio queda pendiente por esa decisión; T37 se entrega con el alcance del prompt.

## B11 — Implementada localmente, a verificar por Claude, 16/10/2026

Motor diario del mes vigente, configuración, cobro por mes con capital primero,
recibo, perdón/reactivación y saldos incorporados; porcentaje inicial cero.
Carlos autorizó «Actualizar los conteos y seguir»: solo dos cantidades de
`CatalogosSeederTest` cambian (11/18 → 12/19); las demás pruebas viejas se conservan.
Suite completa propia: **761 aprobadas, 2 omitidas, 7214 aserciones, 1106,83 s**.
35 pruebas nuevas y 31 capturas reales en escritorio y marcos de 360/375.
[Entrega, criterios y evidencia](../06-pruebas/PRU-04/IMPLEMENTACION-B11.md).
Carlos aprobó el visor celular («En celular esta bien»), Configuración escritorio
(«OK, aprobado») y Cobrar escritorio («OK»); ver capturas y alcance en la entrega.
Pendiente revisión independiente de lógica y del resto visual; sin publicación ni despliegue.
`52e6bdf` y sus descendientes permanecen locales por decisión de Carlos.

## T11 — Comprobación local de Codex, 16/10/2026

Application > Manifest sin errores ni avisos; dos screenshots reconocidos y CDP
sin errores de manifest/instalabilidad. Tres pruebas propias pasan (63 aserciones).
Se corrigió un enlace 404 del informe; la observación y la evidencia están en
[Tercera vuelta](../06-pruebas/PRU-04/VERIFICACION-T11-CODEX-TERCERA.md).
Durante la revisión entró `512f375`: Claude registra el cierre por instalación y
prueba de Carlos en su teléfono. Codex conserva ese cierre; no comprobó la prueba
física ni leyó el chat privado citado. Suite propia: 724 aprobadas, 2 omitidas,
1 fallo documental (727 pruebas descubiertas, 728 en árbol); contadores corregidos
después y guardián aislado 1/7 verde. No se afirma suite completa verde.
Sin despliegue.

## T3 — Verificada por Codex y cerrada, 16/10/2026

Codex CyE comparó `f4e6436` con su anterior: 70 defectos, cero cambios abierto/cerrado
y cuerpos intactos; 56 encabezados distintos. Copia aislada: cinco archivos escritos,
tres ediciones manuales detectadas y CRLF verde. Se devuelve porque cambiar solo
el paso de A6 altera el cierre de 08/10 a 16/10 y el guardián sigue verde.
Suite propia: 723 aprobadas/2 omitidas, 6929 aserciones, 887.55 s; los diez casos
de PerdonConcurrenteT33Test pasan en la suite completa. Siguen siendo 725 pruebas.
[Verificación y evidencia](../06-pruebas/PRU-04/VERIFICACION-T3-CODEX.md).
Sin corrección del generador, producto, datos del club, reloj o despliegue.
**Corregido por Claude el 16/10 (segunda vuelta):** cada tarea guarda su fecha de cierre aparte
(`cerrado_el`); cambiar otro dato no la mueve. Lo cerrado conserva el encabezado que tenía antes
de T3, con sus matices. Dos pruebas nuevas reproducen el caso de A6: 727 pruebas.
**Segunda verificación de Codex CyE, 16/10:** A6 corregido, 67 encabezados/renglones
cerrados restaurados; 66 fechas coinciden y B15 se respalda con SERVIDOR.md y 031bdec.
Devuelta a Claude: cerrar B11 emite `Undefined array key 2` en lib.php:660, aunque
guarda. Corregir el grupo opcional y probar el cierre HTML sin avisos. Suite propia
725 aprobadas/2 omitidas, 6947 aserciones, 681.68 s; diez casos concurrentes T33 verdes.
Sin arreglos ni despliegue. [Segunda vuelta](../06-pruebas/PRU-04/VERIFICACION-T3-CODEX.md#segunda-vuelta).
**Tercera vuelta local de Codex, 16/10:** seis pruebas/27 aserciones pasan; la nueva
detecta el aviso con la biblioteca anterior. Cerrar B11 solo en copia genera HTML
sin avisos y con sello/casilla correctos; origen intacto. Codex actualizó los cuatro
contadores a 728; guardián aislado 1/7 verde. El commit de Claude `c6ef9b6` conserva
los hashes comprobados; los cuatro guardianes finales pasan 12/55. T3 cerrada por
Codex, sin presentar como verde la suite inicial con el fallo de conteo.
[Evidencia](../06-pruebas/PRU-04/VERIFICACION-T3-CODEX.md#tercera-vuelta).

## T2 — Retiro de los importadores viejos de la primera carga, 13/10/2026

Gemini CyE retiró los importadores de consola antiguos (`wings:importar-padron`, `wings:importar-deuda-inicial` y `wings:exportar-padron`), sus servicios asociados (`CargaSaldoInicialPadronService`, `CargaDeudaInicialExcelService`) y sus 16 pruebas asociadas, cumpliendo la decisión P0/P1 de PRU-02 tras consolidarse el importador web único (`/sistema/primera-carga`). Documentación histórica, órdenes y planillas archivadas en `docs/99-archivo/`. Suite en 722 pruebas. [Informe de entrega](../06-pruebas/PRU-04/IMPLEMENTACION-T2.md).

## T33 — Perdón de inscripción y reactivación, 13/10/2026

Codex CyE implementa DU-25/DU-58: ADMIN condona inscripción pendiente y reactiva
cuotas/inscripción perdonadas desde ficha, con motivo 10–500 y ambas decisiones conservadas.
Pagos parciales y original intactos; sin caja/cashflow/recibos ni matrícula reactivada.
31 pruebas nuevas, capturas reales 1366/marco360 y login. Suite local 736/2 verde, 7001 aserciones.
Reporte anterior conserva cuota histórica, pero usa perdón actual de inscripción;
se informa sin modificar esa regla. [Entrega T33](../06-pruebas/PRU-04/IMPLEMENTACION-T33.md).
Sin despliegue. Claude verifica código y pantalla antes de cerrar.

## T31 — El que debe la inscripción también es moroso o deudor, 13/10/2026

Gemini CyE implementó la regla de Carlos (12/10 y 13/10/2026):
- La inscripción impaga convierte al alumno en MOROSO o DEUDOR según su fecha de ingreso (`fecha_alta`).
- Plazo propio del alumno nuevo (`dias_plazo_alta`, 7 días por defecto) o gracia general (`dias_gracia_cobranza`, 10 días): lo que venza más tarde.
- Jerarquía de cobranza: `DEUDOR > MOROSO > EN_PLAZO > AL_DIA`.
- Sincronización exacta entre Cobranza y el buscador de Cobrar caja.
- 18 pruebas nuevas en `CobranzaInscripcionEstadoTest`. Suite en 738 pruebas (incluyendo T33).
- [Informe de entrega](../06-pruebas/PRU-04/IMPLEMENTACION-T31.md).

## T27 — Implementación local y comprobación, 12/10/2026

[Propuesta e inventario](../06-pruebas/PRU-04/PROPUESTA-T27.md): Cashflow como entrada
única, con origen/acciones por fila y acceso separado a cajas sin validar. Maqueta
renderizada por Laravel con datos ficticios: 32 PNG, marco360/login, 20 visitas sin fallos en etapa 1.
Carlos aprobó la propuesta visual el 12/10: «Sí, apruebo la propuesta visual».
Decidió editar el mismo registro conservando historial del valor anterior y el nuevo.
Eliminar conserva el original y crea un asiento en contra.
Motivo opcional para editar y eliminar, por decisión de Carlos.
Se puede corregir cualquier mes anterior, sin límite.
Caja validada y liquidación pagada: solo consulta, sin edición ni eliminación desde T27.
Liquidación sin pagar: Editar lleva al flujo existente, conservando sus restricciones.
Carlos decidió actualizar el reporte del mes original, con historial. Contraasiento con fecha original.
Reglas respondidas; etapa 2 implementada localmente, suite completa verde.
Editor de directos, contraasiento único, historial antes/después y control de versión/reenvío.
18 pruebas propias pasan: 16 de recorrido y 2 con conexiones MariaDB reales.
53 capturas finales, marco360/login, 36 visitas sin fallos. [Informe](../06-pruebas/PRU-04/IMPLEMENTACION-T27.md).
Contradicción corregida: Historial mezclaba egresos operativos positivos
con egresos directos negativos y pintaba estos últimos como ingresos. La captura publicada
`evidencia-t27/capturas/historial-actual-escritorio.png` muestra internet +$23,500.75 en verde.
Se normaliza para cumplir la consistencia de Historial con Cashflow/Inicio/Reportes en T27.
Hallazgos del relevamiento: rechazarCaja acepta solo CERRADA, no VALIDADA;
liquidación pagada no se corrige. Además, el controlador limita edición de operativos
a su caja mientras el contrato limita por propietario solo el cierre; no se cambió.
Suite propia: 687 aprobadas, 2 omitidas; 6588 aserciones, 501,04 s en wings_testing_codex.
Carlos aprobó el resto de las capturas finales y señaló el importe grande desalineado en Historial móvil.
Se amplió solo la columna Monto: diez importes alineados en escritorio y marco360; cuatro visitas sin fallos.
Carlos aprobó Historial corregido: «Esta OK ahora» y «Sí, aprobado»; todas las capturas finales aprobadas.
T27 queda a verificar por Claude para reglas; no se cierra ni se despliega.

## T30 — Centavos y formato compartido, 12/10/2026

Codex CAB implementó la presentación `$48,000.50`, siempre dos decimales, en pantallas,
avisos, PDF nuevos y comunicaciones. Excel sigue numérico; gráficos y recibos emitidos
se conservan. Regla común PHP/JS, sin cambiar cálculos ni validaciones de T24.
T28 se publicó independiente primero (`e1e73d8`), T30 se aplica después.
Suite propia: 669 aprobadas/2 omitidas, 6395 aserciones; 671 pruebas.
[Inventario, capturas y límites](../06-pruebas/PRU-04/IMPLEMENTACION-T30.md).
Revisión visual 12/10: Carlos señaló porcentaje desbordado en la leyenda de Reportes;
porcentaje junto al rubro e importe debajo en celular. Nueva medición de la leyenda,
563 controles/0 fallas en la revisión, sin cambiar fórmulas. Capturas nuevas en
`evidencia-t30/revision-porcentajes-ultima/`; suite propia 669 aprobadas/2 omitidas,
6395 aserciones, 530,97 s.
Carlos aprobó todas las vistas: «El resto de las vistas OK» y «Esta OK» para la
leyenda corregida (12/10). Claude verificó la regla, corrigió con autorización
la portabilidad de las pruebas y cerró T30 en `b4fdd6e`; no pedir otra aprobación visual.
Sin despliegue por Codex.

## T29 — Buscador de Cobrar lista solo a deudores verificado y cerrado, 12/10/2026

Gemini CAB verificó la implementación de Claude (`8a8a6b8`) para T29 (D01-5 de la prueba por días, Carlos 12/10/2026).
Comprobaciones aprobadas:
1. Sin buscar: la lista trae únicamente a los alumnos con deuda y coincide exactamente con el encabezado («X alumnos con deuda pendiente» o «Sin deudas pendientes»).
2. A cualquier alumno al día o sin cuota generada se llega buscándolo por nombre, apellido o DNI y se le puede cobrar normalmente. Se comprobó el flujo completo de cobro real (`POST web.caja.pagar`).
3. El que debe solo inscripción aparece como deudor; cuota de mes futuro no aparece ni suma en el encabezado.
4. Alumno inactivo con deuda no aparece ni suma en el encabezado.
5. Permiso de profesor bloqueado (403). Dueño sin caja cobra directo.
6. Comparación rigurosa con Cobranza explicada: Cobrar lista a todo alumno con saldo pendiente (`saldo['total'] > 0`); Cobranza pestaña DEUDORES filtra a morosos/deudores con plazo vencido para gestión de cobro punitoria. El total adeudado en pesos y la suma de estados coinciden al 100%.
7. Pruebas: 11 pruebas pasando (113 aserciones) en `docs/06-pruebas/PRU-04/evidencia-t29/VerificacionT29GeminiTest.php`. Suite completa 664 tests aprobados.
8. Tablero: `cerrado`, `hizo=Claude`, `verifica=Gemini`. Informe en `docs/06-pruebas/PRU-04/VERIFICACION-T29.md`.

## T28 — Cashflow: vínculo directo por movimiento y columna Val. (segunda vuelta), 12/10/2026

Gemini CAB implementó la segunda vuelta de T28 tras la revisión de Claude y las decisiones de Carlos del 12/10/2026:
1. Migración en base de datos: columna `movimiento_operativo_id` (nullable) en `cashflow_movimientos`. Cada movimiento nuevo generado al validar una caja se vincula de forma directa y unívoca con su movimiento operativo de origen, resolviendo el alumno y quién registró sin mirar la observación ni depender de comparaciones de texto.
2. Tratamiento de movimientos preexistentes: renglones viejos sin ID guardado exigen coincidencia exacta de importe + subrubro + observación con un único movimiento de la caja. Se eliminó el segundo intento por importe y rubro; si no hay certeza exacta, se muestra la observación original y nunca se adivina un alumno.
3. Columna de validación:
   - Escritorio: columna «Val.» (50px) al final de la tabla con las 2 iniciales del administrador que validó (ej. `CB`, `VA`) y tooltip (`title`) con el nombre completo. En movimientos directos sin caja, la celda queda vacía (ni iniciales, ni guión, ni texto).
   - Celular (375px): en la tarjeta, línea inferior con `Registró: <nombre>` a la izquierda y badge compacto `Val: <iniciales>` a la derecha. En directos, solo `Registró: <nombre>`.
   - Cuidado de importes: ancho de Monto fijado en 125px holgados con soporte de decimales (`FormatoImporte::pesos`), evitando desbordes con importes grandes.
   - Desempate de iniciales: si dos administradores activos comparten iniciales (ej. Carlos Bonifacio y Carlos Benítez), desempata la segunda letra con la última del apellido (ej. `CB` y `CZ` o `CO`), según decisión de Carlos.
4. Pruebas: 5 pruebas permanentes en `tests/Feature/CashflowVinculoDirectoYValidadorTest.php` (100% PASS), más `CashflowDetalleAlumnoYUsuarioTest` (5/5 PASS) y `VerificacionT28ClaudeTest` (2/2 PASS con 40 cobros idénticos paginados).
5. Capturas v2 guardadas en `docs/06-pruebas/PRU-04/evidencia-t28/`: `cashflow-v2-escritorio.png`, `cashflow-v2-celular-375.png`, `login-control-v2-escritorio.png`, `login-control-v2-celular-375.png`.
6. Tablero: `a_verificar`, `hizo=Gemini`, `tiene=Claude` para verificación independiente de regla en código y base de datos. Informe en `docs/06-pruebas/PRU-04/IMPLEMENTACION-T28.md`.

## T26 — Historial de caja del dueño cerrado, 12/10/2026

Gemini CAB verificó la implementación de Claude (`f4a030e`) para T26 («El dueño debe ver todo, los límites no son para él», Carlos 11/10/2026).
Comprobaciones aprobadas:
1. El dueño puede ver cualquier fecha histórica con `desde` (arranca en últimos 90 días por defecto); la operativa queda confinada a los 90 días aun forzando fechas en la URL.
2. El dueño ve subrubros exclusivos de ADMIN en la grilla y en el select de filtros; la operativa solo ve los permitidos para su rol.
3. Movimientos cancelados: el dueño los ve marcados con `· Cancelado` y tachados (`line-through`); la operativa no los ve; en ambos roles los totales excluyen lo cancelado.
4. Cobro del dueño sin caja y anulado por camino real (`POST /caja/cobrar/{id}` y `POST /pagos/{id}/anular`): ambos renglones listados con alumna identificada, anulación resta como asiento en contra y total neto da $0,00.
5. Profesora recibe HTTP 403 Forbidden.
6. Filtros (texto, subrubro, tipo, fecha) y paginación plenamente funcionales.
7. Casos borde verificados: caja validada reflejada en cashflow con `REF_CAJA` no duplica filas en el historial; totales recalculan con exactitud ante cualquier filtro aplicado.
Suite de verificación propia `docs/06-pruebas/PRU-04/evidencia-t26/VerificacionT26Test.php` (8 pruebas, 103 aserciones, 100% verde). Suite completa 641/2 aprobadas en `wings_testing_gemini`.
Informe en `docs/06-pruebas/PRU-04/VERIFICACION-T26.md`. Tarea T26 CERRADA en el tablero (`verifica=Gemini`). Sin desplegar al sitio de prueba.

## T24 — aspecto aprobado por Carlos, regla a verificar, 12/10/2026

Codex CAB implementó el componente y el tratamiento común para 16 pantallas/modos,
incluyendo Rectificar de T23. Carlos decidió aceptar coma decimal, mostrar
`48,000.50` y recortar el tercer decimal con texto rojo y aviso de 4 segundos
sin bloquear el valor corregido. Pedidos directos de precisión inválida rechazados
sin escribir; límites, cero, campos protegidos y cálculos conservados.
Suite propia 641 aprobadas/2 omitidas, 6075 aserciones, 448,36 s; 643 pruebas.
Carlos devolvió las capturas del 11/10: revisados adelantado móvil, íconos,
desglose en dos líneas y acciones de Configuración/Liquidación. Aviso de importes
excluido del cierre general para conservar sus cuatro segundos y su reaparición.
Navegador revisado 1351 controles/100 PNG y assets fijos; 18/18 campos previos detectan
regresión sobre dd62c64. [Inventario, visor y límites](../06-pruebas/PRU-04/IMPLEMENTACION-T24.md).
Carlos aprobó la revisión `be5ad9a`: «Ahora OK» (12/10). Claude tiene la
verificación de la regla; T24 sigue a_verificar. Sin cierre ni deploy.
Trabajo ajeno de Historial/permisos fuera del commit propio; el controlador
compartido incorporó las líneas de T24 en 03aaf11 de T23.

## T16 — tercera vuelta aprobada por Codex, 10/10/2026

Corrección `b8ede87` comprobada sobre copia fija `aa33399`: 12 controles originales/225
aserciones, ocho adicionales/269 y ocho cruces propios/220 aprobados. APORTE y RETIRO,
edición/alta, ambos órdenes: espera real comprobada; uno guarda y otro rechaza claramente.
Los 16 pedidos terminan en 0,263–0,943 s; ocho guardados posteriores pasan; sin pares inválidos.
Suite completa 614 aprobadas/2 omitidas, 5.087 aserciones, 637,19 s en wings_testing_codex.
Carlos acotó a guardados desde Wings; cruces con comandos de consola fuera del alcance.
Altas automáticas de sueldo escriben NEGOCIO bajo rubro reservado; API sin rutas registradas.
Nombres nuevos en INGRESO quedan para el admin; DemoSeeder NULL, observación separada.
Tablero cerrado, hizo Claude/verifica Codex/tiene nadie; aspecto aprobado por Carlos.
Sin cambios de Wings, visita al sitio ni despliegue. [Informe](../06-pruebas/PRU-04/VERIFICACION-T16.md).

## T14 — Día 3 recorrido, lunes simulado 12/10, 10/10/2026

Por pantalla: Sandra canceló Patín Principiantes y Fútbol Principiantes 16–17;
admin canceló Patín Intermedias 17–18. Ver → Cancelar → motivo → Cancelar esta;
admin también ofrece Cancelar serie. No se usó. Agenda de hoy: 3 → 1 → 0.
Frena: canceladas del día no aparecen en Clases ni con Cancelada + 12/10,
comprobado como Sandra, admin y Lucía. Profesora no pudo abrir la cancelada de hoy.
Sin asistencia conserva 33; las dos páginas contienen solo fechas hasta 10/10.
En fichas canceladas vistas, Guardar deshabilitado. ADMIN ve Activar; no se pulsó.
Sandra: opción de reactivar sus dos canceladas no comprobada por quedar ocultas.
Inicio/Reportes: ingresos $164.000, deuda $2.010.000, por pagar $0; Cobranza $2.010.000.
Sueldos: Gaitán/Salinas/Quintana $0, Mariela $18.000/6 asistencias.
Molesta: bloqueos de ingreso de Sandra/admin, luego ingresaron; causa no investigada.
Decisión vigente de Carlos: suplencias y oferta de abrir caja en feriado son correctas.
Sin código/base, cobros, apertura, arreglos ni cambio del reloj. T14 sigue abierta;
Claude verifica [Día 3 y capturas](../06-pruebas/PRU-04/DIA-03.md).
[Día 2](../06-pruebas/PRU-04/DIA-02.md) y [Día 1](../06-pruebas/PRU-04/DIA-01.md) históricos.
Conservar cancelaciones, carga, pagos y asistencia para continuar el cuento.

## T15 — implementada, pendiente de verificación por Claude, 10/10/2026

La orden pedía la nueva404 también para `/alumnos/999999` en los cuatro perfiles.
Las rutas actuales exigen sesión y permiso: PROFESOR recibe403 y anónimo ingresa.
Carlos aclaró «Sí, conservar permisos y login»: nueva404 solo cuando corresponde.
Para500/503, Volver usa el acceso existente: al recuperarse el sistema reconoce
la sesión y redirige al inicio del rol; renderizar el error no consulta sesión ni base.
Aspecto solicitado: mismo sistema visual que403; no desplegar, Claude verifica.
Cuatro pantallas nuevas, Inicio en el menú y JSON preservado. Suite594/2,
4966 aserciones; 32 capturas de errores por rol, 134 del menú y cuatro con base
inaccesible/mantenimiento; control de login a360 y primera carga pendiente.
[Entrega y visor](../06-pruebas/PRU-02/IMPLEMENTACION-T15.md). Base del club intacta.

## B10 — verificado y devuelto a Claude, 10/10/2026

Contradicción comprobada por HTTP y filas: la orden pide rechazar alias con espacios;
`CbuOAlias::normalizar()` los elimina antes de validar y guarda otro identificador.
También transforma tabulación/salto; el rechazo de200 caracteres aparece en inglés.
Sin corregir código, vistas/CSS o tests. Pago completo, permisos y privacidad,
primera carga Excel y siete seeders comprobados en base/storage exclusivos de Codex.
Suite581/2,4650aserciones; build correcto. B10 devuelto a Claude;64/74 cerrados.
[Informe y evidencia](../06-pruebas/PRU-02/VERIFICACION-B10.md). Sin despliegue.

## A42 — discrepancia confirmada en fuente, 10/10/2026

La orden indica que falta la consulta inicial. La fuente actual ya ejecuta `actualizar()`
en `resources/js/alumnos-inscripcion.js:28`, desde `11623b6` (22/09), y el bundle también.
Carlos decidió «Sí, verificar y registrar»: comprobar los casos pedidos sin duplicar la
llamada. Sin cambios de aplicación ni JavaScript incrustado. Chrome real:13 controles
aprobados y filas ficticias conservadas; programas/resultados y ochoPNG revisados por otro agente.
Suite completa573 aprobadas/2omitidas,4616aserciones,559,63s correcta. [Entrega y evidencia](../06-pruebas/PRU-02/IMPLEMENTACION-A42.md).

## B12/A23 — plan validado, desarrollo autorizado 09/10/2026

Revisión Alumnos/Sueldos, 09/10: la matrícula solo guarda estado/deporte/nivel
actuales; no acredita activos al cierre de septiembre. La propuesta de Alumnos
separa matrícula de hoy y asistencia del mes por deporte/nivel de la clase;
no transforma clases sin registros en ausencias ni determina bajas automáticas. Consulta probada y propuesta
visual revisada en seis capturas reales; [entrega](../06-pruebas/B12-A23/PROPUESTA-ALUMNOS-2026-10-09.md).
Suite del prototipo543/2,4401aserciones,556,59s correcta. Carlos aprobó el aspecto («Esta OK»); integración habitual comprobada en la entrega posterior.
Sueldos implementado en desarrollo: reparto de cuota neta por asistencias;
comisión sobre cuota cobrada, costo por deporte/profesor/asistencia y evolución.
ADMIN ajusta monto final de comisión ABIERTA o CERRADA sin pagar, con motivo,
auditoría atómica y bloqueo común con pago; pagadas/canceladas intactas.
Pago, recibo, pendientes y resúmenes toman el final sin alterar el cálculo original.
Historia monetaria desde activar; antes, Sin historial. Horarios/asistencias actuales
pueden modificar estimados y reparto; liquidación cerrada conserva importe.
Suite de Sueldos573/2,4615aserciones; controles finales30/185 y revisión independiente26/166.
Catorce HTTP200 y capturas reales completas. Carlos aprobó Sueldos («Esta OK, solo
deberiamos ver desde donde accede en el menu»). Acceso Plata → Reportes aprobado por Carlos el 10/10 («Esta OK el acceso»); aspecto del ajuste aprobado por Carlos el10/10 («Sí, así»). Campo Monto ajustado, total a pagar≥0; diferencia automática con signo; nueve íconos. [Entrega](../06-pruebas/B12-A23/ETIQUETAS-AJUSTE-2026-10-10.md).
[Entrega Sueldos y ajuste final](../06-pruebas/B12-A23/IMPLEMENTACION-SUELDOS-2026-10-09.md). Sin activación ni cambios en base del club.

Acceso Plata → Reportes aplicado tras «Continuar»; [control y capturas](../06-pruebas/B12-A23/ACCESO-REPORTES-2026-10-09.md).
Control de ancho en el escenario: Caja375/scroll465 incluso quitando el enlace nuevo;
contenido fuera del cambio del menú, sin corrección ni reapertura automática de tareas.

Conexión V3, control 09/10: la primera revisión encontró que P1 deja pasar una ruta
nueva que no enumera y que la evolución omite el mes elegido cuando no tiene
movimientos. Casos registrados antes de ajustar el bloqueo y la selección mensual; corregidos y revisados en fuente. Regresiones permanentes P1/mes sin movimientos.

Control HTTP independiente de Inicio, 09/10: el escenario guardado dejaba P1 en
PENDIENTE y el acceso normal redirigía a preparación. Los tests base marcan P1
TERMINADA antes de cada prueba; ese contexto no se conservaba al reconstruir datos.
Corregido solo `ReportesEscenarioSeeder`: P1 TERMINADA en el escenario ficticio,
sin habilitar Deshacer ni retirar la protección del club. Regresión: la prueba fuerza
P1 PENDIENTE antes del seeder. Control HTTP posterior aprobado (Inicio y cuatro listados).

Comprobación de historia, 09/10: descuentos/cambios de plan sobrescriben el importe
original de la cuota; las liquidaciones no conservan fecha de cierre ni deporte
congelado. `afecta_caja` controla admisión operativa, no resultado económico:
Luz/Internet/Intereses son económicos aunque el catálogo lo tiene en falso.
Carlos eligió deuda histórica al cierre: agregar registro fechado desde un corte
explícito, sin certificar meses previos incompletos. Datos ficticios solo en la base
Codex. La clasificación económica debe ser independiente de `afecta_caja`.

Carlos validó los [acuerdos y revisión posterior](../07-evaluacion/ACUERDOS-B12-A23-2026-10-09.md). Motor/historial en desarrollo; Inicio aprobado («Ok, aprobado»), aplicado y verificado por HTTP independiente. Cuatro avisos abren los listados exactos, incluidos pendientes antiguos; permisos conservados. Suite completa: 532 aprobadas/2 omitidas, 4286 aserciones, 280,70 s; posteriormente cuatro regresiones P1 aprobadas (42 aserciones). [Entrega de Inicio](../06-pruebas/B12-A23/IMPLEMENTACION-INICIO-2026-10-09.md) y [visor](../06-pruebas/B12-A23/visor.html).

Carlos rechazó el Reportes original. Después pidió «Hacelo mas visual y de menos lectura», con tres referencias. [Propuesta V3](../06-pruebas/B12-A23/PROPUESTA-VISUAL-2026-10-09.md): tarjetas/tendencias, evolución, cambios por rubro, distribución y detalle plegado; Carlos eligió «Si, mucho mejor». [Aplicada y conectada](../06-pruebas/B12-A23/IMPLEMENTACION-REPORTES-2026-10-09.md) a Inicio, con filtros reales y detalles al corte; revisión independiente de fuente, suite completa537/2,4369 aserciones,476,20s correcta. CuatroHTTP200 y ocho capturas reales; revisión independiente final aprobada para esta fase financiera. Reporte Alumnos aprobado e integrado; Sueldos implementado y aprobado visualmente por Carlos; acceso aprobado el 10/10. Sueldos: costo de clases del mes / cuotas reales del mismo mes netas de bonificaciones y condonación / cada asistencia presente por separado. Atribución proporcional aprobada; historial prospectivo probado en base descartable. Ajuste final ADMIN aplicado también cerrado sin pagar y verificado independientemente. Ver/indicadores de Inicio conectados a la ruta mensual y sus apartados; clasificación de antecedentes pendiente. B12 abierto; A23 cerrado por Claude el 10/10 ([verificación](../06-pruebas/B12-A23/VERIFICACION-A23-INICIO.md)). Sin cambios de base del club por Codex.

## A32 — CERRADO 08/10/2026, verifica Carlos

Carlos aprobó el retoque: «Ok, APROBADO A32». Nombre de la persona en el título; Profesor toma Apellido, Nombre de su ficha vinculada y no repite ese dato abajo. Todas las tarjetas muestran Email y Rol en columnas consistentes. Pedro/Sandra/Victoria son nombres ficticios del ensayo. Se conservan Activo/Tu cuenta/candado y Editar/Nuevo x=224–320 en celular. Seis capturas reales y medidas correctas; controlador, CSS y toggle compartido intactos. Retoque nombres/rol: corrida completa 506 aprobadas, 2 omitidas y 2 fallos documentales durante edición simultánea, 4075 aserciones, 275,69 s; controles documentales repetidos 4/19 verdes, 0,31 s. Ninguna prueba de código falló. CERRADO, verifica Carlos; sin despliegue. [Entrega y capturas](../06-pruebas/PRU-02/IMPLEMENTACION-A32.md).

## A6–A10 — CERRADOS 08/10/2026

A6–A9 verifica Carlos; A10 verifica Claude en segunda revisión, con 432 combinaciones/442 pedidos, 19 pruebas propias del corte final y 45 comprobaciones de navegador sin fallos. Limpiar corregido y pulsado en los cuatro modos. Suite final Codex 508 aprobadas/2 omitidas, 4077 aserciones, 423,14 s; build 38,28 s y capturas finales renovadas. [Entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A6-A10.md), [verificación independiente](../06-pruebas/PRU-02/VERIFICACION-A10.md). A32: candado y retoque de nombres/rol aprobados, entrega separada. Sin despliegue.

## Antecedentes A6–A10, antes del cierre (07/08 de octubre)

### Corrección tras primera revisión independiente, 08/10/2026

Claude devolvió A10: Limpiar escapaba dos veces la dirección y perdía fecha/año/mes en Chrome. El resto de la lógica quedó comprobado con 432 combinaciones y 442 pedidos propios. [Verificación](../06-pruebas/PRU-02/VERIFICACION-A10.md). Corregido el parámetro href de Limpiar; nueva prueba DOM reproduce el rojo antes y pasa después. Diez pruebas A10/110 aserciones verdes; sintaxis y Blade correctos. Suite completa y segunda revisión de Claude en ejecución; A32 sigue esperando elección de Carlos. No desplegado.

### Primera entrega A6–A10, anterior al cierre, 08/10/2026

A6–A9 cerrados con aprobación visual de Carlos (§6a); decisiones y capturas en [entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A6-A10.md). A10 asignado a Claude para verificar lógica; [orden](../06-pruebas/PRU-02/VERIFICAR-A10.md) preparada, revisión todavía no ejecutada. Los cortes anteriores de este apartado son antecedentes.

A10 aprobado por Carlos: «Me gusta, A10 Aprobado». Aplicado localmente Día/Semana/Mes/Año, Nuevo junto al contador y título Nuevo movimiento. Resultado del período = ingresos − egresos; deja de sumar saldo inicial, de acuerdo con la propuesta aprobada y FIN-04. No implementa saldo acumulado. Nueve pruebas de intervalos pasan, 70 aserciones; suite 507 aprobadas/2 omitidas y 24 capturas finales correctas. [Entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A10-PERIODOS.md). Aspecto verificado por Carlos; intervalos/cálculos pendientes de otro agente (§6a), sin deploy. Las notas de maquetas de este apartado conservan el corte anterior a aplicar.

Elecciones conservadas: A6 solo texto, A7 B, A8 A. Capturas A7 renovadas desde Wings con
ADMIN/OPERATIVO en escritorio y marco real de 375×667; el pie muestra los controles.
**Contradicción detectada y corregida por pedido posterior de Carlos:** el pase decía
alineación móvil a derecha aplicada; las primeras capturas mostraron A7 a izquierda.
Carlos pidió corregir únicamente celular. Regla CSS exclusiva de Alumnos bajo 640 px;
12 imágenes posteriores y tres comparaciones geométricas de escritorio correctas.
Build 26,41 s correcto; escenario propio previo 4 pruebas/15 aserciones.
Última suite propia después del ajuste de columnas: 496 aprobadas, 2 omitidas, 1 falla; 3948 aserciones, 367,51 s.
La única falla es el contador documental 491/499, ya registrado en el pase de Claude.
Pedido posterior: Cobrar/Editar alineados entre sí y Ver alineado con Nuevo.
Dos columnas móviles de 96 px y ajuste de margen, sin mover Nuevo; coincidencia exacta
en ADMIN/OPERATIVO y nombre largo. Tres imágenes de escritorio idénticas al antes.
[Antes/después y mediciones actuales](../06-pruebas/PRU-02/evidencia/a6-a10/visor-a7-columnas.html).
A7 aprobado visualmente por Carlos el 08/10; paquete todavía en curso.
A8/A9: 22 imágenes actuales reales; Grupos ADMIN/OPERATIVO, Niveles/Profesores ADMIN,
375×667 y escritorio. Activo/Inactivo y Con/Sin grupos comprobados en filas ficticias.
El pase también sobreestimaba la alineación de estos listados: corregida con tres clases
de pie y CSS exclusivo bajo 640 px; controles a derecha, botones 96×32 en una fila.
Cuatro escritorios idénticos al antes por SHA256; distintivo coloreado de Profesores intacto.
Build 24,21 s, sintaxis y Blade correctos; escenario 4/15 verde, 7,53 s.
Suite posterior: 491 aprobadas/2 omitidas/7 fallas, 3944 aserciones, 293,60 s; base Codex.
Seis fallas HTTP 500 por `$bloqueo` ausente en `operativo/dashboard.blade.php`, modificado
simultáneamente fuera de A6–A10; su controlador entrega `estado`, no `bloqueo` en ese corte.
Una falla documental: contador 491/500, pruebas nuevas todavía locales. No se corrigieron
estos cambios ajenos. [Salida completa](../06-pruebas/PRU-02/evidencia/a6-a10/suite-a8-a9-2026-10-08.txt).
[Grupos y accesos a las otras dos pantallas](../06-pruebas/PRU-02/evidencia/a6-a10/visor-a8-grupos.html).
Carlos abrió el visor actual y respondió «A-8 APROBADO» el 08/10; resultado visual aprobado.
A9: Carlos aprobó si coincide con Nuevo; condición comprobada en las tres pantallas reales
a 375 px: Nuevo/último botón x=224–320; interruptor termina en x=320. Siete capturas nuevas,
sin cambios de aplicación. [Control exacto](../06-pruebas/PRU-02/evidencia/a6-a10/comprobacion-a9-alineacion.json).
Carlos confirmó después «Perfecto, APROBADO A-9 Entonces»; no se vuelve a pedir A9.
A10: 28 capturas reales actuales/propuesta, ADMIN escritorio/375; año/mes/vacío y formulario
completo. Nuevo junto al contador en barra propia, Limpiar en filtros y título Nuevo movimiento
propuestos inicialmente mediante dos variantes Blade; luego aprobados/aplicados, ver entrega A10 arriba.
Filas/período idénticos, seis campos con íconos y acciones móviles visibles; enlaces/sintaxis correctos.
[Cashflow ANTES/propuesta y acceso al formulario](../06-pruebas/PRU-02/evidencia/a6-a10/visor-a10.html).
Sin nueva suite/build por la propuesta documental; entrega/verificación pendientes, sin autocierre/deploy.
 
## A14/A27/A53 — CERRADOS 07/10/2026 (verificados por Gemini, contrastados por Claude)

Corrección de Codex (`3d1808a`) verificada de punta a punta con emulación real de navegador (Chrome Headless controlado vía CDP nativo en Node.js v22 con `verificar_interactivo.mjs` a 375×667):
- **A14 (Formularios largos):** Botones `.mobile-form-actions` visibles al abrir (`top: 610, bottom: 667, height: 57`) y durante todo el scroll (`position: fixed`). Margen libre con el último campo (147.6 a 610 px) sin solapamientos. Resumen dinámico muestra errores en vacío (6 en alumnos, 8 en profesores); al completar un campo desaparece en tiempo real, al vaciarlo reaparece, y al completar todos se oculta. Con DNI repetido (`40111222`), rechazo 422 del servidor muestra mensaje y al modificar el campo pasa a «DNI: dato modificado; se comprueba al guardar». Cartel a 128 px debajo del encabezado sin solaparse.
- **A27 (Movimiento de caja):** Registrar y Cancelar visibles en `y=610` (antes quedaban en `y=740`). Campo Observaciones alcanzable con 131 px libres. Resumen de errores dinámico. Registro real de movimiento completado con OPERATIVO (Egreso, $1.500) persistido en `movimientos_operativos` (id 1) y con redirección exitosa a `/caja`.
- **A53 (Grupos y Cobro):** Grupo largo con 3 tarifas de 7 cifras ($1.250.000, $2.450.000, $3.850.000) y tarjeta de alumna en selector de cobro legibles al 100% sin recortes (`scrollWidth <= clientWidth`) y `document.documentElement.scrollWidth = 375` (cero desborde horizontal).
- **Regresiones:** Salir sin guardar (A4) activo con diálogo `confirm`. En escritorio 1280×900 las 7 vistas conservan layout de escritorio sin barra fija ni cartel móvil. `CspSinCodigoIncrustadoTest` 2/2 PASSED.
- [Informe completo](../06-pruebas/PRU-02/VERIFICACION-A14-A27-A53.md) · Evidencia y 30 capturas en `docs/06-pruebas/PRU-02/evidencia/verificacion-a14-a27-a53/`. Dictamen: APROBADO. Pasa a Claude para control cruzado y cierre definitivo (§6a).

## A15/A16 — CERRADOS 07/10/2026 (verificado por Gemini, contrastado por Claude)

Carlos eligió aviso y confirmación de bloques del reloj y aprobó capturas escritorio/375.
Crear avisa sin escribir; Confirmar permite continuar con los horarios revisados.
Cada día de una serie tiene su horario: seis POST guardan 76 clases de seis grupos,
una serie por grupo; conflicto en cualquier fecha revierte toda la tanda.
18 pruebas nuevas; suite completa final 486 aprobadas/2 omitidas, 3911 aserciones, sin fallas.
Build/PHP/Blade correctos; capturas reales y login de control a 375 comprobados.
[Entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A15-A16.md) · [Contrato V2](../02-contratos/Wings-Contrato-Clases-Asistencias-V2.md).
Solo creación web; no reservas, tarifas, edición masiva ni despliegue. Verifica otro agente.

## Paquete de celular — verificado por Codex CyE 06/10/2026

A20, A28, A33, A36, A40 y A41: CERRADOS, verificado Codex CyE 06/10. A14, A27 y A53: DEVUELTOS a Gemini; T1 también devuelto por cobertura incompleta y recortes.

84 capturas propias, 102 originales inspeccionadas; tres roles y datos ficticios de estrés.
A36 conserva tabla Opción A, no tarjetas; A33 mide 61,61 px por fila.
Suite 489 aprobadas/2 omitidas, 3924 aserciones, 187,17 s, wings_testing_codex.
[Dictámenes, cobertura y límites](../06-pruebas/PRU-02/VERIFICACION-CELULAR-COMPARTIDO.md).
Sin cambios de aplicación ni despliegue.

## A25 CERRADO (verificado por Gemini, contrastado por Claude) — 06/10/2026

Apertura explícita con recibido confirmado, herencia del último cierre del club y motivo
si difiere. Un cajón compartido, un turno abierto. ADMIN configura el medio físico una
vez, guarda aparte sus cobros y cuenta/cierra antes de validar o rechazar.
Cierre con esperado/contado/diferencia, cambio que queda y entrega; permite diferencias
para revisión ADMIN, sin crear asientos de ajuste. Rechazadas conservan conteo/entrega y
actor/fecha originales, sin cambiar la apertura siguiente. Históricos NULL no se rellenan.
26 pruebas permanentes (142 aserciones) pasando en suite compartida, sin fallas.
Carlos aprobó las cinco pantallas escritorio/375; sin CSS ni componentes compartidos tocados.
[Entrega y capturas](../06-pruebas/PRU-02/IMPLEMENTACION-A25.md) · [Contrato V5](../02-contratos/Wings-Contrato-Caja-Cashflow-V5.md).
Verificado por Gemini en una segunda vuelta, con recorrido propio por HTTP y 23 capturas; Claude reprodujo el recorrido y contrastó el informe; CERRADO 06/10 ([Informe](../06-pruebas/PRU-02/VERIFICACION-A25.md)). Sin sesión interactiva de navegador. Base del club y servidor intactos.

## A13/B1/A55 cerrados; A48/A49 asentados — 06/10/2026

Rama a55-inscripcion integrada entera en main `6d3f68a`, conservando ambos logs.
A13/B1/A55 CERRADOS por Codex CyE: cobro ADMIN sin caja, anulación desde ficha con
motivo, historial con períodos originales, contraasientos negativos como egreso y
una inscripción por persona coherente entre selector y ambas fichas.
Regresión A25: esperado operativo $58.000 ($10.000 inicial + $48.000 propios),
sin sumar cobro directo ADMIN de $101.000 ni abrirle caja.
A48/A49 CERRADOS, verificado Claude; búsqueda repetida sobre main coincide.
**41/72 cerrados, 31 abiertos, 0 frenan.** A54 conserva su cierre anterior.
El primer control rechazado queda como antecedente, no como estado actual.
[Informe y capturas](../06-pruebas/PRU-02/VERIFICACION-A13-B1-A55-CIERRE.md). Sin deploy.

## A37 resuelto (Ficha del alumno en celular 375px) — 05/10/2026

- **Defecto A37:** La ficha del alumno (`/alumnos/{id}`) desbordaba horizontalmente en celulares a 375px, cortando los botones de cabecera (`Editar`) y filas de historial (`Recibo`, `Anular`).
- **Solución implementada:**
  - `resources/views/alumnos/show.blade.php`: la barra superior de acciones reemplazó el `justify-content: flex-end` inline por `flex flex-wrap items-center gap-2 mb-4 justify-start sm:justify-end`.
  - Las filas de deudas pendientes e historial de pagos adoptaron `flex flex-wrap sm:flex-nowrap justify-between items-center gap-2 p-2 rounded` con bloque de acciones `w-full sm:w-auto justify-end` para adaptarse en 375px sin desbordes.
  - Botón Volver inferior actualizado a `justify-start sm:justify-end`.
  - Cubierto con `tests/Feature/FichaAlumnoResponsiveA37Test.php` y capturas comparativas en `docs/06-pruebas/PRU-02/capturas-a37/`. Marcado como `HECHO (Gemini), a revisar`.

## P2 Entrega 2 implementada y probada (A17, A34, A3) — 05/10/2026

Implementación de los tres defectos de la ficha del alumno y cobro:
- **A17 (Cobrar desde la ficha):** Botón principal **Cobrar** (`x-ds.button variant="primary"`) incorporado en la cabecera de la ficha del alumno para Admin y Operativo, conduciendo a `/caja/cobrar/{id}`. En la sección de cobranza, cada cuota impaga cuenta con botón de fila **Cobrar** (`ds-btn-row`).
- **A34 (Historial y reimpresión/descarga de recibos):** Sección «Historial de pagos» con enlace directo **Recibo** (`ds-btn-row ds-btn-row--sec`) con `target="_blank"`, que abre el PDF oficial generado con DomPDF (permitiendo imprimir y descargar). Pagos anulados identificados con badge e importe tachado. Historial ampliado a 12 registros en `AlumnoWebController::show`.
- **A3 (Cobro adelantado sin duplicación):** `CajaWebController::cobrar()` proyecta y ofrece en memoria los períodos futuros (próximos 2 meses) al precio de lista vigente del plan activo, con badge «Adelantado». Al cobrar un período adelantado, se crea la `DeudaCuota` como `PAGADA`. Al llegar el día 1, el comando `cobranza:generar-deudas` la omite sin duplicar (`$contSkipped++`). Búsqueda en `/caja/cobrar` permite encontrar a cualquier alumno activo aunque no tenga deuda pendiente.
- **Suite:** 8 pruebas nuevas con 40 aserciones en `tests/Feature/P2Entrega2FichaCobroAdelantadoTest.php`, 100% aprobadas en `wings_testing_gemini`. Regresiones de Cobro y Cobranza (`CobroReciboAccesoTest`, `CobranzaEntrega1Test`, `SaldoUnicoPorAlumnoTest`) 100% verdes.
- **Pendiente:** Control cruzado independiente por otro agente (§6a). Sin despliegue.

## P2 Entrega 1 aprobada en segunda verificación — 05/10/2026

Codex CAB verificó `abc346a` sobre HEAD `4fb185e` en código y navegador:
A51/A52 resueltos, deuda por registro estable al filtrar, ayuda por otro deporte
solo con saldo y estado mensual sin inscripción en listado/ficha/resumen.
Filtros compartidos recorridos a 375px; build verde. Suite desde el repo en
`wings_testing_codex`: **384 pruebas / 2968 aserciones**, todas verdes.
Datos visuales ficticios en copia local alineada a HEAD; sin tocar el padrón ni
el servidor, sin cobros ni despliegue. [Informe y capturas](../06-pruebas/PRU-02/VERIFICACION-ENTREGA1.md).
A53/A54/A55 surgieron fuera de Entrega 1. Estado posterior al control registrado 06/10:
A53 DEVUELTO a Gemini por Codex CyE 06/10; A54/A55 corregidos por Claude y cerrados por Codex.
Ver control sobre main integrado arriba. Gemini puede continuar Entrega 2.

## A43 y permisos A29/A30/A31 verificados y cerrados — 05/10/2026

Verificación independiente realizada por Gemini sobre commits `218ffc5` (A43) y `97cf933` (Permisos).
- **A43:** Ingreso en mes cerrado (`2020-01-20`) muestra aviso dinámico con importes congelados y radios obligatorios; opción Sí genera cuota corriente 100% ($30.000) sin cuotas históricas y deja al alumno En plazo; opción No no genera cuota, conserva inscripción ($5.000) y deja al alumno Al día. Ingreso en mes corriente (`2026-10-05`) oculta el aviso y genera cuota automática con porcentaje del día. Registro de auditoría `alta_cuota` en JSON (`modo`, `usuario_id`, fecha, período, monto).
- **Permisos (A29, A30, A31):** Redirecciones silenciosas eliminadas; Operativo recibe 403 con mensaje en castellano en administración y Volver a `/operativo` con sesión activa; `/admin` y `/caja/validaciones` responden 404 sin exponer datos. Profesor recibe 403 en administración y en `/alumnos`, `/caja`, `/grupos` con Volver a `/clases`. Admin común en cuenta protegida recibe 403 con Volver a `/admin/dashboard`. Cero datos filtrados. Probado en escritorio y móvil (375 px).
- **Suite completa:** 384 pruebas / 2968 aserciones aprobadas en `wings_testing_gemini`.
- [Informe de verificación](../06-pruebas/PRU-02/VERIFICACION-A43-PERMISOS.md). Defectos A29, A30, A31 y A43 CERRADOS. Pendiente de despliegue con migración `alta_cuota`.

## P2 / A11 — Entrega 1 corregida y A11 aprobada, 04/10/2026

Entrega 1 de Cobranza: correcciones de A51 (fila por registro con deporte + DNI, columna Deuda y renglón chico de ayuda), A52 (cálculo de estado unificado basado solo en cuotas, inscripción no convierte en deudor) y A18/A19 (filtros responsive en 375px) implementadas en commit `abc346a`. Carlos aprobó el diseño tras inspeccionar las 5 pantallas a 375px. Suite completa 356/2073 verde en `wings_testing_gemini`.
A11 (Configuración): verificada de forma independiente por Gemini en código y navegador (escritorio y móvil 375px). Grupos «La plata», «La cobranza», «Los avisos», validación estricta en servidor, persistencia de errores, guardado asíncrono y generación mensual fija validados. [Informe de verificación](../06-pruebas/PRU-02/VERIFICACION-A11.md); defecto A11 cerrado. Sin despliegue.

## P1 — primera carga por Excel verificada y aprobada, 05/10/2026

P1 implementada por Codex CAB (`d530c85`) y **verificada de forma independiente por Gemini CyE** (`LOG GEM CYE`).
- **Plantilla y Catálogos:** Generación de plantilla con catálogos reales (deportes, grupos y planes activos con precio) y validación de lista desplegable en hoja Alumnos vacía de 38 columnas. Hoja Guía con instructivo y ejemplos.
- **Revisión y reporte:** Detección de múltiples errores simultáneos (6 errores en `club-con-errores.xlsx`) sin persistencia en base de datos. Exportación de informe con columna AM (`Errores`) preservando intactas las 7.638 celdas A:AL originales y sus tipos de datos.
- **Carga atómica:** Importación transaccional (`DB::transaction`) de 4 alumnos, 6 cuotas ($296.000) y 1 inscripción ($5.000) sumando $301.000 exactos de deuda. Sin cobros, sin movimientos de caja ni descuentos indebidos.
- **Formatos:** Sanitización unificada en `FormatoExcelCargaService` para montos con punto de miles ("52.000"), períodos numéricos de 5 dígitos (92026 -> 2026-09), texto con cero y DNI.
- **Deshacer:** Restricción estricta si existen cobros registrados (incluso anulados) o actividad posterior. Si está libre, revierte íntegramente la carga y restaura el estado a `PENDIENTE`.
- **Permisos y flujo:** Redirección automática de ADMIN mientras esté `PENDIENTE`, bloqueo de alta manual por URL vía middleware `PrepararPrimeraCarga`, y respuesta HTTP 403 para roles Operativo y Profesor.
- **Diseño responsive:** Recorrido en 4 pasos apilados conforme a tokens del Design System, validado en escritorio y móvil (375 px).
- [Informe de verificación independiente](../06-pruebas/PRU-02/VERIFICACION-P1.md). P1 APROBADA.
- **Pendiente de despliegue:** Migración `2026_10_05_180000_create_primera_carga.php` y build de Vite. Importadores antiguos (`wings:importar-padron` y `wings:importar-deuda-inicial`) retirados el 13/10/2026 (T2).

## P0 — primera carga, 04/10/2026

## A2/B2 — implementados localmente, 23/09/2026

Cuota del mes creada en el alta, atómica con alumno/plan/inscripción, con importe y
porcentaje congelados. Cobranza clasifica por saldo pendiente, sin exigir pagos previos.
Suite completa 331/1900; sin vistas ni despliegue. Requiere migración
`2026_09_23_120000_add_porcentaje_alta_to_deuda_cuotas.php` al desplegar.
**Pendiente de verificación independiente en código y pantalla; no cerrados.**
[Evidencia y límites](../06-pruebas/PRU-02/IMPLEMENTACION-A2.md).

## Sitio de prueba — automatización, 23/09/2026

Cron exclusivo de `wingstest` instalado y ejecutado realmente, incluso con nologin
y contraseña bloqueada. Sin heartbeats de producción; instalación repetible al
montar test. Figuran deudas mensuales y resumen diario a las 08:00.
Destinos guardados en Configuración. **Telegram recibido y confirmado por Carlos**;
chat verificado por API. Bot/correo autorizados. **Correo no entregado**: Postfix
rechaza por error de sus consultas MySQL de alias; pendiente reparar transporte
compartido o disponer de SMTP externo. No se tocó producción.
[Verificación y pendientes](../06-pruebas/PRU-02-AUTOMATIZACION-TEST-2026-09-23.md).

## ENT-01 — implementada, 22/09/2026

[Diseño aprobado](../05-pendientes/ENT-01-PROPUESTA-CARGOS-ADICIONALES.md): inscripción
por DNI y cargo separado de cuotas; corte retirado en P0 del 04/10. Pago parcial cubre primero
inscripción; caja y recibo desglosan conceptos. Comisión y estado mensual excluyen
inscripción. Corregir ingreso sin pagos conserva el cargo con auditoría; con pagos se rechaza.
Autorización visual y reemplazo de §5 de Punitorios confirmados por Carlos el 22/09.
FIN-14 incorporó motor y configuración en B11 el 16/10; pendiente verificación
independiente de Claude. Sin deploy.
[Evidencia y límites](../06-pruebas/ENT-01-INSCRIPCION-2026-09-22.md).

## Decisión nueva 21/09/2026 — canchas y liquidaciones de clubes (POS-07)

[Plan v2026-09-21](../07-evaluacion/PLAN-CANCHAS-LIQUIDACIONES-v2026-09-21.md)
documentado; implementación pendiente. Ubicaciones, canchas físicas y tarifas por
hora; bloques completos del reloj y total de clase editable por ADMIN, protegido de
aumentos automáticos. Costos y decisión económica de cancelación inaccesibles al
OPERATIVO: conserva cancelar clases, dejando revisión financiera al ADMIN.
Liquidar por mes o fechas elegidas, solo clases dictadas hasta el corte y pendientes
de liquidación; las incluidas en otra vigente no se repiten, aunque no estén pagadas.
Reporte Club → Deporte → Nivel. Sin código, base ni despliegue en esta tarea.
Reemplaza el planteo POS-07 «mejora futura sin fórmula» y la propuesta histórica de
Reportes V1 de repartir alquiler por cantidad de clases. No cambia liquidación de
profesores por minutos ni resuelve el cálculo pendiente de gastos generales en Reportes.

## Decisión nueva 13/09/2026 — particulares (POS-06)

[Contrato funcional](../02-contratos/Wings-Contrato-Clases-Particulares-V1.md) documentado; implementación pendiente. Solo documentación en este turno, sin verificación funcional nueva. La regla previa de cierre inmutable se enmienda por decisión expresa de Carlos: ADMIN puede cancelar una cerrada no pagada para revisar asistencias; pagadas intactas y ajustes en la siguiente. Rige para todas las clases y perfiles. También se documentan excepciones de particulares sobre agenda, permisos, cancelación y remuneración; no trasladarlas a clases ordinarias fuera del alcance general declarado. [Pendiente y continuidad de Reportes](../05-pendientes/CLASES-PARTICULARES.md).

> Continuidad reorganizada el 12/09, sin revalidación funcional:
> [Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo](PROTOCOLO-CONTINUIDAD.md).
> Logs activos abreviados; originales íntegros en `docs/99-archivo/bitacoras/2026-09-12/`.
> Leer esta página por sección de tarea, no para reconstruir toda la historia.

> **Actualizado:** 12/09/2026
> **Plan vigente:** `docs/07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md`,
> version 2026-09-08.v4.
> Si otro documento contradice este estado, no improvisar: verificar y corregir.

## 1. Estado general

**FIN-12 CERRADA el 21/09/2026:** Cancelar liquidación cerrada no pagada implementada y probada (`CancelarLiquidacionCerradaTest` con 9 tests y `CancelarLiquidacionConcurrenteTest` con 2 tests con MariaDB real en dos conexiones, suite en 274 tests / 1675 aserciones). Permite a ADMIN cancelar con motivo obligatorio (mínimo 5 caracteres) una liquidación en estado `CERRADA` con `estado_pago = PENDIENTE`, preservando detalle y auditoría (`usuario_cancelacion_id`, `cancelada_at`, `motivo_cancelacion`). Liquidaciones en estado `PAGADA` son estrictamente intocables para todos los perfiles. La cancelación concurrida con el pago está coordinada transaccionalmente con `lockForUpdate()`. Mientras la liquidación esté cerrada, las asistencias de las clases involucradas (HORA y COMISIÓN) no pueden ser alteradas por ningún perfil; la cancelación las desbloquea para corrección y habilita una nueva liquidación vinculada por `reemplazada_por_id`. Diseño mínimo autorizado por Carlos en show e index con badge `Cancelada` y formulario con confirmación nativa `data-confirmar` sin inline handlers (CSP en 22 bloques y 10 manejadores intacta). Requiere migración `2026_09_21_120000_permitir_cancelar_liquidacion_cerrada_no_pagada.php`. Sin deploy.

**FIN-09 CERRADA el 19/09/2026:** Límites de fecha y confirmación en movimientos y cobros implementada y probada (`LimiteFechasMovimientosTest`, 8 tests nuevos, suite en 263 tests / 1505 aserciones). Fechas futuras rechazadas con `before_or_equal:today` en alta de caja (`editarStore`), edición de caja (`updateMovimiento`), alta directa de cashflow (`store`) y cobro de cuotas (`pagar`). Fechas del mes en curso se guardan sin aviso. Fechas del mes anterior o más viejas exigen confirmación en pantalla antes de registrar: en caja y cashflow mediante banner informativo server-side y botón `Confirmar` con campo oculto `confirmar_fecha_vieja` (sin scripts nuevos, CSP en 22 bloques intacta); en cobro de cuotas mediante respuesta 409 y confirmación del usuario. El movimiento conserva su fecha real para fines contables/históricos y entra en la caja abierta actual, sin alterar cajas cerradas o validadas. Punto marcado con comentario TODO para futuras alertas por Mail/Telegram al ADMIN.

**ENT-03 CERRADA el 12/09/2026:** Favicon definitivo de patín artístico alado aprobado por Carlos (Opción 1 - Zoom Máximo 95% superficie). Se abandonó la paleta previa negra/roja/blanca en favor de la identidad de patín artístico (bota blanca con taco, alas fucsia y cian sobre degradé claro hielo/lavanda, ruedas oscuras con contraste óptimo en pestaña). Generados `public/favicon.ico` (multi-res 16, 32, 48), `public/favicon-32x32.png`, `public/favicon-16x16.png`, `public/apple-touch-icon.png`, `public/android-chrome-192x192.png`, `public/android-chrome-512x512.png` y `public/site.webmanifest`. Vinculados en el layout raíz `resources/views/layouts/ds-app.blade.php`. Suite de 166 tests (1026 assertions) verde al 100%.

**ENT-04 CERRADA el 12/09/2026:** Ojo para ver/ocultar contraseña implementado en alta y edición de usuarios (`resources/views/usuarios/_form.blade.php`), con botones independientes para "Contraseña" y "Confirmar contraseña" (respetando simetría de columnas y localidad de control). Manejador desacoplado en `resources/js/ds-app.js` compilado con Vite sin alterar CSP (26 scripts incrustados y 24 manejadores HTML en `CspSinCodigoIncrustadoTest`). Diseño autorizado por Carlos el 07/09.

**ENT-05 CERRADA el 13/09/2026:** Acceso directo a 1 clic al recibo de cuota: 1) en la confirmación de cobro (banner flash tras volver al listado de cajas) con botón `Recibo` (`target="_blank"`, `inline=1`), y 2) en la ficha del alumno (`alumnos/show.blade.php`), con botón `Recibo` en cada fila del historial de pagos (soportando tanto pagos activos como anulados). Auto-dismiss de 3s en `ds-app.js` configurado para no descartar banners con enlaces interactivos. Protegido contra rol Profesor. Diseño autorizado por Carlos el 07/09. Suite verde con nueva prueba `CobroReciboAccesoTest`.

**SEG-11 en avance el 13/09/2026:** Migrados los 14 onclick de vistas a `ds-app.js` (`MANEJADORES_PERMITIDOS` bajó a 10). Unificado el validador de disponibilidad en vivo en `ds-app.js` para `niveles` y `tipos-caja`. Migrados los scripts de `grupos` y `usuarios` a archivos dedicados (`resources/js/grupos.js` y `resources/js/usuarios.js`) compilados con Vite y cargados vía `@vite` conforme a DESIGN-RULES.md §8 (`BLOQUES_SCRIPT_PERMITIDOS` bajó de 24 a 22 en `CspSinCodigoIncrustadoTest`). Formateo de precios por frecuencia vía `window.initMoneyInput` verificado. Assets compilados; diseño intacto. Suite completa en 222 tests (1347 assertions).

**FIN-06 CERRADA el 13/09/2026:** Comisión histórica en liquidaciones implementada y probada (`LiquidacionComisionHistoricaTest`, 7 tests nuevos, suite total en 229 tests / 1372 aserciones). Removidos los filtros de estado actual (`activo = true`, `deporte_id`) en `calcularLiquidacionComision()`: sólo hechos históricos deciden la inclusión (pago completado para el mes/año y asistencia confirmada a clase no cancelada del profesor). Congelado el `porcentaje_comision_aplicado` en tabla `liquidaciones` al generar el registro, utilizado en cálculo y recálculo con fallback a comisión actual si es nulo. Migración con backfill para liquidaciones COMISION preexistentes. Expuesto en `show.blade.php` vía controlador en memoria sin modificar vistas. Anulación de pago en recálculo probada. Suite verde.

**COB-05, COB-09 y FIN-02 VERIFICADAS 11/09 sobre e921e5d:** 15 cobros por
Chrome en una misma base sintetica, incluida subida/bajada con descuento y
asistencia, cancelacion/recobro y medios de pago distintos. Suite 161/977.
Supera el freno de a9795c6. Evidencia: `docs/06-pruebas/COB-05-CIERRE-2026-09-11.md`.

FDS-04 verificada el 11/09/2026: Cobranza por rol, dos cobros simples en navegador
y bloqueo manual completo de rubros reservados y sus hijos. Carlos revoco la
excepcion de editar observaciones. Evidencia: `docs/06-pruebas/FDS-04-2026-09-11.md`.

**Bloque 1 de cobros verificado al 11/09, incluido COB-05.** COB-01 a COB-04 y COB-06 a COB-08
verificadas por navegador e integradas en `main`. COB-04 incluyo una correccion
posterior verificada en copia aislada: pantalla y cobro usan ahora un solo calculo del
importe con descuento. COB-05 cruzo cambio de plan, descuento y asistencia sobre
main con COB-09 integrado. Evidencia en `docs/06-pruebas/`.

**Bloque FIN al 11/09:** FIN-02 corregida (el recibo toma el medio de pago del
movimiento exacto), verificada por navegador. FIN-01 no aplica por decision de Carlos.
FIN-03 implementada el 11/09: anulaciones nuevas conservan periodos/importes y
motivo para el PDF, sin imputaciones activas. Contrato Recibos V2; V1 historica.
Suite 166/1026 y PDF real revisado. Pendiente verificacion cruzada de Claude y deploy.

**Nada de esto esta desplegado:** producción sigue en `314e485` (leído por consola el 06/10; este texto decía `81f27ef`), anterior a las ocho
correcciones. No hay riesgo inmediato porque el club todavia no cargo alumnos.

Las ocho evaluaciones historicas se conservaron sin cambios de contenido en
`docs/07-evaluacion/Evaluaciones previas/`; el plan vigente permanece en la carpeta padre.

Wings esta publicado en `https://wings.gestionar-te.com.ar`, pero el gate final de
produccion no esta firmado. La base del servidor quedo preparada para que el club
cargue sus datos por pantalla y Vanina ya tiene una cuenta ADMIN. Al 11/09, segun
Carlos, **todavia no hay ningun alumno cargado**; el alcance del resto de la carga no
fue inspeccionado.

## 2. Servidor — corte SSH 08/09 y avance por consola 09/09

| Que | Estado |
|---|---|
| Commit desplegado | Producción `314e485`, leído por consola el 06/10 (el 09/09 era `81f27ef`). Sitio de prueba `671a3ea` desde el 10/10 |
| Diferencia con `main` al corte | El servidor incorporo documentos y scripts hasta `81f27ef`; los commits posteriores requieren sincronizacion |
| Plataforma | AlmaLinux 9, PHP 8.2.33, Laravel 12.68.0 |
| HTTPS | Activo |
| Cloudflare | Proxy activo; acceso web directo al servidor cerrado |
| Migraciones | Sin pendientes en la ultima verificacion |
| Scheduler | Registrado y ejecutado cada minuto; deuda mensual programada para dia 1 a las 06:00 |
| Backups | Diarios, cifrados, rotados y copiados a Drive |
| Monitoreo | FDS-02 cerrada 09/09: cron y respaldo instalados; fallos y recuperaciones probados. Email y Telegram recibidos por Carlos. Monitor HTTPS y ambos heartbeats Up |

No se pudo demostrar que la corrida mensual del 01/09 haya producido resultado: no
quedo un log que lo pruebe o descarte.

## 3. Base de entrega del servidor

**La base del servidor tiene carga real en curso, pero todavia sin alumnos.** El 09/09
Carlos informo que el club empezo a cargar datos; el 11/09 aclaro que **Vanina aun no
subio ningun alumno**. Lo que haya son catalogos (deportes, grupos, planes y similares),
sin personas, deudas ni cobros. No fue revalidado por SSH desde esta computadora.

Consecuencias inmediatas: no correr `CatalogosSeeder` ni ningun seeder contra esa base
(puede quitar la proteccion de Cuotas y Sueldos; FIN-01 se dio por no aplica porque ningun despliegue lo corre), no usarla para pruebas destructivas, y tratar el
respaldo como la unica red — FDS-03 ya no puede "reconstruir" el estado, porque el
estado ahora incluye datos que solo existen ahi.

Estado con el que fue preparada el 07/09, con respaldo previo — **historico, ya superado
por la carga humana**:

- Sin alumnos, deudas, pagos, clases ni operacion real.
- Se conservan `Cuotas` con `Cuota Mensual` y `Sueldos` vacio porque el codigo los
  busca por nombre.
- Se conservan las configuraciones existentes y las tres reglas de primer pago.
- Deportes, niveles, grupos, planes, tipos de caja y demas catalogos los carga el
  usuario por pantalla.
- Existe una cuenta superadmin protegida y una cuenta ADMIN de Vanina.

FDS-03 esta pausada por Carlos desde el 09/09: ese estado minimo es historico.
No crear un seeder ni limpiar datos reales; redefinir la tarea antes de ejecutarla.

## 4. Estado confirmado del repositorio

| Area | Estado |
|---|---|
| Stack | Laravel 12, PHP 8.2, MariaDB, Blade y Vite |
| **Tests** | **823 pruebas** en la carpeta compartida (**818 versionadas con T36**). Suite completa Codex 17/10: **821 ejecutadas: 818 aprobadas, 2 omitidas y 1 fallida (T38)**; 7584 aserciones, 1332,77 s, salida 1, base y archivos temporales exclusivos. Tras cbc851e de Claude: repetición integrada T38/T36/FIN-11/guardianes, 50 aprobadas/314 aserciones, 34,81 s. No se certifica suite completa verde; detalle en IMPLEMENTACION-T36.md |
| Roles | ADMIN, OPERATIVO y PROFESOR; superadmin protegido |
| Cobranza | ADMIN y OPERATIVO entran; PROFESOR rechazado; T31 implementada 13/10 (Gemini CyE): inscripción impaga convierte en MOROSO o DEUDOR según fecha_alta y dias_plazo_alta (7) / dias_gracia_cobranza (10), jerarquía DEUDOR > MOROSO > EN_PLAZO > AL_DIA; paridad estricta con buscador de Cobrar; Entrega 1 aprobada por Codex 05/10: apertura deudores/morosos por antigüedad, fila por registro deporte + DNI con deuda propia y ayuda por otro deporte, filtros 375 y botones Cobrar/Ver de 64px |
| Alumnos | CRUD, plan vigente, fecha de alta y grupo validado contra deporte |
| Cobros | COB-05 y COB-09 verificadas en main e921e5d: 15 cobros por navegador. FIN-02 verificada: medios correctos en recibos. Evidencia COB-05-CIERRE-2026-09-11.md |
| Caja | A25 CERRADA: apertura declarada, cajón compartido, arqueo/conteo y separación cambio/entrega. Verificado por Gemini 06/10, contrastado por Claude. No desplegada |
| Cashflow | Integra cajas validadas y saldo inicial; definición FIN-04 cerrada 22/09; aplicación del contrato de Reportes pendiente |
| Clases | FIN-10 implementada y probada: edición atómica con control de profesores/presentes, fechas y liquidación cerrada; migración pendiente de deploy |
| Liquidaciones | Generacion, cierre, pago, recibos y cancelacion. FIN-05 corregida el 11/09: dos pagos a la vez de la misma liquidacion ya no registran dos egresos. FIN-06 implementada y probada: comisión histórica y porcentaje congelado en BD. FIN-13 cerrada 17/09: liquidación por duración. FIN-12 cerrada 21/09: cancelación de liquidación cerrada no pagada por ADMIN con auditoría, desbloqueo de asistencias y concurrencia protegida contra pago. Migración pendiente de deploy |
| Carga inicial | P1 implementada el 05/10: plantilla vacía, revisión sin escritura, informe Excel, carga atómica y Deshacer. Importadores de consola antiguos y exportador retirados el 13/10/2026 (T2). Rige el importador web único (`/sistema/primera-carga`). |
| Dump | Fuera de Git e ignorado; `DemoSeeder` ya no lo exporta |
| PHP | `composer audit` sin avisos el 08/09 |
| JavaScript | SEG-01: Axios retirado y lock actualizado; audit cero, build y 154 pruebas/920 aserciones en copia aislada el 11/09. Sin deploy |
| CSP | Report-only con endpoint /csp-reporte; corte T36 17/10: 8 bloques script en 8 vistas y 8 manejadores inline, contados en fuente. Detalle y Resumen de caja sin código incrustado. |
| Diseño | Protegido por `AGENTS.md` y hook de commit |

## 5. Lo cerrado del 5 al 7 de septiembre

- Suite migrada de SQLite a MariaDB.
- Dump retirado y segunda puerta de exportacion eliminada.
- Falla de copia nocturna a Drive corregida y respaldos revalidados.
- Cloudflare configurado con confianza acotada.
- Precio de planes y frecuencias obligatorias protegidos.
- Descuento de primera cuota limitado al mes de alta.
- Importador de deuda inicial validado y carga de prueba preparada.
- Rubros `Cuotas` y `Sueldos` protegidos en la aplicacion.
- Acceso y menu de Cobranza corregidos para OPERATIVO.
- Menu reorganizado y desplegado en `9fdd03d`.
- Base del servidor preparada y cuenta ADMIN de Vanina creada.

## 6. Orden de trabajo

FDS-01 y FDS-02 cerrados. Alertas reales recibidas el 09/09. El orden restante es:

1. **FDS:** FDS-04 verificada el 11/09. FDS-03 pausada hasta redefinir su objetivo.
2. **COB-05:** verificada junto a COB-09 sobre main e921e5d. Bloque 1 verificado.
3. **FIN:** FIN-02 verificada; FIN-03 implementada, pendiente revision cruzada. Siguen historia, balance y
   concurrencia financiera.
4. **SEG:** npm, sesiones, despliegue, recuperacion, alertas, CI y CSP.
5. **PRU:** recorrido humano completo, proceso mensual y gate.
6. **ENT:** pedidos concretos de Carlos.
7. **POS:** reportes y evolucion posterior; no bloquean por aparecer en una evaluacion.

Los criterios y dependencias estan en el plan vigente. La version HTML marcable es
`docs/07-evaluacion/PLAN-TRABAJO-CARLOS-v2026-09-08.html`.

## 7. Decisiones pendientes de Carlos

- FIN-04 decidido 22/09: saldo acumulado separado de resultados/proyecciones; gastos del club no se descuentan por deporte. Consulta financiera V3 aplicada localmente 09/10; clasificación de antecedentes y otras pantallas de Reportes pendientes.
- Como resolver revisiones con parcial, observaciones e importe historico.
- Limites temporales de movimientos manuales.
- A2/B2: enmienda del 23/09 implementada localmente: cuota en alta y estado basado en
  deuda, sin exigir pagos previos. Pendiente de verificación independiente y despliegue.
- ENT-01 resuelta: cargos separados, prioridad de inscripción, sin comisión.
- Logo, paleta y favicon del club.
- ~~Liquidación por hora~~: CERRADA 17/09 — tarifa × minutos / 60, tarifa y minutos congelados al liquidar; pantalla y recibo autorizados muestran formato "1 h 20 min" y centavos (FIN-13).

- ~~**Contradiccion registrada 17/09 (FIN-08)**~~ **resuelta 21/09:** la revision de cobranza quedo abierta a ADMIN y OPERATIVO y "Inactivo" no condona desde el 19/09. Condonar sigue solo ADMIN.

## 8. Riesgos y limites conocidos

- El rollback del deploy no revierte migraciones.
- Preflight corre despues de reabrir el sitio.
- La restauracion probada importa SQL; no reconstruye sola archivos y configuracion.
- El control historico del restore compara conteos, no contenido financiero completo.
- Un fallo de Drive conserva la copia local y salida 0; desde el 09/09 produce
  alerta diferenciada por Better Stack y Telegram, probada y recibida.
- `MoneyLockingTest` verifica texto del codigo, no concurrencia real.
- Cambiar contraseña no revoca por si solo sesiones y `remember_token`.
- Los errores de recibos pueden devolver el mensaje tecnico de la excepcion.
- Aritmetica monetaria usa `float` en parte del dominio; el daño no esta demostrado.
- `View::composer('*')` ejecuta una consulta global para el badge de clases.

## 9. Contradicciones abiertas

**A56 — cerrado por Codex CyE 06/10/2026:** signo real del contraasiento y gasto normal,
totales y marco 375 verificados en código, HTTP y navegador. Inicial $10.000, cobro y
devolución compensados, gasto $2.500, balance $7.500. Ambos seguimientos 30/72.
[Informe y límites](../06-pruebas/PRU-02/VERIFICACION-A56.md). Sin nuevas pruebas permanentes ni deploy.

**A4/A5 — cerrados 06/10/2026:** verificados de forma independiente por Gemini en pantalla interactiva y código (aviso superior accesible sin scroll, confirm en menú y Cancelar, beforeunload, y rechazo en servidor de profesores ajenos o inactivos). 11 pruebas pasando. [Informe de verificación](../06-pruebas/PRU-02/VERIFICACION-A4-A5.md). Sin despliegue ni base del club tocada. Pasan a 29 cerrados de 72.


FIN-11, 12/09: reproducido y corregido cancelar/validar en ambos órdenes
(dejaba pago ANULADO y deuda pagada 0 con 10.000 en cashflow). Corregidas también
las esperas tardías de cobrar/cancelar y validar/cobrar. Seis casos pasan, 103
aserciones aisladas. Pausa levantada el 13/09: Codex reejecutó la suite completa
en wings_testing, 187 pruebas / 1206 aserciones, sin fallas. Implementada y probada;
sin deploy. Evidencia: ../06-pruebas/FIN-11-CONCURRENCIA-2026-09-12.md.

FIN-07: pausa resuelta por Carlos el 12/09. Solo ADMIN perdona el saldo pendiente,
conservando original, pagos e imputaciones. Condonación transaccional con bloqueo
de la deuda, compartido con el cobro. Tres casos con conexiones reales y prueba
negativa sin bloqueo. Contrato Wings-Contrato-Condonacion-V1.md. Sin deploy.

FIN-03: freno documental resuelto el 11/09. Carlos autorizo Recibos V2 con
FIN-02 y FIN-03; V1 queda como antecedente. No se reconstruyen periodos ya
borrados en anulaciones antiguas. Migracion probada solo en base descartable.

COB-03 y total (COB-06), verificados el 10/09 en `cob-total` (`5238825`), no mergeada:
ambos casos, septiembre existente/virtual, muestran y registran $29.600 con cartel
70%; septiembre conserva $18.000 pendientes. Sin descuento, $38.000 correctos.
Cadena financiera y PDF verificados; suite de esa rama: 133 pruebas, 726 aserciones.
El defecto sigue en main hasta integrar; no confundir verificacion con despliegue.
Evidencia: `docs/06-pruebas/COB-03-VERIFICACION-2026-09-10.md`.

| Tema | Estado real | Proxima accion |
|---|---|---|
| `dia_generacion_deuda` editable | Confirmado 09/09: existe como fila de configuracion y **ningun codigo la lee**. El scheduler usa dia 1 fijo en `routes/console.php:12` | Definir si gobierna la tarea o se retira de pantalla |
| Alumno sin plan en la corrida mensual | `GenerarDeudasMensualesCommand:77-81` lo saltea: no genera deuda, no entra a revision, solo un `warn` que muere en el cron | Que caiga en la cola de revision con motivo propio |
| Recalcular una liquidacion puede cambiar el total de una ya cerrada | `LiquidacionService::recalcularLiquidacion()` chequea si esta cerrada **afuera** de la transaccion y sin tomar la fila. Si otra pestaña la cierra en ese instante, el recalculo cambia el total igual. `cerrarLiquidacion()` y `eliminarLiquidacion()` tienen el mismo patron, con daño nulo o que requiere tres acciones a la vez. El doble clic lo frena el anti doble envio de `ds-app.js`; queda el caso de dos pestañas o dos personas | Encontrado en el barrido de FIN-05. Corresponde a FIN-11 |
| `pagos.monto_base` se guarda mal | `crearPago()` lo reconstruye dividiendo lo cobrado por el porcentaje. Con una seña en el mes de alta da 10.000 / 0,7 = 14.285; con el mes de alta y otro mes en el mismo cobro divide tambien el que no tenia descuento. **Nadie lo lee hoy**: ni pantallas, ni recibos, ni reportes. Es una trampa para el rediseño del recibo, que querria mostrar el precio sin descuento | Definir que tiene que valer en un cobro con seña o con varios meses antes de que algo lo use |
| Descuento a un alumno de carga inicial cobrado en su propio mes de alta | `calcularReglaPrimerPago()` solo exige que el mes de alta este entre los periodos cobrados. Un alumno importado con deuda inicial de su mes de alta recibe el descuento al pagarla. La prueba existente solo cubre cobrarle **otro** mes | Carlos define si un alumno traido de la carga inicial puede recibir descuento de primer pago alguna vez |
| Wings no tenía arqueo | Relevamiento previo 06/10 confirmó que faltaban tanto inicio como conteo/diferencia; no era sumar a un arqueo existente | A25 implementada después de decisiones de Carlos. [Entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A25.md). CERRADO (verificado por Gemini 06/10); no desplegada |
| Resultado y saldo de Cashflow | A10 muestra resultado del período sin sumar saldo inicial | Aplicado localmente 08/10, aspecto aprobado por Carlos; intervalos/cálculo a verificar. Saldo acumulado financiero aplicado localmente en V3 B12, 09/10; falta clasificación de antecedentes y las demás pantallas POS-01 |
| Estado minimo de entrega | El club ya carga datos reales | FDS-03 pausada por Carlos el 09/09; redefinir, no limpiar |
| Tope de 1200px en guia de diseño | `app.css` no lo implementa | Decidir guia o implementacion; no tocar sin autorizacion |

## 10. Fuentes vigentes

| Necesidad | Ruta |
|---|---|
| Plan para IA | `docs/07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md` |
| Plan marcable para Carlos | `docs/07-evaluacion/PLAN-TRABAJO-CARLOS-v2026-09-08.html` |
| Evaluacion Codex | `docs/07-evaluacion/Evaluacion Codex 8-9-26.md` |
| Evaluacion Claude | `docs/07-evaluacion/Evaluacion Claude 8-9-26.md` |
| Bitacora Codex | `docs/00-estado/LOG-CODEX.md` |
| Bitacora Claude | `docs/00-estado/LOG-CLAUDE.md` |
| Acciones de Carlos | `docs/00-estado/CHECKLIST-CARLOS.md` |
| Contratos | `docs/02-contratos/` |
| Pruebas funcionales | `docs/06-pruebas/` |
