# Wings — Bitácora activa de CODEX

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


## 2026-10-04 — Codex CAB — Maqueta P1 en navegador, espera aprobación

P0 en `ad24769`, suite 338/1932 verde en ese corte; pendiente de Gemini.
P1 solo maqueta estática: cuatro pasos, errores por fila/columna y Excel marcado.
Carlos aprobó una fila con datos, dos Sí/No y 12 pares Período/Monto; reemplaza hojas separadas.
Solo Alumnos se completa; Catálogos alimenta listas y Guía reproduce ejemplos visuales.
Entrada automática obligatoria pedida; estado pendiente/terminada y bloqueo de alta son propuesta.
Instructivo y colores Wings revisados en escritorio/390 px, sin desborde; descarga comprobada.
XLSX: 38 columnas, tipos/valores originales conservados en marcado; ejemplo total $301.000.
Carga bloqueada con errores y Deshacer tras cobro simulado; Excel nativo no comprobado.
[Maqueta y evidencia](../05-pendientes/maqueta-primera-carga/README.md). Sin importador ni servidor tocados.
Carlos debe aprobar maqueta completa antes de programar. No cerrado; Gemini verifica.

## 2026-10-04 — Codex CAB — P0 entregado; P1 requiere maqueta aprobada

A43 reescrito: cuota del mes real de ingreso al porcentaje del día, sin corte ni pregunta.
Plan inicial vigente desde ingreso; cobro conserva importe congelado. Inscripción manual
única por DNI, sin corte; corrección auditada no crea/anula cargos por fecha.
Migración retira solo el parámetro legado; ambos importadores anteriores se conservan.
Pruebas antes: 9 fallos/20 correctas; final MariaDB wings_testing: 338/1932, todo verde.
Sintaxis PHP, compilación Blade y diff verificados; campo de Configuración retirado con
autorización de P0, sin CSS nuevo ni deploy. Documentos vigentes y tableros actualizados.
[Evidencia](../06-pruebas/PRU-02/P0-CARGA-INICIAL-2026-10-04.md). Pendiente Gemini, no cerrado.
Siguiente: maqueta P1 fuera de Wings; Carlos aprueba en navegador antes de programar.

Entradas anteriores archivadas intactas en [LOG-CODEX-ANTES-A43.md](../99-archivo/bitacoras/2026-10-04/LOG-CODEX-ANTES-A43.md).
