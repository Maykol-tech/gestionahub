<?php

namespace Database\Seeders;

use App\Models\Repartidor;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles del panel y usuarios de demostracion (uno por rol).
 * Usuarios antiguos sin rol pasan a ser administradores de su negocio.
 * Se puede ejecutar varias veces: no duplica nada.
 *
 *   php artisan db:seed --class=RolesDemoSeeder --force
 */
class RolesDemoSeeder extends Seeder
{
    /** [correo, nombre, clave, rol] */
    public const USUARIOS = [
        ['vendedor@gestionahub.pe', 'Vendedor Demo', 'Vendedor2026!', 'vendedor'],
        ['cajero@gestionahub.pe', 'Cajero Demo', 'Cajero2026!', 'cajero'],
        ['repartidor@gestionahub.pe', 'Repartidor Demo', 'Repartidor2026!', 'repartidor'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['admin', 'vendedor', 'cajero', 'repartidor'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }

        $tenantId = (int) (Tenant::query()->orderBy('id')->value('id') ?? 1);

        // Usuarios antiguos sin ningun rol: quedan como administradores de su negocio
        User::query()->doesntHave('roles')->get()->each(fn (User $u) => $u->assignRole('admin'));

        foreach (self::USUARIOS as [$correo, $nombre, $clave, $rol]) {
            $usuario = User::where('email', $correo)->first() ?? new User(['email' => $correo]);
            $usuario->fill(['name' => $nombre, 'tenant_id' => $tenantId]);
            if (! $usuario->exists) {
                $usuario->password = $clave; // la clave demo solo se pone al crear el usuario
            }
            $usuario->email_verified_at ??= now();
            $usuario->save();
            $usuario->syncRoles([$rol]);

            if ($rol === 'repartidor') {
                Repartidor::withoutGlobalScopes()->updateOrCreate(
                    ['tenant_id' => $tenantId, 'user_id' => $usuario->id],
                    ['nombre' => $nombre, 'telefono' => '900000001', 'vehiculo' => 'moto', 'activo' => true, 'disponible' => true]
                );
            }
        }

        $this->command?->info('Demo: vendedor@gestionahub.pe / Vendedor2026!  ·  cajero@gestionahub.pe / Cajero2026!  ·  repartidor@gestionahub.pe / Repartidor2026!');
    }
}