# PRU-04 · Día23 · Entrega parcial por decisión de Carlos

Codex CyE · 26/10/2026 · TEST/wingstest · sitio500ce7bc · guion0abc15af.

[Guion congelado](evidencia-dia-23/guion-congelado.md). Reloj fijado una sola vez al01/11 05:55. Comparador/esperado copiados a/tmp/wings-dia23-20261026, legibles por wingstest y con SHA256 coincidente. No producto, despliegue ni pagos.

## Resultado y alcance final de esta ejecución

Carlos cambió la instrucción de cerrar las ocho: **«deja las demas abiertas»**. Se detuvieron los cierres. Revalidación posterior por pantalla: **3 Cerradas y 5 Abiertas; ninguna Pagada**. [Listado final](evidencia-dia-23/47-revalidacion-continuidad-tres-cerradas-cinco-abiertas.txt) · [captura completa](evidencia-dia-23/47-revalidacion-completa.jpg). T14 queda en curso para revisión independiente de Claude; no se certifica el guion completo.

-23.1: automática a06:00:07; constancia06:00:09,100creadas/3existentes/0Revisión. Antes05:55:20 sin noviembre y Cobranza0/103Al día.
-23.2/23.3: ambos comparadores100de100,total3.783.000,0sobran/faltan/diferentes/duplicadas/Revisión. Segunda manual:0creadas,103existentes.
-23.4: Cobranza3.783.000,103activos,100En plazo/3Al día.
-23.5: Alma44.000,Lucas38.000,Mora44.000Patín/28.000Fútbol;FelipeAl día,noviembre38.000pagado22/10 sin deuda nueva visible.
-23.6: selectorLucía20, generada17/204.000; coincide con T87 conocido.
-23.7–23.10:8generadas/importes correctos;9atrasadasHernán(128.400) y4Martín(46.200)validadas. Cerrar no se ofrecía mientras quedaban pendientes. Tras recuperarse el navegador quedaron cerradas Lucía octubre/septiembre y Verónica octubre; las otras cinco se conservan abiertas por la última orden de Carlos.
-23.11: parcial; Inicio muestra3 pendientes/por pagar345.000, disponible4.959.900,75. No cumple el esperado original de8Cerradas/1.370.280: Carlos detuvo los cierres. [Inicio comprobado](evidencia-dia-23/46-inicio-tres-cerradas-cinco-abiertas.txt).
-23.12: opcional; no ejecutado.

## Liquidaciones al detener los cierres

| Profesor | Mes | ID | Clases | Importe confirmado | Estado |
|---|---|---:|---:|---:|---|
| Lucía Gaitán | Octubre2026 | 2 | 17 | $204,000 | Cerrada, sin pagar |
| Lucía Gaitán | Septiembre2026 | 3 | 3 | $36,000 | Cerrada, sin pagar |
| Verónica Salinas | Octubre2026 | 4 | 7 | $105,000 | Cerrada, sin pagar |
| Verónica Salinas | Septiembre2026 | 5 | 2 | $30,000 | Abierta, sin pagar |
| Mariela Ocampo | Octubre2026 | 6 | 15 | $270,000 | Abierta, sin pagar |
| Mariela Ocampo | Septiembre2026 | 7 | 3 | $54,000 | Abierta, sin pagar |
| Hernán Quintana | Octubre2026 | 8 | Comisión | $484,680 | Abierta, sin pagar |
| Martín Acosta | Octubre2026 | 9 | Comisión | $186,600 | Abierta, sin pagar |

Total generado: **$1.370.280**. Cerradas: **$345.000**; abiertas: **$1.025.280**. Ninguna pagada. El [registro de preparación](evidencia-dia-23/liquidaciones-registro.json) conserva el corte histórico de ocho abiertas; el [registro final](evidencia-dia-23/estado-final-liquidaciones.json) lo sustituye para continuar.

## Diferencias y límites

Inicio muestra ingresosnoviembre103.000 antes de generar, guion0; diferencia conservada, sin atribuir causa. Disponible inicial4.959.900,75. Los metadatos del esperado dicen precio_vigente_desde18/10 para10cuotas PatínPrincipiantes2veces; el guion dice aumento desde noviembre. Los importes de noviembre autorizados coinciden y se verificaron sin editar fuentes. JSON de liquidaciones contiene además Vanina/septiembre1clase/0; se generaron solamente las8positivas del guion.

[Rechazo automático histórico](evidencia-dia-23/35-bloqueo-cierres-y-autorizacion-pendiente.md). No se sustituyeron cierres por consola; ninguna liquidación pagada.

## Salidas completas

### 00-entorno-previo.txt

[Archivo](evidencia-dia-23/00-entorno-previo.txt)

```text
500ce7bc

   INFO  The application environment is [staging].

Fecha simulada fijada: 2026-10-31 15:00:00. Para Wings ahora son las 31/10/2026 18:03
total 36
-rw-r--r-- 1 root root  3293 Oct 26 19:24 comparar-generacion.php
-rw-r--r-- 1 root root 32232 Oct 26 19:24 esperado.json
283076 storage/logs/scheduler.log
DB=wingstest; ahora=2026-10-31 18:03:52
[
    {
        "id": 1,
        "periodo": "2026-10",
        "terminada_at": "2026-10-21 14:05:23",
        "creadas": 0,
        "a_revision": 0,
        "ya_existian": 0,
        "sin_plan": 0,
        "created_at": "2026-10-21 14:05:23",
        "updated_at": "2026-10-21 14:05:23"
    }
]
```

### 01-reloj-y-tabla-antes.txt

[Archivo](evidencia-dia-23/01-reloj-y-tabla-antes.txt)

```text
7b2ad2a5431bdae3d108eac4e40af59b7fbc266c6e8d6b25bf49575d7589749f  /tmp/wings-dia23-20261026/comparar-generacion.php
f5b1229b9f41c71bef062272169bb562b8795e967ce38c1601a778e961ea642b  /tmp/wings-dia23-20261026/esperado.json

   INFO  Configuration cache cleared successfully.

Wings ahora cree que es domingo 1 de noviembre de 2026, 05:55
El reloj avanza solo desde esa hora y se detiene a la medianoche de ese día.
Las sesiones abiertas se caen al saltar de día: hay que volver a entrar.
inactive
```

### 02-generaciones-antes-y-programador.txt

[Archivo](evidencia-dia-23/02-generaciones-antes-y-programador.txt)

```text
Ahora=2026-11-01 05:55:20
Generaciones noviembre: []
active



SHELL=/bin/bash
* * * * * /bin/bash /home/wingstest/app/scripts/servidor/ejecutar-scheduler-test.sh >> /home/wingstest/app/storage/logs/scheduler.log 2>&1
   INFO  No scheduled commands are ready to run.

2026-10-26T19:24:02+00:00 scheduler-test fin estado=0
2026-10-26T19:25:02+00:00 scheduler-test inicio usuario=wingstest

   INFO  No scheduled commands are ready to run.

2026-10-26T19:25:03+00:00 scheduler-test fin estado=0
```

### 08-generacion-automatica-log-y-tabla.txt

[Archivo](evidencia-dia-23/08-generacion-automatica-log-y-tabla.txt)

```text
Ahora=2026-11-01 06:02:13
[
    {
        "id": 2,
        "periodo": "2026-11",
        "terminada_at": "2026-11-01 06:00:09",
        "creadas": 100,
        "a_revision": 0,
        "ya_existian": 3,
        "sin_plan": 0,
        "created_at": "2026-11-01 06:00:09",
        "updated_at": "2026-11-01 06:00:09"
    }
]
2026-10-26T19:25:02+00:00 scheduler-test inicio usuario=wingstest

   INFO  No scheduled commands are ready to run.

2026-10-26T19:25:03+00:00 scheduler-test fin estado=0
2026-10-26T19:26:01+00:00 scheduler-test inicio usuario=wingstest

   INFO  No scheduled commands are ready to run.

2026-10-26T19:26:02+00:00 scheduler-test fin estado=0
2026-10-26T19:27:02+00:00 scheduler-test inicio usuario=wingstest

   INFO  No scheduled commands are ready to run.

2026-10-26T19:27:03+00:00 scheduler-test fin estado=0
2026-10-26T19:28:01+00:00 scheduler-test inicio usuario=wingstest

   INFO  No scheduled commands are ready to run.

2026-10-26T19:28:02+00:00 scheduler-test fin estado=0
2026-10-26T19:29:01+00:00 scheduler-test inicio usuario=wingstest

   INFO  No scheduled commands are ready to run.

2026-10-26T19:29:02+00:00 scheduler-test fin estado=0
2026-10-26T19:30:02+00:00 scheduler-test inicio usuario=wingstest

  2026-11-01 06:00:07 Running ['artisan' cobranza:generar-deudas --si-falta]  1s DONE
  ⇂ '/opt/remi/php82/root/usr/bin/php' 'artisan' cobranza:generar-deudas --si-falta > '/dev/null' 2>&1

2026-10-26T19:30:05+00:00 scheduler-test fin estado=0
2026-10-26T19:31:01+00:00 scheduler-test inicio usuario=wingstest

   INFO  No scheduled commands are ready to run.

2026-10-26T19:31:02+00:00 scheduler-test fin estado=0
2026-10-26T19:32:02+00:00 scheduler-test inicio usuario=wingstest

   INFO  No scheduled commands are ready to run.

2026-10-26T19:32:03+00:00 scheduler-test fin estado=0
```

### 09-comparador-primero.txt

[Archivo](evidencia-dia-23/09-comparador-primero.txt)

```text
Período 2026-11 · generación registrada: sí
Coinciden 100 de 100 · total generado $3.783.000 (esperado $3.783.000)
Sobran: 0
Faltan: 0
Importe distinto: 0
Cuotas duplicadas: 0
Revisión de 2026-11: 0
```

### 10-segunda-generacion-manual.txt

[Archivo](evidencia-dia-23/10-segunda-generacion-manual.txt)

```text
=== Generación de deudas para período: 2026-11 ===
Verificando asistencias del mes anterior: 2026-10

=== Resumen ===
Total alumnos activos: 103
Deudas creadas: 0
Enviados a revisión: 0
Ya existían (skipped): 103
```

### 11-comparador-segundo.txt

[Archivo](evidencia-dia-23/11-comparador-segundo.txt)

```text
Período 2026-11 · generación registrada: sí
Coinciden 100 de 100 · total generado $3.783.000 (esperado $3.783.000)
Sobran: 0
Faltan: 0
Importe distinto: 0
Cuotas duplicadas: 0
Revisión de 2026-11: 0
```

[Control documental independiente de la preparación](evidencia-dia-23/VERIFICACION-DOCUMENTAL-PREPARACION.md): corte histórico anterior a los tres cierres; no certifica el resultado posterior.

## Incidente de confirmación y orden vigente

La autorización anterior fue «Sí, cerrá las ocho desde el integrado, sin pagar». El bloqueo de acceso documentado en la [constancia37](evidencia-dia-23/37-autorizacion-carlos-y-navegador-deshabilitado.md) se superó; ya no describe el estado actual.

Lucía octubre: el clic del integrado agotó la espera; la lectura inicial seguía Abierta y una posterior mostró Cerrada. Carlos informó su confirmación manual: «Yo te acepte lucia». Lucía septiembre: Codex envió la aceptación del diálogo y comprobó Cerrada. Verónica octubre: el clic se envió antes de la orden de detener; agotó la espera y luego se comprobó Cerrada. No se reenviaron los clics tras los resultados inciertos.

**Aclaración posterior de Carlos:** «Confirme tambien las otras dos, salieron compulsivamente». Queda registrada la **confirmación manual de Carlos para las tres** (Lucía octubre/septiembre y Verónica octubre), según su declaración. El envío de aceptación por la herramienta en septiembre no permite atribuirle en exclusiva la confirmación. Los cuadros aparecieron sucesivamente según Carlos; no se provocaron más cierres después de su orden de detener.

Última orden: «Yo te acepte lucia. Pero si sos IA, hace un modal propio la proxima vez. El de Windows nos caga a todos, deja las demas abiertas». Al consultar el cuadro pendiente ya no había diálogo; no se aceptó ni canceló otro cierre. Listado posterior y revalidación en esta continuación confirman3Cerradas/5Abiertas. No reabrir ni cerrar las restantes, no pagar ni repetir generación/reloj/validaciones.

**Pedido para la próxima prueba:** sustituir la confirmación nativa por un modal propio de Wings. Pedido registrado, **sin implementar** en esta ejecución. Necesita trabajo de producto y la revisión visual prevista por AGENTS.md; no se modifica ninguna vista para concluir esta prueba.

[Control documental independiente final](evidencia-dia-23/VERIFICACION-DOCUMENTAL-FINAL.md): estados, importes, enlaces y aclaración de Carlos consistentes; la revisión técnica de Claude sigue pendiente.
