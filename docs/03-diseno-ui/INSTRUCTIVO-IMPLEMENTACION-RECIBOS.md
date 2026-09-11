# Instructivo de Implementación: Nuevos Recibos PDF (ENT-02)

> **Destinatarios:** Agentes de desarrollo (Codex / Claude Code).  
> **Área:** Facturación y Cobranzas (`resources/views/pdfs/`, `app/Services/ReciboService.php`).  
> **Estado del Diseño:** **Aprobado por Carlos y Vanina (11/09/2026)**.  
> **Archivos de Referencia Visual:**
> - [PREVIEW-RECIBOS.html](file:///c:/xampp/htdocs/Gestion_Wings/docs/03-diseno-ui/PREVIEW-RECIBOS.html) (Muestrario interactivo 4 pestañas)
> - [MUESTRA-RECIBO-CUOTA.pdf](file:///c:/xampp/htdocs/Gestion_Wings/docs/03-diseno-ui/MUESTRA-RECIBO-CUOTA.pdf) (1 página A5)
> - [MUESTRA-RECIBO-CUOTA-ANULADO.pdf](file:///c:/xampp/htdocs/Gestion_Wings/docs/03-diseno-ui/MUESTRA-RECIBO-CUOTA-ANULADO.pdf) (1 página A5)
> - [MUESTRA-RECIBO-LIQUIDACION-HORA.pdf](file:///c:/xampp/htdocs/Gestion_Wings/docs/03-diseno-ui/MUESTRA-RECIBO-LIQUIDACION-HORA.pdf) (2 páginas A5)
> - [MUESTRA-RECIBO-LIQUIDACION-COMISION.pdf](file:///c:/xampp/htdocs/Gestion_Wings/docs/03-diseno-ui/MUESTRA-RECIBO-LIQUIDACION-COMISION.pdf) (2 páginas A5)

---

## 1. Resumen Ejecutivo y Alcance

Este documento describe paso a paso cómo trasladar los nuevos diseños de comprobantes PDF aprobados al código productivo del sistema Wings, cumpliendo estrictamente con el motor **DomPDF (CSS 2.1)** y las reglas innegociables de [AGENTS.md](file:///c:/xampp/htdocs/Gestion_Wings/AGENTS.md).

### Qué cambia
1. **Recibo de Cobro de Cuotas** (`recibo-cuota.blade.php`):
   - Estética institucional sobria en azul pizarra oscuro (`#0F172A`) y gris pizarra (`#1E293B`, `#334155`).
   - Logo institucional dentro de un marco oscuro de alto contraste (`#0F172A`) para preservar el patinador blanco y los círculos rojos originales.
   - Formato A5 vertical (`148mm × 210mm`) de **1 página exacta**.
   - **Caso Anulado / Cancelado:** Todo el cuerpo mantiene la estética neutra/azul (incluyendo importes en negro/slate), y **únicamente** el encabezado de alerta superior se muestra en rojo (`#FEF2F2` / `#DC2626` / `#B91C1C`).
   - **Trazabilidad de Cancelación:** En la sección *Observaciones* se incorpora una grilla de auditoría con fecha de emisión, fecha de cobro original, fecha de cancelación, usuario que canceló, motivo y observaciones originales.
2. **Recibo de Liquidación Docente / Haberes** (`recibo-liquidacion.blade.php`):
   - Formato A5 vertical de **2 páginas exactas**:
     - **Página 1 (Resumen Ejecutivo):** Datos del profesor, período liquidado, horas o alumnos totales, total neto liquidado e imputación contable/caja, con firma de conformidad.
     - **Página 2 (Anexo Detallado):**
       - Para profesores por **Hora**: Detalle cronológico de cada clase dictada (fecha, horario/grupo, horas, estado de validación y subtotal).
       - Para profesores por **Comisión**: Detalle de alumnos del período con cobro verificado (alumno, cuota cobrada, % de comisión aplicado y subtotal).

---

## 2. Restricciones Técnicas Críticas (DomPDF)

1. **Sin Flexbox ni CSS Grid:** DomPDF soporta únicamente CSS 2.1. Todo el layout de dos columnas, tarjetas y alineaciones debe estructurarse mediante `<table>` con anchos porcentuales (`width: 58%`, `width: 42%`, etc.) y `border-collapse: collapse;`.
2. **Dimensiones y Márgenes de Página A5:**
   ```css
   @page {
       size: 148mm 210mm portrait;
       margin: 10mm 12mm 10mm 12mm;
   }
   ```
   *El alto imprimible útil es de 190mm. No superar este alto en la página 1 para evitar saltos indeseados.*
3. **Manejo de Saltos de Página (Liquidaciones):**
   ```css
   .page-break {
       page-break-after: always;
   }
   thead {
       display: table-header-group;
   }
   tr {
       page-break-inside: avoid;
   }
   ```
4. **Tipografías nativas de DomPDF:**
   - Texto principal, títulos y etiquetas: `'DejaVu Sans', Arial, sans-serif`
   - Importes, montos y códigos de comprobante: `'DejaVu Sans Mono', monospace`
5. **Logo:**
   - Usar `public_path('img/logo-wings.png')` dentro de `<img>` o incrustar en base64 si se renderiza en memoria.
   - El logo debe estar siempre contenido en el wrapper `.logo-frame`:
     ```html
     <div class="logo-frame">
         <img src="{{ public_path('img/logo-wings.png') }}" class="recibo-brand-logo" alt="Wings">
     </div>
     ```
   - Regla CSS asociada:
     ```css
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
     ```
6. **Prohibido el color Rojo en importes:**
   - La directiva explícita de Carlos: el color rojo es exclusivo para alertas o cancelaciones en el encabezado.
   - Todos los importes y totales van en color `#0F172A`.

---

## 3. Modificaciones en Backend (`ReciboService.php`)

### 3.1. En `generarReciboCuota(int $pagoId)`
Asegurar que el array `$data` que se envía a la vista `pdfs.recibo-cuota` contenga los siguientes campos (compatibles con pagos activos y anulados):

```php
// En app/Services/ReciboService.php
$esAnulado = ($pago->estado === Pago::ESTADO_ANULADO);

// Si existe detalle_anulacion (FIN-03) o se recupera del movimiento operativo:
$detalleAnulacion = $pago->detalle_anulacion ?? null;
$movimiento = $pago->movimientoOperativo ?? null; // o buscar por referencia

$data = [
    'numero_recibo' => "CUOTA-{$pagoId}",
    'fecha_emision' => Carbon::now(),
    'fecha_pago'    => $pago->fecha_pago,
    'anulado'       => $esAnulado,
    
    // Metadatos específicos de anulación
    'fecha_cancelacion'   => $detalleAnulacion['fecha'] ?? ($pago->updated_at ?? null),
    'cancelado_por'       => $detalleAnulacion['usuario'] ?? 'Administración',
    'motivo_cancelacion'  => $detalleAnulacion['motivo'] ?? 'Reversión de cobro registrada en sistema.',
    
    'alumno' => [
        'nombre'  => trim(($pago->alumno->nombre ?? '') . ' ' . ($pago->alumno->apellido ?? '')),
        'dni'     => $pago->alumno->dni ?? 'N/D',
        'deporte' => $pago->alumno->deporte->nombre ?? 'N/D',
    ],
    'periodos'     => $this->obtenerPeriodosImputados($pago),
    'monto_total'  => $pago->monto_final,
    'medio_cobro'  => [
        'tipo_caja' => $tipoCaja['nombre'] ?? 'N/D',
        'origen'    => $tipoCaja['origen'] ?? 'N/D',
    ],
    'observaciones' => $pago->observaciones,
];
```

### 3.2. En `generarReciboLiquidacion(int $liquidacionId)`
Asegurar que la liquidación cargue su relación de detalles:
```php
$liquidacion = Liquidacion::with(['profesor.deportes', 'detalles'])->findOrFail($liquidacionId);
```
Y pasar a `pdfs.recibo-liquidacion`:
- Modalidad (`HORA` o `COMISION`).
- Subrubro contable (`Sueldo - Apellido, Nombre`).
- Colección `$detalles` formateada para la tabla de la Página 2:
  - Si es por hora: `$detalle->fecha`, `$detalle->clase->nombre`, `$detalle->horas`, `$detalle->subtotal`.
  - Si es comisión: `$detalle->alumno->nombre_completo`, `$detalle->monto_cuota`, `$detalle->porcentaje_comision`, `$detalle->subtotal`.

---

## 4. Código Completo de Vistas Blade

### 4.1. Vista `resources/views/pdfs/recibo-cuota.blade.php`

```blade
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
                            <span class="recibo-meta-value">{{ $fecha_emision ? $fecha_emision->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: right; font-size: 8px; line-height: 12px;">
                            <span class="recibo-meta-label">Fecha Pago:</span>
                            <span class="recibo-meta-value">{{ $fecha_pago ? $fecha_pago->format('d/m/Y') : 'N/D' }}</span>
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
                        <strong>{{ $fecha_emision ? $fecha_emision->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }} hs</strong>
                    </td>
                    <td style="width: 50%; padding-bottom: 2px;">
                        <span style="color: #64748b; font-weight: bold; text-transform: uppercase;">2. Fecha de Pago:</span>
                        <strong>{{ $fecha_pago ? $fecha_pago->format('d/m/Y') : 'N/D' }}</strong> ({{ $medio_cobro['origen'] }})
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
```

---

### 4.2. Vista `resources/views/pdfs/recibo-liquidacion.blade.php`

```blade
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
                            <span class="recibo-meta-value">{{ $fecha_emision ? $fecha_emision->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: right; font-size: 8px; line-height: 12px;">
                            <span class="recibo-meta-label">Fecha Pago:</span>
                            <span class="recibo-meta-value">{{ $fecha_pago ? $fecha_pago->format('d/m/Y') : 'N/D' }}</span>
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
                <span style="font-size: 7.5px; color: #94a3b8;">Página 2 de 2</span>
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
```

---

## 5. Regla Git Obligatoria para Commits

El repositorio tiene activo un control en `.git/hooks/commit-msg` que rechaza cualquier cambio que toque archivos bajo `resources/views/` salvo que se declare expresamente la autorización.

Al commitear los cambios, agregar obligatoriamente la línea:

```text
feat(recibos): implementar rediseno de comprobantes pdf de cuotas y liquidaciones

- Aplica nueva estetica A5 aprobada para recibo-cuota y recibo-liquidacion
- Mantiene paleta sobria azul/negro sin rojos en importes
- Soporta auditoria de cancelaciones en cuotas y anexo de 2da pagina en haberes

Diseno-autorizado: Carlos y Vanina aprobaron el rediseno de recibos ENT-02
```

---

## 6. Procedimiento de Verificación y Cierre

Antes de dar la tarea por concluida, ejecutar la suite completa de controles:

```bash
# 1. Verificación de sintaxis de vistas Blade y servicios
php -l resources/views/pdfs/recibo-cuota.blade.php
php -l resources/views/pdfs/recibo-liquidacion.blade.php
php -l app/Services/ReciboService.php

# 2. Compilación de vistas
php artisan view:cache && php artisan view:clear

# 3. Pruebas automatizadas (no deben romper ninguna prueba existente)
php artisan test --filter=ReciboServiceTest
php artisan test --filter=CancelarCobroOperativoTest
php artisan test

# 4. Verificación de diseño en git diff (debe listar únicamente las vistas autorizadas)
git diff --stat -- resources/views
```

---

## 7. Cierre Documental

De acuerdo a `AGENTS.md §6d` ("Definición de terminado — qué documento acabo de dejar mintiendo"):

1. Actualizar `docs/00-estado/ESTADO-ACTUAL.md`:
   - Marcar la tarea **ENT-02** como **Completada / Verificada**.
2. Escribir en la bitácora del agente correspondiente:
   - Si implementa Codex: registrar entrada en `docs/00-estado/LOG-CODEX.md`.
   - Si implementa Claude: registrar entrada en `docs/00-estado/LOG-CLAUDE.md`.
