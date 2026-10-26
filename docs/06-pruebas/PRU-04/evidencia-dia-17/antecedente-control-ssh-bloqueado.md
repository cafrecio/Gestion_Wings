# Día 17 — lunes 26/10/2026 — control previo incompleto

26/10/2026 · Codex CyE · chat «Prueba Manual GW I».

Fuente de verdad: [GUION-DIAS-17-18.md](GUION-DIAS-17-18.md), leído completo después de `git pull` (`Already up to date.`, HEAD `0542eec9`). Las operaciones están autorizadas; **no se inició el día** porque no se pudo completar el requisito técnico previo. No es una ejecución aprobada ni una diferencia financiera.

## Bloqueo comprobado

Desde Ventas_CyE, `ssh vps hostname` no conecta: vence el tiempo de conexión al puerto 22, antes de autenticar. Se repitió con timeout explícito y fuera del entorno restringido; mismo resultado. Se ejecutó el instalador de acceso previsto por AGENTS: encontró clave y alias existentes, pero su prueba también agotó el tiempo. No se cambió la configuración SSH ni se tocaron claves.

Salida del intento con diagnóstico:

```text
OpenSSH_for_Windows_9.5p2, LibreSSL 3.8.2
debug1: Reading configuration data C:\\Users\\cafre/.ssh/config
debug1: C:\\Users\\cafre/.ssh/config line 2: Applying options for vps
debug1: Connecting to 2.25.204.38 [2.25.204.38] port 22.
debug1: connect to address 2.25.204.38 port 22: Connection timed out
ssh: connect to host 2.25.204.38 port 22: Connection timed out
```

Salida del intento fuera del entorno restringido (salida 1):

```text
ssh: connect to host 2.25.204.38 port 22: Connection timed out
```

El instalador terminó con salida 1: `ssh.exe : ssh: connect to host 2.25.204.38 port 22: Connection timed out`. El navegador sí abrió el sitio de prueba y permitió iniciar sesión de Admin. Pruebas TCP directas adicionales, con espera de 4 s por destino: [salida](evidencia-dia-17/00-pruebas-conexion.txt). También agotaron el tiempo VPS:443 y GitHub:22. Esto **no determina** si la causa está en la red local, en un filtro intermedio o en el servidor; no se atribuye a claves ni a un defecto de Wings.

## Reloj y rutinas

| Comando previsto | Ejecuciones | Salida |
|---|---:|---|
| `artisan wings:fecha-simulada "2026-10-26 15:00"` | 0 | No enviado: SSH no conectó. |
| `scripts/pruebas/rutina/rutina.php docs/06-pruebas/PRU-04/rutina/dia-17-manana.json --simular` | 0 | No enviado: requisito previo pendiente. |
| Rutina real `dia-17-manana.json` | 0 | No enviada. Debe ir después del 17.1. |
| Rutina `dia-17-noche.json` | 0 | No enviada. |

No se inventa salida de scripts. No se consultó la base del servidor. La pantalla actualizada de Clases dice domingo 25/10; Inicio y Reportes cortan al 25/10. No se fijó el reloj del 26/10. Estos datos son una **consulta previa al reloj**, no el control exigido después de fijarlo y simular.

## Comparación por pantalla, solo lectura

La pestaña existente tenía datos viejos del día 4. Se recargó antes de confiar en ella; pidió ingreso y se inició con Admin de prueba. Solo menús y enlaces, sin URLs internas escritas ni operaciones de negocio. Evidencia: [carpeta y alcance](evidencia-dia-17/LEEME.md).

| Control | Guion al cierre del día 16 | Observado hoy, antes de fijar reloj | Resultado limitado |
|---|---|---|---|
| Cobranza | Deuda $391.000; 104 activos, 99 Al día, 0 En plazo, 1 Moroso y 4 Deudores | Exactamente esos importes y estados | Coincide en la consulta previa |
| Cinco alumnos con deuda | Alma $58.000; Santino $84.000; Berenice $129.000; Felipe $76.000; Joaquín $44.000 | Cinco filas concretas: los cuatro primeros Deudor, Joaquín Moroso. Santino desde agosto, tres cuotas | Coincide en la consulta previa |
| Reportes octubre | Ingresos $1.824.850,75; egresos $240.850 | Coinciden. Resultado $1.584.000,75 | Coincide en la consulta previa |
| Disponible | $3.337.000,75: EFE $1.182.900; MP $1.888.550,75; Galicia $137.550; Nación $128.000 | Coinciden los cinco importes. Nación ahorro $0; todos por validar $0 | Coincide en la consulta previa |
| Sin asistencia | Una clase, #76 del sábado 24/10 | Listado 1; Ver abre #76, Fútbol Avanzadas 11–12, Hernán, 15 alumnos, cero presentes marcados. Sin guardar | Coincide en la consulta previa |
| Julieta Álvarez | Inactiva, cinco cuotas, $143.000 | Ficha #262 Inactivo, Deudor $143.000: Nov/Dic 2025 $22.000 c/u; Ago/Sep/Oct 2026 $33.000 c/u. Sin pagos | Coincide en la consulta previa |

## Operaciones del día

**17.1–17.7: sin ejecutar.** No se cambió la clave de Rocío, no se abrió caja, no se cobró, no se creó profesor/usuario, no se guardó asistencia ni se reactivó a Julieta. Tampoco se cerró o validó caja. No se reparó ninguna diferencia.

## Continuidad

T14 sigue en curso, `tiene=Claude`. Antes de continuar debe estar disponible el acceso al servidor para fijar el reloj una vez y ejecutar una vez la simulación. Después, volver a comparar el punto de partida y respetar el orden: 17.1 → rutina real de mañana → 17.2–17.7 → rutina de noche → cierre comprobado → día 18. No hay operaciones nuevas que deban deshacerse o repetirse.

Sin cambios de producto, tabla externa, datos de negocio, despliegue, suite ni push. El índice de código no estuvo disponible (`Transport closed`); no se hizo análisis estructural ni se usó un índice como prueba.
