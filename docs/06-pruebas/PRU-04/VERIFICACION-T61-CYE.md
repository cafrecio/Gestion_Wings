# T61 — Integración y recorrido desde CyE

Codex CyE, 20/10/2026. Continuación solicitada por Carlos. **Integración y recorrido real completados. Pendientes aprobación visual de Carlos y control cruzado de lógica por Claude; sin cierre ni despliegue.**

## Integración y comprobaciones

- Entrega `c9b68d2` combinada con `main` `c2721d8d`; T52 conservada, sin cambios en su controlador, vista ni cinco pruebas.
- Conflictos solo documentales; se conservaron T52 y los antecedentes de CAB. Contador del conjunto: 956 métodos.
- [Dirigidas aisladas](evidencia-t61-cye/dirigidas.txt): 24 aprobadas, 239 aserciones, 15,58 s, salida 0. Incluyen 12 de T61, cinco de T52 y guardianes de documentos/tablero.
- Primera corrida no válida: el integrador inició por error otra prueba sobre la misma base mientras la anterior seguía activa. La anterior terminó con cinco fallos T52 por columnas ausentes; la repetición aislada pasó completa. No se atribuye un defecto al producto a partir de esa corrida.
- [Revisión independiente de código](VERIFICACION-T61-INDEPENDIENTE.md): subagente revisor, sin hallazgos bloqueantes; solo revisión de fuentes. No sustituye el resultado del navegador ni registra una verificación de Claude/Gemini en el tablero.
- [Build](evidencia-t61-cye/build.txt) correcto, 2 min 59 s. [Sintaxis PHP](evidencia-t61-cye/sintaxis.txt) correcta en los ocho archivos de aplicación/pruebas T61.
- [Suite completa conjunta](evidencia-t61-cye/suite-completa.txt): **952 aprobadas, 4 omitidas, 8455 aserciones, 1102,57 s, salida 0**, base wings_testing_codex. [Código de salida](evidencia-t61-cye/suite-exit.txt). Sin otra suite detectada en el control inicial y posterior; el servidor de recorrido usa otra base. [Once fuentes T61 iguales a la entrega CAB](evidencia-t61-cye/fuentes-integradas.json).
- Compilación y limpieza de vistas correctas después de la suite, sin alterar fuentes. Diff de diseño limitado a las tres vistas T61 ya autorizadas; CSS intacto.

## Recorrido real

Chrome local mediante CUA, Wings en `127.0.0.1:8761`, base ficticia separada `wings_testing_codex_t61`. La suite usa `wings_testing_codex`; no comparten tablas. Solo datos sintéticos. Migración de historia realizada con mantenimiento aislado en `storage/t61-migracion`, sin activar mantenimiento en el sitio de trabajo.

1. Ingreso ADMIN con cuenta ficticia. Inicio antes: ingresos $12.000, gastos $5.000, resultado $7.000; Banco $7.000, Efectivo $606.000.
2. Cashflow → Nuevo → EGRESO; Efectivo, Transferencias internas — salidas, Salida a otro medio, $300.000. Guardado real con mensaje de éxito; Efectivo quedó $306.000.
3. Cashflow → Nuevo → INGRESO; Banco Nación, Transferencias internas — entradas, Entrada desde otro medio, $300.000. Guardado real; Banco quedó $307.000.
4. Cashflow muestra seis movimientos; ingresos $320.000, egresos $307.000 y neto $13.000, incluyendo ambas cargas. Los dos renglones dicen Traspaso. Ancho real de escritorio 1366; documento 1366, sin desborde horizontal.
5. Reportes después: ingresos $12.000, gastos $5.000, resultado del club $7.000. Detalle abierto por clic: Banco $307.000, Efectivo $306.000, disponible $613.000, aportes $8.000, retiros $2.000 y global $13.000; explicación incluye traspasos.
6. Inicio después conserva los indicadores del club y muestra los saldos nuevos.

## Capturas guardadas

Respuestas de la aplicación andando, no maquetas. Escritorio 1366 mediante viewport explícito de Chrome. Capturas después del build renovadas para Cashflow, Reportes e Inicio.

- [Cashflow final](evidencia-t61-cye/cashflow-1366.jpg)
- [Reportes, detalle abierto](evidencia-t61-cye/reportes-1366.jpg)
- [Inicio final](evidencia-t61-cye/inicio-1366.jpg)
- [Formulario de salida](evidencia-t61-cye/salida-1366.jpg) y [entrada](evidencia-t61-cye/entrada-1366.jpg)
- [Cashflow antes](evidencia-t61-cye/cashflow-antes-1366.jpg) e [Inicio antes](evidencia-t61-cye/inicio-antes-1366.jpg)

## Bloqueo anterior — resuelto en la continuación final

Después de navegar a Rubros, la herramienta devolvió «Local browser use is disabled in Work settings». Una consulta nueva de inventario confirmó que no ofrecía ningún navegador. No se cambió de herramienta para eludir esa restricción. Carlos aportó [captura del interruptor integrado encendido](evidencia-t61-cye/navegador-habilitado-carlos.png) y afirmó que no lo cambió. Se reinició solo la conexión CUA y se repitió el inventario: mismo rechazo. La causa de esa discrepancia no se comprobó; no se exige repetir el reinicio de la aplicación que ya había fallado en CAB.

En aquel corte faltaban capturas a 375, Crear/Editar subrubros en ambos tipos, recorrido operativo y control de consola; esos puntos se completaron en la continuación final de abajo. Siguen pendientes la verificación cruzada y la aprobación visual de Carlos. La ayuda sigue legítimamente como texto entregado para T13, según contrato; no es una pantalla pendiente de construir en T61.

## Reinstalación de dependencias

Carlos solicitó aviso cuando fuera seguro reinstalar. Suite y build terminados; se guarda la integración antes de dar ese aviso. El servidor local se detiene después de conservar datos ficticios, para poder iniciarlo de nuevo al recuperar el navegador. No se afirma que reinstalar resuelva el rechazo.

Integración y evidencia se guardan en el commit que contiene este informe, sobre main. Las novedades ajenas de DU-09 permanecen fuera de ese commit. El HTML de T34 regenerado por la suite se repuso desde HEAD, tras comprobar la escritura en su prueba. Guardianes documentales finales: 7 aprobadas, 34 aserciones, 0,30 s ([salida](evidencia-t61-cye/guardianes-finales.txt)).

Los registros de consola conservan resultados y mensajes; se normalizan finales de línea y espacios finales para versionarlos.

## Guardado antes de reinstalar

Integración guardada en `758955ef`. La suite y el build terminaron; no quedan pruebas activas y el servidor ficticio del puerto 8761 fue detenido por sus dos PID verificados. Base ficticia y capturas conservadas. La revisión automática rechazó publicar tanto en main como en rama propia: exige autorización humana que identifique repositorio y contenido a transferir. No se publicó esta integración. El commit ajeno `49a15f92` apareció después, con DU-09/T52; no se atribuye ni certifica ese trabajo. Para publicar solo este avance se pedirá autorización sobre `758955ef` en `cafrecio/Gestion_Wings`, rama `codex/t61-integracion-cye`.

## Continuación final — 20/10/2026, Codex CyE

Navegador recuperado tras la reinstalación informada por Carlos. Servidor local en
127.0.0.1:8761, base ficticia conservada `wings_testing_codex_t61`. La primera instancia
escuchaba pero Chrome no llegaba; se detuvieron solo sus dos PID comprobados y se abrió
fuera del aislamiento de red. No se modificó el servidor remoto ni la base del club.

- Los dos movimientos existentes de $300.000 siguen en Cashflow: no se duplicaron.
  Efectivo $306.000, Banco $307.000; ingresos $320.000, egresos $307.000, neto $13.000.
  Inicio y Reportes: ingresos $12.000, gastos $5.000, resultado del club $7.000;
  disponible $613.000, aportes $8.000, retiros $2.000, global $13.000.
- Crear y Editar subrubros, INGRESO y EGRESO, recorridos. TRASPASO está disponible en
  ambos; las ediciones conservan su clasificación. Las altas se capturan en estado
  inicial, sin guardarlas; no se creó un catálogo paralelo.
- Operativa ficticia: Registrar muestra sus conceptos, sin los dos traspasos ADMIN.
  Cashflow no está en su menú; abrir su URL muestra Sin permiso. Capturas de evidencia
  guardadas; los POST directos siguen cubiertos por las pruebas T61 de la entrega.
- 22 capturas finales con medidas en [control-capturas-final.json](evidencia-t61-cye/control-capturas-final.json).
  Marco de emulación CDP de Chrome: `innerWidth=375` / 1366, sin achicar la ventana
  física ni recortar un layout de 500. Control: ingreso real completo y legible.
  Se guardan los píxeles tal como los entrega Chrome, incluida su barra vertical;
  no se reescalan ni se fabrican pantallas. Las capturas tomadas durante una transición
  del menú se reemplazaron con el ancho fijo y tras comprobar el render real.
- [Consola ADMIN](evidencia-t61-cye/consola-admin.json) y
  [operativa](evidencia-t61-cye/consola-operativa.json): el lector devolvió cero avisos/errores.
- [Once fuentes principales](evidencia-t61-cye/fuentes-recorrido-final.json) iguales
  a la integración. Sin cambios de aplicación, vistas o CSS en este recorrido.
  No se repite suite por una entrega de evidencia/documentación; se conserva el
  resultado completo propio de la integración, citado arriba con su alcance y fecha.
- La integración 758955ef ya es antecesora de main publicada; el rechazo de publicación
  descrito en el corte anterior es histórico, no un bloqueo actual.

[Galería final para Carlos: siete pares y tres complementos](RECORRIDO-T61.html).
Mi recorrido pendiente queda entregado; T61 permanece a_verificar, con aprobación
visual de Carlos y control de lógica por Claude. Sin autocierre ni despliegue.
