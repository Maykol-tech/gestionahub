<?php

namespace App\Support;

use App\Models\Repartidor;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Roles funcionales del panel (Spatie) y los modulos que puede ver cada uno.
 *  - admin: todo el panel.
 *  - vendedor: pedidos, productos, clientes y CRM basico.
 *  - cajero: caja, cobros y reportes del dia.
 *  - repartidor: no entra al panel; trabaja desde su app privada /r/{token}.
 */
class Roles
{
    /** Orden de prioridad si un usuario tiene varios roles. */
    public const PRIORIDAD = ['superadmin', 'admin', 'vendedor', 'cajero', 'repartidor', 'delivery'];

    /** Modulos del panel permitidos por rol ('*' = todos). */
    public const MODULOS = [
        'superadmin' => ['*'],
        'admin' => ['*'],
        'vendedor' => ['dashboard', 'pedidos', 'productos', 'clientes', 'fidelizacion', 'servicios', 'tienda', 'prospectar', 'pos'],
        'cajero' => ['dashboard', 'pedidos', 'caja', 'cobros', 'reportes', 'pos', 'comprobantes'],
        'repartidor' => [],
        'delivery' => [],
    ];

    /** Segmentos de URL que pertenecen a otro modulo. */
    private const ALIAS = [
        'categorias' => 'productos',
        'repartidores' => 'repartos',
        'zonas' => 'repartos',
    ];

    /** Rol principal del usuario (o null si no tiene ninguno). */
    public static function rol(?User $usuario): ?string
    {
        if (! $usuario) {
            return null;
        }
        foreach (self::PRIORIDAD as $rol) {
            if ($usuario->hasRole($rol)) {
                return $rol;
            }
        }

        return null;
    }

    /** Indica si alguno de los roles del usuario puede abrir el modulo. */
    public static function puede(?User $usuario, string $modulo): bool
    {
        if (! $usuario) {
            return false;
        }
        foreach (self::PRIORIDAD as $rol) {
            if (! $usuario->hasRole($rol)) {
                continue;
            }
            $permitidos = self::MODULOS[$rol] ?? [];
            if (in_array('*', $permitidos, true) || in_array($modulo, $permitidos, true)) {
                return true;
            }
        }

        return false;
    }

    /** Modulo al que pertenece una peticion del panel: /admin/{modulo}/... */
    public static function moduloDe(Request $request): string
    {
        $segmento = $request->segment(2) ?? 'dashboard';
        if ($segmento === 'm') {
            $segmento = $request->segment(3) ?? 'dashboard';
        }

        return self::ALIAS[$segmento] ?? $segmento;
    }

    /** Para el menu: $destino es un nombre de ruta (admin.pedidos) o un modulo (caja). */
    public static function ver(string $destino): bool
    {
        $modulo = str_starts_with($destino, 'admin.') ? substr($destino, 6) : $destino;

        return self::puede(auth()->user(), $modulo);
    }

    /** Pagina inicial de cada rol despues de entrar. Null si el repartidor no tiene app. */
    public static function inicio(?User $usuario): ?string
    {
        return match (self::rol($usuario)) {
            'vendedor' => route('admin.pedidos'),
            'cajero' => route('admin.modulo', 'caja'),
            'repartidor', 'delivery' => self::appRepartidor($usuario),
            default => route('admin.dashboard'),
        };
    }

    /** Enlace privado de la app del repartidor vinculado al usuario. */
    public static function appRepartidor(?User $usuario): ?string
    {
        if (! $usuario) {
            return null;
        }
        $repartidor = Repartidor::withoutGlobalScopes()->where('user_id', $usuario->id)->first();

        return $repartidor ? route('repartidor.app', $repartidor->token) : null;
    }
}