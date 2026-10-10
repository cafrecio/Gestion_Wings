# B10 — Dónde guardar el CBU o alias · DEVUELTO a Claude 10/10, verificado Codex

Control independiente posterior: acepta y transforma blancos internos en alias y muestra en inglés el rechazo de longitud. [Verificación completa](VERIFICACION-B10.md). La entrega del autor y la aprobación visual de Carlos se conservan abajo; no está cerrado.

10/10/2026, Claude CyE, a pedido de Carlos: «hacelo vos y le pedimos a codex que lo pruebe».

## Decisiones de Carlos

«Acepto tu propuesta para 1, 2 y 3»: un solo campo «CBU o alias»; lo ve solo el admin; es
opcional. Y: «Se lo pondría al operativo que también cobra sueldo». Sobre las capturas: «Ok».

## Qué se hizo

- Columna `cbu_alias` en `profesores` y en `users` (migración `2026_10_10_120000`).
- Formulario y ficha del profesor: campo «CBU o alias», debajo de Teléfono.
- Pantalla de pagar una liquidación: el dato para transferir arriba del formulario de pago; si
  falta, lo dice y lleva a editar al profesor.
- Alta y edición de usuario: el campo aparece solo con el rol Operativo. Con otro rol no se
  guarda, y al cambiar de rol se borra.
- Control al guardar (`App\Rules\CbuOAlias`): un CBU o CVU de 22 números, o un alias de 6 a 20
  letras, números, puntos o guiones. Saca los espacios de un CBU pegado. No verifica que la
  cuenta exista ni el dígito verificador. Esto lo agregué sin que Carlos lo pidiera; lo vio en
  la hoja de decisiones.

No entra: cargar el importe del sueldo del operativo ni liquidárselo. B10 solo guarda a dónde
transferir.

## Verificación del autor

- `tests/Feature/CbuAliasB10Test.php`: 8 pruebas, 33 aserciones.
- Suite completa en `wings_testing_claude`: 581 aprobadas, 2 omitidas, 4650 aserciones, sin fallas.
- Capturas de páginas pedidas al sistema, en `evidencia/b10/`
  ([reproductor](evidencia/b10/PaginasB10Test.php)); las que vio Carlos están en
  `docs/00-estado/PARA-DECIDIR.html`.

## Para quien lo verifique

El aspecto lo aprobó Carlos. Falta que otro agente compruebe la regla: que se guarde y se
borre bien, que rechace lo inválido sin perder lo cargado, que el dato aparezca al pagar, que
un operativo o un profesor no puedan verlo ni por pantalla ni por dirección, y que cambiar el
rol de un usuario borre el dato.

No verificado por el autor: sesión de navegador a mano; el campo en celular; que el panel del
operativo aparezca y desaparezca al cambiar el rol en vivo (se capturó ya cargado).

---

## Segunda vuelta — 10/10/2026

Codex lo devolvió por dos fallas ([verificación](VERIFICACION-B10.md)). Corregidas:

- **Aceptaba y transformaba un alias con espacios.** `alias con espacio` se guardaba como
  `aliasconespacio`. La limpieza de espacios era para el CBU pegado y se aplicaba también al
  alias. Ahora los espacios de adentro se sacan solo si todo lo demás son números; un alias
  con espacio, tabulación o salto de línea se rechaza.
- **Un texto largo se rechazaba en inglés.** Lo rechazaba la regla genérica de largo, que no
  tiene traducción. Ahora la regla propia es el único control del campo y todo rechazo sale
  con el mismo mensaje en castellano. También rechaza un arreglo mandado por POST directo.

Los cinco casos quedaron en `CbuAliasB10Test` (8 pruebas, 43 aserciones).

No cambiado, a propósito: un alias formado solo por números sigue rechazándose. Codex señala
que el formato del BCRA lo admitiría. Lo dejé así porque un número suelto es, casi siempre, un
CBU mal copiado. Queda para que lo decida Carlos.
