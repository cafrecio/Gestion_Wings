<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;

trait GuardaHistorialAtomico
{
    public function save(array $options = [])
    {
        return DB::connection($this->getConnectionName())->transaction(fn () => parent::save($options));
    }

    public function delete()
    {
        return DB::connection($this->getConnectionName())->transaction(fn () => parent::delete());
    }
}
