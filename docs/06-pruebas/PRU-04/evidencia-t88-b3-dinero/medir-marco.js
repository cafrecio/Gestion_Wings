const marco = document.getElementById('pantalla');
marco.addEventListener('load', () => {
    document.getElementById('medida').textContent = `Marco375 · ancho real ${marco.contentWindow.innerWidth}px`;
});
