import re
import zlib
import os

pdf_dir = os.path.dirname(os.path.abspath(__file__))

def extract_streams(pdf_path):
    with open(pdf_path, 'rb') as f:
        data = f.read()
    
    streams = []
    # Find all streams
    for match in re.finditer(b'stream\r?\n(.*?)\r?\nendstream', data, re.DOTALL):
        stream_bytes = match.group(1)
        # Try decompressing with zlib
        try:
            decompressed = zlib.decompress(stream_bytes)
            streams.append(decompressed)
        except Exception:
            streams.append(stream_bytes)
    return streams

def extract_text_from_pdf(pdf_path):
    streams = extract_streams(pdf_path)
    text_fragments = []
    for s in streams:
        # Search for text in PDF syntax: (text) Tj or [(text)] TJ
        # Match literal strings (...)
        for m in re.finditer(b'\((.*?)\)', s):
            try:
                # Decode latin1 or utf-8 or cp1252
                text_fragments.append(m.group(1).decode('latin1', errors='ignore'))
            except Exception:
                pass
    return text_fragments

chars_to_check = [
    'á', 'é', 'í', 'ó', 'ú',
    'Á', 'É', 'Í', 'Ó', 'Ú',
    'ñ', 'Ñ',
    '$', '–', '·', '/', ':', ',', '.', '%'
]

print("=== VERIFICACIÓN DE CARACTERES EN RECIBOS CON RECORTE ===")
for base in ['recibo-cuota', 'recibo-cuota-anulada', 'recibo-liquidacion-hora', 'recibo-liquidacion-comision']:
    sin_path = os.path.join(pdf_dir, f"{base}-sin-recorte.pdf")
    con_path = os.path.join(pdf_dir, f"{base}-con-recorte.pdf")
    
    sin_size = os.path.getsize(sin_path)
    con_size = os.path.getsize(con_path)
    
    sin_text = extract_text_from_pdf(sin_path)
    con_text = extract_text_from_pdf(con_path)
    
    sin_combined = " ".join(sin_text)
    con_combined = " ".join(con_text)
    
    print(f"\n--- {base} ---")
    print(f"Tamaño sin recorte: {sin_size/1024:.1f} KB | con recorte: {con_size/1024:.1f} KB")
    
    # Check key words
    keywords = {
        'recibo-cuota': ['Bego', 'ez', 'P', '35,000.00', 'Efectivo'],
        'recibo-cuota-anulada': ['ANULAD', 'Error', 'tipeo', 'Transferencia'],
        'recibo-liquidacion-hora': ['G', 'ez', '12,000.00', 'dictadas', '72,000.00'],
        'recibo-liquidacion-comision': ['Comisi', 'cobradas', '45,000.00', '15,000.00']
    }
    
    print(f"Número de fragmentos de texto: sin={len(sin_text)}, con={len(con_text)}")
