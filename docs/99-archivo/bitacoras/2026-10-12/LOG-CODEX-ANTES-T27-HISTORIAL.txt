# Wings — Bitácora activa de CODEX

## 2026-10-12 — Codex CAB — T27 etapa 2, implementación y comprobación

Carlos aprobó visual y reglas: editar con historial, eliminar con contraasiento, motivo opcional y cualquier mes.
Reporte del mes original; cajas validadas/liquidaciones pagadas solo consulta; sin pagar, edición existente.
Original, contraasiento e historial atómicos; versión/clave impiden sobrescritura y reenvío duplicado.
Historial corregido: egreso directo ya no aparece como entrada; 13 pruebas rojas antes del cambio.
Propias 16/168 y dos conexiones 2/24 verdes; captura Laravel 1/33; 53 PNG, marco360/login, 36 visitas sin fallos.
Script de Cashflow fuera de Blade; guardián CSP de 13 a 12. Suite propia 687/2, 6588 aserciones, 536,25 s; 689.
[Informe y límites](../06-pruebas/PRU-04/IMPLEMENTACION-T27.md). Solo wings_testing_codex; sin sitio de prueba/reloj/despliegue.
Sigue: Carlos aprueba capturas finales; después otro agente verifica las reglas. No se autocierra.

## 2026-10-12 — Codex CAB — T27, etapa 1: propuesta de entrada única

Relevados directos, cobros/inscripción, aportes/retiros, liquidación y cajas.
Propongo Cashflow: acceso por fila y cajas todavía editables en sección separada.
Carlos pidió íconos de acciones en la tabla, sin botón Corregir; aplicados también a las tarjetas móviles.
VALIDADA no admite rechazar hoy; liquidación pagada protegida. Decisiones pendientes en la propuesta.
Maqueta Blade aislada, controlador/datos de Laravel reales; no rutas ni correcciones nuevas en producción.
Ensayo propio: 1/19, financieros iguales tras GET; 403 de otros roles. Chrome: 20 visitas/0 fallas, 32 PNG.
Escritorio/marco360 y login. Sin base del club, sitio de prueba/reloj/despliegue.
[Propuesta, preguntas y capturas](../06-pruebas/PRU-04/PROPUESTA-T27.md). Etapa 2 no empezada.
Carlos aprobó la propuesta visual (12/10): «Sí, apruebo la propuesta visual».
Edición: «Modificar el registro y conservar historial», anterior/nuevo.
Eliminación: «Conservarlo y crear un asiento en contra». Motivo opcional en ambos casos.
Cualquier mes anterior, sin límite. Caja validada: «Solo consulta».
Pendientes liquidación pagada y mes cerrado/fecha del contraasiento.
T27 en_curso, tiene Carlos: responde las decisiones restantes antes de implementar.

## 2026-10-12 — Codex CAB — T30, porcentaje junto al rubro en celular

Carlos marcó el desborde de la leyenda y pidió porcentaje junto al título.
Nombre/porcentaje arriba, importe debajo a 360; escritorio conserva porcentaje junto al monto.
Misma fórmula y formato; texto del importe móvil a 0,85 rem para que entre completo.
Control nuevo de límites de la leyenda y fuentes cargadas: detectó dos fallos anteriores.
Revisión: 563 controles/0 fallas, 162 PNG y login; respuestas reales en base propia.
Suite propia: 669/2, 6395 aserciones, 530,97 s; 671. Sin sitio de prueba/reloj/despliegue ni cierre.
[Captura de celular](../06-pruebas/PRU-04/evidencia-t30/revision-porcentajes-ultima/capturas/reportes-millones-marco-360.png).
Carlos aprobó todas las vistas: «El resto de las vistas OK» y «Esta OK» (12/10). Solo falta Claude para la regla. [Informe](../06-pruebas/PRU-04/IMPLEMENTACION-T30.md).

## 2026-10-12 — Codex CAB — T30, centavos y formato compartido, a verificar

Inventario completo; $48,000.50 y siempre dos decimales por decisión de Carlos.
Formato común PHP/JS conectado a pantallas, avisos, PDF nuevos, mail/Telegram y Excel numérico.
Gráficos y PDF ya emitidos conservados; cálculos/datos/validación T24 sin cambios.
T28 independiente publicado primero en e1e73d8; T30 después, solo formatos de Cashflow.
Suite propia 669 aprobadas/2 omitidas, 6395 aserciones, 585,49 s; 671 pruebas.
Matriz 39 pantallas, 162 PNG: 563 controles/0 fallas; T24: 1351/0, 100 PNG. Marco360/login y adelantados.
[Inventario, visor y límites](../06-pruebas/PRU-04/IMPLEMENTACION-T30.md). No se cierra ni despliega.
Carlos aprueba capturas; después otro agente verifica regla. Sin sitio de prueba ni reloj.

## 2026-10-12 — Codex CAB — T24, revisión visual aprobada por Carlos

Carlos pidió corregir adelantado desbordado, íconos, desglose y botones móviles a la derecha.
Adelantado arriba del mes a 360; inscripción/cuotas en dos líneas, cuota con dos decimales.
Íconos en movimientos/entregado/configuración; acciones de campos/reglas y liquidación a la derecha.
Repetición real detectó que el cierre general retiraba el aviso T24; ahora respeta su temporizador.
Suite propia 641/2, 6075 aserciones, 448,36 s; 643 sin cambio de cantidad. Navegador 1351/0 fallas, 100 PNG.
Build/sintaxis/vistas correctos; respuestas reales de Laravel y assets fijos, marco360/login. Sin sitio de prueba/reloj/deploy.
[Propuesta, evidencia y límites](../06-pruebas/PRU-04/IMPLEMENTACION-T24.md). Carlos aprobó `be5ad9a`: «Ahora OK» (12/10). Regla a verificar por Claude; T24 sigue a_verificar.
Archivo anterior íntegro: [corte antes de esta revisión](../99-archivo/bitacoras/2026-10-12/LOG-CODEX-ANTES-T24-REVISION.txt).

## 2026-10-11 — Codex CAB — T24 implementada, a verificar por Carlos y después otro agente

16 pantallas/modos con Rectificar T23, filas nuevas de Grupos/adelantados y alta de plan por pedido directo.
Fuentes reales y rutas leídas tras pull actualizado; lector Excel ejecutado aislado, sin base.
Carlos: coma decimal aceptada; mostrar 48,000.50; tercer decimal recortado a dos, rojo y aviso de 4 s sin bloquear.
Componente/parser comunes y regla PHP; guardados directos sin redondear inválidos. Excel, porcentajes y cálculos conservados.
Coordinación T23: campo de rectificación incluido; cambios T24 del controlador compartido entraron en 03aaf11 de Gemini.
Suite completa propia 641/2, 6075 aserciones, 428,69 s; 643 pruebas. Navegador 1292 controles/90 PNG, assets fijados.
18/18 campos anteriores detectan regresión sobre dd62c64; 57 pedidos previos registrados. Sintaxis/build/vistas correctos.
[Inventario, visor y límites](../06-pruebas/PRU-04/IMPLEMENTACION-T24.md). T24 a_verificar, Carlos aprueba imágenes y después otro agente regla; sin cierre ni deploy.
Publicar solo archivos propios; trabajo ajeno de Historial/permisos preservado. Sin sitio de prueba ni reloj por Codex.

## 2026-10-10 — Codex CyE — T14 Día 3 recorrido, a verificar por Claude

Objetivo: feriado simulado lunes 12/10; seis tareas por menú/botones, expectativas antes de actuar.
Sandra cancela las dos de las 16; admin Patín Intermedias 17–18; motivo y Cancelar esta, sin serie.
Agenda 3 → 1 → 0; Sin asistencia 33 antes/después, dos páginas sin fechas del 12.
Frena: canceladas de hoy ocultas en Clases aun con Cancelada + fecha, Sandra/admin/Lucía.
Guardar deshabilitado en fichas canceladas vistas; ADMIN ve Activar, no se pulsó; Sandra sin comprobar.
Inicio/Reportes ingresos $164.000/deuda $2.010.000/por pagar $0; Cobranza $2.010.000, 65/0/22/13.
Sueldos Gaitán/Salinas/Quintana $0; Mariela $18.000/6 asistencias. Bloqueos de ingreso, luego pudieron entrar.
Carlos decidió suplencias y Abrir en feriado correctos; fuera de hallazgos. Sin código/base, arreglos, cobros ni reloj.
Chrome real por clics, integrado falló; cinco capturas de fallas. [Informe único](../06-pruebas/PRU-04/DIA-03.md).
T14 abierta, solo paso actualizado; Claude verifica. Conservar tres cancelaciones; cuenta/navegador propios cerrados.

## 2026-10-10 — Codex CyE — T14 Día 2 recorrido, a verificar por Claude

Objetivo: domingo simulado 11/10; seis tareas por menú/botones, expectativas antes de actuar.
Inicio/Cobranza/Reportes: ingresos $164.000, deuda $2.010.000; cuotas $159.000/inscripción $5.000.
65 al día/0 en plazo/22 morosos/13 deudores; ayer 65/22/0/13, captura contrastada; dos fichas solo octubre Moroso.
Hoy 0 clases; 33 históricas sin asistencia, antes 32. Mariela conserva sábado 6 presentes/2 ausentes.
Frena: Mariela abre clase de Lucía con 26 alumnos/Guardar; no guardó. Molesta: domingo pide Abrir.
Sandra entró tras unos diez minutos de bloqueo; sábado VALIDADA, domingo $0/0 cajas; Cobrar desvía a Apertura.
Chrome real con clics automatizados; integrado falló. Sin código/base de Wings, cobros, aperturas, arreglos ni reloj.
[Informe único y cuatro capturas de fallas](../06-pruebas/PRU-04/DIA-02.md). Solo documentación, sin suite ni deploy.
T14 sigue en_curso: solo paso actualizado; Claude verifica. Día 3 lunes 12 feriado, tres clases previstas para cancelar.
Archivo anterior íntegro preservado; publicar únicamente archivos propios. Se salió de la cuenta y cerró el navegador propio.

## 2026-10-10 — Codex CyE — T16 tercera vuelta aprobada

Objetivo: verificar b8ede87 sobre copia fija aa33399; solo wings_testing_codex.
12 controles originales/225 aserciones, ocho adicionales/269 y ocho cruces propios/220 aprobados.
APORTE/RETIRO, alta/edicion, ambos ordenes: espera InnoDB comprobada; uno guarda y otro rechaza.
16 pedidos HTTP terminan en 0,263–0,943 s; ocho posteriores pasan; sin pares invalidos ni transacciones abiertas.
Suite completa 611 aprobadas/2 omitidas, 5087 aserciones, 637,19 s; preparacion del verificador corregida.
Carlos: «No, verificar los guardados desde Wings»; consola excluida. Sueldos NEGOCIO reservado; API deshabilitada.
Sin arreglos de Wings, visita al sitio ni deploy; MCP caido, fuentes reales leidas. [Informe](../06-pruebas/PRU-04/VERIFICACION-T16.md).
Tablero cerrado, hizo Claude/verifica Codex/tiene nadie; aspecto aprobado por Carlos; no queda paso de T16.
Archivo anterior integro preservado; publicacion solo de archivos propios.

## 2026-10-10 — Codex CyE — T16 segunda vuelta devuelta por concurrencia

Objetivo: verificar fcdc8f2; copia publicada fija d4f18d6, solo wings_testing_codex.
Dos fallas originales corregidas: 12 controles/225 aserciones aprobados; ocho adicionales/269 aprobados.
Altas, campos falsificados, padre cruzado/toggle/inactivos y cambio valido tras reclasificar comprobados.
Dos procesos con pedidos HTTP aceptados dejan EGRESO + APORTE; prueba fallida, 10 aserciones, evidencia concreta.
Suite completa: 611 aprobadas/2 omitidas, 5087 aserciones, 785,65 s. Errores de preparacion del auxiliar corregidos.
Nombres nuevos en INGRESO quedan NULL para revision del admin; migracion repetida no duplica ni pisa.
Demo: seis sueldos NULL; rechazo production probado en base descartable. Recomiendo arreglo separado, no bloquea T16.
Sin arreglos de Wings, visita al sitio ni deploy. [Informe actualizado](../06-pruebas/PRU-04/VERIFICACION-T16.md).
Tablero devuelto, tiene Claude/verifica Codex. Sigue: proteger el cruce y volver a verificar; aspecto aprobado.

[Archivo íntegro antes de etapa 2 T27](../99-archivo/bitacoras/2026-10-12/LOG-CODEX-ANTES-T27-ETAPA2.txt).
