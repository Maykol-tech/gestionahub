<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\TenantScope;

class Producto extends Model
{
    use HasFactory;
    protected $table = 'productos';
    protected $fillable = ['tenant_id', 'categoria_id', 'nombre', 'slug', 'descripcion', 'precio', 'precio_oferta', 'stock', 'stock_minimo', 'sku', 'imagen_url', 'tiene_variantes', 'activo'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}