# Wings — Bitácora activa de CODEX

[Antecedente archivado al tomar T42](../99-archivo/bitacoras/2026-10-20/LOG-CODEX-TOMA-T42-ANTECEDENTE.md).

[Antecedente archivado al reverificar T52](../99-archivo/bitacoras/2026-10-20/LOG-CODEX-T52-REVERIFICACION-ANTECEDENTE.md).

[Antecedente archivado al corregir T61](../99-archivo/bitacoras/2026-10-20/LOG-CODEX-T61-TRANSFERENCIA-ANTECEDENTE.md).


[Entradas anteriores archivadas al continuar T61 desde CyE](../99-archivo/bitacoras/2026-10-20/LOG-CODEX-CONTINUIDAD-CYE.md).


[Dos antecedentes archivados intactos al verificar T52](../99-archivo/bitacoras/2026-10-20/LOG-CODEX-T52-ANTECEDENTES.md).

[Antecedente archivado intacto al completar el recorrido T61](../99-archivo/bitacoras/2026-10-20/LOG-CODEX-T61-RECORRIDO-ANTECEDENTE.md).

[Antecedente archivado al completar FIN-09](../99-archivo/bitacoras/2026-10-20/LOG-CODEX-T61-FIN09-ANTECEDENTE.md).

[Antecedentes archivados al entregar T42 y cerrar T52](../99-archivo/bitacoras/2026-10-20/LOG-CODEX-T42-T52-ANTECEDENTES.md).

[Antecedente archivado al tomar T43](../99-archivo/bitacoras/2026-10-20/LOG-CODEX-TOMA-T43-ANTECEDENTE.md).

[Antecedentes archivados durante T43](../99-archivo/bitacoras/2026-10-20/LOG-CODEX-T43-ANTECEDENTES.md).

[Antecedente archivado durante la corrección de interacción T43](../99-archivo/bitacoras/2026-10-20/LOG-CODEX-T43-UX-ANTECEDENTE.md).

[Antecedente archivado al publicar la bitácora](../99-archivo/bitacoras/2026-10-20/LOG-CODEX-PUBLICACION-FINAL-ANTECEDENTE.md).

[Antecedente archivado intacto al verificar T50](../99-archivo/bitacoras/2026-10-21/LOG-CODEX-T50-ANTECEDENTE.md).

[Dos antecedentes archivados intactos al corregir T61](../99-archivo/bitacoras/2026-10-21/LOG-CODEX-T61-SEGUNDA-ANTECEDENTES.md).

## 2026-10-21 — Codex CAB — T61 segunda vuelta, TRASPASO solo del sistema

Pedido ejecutado aislado en `codex/t61-segunda-vuelta`, base 2c7cebe; toma 1668ca4. Carpeta principal con cambios ajenos T50 preservada.
TRASPASO retirado de alta/edición comunes y rechazado en caja, correcciones, reflejos y pagos. Transferencia aprobada y sus 13 métodos de prueba intactos; sin reparación ni migración histórica.
Inventario previo de lectura en única base propia wings_testing_codex: cero subrubros comunes TRASPASO y cero movimientos sueltos según las consultas guardadas. No se consultó club/producción/prueba/servidor.
Tres regresiones nuevas: rojas antes; 16/198 verdes después, 14,40 s. Área cobros 125/851 verde. Primera completa descartada por error propio corregido; final 1020 aprobadas/6 omitidas, 8846 aserciones, 934,96 s, salida 0, sin fuentes cambiadas ni otras suites detectadas.
Ocho capturas reales antes/después: 1366 y móviles medidos 360 (no 375); recaptura 375 pendiente por bloqueo de navegación local. Solo cambia contenido del selector; sin CSS/JS nuevo. Revisión Carlos pendiente, aprobación de transferencia conservada.
[Segunda vuelta e inventario](../06-pruebas/PRU-04/IMPLEMENTACION-T61.md#segunda-vuelta--h1-a-h3) · [Capturas observadas](../06-pruebas/PRU-04/REVISION-T61-SEGUNDA.html). Tabla Carlos/Claude y control de Claude intactos.
Entrega a_verificar para Claude en rama aislada; pendiente visual 375/Carlos registrado, no autocerrada. Inventario posterior estricto también cero. Sin despliegue ni push manual.
## 2026-10-21 — Codex CAB — T50 verificada y devuelta; higiene de evidencia

Verificación independiente de 3704063, sin corregir producto. H1: inscripción futura suma deuda antes de ingreso; H2: fecha mal formada en edición da 500; H3: Chrome calcula límite con otro reloj que Wings.
Completa propia: 1017 aprobadas/6 omitidas, 8791 aserciones, 984,61 s, salida 0; sin otras suites observadas. Controles independientes: 16/115, dos fallas reales; desfase comprobado en Chrome local tras build, consola observada limpia.
Base exclusiva wings_testing_codex y datos ficticios; fuente sin cambios. T34 generado restaurado. Tabla Carlos/Claude intacta. Continuación: tipeo real e incremento nativo comprobados; Guardar rechaza en castellano. Calendario emergente no observable.
[Informe y evidencia](../06-pruebas/PRU-04/VERIFICACION-T50-CODEX.md). Devuelta a Gemini por tablero; sin despliegue.
Carlos pidió higiene y publicación solo de lo propio: se retiran cuatro auxiliares duplicados/de diagnóstico; se conservan arnés reproducible y evidencia final. Dos commits locales ajenos de Gemini no se publican por arrastre; entrega aislada desde origin/main.
Próximo paso: Gemini corrige los tres hallazgos y entrega a verificación independiente.

## 2026-10-20 — Codex CyE — Entrega publicada y limpieza final

Carlos pidió actualizar la bitácora y subir todos los archivos propios.
T43 publicada en main: implementación b8e1ac1e y constancia e51ced7a; remoto comprobado por ls-remote.
Aprobación de Carlos: «Ahora esta OK». Completa 1017 aprobadas/6 omitidas y dirigidas finales 18/134 verdes; lógica pendiente de Claude, sin despliegue.
Revisión del estado local: solo quedaban dos auxiliares propios de preparación en storage, sin trabajo de producto pendiente; se retiran tras comprobar su contenido. Código, pruebas, capturas, PDF e informes ya versionados.
Esta actualización es documental; se revisan enlaces y diff, sin repetir suite ni migraciones.

## 2026-10-20 — Codex CyE — T43, corrección aprobada por Carlos

DU-36: origen por parte y decisión auditada del dueño; pendientes/rechazadas excluidas del confirmado/recibo. Cierre y pago bloqueados hasta resolver; cálculo original, ajuste absoluto A23 e históricos conservados.
15 regresiones nuevas; dirigidas previas 39/225 verdes. Rápida: 895 aprobadas/6 omitidas, 7147 aserciones, 186,82 s, salida 0 (corte 1016); completa final 1012 aprobadas/6 omitidas, 8743 aserciones, 740,41 s, salida 0, corte 1018, wings_testing_codex y una sola suite activa.
Navegador real 1366/375: decisiones por formularios, botones 96 × 32, sin desbordes. PDF real: agosto/septiembre incluidos, julio rechazado fuera; pago ficticio -60000.00 en base aislada de capturas. Adelanto octubre separado, $32.000.
[Entrega](../06-pruebas/PRU-04/IMPLEMENTACION-T43.md) · [Galería](../06-pruebas/PRU-04/APROBAR-T43.html). Carlos rechazó ubicación de Validar: corregida a la derecha, Rechazar a la izquierda, sin huecos; 96 × 32 en 1366/375. Prueba manual local /liquidaciones/4 realizada por Carlos; aprobó la corrección: «Ahora esta OK». Carlos autorizó enviar LIQ-002: e/h y fuente releídos en LIQ v5, a alineado con DU-19 y releído en v6; evidencia en la entrega.
Dirigidas finales 41/240 verdes (10,82 s); historial y saldo por pagar comprobados. Sintaxis correcta y vistas compilan. Contrato/estado/contador actualizados, sin despliegue ni escritura en bases del club; evidencia T34 ajena preservada fuera de lo propio. Corrección visual: rápida 897/6 omitidas, 7162 aserciones, 300,20 s, salida 0; dirigidas 15/96 verdes. Publicada b8e1ac1e en main, remoto comprobado; sigue revisión independiente de lógica por Claude.

Corrección pedida por Carlos: cabecera separada; POST JSON sin GET de show ni consulta del contador del menú, pie Total confirmado. 17/127 dirigidas verdes; build y sintaxis correctos; completa JSON 1017 aprobadas, 6 omitidas, 8791 aserciones, 844,65 s, salida 0; dirigidas finales 18/134 verdes y vistas compiladas. Navegador: tarjeta en y=380,59375 antes/después, total 40.000→60.000 y texto sin guardar conservado. Capturas escritorio y cabecera 375 guardadas. Work deshabilitó el acceso durante la última interacción móvil; no se declara ese clic verificado. Carlos aprobó después de probar: «Ahora esta OK». Carlos autorizó expresamente el push después del rechazo automático inicial. Publicada b8e1ac1e en main y comprobada por ls-remote; lógica para Claude, sin despliegue.

## 2026-10-20 — Codex CyE — Toma T43 tras entregar T42

T42 publicada en 6c8602f3, completa 997/6 omitidas verde, a verificar por Claude. T52 verificada y cerrada; T61 conserva aprobación visual y control de lógica por Claude.
T43 tomada por script: persistir origen de cada parte y decisión del dueño sobre atrasadas; no incluir las pendientes/rechazadas en el recibo. Adelantos siguen DU-19.
Conservar cálculo original, importe final ajustado con su auditoría y documentos cerrados/pagados; decisiones con responsable y fecha. Sin reconstrucción arbitraria de históricos.
Pruebas de tres orígenes, mezcla, anulación, cerradas y cambio de año; completa única en wings_testing_codex. Sin servidor ni despliegue.
Capturas reales de liquidación/recibo 1366/375 y APROBAR-T43.html. No commitear vistas hasta OK de Carlos; implementación pendiente al tomar.

## 2026-10-20 — Codex CyE — T42 implementada, a verificar por Claude

DU-19: cada parte de cuota liquida en max(mes cobro, mes pagado), con asistencia del mes liquidado; ingreso adelantado en Reportes/Inicio por mes pagado.
Dinero, fechas y saldos de Caja/Cashflow conservados. Carlos: «Sí, conservar el mes original solo en esos casos» no identificables. Captura de imputaciones e historia sin reescritura.
19 regresiones nuevas; dirigidas finales 29/150 verdes. Completa final 997 aprobadas/6 omitidas, 8652 aserciones, 1242,09 s, salida 0, wings_testing_codex, corte 1003.
Primera completa falló en una selección de prueba por primer pago, corregida por ID; segunda frenó por contador al entrar H5 T52. Salidas preservadas; sin atribuir causas ajenas.
COB-012 e/h vivo y repo actualizados con autorización expresa; relectura ArtifactData versión 10 comprobada. Copia LIQ-002 corregida por DU-19; control del dueño sigue en T43.
[Entrega y evidencia](../06-pruebas/PRU-04/IMPLEMENTACION-T42.md). Contratos/estado/contador/tablero al día. Sin vistas/CSS ni despliegue. Sigue T43; lógica T42 para Claude.

## 2026-10-20 — Codex CyE — T52 H5 verificada y cerrada

Revisión de e1e7c499: el scope exige siempre ingreso y llegada al grupo; Codex no modifica producto.
Repetición HTTP propia: ingreso 01/11, cambio 20/10, clase 20/10; no aparece, POST 422 y sin marca. H1–H4, tres roles, plan diferido y cerradas/pagadas HORA/COMISIÓN preservados.
9 independientes + 12 regresiones: 21 aprobadas/139 aserciones, 19,79 s, salida 0, wings_testing_codex; consultas 12→12. Completa compartida T42/T52: 997/6 omitidas verde.
Visual previo Carlos conservado («Las vistas están OK»). Históricos migrados de modo conservador, sin prometer fecha real reconstruida.
[Informe y datos](../06-pruebas/PRU-04/VERIFICACION-T52-CODEX.md). Cerrada por script: hizo Claude, verifica Codex. Sin despliegue ni migrar el club.

## 2026-10-20 — Codex CyE — T61, confirmación FIN-09

El formulario nuevo omitía confirmar fechas de un mes anterior, exigido por Caja/Cashflow V4 §3.6. Corregido antes de continuar T42.
Primer envío valida y avisa sin escribir; Confirmar guarda ambas patas con la fecha real y usa el aviso existente al dueño. Reenvío secuencial sin duplicar transferencia ni aviso.
13 pruebas dirigidas/143 aserciones verdes; completa 977 aprobadas/6 omitidas, 8541 aserciones, 697,65 s, salida 0, wings_testing_codex, corte 983. Sin otra suite activa.
Capturas reales 1366/375 del estado adicional; Carlos aprobó: «Apruebo el aviso». Formulario principal y Rubros conservan «Apruebo el visual corregido».
[Entrega y evidencia](../06-pruebas/PRU-04/IMPLEMENTACION-T61.md). A verificar lógica por Claude, sin autocierre ni despliegue. Continúa T42 y después T43.
