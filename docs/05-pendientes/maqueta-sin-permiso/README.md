# A29 / A30 / A31 — maqueta aprobada; implementación entregada, pendiente Gemini

04/10/2026, Codex CAB. Preparación sobre `853873a`, después de git pull.

Carlos aprobó ambas maquetas con «OK ambos», después de quitar la comparación
de perfiles y corregir Cancelar/Guardar en A43. Luego escribió:

`Diseno-autorizado: Carlos aprueba A43 y A29, A30 y A31 según las maquetas presentadas, conservando el diseño Wings.`

Abrir [Profesor](index.html), [Operativo](operativo.html) y
[Administrador](admin.html). Comparten el mensaje y el botón **Volver**;
el destino es Clases, Inicio del operativo o tablero del administrador.
Carlos pidió quitar la comparación de perfiles: retirada de las tres maquetas.
Cada ejemplo se abre por los enlaces de este documento. Las explicaciones
de la maqueta no se incorporan a la pantalla del sistema.

Usa exclusivamente el CSS compilado existente de Wings. Sin CSS nuevo,
colores propios, JavaScript ni cambios de permisos. La maqueta se sirve localmente
desde `docs/05-pendientes`, en `http://127.0.0.1:8771/maqueta-sin-permiso/`.
Necesita la instalación local `http://gestion-wings` y sus recursos compilados.

Comprobada en navegador a ancho normal y 375 px: ancho del documento 375 px;
botón visible de 96 × 32 px. [Captura](../../06-pruebas/PRU-02/evidencia/permisos/maqueta-profesor-desktop.png)
y [celular](../../06-pruebas/PRU-02/evidencia/permisos/maqueta-profesor-mobile.png).

Pruebas previas contra el código actual, en `wings_testing_codex`:
**12 fallidas / 2 aprobadas, 21 aserciones**. Una ejecución independiente por
perfil y ruta: profesor en Alumnos, Caja, Grupos, Cashflow, Liquidaciones,
Usuarios y Configuración; operativo en Cashflow, Liquidaciones, Usuarios y
Configuración; administrador común al editar una cuenta protegida. También
se comprueba que anónimo e inactivo sigan requiriendo ingreso.

El borrador se retiró durante la espera de autorización. Al implementar se repuso
como `tests/Feature/AccesoSinPermisoTest.php`, con 14 métodos explícitos, uno por
rol/ruta y casos anónimo e inactivo; mismo resultado previo 12 fallos/2 correctos.
Registro local no versionado: `storage/logs/a29-a31-pruebas-rojas.txt`.

Aplicación implementada con autorización recibida; [entrega y capturas reales](../../06-pruebas/PRU-02/IMPLEMENTACION-PERMISOS.md).
A29, A30 y A31 siguen abiertos hasta que Gemini verifique código y pantalla. Sin despliegue.
