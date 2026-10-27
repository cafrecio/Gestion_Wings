<?php

namespace App\Exceptions;

final class ExcepcionCobranzaRequerida extends \DomainException
{
    public function __construct(public readonly array $detalle)
    {
        parent::__construct($detalle['message']);
    }
}
