const pantalla = document.getElementById('pantalla');
const ancho = document.getElementById('ancho');
function mostrar() {
    for (const articulo of document.querySelectorAll('[data-porcentaje]')) {
        const porcentaje = articulo.dataset.porcentaje;
        const nombre = `${pantalla.value}-${porcentaje}`;
        const archivo = `${nombre}-${ancho.value}.jpg`;
        const marco = articulo.querySelector('.imagen');
        marco.className = 'imagen ' + (ancho.value === '1366' ? 'escritorio' : 'movil');
        marco.style.width = ancho.value === '1366' ? '100%' : ancho.value + 'px';
        articulo.querySelector('img').src = archivo;
        articulo.querySelector('[data-captura]').href = archivo;
        articulo.querySelector('[data-pantalla]').href = nombre + (pantalla.value === 'recibo' ? '-pdf' : '') + '.html';
        const pdf = articulo.querySelector('[data-pdf]');
        pdf.hidden = pantalla.value !== 'recibo';
        pdf.href = `recibo-${porcentaje}.pdf`;
    }
    document.getElementById('dia').href = `configuracion-dia-${ancho.value}.jpg`;
    document.getElementById('login').href = `login-${ancho.value}.jpg`;
}
pantalla.addEventListener('change', mostrar);
ancho.addEventListener('change', mostrar);
mostrar();
