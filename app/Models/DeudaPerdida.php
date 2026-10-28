<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeudaPerdida extends Model
{
    protected $table = 'deudas_perdidas';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['detalle' => 'array', 'importe' => 'decimal:2', 'created_at' => 'datetime'];

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
}
