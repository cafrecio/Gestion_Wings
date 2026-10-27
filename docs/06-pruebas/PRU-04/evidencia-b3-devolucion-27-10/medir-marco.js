document.getElementById('pantalla').addEventListener('load', function () {
    document.getElementById('medicion').textContent = 'Marco375 · ancho real ' + this.contentDocument.documentElement.clientWidth + 'px';
});
