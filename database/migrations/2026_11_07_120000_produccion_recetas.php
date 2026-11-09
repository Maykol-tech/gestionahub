<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Produccion: insumos con stock y costo, recetas con sus insumos, y lotes ligados a una receta. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('insumos')) {
            Schema::create('insumos', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->string('nombre', 120);
                $t->string('unidad', 20)->default('und');
                $t->decimal('stock', 12, 3)->default(0);
                $t->decimal('stock_minimo', 12, 3)->default(0);
                $t->decimal('costo_unitario', 12, 4)->default(0);
                $t->boolean('activo')->default(true);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('recetas')) {
            Schema::create('recetas', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->string('nombre', 120);
                $t->unsignedInteger('rendimiento')->default(1);
                $t->text('notas')->nullable();
                $t->boolean('activo')->default(true);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('receta_insumos')) {
            Schema::create('receta_insumos', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->foreignId('receta_id')->constrained('recetas')->cascadeOnDelete();
                $t->foreignId('insumo_id')->constrained('insumos')->cascadeOnDelete();
                $t->decimal('cantidad', 12, 3);
            });
        }

        if (Schema::hasTable('ordenes_produccion')) {
            Schema::table('ordenes_produccion', function (Blueprint $t) {
                if (! Schema::hasColumn('ordenes_produccion', 'receta_id')) {
                    $t->foreignId('receta_id')->nullable()->after('titulo')->constrained('recetas')->nullOnDelete();
                }
                if (! Schema::hasColumn('ordenes_produccion', 'costo_total')) {
                    $t->decimal('costo_total', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('ordenes_produccion', 'descontado_at')) {
                    $t->timestamp('descontado_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ordenes_produccion')) {
            Schema::table('ordenes_produccion', function (Blueprint $t) {
                if (Schema::hasColumn('ordenes_produccion', 'receta_id')) {
                    $t->dropConstrainedForeignId('receta_id');
                }
                foreach (['costo_total', 'descontado_at'] as $columna) {
                    if (Schema::hasColumn('ordenes_produccion', $columna)) {
                        $t->dropColumn($columna);
                    }
                }
            });
        }

        Schema::dropIfExists('receta_insumos');
        Schema::dropIfExists('recetas');
        Schema::dropIfExists('insumos');
    }
};
