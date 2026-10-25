# Wings — Checklist de Carlos

> **Actualizado:** 08/09/2026
> **Plan vigente:** `docs/07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md`,
> version 2026-09-08.v4.

Solo contiene acciones o decisiones que necesitan a Carlos. Las tareas tecnicas de
Codex y Claude no van aca.

## 0. Para retomar — 21/09/2026

Entrega A15/A16, 06/10: **488 pruebas: 486 aprobadas y 2 omitidas, 3911 aserciones**; 18 nuevas, suite completa final en wings_testing_codex (342,01 s), sin fallas. Diseño aprobado por Carlos; **HECHO (Codex), a revisar** por otro agente. [Entrega y capturas](../06-pruebas/PRU-02/IMPLEMENTACION-A15-A16.md). A25 CERRADO: verificado por Gemini de forma independiente ([informe](../06-pruebas/PRU-02/VERIFICACION-A25.md)). Sin despliegue.

Entrega P1: **427 pruebas / 2968 aserciones**, todas verdes en copia exclusiva sobre main f1df4fd, base wings_testing_codex;
resultado y alcance en [evidencia P1](../06-pruebas/PRU-02/P1-IMPLEMENTACION-2026-10-05.md).
El corte previo de Entrega 1 (P2) fue 343/1955. A11 verificada por Gemini; A43 entregada, pendiente Gemini; sin deploy.
A43: Carlos autorizó el diseño y aclaró que No evita solo cuota; inscripción sin cambios.
Entrega 1 de P2 (Cobranza) **verificada y aprobada por Codex el 05/10**:
`abc346a`, código/pantalla y filtros 375; suite propia 380/2242.
Gemini puede continuar Entrega 2. A54 conserva su cierre; A53 DEVUELTO a Gemini por Codex CyE 06/10; [control de celular](../06-pruebas/PRU-02/VERIFICACION-CELULAR-COMPARTIDO.md).
A13/B1/A55 CERRADOS por Codex CyE el 06/10 sobre main integrado; A48/A49 CERRADOS,
verificado Claude. Carlos autorizó integrar y publicar; no queda aprobación pendiente
para estos cierres. [Informe independiente](../06-pruebas/PRU-02/VERIFICACION-A13-B1-A55-CIERRE.md). Sin deploy.

**Lo que necesita tu decision o tu presencia:**

- [x] **A15/A16, 06/10:** Carlos eligió avisar y permitir confirmar, y aprobó la propuesta
  de horarios por día con «Si, bien!!». [Capturas aprobadas](../06-pruebas/PRU-02/PROPUESTA-A15-A16.md).
  Implementadas; otro agente verifica. No volver a pedir aprobación visual de esta entrega.
- [x] **Reglas A25, cambio del mostrador — 06/10:** definido separar cambio/retiro, heredar con
  confirmación del último cierre del club (cajón compartido) y permitir cierre con diferencia
  para revisión ADMIN. Ya respondió: primer importe declarado, corrección con motivo,
  un turno abierto y cambio retenido elegible. ADMIN configura el medio físico una vez y
  guarda aparte sus cobros; debe contar/cerrar antes de validar. [Detalle](../05-pendientes/A25-CAMBIO-INICIAL-CAJA.md).
  Implementado; Carlos aprobó las cinco pantallas escritorio/375 el 06/10. [Capturas](../06-pruebas/PRU-02/evidencia/a25/capturas/index.html). No volver a pedir esa aprobación; verifica otro agente.
- [x] **Excepción A25 respondida 06/10:** al corregir una caja rechazada se conservan
  contado/cambio/retiro originales, sin modificar lo recibido por el siguiente turno.

- [x] **Maqueta de Primera carga aprobada el 05/10.** Decision del 26/09: la carga
  inicial pasa a ser un Excel unico con alumnos y deuda, se saca la fecha de corte y lo que
  se carga a mano sigue las reglas del sistema. Detalle y lo que queda abierto en
  [PRIMERA-CARGA-EXCEL.md](../05-pendientes/PRIMERA-CARGA-EXCEL.md). Codex prepara la
  [maqueta fuera del sistema](../05-pendientes/maqueta-primera-carga/index.html) entregada
  el 04/10 y aprobada el 05/10. P1 implementada, pendiente Gemini; no volver a pedir esa aprobación.
- [x] **Regla del alta manual decidida:** cuota del mes de ingreso con el porcentaje del
  día, sin corte. A43 enmienda el ingreso en mes cerrado: Sí cuota corriente completa, No solo evita cuota. P0 y A43 entregados; Gemini verifica.

- [x] **Avisos de test (23/09):** bot y correo autorizados; chat verificado y recepción
  de Telegram confirmada por Carlos. Cron probado. Correo no entregado por fallo
  del transporte del servidor; pendiente técnico, no volver a pedir token o chat. [Evidencia](../06-pruebas/PRU-02-AUTOMATIZACION-TEST-2026-09-23.md).

- [ ] **Clases particulares: ¿entra antes o despues de la prueba grande?** Antes cubre el
  modulo nuevo pero vuelve a correr el reloj; despues prueba lo que ya esta y lo
  suma sobre una base probada. Recomendacion de Claude: despues.
- [x] **Asignar FIN-12** (cancelar liquidacion cerrada no pagada): **Gemini** (Carlos, 17/09).
- [ ] **Estar presente en el despliegue a wings**, cuando pase la prueba grande.

**El orden acordado (Carlos, 17/09):** ~~FIN-12 y la pantalla/recibo de FIN-13~~ (hechas
21/09 y 17/09) → **siguiente:** actualizar test **una sola vez, con la version que se va a probar** → prueba grande en
`test.gestionar-te` → ensayo de restauracion → desplegar en wings. Actualizar test
antes de terminar los cambios no sirve: habria que volver a hacerlo.

`test.gestionar-te.com.ar` esta montado (usuario, base, PHP y certificado propios;
no comparte nada con wings). Entrar con `admin@wings.test` / `PruebaWings2026`.

## 1. Al cambiar de computadora

1. Ejecutar `git pull --ff-only`.
2. Ejecutar `composer install`, `npm install` y `npm run build` si cambiaron
   dependencias o assets.
3. Instalar/verificar la guardia de diseño con `bash scripts/hooks/instalar.sh`.
4. Ejecutar `php artisan migrate` sobre la base local correspondiente.
5. **Solo la primera vez en cada computadora:** instalar `codebase-memory-mcp`, el
   indice del codigo que usan los agentes para buscar (ver `AGENTS.md` §6e). En
   PowerShell:

```powershell
Invoke-WebRequest -Uri https://raw.githubusercontent.com/DeusData/codebase-memory-mcp/main/install.ps1 -OutFile install.ps1
Unblock-File .\install.ps1
.\install.ps1
```

   El instalador verifica la huella del programa y lo registra solo en los agentes que
   encuentre en esa maquina. **Despues hay que reiniciar las sesiones de Claude, Codex y
   Gemini** para que lo tomen. En CyE quedo instalado el 17/09 (version 0.11.0) para
   Claude Code, Codex y VS Code; **Gemini no fue detectado ahi**.
6. **Acceso al servidor (`ssh vps`).** Probar con `ssh vps hostname`. Si no entra, la
   maquina no tiene su clave: se instala con

```powershell
powershell -ExecutionPolicy Bypass -File scripts\maquina\instalar-acceso-servidor.ps1
```

   Si el script deja el alias bien y el servidor igual rechaza la clave, **falta la
   sincronizacion automatica en el servidor** (05/10/2026). Se instala una sola vez, como
   root, desde el panel del servidor o por SSH desde una maquina que entre:

```bash
curl -fsSL https://raw.githubusercontent.com/cafrecio/Gestion_Wings/main/scripts/servidor/acceso-agentes.sh \
  -o /root/acceso-agentes.sh && bash /root/acceso-agentes.sh --instalar
```

   Desde ahi, el servidor rearma la lista de claves cada 15 minutos desde
   `https://github.com/cafrecio.keys` y avisa por Telegram si queda roto. **Una maquina
   nueva no necesita ningun paso en el servidor:** alcanza con cargar su clave publica en
   el GitHub de Carlos. No agregar claves a mano: se pierden en la siguiente vuelta.

   **No tenes que hacer nada:** CAB y CyE estan autorizadas desde el 22/09 (CyE con la
   clave que cargaste en GitHub). Si en una maquina no entra, el agente corre el script.
7. Ejecutar la suite completa:

```bash
$env:DB_DATABASE='wings_testing_codex'; php artisan test  # 1041 pruebas. T77: 9 regresiones nuevas; corte incluye 3 pruebas ajenas T78. Dirigidas finales 68/442 verdes. Completa 1034 aprobadas, 6 omitidas y 1 fallo de fixture T78; repeticion final de T78 verde, sin cambios propios en esa tarea (25/10). Antes: T50 cierre observaciones Gemini CAB 21/10: 13 pruebas en FechaIngresoFuturaT50Test (agrega validaciones castellano en previews); corte 1029. Antes, Integración Claude CAB 21/10 de T61 segunda vuelta (+3) y T50 segunda vuelta (+2): corte 1028. T61 segunda vuelta, Codex CAB 21/10: completa 1020 aprobadas, 6 omitidas, 8846 aserciones, 934,96 s, salida 0, wings_testing_codex, corte 1026; 16/198 dirigidas y 125/851 del área verdes. Ver IMPLEMENTACION-T61.md. Antes: T43 JSON: completa 1017 aprobadas, 6 omitidas, 8791 aserciones, 844,65 s, salida 0, wings_testing_codex; corte 1023. Antes: T64 corrección Gemini CAB 20/10: 11 pruebas en ReglaPrimerPagoSinHuecosT64Test (agrega 3 por H2, H3 y preservación de inactivas) y 5 en ReglaPrimerPagoSinSuperposicionTest; dirigidas y rápida en verde en wings_testing_gemini; corte 1021. Antes, T43 Codex CyE 20/10: completa 1012 aprobadas, 6 omitidas, 8743 aserciones, 740,41 s, salida 0, wings_testing_codex, corte 1018. Ver IMPLEMENTACION-T43.md. Antes, T42 Codex CyE 20/10: completa 997 aprobadas, 6 omitidas, 8652 aserciones, 1242,09 s, salida 0, wings_testing_codex, corte 1003. Ver IMPLEMENTACION-T42.md. Antes, Seguimiento FIN-09 de T61: 13/143 dirigidas y completa **977 aprobadas, 6 omitidas, 8541 aserciones, 697,65 s, salida 0**, wings_testing_codex, corte 983. [Salida del seguimiento](../06-pruebas/PRU-04/evidencia-t61-transferencia/suite-seguimiento-fecha.txt). Antes, T61 corregida, Codex CyE 20/10: 976 aprobadas, 6 omitidas, 8527 aserciones, 804,11 s, salida 0 en wings_testing_codex; corte 982. Antes, T64 Gemini CAB 20/10: 8 pruebas en ReglaPrimerPagoSinHuecosT64Test y 1 en CapturaConfiguracionReglasT64Test; dirigidas y capturas en verde en wings_testing_gemini; corte 982. Antes, Verificación T52, Codex CyE 20/10: completa 962 aprobadas, 5 omitidas, 2 fallidas T33, 8507 aserciones, 1019,61 s, salida 2; T33 aislada 10/120 verdes, 179,90 s, salida 0, sin causa atribuida. T50 Gemini CAB 20/10: 10 pruebas en FechaIngresoFuturaT50Test y 1 en CapturaT50Test; suite rápida y dirigidas verdes en wings_testing_gemini; corte 969. Antes, T52 segunda parte, Claude CyE 20/10: 952 aprobadas, 4 omitidas, 2 fallidas (contador, y CancelarLiquidacionCerradaTest por un alumno de prueba sin fecha de ingreso, corregido: 9/9), 8472 aserciones, 952 s en wings_testing_claude; corte 958. Antes, Integración T61 + T52, Codex CyE 20/10: 952 aprobadas, 4 omitidas, 8455 aserciones, 1102,57 s, salida 0 en wings_testing_codex; corte 956. Fuentes T61 iguales a entrega CAB y T52 intacta. Antecedente T52 Claude CAB 20/10: suite rápida en wings_testing_claude, 835 aprobadas, 4 omitidas; las 2 fallidas son de T61 sin subir (Codex); corte 944. Antes, T74 Codex CyE 19/10: 935 aprobadas, 4 omitidas, 8243 aserciones, 1283,46 s, salida 0 en wings_testing_codex; corte 939. Antes, T34 C1/C2 Gemini CyE 19/10: 934 aprobadas, 4 omitidas, 8239 aserciones, 1498.66 s, salida 0 en wings_testing_gemini; corte 939 (agrega 5 pruebas de C1 y C2 en ClaseSinAsistenciaYDniT34T67Test). Antes, T40 Claude CyE 19/10: 929 aprobadas, 4 omitidas, 1 fallida (este contador, antes de actualizarlo a 934), 8215 aserciones, 1346 s en wings_testing_claude; corte 934. Antes, T34 Codex CyE 19/10: 921 aprobadas, 4 omitidas, 8179 aserciones, 1343,15 s, salida 0 en wings_testing_codex; corte 925. Antes, T66 Codex CAB 19/10: 920 aprobadas, 4 omitidas, 8155 aserciones, 960,48 s, salida 0 en wings_testing_codex; corte 924. Antes, T34 corrección Gemini CAB 19/10: 903 aprobadas, 4 omitidas, wings_testing_gemini, corte 908 (agrega 2 pruebas de H1 y H2 en ClaseSinAsistenciaYDniT34T67Test). Antes, T59/T47 Claude CAB 19/10: 901 aprobadas, 4 omitidas en wings_testing_claude; corte 906 (incluye T56/T68). Antes, T74: 886 aprobadas, 3 omitidas en wings_testing_claude; corte 890. Antes: Suite T44 corrección visual Codex CAB 19/10: 882 aprobadas, 3 omitidas, 7821 aserciones, 1134.95 s, wings_testing_codex, corte 885. Ver IMPLEMENTACION-T44.md. T50 segunda vuelta: T50 segunda vuelta (H2 y H3) Gemini CAB 21/10: 12 pruebas en FechaIngresoFuturaT50Test (agrega 2 por H2 y H3); corte 1025. Antes: T43 JSON: completa 1017 aprobadas, 6 omitidas, 8791 aserciones, 844,65 s, salida 0, wings_testing_codex; corte 1023. Antes: T64 corrección Gemini CAB 20/10: 11 pruebas en ReglaPrimerPagoSinHuecosT64Test (agrega 3 por H2, H3 y preservación de inactivas) y 5 en ReglaPrimerPagoSinSuperposicionTest; dirigidas y rápida en verde en wings_testing_gemini; corte 1021. Antes, T43 Codex CyE 20/10: completa 1012 aprobadas, 6 omitidas, 8743 aserciones, 740,41 s, salida 0, wings_testing_codex, corte 1018. Ver IMPLEMENTACION-T43.md. Antes, T42 Codex CyE 20/10: completa 997 aprobadas, 6 omitidas, 8652 aserciones, 1242,09 s, salida 0, wings_testing_codex, corte 1003. Ver IMPLEMENTACION-T42.md. Antes, Seguimiento FIN-09 de T61: 13/143 dirigidas y completa **977 aprobadas, 6 omitidas, 8541 aserciones, 697,65 s, salida 0**, wings_testing_codex, corte 983. [Salida del seguimiento](../06-pruebas/PRU-04/evidencia-t61-transferencia/suite-seguimiento-fecha.txt). Antes, T61 corregida, Codex CyE 20/10: 976 aprobadas, 6 omitidas, 8527 aserciones, 804,11 s, salida 0 en wings_testing_codex; corte 982. Antes, T64 Gemini CAB 20/10: 8 pruebas en ReglaPrimerPagoSinHuecosT64Test y 1 en CapturaConfiguracionReglasT64Test; dirigidas y capturas en verde en wings_testing_gemini; corte 982. Antes, Verificación T52, Codex CyE 20/10: completa 962 aprobadas, 5 omitidas, 2 fallidas T33, 8507 aserciones, 1019,61 s, salida 2; T33 aislada 10/120 verdes, 179,90 s, salida 0, sin causa atribuida. T50 Gemini CAB 20/10: 10 pruebas en FechaIngresoFuturaT50Test y 1 en CapturaT50Test; suite rápida y dirigidas verdes en wings_testing_gemini; corte 969. Antes, T52 segunda parte, Claude CyE 20/10: 952 aprobadas, 4 omitidas, 2 fallidas (contador, y CancelarLiquidacionCerradaTest por un alumno de prueba sin fecha de ingreso, corregido: 9/9), 8472 aserciones, 952 s en wings_testing_claude; corte 958. Antes, Integración T61 + T52, Codex CyE 20/10: 952 aprobadas, 4 omitidas, 8455 aserciones, 1102,57 s, salida 0 en wings_testing_codex; corte 956. Fuentes T61 iguales a entrega CAB y T52 intacta. Antecedente T52 Claude CAB 20/10: suite rápida en wings_testing_claude, 835 aprobadas, 4 omitidas; las 2 fallidas son de T61 sin subir (Codex); corte 944. Antes, T74 Codex CyE 19/10: 935 aprobadas, 4 omitidas, 8243 aserciones, 1283,46 s, salida 0 en wings_testing_codex; corte 939. Antes, T34 C1/C2 Gemini CyE 19/10: 934 aprobadas, 4 omitidas, 8239 aserciones, 1498.66 s, salida 0 en wings_testing_gemini; corte 939 (agrega 5 pruebas de C1 y C2 en ClaseSinAsistenciaYDniT34T67Test). Antes, T40 Claude CyE 19/10: 929 aprobadas, 4 omitidas, 1 fallida (este contador, antes de actualizarlo a 934), 8215 aserciones, 1346 s en wings_testing_claude; corte 934. Antes, T34 Codex CyE 19/10: 921 aprobadas, 4 omitidas, 8179 aserciones, 1343,15 s, salida 0 en wings_testing_codex; corte 925. Antes, T66 Codex CAB 19/10: 920 aprobadas, 4 omitidas, 8155 aserciones, 960,48 s, salida 0 en wings_testing_codex; corte 924. Antes, T34 corrección Gemini CAB 19/10: 903 aprobadas, 4 omitidas, wings_testing_gemini, corte 908 (agrega 2 pruebas de H1 y H2 en ClaseSinAsistenciaYDniT34T67Test). Antes, T59/T47 Claude CAB 19/10: 901 aprobadas, 4 omitidas en wings_testing_claude; corte 906 (incluye T56/T68). Antes, T74: 886 aprobadas, 3 omitidas en wings_testing_claude; corte 890. Antes: Suite T44 corrección visual Codex CAB 19/10: 882 aprobadas, 3 omitidas, 7821 aserciones, 1134.95 s, wings_testing_codex, corte 885. Ver IMPLEMENTACION-T44.md.
```

Entrega P1 del 05/10: **427 pruebas / 2968 aserciones**, verdes en copia exclusiva, sin las 8 pruebas de P2 aún sin commit.
Ver [resultado y límites](../06-pruebas/PRU-02/P1-IMPLEMENTACION-2026-10-05.md).
Suite completa el 22/09: incluye ENT-01, ENT-06 y FIN-10, FIN-11, SEG, ENT-05, FIN-06, FIN-13, FIN-09, FIN-12
y el seeder de primera carga.
**Migraciones pendientes en produccion: doce al 08/10** (corre `314e485`: siete ya estaban sin correr el 06/10, leído con `migrate:status`, y main sumó cinco). Lo que sigue es el conteo viejo del 22/09, cuando eran seis sobre `81f27ef`. Lista
sacada de git el 22/09 con `git log 81f27ef..origin/main --diff-filter=A -- database/migrations`,
no escrita a mano: la version anterior de esta lista tenia cuatro y se le habian pasado dos.

| Migracion | Tarea | Ojo |
|---|---|---|
| `add_detalle_anulacion_to_pagos_table` | FIN-03 | No recupera detalles de anulaciones antiguas |
| `add_motivo_cambio_horario_to_clases_table` | FIN-10 | Sin ella, editar el horario de una clase pasada rompe |
| `add_porcentaje_comision_aplicado_to_liquidaciones_table` | FIN-06 | Backfill de liquidaciones COMISION |
| `congelar_tarifa_y_minutos_en_liquidacion_hora` | FIN-13 | **Toca liquidaciones existentes**: congela tarifa y minutos |
| `add_destino_de_avisos_a_configuraciones` | Avisos | Crea las dos claves de destino de avisos |
| `permitir_cancelar_liquidacion_cerrada_no_pagada` | FIN-12 | Estado CANCELADA y auditoria |

**No importar ni volver a versionar `database/dump.sql`.** Fue retirado el 05/09.

**No correr `CatalogosSeeder` a mano sobre una base con datos**: puede quitar la
proteccion de los rubros `Cuotas` y `Sueldos`. Sirve solo para armar una base nueva.
El despliegue del servidor no lo ejecuta, y el script viejo de Windows que lo corria
solo (`deploy-wings.bat`) se elimino el 11/09.

## 2. Decisiones necesarias antes de programar

- [x] **Profesores por hora: ¿cobran por clase o por hora de duracion?** Decidido el
  17/09: por duracion (clase de 1,5 hs a $5.000 = $7.500). Implementado como FIN-13.
- [ ] **`monto_base` de los pagos:** definir que tiene que valer en un cobro con seña o
  con varios meses. Hoy se guarda mal y nadie lo lee, pero el recibo nuevo lo va a querer.
- [x] ~~**Regla en la base para liquidaciones**~~ — **RETIRADA el 12/09. No la apruebes.**
  La regla que te propuse (una unica sobre `referencia_tipo` + `referencia_id`)
  **rompe la validacion de cajas**: una caja validada crea un asiento por cada cobro y
  todos llevan la misma referencia, asi que cualquier caja con mas de un movimiento
  fallaria. La version que funcionaria exige una columna generada y un indice sobre
  ella, en la tabla de plata de la base definitiva. **Recomendacion corregida: no
  hacerlo**; el bloqueo de FIN-05 ya resuelve el caso real y esta probado con dos
  conexiones. Detalle en el plan, seccion FIN-05.
- [x] **COB-04:** un pago anulado **no** cuenta como primer pago. Decidido el 10/09;
  corregido y verificado en navegador.
- [x] **FIN-04, definición:** Carlos distinguió resultado del período y saldo acumulado el 22/09.
  A10 aprobado 08/10 muestra resultado sin saldo inicial; aplicado y cerrado, verifica Claude en segunda revisión; Limpiar conserva el período con clic real en los cuatro modos.
  Saldo acumulado financiero aplicado localmente en V3 B12, 09/10; faltan clasificación de antecedentes y las demás pantallas. No requiere repetir la decisión.
- [x] **FIN-08, retoques de la pantalla Revision** (encontrados por Gemini 21/09): mostrar el
  error si la nota falta, filtros que no se aprieten en celular y sacar un script duplicado.
  **Implementados por Gemini el 22/09:** banner de `$errors` visible sin JS (`ds-flash--error`),
  filtros con `filtros-row` y `flex-wrap` para apilarse en celular, y retiro del `<script>`
  duplicado (scripts de CSP bajan a 21).
- [x] **FIN-08:** decidido y hecho el 21/09. El operativo resuelve la revision; parcial ya no aplica;
  "Continua" usa la tarifa aplicable al alumno y al período, por historial (T44).
  Ya decidido (17/09): la hace el operativo, no solo el admin; las notas viejas se conservan.
- [x] **FIN-09:** decidido 17/09. Nada futuro; mes en curso y anterior normal; mas viejo
  se carga con su fecha real en la caja de hoy (no se tocan cajas controladas), avisa en
  pantalla y manda mail y Telegram al admin. El reporte del mes viejo cambia: aceptado. Falta tu OK:
  el aviso salta desde el mes anterior (no solo lo muy viejo) y el reporte del mes viejo
  lista siempre los ajustes cargados despues, con fecha de carga y quien los cargo.
- [x] **PRU-03 / A2-B2:** definido por Carlos el 23/09: sin saldo pendiente es AL DÍA;
  la cuota nace en el alta. Implementación local con pruebas; falta verificación por
  otro agente en código y pantalla, sin despliegue en esta tarea.
- [x] **ENT-01:** aprobado el 22/09: una inscripción por DNI, cargos separados, cobro
  primero de inscripción, sin comisión. Implementada; revisión cruzada y actualización
  del servidor por Claude. ENT-10 conserva los manuales pendientes.

## 3. Recursos que faltan

- [ ] **ENT-02:** entregar o aprobar logo y paleta del club para rediseñar el recibo.
- [x] **ENT-03:** entregar o aprobar el recurso del favicon. (Aprobado por Carlos el 12/09/2026: patín artístico con alas zoom 95% e implementado)
- [ ] **SEG-12, postergada por vos el 22/09:** un dia tranquilo, con Wings terminado, vemos
  seguridad y contrasenas de todo, explicado sin tecnicismos. Ya decidido: guardarlas en el
  administrador de contrasenas de Google. Sin urgencia: la clave de respaldos ya esta a salvo.
- [ ] Pasar credenciales del servidor y clave de backups a un administrador de
  contraseñas. No ponerlas en el repositorio ni en las bitacoras.

## 4. Para la entrega

- [x] FDS-02: Carlos confirmo email y Telegram de las pruebas de monitoreo el 09/09. No falta configurar otra cuenta ni entregar nuevamente el token.
- [x] Vanina tiene cuenta ADMIN en el servidor, verificado el 08/09.
- [ ] Confirmar si habra otros usuarios reales y sus roles.
- [ ] Cargar deportes, niveles, grupos, planes, tipos de caja y demas datos reales por
  las pantallas acordadas.
- [ ] **Primera carga de alumnos y deuda inicial:** descargar la plantilla desde
  `/sistema/primera-carga`, completarla con los alumnos y la deuda inicial por período, y
  realizar la carga inicial unificada por pantalla.
- [x] **Ensayo de restauracion hecho el 22/09** por Claude, contra el respaldo real del
  dia: se restaura completo. El respaldo del servidor sirve.
- [ ] Reservar dos o tres horas para el recorrido humano completo, despues de cerrar
  los defectos prioritarios de cobro.
- [ ] Acompañar la apertura y cierre de la primera caja real.
- [ ] Firmar el gate final de produccion.
- [ ] **Lo legal (T10, borradores del 17/10):** leer `docs/01-producto/legal/README.md` y:
  completar los datos entre corchetes (razon social, CUIT, domicilio, correo, ciudad);
  elegir si el abono de USD 30 se paga en dolares o en pesos al cambio BNA; confirmar con el
  contador si lleva IVA; confirmar el pais del centro de datos de Hostinger; inscribir a
  Gestionar-te en el Registro Nacional de Bases de Datos de la AAIP (TAD, clave fiscal
  nivel 2) y pedirle a cada club que inscriba su base; imprimir la leyenda en la ficha de
  inscripcion de cada club.

## 5. Ya resuelto — no volver a pedir

- Acceso SSH, dominio, TLS y Cloudflare.
- Backups diarios cifrados con copia a Drive.
- Dump fuera del repositorio y exportacion automatica eliminada.
- Suite funcionando sobre MariaDB.
- Base del servidor preparada para la carga humana.
- Cuenta ADMIN de Vanina creada.
- Acceso de Cobranza para OPERATIVO.
