# Wings — Checklist de Carlos

> **Actualizado:** 08/09/2026
> **Plan vigente:** `docs/07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md`,
> version 2026-09-08.v4.

Solo contiene acciones o decisiones que necesitan a Carlos. Las tareas tecnicas de
Codex y Claude no van aca.

## 0. Para retomar — 21/09/2026

Suite local actual: 335 pruebas / 1900 aserciones (26/09). **5 en rojo a proposito:**
son las de A43, escritas antes del arreglo que esta haciendo Codex. La lista
tecnica completa, con dueño, esta arriba de los tres logs (`LOG-CLAUDE`, `LOG-CODEX`,
`LOG-GEMINI`), entrada "PENDIENTES comunes".

**Lo que necesita tu decision o tu presencia:**

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

   **No tenes que hacer nada:** CAB y CyE estan autorizadas desde el 22/09 (CyE con la
   clave que cargaste en GitHub). Si en una maquina no entra, el agente corre el script.
7. Ejecutar la suite completa:

```bash
php artisan test          # 335 pruebas deben pasar
```

Corte verificado del 23/09: **335 pruebas / 1900 aserciones**, todas verdes; base descartable exclusiva.
Suite completa el 22/09: incluye ENT-01, ENT-06 y FIN-10, FIN-11, SEG, ENT-05, FIN-06, FIN-13, FIN-09, FIN-12
y el seeder de primera carga.
**Migraciones pendientes en produccion: seis** (el servidor corre `81f27ef`). Lista
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
- [ ] **FIN-04:** decidir si “Balance” de Cashflow significa saldo acumulado o
  resultado del periodo.
- [x] **FIN-08, retoques de la pantalla Revision** (encontrados por Gemini 21/09): mostrar el
  error si la nota falta, filtros que no se aprieten en celular y sacar un script duplicado.
  **Implementados por Gemini el 22/09:** banner de `$errors` visible sin JS (`ds-flash--error`),
  filtros con `filtros-row` y `flex-wrap` para apilarse en celular, y retiro del `<script>`
  duplicado (scripts de CSP bajan a 21).
- [x] **FIN-08:** decidido y hecho el 21/09. El operativo resuelve la revision; parcial ya no aplica;
  "Continua" usa el precio vigente del plan.
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
- [ ] **Cuando Vanina termine de cargar los alumnos:** exportar el padron, hacerselo
  completar con DEBE por alumno, e importarlo. Cierra el mes de corte y Wings arranca
  a facturar el mes siguiente. Procedimiento en
  `docs/06-pruebas/CARGA-PADRON-SALDO-INICIAL.md`.
- [x] **Ensayo de restauracion hecho el 22/09** por Claude, contra el respaldo real del
  dia: se restaura completo. El respaldo del servidor sirve.
- [ ] Reservar dos o tres horas para el recorrido humano completo, despues de cerrar
  los defectos prioritarios de cobro.
- [ ] Acompañar la apertura y cierre de la primera caja real.
- [ ] Firmar el gate final de produccion.

## 5. Ya resuelto — no volver a pedir

- Acceso SSH, dominio, TLS y Cloudflare.
- Backups diarios cifrados con copia a Drive.
- Dump fuera del repositorio y exportacion automatica eliminada.
- Suite funcionando sobre MariaDB.
- Base del servidor preparada para la carga humana.
- Cuenta ADMIN de Vanina creada.
- Acceso de Cobranza para OPERATIVO.
