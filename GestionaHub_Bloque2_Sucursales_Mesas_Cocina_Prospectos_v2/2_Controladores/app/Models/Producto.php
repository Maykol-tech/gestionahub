<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'tenant_id', 'categoria_id', 'nombre', 'slug', 'descripcion', 'precio', 'precio_oferta',
        'stock', 'stock_minimo', 'sku', 'imagen_url', 'tiene_variantes', 'activo',
    ];

    protected $casts = [
        'precio'          => 'decimal:2',
        'precio_oferta'   => 'decimal:2',
        'stock'           => 'integer',
        'stock_minimo'    => 'integer',
        'tiene_variantes' => 'boolean',
        'activo'          => 'boolean',
    ];

    protected $appends = ['stock_bajo'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);

        // El slug es obligatorio en la tabla: si el alta no lo trae, se genera a partir del nombre
        static::creating(function (Producto $producto) {
            if (blank($producto->slug)) {
                $producto->slug = \Illuminate\Support\Str::slug((string) $producto->nombre) . '-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(5));
            }
        });
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function variantes()
    {
        return $this->hasMany(ProductoVariante::class, 'producto_id')->chaperone('producto')->orderBy('orden');
    }

    public function imagenes()
    {
        return $this->hasMany(ProductoImagen::class, 'producto_id')->orderBy('orden');
    }

    public function imagenPrincipal()
    {
        return $this->hasOne(ProductoImagen::class, 'producto_id')->where('es_principal', true);
    }

    /** Recalcula el stock total desde las variantes (si las tiene). */
    public function recalcularStock(): void
    {
        if ($this->tiene_variantes) {
            $this->stock = (int) $this->variantes()->where('activo', true)->sum('stock');
            $this->saveQuietly();
        }
    }

    /** Pone como principal la primera imagen si ninguna lo es y sincroniza imagen_url. */
    public function sincronizarImagenPrincipal(): void
    {
        $principal = $this->imagenes()->where('es_principal', true)->first();

        if (! $principal) {
            $principal = $this->imagenes()->first();
            $principal?->update(['es_principal' => true]);
        }

        $this->imagen_url = $principal?->url;
        $this->saveQuietly();
    }

    public function getStockBajoAttribute(): bool
    {
        return $this->stock <= $this->stock_minimo;
    }

    public function scopeStockBajo($query)
    {
        return $query->whereColumn('stock', '<=', 'stock_minimo');
    }
}
