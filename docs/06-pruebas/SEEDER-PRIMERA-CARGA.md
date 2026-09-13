# Seeder de primera carga

Deja la base en el punto exacto donde terminó la carga manual del 06/09/2026, sin
repetirla por pantalla. Existe porque repetir esa carga a mano cuesta horas de
trabajo humano y muchísimos tokens de agente, y el resultado ya está verificado en
[RESULTADO-PRIMERA-CARGA-V1.md](RESULTADO-PRIMERA-CARGA-V1.md).

## Cómo se corre

```bash
php artisan migrate:fresh
php artisan db:seed --class=PrimeraCargaCompletaSeeder
```

Sobre una base **recién migrada**. Si encuentra alumnos, pagos, deudas, cajas,
clases o liquidaciones, aborta con el nombre de la tabla que lo frenó: correrlo dos
veces duplicaría los alumnos y rompería la distribución.

**Nunca sobre `gestion_wings` ni sobre el servidor.** El seeder aborta solo si el
entorno es `production`, y eso no alcanza como protección: la base de trabajo local
también es `local`. La base a usar es una descartable.

## Qué deja

| | Cantidad | Detalle |
|---|---|---|
| Deportes | 2 | Patín (HORA) y Fútbol (COMISION) |
| Niveles | 4 | Principiantes, Intermedias, Avanzadas, Federadas |
| Grupos | 6 | Patín en los cuatro niveles; Fútbol en Principiantes y Avanzadas |
| Planes | 12 | Dos frecuencias por grupo, con los precios de C07–C12 |
| Tipos de caja | 2 activos | Efectivo/EFE $250.000 y Mercado Pago/MP $1.320.000 |
| Profesores | 4 | Tres de Patín por hora ($12.000, $15.000, $18.000) y uno de Fútbol al 40% |
| Cuentas | 7 | Un ADMIN, dos OPERATIVO y cuatro PROFESOR vinculados a su ficha |
| Alumnos | 60 | Patín 40, Fútbol 20; todos con plan activo |

Reglas de primer pago (1–15 al 100%, 16–23 al 70%, 24–31 al 40%) y las dos
configuraciones (`dias_gracia_cobranza` = 10, `dia_generacion_deuda` = 1).

**No deja deudas, pagos, cajas, clases, asistencias ni liquidaciones.** Ese es el
punto: la prueba humana arranca de ahí, igual que arrancó el 06/09.

## Las cuentas

Todas con la misma clave: **`PruebaWings2026`**.

| Rol | Correo |
|---|---|
| ADMIN | `admin@wings.test` |
| OPERATIVO | `sandra.vidal@wings.test`, `pablo.ledesma@wings.test` |
| PROFESOR | `lucia.gaitan@wings.test`, `veronica.salinas@wings.test`, `mariela.ocampo@wings.test`, `hernan.quintana@wings.test` |

La clave está a la vista a propósito: es de prueba, tiene los doce caracteres que
exige el sistema y el seeder no corre en producción.

## Los once primeros alumnos no son los originales

`PrimeraCargaAlumnosSeeder` genera 49 de los 60 y exige encontrar 11 ya cargados,
porque en septiembre esos once se cargaron a mano por pantalla. Aquella base
(`wings_test`) quedó vacía y esas once personas se perdieron.

Los que crea este seeder son **once equivalentes**, no los mismos. Respetan lo que
`PrimeraCargaAlumnosSeeder` valida y la carga manual dejó verificado:

- Seis de Patín y cinco de Fútbol.
- Dos altas de 2025, una del primer trimestre de 2026 y ocho del segundo.
- Sofía Morales, DNI `32123456`, inscripta en los dos deportes — la excepción que
  Carlos autorizó el 06/09 y que hace que sean 49 alumnos generados y no 50.

Si alguien toca esa distribución, no falla acá: falla en
`PrimeraCargaAlumnosSeeder`, que la verifica antes de completar el resto.

## Qué se comprueba

`tests/Feature/PrimeraCargaCompletaSeederTest.php` — 12 pruebas que miran el
resultado en la base, no que el seeder haya corrido: reparto por deporte, tramos de
fecha de alta, la dupla de Sofía, que cada alumno tenga plan activo **de su propio
grupo**, que ningún grupo sea de otro deporte (el defecto H06 de la carga manual),
los saldos iniciales, que cada profesor tenga su propio subrubro de sueldo, que las
cuentas de profesor apunten a una ficha real, que la clave permita iniciar sesión, y
que no haya quedado operación cargada.
