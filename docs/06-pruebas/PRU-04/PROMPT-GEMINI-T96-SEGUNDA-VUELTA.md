# PROMPT — T96, segunda vuelta: controlar el saldo inicial de Efectivo — para Gemini

> Prompt para Gemini, escrito por Claude CAB el 01/11/2026. Carlos lo copia y se lo pasa a Gemini.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Traer `main` actualizado y marcar T96 `en_curso, tiene=Gemini`.

## Qué pasó

Tu informe `VERIFICACION-T96-GEMINI.md` encontró que cambiar la cuenta del cajón con cambio retenido corría ese importe de cuenta en Cashflow (H1). Claude confirmó la causa leyendo el código. Carlos decidió sacar el problema de raíz, y su decisión reemplaza DU-59:

- Un solo cajón, siempre en la cuenta **Efectivo**. No se elige otra, no se cambia y no se renombra.
- El dueño configura una sola vez: lo único que carga es el **saldo inicial de Efectivo**.
- Una vez guardado, ese saldo queda fijo. Cualquier ajuste posterior se hace con un movimiento «Ajuste de saldo», que Claude agregó al catálogo: entrada como Aporte y salida como Retiro, solo para el dueño.
- El saldo de la cuenta y el efectivo del cajón son dos datos separados. La operativa nunca ve el saldo de la cuenta.

Claude no controla su propio trabajo: el control es tuyo, sin dar por cierto nada de lo que dice este prompt. **El aspecto ya lo aprobó Carlos** (`docs/06-pruebas/PRU-04/APROBAR-T96-PROPUESTA.html`). No opines sobre diseño ni cambies vistas, pero sí comparalo: las pantallas reales tienen que coincidir con esas capturas.

**Una corrección a tu informe anterior.** En §3.1 dice que el selector excluía bancos y Mercado Pago por `tiposElegiblesParaMostrador` en `CajaWebController.php:190-192`. Esa función no existía y el selector ofrecía todas las cuentas activas. Revisá cómo llegó esa cita y dejá la corrección escrita en el informe nuevo.

## Qué se cambió

- `app/Services/CajaService.php`: `configurarMostrador` rechaza un cambio; `configurarEfectivo` es nuevo; texto nuevo del aviso al abrir sin configurar.
- `app/Http/Controllers/CajaWebController.php` y `TipoCajaWebController.php`; `app/Models/TipoCaja.php`.
- `app/Support/ClasificacionSubrubros.php`, `database/seeders/CatalogosSeeder.php` y la migración `2026_11_01_120000_agregar_ajustes_de_saldo.php`.
- Vistas: `caja/index`, `caja/configuracion`, `caja/apertura`, `tipos-caja/_form`.
- Manual: `admin/configurar-mostrador` (reescrita, con captura nueva) y `admin/administrar-tipos-caja`.
- Contratos: `Wings-Contrato-Caja-Cashflow-V5.md`, V4 §4.3 y `Wings-ER-Caja-Cashflow-V5.md`. Plan: `docs/04-tecnico/DESPLIEGUE-PRODUCCION.md` paso 10.
- Pruebas: `MedioPagoCajonTest` reescrita. También `CatalogosSeederTest` y `TraspasosEntreMediosT61Test` (contadores del catálogo), `InicioOperativoTest`, `support/ImportesT24`, y `AvisoAdminResumenDiarioTest`, que usaba el reloj real y fallaba el día 1 de cada mes.

## Qué controlar

Ejecutalo, no alcanza con leer. Pantallas por navegador contra una base tuya; las reglas también con pedidos directos.

| # | Caso | Resultado esperado |
|---|---|---|
| 1 | Dueño, sin configurar, en Caja | «Falta cargar el saldo de Efectivo» y un solo botón, **Configurar**; **Abrir** no aparece |
| 2 | Dueño en la pantalla | **Saldo inicial de Efectivo**: cuenta fija, sin lista, saldo vacío y obligatorio |
| 3 | Guardar 2,310,000.50 | «Efectivo configurado.»; saldo, cuenta, autor y fecha guardados; en Caja solo **Abrir** |
| 4 | Guardar vacío, negativo, tres decimales, letras | No guarda ni configura nada; avisa junto al campo |
| 5 | Guardar 0 | Se acepta |
| 6 | Efectivo con un movimiento previo | Saldo visible, sin editar; **Guardar** configura y el saldo no cambia, aunque un pedido directo mande otro |
| 7 | Ya configurada, escribiendo la dirección | Vuelve a Caja |
| 8 | Ya configurada, POST directo con otro saldo o `tipo_caja_id`, con y sin caja abierta | «Efectivo ya está configurado y no se puede cambiar.»; nada cambia |
| 9 | Tipos de Caja, Efectivo configurada | Nombre y saldo de solo lectura; por pedido directo tampoco cambian; no se desactiva |
| 10 | Tipos de Caja, Efectivo **sin** configurar | Tampoco se renombra ni se desactiva; decí qué pasa con su saldo |
| 11 | Otra cuenta | Se edita como antes hasta su primer movimiento |
| 12 | Operativa con el saldo sin cargar | No ve la fila ni **Configurar**; al abrir, «El dueño tiene que configurar Efectivo antes de abrir la caja.»; por dirección y POST, 403 |
| 13 | Profesora | No llega a esta función; anotá qué responde |
| 14 | Ajuste en Cashflow, entrada y salida, por pantalla | Mueve la cuenta. Mirá al peso el saldo, el resultado del mes en Inicio y Reportes y dónde aparece |
| 15 | Operativa intentando usar un subrubro de ajuste | No lo ve y no lo puede cargar |

**Prestá atención a esto, que Claude no comprobó por pantalla:**

- Tu escenario de H1 ya no tiene que poder armarse. Intentá llegar a un saldo corrido por cualquier camino: por pantalla, por pedido directo, renombrando, o desactivando y recreando Efectivo.
- Una base ya en uso, como el sitio de prueba: una cuenta configurada que no se llame Efectivo, y el efecto de la migración si ya existe un subrubro con ese nombre.
- Dos pedidos a la vez: dos `configurarEfectivo`, y uno con una apertura de caja.
- Cualquier otro lugar que muestre el saldo de una cuenta a quien no sea el dueño.

**También controlar:**

- Los dos manuales contra el código, condición por condición (`docs/06-pruebas/PRU-04/REVISOR-MANUAL.md` punto 6), y `php scripts/manual/verificar-manual.php`. Las fichas `evidencia-t12-t13/fuentes/admin-configurar-mostrador.md` y `admin-administrar-tipos-caja.md`.
- Las pruebas nuevas tienen que fallar si se saca cada regla. Probalo con mutaciones y dejá el código como estaba.
- Suite rápida en `wings_testing_gemini` (`--exclude-group=lento`).

## Reglas

- Pruebas solo en `wings_testing_gemini`. **No uses la base local de trabajo.** Sin despliegue: Cora trabaja en el sitio de prueba.
- No corregir el producto. Lo que encuentres se informa.

## Cómo entregar

- Informe en `docs/06-pruebas/PRU-04/VERIFICACION-T96-GEMINI-2.md`: una fila por caso, lo comprobado y lo inferido por separado, cada hallazgo con gravedad y `archivo:línea`.
- Sin graves ni medios: `php scripts/tablero/tablero.php cambiar T96 estado=cerrado verifica=Gemini tiene=nadie`, con un paso que diga el resultado. Con hallazgos: `estado=devuelto tiene=Claude`, con el paso que corresponda.
- Entrada en tu bitácora `docs/00-estado/LOG-GEMINI.md`, firmada según la máquina.
- **Commit solo de lo tuyo:** `git status`, mirar archivo por archivo, agregar por nombre con `git add <archivo>`, nunca `git add -A`, `git add .` ni `git commit -a`. Si un archivo tuyo tiene cambios ajenos sin publicar, frenar y avisar a Carlos.
