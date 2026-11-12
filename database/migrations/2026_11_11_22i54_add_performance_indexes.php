<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        $pairs = [
            ['pedidos', 'tenant_id'],
            ['pedidos', 'estado'],
            ['pedidos', 'created_at'],
            ['crm_clientes', 'tenant_id'],
            ['productos', 'tenant_id'],
            ['productos', 'activo'],
            ['ventas_pos', 'tenant_id'],
            ['ventas_pos', 'created_at'],
            ['repartidores', 'tenant_id'],
            ['sucursales', 'tenant_id'],
        ];
        foreach ($pairs as [$tabla, $col]) {
            try {
                DB::statement("ALTER TABLE `$tabla` ADD INDEX `idx_{$tabla}_{$col}` (`$col`)");
            } catch (\Throwable $e) { /* ya existe */ }
        }
    }
    public function down(): void {}
};