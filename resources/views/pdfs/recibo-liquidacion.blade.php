<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Liquidación {{ $numero_recibo }}</title>
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
        .recibo-settlement-table {
            width: 100%;
            border-collapse: collapse;
        }
        .recibo-periodo-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 7px 10px;
        }
        .recibo-periodo-label {
            font-size: 7px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .recibo-periodo-value {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }
        .recibo-periodo-badge {
            display: inline-block;
            background-color: #e2e8f0;
            color: #334155;
            font-size: 7.5px;
            font-weight: bold;
            padding: 1px 5px;
            border-radius: 3px;
            margin-top: 3px;
        }
        .recibo-monto-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 7px 10px;
            text-align: center;
        }
        .recibo-monto-label {
            font-size: 7px;
            color: #475569;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .recibo-monto-value {
            font-size: 17px;
            font-weight: bold;
            color: #0f172a;
            font-family: 'DejaVu Sans Mono', monospace;
        }
        .recibo-obs-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 5px 8px;
            font-size: 8px;
            color: #334155;
        }
        .recibo-obs-title {
            font-size: 7px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 2px;
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
            font-size: 8px;
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
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    <!-- ============================================== -->
    <!-- HOJA 1: RESUMEN EJECUTIVO DE HABERES           -->
    <!-- ============================================== -->
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
                            <div class="recibo-badge-doc">Documento Interno · Haberes</div>
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
                </table>
            </td>
        </tr>
        <tr><td colspan="2" class="recibo-header-divider-td">&nbsp;</td></tr>
    </table>

    <div class="recibo-section">
        <div class="recibo-section-title">Datos del Profesor / Instructor</div>
        <table class="recibo-card-table">
            <tr>
                <td style="width: 45%;">
                    <span class="recibo-card-label">Profesor / Beneficiario</span>
                    <span class="recibo-card-value">{{ $profesor['nombre'] }}</span>
                </td>
                <td style="width: 25%;">
                    <span class="recibo-card-label">Deporte / Rama</span>
                    <span class="recibo-card-value">{{ $profesor['deporte'] }}</span>
                </td>
                <td style="width: 30%;">
                    <span class="recibo-card-label">Modalidad Liquidación</span>
                    <span class="recibo-card-value">{{ $profesor['modalidad_texto'] }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="recibo-section">
        <div class="recibo-section-title">Resumen de Haberes del Período</div>
        <table class="recibo-settlement-table">
            <tr>
                <td style="width: 42%; padding-right: 8px;">
                    <div class="recibo-periodo-box">
                        <div class="recibo-periodo-label">Período Liquidado</div>
                        <div class="recibo-periodo-value">{{ $periodo['nombre'] }}</div>
                        <div class="recibo-periodo-badge">{{ $periodo['conteo_texto'] }}</div>
                    </div>
                </td>
                <td style="width: 58%;">
                    <div class="recibo-monto-box">
                        <div class="recibo-monto-label">Total Neto Liquidado</div>
                        <div class="recibo-monto-value">$ {{ number_format($monto_total, 2, ',', '.') }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="recibo-section">
        <div class="recibo-section-title">Imputación y Medio de Pago</div>
        <table class="recibo-card-table">
            <tr>
                <td style="width: 45%;">
                    <span class="recibo-card-label">Medio de Pago</span>
                    <span class="recibo-card-value">{{ $medio_pago['tipo_caja'] }}</span>
                </td>
                <td style="width: 55%;">
                    <span class="recibo-card-label">Subrubro Contable</span>
                    <span class="recibo-card-value">{{ $medio_pago['subrubro'] }}</span>
                </td>
            </tr>
        </table>
    </div>

    @if(!empty($observaciones))
    <div class="recibo-section">
        <div class="recibo-obs-box">
            <div class="recibo-obs-title">Observaciones</div>
            <div>{{ $observaciones }}</div>
        </div>
    </div>
    @endif

    <table class="recibo-footer-table" style="margin-top: 28px;">
        <tr>
            <td style="width: 55%;" class="recibo-footer-legal">
                <strong>Club Wings</strong> — Comprobante interno de haberes / servicios.<br>
                <em>Ver detalle discriminado en el Anexo adjunto (Página 2).</em>
            </td>
            <td style="width: 45%;">
                <div class="recibo-firma-container">
                    <div class="recibo-firma-line"></div>
                    <div class="recibo-firma-label">Firma de Conformidad del Profesor</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- ============================================== -->
    <!-- HOJA 2: ANEXO DE CLASES / COMISIONES           -->
    <!-- ============================================== -->
    <div class="page-break"></div>

    <table class="recibo-header-table" style="margin-bottom: 6px;">
        <tr>
            <td>
                <div class="recibo-brand-title" style="font-size: 14px;">
                    WINGS CLUB 
                    <span style="background:#334155; color:#fff; font-size:7.5px; padding:2px 5px; border-radius:3px;">
                        {{ $modalidad === 'HORA' ? 'Anexo Clases Dictadas' : 'Anexo Alumnos Comisionados' }} · {{ $numero_recibo }}
                    </span>
                </div>
                <div style="font-size: 8px; color: #64748b; margin-top: 2px;">
                    Profesor: <strong>{{ $profesor['nombre'] }}</strong> · Período: <strong>{{ $periodo['nombre'] }}</strong>
                </div>
            </td>
            <td style="text-align: right; vertical-align: top;">
                <span style="font-size: 7.5px; color: #94a3b8;">Página 2</span>
            </td>
        </tr>
        <tr><td colspan="2" class="recibo-header-divider-td" style="height: 4px;">&nbsp;</td></tr>
    </table>

    @if($modalidad === 'HORA')
    <!-- DETALLE POR HORA -->
    <div class="recibo-section">
        <div class="recibo-section-title">Detalle Cronológico de Clases Validadas</div>
        <table class="recibo-items-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Fecha</th>
                    <th style="width: 35%;">Grupo / Horario</th>
                    <th style="width: 14%; text-align: center;">Horas</th>
                    <th style="width: 16%; text-align: center;">Estado</th>
                    <th style="width: 20%; text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($detalles as $det)
                    <tr>
                        <td>{{ $det['fecha'] }}</td>
                        <td>{{ $det['grupo'] }}</td>
                        <td style="text-align: center;">{{ number_format($det['horas'], 1) }} hs</td>
                        <td style="text-align: center;">{{ $det['estado'] }}</td>
                        <td class="monto">$ {{ number_format($det['subtotal'], 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align: center; color: #94a3b8;">Sin clases registradas en el período.</td></tr>
                @endforelse
                <tr class="recibo-total-row">
                    <td colspan="4" class="recibo-total-label">Total Clases Computadas</td>
                    <td class="recibo-total-monto">$ {{ number_format($monto_total, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @else
    <!-- DETALLE POR COMISIÓN -->
    <div class="recibo-section">
        <div class="recibo-section-title">Alumnos del Deporte con Cobro Verificado en el Período</div>
        <table class="recibo-items-table">
            <thead>
                <tr>
                    <th>Alumno / Alumna</th>
                    <th style="width: 25%;">Cuota Cobrada</th>
                    <th style="width: 18%; text-align: center;">% Com.</th>
                    <th style="width: 24%; text-align: right;">Comisión</th>
                </tr>
            </thead>
            <tbody>
                @forelse($detalles as $det)
                    <tr>
                        <td>{{ $det['alumno'] }}</td>
                        <td>$ {{ number_format($det['cuota_base'], 2, ',', '.') }}</td>
                        <td style="text-align: center;">{{ $det['porcentaje'] }}%</td>
                        <td class="monto">$ {{ number_format($det['subtotal'], 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align: center; color: #94a3b8;">Sin alumnos comisionados en el período.</td></tr>
                @endforelse
                <tr class="recibo-total-row">
                    <td colspan="3" class="recibo-total-label">Total Comisión Liquidada</td>
                    <td class="recibo-total-monto">$ {{ number_format($monto_total, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <table class="recibo-footer-table" style="margin-top: 28px;">
        <tr>
            <td style="width: 60%;" class="recibo-footer-legal">
                Comprobante interno emitido por el sistema de gestión deportiva Wings.<br>
                <em>Documento no válido como factura fiscal.</em>
            </td>
            <td style="width: 40%;">
                <div class="recibo-firma-container">
                    <div class="recibo-firma-line"></div>
                    <div class="recibo-firma-label">Firma / Control Administración</div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
