# Checklist de Carlos

> Lo que solo podés hacer vos, y lo que hay que repetir en cada máquina.
> Actualizado: martes 25/08/2026.

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

**Hoy (hasta que salga la tarea 1.2):**

```bash
"C:/xampp/mysql/bin/mysql.exe" -u root gestion_wings < database/dump.sql
php artisan db:seed --class=UserSeeder
```

**Después de la tarea 1.2 esto cambia.** `dump.sql` sale del repo, así que el `git
pull` ya no te va a traer la base. El reemplazo:

```bash
php artisan migrate:fresh
php artisan db:seed --class=CatalogosSeeder
php artisan wings:crear-admin
```

El seeder deja los catálogos —rubros, deportes, tipos de caja, niveles— y el comando
crea tu usuario admin pidiéndote la contraseña por consola.

> **Ojo:** `migrate:fresh` borra todo lo que haya en esa base. Está bien para las
> máquinas de desarrollo. **Nunca** correrlo contra producción.

### A4. Verificar que quedó bien

```bash
php artisan test          # 33 tests deben pasar
php artisan route:list    # 127 rutas
```

---

## B. Lo que necesito de vos

Ordenado por lo que frena antes.

### B1. Acceso SSH al servidor — BLOQUEA TODO EL MIÉRCOLES

Sin esto no se puede armar el servidor, y el miércoles entero depende de eso.
Explicación de qué es y cómo conseguirlo: sección C.

**Estado: pendiente.**

### B2. El dominio apuntando al servidor

El nombre por el que va a entrar la gente (`wings.com.ar`, `gestion.wings.com.ar`,
el que sea) tiene que estar apuntado a la dirección IP del servidor.

Se hace desde donde compraste el dominio: un registro tipo `A` con la IP.

**Por qué importa:** el certificado de seguridad (el candadito del navegador) se
emite verificando que el dominio realmente apunta a ese servidor. Sin el dominio
apuntado, no hay HTTPS.

**Estado: pendiente.**

### B3. Un lugar afuera para guardar los backups

Puede ser Backblaze B2, Amazon S3, Google Drive, o incluso otro servidor. Lo único
que no sirve es guardarlos en el mismo servidor: si se rompe ese disco, se van los
dos juntos.

Necesito el acceso a donde sea que elijas.

**Estado: pendiente.** Se necesita el miércoles.

### B4. La lista de usuarios reales

Nombre, email y rol de cada persona que va a usar el sistema:

| Nombre | Email | Rol |
|---|---|---|
| | | ADMIN / OPERATIVO / PROFESOR |

Las contraseñas las pone cada uno o las generás vos: no me las pases a mí ni las
escribas en un archivo del proyecto.

**Estado: pendiente.** Se necesita el jueves.

### B5. Qué alumnos y planes son reales

De lo que hay hoy en la base, cuáles son alumnos de verdad del club y cuáles quedaron
de las pruebas. Solo los reales se van a subir a producción.

Alcanza con que me digas un criterio ("los que tienen DNI cargado", "todos menos
estos cinco", "los del grupo tal"), no hace falta una lista uno por uno.

**Estado: pendiente.** Se necesita el jueves.

### B6. Dos o tres horas tuyas el jueves

Para el recorrido funcional sobre el servidor real. Es la única prueba que hace una
persona y no un script, y es donde aparecen las cosas que ningún test previó.

**Estado: pendiente.**

---

## C. Qué es el acceso SSH, en criollo

**SSH es la forma de entrar al servidor para escribir comandos.**

El servidor es una computadora sin pantalla, sin teclado y sin mouse, prendida en
algún lado. SSH es el cable invisible que te deja escribirle órdenes desde tu
computadora. Cuando ves en el plan cosas como `dnf install nginx`, eso se escribe
entrando por SSH.

Sin SSH, el servidor es una caja cerrada: existe, pero nadie puede instalarle nada.

### Qué hace falta concretamente

Tres datos:

1. **La dirección IP del servidor** — algo como `192.168.1.50` o `45.79.123.4`.
2. **Un usuario** — normalmente `root`, que es el administrador.
3. **La forma de identificarse** — una contraseña, o mejor, una *clave SSH*.

### Contraseña vs clave

- **Con contraseña:** escribís una clave cada vez que entrás. Simple, pero los bots
  que escanean internet prueban contraseñas todo el día. Por eso el plan la desactiva
  una vez que el servidor está armado (tarea 2.2).
- **Con clave SSH:** son dos archivos que se generan juntos. Uno queda en tu
  computadora (privado, nunca se comparte) y el otro se copia al servidor (público).
  Es como una cerradura y su única llave. Mucho más seguro y no hay nada que
  recordar.

Para arrancar alcanza con la contraseña; la clave la configuramos el miércoles.

### De dónde sale eso

Depende de dónde esté el servidor:

- **Si lo contrataste en un proveedor** (DigitalOcean, Hetzner, Linode, Contabo,
  Vultr, AWS, Donweb, etc.): te mandaron un mail al crearlo con la IP y la
  contraseña de root. También está en el panel del proveedor, en la ficha del
  servidor.
- **Si te lo armó alguien:** pedile esos tres datos.
- **Si todavía no lo contrataste:** decímelo y te recomiendo proveedor y plan. Para
  un club, con AlmaLinux 9, alcanza con algo chico: 2 GB de RAM y 40 GB de disco,
  del orden de 5 a 10 dólares por mes.

### Cómo me lo pasás

**No escribas la contraseña en el chat ni en un archivo del proyecto.**

Lo que sí podés mandarme sin problema es la IP y el nombre de usuario. Para la
contraseña, la opción sana es que la cargues vos directamente cuando estemos
conectando, o que generemos una clave SSH y me des acceso con eso.

### Cómo saber si ya lo tenés

Si en algún mail o panel ves algo así, ya está:

```
Host: 45.79.123.4
User: root
Password: ••••••••
Port: 22
```

Ese `22` es el puerto de SSH. Si aparece, es que el servidor lo tiene habilitado.

---

## D. Estado del plan

| Día | Estado |
|---|---|
| Martes 25 — blindaje de código | En curso |
| Miércoles 26 — servidor | **Bloqueado por B1** |
| Jueves 27 — pruebas | Depende del miércoles |
| Viernes 28 — go-live | Depende del jueves |

**Si el acceso al servidor no llega hoy**, el miércoles se corre al jueves, el jueves
al viernes, y el go-live pasa al lunes 31. No es un problema si pasa: es preferible a
subir sin haber probado.
