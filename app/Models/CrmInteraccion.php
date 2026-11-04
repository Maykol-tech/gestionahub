<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmInteraccion extends Model
{
    use HasFactory;

    protected $fillable = ['tenant_id', 'cliente_id', 'tipo', 'detalle'];

    public function cliente()
    {
        return $this->belongsTo(CrmCliente::class, 'cliente_id');
    }
}
