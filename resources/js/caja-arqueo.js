const form = document.querySelector('[data-caja-arqueo]');
if (form) {
    const contado = form.querySelector('[name="efectivo_contado"]');
    const cambio = form.querySelector('[name="cambio_retenido"]');
    const diferencia = form.querySelector('[data-diferencia]');
    const retiro = form.querySelector('[data-retiro]');
    const esperado = form.dataset.esperadoCentavos === '' ? null : Number(form.dataset.esperadoCentavos);
    const money = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' });
    // data-money muestra pesos enteros con separador de miles; los ocultos son DECIMAL canónico.
    const centavos = (input) => input.value === '' ? null : Math.round(Number(
        input.hasAttribute('data-importe-canonico') ? input.value : input.value.replaceAll('.', '')
    ) * 100);
    function actualizar() {
        const real = centavos(contado);
        const retenido = centavos(cambio);
        cambio.setCustomValidity('');
        diferencia.textContent = real === null ? 'Ingresá el efectivo contado'
            : esperado === null ? 'No calculable sin declaración inicial'
            : (real < esperado ? 'Faltante ' : real > esperado ? 'Sobrante ' : 'Sin diferencia ')
                + money.format(Math.abs(real - esperado) / 100);
        diferencia.style.color = real !== null && esperado !== null && real !== esperado
            ? 'var(--color-danger)' : 'var(--color-text)';
        retiro.textContent = real === null || retenido === null ? 'Contado menos cambio que queda'
            : retenido > real ? 'El cambio no puede superar lo contado' : money.format((real - retenido) / 100);
        if (real !== null && retenido !== null && retenido > real) {
            cambio.setCustomValidity('El cambio no puede superar el efectivo contado.');
        }
    }
    // La delegación lee el importe después del formateador compartido del componente.
    form.addEventListener('input', actualizar);
    actualizar();
}
