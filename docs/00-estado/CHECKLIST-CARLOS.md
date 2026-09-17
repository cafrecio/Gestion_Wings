# Wings — Checklist de Carlos

> **Actualizado:** 08/09/2026
> **Plan vigente:** `docs/07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md`,
> version 2026-09-08.v4.

Solo contiene acciones o decisiones que necesitan a Carlos. Las tareas tecnicas de
Codex y Claude no van aca.

## 0. Para retomar — 16/09/2026

Todo esta subido a `main`; suite 229 pruebas / 1374 aserciones en verde. La lista
tecnica completa, con dueño, esta arriba de los tres logs (`LOG-CLAUDE`, `LOG-CODEX`,
`LOG-GEMINI`), entrada "PENDIENTES comunes".

**Lo que necesita tu decision o tu presencia:**

- [ ] **Clases particulares: ¿entra antes o despues de la prueba grande?** Antes cubre el
  modulo nuevo pero vuelve a correr el reloj; despues prueba lo que ya esta y lo
  suma sobre una base probada. Recomendacion de Claude: despues.
- [ ] **Asignar FIN-12** (cancelar liquidacion cerrada no pagada). Propuesta: Gemini.
- [ ] **Estar presente en el despliegue a wings**, cuando pase la prueba grande.

**El orden acordado:** actualizar test con los recibos nuevos → FIN-12 → prueba grande
en `test.gestionar-te` → ensayo de restauracion → desplegar en wings.

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
6. Ejecutar la suite completa:

```bash
php artisan test          # 229 pruebas deben pasar
```

Corte verificado del 13/09: **229 pruebas**, todas verdes.
Suite completa el 13/09: 229 pruebas / 1372 aserciones; incluye FIN-10, FIN-11, SEG, ENT-05, FIN-06
y el seeder de primera carga.
FIN-06 requiere aplicar la migración de porcentaje_comision_aplicado al desplegar (con backfill para liquidaciones COMISION).
FIN-10 requiere aplicar la migración de motivo_cambio_horario al desplegar; probada solo en wings_testing.
FIN-03 requiere aplicar la nueva migracion al desplegar; no fue aplicada a la
base de trabajo ni al servidor. No recupera detalles de anulaciones antiguas.
FIN-10 tambien agrega migracion (`motivo_cambio_horario` en `clases`): si se
despliega sin correrla, editar el horario de una clase pasada rompe en pantalla.

**No importar ni volver a versionar `database/dump.sql`.** Fue retirado el 05/09.

**No correr `CatalogosSeeder` a mano sobre una base con datos**: puede quitar la
proteccion de los rubros `Cuotas` y `Sueldos`. Sirve solo para armar una base nueva.
El despliegue del servidor no lo ejecuta, y el script viejo de Windows que lo corria
solo (`deploy-wings.bat`) se elimino el 11/09.

## 2. Decisiones necesarias antes de programar

- [ ] **Profesores por hora: ¿cobran por clase o por hora de duracion?** El diseño del
  recibo aprobado por Vanina paga por hora (una clase de 1,5 hs = $7.500); el sistema hoy
  paga por clase (cualquier clase = $5.000). Si es por hora, la liquidacion paga de menos
  y hay que corregirla antes de la primera real. **Bloquea el recibo nuevo.**
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
- [ ] **FIN-08:** definir como tratar una revision con pago parcial, observaciones
  previas y precio historico.
- [ ] **FIN-09:** definir hasta que fecha pasada o futura se permite cargar movimientos.
- [ ] **PRU-03:** confirmar que significa DEUDOR cuando el alumno no tiene pagos ni
  saldo pendiente.
- [ ] **ENT-01:** confirmar como se contabiliza la inscripcion configurable del alumno
  nuevo.

## 3. Recursos que faltan

- [ ] **ENT-02:** entregar o aprobar logo y paleta del club para rediseñar el recibo.
- [x] **ENT-03:** entregar o aprobar el recurso del favicon. (Aprobado por Carlos el 12/09/2026: patín artístico con alas zoom 95% e implementado)
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
- [ ] ~~Correr el ensayo de restauracion~~ — **lo hace Claude por SSH** (16/09), no
  es tarea tuya. Sigue pendiente: hasta hacerlo, que el respaldo del servidor sirva
  no esta demostrado.
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
