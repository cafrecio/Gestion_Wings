# Documentacion Wings

Este directorio concentra la documentacion viva del proyecto Wings.

Condonación de saldo pendiente: [contrato V1, decisión FIN-07 del 12/09](02-contratos/Wings-Contrato-Condonacion-V1.md).

Inicio común: [Resumen de arranque](00-estado/RESUMEN-ARRANQUE.md) y
[Protocolo de continuidad](00-estado/PROTOCOLO-CONTINUIDAD.md).
[Archivo íntegro del corte 12/09](99-archivo/bitacoras/2026-09-12/INDICE.md).
No leer el histórico de rutina; buscar por tarea.

Plan vigente: `07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md` y su indice HTML
`07-evaluacion/PLAN-TRABAJO-CARLOS-v2026-09-08.html` (version 4).
Las ocho evaluaciones historicas movidas se conservan sin cambios de contenido en
`07-evaluacion/Evaluaciones previas/`.

La fuente de verdad operativa es:

- `docs/00-estado/ESTADO-ACTUAL.md`

La continuidad del trabajo entre las computadoras de CyE y CAB se registra en:

- `docs/00-estado/LOG-CODEX.md`
- `docs/00-estado/LOG-CLAUDE.md`
- `docs/00-estado/LOG-GEMINI.md`

El informe del ultimo ciclo de trabajo esta disponible en dos formatos:

- `docs/00-estado/PROMPT-CODEX-1-260809.md` — traspaso tecnico para Claude Code.
- `docs/00-estado/PROMPT-CODEX-1-260809.html` — resumen explicado para personas no tecnicas.

El resultado de la prueba funcional cerrada del 9 de agosto de 2026 esta en:

- `docs/06-pruebas/RESULTADO-PRUEBA-260809.md` — evidencia tecnica y traspaso para Claude Code.
- `docs/06-pruebas/RESULTADO-PRUEBA-260809.html` — informe ejecutivo explicado para el usuario.

El mapa visual de trabajo esta en:

- `docs/00-mapa-proyecto/index.html`

## Estructura

| Carpeta | Uso |
|---|---|
| `00-estado/` | Estado actual, contradicciones, pendientes y prioridades confirmadas. |
| `00-mapa-proyecto/` | Mapa HTML navegable del repo y sus modulos. |
| `01-producto/` | Vision de producto, plan maestro, menu y decisiones funcionales generales. |
| `02-contratos/` | Contratos de negocio cerrados o semi-cerrados por modulo. |
| `03-diseno-ui/` | Design system, reglas visuales, skill de UI y referencias de implementacion. |
| `04-tecnico/` | Setup, entorno, base de datos, despliegue y notas tecnicas. |
| `05-pendientes/` | Pendientes crudos o listas de trabajo que todavia no fueron normalizadas. |
| `06-pruebas/` | Plan de pruebas funcionales, guia para colaboradores y resultados de ciclos de prueba. |
| `99-archivo/` | Historico, documentos viejos, respaldos y referencias no vigentes. |

## Reglas de uso

1. Antes de tocar funcionalidad, revisar `00-estado/ESTADO-ACTUAL.md`.
2. Si un documento historico contradice el estado actual, prevalece `ESTADO-ACTUAL.md`.
3. Los contratos en `02-contratos/` prevalecen sobre notas sueltas cuando describen reglas de negocio cerradas.
4. Antes de tocar cualquier control de acceso (permisos, roles, `abort(403)`, filtros por usuario), revisar siempre `02-contratos/PERMISOS-ROLES.md`.
5. Para vistas Blade o CSS, revisar siempre `03-diseno-ui/wings-design/SKILL.md`.
6. Para seeders o pruebas funcionales, revisar siempre `06-pruebas/PLAN-PRUEBAS-FUNCIONALES.md`.
7. No usar `README.md` raiz como fuente de verdad del proyecto; se conserva como archivo base de Laravel.
