# Comparativa de auditoría — v02 vs v03

**Corte v03:** 2026-08-04 05:49:00 -03:00  
**Rama/commit:** `main` / `c763933ec34bbb44c1f88325af5c0b5b4eb5b566`  
**Fuentes:** `ANALISIS-INTEGRAL-v02.md` y `ANALISIS-INTEGRAL-v03.md`

## 1. Cómo leer esta comparación

Esta comparación no usa “no apareció” como sinónimo de “corregido”. Los estados son:

- **PERSISTE/CONFIRMADO:** v03 encontró nuevamente evidencia concreta.
- **AMPLIADO:** persiste y v03 agregó alcance/evidencia.
- **NUEVO:** v03 lo detectó y v02 no lo documentó.
- **NO REVALIDADO:** v03 no ejecutó el dato/request necesario; no implica corrección.
- **REFORMULADO:** la afirmación v02 era parcial, estaba sobredimensionada o se consolidó de otra forma.
- **CONDICIONAL:** código presente pero superficie actualmente apagada.

Hecho de control: v02 fue introducida en `ccc6c74` y desde entonces hasta el commit v03 solo cambiaron los propios documentos de evaluación. **No hubo cambios de código de aplicación.** Por eso, una falla estática de v02 no pudo haberse corregido en ese intervalo; aun así, v03 no la presenta como confirmada si no obtuvo evidencia suficiente o si la formulación anterior excedía la evidencia.

## 2. Resumen cuantitativo

| Métrica | v02 | v03 | Lectura |
|---|---:|---:|---|
| Hallazgos declarados | 41* | 57 | v03 separa áreas y añade infraestructura/docs/performance |
| Críticos declarados | 7* | 6 | Cambio de clasificación y deduplicación; no mejora del código |
| Altos declarados | 14* | 31 | Mayor granularidad y nuevos riesgos P0/P1 |
| Medios declarados | 14* | 15 | Similar magnitud, distinta agrupación |
| Bajos declarados | 6* | 4 | Varios se absorbieron en hallazgos mayores |
| Informativos | verificaciones negativas | 1 | v03 formaliza código/consumidores no verificables |
| Suite | 12 fallos, 2 pases | 12 fallos, 2 pases | Sin cambio; cobertura ejecutable de negocio nula |
| Dependencias | 38 Composer/10 npm | 38 Composer/10 npm | Confirmado nuevamente con red; severidades detalladas |

\* **Advertencia:** los conteos de v02 son los que ese documento declara, pero no concilian con sus propios IDs. Su tabla enumera 53 referencias y, aun descontando los dos duplicados explícitos (`S-02/P-01`, `F-01/D-04`), quedan 51 identificadores lógicos. Por eso ninguna diferencia numérica v02→v03 se interpreta como mejora o empeoramiento. v03 sí usa 57 IDs consecutivos y conteos mecánicamente verificados.

## 3. Principales diferencias

### Nuevos riesgos críticos de v03

1. **AUD-003 — XSS almacenado en autocomplete.** v02 declaró explícitamente que no había XSS; v03 trazó entrada persistida → JSON → `innerHTML` con líneas concretas.
2. **AUD-004 — Cuenta predecible y cashflow demo en deploy.** v02 revisó seeders/dump, pero no conectó `deploy-wings.bat → DatabaseSeeder → UserFactory/CashflowMovimientoSeeder`.

### Nuevos riesgos altos relevantes

- asistencia de alumno ajeno y guardado parcial (`AUD-011`);
- cambio de plan/cobro fuera de una transacción común (`AUD-012`);
- doble pago concurrente de liquidación (`AUD-018`);
- edición de clase sin solapamiento/trazabilidad (`AUD-019`);
- comisión dependiente de datos actuales mutables (`AUD-020`);
- pago anulado usado por el generador (`AUD-024`);
- ACL Windows amplias, deploy no atómico y backup/restore no confiable (`AUD-033` a `AUD-035`);
- fuente de verdad obsoleta y funciones “implementadas” solo en API apagada (`AUD-037`).

### Hallazgos v02 que v03 no afirma como actuales

- **A-01 `/cobranza` siempre 500:** no se hizo request autenticado ni se consultó DB. La ruta/servicio existen; queda NO REVALIDADO.
- **F-02 el generador no crea ninguna deuda:** el código contiene ramas que sí crean; v03 no ejecutó el comando sobre datos. Se reemplaza por fallas confirmadas de disparador/período/elegibilidad (`AUD-023`, `AUD-024`).
- **Datos reales sanos o afectados:** v02 consultó una DB y citó conteos/saldos; v03 no consultó esa DB. No se heredan esos conteos.

## 4. Mapa completo de hallazgos v02

| ID v02 | Estado en v03 | Mapeo / comentario |
|---|---|---|
| S-01 | **PERSISTE/AMPLIADO** | `AUD-001`; se agregó alcance a `/operativo`, movimientos, grupos y PII |
| S-02 / P-01 | **PERSISTE** | `AUD-002`; login y revocación siguen sin control global |
| S-03 | **PERSISTE** | `AUD-008`; dump aún versionado con PII/PAT/sesiones |
| S-04 | **PERSISTE/REFORMULADO** | `AUD-009` + `AUD-046`; debug/HTTP y mensajes técnicos |
| S-05 | **PERSISTE, absorbido** | Throttle web `10,1`; no se contó separado en v03 frente a riesgos P0 |
| S-06 | **PERSISTE, recomendación auxiliar** | Password mínima/sin autogestión; no ID separado v03 |
| S-07 | **PERSISTE, absorbido** | Falta auditoría durable reflejada en `AUD-017`, `AUD-019`, `AUD-045` |
| S-08 | **PERSISTE/ACTUALIZADO** | `AUD-007`; 38 Composer + 10 npm confirmados con red |
| S-09 | **NO REVALIDADO** | v03 no reprodujo contenidos PII del log; `AUD-049` cubre política/retención |
| S-10 | **PERSISTE/ABSORBIDO** | `AUD-009`, `AUD-053` |
| S-11 | **NO CONSOLIDADO** | Validación de fechas no fue hallazgo prioritario v03; sin cambio de código no se declara corregido |
| S-12 | **PERSISTE/ABSORBIDO** | Middleware de caja vieja sin disparador; `AUD-023`/arquitectura de middleware |
| S-13 | **PERSISTE CONDICIONAL** | `AUD-026`; API continúa apagada y sería insegura al reactivar |
| P-02 | **PERSISTE** | `AUD-010`; POST no scopea movimiento por caja |
| P-03 | **PERSISTE** | `AUD-022`; nuevo subrubro no valida reservado/rol/activo |
| P-04 | **PERSISTE** | `AUD-038`; contrato vs propiedad |
| P-05 | **PERSISTE/AMPLIADO** | `AUD-001`, `AUD-038`, `AUD-031`; historial global vs vistas propias |
| P-06 | **NO REVALIDADO** | No se comprobó UX admin específica; no afirmar corrección |
| P-07 | **PERSISTE/DEDUPLICADO** | Es la causa de P-02; `AUD-010` |
| P-08 | **PERSISTE/ABSORBIDO** | Middleware profesor no usado y scheduler/bloqueo no aplicado; `AUD-001`, `AUD-023` |
| P-09 | **REFORMULADO** | Migraciones normales dan enum/default, pero controles fail-open siguen siendo riesgo; usuarios reales no consultados |
| P-10 | **NO REVALIDADO** | Salvaguarda de último admin no se probó; revocación sí persiste en `AUD-002` |
| D-01 | **PERSISTE** | `AUD-005` |
| D-02 | **PERSISTE/ACLARADO** | `AUD-015`; v03 confirma que servicio usa caja y seeder movimiento, contrario al contrato |
| D-03 | **PERSISTE** | `AUD-016` |
| D-04 / F-01 | **PERSISTE** | `AUD-006` |
| D-05 | **PERSISTE** | `AUD-017` |
| D-06 | **PERSISTE** | `AUD-014` |
| D-07 | **PERSISTE** | `AUD-025` |
| D-08 | **PERSISTE CON MITIGACIÓN APLICATIVA** | No unique de caja abierta; apertura sí bloquea User. No ID separado v03 |
| D-09 | **PERSISTE/AMPLIADO** | `AUD-021`; ajuste debajo de pagado/condonación parcial |
| D-10 | **PERSISTE, BAJA** | Tipos DECIMAL de distintas precisiones; absorbido en integridad, sin ID propio |
| A-01 | **NO REVALIDADO** | No hubo request autenticado/DB; no afirmar corrección |
| A-02 | **PERSISTE, NO PRIORIZADO** | Accessor aún depende de relaciones cargadas; no ID separado |
| A-03 | **PERSISTE/REFORMULADO** | `AUD-039`; falta contrato/tabla única de cobranza |
| A-04 | **PERSISTE/ABSORBIDO** | `AUD-039`, `AUD-043`, `AUD-044` |
| T-01 | **PERSISTE** | `AUD-027`, `AUD-028` |
| T-02 | **PERSISTE/AMPLIADO** | `AUD-027`; v03 detectó cuatro migraciones MySQL incompatibles, no una |
| R-01 | **PERSISTE/REFORMULADO** | `AUD-044`; consulta global y regla duplicada |
| R-02 | **PERSISTE** | `AUD-031` |
| R-03 | **PERSISTE/AMPLIADO** | `AUD-048`; candidatos y filtros no sargables concretos |
| C-01 | **PERSISTE/AMPLIADO** | `AUD-023`; además período por defecto mes siguiente |
| C-02 | **REFORMULADO** | `.env.example` sí declara SQLite y variables MariaDB comentadas; riesgo real es deploy local/debug/root vacío (`AUD-009`) |
| F-02 | **NO REVALIDADO/REFORMULADO** | Código sí puede crear; v03 confirma scheduler/período/eligibilidad, no “ninguna deuda” |
| F-03 | **PERSISTE** | `AUD-039`; ausencia de deuda retorna AL_DIA |
| F-04 | **COMPORTAMIENTO CONFIRMADO, SEVERIDAD PENDIENTE** | Cierre inmutable parece intencional; bloqueo histórico requiere definición, no se contó defecto separado |
| F-05 | **PERSISTE/AMPLIADO** | `AUD-037`; capacidades solo en API apagada |
| F-06 | **PERSISTE** | `AUD-013` |
| F-07 | **NO REVALIDADO** | Regla de primer pago no fue hallazgo consolidado; no afirmar corrección |
| F-08 | **PERSISTE/AMPLIADO** | `AUD-020`; además alumno/deporte actuales mutables |
| DOC-01 | **PERSISTE/AMPLIADO** | `AUD-039`, `AUD-037`, `AUD-052`; falta contrato y gobernanza obsoleta |

## 5. Diferencias de metodología

| Dimensión | v02 | v03 |
|---|---|---|
| Agentes | Informe decía agentes, con nota de transparencia | Nueve especialidades ejecutadas y consolidación separada |
| DB viva | Incluyó consultas/conteos reales | Prohibición estricta: no se consultó DB productiva |
| Requests HTTP | Incluyó algunas verificaciones | No se enviaron requests mutantes; route-list y estática |
| Dependencias | Audits y reachability general | Audits confirmados con red y severidades; reachability separada |
| Infra Windows | Parcial | ACL, Task Scheduler, deploy, backup/restore, logs/cola |
| Documentación | Contradicciones principales | Barrido completo, settings inertes, manual/ER/API/fuente de verdad |
| Performance | Tres hotspots | Doce patrones con crecimiento y evidencia concreta |

## 6. Conclusión comparativa

No hay evidencia de mejora del sistema entre v02 y v03 porque no hubo cambios de código. v03 aumenta la confianza en varios hallazgos previos, corrige formulaciones demasiado absolutas de v02 y descubre riesgos críticos omitidos. La prioridad práctica cambia: además de permisos y contabilidad, debe bloquearse inmediatamente el XSS y retirarse el seeding general del despliegue.

Para una futura v04, cada hallazgo debe pasar por una de estas pruebas: **corregido y verificado**, **persiste**, **no revisado** o **no aplicable por decisión documentada**. Nunca “corregido porque no apareció”.
