# Protocolo de continuidad — Codex, Claude y Gemini

Vigente desde el 12/09/2026. Carlos autorizó el mismo flujo para los tres agentes.
Este protocolo organiza la lectura y el registro; no cambia reglas de negocio ni permisos.

## Al iniciar una sesión

1. Leer AGENTS.md completo y la guía del agente (CLAUDE.md o GEMINI.md, si corresponde).
   La guía extendida CLAUDE.md sigue aplicando a todos según AGENTS.
2. Leer RESUMEN-ARRANQUE.md completo y las tres bitácoras ACTIVAS:
   LOG-CODEX.md, LOG-CLAUDE.md y LOG-GEMINI.md. Son archivos cortos.
3. Antes de tomar una tarea, leer el índice compacto del plan vigente.
   Leer después SOLO el criterio, contrato, evidencia y detalle de esa tarea.
4. Revisar git status; respetar cambios ajenos. Comprobar entorno antes de operar.
5. En una sesión ya iniciada, leer solo novedades desde la última entrada vista.
   No volver a cargar los mismos documentos sin cambios.

No leer de rutina los archivos históricos, evaluaciones completas, todos los contratos
ni el HTML de Carlos. Buscar por ID de tarea, fecha o tema y abrir el fragmento necesario.
Los enlaces no son una orden de seguir recursivamente todos los documentos.

## Responsabilidades de los documentos

- RESUMEN-ARRANQUE.md: orientación común, último corte documental, bloqueos y siguiente paso.
  No certifica servidor, base o suite hoy por repetir una cifra histórica.
- Plan compacto: orden, estado declarado, dependencias y acceso al criterio de cada tarea.
- ESTADO-ACTUAL.md: detalle funcional y contradicciones; leer la sección de la tarea.
- Contratos: decisiones de negocio; leer el del área antes de implementar.
- Tres logs activos: novedades y coordinación. Cada agente escribe solo en el suyo.
- Histórico: evidencia completa y decisiones anteriores; consulta dirigida, nunca arranque.
- Informes de pruebas: detalle, comandos, capturas y resultados; el log solo los enlaza.

Si dos fuentes discrepan, conservar la diferencia y frenar la implementación dependiente.
No elegir por agente, por tamaño del texto ni por confianza. Una anotación histórica
no revoca una decisión posterior. No resumir autorizaciones cambiando su alcance.

## Al cerrar una tarea

- Actualizar solo el estado/plan/contrato que el trabajo haya vuelto falso.
- Actualizar el resumen compartido si cambió un bloqueo, decisión, entrega o siguiente paso.
  Releer su contenido antes de editar; integrar aportes simultáneos sin pisarlos.
- Escribir en el log propio una entrada de 5–10 líneas, más el título:
  objetivo/ID; cambio; decisión y límites; evidencia fechada; commit/entorno si se comprobó;
  próximo paso. No copiar chats completos, código, comandos largos ni resultados extensos.
- Separar IMPLEMENTADA, VERIFICADA y DESPLEGADA. No afirmar sincronización sin comprobarla.
- Claude conserva la verificación cruzada de Codex y registra el resultado en su log.
- Misma regla para los tres: sin secretos ni datos personales, entradas nuevas arriba.
- Para cambios exclusivamente documentales: revisar enlaces, integridad del archivo,
  diff y ausencia de cambios de aplicación. No ejecutar suite/migraciones/cachés por
  reorganizar documentos. Para código siguen vigentes las verificaciones del proyecto.

## Presupuesto y archivo

- Resumen: objetivo 100 líneas, máximo 150; reemplazar estado superado, no acumular historia.
- Cada log activo: máximo 150 líneas o 12.000 caracteres; conservar hasta 10 entradas recientes.
- Al superar cualquiera de esos límites, archivar las entradas antiguas intactas en
  docs/99-archivo/bitacoras/ con agente y fecha; dejar índice/enlace en el log activo.
- Antes de archivar, comprobar que decisiones vigentes y bloqueos queden en resumen,
  plan o contrato. Nunca borrar la única evidencia de una autorización o limitación.
- No reescribir historia ni atribuir entradas nuevas a otro agente.
- El plan conserva el índice y criterios; explicaciones largas van a evidencias enlazadas.
- Mantener un solo protocolo: las tres guías apuntan aquí, no crean reglas incompatibles.

## Firmas

| Agente | CyE | Casa de Carlos |
|---|---|---|
| Codex | Codex CyE | Codex CAB |
| Claude | Claude CyE | Claude CAB |
| Gemini | LOG GEM CYE | LOG GEM CAB |

## Archivo inicial

El corte 12/09/2026 preserva los archivos anteriores byte por byte.
[Índice y huellas del corte](../99-archivo/bitacoras/2026-09-12/INDICE.md).
Los resúmenes de entradas ajenas son extractos documentales preparados por Codex,
no nuevas verificaciones ni firmas de Claude o Gemini.
