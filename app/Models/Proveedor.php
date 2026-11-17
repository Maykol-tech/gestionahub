<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\TenantScope;

class Proveedor extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'tenant_id', 'nombre', 'ruc', 'contacto', 'telefono', 'email', 'notas', 'activo', 'direccion', 'condicion_pago'
    ];

    public const CONDICIONES = ['contado' => 'Contado', 'credito_15' => 'Crédito a 15 días', 'credito_30' => 'Crédito a 30 días', 'credito_60' => 'Crédito a 60 días'];

    public function diasCredito(): int
    {
        return (int) (preg_match('/credito_(\d+)/', (string) $this->condicion_pago, $m) ? $m[1] : 0);
    }

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}