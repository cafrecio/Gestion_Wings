# Trabajo en curso del 07/10/2026, resguardado sin publicar

Rama `en-curso/cye-2026-10-07`. **No es para main.** Guardado por Claude al cierre del día
para que el trabajo sin subir de la máquina CyE viaje a la otra máquina.

Los cambios de diseño (vistas y `app.css`) **no están commiteados**: todavía no tienen la
aprobación completa de Carlos. Van como parches, que se aplican encima de esta rama:

```
git fetch
git checkout en-curso/cye-2026-10-07
git apply docs/99-archivo/en-curso/2026-10-07/codex-a6-a10-diseno.patch
git apply docs/99-archivo/en-curso/2026-10-07/claude-a26-a39-a44-diseno.patch
npm run build
```

| Parche | De quién | Qué trae | Qué le falta |
|---|---|---|---|
| `codex-a6-a10-diseno.patch` | Codex | A6 a A10: `app.css` y siete vistas | Capturas completas, la propuesta restante de A10, suite y verificador. Ver `IMPLEMENTACION-A6-A10.md` y `LOG-CODEX.md` |
| `claude-a26-a39-a44-diseno.patch` | Claude | A26 (asterisco del celular), A39 (menú del operativo), A44 (plantilla de correo) | El OK de Carlos sobre dos capturas. Ver `IMPLEMENTACION-A26-A38-A39-A44.md` y `LOG-CLAUDE.md` |

Lo que no es diseño está commiteado en esta misma rama: controlador, idioma, pruebas,
documentos, evidencia y el tablero.

Al terminar cada trabajo y publicarlo en main, borrar esta rama y esta carpeta.
