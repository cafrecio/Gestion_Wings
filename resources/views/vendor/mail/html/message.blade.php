{{-- Plantilla de los correos de Wings (A44). Reemplaza la de Laravel, que firmaba con
     el nombre de la aplicacion: si APP_NAME quedaba en "Laravel", al duenio del club le
     llegaba el aviso con el logo y el pie de Laravel. El nombre va escrito aca para que
     no dependa de la configuracion de cada servidor. --}}
<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
Wings
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
Aviso automático de Wings. No hace falta responder este correo.
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
