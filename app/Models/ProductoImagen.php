<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductoImagen extends Model
{
    use HasFactory;

    protected $table = 'producto_imagenes';

    protected $fillable = [
        'tenant_id', 'producto_id', 'variante_id', 'ruta', 'alt', 'orden', 'es_principal',
    ];

    protected $casts = [
        'orden'        => 'integer',
        'es_principal' => 'boolean',
    ];

    protected $appends = ['url'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);

        // Al borrar el registro, borra tambien el archivo fisico
        static::deleted(function (ProductoImagen $imagen) {
            Storage::disk('public')->delete($imagen->ruta);
        });
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class, 'variante_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->ruta);
    }
}
