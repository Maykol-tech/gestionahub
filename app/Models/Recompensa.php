<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\TenantScope;

class Recompensa extends Model
{
    use HasFactory;
    protected $table = 'recompensas';
    protected $fillable = ['tenant_id', 'nombre', 'puntos_requeridos', 'descuento_pct', 'activo'];

    protected static function booted() {
        static::addGlobalScope(new TenantScope);
    }
}