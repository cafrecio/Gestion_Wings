const byId = id => document.getElementById(id);
let revisado = false;
let catalogosListos = true;
const mostrar = (id, visible) => { byId(id).hidden = !visible; };
function estadoCatalogos(listos) {
  catalogosListos = listos;
  byId('planes-total').textContent = listos ? '2' : '0';
  byId('catalogos-estado').className = `notice ${listos ? 'success' : 'warning'}`;
  byId('catalogos-estado').textContent = listos ? 'Listo. Podés descargar la plantilla.' : 'Faltan planes con precio. Prepará los catálogos antes de descargar y revisar el Excel.';
  mostrar('catalogos-acciones', !listos);
  mostrar('catalogos-lista', listos);
  byId('revisar').disabled = !listos;
  byId('descargar-plantilla').setAttribute('aria-disabled', String(!listos));
  byId('descargar-plantilla').style.opacity = listos ? '1' : '.45';
}
function reiniciar() {
  revisado = false;
  byId('revision-vacia').textContent = 'Revisá el archivo para saber si está listo para cargar.';
  const escenario = byId('escenario').value;
  estadoCatalogos(escenario !== 'catalogos');
  byId('archivo-nombre').textContent = escenario === 'errores' ? 'club-con-errores.xlsx' : 'club-corregido.xlsx';
  ['revision-resultado','confirmacion','carga-realizada'].forEach(id => mostrar(id, false));
  ['revision-vacia','carga-pendiente'].forEach(id => mostrar(id, true));
  byId('cargar').disabled = true;
  byId('carga-ayuda').textContent = 'Primero revisá el Excel.';
  if (escenario === 'pagos') {
    mostrar('revision-vacia', false);
    revisar();
    terminar();
  }
}
function revisar() {
  if (!catalogosListos) return;
  const conErrores = byId('escenario').value === 'errores';
  revisado = !conErrores;
  mostrar('revision-vacia', false);
  mostrar('revision-resultado', true);
  mostrar('errores-panel', conErrores);
  mostrar('correcto-panel', !conErrores);
  byId('revision-estado').className = `notice ${conErrores ? 'danger' : 'success'}`;
  byId('revision-estado').textContent = conErrores ? 'No se cargó nada. Encontramos 6 errores en 3 filas. Descargá el Excel marcado, corregilo y volvé a Revisar.' : 'Revisión terminada: sin errores. Todavía no se cargó nada. Podés confirmar la carga.';
  byId('cargar').disabled = conErrores;
  byId('carga-ayuda').textContent = conErrores ? 'Corregí los 6 errores antes de cargar.' : 'Archivo revisado. 4 alumnos y $157.000 de deuda.';
}
function terminar() {
  byId('revision-estado').textContent = 'Este archivo se revisó sin errores y su carga ya terminó.';
  mostrar('confirmacion', false);
  mostrar('carga-pendiente', false);
  mostrar('carga-realizada', true);
  const conPagos = byId('escenario').value === 'pagos';
  mostrar('deshacer-disponible', !conPagos);
  mostrar('deshacer-bloqueado', conPagos);
}
byId('escenario').addEventListener('change', reiniciar);
byId('reiniciar').addEventListener('click', reiniciar);
byId('preparar').addEventListener('click', () => estadoCatalogos(true));
byId('revisar').addEventListener('click', revisar);
byId('descargar-plantilla').addEventListener('click', event => { if (!catalogosListos) event.preventDefault(); });
byId('cargar').addEventListener('click', () => { if (revisado) { mostrar('carga-pendiente', false); mostrar('confirmacion', true); }});
byId('volver').addEventListener('click', () => { mostrar('confirmacion', false); mostrar('carga-pendiente', true); });
byId('confirmar').addEventListener('click', terminar);
byId('deshacer').addEventListener('click', () => {
  if (!window.confirm('¿Deshacer esta carga de ejemplo? Se retirarán los 4 alumnos y sus deudas.')) return;
  reiniciar();
  byId('revision-vacia').textContent = 'Carga de ejemplo deshecha. Podés revisar el Excel nuevamente.';
});
reiniciar();
