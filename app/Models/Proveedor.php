<?php
namespace App\Models;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Proveedor extends Model {
    use SoftDeletes;
    protected $guarded = [];
    protected static function booted() { static::addGlobalScope(new TenantScope()); }
}