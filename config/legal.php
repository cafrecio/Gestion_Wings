<?php

return [
    // Cambiar la versión hace que todos vuelvan a aceptar (términos, punto 8).
    'version' => '2026-11-01',
    'exigir' => (bool) env('LEGAL_EXIGIR', true),
    'prestador' => env('LEGAL_PRESTADOR', 'Gestionar-te'),
];
