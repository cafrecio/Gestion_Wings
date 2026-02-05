<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo {{ $numero_recibo }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #059669;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            color: #047857;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }

        .header .subtitle {
            font-size: 14px;
            color: #6b7280;
        }

        .recibo-numero {
            background-color: #047857;
            color: white;
            padding: 8px 15px;
            display: inline-block;
            font-size: 14px;
            font-weight: bold;
            border-radius: 4px;
            margin-top: 10px;
        }

        .tipo-badge {
            display: inline-block;
            background-color: #fef3c7;
            color: #92400e;
            padding: 3px 8px;
            font-size: 10px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
            margin-left: 10px;
        }

        .fechas {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            background-color: #f3f4f6;
            padding: 10px;
            border-radius: 4px;
        }

        .fechas .col {
            display: table-cell;
            width: 50%;
        }

        .fechas .label {
            font-weight: bold;
            color: #4b5563;
            font-size: 10px;
            text-transform: uppercase;
        }

        .fechas .value {
            font-size: 12px;
            margin-top: 2px;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #047857;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .info-grid {
            display: table;
            width: 100%;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            padding: 4px 0;
            font-weight: bold;
            color: #4b5563;
            width: 35%;
        }

        .info-value {
            display: table-cell;
            padding: 4px 0;
        }

        .monto-box {
            background-color: #047857;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin: 20px 0;
        }

        .monto-box .label {
            font-size: 11px;
            text-transform: uppercase;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .monto-box .monto {
            font-size: 28px;
            font-weight: bold;
            font-family: 'DejaVu Sans Mono', monospace;
        }

        .periodo-box {
            background-color: #ecfdf5;
            border: 1px solid #6ee7b7;
            border-radius: 4px;
            padding: 15px;
            text-align: center;
        }

        .periodo-box .label {
            font-size: 10px;
            color: #4b5563;
            text-transform: uppercase;
        }

        .periodo-box .value {
            font-size: 16px;
            font-weight: bold;
            color: #047857;
            margin-top: 5px;
        }

        .medio-pago {
            background-color: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 4px;
            padding: 10px;
        }

        .observaciones {
            background-color: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 4px;
            padding: 10px;
            font-style: italic;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px dashed #9ca3af;
            text-align: center;
        }

        .footer .leyenda {
            font-size: 10px;
            color: #6b7280;
            font-style: italic;
        }

        .footer .firma {
            margin-top: 40px;
            padding-top: 5px;
            border-top: 1px solid #333;
            width: 200px;
            margin-left: auto;
            margin-right: auto;
            font-size: 10px;
            color: #6b7280;
        }

        .interno-badge {
            display: inline-block;
            background-color: #fee2e2;
            color: #991b1b;
            padding: 2px 6px;
            font-size: 9px;
            font-weight: bold;
            border-radius: 2px;
            text-transform: uppercase;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>WINGS</h1>
        <div class="subtitle">Academia Deportiva <span class="interno-badge">Documento Interno</span></div>
        <div class="recibo-numero">{{ $numero_recibo }}</div>
        <span class="tipo-badge">Liquidacion {{ $profesor['tipo_liquidacion'] }}</span>
    </div>

    <div class="fechas">
        <div class="col">
            <div class="label">Fecha de Emision</div>
            <div class="value">{{ $fecha_emision->format('d/m/Y H:i') }}</div>
        </div>
        <div class="col">
            <div class="label">Fecha de Pago</div>
            <div class="value">{{ $fecha_pago ? $fecha_pago->format('d/m/Y') : 'N/D' }}</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Datos del Profesor</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nombre:</div>
                <div class="info-value">{{ $profesor['nombre'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Deporte:</div>
                <div class="info-value">{{ $profesor['deporte'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Tipo Liquidacion:</div>
                <div class="info-value">{{ $profesor['tipo_liquidacion'] }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Periodo Liquidado</div>
        <div class="periodo-box">
            <div class="label">Mes / Anio</div>
            <div class="value">{{ $periodo['texto'] }}</div>
        </div>
    </div>

    <div class="monto-box">
        <div class="label">Total Liquidado</div>
        <div class="monto">$ {{ number_format($total_liquidado, 2, ',', '.') }}</div>
    </div>

    <div class="section">
        <div class="section-title">Medio de Pago</div>
        <div class="medio-pago">
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Tipo de Caja:</div>
                    <div class="info-value">{{ $medio_pago['tipo_caja'] }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Subrubro:</div>
                    <div class="info-value">{{ $medio_pago['subrubro'] }}</div>
                </div>
            </div>
        </div>
    </div>

    @if($observaciones)
    <div class="section">
        <div class="section-title">Observaciones</div>
        <div class="observaciones">
            {{ $observaciones }}
        </div>
    </div>
    @endif

    <div class="footer">
        <div class="leyenda">
            Wings - Comprobante interno
        </div>
        <div class="firma">
            Firma / Sello
        </div>
    </div>
</body>
</html>
