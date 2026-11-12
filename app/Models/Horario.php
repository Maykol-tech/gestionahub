<?php
namespace App\Models;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class Horario extends Model {
    protected $table = 'horarios';
    protected $guarded = [];
    protected static function booted() {
        static::addGlobalScope(new TenantScope());
    }
}