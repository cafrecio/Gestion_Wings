# Orden de trabajo — Codex — D1 (jueves 27/08)

> **Leer `AGENTS.md` completo antes de tocar un archivo.** No se repite acá.
> Criterios de aceptación detallados: `docs/00-estado/PLAN-PRODUCCION.md`.

---

## Límites de este lote

**No tocar** — ninguna tarea de este lote lo necesita:

- `resources/views/**`
- `resources/css/app.css`
- `resources/views/components/ds/**`
- `app/Services/PagoCuotaService.php` (lo toma Claude en 1.9)
- `app/Http/Controllers/CajaWebController.php` (lo toma Claude en 1.9b)
- `bootstrap/app.php` (la API queda apagada)

Antes de cerrar cada tarea:

```bash
git diff --stat -- resources/views resources/css   # debe estar vacío
php artisan test                                    # 33 tests deben pasar
php -l <cada archivo tocado>
```

**Un commit por tarea.** El mensaje dice qué riesgo cierra, no qué archivos tocó.

---

## Orden de ejecución

Las dependencias importan: 1.3 antes que 1.2, y 1.7 antes que 1.8.

> **1.3 ya está escrita y sin commitear.** Cerrarla y commitearla primero.
> El contenido de los catálogos lo define el cliente: el seeder solo deja el
> sistema arrancable. Dos nombres son obligatorios porque el código los busca
> literalmente: el subrubro `Cuota Mensual` y el rubro `Sueldos`.

### Bloque A — el que destraba la fuga de datos

**1.3 · `CatalogosSeeder` idempotente** · 1.5 h · cierra B2

Crear `database/seeders/CatalogosSeeder.php` que deje una base vacía en estado
utilizable, sin un solo dato personal adentro.

Cubre: rubros, subrubros, tipos de caja, deportes, niveles y reglas de primer pago.

- Idempotente de verdad: `updateOrCreate` contra una clave natural, no `create`.
- Los valores salen de la base actual — son catálogos, no datos de personas.
- Respetar el subrubro reservado `Cuota Mensual`: `PagoCuotaService` lo busca por
  nombre exacto y explota si no existe.

*Aceptación:* sobre una base vacía, `migrate --seed` la deja usable. Correrlo una
segunda vez no modifica ninguna fila.

---

**1.2 · Sacar `dump.sql` del repo** · 30 min · cierra B2 · **depende de 1.3**

```bash
git rm --cached database/dump.sql
echo "database/dump.sql" >> .gitignore
```

Después invalidar lo que quedó expuesto: vaciar `personal_access_tokens` y
`sessions` en la base local.

Actualizar `CLAUDE.md`: la sección de base de datos todavía explica el flujo de
export/import del dump, que deja de ser el mecanismo. Reemplazarlo por el seeder.

No intentar limpiar la historia de Git: es tarea diferida, coordinada.

*Aceptación:* `git ls-files database/dump.sql` no devuelve nada y el archivo sigue
existiendo en disco.

---

### Bloque B — seeders y arranque seguro

**1.4 · Sanear los seeders** · 40 min · cierra B1

- `DatabaseSeeder` pasa a llamar únicamente a `CatalogosSeeder`.
- La cuenta `test@example.com` se muda a un `TestSeeder` aparte.
- `DemoSeeder` y `TestSeeder` abortan en producción:

```php
if (app()->environment('production')) {
    throw new \RuntimeException('Este seeder no corre en produccion.');
}
```

*Aceptación:* con `APP_ENV=production`, `db:seed` no crea ningún usuario y los
seeders de datos fallan con mensaje claro.

---

**1.5 · Comando `wings:crear-admin`** · 30 min · cierra B1

Crear el primer administrador de forma interactiva y segura.

- Pide nombre, email y contraseña, con la contraseña **oculta al tipear**
  (`$this->secret()`).
- Confirmación de contraseña y validación de fuerza mínima.
- Rechaza si ya existe un ADMIN, salvo bandera explícita.
- Nunca aceptar la contraseña como argumento de línea de comandos: quedaría en el
  historial del shell.

*Aceptación:* crea el admin, permite iniciar sesión, y la contraseña no aparece en
`history`.

---

### Bloque C — configuración de producción

**1.7 · `.env.production.example`** · 25 min · cierra B4 · **antes de 1.8**

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<dominio>
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
SESSION_SAME_SITE=strict
LOG_LEVEL=warning
DB_USERNAME=wings_app
```

Plantilla sin ningún secreto real. No tocar el `.env` local.

---

**1.8 · Comando `wings:preflight`** · 1 h · cierra B4 · **depende de 1.7**

Aborta el despliegue si algo está mal configurado. Sale con código distinto de cero
para que `deploy.sh` se corte.

Verifica y falla si:

| Condición | Motivo |
|---|---|
| `APP_DEBUG` activo | Publica código fuente y credenciales en cada error |
| `APP_ENV` distinto de `production` | Perfil de desarrollo en el servidor |
| `APP_KEY` vacía | Sesiones y cifrado inseguros |
| `DB_USERNAME` es `root` | Un fallo escala a control total de la base |
| `APP_URL` sin `https` | Credenciales sin TLS |
| `SESSION_SECURE_COOKIE` distinto de `true` | Cookie de sesión viaja en claro |
| Existe `test@example.com` | Cuenta de acceso conocida |
| Algún usuario con hash de password conocida | Ídem |

Salida legible: una línea por chequeo, con el motivo del fallo.

*Aceptación:* con el `.env` local falla señalando cada problema; con el de
producción pasa limpio.

---

### Bloque D — endurecimiento

**1.6 · Middleware `SecurityHeaders`** · 45 min · cierra B5

Middleware global que agregue en cada respuesta:

```
X-Frame-Options            DENY
X-Content-Type-Options     nosniff
Referrer-Policy            same-origin
Permissions-Policy         camera=(), microphone=(), geolocation=()
Strict-Transport-Security  max-age=31536000; includeSubDomains   (solo bajo HTTPS)
```

**NO agregar `Content-Security-Policy` en esta tarea.** Es la 1.6b, es supervisada
y no la toma Codex. Ver `AGENTS.md` §2: una CSP estricta rompe visualmente toda la
aplicación porque las vistas usan `style="..."` inline.

*Aceptación:* `curl -I` sobre cualquier ruta muestra los cinco headers, y las
pantallas se ven exactamente igual que antes.

---

**1.11 · Endurecer el login** · 20 min

- Bajar el throttle de `10,1` a `5,1`, contando por IP **y** por email.
- Bloqueo temporal creciente tras varios fallos seguidos.
- Dejar el mensaje de error genérico: no revelar si el email existe.

*Aceptación:* al sexto intento fallido en un minuto, la respuesta es 429.

---

**1.10 · Asistencias transaccionales y validadas** · 1 h · cierra B7

En `ClaseWebController::storeAsistencias()`:

- Envolver la segunda pasada (el bucle de guardado) en `DB::transaction`.
- Crear un FormRequest que valide la estructura completa y que **cada `alumno_id`
  pertenezca al grupo de la clase**.

Ya existe una primera pasada que valida solapamientos y no escribe nada si algo
falla. La de escritura tiene que quedar igual de estricta.

*Aceptación:* con un alumno de otro grupo, rechaza sin escribir nada. Con un fallo
a mitad de lista, no queda ninguna asistencia guardada.

---

### Bloque E — higiene

**1.12 · Limpiar `formas_pago`** · 10 min

La migración `drop_forma_pago` la eliminó, pero sobrevivió en la base local porque
al importar el dump solo se ejecuta lo que el dump contiene.

```sql
DROP TABLE IF EXISTS formas_pago;
```

Verificar después que no haya otras tablas huérfanas comparando contra las
migraciones aplicadas.

---

## Qué NO está en este lote

| # | Tarea | Quién | Por qué |
|---|---|---|---|
| 1.6b | CSP | Supervisada | Puede destruir el diseño. `AGENTS.md` §2 |
| 1.9 | FIFO fuerte real | Supervisada | Toca el núcleo del cobro |
| 1.9b | Atomicidad del cambio de plan | Supervisada | Va atada a 1.9 |
| 1.13 | Cierre y `/security-review` | Supervisada | Después de todo lo demás |

---

## Al terminar

Reportar por tarea:

1. Qué se hizo.
2. Resultado del criterio de aceptación.
3. Cualquier cosa que **pareció** requerir tocar una vista o el CSS — aunque no se
   haya tocado. Eso es señal de que la tarea se entendió mal.
4. Salida de `git diff --stat -- resources/views resources/css` (debe estar vacía).
