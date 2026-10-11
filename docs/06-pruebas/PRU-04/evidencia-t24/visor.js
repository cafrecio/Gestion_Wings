const pantallas = [
    ['apertura','Apertura de caja'],['rectificacion','Rectificar apertura'],
    ['cierre-contado','Cierre · efectivo contado'],['cierre-cambio','Cierre · cambio que queda'],
    ['cuota','Cobrar · cuota pendiente'],['adelantado','Cobrar · elegir importe adelantado'],
    ['adelantado-fila-nueva','Cobrar · fila adelantada agregada'],['entregado','Cobrar · importe entregado'],
    ['caja-agregar','Caja · agregar movimiento'],['caja-editar','Caja · editar movimiento'],
    ['caja-movimiento','Caja · nuevo movimiento'],['cashflow','Cashflow · nuevo movimiento'],
    ['grupo-alta','Grupos · alta'],['grupo-editar','Grupos · editar'],['grupo-fila-nueva','Grupos · frecuencia agregada'],
    ['profesor-alta','Profesores · alta'],['profesor-editar','Profesores · editar'],
    ['medio-alta','Medio de pago · alta'],['medio-editar','Medio de pago · editar'],
    ['configuracion','Configuración · inscripción'],['liquidacion','Liquidación · monto ajustado'],
    ['medio-historial','Medio con historial · solo lectura',true],['cierre-con-conteo','Cierre ya contado · importes conservados',true],
    ['login','Login · control del marco',true],
];
const resolucion = document.getElementById('resolucion');
const estado = document.getElementById('estado');
function pintar() {
    const galeria = document.getElementById('galeria');
    galeria.replaceChildren();
    for (const [id,titulo,soloLectura] of pantallas) {
        const modo = resolucion.value;
        const estadoImagen = soloLectura ? 'valido' : estado.value;
        const ruta = id === 'login' ? `capturas/login-${modo}.png` : `capturas/${id}-${modo}-${estadoImagen}.png`;
        const articulo = document.createElement('article');
        const encabezado = document.createElement('h2'); encabezado.textContent = titulo;
        const enlace = document.createElement('a'); enlace.href = ruta; enlace.target = '_blank'; enlace.rel = 'noopener';
        const imagen = document.createElement('img'); imagen.src = ruta; imagen.alt = `${titulo} · ${modo} · ${estadoImagen}`; imagen.loading = 'lazy';
        const nota = document.createElement('p'); nota.className = 'nota'; nota.textContent = soloLectura
            ? (id === 'login' ? 'Control: contenido completo a 360 px.' : 'Importe protegido: no se puede escribir.')
            : (estadoImagen === 'aviso' ? '48.505 → 48.50 · aviso de 4 segundos.' : '48,000.50 · coma de miles y punto decimal.');
        enlace.append(imagen); articulo.append(encabezado,enlace,nota); galeria.append(articulo);
    }
}
resolucion.addEventListener('change', pintar); estado.addEventListener('change', pintar); pintar();
