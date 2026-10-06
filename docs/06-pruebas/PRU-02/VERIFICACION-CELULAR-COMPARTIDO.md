# Verificación independiente del paquete celular — Codex CyE

06/10/2026. Main `6f9d214`, después de `git pull --ff-only` (sin novedades).
Entregas contrastadas: `26a67c6`, `20dc5ae`, `ce58d5a`. Implementó Gemini; verifica Codex.
**Seis defectos cerrados; A14, A27 y A53 devueltos a Gemini. T1 devuelto.**
Sin cambios de aplicación, vistas ni CSS; sin despliegue, base del club ni servidor.

## Método y evidencia

Wings ejecutado por HTTP local en `127.0.0.1:8792`, entorno testing y base
`wings_testing_codex`, con usuarios ADMIN, OPERATIVO y PROFESOR ficticios.
El [servidor del ensayo](evidencia/verificacion-celular-compartido/servidor.php)
comprueba entorno/base antes de ejecutar el kernel de Laravel. Usa los actores ficticios
con `Auth::setUser`: se comprueban pantallas y permisos del rol, no el ingreso por contraseña.
El marco original de `capturas-cashflow/marco-375.html` contiene la respuesta HTTP real
en `srcdoc`; no se inventó markup de Wings. Los assets son los de la aplicación.
Los errores provienen de POST inválidos hechos por el [ensayo preparador](evidencia/verificacion-celular-compartido/PrepararEscenarioTest.php).

Control inicial: [login a 375](evidencia/verificacion-celular-compartido/capturas/login-control-375.jpg).
También [375 × 667](evidencia/verificacion-celular-compartido/capturas/login-control-667-375.jpg).
Marco: ancho CSS 375, área útil 360 cuando aparece scrollbar; escritorio 1280 × 1040.
Marco de 1000 de alto para comparar con Gemini; 667 para comprobar acciones en teléfono.

[Escenario y cinco filas concretas](evidencia/verificacion-celular-compartido/escenario.json):
20 alumnos en una clase, nombres compuestos, deporte/nivel largos, tres frecuencias,
tarifas $2.345.678,90 y $9.876.543,21, deuda inicial $24.691.357,80 y Cashflow $1.570.000.
El guardado de una asistencia y la generación automática de cargos al consultar Cobrar
modificaron exclusivamente esos datos descartables; las imágenes muestran su estado de ese momento.

**84 capturas propias**, abiertas por Codex, más 102 originales de Gemini inspeccionadas.
[Visor propio](evidencia/verificacion-celular-compartido/visor.html),
[mediciones DOM y estado accesible](evidencia/verificacion-celular-compartido/capturas/mediciones.json),
[huellas propias](evidencia/verificacion-celular-compartido/propias-inspeccion.json).
Las láminas son ayudas de lectura; no sustituyen los archivos originales.

## Dictamen por defecto

| ID | Dictamen | Comprobado en pantalla y fuente actual | Evidencia propia / qué falta |
|---|---|---|---|
| A14 | **DEVUELTO a Gemini** | Alta/edición de Alumnos y Profesores conservan acciones estáticas al pie. En Profesor Guardar empieza en y=1086, fuera del marco de 1000. Tras error de Alumno, Guardar está en y=1498; al llegar al pie, el resumen de errores quedó arriba, fuera de vista (y=-449). Alinear a derecha no resuelve el criterio. | [Profesor](evidencia/verificacion-celular-compartido/capturas/admin-profesores-edit-375.jpg), [error arriba](evidencia/verificacion-celular-compartido/capturas/admin-alumnos-error-arriba-375.jpg), [error abajo](evidencia/verificacion-celular-compartido/capturas/admin-alumnos-error-abajo-375.jpg). Hacer alcanzables acciones y errores sin perderlos de vista. |
| A20 | **CERRADO, Codex** | Cashflow muestra ingreso/balance $1.570.000 completos. Nuevo queda debajo, sin solapar saldo ni contador. En escritorio mantiene una fila contenida. El saldo inicial del escenario es cero; no se afirma prueba de saldo inicial millonario. | [375](evidencia/verificacion-celular-compartido/capturas/admin-cashflow-375.jpg), [escritorio](evidencia/verificacion-celular-compartido/capturas/admin-cashflow-desktop.jpg). |
| A27 | **DEVUELTO a Gemini** | En 375 × 1000 los botones entran; en 375 × 667 Registrar y Cancelar están en y=740–772, fuera de vista y sin barra fija. Observaciones sí es opcional en vista y validación `nullable` de `movimientoStore`; esta parte pasa. | [667](evidencia/verificacion-celular-compartido/capturas/operativo-movimiento-667-375.jpg), [1000](evidencia/verificacion-celular-compartido/capturas/operativo-movimiento-375.jpg), [errores reales](evidencia/verificacion-celular-compartido/capturas/operativo-movimiento-error-375.jpg). Resolver visibilidad de acciones. |
| A28 | **CERRADO, Codex** | Grupos no produce desborde de página aun con tres tarifas millonarias: scrollWidth=clientWidth=360. Activo está en el extremo izquierdo y Editar en el derecho, separados. **La contención usa elipsis**; la lectura completa de tarifas no pasa y queda en A53. | [Tarifas/acciones](evidencia/verificacion-celular-compartido/capturas/admin-grupos-tarifas-detalle-375.jpg), [escritorio](evidencia/verificacion-celular-compartido/capturas/admin-grupos-desktop.jpg). |
| A33 | **CERRADO, Codex** | 20 filas compactas de 61,61 px más 10 px de separación (no los 54 declarados). Lista de 1422 px aproximadamente; los controles se alcanzan y Guardar queda tras el último alumno. Con PROFESOR se marcó uno y el sistema respondió «Asistencias guardadas». | [Primeros](evidencia/verificacion-celular-compartido/capturas/profesor-asistencia-primeros-375.jpg), [últimos y Guardar](evidencia/verificacion-celular-compartido/capturas/profesor-asistencia-ultimos-375.jpg), [escritorio](evidencia/verificacion-celular-compartido/capturas/profesor-asistencia-desktop.jpg). Límite: nombres largos se truncan y el título largo agrega altura antes de la lista; no se certifica toda la ficha como libre de recortes. |
| A36 | **CERRADO, Codex** | Opción A de Carlos: tabla única, columnas separadas, nombre y permiso visibles al inicio; desplazamiento local permite llegar a Caja/Editar/Pausar. Tabla 480 px dentro de contenedor 291; al desplazarse 189 px la página sigue en 360. No hay tarjetas móviles alternativas. | [Izquierda](evidencia/verificacion-celular-compartido/capturas/admin-rubros-375.jpg), [derecha](evidencia/verificacion-celular-compartido/capturas/admin-rubros-derecha-375.jpg), [escritorio](evidencia/verificacion-celular-compartido/capturas/admin-rubros-desktop.jpg). |
| A40 | **CERRADO, Codex** | Inicio ADMIN contiene deuda $24.691.358 dentro del indicador a 375. Valor y título no cruzan el borde. | [375](evidencia/verificacion-celular-compartido/capturas/admin-dashboard-375.jpg), [escritorio](evidencia/verificacion-celular-compartido/capturas/admin-dashboard-desktop.jpg). |
| A41 | **CERRADO, Codex** | Movimientos muestra Desde y Hasta junto a los dos campos, en ADMIN y OPERATIVO, a 375 y en escritorio. | [ADMIN](evidencia/verificacion-celular-compartido/capturas/admin-fechas-375.jpg), [OPERATIVO](evidencia/verificacion-celular-compartido/capturas/operativo-movimientos-375.jpg), [escritorio](evidencia/verificacion-celular-compartido/capturas/admin-fechas-desktop.jpg). |
| A53 | **DEVUELTO a Gemini** | Grupos conserva `info-value ds-truncate`: una cadena de tarifas requiere 383 px y recibe 225; se ve «2x/sem — $2.3…» y desaparece la tercera frecuencia. Selector mantiene `ds-truncate` en Grupo: necesita 695 px y recibe 248, sin nombre completo visible. Nombre compuesto del alumno y saldo millonario sí entran. El problema pasa de desbordar a ocultar datos. | [Grupos](evidencia/verificacion-celular-compartido/capturas/admin-grupos-tarifas-detalle-375.jpg), [selector con nombre largo](evidencia/verificacion-celular-compartido/capturas/admin-selector-nombre-largo-375.jpg). Mostrar completos precios y grupo, sin desborde. |

## T1: cobertura, autenticidad y alcance del cambio compartido

**DEVUELTO a Gemini.** `git grep -l filtros-actions -- resources/views` devuelve
40 vistas. [Cruce exhaustivo](evidencia/verificacion-celular-compartido/cobertura.json):
39 tienen par de captura; falta **`resources/views/grupos/show.blade.php`**, ruta
real `/grupos/{id}`. Se capturó independientemente para ADMIN y OPERATIVO.
Los formularios compartidos están cubiertos por create/edit de sus módulos;
ningún parcial `_form` agrega otra aparición literal que haya que contar aparte.
51 pantallas no equivale a 40 consumidores cubiertos.

Originales 01–51, escritorio y móvil, abiertos en 13 láminas:
[registro de las 102](evidencia/verificacion-celular-compartido/INSPECCION-102.md) y
[huellas](evidencia/verificacion-celular-compartido/originales-inspeccion.json).
Hay recortes en Caja índice, Cobrar alumno y Clases; no se aprueba la afirmación
de «cero desbordes» del informe de Gemini. Las tablas que Carlos eligió con scroll
local (Rubros, Historial, Movimientos) no se confunden con desborde de página.

Reproducción propia de **10 módulos** en ambos tamaños: Dashboard, Cashflow, Caja,
Grupos, Clases/asistencia, Rubros, Movimientos, Alumnos, Profesores y Revisión.
El aspecto, estructura y alineación coinciden con las originales de esos módulos,
salvo diferencias explicadas por datos/roles, longitud del texto, estado del turno
y las entregas posteriores de A15/A16 y A25. Se recorrieron además todas las pantallas
capturadas de Caja, Clases, Grupos y Profesores, y las dos de Liquidaciones.
No hay fundamento para llamar falsas las originales; **la autenticidad de las otras
pantallas se infiere del muestreo, no se certifica individualmente**.

### Roles comprobados

- OPERATIVO: Alumnos índice/alta/edición; Caja índice, apertura, cierre, historial,
  movimiento, edición, cancelación, selector y cobro; Cobranza, Movimientos,
  Clases/lista y asistencia; Grupos/lista y ficha; Revisión. Botones de administración
  ausentes donde corresponde. Cada pantalla tiene captura propia.
- PROFESOR: Clases índice y ficha con 20 alumnos, a 375 y escritorio, control y guardado.
  Su única barra consumidora literal es `clases/index`; la ficha también fue controlada
  porque cambió el paquete. No se otorgaron permisos nuevos.
- ADMIN: pantallas anteriores con sus acciones propias, configuración del mostrador,
  alta/edición de Clases y Grupos y módulos exclusivamente administrativos.

### Observaciones compartidas para Gemini

1. **Caja índice**: clientWidth=360, scrollWidth=487; corta egreso/neto del resumen.
   [ADMIN](evidencia/verificacion-celular-compartido/capturas/admin-caja-375.jpg),
   [OPERATIVO](evidencia/verificacion-celular-compartido/capturas/operativo-caja-375.jpg).
2. **Cobrar alumno**: página 432 para área útil 360; total pendiente queda recortado.
   Reproduce el problema visto en la original 18.
   [Captura](evidencia/verificacion-celular-compartido/capturas/operativo-cobrar-375.jpg).
3. **Clases del día/ficha**: datos se recortan en contenedores internos; título largo
   también alarga la cabecera. No es desborde raíz del listado propio (360/360).
   [Lista](evidencia/verificacion-celular-compartido/capturas/profesor-clases-375.jpg).
4. **Revisión**: Limpiar queda a izquierda a 375, distinto de las demás barras.
   El botón está en un div sin `filtros-actions`; escritorio no lo deja fuera del contenedor.
   [375](evidencia/verificacion-celular-compartido/capturas/admin-revision-375.jpg),
   [escritorio](evidencia/verificacion-celular-compartido/capturas/admin-revision-desktop.jpg).
   Observación, sin arreglo en esta verificación.
5. **Liquidaciones original 44**: se ve Nuevo recortado. En la propia con cero liquidaciones
   no se reproduce; se revisaron índice y alta. No atribuir causa sin reproducir ese estado.

`20dc5ae` cambia `justify-content` dentro de `@media (max-width:768px)`;
esa regla CSS no mueve botones de escritorio. Los cambios locales de vistas sí afectan
ambos tamaños: Subrubro en Rubros pierde `margin-right:auto` y pasa al grupo derecho;
en selector se invierte Ver/Cobrar y en Grupos cambia el orden de Activo/Editar/Ver.
Se ven contenidos en escritorio. El pedido documentado habla del arreglo de celular:
T1 debe explicitar esos efectos de escritorio, sin presumir una aprobación adicional.
No se atribuyen esos cambios a la regla CSS ni se reparan en esta verificación.

## Verificaciones y publicación

- Preparación del escenario: 1 prueba / 6 aserciones, 7,63 s; fuera de la suite permanente.
- Suite completa: **489 aprobadas, 2 omitidas, 3924 aserciones; 187,17 s**, base
  `wings_testing_codex`. [Salida completa](evidencia/verificacion-celular-compartido/suite.txt).
- Tablero canónico: seis cierres, tres devoluciones y T1 devuelto; ambos DEFECTOS actualizados.
- Controles posteriores de documentación/CSP: 6 aprobados, 21 aserciones, 0,55 s.
  [Salida](evidencia/verificacion-celular-compartido/controles.txt).
- Corte publicado: **41/72 cerrados, 31 abiertos, 0 frenan**. El cierre A25 de Gemini
  permanece fuera del commit propio; se conservan sus archivos de trabajo.
- Diferencias de aplicación/vistas/CSS vacías. Solo documentación e instrumentación del ensayo.
- Commit/push autorizados por el prompt de Carlos; sin despliegue.
