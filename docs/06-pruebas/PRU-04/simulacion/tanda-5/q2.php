<?php
// Las tarjetas que Codex tiene que leer el 26/11 en Cashflow e Historial (48.9), sobre la foto del día 48.
require __DIR__.'/lib.php';
$ad = U('Admin Prueba');
$todas = function (string $u) use ($ad) { [$st,, $h] = http($ad, $u); preg_match_all('/ds-money--total[^>]*>\s*(.*?)\s*<\//s', $h, $m); return $st.' '.implode(' · ', array_map(fn ($x) => trim(strip_tags($x)), $m[1])); };
echo 'Cashflow, semana, sin filtro: '.$todas('/cashflow?periodo=semana&fecha=2026-11-26')."\n";
echo 'Cashflow, semana, Galicia: '.$todas('/cashflow?periodo=semana&fecha=2026-11-26&tipo_caja_id='.M('Banco Galicia'))."\n";
[$st,, $h] = http($ad, '/caja/historial?desde=2026-11-23&hasta=2026-11-26');
echo "Historial 23 al 26/11 ($st): ".textoDe($h, 'Ingresos', 200)."\n";
