<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('whatsapp_conversations')) {
            Schema::create('whatsapp_conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('phone_number');
                $table->string('step');
                $table->json('context')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'phone_number']);
            });
        }
    }
    public function down(): void { Schema::dropIfExists('whatsapp_conversations'); }
};