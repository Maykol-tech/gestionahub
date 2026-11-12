<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('repartidores')) {
            Schema::create('repartidores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('nombre_completo');
                $table->string('tipo_documento');
                $table->string('numero_documento');
                $table->date('fecha_nacimiento');
                $table->string('foto_perfil')->nullable();
                $table->string('telefono');
                $table->string('email')->unique();
                $table->string('direccion')->nullable();
                $table->string('tipo_vehiculo');
                $table->string('placa')->nullable();
                $table->string('capacidad_carga')->nullable();
                $table->date('licencia_vencimiento')->nullable();
                $table->string('soat_archivo')->nullable();
                $table->string('antecedentes_archivo')->nullable();
                $table->string('banco')->nullable();
                $table->string('tipo_cuenta')->nullable();
                $table->string('numero_cuenta')->nullable();
                $table->string('cci')->nullable();
                $table->timestamps();
            });
        } else {
            // Si ya existe, agregar las columnas nuevas de forma segura
            Schema::table('repartidores', function (Blueprint $table) {
                if (!Schema::hasColumn('repartidores', 'nombre_completo')) $table->string('nombre_completo')->nullable();
                if (!Schema::hasColumn('repartidores', 'tipo_documento')) $table->string('tipo_documento')->nullable();
                if (!Schema::hasColumn('repartidores', 'numero_documento')) $table->string('numero_documento')->nullable();
                if (!Schema::hasColumn('repartidores', 'fecha_nacimiento')) $table->date('fecha_nacimiento')->nullable();
                if (!Schema::hasColumn('repartidores', 'foto_perfil')) $table->string('foto_perfil')->nullable();
                if (!Schema::hasColumn('repartidores', 'tipo_vehiculo')) $table->string('tipo_vehiculo')->nullable();
                if (!Schema::hasColumn('repartidores', 'placa')) $table->string('placa')->nullable();
                if (!Schema::hasColumn('repartidores', 'capacidad_carga')) $table->string('capacidad_carga')->nullable();
                if (!Schema::hasColumn('repartidores', 'licencia_vencimiento')) $table->date('licencia_vencimiento')->nullable();
                if (!Schema::hasColumn('repartidores', 'soat_archivo')) $table->string('soat_archivo')->nullable();
                if (!Schema::hasColumn('repartidores', 'antecedentes_archivo')) $table->string('antecedentes_archivo')->nullable();
                if (!Schema::hasColumn('repartidores', 'banco')) $table->string('banco')->nullable();
                if (!Schema::hasColumn('repartidores', 'tipo_cuenta')) $table->string('tipo_cuenta')->nullable();
                if (!Schema::hasColumn('repartidores', 'numero_cuenta')) $table->string('numero_cuenta')->nullable();
                if (!Schema::hasColumn('repartidores', 'cci')) $table->string('cci')->nullable();
            });
        }
    }
};