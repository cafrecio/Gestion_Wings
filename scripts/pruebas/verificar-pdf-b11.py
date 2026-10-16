"""Comprobar los PDF reales de B11; no genera ni modifica sus páginas."""
import json
from pathlib import Path
from pypdf import PdfReader

directorio = Path('docs/06-pruebas/PRU-04/evidencia-b11')
resultados = []
for porcentaje in (0, 5):
    lector = PdfReader(directorio / f'recibo-{porcentaje}.pdf')
    texto = '\n'.join(pagina.extract_text() for pagina in lector.pages)
    assert len(lector.pages) == 1, 'El recibo debe conservar una página A5.'
    assert 'Octubre 2026' in texto and '$10,000.00' in texto
    assert texto.count('Recargo por mora') == (1 if porcentaje else 0)
    if porcentaje:
        assert '$500.00' in texto and '$10,500.00' in texto
    else:
        assert '$500.00' not in texto and '$10,500.00' not in texto
    resultados.append({'porcentaje': porcentaje, 'paginas': len(lector.pages),
                       'recargos': texto.count('Recargo por mora'), 'texto': texto})
(directorio / 'recibos-texto.json').write_text(json.dumps(resultados, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
print('PDF de 0% y 5% correctos: una página A5, recargo separado solo en 5%.')
