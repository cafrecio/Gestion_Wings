# Checklist de Carlos

> Lo que solo podés hacer vos, y lo que hay que repetir en cada máquina.
> Actualizado: lunes 31/08/2026.

---

## A. Al sentarte en la otra máquina

Estos pasos **no se versionan**, o sea que Git no los trae. Hay que hacerlos a mano
una vez en cada computadora.

### A1. Desactivar el hook que sube la base a GitHub — HACELO PRIMERO

Antes de cualquier commit desde esa máquina:

```bash
mv .git/hooks/pre-commit .git/hooks/pre-commit.disabled
```

**Por qué:** ese hook exporta la base entera —con nombres, DNI y teléfonos de los
alumnos, los pagos y las sesiones abiertas— y la sube a GitHub en **cada** commit.
Los hooks viven dentro de `.git/`, que no se versiona, así que desactivarlo en una
computadora no lo desactiva en la otra.

**Cómo saber si ya está hecho:**

```bash
ls .git/hooks/ | grep pre-commit
```

Si dice `pre-commit.disabled` está bien. Si dice `pre-commit` a secas, todavía está
armado.

### A2. Después de cada `git pull`

```bash
composer install
npm install
npm run build
```

### A3. Levantar la base

```bash
php artisan migrate:fresh
php artisan db:seed --class=CatalogosSeeder
php artisan wings:crear-admin
```

El seeder deja los catalogos —rubros, deportes, tipos de caja, niveles— y el comando
crea tu usuario admin pidiendote la contrasenia por consola.

> **Ojo:** `migrate:fresh` borra todo lo que haya en esa base. Esta bien para las
> maquinas de desarrollo. **Nunca** correrlo contra produccion.

> **Nota:** `dump.sql` todavia figura en el repo, pero ya no es el mecanismo para
> levantar la base. Sale antes de la carga productiva.

### A4. Verificar que quedó bien

```bash
php artisan test          # 77 pruebas deben pasar
php artisan route:list    # rutas cargadas
```

---

## B. Lo que necesito de vos

Ordenado por lo que frena antes.

### B1. Acceso al servidor — RESUELTO

Wings está publicado en `https://wings.gestionar-te.com.ar`, sobre AlmaLinux 9 con
PHP 8.2, TLS de Let's Encrypt y base con usuario de privilegio mínimo.

**Estado: cerrado el 30/08.**

### B2. El dominio — RESUELTO

Apuntado y con certificado emitido. Redirección desde HTTP activa.

**Estado: cerrado el 30/08.**

### B3. Destino de los backups — RESUELTO

Diarios, cifrados, con rotación y subida a Google Drive por rclone. **La restauración
se probó**: se recuperó en una base descartable y coincidieron las 15 tablas. La clave
de cifrado quedó fuera del servidor.

**Estado: cerrado el 30/08.**

### B4. Credenciales del servidor a un administrador de contrasenias

Hoy viven en un archivo unico en `D:\CAB Consultores\Gestionar-te\VPS`, o sea que
**dependen de una sola maquina**. Si ese disco falla o no estas frente a el, nadie
puede entrar al servidor.

Que esten fuera del repositorio esta bien y no se cambia. Lo que falta es que no
dependan de un equipo: pasarlas a un administrador de contrasenias.

Incluye la clave de cifrado de los backups. Sin ella los respaldos son inservibles
justo el dia que se los necesita.

**Estado: pendiente.** Decidido el 02/09.

### B5. La lista de usuarios reales

Nombre, email y rol de cada persona que va a usar el sistema:

| Nombre | Email | Rol |
|---|---|---|
| | | ADMIN / OPERATIVO / PROFESOR |

Las contraseñas las pone cada uno o las generás vos: no me las pases a mí ni las
escribas en un archivo del proyecto.

**Estado: pendiente.** Se necesita antes del go-live.

### B6. Qué alumnos y planes son reales

De lo que hay hoy en la base, cuáles son alumnos de verdad del club y cuáles quedaron
de las pruebas. Solo los reales se van a subir a producción.

Alcanza con que me digas un criterio ("los que tienen DNI cargado", "todos menos
estos cinco", "los del grupo tal"), no hace falta una lista uno por uno.

**Estado: pendiente.** Se necesita antes del go-live.

### B7. Dos o tres horas tuyas

Para el recorrido funcional sobre el servidor real. Es la única prueba que hace una
persona y no un script, y es donde aparecen las cosas que ningún test previó.

**Estado: pendiente.** Se necesita antes del go-live.

---

## C. El servidor, para referencia

Wings vive en `https://wings.gestionar-te.com.ar`, sobre AlmaLinux 9.

Los datos de acceso y la configuracion **no estan en este repositorio**: son de la
plataforma Gestionar-te, no del producto. Quedaron en la carpeta de VPS de CAB
Consultores, fuera del repo.

**SSH**, por si aparece el termino: es la forma de entrar al servidor para escribirle
comandos. El servidor es una computadora sin pantalla ni teclado, y SSH es el cable
invisible que permite darle ordenes desde la tuya.

---

## D. Estado del plan

| Bloque | Estado |
|---|---|
| Blindaje de código | **Cerrado** salvo la CSP definitiva |
| Servidor, TLS, backups, despliegue | **Cerrado** |
| Datos para probar: seeder y simulador | **Pendiente** |
| Prueba humana completa | **Pendiente** |
| Gate y carga productiva | **Pendiente** |

Lo que falta, con detalle y prioridad, está en `PLAN-PRODUCCION.md`.

**Ya no hay nada bloqueado esperándote**, salvo la lista de usuarios reales y qué
alumnos son de verdad, que se necesitan recién en el momento de la carga productiva.
