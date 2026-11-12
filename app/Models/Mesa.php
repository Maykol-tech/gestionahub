<?php
namespace App\Models;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Mesa extends Model {
    protected $fillable = ['tenant_id', 'nombre', 'zona', 'capacidad', 'token', 'estado'];
    
    protected static function booted() {
        static::addGlobalScope(new TenantScope());
        static::creating(function ($mesa) {
            if (empty($mesa->token)) {
                $mesa->token = Str::random(10);
            }
        });
    }
}