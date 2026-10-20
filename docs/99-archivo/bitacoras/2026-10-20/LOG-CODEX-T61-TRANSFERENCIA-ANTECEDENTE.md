## 2026-10-19 — Codex CyE — T68, corrección H1 del importe vigente

Alcance propio: usar monto_a_pagar en el historial; conservar cálculo, estilos, estructura y H2.
Regresión permanente: sin ajuste muestra $15,000.00; POST real ajusta a $18,000.50 y ficha/detalle coinciden, sin el anterior en ficha.
Roja antes: 1 fallida/11 aserciones, 11,80 s; después 16 dirigidas/108 aserciones verdes, 14,08 s.
Captura real 1/14 y cuatro controles de navegador; cambios visuales paralelos de otras tareas preservados, fuera del alcance H1.
Suite: 919 aprobadas, 4 omitidas, 2 fallidas, 8172 aserciones, 1963,93 s en wings_testing_codex. Repetición aislada 2/11 verde sin cambios; causa no comprobada. Corte 925 y contadores al día.
Carlos respondió «Sí, autorizo H1 y su publicación»; implementación en 02b72588, sin cambios visuales ajenos. Guardianes documentales 12/55 verdes.
[Corrección y evidencia](../06-pruebas/PRU-04/VERIFICACION-T68-CODEX.md#corrección-de-h1). Claude revisa lógica; Carlos visual con APROBAR-T68.html. Sin servidor ni despliegue.
