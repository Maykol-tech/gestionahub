<?php
namespace App\Models;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
class ZonaDelivery extends Model {
    protected $guarded = [];
    protected static function booted() { static::addGlobalScope(new TenantScope()); }
}