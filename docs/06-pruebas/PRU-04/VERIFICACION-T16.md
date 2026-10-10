# T16 — verificación independiente, 10/10/2026

**DEVUELTO a Claude.** Codex CyE verificó `462e8b1` sobre una copia de `1972b48`, excluyendo los cambios simultáneos de T17/T18. Solo `wings_testing_codex`; sitio de prueba únicamente consultado.

Dos fallas reproducidas:

1. **La edición del rubro elude la regla de clasificación.** ADMIN abre Editar y envía `PUT /rubros/{id}`: Indumentaria pasa a EGRESO conservando sus APORTE; Retiros pasa a INGRESO conservando RETIRO. Ambos guardan con HTTP302; los movimientos posteriores capturan esas combinaciones inválidas. Falta el control en [RubroWebController::update](../../../app/Http/Controllers/RubroWebController.php), además del existente en SubrubroWebController.
2. **Nombre nuevo preexistente sin clasificación:** con «Retiro de dueños» y «Pago al organizador» ya creados bajo sus rubros correctos, pero con clasificación NULL, la migración deja ambos NULL. [aplicarCatalogo](../../../app/Support/ClasificacionSubrubros.php) salta cualquier nombre existente antes de completar lo faltante.

| Pedido | Comprobación |
|---|---|
| 1. Catálogo de Carlos | 18 entradas conocidas + los dos nuevos comprobados. Indumentaria: sus tres APORTE; Retiro de dueños: RETIRO; resto: NEGOCIO. |
| 2. Instalación existente | Migración real ejecutada dos veces: preserva clasificaciones y filas desconocidas; no duplica rubros/subrubros. Falla el borde del punto 2 de arriba. |
| 3. Instalación nueva | `migrate:fresh --seed` (DatabaseSeeder llama CatalogosSeeder): 18 subrubros, ninguno NULL; los dos rubros y sus subrubros, una vez cada uno. Filas completas inspeccionadas. |
| 4. Movimientos | Cashflow y operativos: NULL empieza a contar; las filas clasificadas conservan todos sus atributos. También al clasificar desde PUT. Cambiar luego el subrubro conserva la clasificación histórica de sus movimientos. |
| 5. Formulario | POST/PUT manuales rechazan vacío, APORTE en egreso, RETIRO en ingreso, array y valor desconocido, sin guardar. Admiten ambas opciones válidas por tipo. |
| 6. Reservados | Cuota Mensual, Inscripción y sueldo: editar/PUT/toggle, campos falsificados, padre cruzado y DELETE no los alteran. API sin rutas registradas. Altas/ediciones de profesor y operativo ignoran intentos de imponer otra clasificación. |
| 7. Otros caminos | Barrido de app, migraciones, seeders, rutas, scripts y JS; observador y reflejo de caja usan la clasificación guardada. Hallazgo: edición del padre, punto 1. |

**Observación separada:** la fase `DemoSeeder::fase2Profesores` aún crea seis subrubros de sueldo sin clasificación. Se ejecutó esa fase aislada y se miraron las seis filas. DemoSeeder está bloqueado en producción; no se ejecutó completo. PrimeraCargaCompletaSeeder y las altas habituales usan SubrubroSueldoService, que asigna NEGOCIO.

**Pruebas:** suite completa publicada de T16, 598 aprobadas/2 omitidas, 5.003 aserciones, 737,33 s. Controles independientes: **9 aprobados/3 fallidos**, 224 aserciones, 26,39 s ([salida](evidencia-t16/controles-independientes.txt)); las tres fallas corresponden a las dos causas anteriores. Los primeros controles tuvieron dos errores del verificador (fixture incompleto y HTTP esperado), corregidos antes de la entrega.

**Pantalla:** Inicio y Reportes muestran $164.000; Reportes desglosa cuotas $159.000 + inscripción $5.000. No aparece el aviso de sin clasificar. Capturas reales: [Inicio](evidencia-t16/inicio-test.png), [Reportes](evidencia-t16/reportes-test.png).

**No probado:** producción, instalación real del club, despliegue por consola, DemoSeeder completo ni una nueva evaluación del diseño ya aprobado. No se corrigió Wings ni se cobró/modificó el sitio de prueba.

Evidencia: [suite](evidencia-t16/suite-completa.txt), [instalación nueva](evidencia-t16/instalacion-nueva.txt), [filas del catálogo](evidencia-t16/instalacion-nueva.json), [casos reproducidos](evidencia-t16/casos.jsonl), [verificador](evidencia-t16/VerificacionT16Test.php).

Repetir los controles, sobre el código de T16 y sin otra suite de Codex simultánea:

```powershell
$env:DB_DATABASE='wings_testing_codex'
php vendor/bin/phpunit --configuration phpunit.xml --testdox docs/06-pruebas/PRU-04/evidencia-t16/VerificacionT16Test.php
```

Sigue: Claude corrige las dos causas; Codex verifica otra vez. El formulario conserva la aprobación de Carlos («T16 OK»).
