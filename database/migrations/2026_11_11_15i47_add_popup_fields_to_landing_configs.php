<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('landing_configs')) {
            Schema::table('landing_configs', function (Blueprint $table) {
                if (!Schema::hasColumn('landing_configs', 'popup_activo')) {
                    $table->boolean('popup_activo')->default(true);
                }
                if (!Schema::hasColumn('landing_configs', 'popup_delay_seconds')) {
                    $table->integer('popup_delay_seconds')->default(30);
                }
                if (!Schema::hasColumn('landing_configs', 'popup_exit_intent')) {
                    $table->boolean('popup_exit_intent')->default(true);
                }
                if (!Schema::hasColumn('landing_configs', 'popup_frecuencia_dias')) {
                    $table->integer('popup_frecuencia_dias')->default(7);
                }
                if (!Schema::hasColumn('landing_configs', 'popup_cuenta_regresiva_min')) {
                    $table->integer('popup_cuenta_regresiva_min')->default(10);
                }
                if (!Schema::hasColumn('landing_configs', 'popup_badge')) {
                    $table->string('popup_badge')->default('Oferta por tiempo limitado');
                }
                if (!Schema::hasColumn('landing_configs', 'popup_titulo')) {
                    $table->string('popup_titulo')->default('Tu primer mes al 50% y configuración gratis');
                }
                if (!Schema::hasColumn('landing_configs', 'popup_texto')) {
                    $table->text('popup_texto')->nullable();
                }
                if (!Schema::hasColumn('landing_configs', 'popup_beneficios')) {
                    $table->json('popup_beneficios')->nullable();
                }
                if (!Schema::hasColumn('landing_configs', 'popup_cta')) {
                    $table->string('popup_cta')->default('Quiero mi beneficio');
                }
                if (!Schema::hasColumn('landing_configs', 'popup_oferta_codigo')) {
                    $table->string('popup_oferta_codigo')->default('POPUP50');
                }
            });
        }
    }
    public function down(): void {}
};