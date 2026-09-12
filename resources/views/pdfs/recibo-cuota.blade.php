<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo {{ $numero_recibo }}</title>
    <style>
        @page {
            size: 148mm 210mm portrait;
            margin: 10mm 12mm 10mm 12mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            line-height: 1.35;
            color: #1e293b;
        }
        .recibo-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .recibo-header-table td {
            vertical-align: middle;
        }
        .logo-frame {
            background-color: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 5px;
            padding: 3px 6px;
            display: inline-block;
            vertical-align: middle;
            margin-right: 8px;
        }
        .recibo-brand-logo {
            height: 44px;
            width: auto;
            display: block;
        }
        .recibo-brand-title {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 1.2px;
            line-height: 1.1;
        }
        .recibo-brand-subtitle {
            font-size: 8px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 1px;
        }
        .recibo-badge-doc {
            font-size: 7.5px;
            color: #64748b;
            margin-top: 2px;
            font-weight: bold;
        }
        .recibo-badge-num {
            background-color: #0f172a;
            color: #ffffff;
            padding: 3px 9px;
            font-size: 11px;
            font-weight: bold;
            font-family: 'DejaVu Sans Mono', monospace;
            border-radius: 4px;
            display: inline-block;
        }
        .recibo-meta-label {
            color: #64748b;
            font-weight: bold;
            text-transform: uppercase;
            margin-right: 4px;
        }
        .recibo-meta-value {
            color: #0f172a;
            font-weight: bold;
        }
        .recibo-header-divider-td {
            border-bottom: 2px solid #0f172a;
            height: 5px;
            font-size: 1px;
            line-height: 1px;
            padding: 0;
        }
        .recibo-sello-anulado {
            background-color: #fef2f2;
            border: 2px dashed #dc2626;
            color: #b91c1c;
            text-align: center;
            font-size: 10.5px;
            font-weight: bold;
            letter-spacing: 1.5px;
            padding: 5px;
            border-radius: 4px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .recibo-section {
            margin-bottom: 8px;
        }
        .recibo-section-title {
            font-size: 8px;
            font-weight: bold;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 3px;
            padding-bottom: 2px;
            border-bottom: 1px solid #e2e8f0;
        }
        .recibo-card-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        .recibo-card-table td {
            padding: 4px 7px;
            font-size: 8.5px;
            vertical-align: top;
        }
        .recibo-card-label {
            color: #64748b;
            font-size: 7px;
            text-transform: uppercase;
            font-weight: bold;
            display: block;
            margin-bottom: 1px;
        }
        .recibo-card-value {
            color: #0f172a;
            font-weight: bold;
        }
        .recibo-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
            border: 1px solid #e2e8f0;
        }
        .recibo-items-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 4px 7px;
            border: 1px solid #e2e8f0;
            text-align: left;
        }
        .recibo-items-table td {
            padding: 4px 7px;
            border: 1px solid #e2e8f0;
            font-size: 8.5px;
        }
        .recibo-items-table td.monto {
            text-align: right;
            font-weight: bold;
            font-family: 'DejaVu Sans Mono', monospace;
            color: #0f172a;
        }
        .recibo-total-row td {
            background-color: #f1f5f9;
            border-top: 2px solid #cbd5e1;
            padding: 5px 7px;
        }
        .recibo-total-label {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.5px;
            color: #1e293b;
        }
        .recibo-total-monto {
            text-align: right;
            font-weight: bold;
            font-size: 11.5px;
            font-family: 'DejaVu Sans Mono', monospace;
            color: #0f172a;
        }
        .recibo-obs-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 3px solid #64748b;
            border-radius: 3px;
            padding: 4px 7px;
            font-size: 8px;
            color: #334155;
        }
        .recibo-obs-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7px;
            margin-bottom: 1px;
            color: #475569;
        }
        .recibo-footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px dashed #cbd5e1;
        }
        .recibo-footer-table td {
            vertical-align: bottom;
        }
        .recibo-footer-legal {
            font-size: 7px;
            color: #94a3b8;
            line-height: 1.3;
        }
        .recibo-footer-legal strong {
            color: #475569;
        }
        .recibo-firma-container {
            width: 150px;
            margin-left: auto;
            text-align: center;
        }
        .recibo-firma-line {
            border-top: 1px solid #475569;
            margin-bottom: 2px;
        }
        .recibo-firma-label {
            font-size: 7px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

    @if($anulado ?? false)
        <div class="recibo-sello-anulado">✗ RECIBO ANULADO — EL PAGO FUE CANCELADO</div>
    @endif

    <table class="recibo-header-table">
        <tr>
            <td style="width: 58%;">
                <table style="border-collapse: collapse;">
                    <tr>
                        <td style="padding-right: 8px; vertical-align: middle;">
                            <div class="logo-frame">
                                <img src="{{ public_path('img/logo-wings.png') }}" class="recibo-brand-logo" alt="Wings">
                            </div>
                        </td>
                        <td style="vertical-align: middle;">
                            <div class="recibo-brand-title">WINGS CLUB</div>
                            <div class="recibo-brand-subtitle">Academia Deportiva</div>
                            <div class="recibo-badge-doc">Comprobante de Cobro de Cuota</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 42%; vertical-align: middle;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="text-align: right; padding-bottom: 3px;">
                            <span class="recibo-badge-num">{{ $numero_recibo }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: right; font-size: 8px; line-height: 12px;">
                            <span class="recibo-meta-label">Emisión:</span>
                            <span class="recibo-meta-value">{{ $fecha_emision ? ($fecha_emision instanceof \DateTimeInterface ? $fecha_emision->format('d/m/Y H:i') : \Carbon\Carbon::parse($fecha_emision)->format('d/m/Y H:i')) : now()->format('d/m/Y H:i') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: right; font-size: 8px; line-height: 12px;">
                            <span class="recibo-meta-label">Fecha Pago:</span>
                            <span class="recibo-meta-value">{{ $fecha_pago ? ($fecha_pago instanceof \DateTimeInterface ? $fecha_pago->format('d/m/Y') : \Carbon\Carbon::parse($fecha_pago)->format('d/m/Y')) : 'N/D' }}</span>
                        </td>
                    </tr>
                    @if($anulado ?? false)
                    <tr>
                        <td style="text-align: right; font-size: 8px; line-height: 12px;">
                            <span class="recibo-meta-label">Cancelado:</span>
                            <span class="recibo-meta-value" style="color: #b91c1c;">
                                {{ isset($fecha_cancelacion) && $fecha_cancelacion ? \Carbon\Carbon::parse($fecha_cancelacion)->format('d/m/Y H:i') : 'Registrado' }}
                            </span>
                        </td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
        <tr><td colspan="2" class="recibo-header-divider-td">&nbsp;</td></tr>
    </table>

    <div class="recibo-section">
        <div class="recibo-section-title">Datos del Alumno</div>
        <table class="recibo-card-table">
            <tr>
                <td style="width: 45%;">
                    <span class="recibo-card-label">Alumno / Alumna</span>
                    <span class="recibo-card-value">{{ $alumno['nombre'] }}</span>
                </td>
                <td style="width: 25%;">
                    <span class="recibo-card-label">Documento (DNI)</span>
                    <span class="recibo-card-value">{{ $alumno['dni'] }}</span>
                </td>
                <td style="width: 30%;">
                    <span class="recibo-card-label">Deporte / Disciplina</span>
                    <span class="recibo-card-value">{{ $alumno['deporte'] }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="recibo-section">
        <div class="recibo-section-title">Detalle de Períodos Cobrados</div>
        <table class="recibo-items-table">
            <thead>
                <tr>
                    <th>Concepto / Período</th>
                    <th style="width: 30%; text-align: right;">Importe</th>
                </tr>
            </thead>
            <tbody>
                @forelse($periodos as $periodo)
                    <tr>
                        <td>{{ $periodo['periodo_texto'] ?? $periodo['periodo'] }}</td>
                        <td class="monto">$ {{ number_format($periodo['monto_aplicado'], 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="text-align: center; color: #94a3b8; font-style: italic;">
                            Cobro registrado sin detalle de períodos disponible
                        </td>
                    </tr>
                @endforelse
                <tr class="recibo-total-row">
                    <td class="recibo-total-label">
                        {{ ($anulado ?? false) ? 'Total Cobrado (Revertido)' : 'Total Cobrado' }}
                    </td>
                    <td class="recibo-total-monto">$ {{ number_format($monto_total, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="recibo-section">
        <div class="recibo-section-title">Información de Cobro</div>
        <table class="recibo-card-table">
            <tr>
                <td style="width: {{ ($anulado ?? false) ? '33%' : '50%' }};">
                    <span class="recibo-card-label">Medio de Cobro</span>
                    <span class="recibo-card-value">{{ $medio_cobro['tipo_caja'] }}</span>
                </td>
                <td style="width: {{ ($anulado ?? false) ? '33%' : '50%' }};">
                    <span class="recibo-card-label">Registrado por</span>
                    <span class="recibo-card-value">{{ $medio_cobro['origen'] }}</span>
                </td>
                @if($anulado ?? false)
                <td style="width: 34%;">
                    <span class="recibo-card-label">Cancelado por</span>
                    <span class="recibo-card-value">{{ $cancelado_por ?? 'Administración' }}</span>
                </td>
                @endif
            </tr>
        </table>
    </div>

    @if($anulado ?? false)
    <!-- AUDITORÍA DE CANCELACIÓN -->
    <div class="recibo-section">
        <div class="recibo-obs-box" style="border-left: 3px solid #dc2626;">
            <div class="recibo-obs-title" style="color: #0f172a; margin-bottom: 3px;">Auditoría de Fechas & Trazabilidad de Cancelación</div>
            <table style="width: 100%; border-collapse: collapse; font-size: 8px; line-height: 1.35; color: #1e293b;">
                <tr>
                    <td style="width: 50%; padding-bottom: 2px;">
                        <span style="color: #64748b; font-weight: bold; text-transform: uppercase;">1. Fecha Emisión:</span>
                        <strong>{{ $fecha_emision ? ($fecha_emision instanceof \DateTimeInterface ? $fecha_emision->format('d/m/Y H:i') : \Carbon\Carbon::parse($fecha_emision)->format('d/m/Y H:i')) : now()->format('d/m/Y H:i') }} hs</strong>
                    </td>
                    <td style="width: 50%; padding-bottom: 2px;">
                        <span style="color: #64748b; font-weight: bold; text-transform: uppercase;">2. Fecha de Pago:</span>
                        <strong>{{ $fecha_pago ? ($fecha_pago instanceof \DateTimeInterface ? $fecha_pago->format('d/m/Y') : \Carbon\Carbon::parse($fecha_pago)->format('d/m/Y')) : 'N/D' }}</strong> ({{ $medio_cobro['origen'] }})
                    </td>
                </tr>
                <tr>
                    <td style="width: 50%; padding-bottom: 2px;">
                        <span style="color: #64748b; font-weight: bold; text-transform: uppercase;">3. Fecha Cancelación:</span>
                        <strong style="color: #b91c1c;">
                            {{ isset($fecha_cancelacion) && $fecha_cancelacion ? \Carbon\Carbon::parse($fecha_cancelacion)->format('d/m/Y H:i') : 'Registrado' }} hs
                        </strong>
                    </td>
                    <td style="width: 50%; padding-bottom: 2px;">
                        <span style="color: #64748b; font-weight: bold; text-transform: uppercase;">4. Cancelado por:</span>
                        <strong style="color: #0f172a;">{{ $cancelado_por ?? 'Administración' }}</strong>
                    </td>
                </tr>
                @if(!empty($motivo_cancelacion))
                <tr>
                    <td colspan="2" style="padding-top: 3px; border-top: 1px dashed #cbd5e1;">
                        <span style="color: #64748b; font-weight: bold; text-transform: uppercase;">Motivo de cancelación:</span>
                        <em>{{ $motivo_cancelacion }}</em>
                    </td>
                </tr>
                @endif
                @if(!empty($observaciones))
                <tr>
                    <td colspan="2" style="padding-top: 2px;">
                        <span style="color: #64748b; font-weight: bold; text-transform: uppercase;">Observaciones del cobro:</span>
                        <em>{{ $observaciones }}</em>
                    </td>
                </tr>
                @endif
            </table>
        </div>
    </div>
    @elseif(!empty($observaciones))
    <div class="recibo-section">
        <div class="recibo-obs-box">
            <div class="recibo-obs-title">Observaciones</div>
            <div>{{ $observaciones }}</div>
        </div>
    </div>
    @endif

    <table class="recibo-footer-table" style="margin-top: {{ ($anulado ?? false) ? '12px' : '18px' }};">
        <tr>
            <td style="width: 60%;" class="recibo-footer-legal">
                <strong>Club Wings</strong> — {{ ($anulado ?? false) ? 'Comprobante anulado emitido por el sistema de gestión.' : 'Comprobante emitido por el sistema de gestión.' }}<br>
                <em>{{ ($anulado ?? false) ? 'Documento no válido como factura comercial ni constancia de pago.' : 'Documento no válido como factura comercial.' }}</em>
            </td>
            <td style="width: 40%;">
                <div class="recibo-firma-container">
                    <div class="recibo-firma-line"></div>
                    <div class="recibo-firma-label">{{ ($anulado ?? false) ? 'Firma / Control Anulación' : 'Firma / Sello Receptor' }}</div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
