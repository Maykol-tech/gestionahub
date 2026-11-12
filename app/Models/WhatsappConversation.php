<?php
namespace App\Models;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class WhatsappConversation extends Model {
    protected $guarded = [];
    protected $casts = ['context' => 'array'];
    protected static function booted() { static::addGlobalScope(new TenantScope()); }
}