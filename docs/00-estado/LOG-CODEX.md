# Wings — Bitácora activa de CODEX

## 2026-10-05 — Codex CAB — A4/A5 hechos, a revisar

Carlos aprobó aviso superior y profesores activos del deporte de la clase; pidió Hecho (Cx), a revisar.
A4: resumen visible, datos/plan conservados y advertencia de salida; A5: filtro y rechazo servidor.
JavaScript propio/Vite; sin CSS ni permisos nuevos. Backend inicial A5 entró con 8869263 de Claude.
Previas: 7 rojas/2 verdes, 27 aserciones; específica final: 11/39 verdes.
Suite propia wings_testing_codex: 437 aprobadas, 1 omitida, 2949 aserciones; última vuelta 129,48 s. PHP/Blade/build OK.
Diez capturas reales escritorio/375; Chrome mostró confirm al salir, pero se trabó al cancelar: revisor debe comprobar retención y cierre.
[Entrega y capturas](../06-pruebas/PRU-02/IMPLEMENTACION-A4-A5.md); ambos seguimientos y cuatro estados actualizados.
Sin base del club ni servidor tocados; no desplegado ni cerrado. Sigue control independiente.

## 2026-10-05 — Codex CAB — A4/A5 frenados por instrucción de cierre contradictoria

Main sincronizado en `6ebfe83`; árbol limpio al iniciar y continuidad revisada.
El prompt exige CERRADOS por el autor; AGENTS.md §6a y DEFECTOS.md exigen control ajeno previo.
Consulta: entregar IMPLEMENTADOS pendientes de revisión en ambos seguimientos y cerrar después del control.
Freno registrado en ESTADO-ACTUAL §9; A4/A5 siguen abiertos, sin modificar sus contadores.
MCP probado: transporte cerrado; contraste documental hecho contra archivos reales.
Sin aplicación, vistas, pruebas, datos ni servidor tocados; no se corrió suite por documentación.
Siguiente: Carlos aclara el cierre; después propuesta visual con capturas antes de pedir autorización.

## 2026-10-05 — Codex CAB — P1 implementada, pendiente Gemini

Carlos aprobó maqueta y aclaró producción sin alumnos/deudas/pagos; levantado el freno previo.
Plantilla vacía, listas reales, revisión completa sin escritura y Excel original con AM Errores.
Carga atómica de lo declarado, sin descuento ni cuota automática; Deshacer protegido.
Estado persistente y entrada ADMIN automática; alta individual no saltea el control servidor.
Previas: 16 rojas. Final exclusiva: 404/2815 verdes, wings_testing_codex, 134,16 s; PHP/Blade/build OK.
Navegador normal/375: seis errores, carga 4 alumnos/6 cuotas + inscripción = $301.000, cero caja; Deshacer verificado.
Writer omitía textos vacíos: informe corregido y prueba compara el XLSX guardado por valor/tipo.
[Entrega y capturas](../06-pruebas/PRU-02/P1-IMPLEMENTACION-2026-10-05.md). P2 ajeno preservado fuera del commit.
Sin deploy, limpieza ni base real tocada. Gemini verifica; después retiro de CLI antiguos en commit aparte.

## 2026-10-05 — Codex CAB — P1 autorizada; freno por transición de bases existentes

Main actualizado desde GitHub; HEAD `1503df2`. Leídos decisión, maqueta aprobada y P0.
Revisados importador de padrón, inscripción, rutas y entrada por rol; MCP sin transporte.
No existe estado persistente de primera carga en aplicación/migraciones.
La tarea exige estado pendiente/terminada y bloqueo servidor, sin inferirlo por alumnos.
Falta decidir cómo inicializar ese estado en un club que ya está trabajando con alumnos.
Marcar todas las bases pendientes podría bloquear el alta manual existente; no se implementó.
Consulta a Carlos: conservar esas bases como preparadas y exigir Excel solo a clubes nuevos.
Sin código, migraciones, suite, datos ni servidor tocados; importadores antiguos conservados.
Cambios simultáneos ajenos en Cobranza/cobro y su prueba preservados. P1 no entregada.

## 2026-10-05 — Codex CAB — Entrega 1 de Cobranza verificada y aprobada

Pull al día, HEAD `4fb185e`; revisado `abc346a` y cuerpos reales, sin cambiar código.
A51: fila por deporte + DNI, importe estable en filtros y ayuda solo por saldo ajeno.
A52: inscripción no cambia estado en listado, ficha ni resumen; Al día/En plazo vistos.
Navegador ADMIN/OPERATIVO; copia alineada a HEAD y datos ficticios en base propia.
Build verde y todas las barras compartidas revisadas a 375, con capturas propias.
Suite propia `wings_testing_codex`: **380/2242**, toda verde (144,94 s).
A53/A54/A55 nuevos, fuera de Entrega 1: tarjetas móviles y selector de cobro; sin arreglos.
[Segunda vuelta y límites](../06-pruebas/PRU-02/VERIFICACION-ENTREGA1.md). Sin deploy.
Siguiente: Gemini puede continuar Entrega 2; A43/permisos siguen pendientes de su control.

## 2026-10-04 — Codex CAB — A29/A30/A31 entregados, pendiente Gemini

Commit `97cf933`. Autorización literal de Carlos aplicada a 403; mismo aviso y Volver al inicio propio.
EnsureAdminWeb rechaza sin redirect; cuenta inactiva/anónimo mantienen login.
Pruebas previas 12 rojas/2 verdes; final 380/2242 verde en wings_testing_codex.
Dos pruebas existentes actualizadas para rechazo explícito; Rubros no cambia lógica.
PHP/Blade y diff verificados; sin CSS/script nuevos, CSP conserva 19/10.
Tres roles en navegador normal/375 px; Volver conserva sesión y abre inicio correcto.
[Entrega y capturas](../06-pruebas/PRU-02/IMPLEMENTACION-PERMISOS.md). Sin deploy ni cierre.
A43 en 218ffc5; siguiente paso: Gemini debe verificar ambas entregas.

## 2026-10-04 — Codex CAB — A43 entregada, pendiente Gemini

Commit `218ffc5`. Carlos escribió la autorización literal y aclaró «A43 solo la cuota».
Mes cerrado exige elección: Sí cuota corriente completa, No solo evita cuota.
Inscripción por DNI conservada; decisión/autor auditados, rollback y reintento probados.
Pruebas previas 10 rojas; final 366/2131 verde en copia exclusiva sobre e5bc981 + A43.
Base wings_testing_codex; PHP/Blade/build verificados, archivos idénticos a entrega.
Formulario real normal/375 px: decisión obligatoria, Sí/No y ficha comprobados.
[Entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A43.md). Migración nueva alta_cuota; no deploy.
No cerrada: Gemini verifica. Siguiente: permisos A29/A30/A31 ya autorizados.

## 2026-10-04 — Codex CAB — A43 y permisos, maquetas para autorizar

Pull al día en `853873a`; cambios ajenos de Cobranza/CSS conservados.
A43 no estaba a medias: aún genera cuota histórica; maqueta del aviso preparada.
Ajustado pie de maqueta A43 al alta existente: Cancelar y Guardar a la derecha.
Consulta entonces pendiente sobre inscripción; Carlos aclaró después «A43 solo la cuota».
A29/A30/A31: maqueta con mensaje común y Volver al inicio de cada rol.
Carlos pidió quitar la comparación de perfiles; retirada de las tres maquetas.
Carlos aprobó ambas maquetas: «OK ambos». Luego escribió la línea formal; ver entrega posterior.
Navegador normal/375 px; prueba previa propia: 12 rojas/2 verdes, 21 aserciones.
Borrador de pruebas retirado de suite activa durante la espera de autorización.
Sin aplicación modificada, suite completa ni deploy; defectos siguen abiertos.
Preparación histórica; autorización y aclaración recibidas, implementación en entrada superior.

## 2026-10-04 — Codex CAB — A11 Configuración entregada

Commit `7a8fe09`; Carlos aprobó maqueta y escribió Diseno-autorizado, respetada literalmente.
Grupos, nombres humanos, validación de servidor y errores persistentes arriba/junto al campo.
Guardar explícito; generación mensual fija; editor de porcentajes conserva días 1–31.
JavaScript propio/Vite; CSP 20→19, sin CSS nuevo ni controles de acceso modificados.
Pruebas previas 7 rojas/1 verde; suite en base Codex 355/2070 verde, incluye pruebas ajenas.
Copia exclusiva de entrega verde: 352/2057, base wings_testing_codex; archivos coinciden.
Escritorio/375 px, errores y corrección comprobados con valores originales; sin deploy.
[Entrega y evidencia](../06-pruebas/PRU-02/IMPLEMENTACION-A11.md). Pendiente Gemini; no cerrada.

## 2026-10-04 — Codex CAB — Control de Cobranza y propuesta A11

Revisión independiente de Entrega 1 sobre `1c4e6dc` y `867c295`, código y navegador local.
No aprobada completa: A51 total por persona cambia con filtros; A52 inscripción altera estado.
A18 parcial; filtros a 375 px siguen ilegibles (A19). No se corrigió código ajeno ni datos.
Suite propia verde: 343/1955 en wings_testing, sin corridas simultáneas; Entrega2Test ausente.
[Informe y capturas](../06-pruebas/PRU-02/VERIFICACION-ENTREGA1.md); base local sin monto_condonado.
A11: [maqueta estática](../05-pendientes/maqueta-configuracion/README.md), CSS Wings existente,
grupos, etiquetas, errores y generación mensual fija. Abierta en navegador para Carlos.
Carlos aprueba maqueta («Ok, aprobada»); autorización e implementación posteriores en la entrada superior.
Pruebas A11 preparadas localmente: 7 rojas / 1 verde, 58 aserciones. Sin commit de los tests.


Entrada de maqueta P1 archivada intacta en [LOG-CODEX-MAQUETA-P1.md](../99-archivo/bitacoras/2026-10-05/LOG-CODEX-MAQUETA-P1.md).

Entrada P0 archivada en [LOG-CODEX-P0.md](../99-archivo/bitacoras/2026-10-05/LOG-CODEX-P0.md); texto original conservado, acceso a evidencia indicado allí.

Entradas anteriores archivadas intactas en [LOG-CODEX-ANTES-A43.md](../99-archivo/bitacoras/2026-10-04/LOG-CODEX-ANTES-A43.md).
