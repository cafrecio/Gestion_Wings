# Primera carga — cómo se arma el club desde cero

> Definido por Carlos el 05/09/2026. **Especificación, sin implementar.**
> Reemplaza el enfoque de `DATASET-SEEDER-V1.md`: no es un seeder que inventa un
> club, es **reproducir la carga inicial real tal como la haría una persona**.

## Punto de partida: la base vacía

Se borra **toda** la base. Queda únicamente lo que **no se puede crear desde la
aplicación**:

| Qué queda | Detalle |
|---|---|
| Usuario ADMIN | `admin@wings.com` / `wings2026` |
| Usuario SUPERADMIN | El protegido, se crea por consola |
| Rubro y subrubro de cuotas | El que cobra las clases — el código lo busca por nombre exacto |
| Rubro Sueldos | **Sin subrubros**: se crean solos al dar de alta cada profesor |

> Las contraseñas de arriba son de prueba y así se usan a propósito. No aplica acá
> la discusión de claves fuertes: esta base es descartable.

Todo lo demás **lo carga una persona desde las pantallas**. Ese es el punto: si algo
no se puede cargar desde la aplicación, eso mismo es el hallazgo.

---

# Etapa 1 · Armar el club a mano

## Catálogos

| Qué | Cuánto |
|---|---|
| **Deportes** | 2: Patín y Fútbol |
| **Niveles** | 4: Principiantes, Intermedias, Avanzadas, Federadas |
| **Grupos de Patín** | 4, uno por nivel |
| **Grupos de Fútbol** | 2: Principiantes y Avanzados |
| **Tipos de caja** | 2: Efectivo y Mercado Pago |

## Profesores

| Deporte | Cuántos | Cómo cobran |
|---|---|---|
| Patín | 3 | Por hora, **con valores distintos entre sí** |
| Fútbol | 1 | Por comisión |

## Usuarios

Dos **operativos** y cuatro **profesores**, uno por cada profesor cargado.

## Alumnos — 60 en total

| Deporte | Cuántos | Género |
|---|---|---|
| Patín | 40 | Mujeres |
| Fútbol | 20 | Varones |

**Todas las fechas de alta son anteriores a hoy.** Es una carga inicial: el club ya
venía funcionando.

### Diez se cargan a mano, como lo haría una persona

De los 60, **diez se cargan uno por uno desde la pantalla**, no por seeder.

- **Algunos con el usuario admin y otros con un operativo**, para ejercitar los dos
  caminos.
- **Probando todas las validaciones**: campos obligatorios vacíos, DNI repetido,
  formatos inválidos, menor sin tutor.
- **Verificando que quedaron bien cargados**, no solo que la pantalla no dio error.

### Los otros cincuenta, por seeder

**Realistas.** Sin datos incompletos y sin repetidos. Nombres, DNI, celulares y
correos que podrían ser de personas distintas de verdad.

### Una alumna en los dos deportes

Una misma persona anotada en Patín y en Fútbol.

**No es un alumno con dos deportes: son dos registros con el mismo DNI**, uno por
deporte, cada uno con su grupo, su plan y su cuota. Lo permite el contrato
`Wings-Contrato-ABM-Admin-V1.md` §9.c: el DNI es único **combinado con el deporte**.

---

## Falta definir: los planes

**El documento no los menciona y sin ellos no se puede crear ningún alumno.**

Los planes son la frecuencia semanal con su precio, se cargan **dentro de cada grupo**
y no están en el menú lateral. El campo para elegirlos **no aparece en el formulario
de alumno** si el grupo no tiene ninguno, así que la carga se traba sin explicación
visible.

Hay que definir, antes de empezar: **cuántas frecuencias por grupo y a qué precio.**

---

# Etapa 2 · Cargar la deuda desde un Excel

Un script que toma un Excel del cliente y carga la deuda que cada alumno arrastra.

Pendiente de definir el formato. Ya se conversó que el Excel debe traer **el monto de
cada mes**, no calcularlo del plan actual: si el precio subió, derivarlo cobraría de
más por meses viejos.

---

# Etapa 3 · Simulación de tres meses

Día por día, **a modo de cuento**: qué pasa cada día en el club y cómo lo cubre el
sistema. Con todas las situaciones que pueden darse en una jornada.

Reemplaza y absorbe lo especificado en `SIMULADOR-TRES-MESES-V1.md`, que queda como
referencia de qué verificar al cierre de cada mes.

---

# Para el final, después de todo lo anterior

- **Organizar el menú del administrador.**
- **Dashboards.**

Los dos van al final a propósito: los reportes agregan pantallas y el menú se
reordena igual, así que hacerlo antes obliga a hacerlo dos veces.

---

## Qué reemplaza este documento

`DATASET-SEEDER-V1.md` describía 15 alumnos inventados, cada uno para verificar un
caso. **Ese enfoque queda de lado.** Acá la prueba no es un catálogo de casos: es
**reproducir la carga real de un club**, y ver si el sistema la aguanta.
