# T50 — Verificación final de Codex CyE

21/10/2026. Commit revisado: `738de598ba28c1e4fea41cfe05a88a9e074747a4`, coincidente con `origin/main` al iniciar. Equipo comprobado: `Ventas_CyE`. Implementación de Gemini; verificación independiente de Codex.

## Resultado: APROBADA — T50 cerrada

Leído el auxiliar completo: `DB::connection()->getDatabaseName()` comprueba la base efectiva antes de cualquier migración, seeder o escritura del auxiliar. También conserva los controles de entorno, configuración estática y base del URL. Resuelve la única observación pendiente de la tercera revisión.

Se repitió **el arnés original de Codex de b4e0b2e0**, en una carpeta nueva, sin cambiar sus casos ni esperados:

| Caso | Salida del auxiliar | ERROR DE SEGURIDAD | Llegó a preparación |
|---|---:|---|---|
| Base no autorizada | 1 | Sí | No |
| Entorno production | 1 | Sí | No |
| DB_DATABASE permitido, DB_URL a base no autorizada | 1 | Sí | No |

El arnés ahora devuelve **0**. [Resultados](evidencia-verificacion-t50-final-codex/guardia-resultados.json) · [Consola](evidencia-verificacion-t50-final-codex/guardia-consola.txt) · [Reproductor](evidencia-verificacion-t50-final-codex/verificar-guardia.ps1).

Como control positivo, una configuración local de prueba permitida supera la guardia y llega a preparación. Se fuerza igualmente el puerto cerrado `127.0.0.1:1`: el intento de conexión falla antes de migrar o sembrar. Esto comprueba que la guardia no rechaza todo; no certifica la ejecución de una carga. [Resultado](evidencia-verificacion-t50-final-codex/configuracion-permitida.json) · [Salida](evidencia-verificacion-t50-final-codex/guardia-configuracion-permitida.txt).

## Regresiones propias

- Permanentes T50: **13 aprobadas, 71 aserciones, 10,89 s, salida 0**, `wings_testing_codex`. [Salida](evidencia-verificacion-t50-final-codex/regresiones-permanentes.txt).
- Controles independientes de la tercera revisión, copiados sin alterar sus esperados: **17 aprobados, 214 aserciones, 32,934 s, salida 0**. Incluyen H1 según Carlos, H2/H3, Excel, cambio de año y 20 peticiones inválidas con mensajes castellanos y huellas de tablas sin cambios. [Salida](evidencia-verificacion-t50-final-codex/controles-independientes.txt) · [Casos](evidencia-verificacion-t50-final-codex/controles.jsonl).
- PHP sin errores de sintaxis en el auxiliar.
- Vistas compiladas y limpiadas correctamente. Guardianes tras cerrar el tablero: **9 aprobados, 43 aserciones, 0,42 s, salida 0** (`DocumentacionNoMienteTest`, `TableroAlDiaTest`, `TablerosNoDivergenTest`). [Salida](evidencia-verificacion-t50-final-codex/guardianes-finales.txt).

- Suite rápida: **908 aprobadas, 6 omitidas, 7286 aserciones, 158,31 s, salida 0** en `wings_testing_codex`, sin otra suite al iniciar. [Salida](evidencia-verificacion-t50-final-codex/suite-rapida.txt).

El corte permanente sigue en **1029**: los controles de evidencia están fuera de `tests/` y no aumentan el contador. El cierre se registra mediante el generador del tablero: hizo=Gemini, verifica=Codex.

El HTML T34 regenerado por la suite se restituyó a su contenido previo. Diff de producto, Blade y CSS respecto de `738de598` vacío; no quedaron cambios de aplicación por esta revisión.

## Alcance

El único cambio de código desde la revisión anterior es el auxiliar CLI; el producto conserva los controles previos. H1 no se reabre: Carlos decidió «Inscripción a partir de hoy»; la cuota futura comienza a sumar en su mes. No se cambian reglas, diseño ni tabla externa.

No se repitió el recorrido visual de Chrome: esta corrección afecta al auxiliar CLI, y el recorrido de H3 está documentado en la segunda revisión. No hay cambios en Blade ni CSS. No se ejecuta una suite completa nueva ni se presenta la completa histórica del corte 1025 como corrida del corte 1029.

El MCP codebase-memory continúa respondiendo `Transport closed` (estado y cobertura); se verificó directamente el archivo y se ejecutaron los controles. Todas las pruebas de aplicación usan la base propia `wings_testing_codex`. Los casos de la guardia apuntan a un puerto cerrado: no consultan ni escriben bases reales. Sin servidor, despliegue ni cambios de producto por Codex.

Se recuperan desde `b4e0b2e0` las cuatro salidas históricas de la guardia de la tercera revisión, que `738de598` había sobreescrito al repetirlas: así su informe sigue enlazando su resultado real. Los resultados nuevos están en `evidencia-verificacion-t50-final-codex/`; la adaptación de compatibilidad del arnés de Gemini se conserva. No se revierte la corrección del auxiliar.
