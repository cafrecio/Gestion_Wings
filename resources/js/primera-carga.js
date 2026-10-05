const descarga = document.querySelector('[data-descargar-plantilla]');
descarga?.addEventListener('click', async (event) => {
    event.preventDefault();
    if (descarga.dataset.enCurso) return;
    descarga.dataset.enCurso = '1';
    try {
        const response = await fetch(descarga.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!response.ok || !response.headers.get('Content-Type')?.includes('spreadsheetml')) throw new Error('No se pudo descargar');
        const url = URL.createObjectURL(await response.blob());
        const link = document.createElement('a');
        link.href = url;
        link.download = 'primera-carga.xlsx';
        link.click();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
        const destino = new URL(descarga.dataset.volver, window.location.href);
        // Un cambio de #fragmento no consulta al servidor: hay que releer el paso
        // que la descarga acaba de guardar en sesión.
        if (destino.pathname === window.location.pathname && destino.search === window.location.search) {
            window.location.hash = destino.hash;
            window.location.reload();
        } else {
            window.location.assign(destino.href);
        }
    } catch {
        window.location.assign(descarga.href);
    } finally {
        delete descarga.dataset.enCurso;
    }
});

const confirmar = document.querySelector('[data-carga-confirmar]');
const form = document.querySelector('[data-carga-form]');
confirmar?.addEventListener('click', () => {
    form.hidden = false;
    confirmar.closest('.alumno-actions').hidden = true;
    form.querySelector('button[type="submit"]').focus();
});
document.querySelector('[data-carga-volver]')?.addEventListener('click', () => {
    form.hidden = true;
    confirmar.closest('.alumno-actions').hidden = false;
    confirmar.focus();
});
