<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Models\ReglaPrimerPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConfiguracionWebController extends Controller
{
    private const CAMPOS = [
        'inscripcion_importe' => [
            'titulo' => 'Inscripción al club', 'etiqueta' => 'Importe de la inscripción ($)',
            'descripcion' => 'Se cobra una sola vez por persona. Cambiar este importe afecta las inscripciones nuevas; las deudas ya creadas conservan su precio.',
            'ayuda' => 'Ingresá un importe mayor que cero, con hasta dos decimales.',
            'tipo' => 'number', 'min' => '0.01', 'max' => '99999999.99', 'step' => '0.01', 'obligatorio' => true,
        ],
        'dias_gracia_cobranza' => [
            'titulo' => 'Días de gracia para pagar', 'etiqueta' => 'Último día de gracia del mes',
            'descripcion' => 'Hasta este día del mes, el que no pagó todavía no es moroso.',
            'ayuda' => 'Elegí un día del 1 al 28. Con 10, una cuota de este mes pasa a morosa el día 11. Si debe meses anteriores, sigue siendo deudor.',
            'tipo' => 'number', 'min' => '1', 'max' => '28', 'step' => '1', 'obligatorio' => true,
        ],
        'avisos_email' => [
            'titulo' => 'Correo para los avisos del club', 'etiqueta' => 'Correo electrónico',
            'descripcion' => 'Acá llegan los pendientes y las situaciones que necesitan atención. Si queda vacío, se usan los correos de los administradores.',
            'ayuda' => 'Ingresá una dirección completa o dejá el campo vacío.',
            'tipo' => 'email', 'placeholder' => 'Ejemplo: administracion@club.com',
        ],
        'avisos_telegram_chat_id' => [
            'titulo' => 'Telegram para los avisos del club', 'etiqueta' => 'Número del chat de Telegram',
            'descripcion' => 'También permite recibir los avisos por Telegram. Dejarlo vacío conserva el chat de respaldo del club, si está configurado.',
            'ayuda' => 'Primero escribile al bot del club. Usá el número del chat que haya sido verificado; no ingreses tu teléfono.',
            'tipo' => 'text', 'placeholder' => 'Número del chat, no del teléfono',
        ],
    ];

    public function index()
    {
        $configuraciones  = Configuracion::whereIn('clave', array_keys(self::CAMPOS))->get()->keyBy('clave');
        $reglasPrimerPago = ReglaPrimerPago::orderBy('dia_desde')->get();
        $campos = self::CAMPOS;

        return view('configuraciones.index', compact('configuraciones', 'reglasPrimerPago', 'campos'));
    }

    public function update(Request $request, string $clave)
    {
        $config = Configuracion::where('clave', $clave)->firstOrFail();

        $rules = match ($clave) {
            'inscripcion_importe' => 'required|numeric|min:0.01|max:99999999.99|decimal:0,2',
            'dias_gracia_cobranza' => 'required|integer|min:1|max:28',
            'avisos_email' => 'nullable|string|email|max:255',
            'avisos_telegram_chat_id' => 'nullable|string|regex:/^-?[1-9][0-9]*$/|max:255',
            default => [fn ($attribute, $value, $fail) => $fail('Este valor es fijo y no se cambia desde Configuración.')],
        };
        // La generación del día 1 está fijada en el scheduler. No aceptar tampoco
        // una petición directa que aparente modificarla, incluso sin valor.
        if (!array_key_exists($clave, self::CAMPOS)) {
            $rules = ['required', fn ($attribute, $value, $fail) => $fail('Este valor es fijo y no se cambia desde Configuración.')];
        }
        $validator = Validator::make($request->all(), ['valor' => $rules], [
            'valor.required' => 'Completá este valor antes de guardar.',
            'valor.numeric' => 'Ingresá un importe válido, con hasta dos decimales.',
            'valor.decimal' => 'Ingresá un importe con hasta dos decimales.',
            'valor.integer' => 'Elegí un día entero del 1 al 28.',
            'valor.min' => $clave === 'inscripcion_importe'
                ? 'El importe de la inscripción debe ser mayor que cero.' : 'Elegí un día del 1 al 28.',
            'valor.max' => match ($clave) {
                'inscripcion_importe' => 'El importe no puede superar $99.999.999,99.',
                'dias_gracia_cobranza' => 'Elegí un día del 1 al 28.',
                default => 'Este valor no puede tener más de 255 caracteres.',
            },
            'valor.email' => 'Ingresá un correo válido, por ejemplo administracion@club.com.',
            'valor.string' => 'Ingresá este dato como texto.',
            'valor.regex' => 'Ingresá el número del chat de Telegram verificado, sin espacios ni letras.',
        ]);
        if ($validator->fails() && !$request->expectsJson()) {
            $request->session()->flash('configuracion_error_clave', $clave);
        }
        $validated = $validator->validate();

        $config->update(['valor' => (string) ($validated['valor'] ?? '')]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'valor' => $config->valor]);
        }

        return redirect()->route('web.configuraciones.index')
            ->with('success', 'Configuración guardada.');
    }
}
