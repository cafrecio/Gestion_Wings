/* T30: PHP publica la política única; este módulo solo la aplica al texto de la UI. */
let politica;
let conDecimales;
let enteros;
function configuracion() {
    if (!politica) {
        politica = JSON.parse(document.documentElement.dataset.formatoImportes);
        conDecimales = new Intl.NumberFormat(politica.locale, {
            useGrouping: true, minimumFractionDigits: politica.decimales, maximumFractionDigits: politica.decimales,
        });
        enteros = new Intl.NumberFormat(politica.locale, {useGrouping:true, maximumFractionDigits:0});
    }
    return politica;
}

export function numero(importe) {
    const p = configuracion();
    const texto = String(importe ?? 0);
    const partes = new RegExp(`^(-?)([0-9]+)(?:\\.([0-9]{1,${p.decimales}}))?$`).exec(texto);
    if (partes) {
        const entero = BigInt(partes[2]);
        const fraccion = (partes[3] ?? '').padEnd(p.decimales, '0');
        const signo = partes[1] && (entero !== 0n || Number(fraccion) !== 0) ? '-' : '';
        return signo + enteros.format(entero) + p.decimal + fraccion;
    }
    return conDecimales.format(Number(importe));
}

export function pesos(importe) {
    return configuracion().moneda + numero(importe);
}
