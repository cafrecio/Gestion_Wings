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
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            color: #1e40af;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }

        .header .subtitle {
            font-size: 14px;
            color: #6b7280;
        }

        .recibo-numero {
            background-color: #1e40af;
            color: white;
            padding: 8px 15px;
            display: inline-block;
            font-size: 14px;
            font-weight: bold;
            border-radius: 4px;
            margin-top: 10px;
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
            color: #1e40af;
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
            width: 30%;
        }

        .info-value {
            display: table-cell;
            padding: 4px 0;
        }

        .periodos-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .periodos-table th,
        .periodos-table td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            text-align: left;
        }

        .periodos-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #374151;
            font-size: 10px;
            text-transform: uppercase;
        }

        .periodos-table td.monto {
            text-align: right;
            font-family: 'DejaVu Sans Mono', monospace;
        }

        .total-row {
            background-color: #1e40af;
            color: white;
        }

        .total-row td {
            font-weight: bold;
            border-color: #1e40af;
        }

        .monto-total {
            font-size: 16px;
            text-align: right;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>WINGS</h1>
        <div class="subtitle">Academia Deportiva</div>
        <div class="recibo-numero">{{ $numero_recibo }}</div>
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
        <div class="section-title">Datos del Alumno</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nombre:</div>
                <div class="info-value">{{ $alumno['nombre'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">DNI:</div>
                <div class="info-value">{{ $alumno['dni'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Deporte:</div>
                <div class="info-value">{{ $alumno['deporte'] }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Detalle del Pago</div>
        <table class="periodos-table">
            <thead>
                <tr>
                    <th>Periodo</th>
                    <th style="text-align: right;">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($periodos as $periodo)
                <tr>
                    <td>{{ $periodo['periodo_texto'] }}</td>
                    <td class="monto">$ {{ number_format($periodo['monto_aplicado'], 2, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td><strong>TOTAL PAGADO</strong></td>
                    <td class="monto monto-total">$ {{ number_format($monto_total, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Medio de Cobro</div>
        <div class="medio-pago">
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Tipo:</div>
                    <div class="info-value">{{ $medio_cobro['tipo_caja'] }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Registrado por:</div>
                    <div class="info-value">{{ $medio_cobro['origen'] }}</div>
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
            Wings - Recibo no valido como factura
        </div>
        <div class="firma">
            Firma / Sello
        </div>
    </div>
</body>
</html>
