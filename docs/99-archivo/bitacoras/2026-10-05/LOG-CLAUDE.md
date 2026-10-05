## 2026-09-21 — Claude CyE — FIN-08 cerrada: la revision la hace el operativo

Carlos: si solo el admin puede hacer cosas, los otros roles no tienen sentido.
Ruta de revision (ver y resolver) pasada de `ensure.admin.web` al grupo que comparten
ADMIN y OPERATIVO, como Cobranza. Enlace del menu movido de "Plata" (admin) a "Dia a dia",
mismo `ds-nav-link` e icono; diseno autorizado por Carlos. Condonar sigue solo ADMIN.
Parcial y precio: Carlos acepta que ya no aplican (nada pisa un parcial; precio vigente, igual
que la generacion mensual). Se retiro la prueba que exigia "la revision es del admin".
`RevisionCobranzaOperativoTest` 6 pruebas, 4 fallan con la ruta vieja. Suite 279/1585.
PERMISOS-ROLES con el caso nuevo. Sin deploy. Enfasis de Carlos: todo retoque de diseno de
la prueba grande respeta siempre el design system (anotado en PRU-02).
