# B15 — respaldo de la fecha histórica

Lectura propia de Git, 16/10/2026. No se consultó ni cambió DNS.

- `f4e6436^:docs/04-tecnico/SERVIDOR.md:99–112`: corrección documentada el 23/09/2026.
- `031bdec5a65d1df235acc2441925117a0cb9a8f0`, 23/09/2026: B15 ya figura «Verificado y corregido».
- El encabezado inmediato anterior a T3 no incluía fecha; no se atribuye a ese encabezado una prueba que no contiene.

## Sección técnica preservada

```text
### El SPF del subdominio de prueba anuló su comodín — 23/09/2026

Al publicar el TXT de SPF para `test.gestionar-te.com.ar` se rompió el acceso al sitio de
prueba, con `DNS_PROBE_FINISHED_NXDOMAIN` para todo el mundo. El motivo: ese subdominio no
tenía dirección propia, resolvía por el comodín `*.gestionar-te.com.ar`, y **un comodín deja
de aplicar a un nombre que existe con cualquier otro tipo de registro**. Al crear el TXT, el
nombre pasó a existir y el comodín dejó de darle la dirección.

Se corrigió agregando el registro A explícito de `test.gestionar-te.com.ar` (2.25.204.38,
detrás del proxy de Cloudflare, igual que wings).

**Regla para la próxima:** antes de agregar un TXT a un subdominio que depende del comodín,
crear primero su registro A. Y comprobar desde afuera, no desde el servidor: desde el
servidor seguía respondiendo mientras nadie más podía entrar.
```

## Entrada contemporánea preservada

```text
### B15. El SPF del subdominio anuló su comodín de DNS · Verificado y corregido

Documentado en `docs/04-tecnico/SERVIDOR.md`. Queda como lección de proceso: comprobar desde
afuera, no desde el servidor.
```
