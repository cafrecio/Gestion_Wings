"""Verifica texto y sumas de PDF reales exportados por MostrarImportesT30Test.

Requiere pypdf; no consulta bases ni modifica los recibos.
"""
from decimal import Decimal
from pathlib import Path
import re
from pypdf import PdfReader

root = Path('docs/06-pruebas/PRU-04/evidencia-t30/pdf')
for perfil, importe in [('centavos', Decimal('1250.50')), ('millones', Decimal('1250000.50'))]:
    for tipo, total in [('cuota', importe), ('hora', importe * Decimal('1.5')), ('comision', importe * Decimal('.2'))]:
        texto = '\n'.join(p.extract_text() for p in PdfReader(root / f'recibo-{tipo}-{perfil}.pdf').pages)
        formato = f'${total:,.2f}'
        assert texto.count(formato) >= 2, f'{tipo}/{perfil}: faltan detalle y total {formato}'
        montos = re.findall(r'\$([\d,]+\.\d{2})(?!\d)', texto)
        assert montos, f'{tipo}/{perfil}: no hay importes'
        assert all('.' in x and len(x.rsplit('.', 1)[1]) == 2 for x in montos)
        if tipo == 'hora':
            filas = re.findall(r'(?:Validada|Con asistencia)\s+\$([\d,]+\.\d{2})', texto)
            assert len(filas) == 2, f'{tipo}/{perfil}: faltan clases'
            assert sum(Decimal(x.replace(',', '')) for x in filas) == total
        if tipo == 'comision':
            assert f'${importe:,.2f}' in texto, 'La cuota base perdió centavos'
        print(f'{tipo}/{perfil}: detalle y total {formato} correctos')
