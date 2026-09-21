# Cuentas de prueba

> **Estas cuentas son inventadas y viven en el repositorio a propósito.** Las crea
> `PrimeraCargaCompletaSeeder`, solo existen en bases descartables y el seeder se
> niega a correr en producción. No hay nada que proteger acá.
>
> **Las credenciales reales nunca entran al repositorio.** Viven fuera, y
> `.gitignore` bloquea `docs/06-pruebas/Credenciales*` para que un descuido no las
> suba. Por eso este archivo se llama distinto: si le cambiás el nombre a algo que
> empiece con "Credenciales", deja de subir.

Existe para que la clave sea **la misma en todas las máquinas y en test**, y nadie
tenga que preguntar cuál es la suya.

## Todas usan la misma clave

**`PruebaWings2026`**

Doce caracteres porque el sistema no acepta menos (SEG-03).

| Rol | Correo | Nombre |
|---|---|---|
| ADMIN | `admin@wings.test` | Admin Prueba |
| OPERATIVO | `sandra.vidal@wings.test` | Sandra Vidal |
| OPERATIVO | `pablo.ledesma@wings.test` | Pablo Ledesma |
| PROFESOR | `lucia.gaitan@wings.test` | Lucía Gaitán |
| PROFESOR | `veronica.salinas@wings.test` | Verónica Salinas |
| PROFESOR | `mariela.ocampo@wings.test` | Mariela Ocampo |
| PROFESOR | `hernan.quintana@wings.test` | Hernán Quintana |

Las cuatro de PROFESOR están atadas a su ficha, así que sirven para probar lo que
ve un profesor de verdad. Las dos de OPERATIVO son distintas entre sí para poder
probar dos cajas al mismo tiempo.

## Dónde funcionan

- **En tu máquina**, después de correr el seeder (ver abajo).
- **En `test.gestionar-te.com.ar`**, que se arma con el mismo seeder.
- **En producción no**, y no tiene que funcionar nunca: ahí están las cuentas
  reales del club.

## Cómo dejar tu máquina lista

```bash
php artisan migrate:fresh
php artisan db:seed --class=PrimeraCargaCompletaSeeder
```

Deja 60 alumnos, 6 grupos, 12 planes, 4 profesores y estas 7 cuentas, sin deudas
ni pagos: es el punto donde terminó la carga manual del 06/09. El detalle completo
está en [SEEDER-PRIMERA-CARGA.md](SEEDER-PRIMERA-CARGA.md).

**`migrate:fresh` borra la base que tengas configurada en el `.env`.** Mirá a cuál
apunta antes de correrlo — nunca a `gestion_wings` ni a la del servidor.
