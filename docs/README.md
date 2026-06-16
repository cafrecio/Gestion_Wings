# Documentacion Wings

Este directorio concentra la documentacion viva del proyecto Wings.

La fuente de verdad operativa es:

- `docs/00-estado/ESTADO-ACTUAL.md`

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
4. Para vistas Blade o CSS, revisar siempre `03-diseno-ui/wings-design/SKILL.md`.
5. Para seeders o pruebas funcionales, revisar siempre `06-pruebas/PLAN-PRUEBAS-FUNCIONALES.md`.
6. No usar `README.md` raiz como fuente de verdad del proyecto; se conserva como archivo base de Laravel.
