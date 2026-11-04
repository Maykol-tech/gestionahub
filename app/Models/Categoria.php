<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\TenantScope;

class Categoria extends Model
{
    use HasFactory;
    protected $table = 'categorias';
    protected $fillable = ['tenant_id', 'nombre', 'slug', 'imagen_url', 'orden', 'activo'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}