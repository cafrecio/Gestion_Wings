## 2026-10-19 — Codex CyE — Verificación de corrección T34

Verifico f2b065f7/c5c0f41f; T67 permanece cerrada y visual T34 aprobado por Carlos en fb88bd3c.
H1 y PATCH H2 pasan: estados coherentes; HORA/COMISIÓN, pendiente/pagada, JSON/formulario; filas intactas.
C1: listado 33→75 consultas con 2→16 tarjetas, por contador compartido del provider.
C2: incluir_hoy valida otra clase aun cuando la generación de septiembre se rechaza por duplicada.
Ambos antecedentes anteriores a la corrección; no se demostró bypass de cerrada por ese otro camino.
Selección final 16 pasan/2 fallan por C1/C2, 259 aserciones, 17,49 s; suite completa 921/4 omitidas, 8179 aserciones, 1343,15 s, verde.
[Informe y reproducciones](../06-pruebas/PRU-04/VERIFICACION-T34-T67-CODEX.md#verificación-de-la-corrección). Solo wings_testing_codex; diez controles visuales sin desbordes.
Sin corregir producto ni desplegar; T34 se entrega con hallazgos de c/f para Gemini.
