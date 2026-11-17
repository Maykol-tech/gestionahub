<?php

namespace App\Http\Controllers;

use App\Models\CrmCliente;
use App\Models\FidelizacionMovimiento;
use App\Models\Recompensa;
use App\Models\Tenant;
use App\Support\DisenoNegocio;
use App\Support\Fidelizacion;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Tarjeta digital del cliente: una página pública (con su código secreto)
 * donde ve sus puntos, su nivel y qué puede canjear. Tiene un QR que el
 * negocio escanea en caja para ubicarlo.
 */
class TarjetaClienteController extends Controller
{
    public function ver(string $token)
    {
        $cliente = CrmCliente::withoutGlobalScopes()->where('tarjeta_token', $token)->where('activo', true)->firstOrFail();
        $negocio = Tenant::findOrFail($cliente->tenant_id);
        $diseno = DisenoNegocio::de($negocio);
        $programa = Fidelizacion::programa((int) $negocio->id);
        $niveles = Fidelizacion::nivelesDe((int) $negocio->id);
        $historicos = (int) $cliente->puntos_historicos;
        $saldo = (int) $cliente->puntos_saldo;
        $claveNivel = Fidelizacion::nivelDe($historicos, $niveles);

        // Siguiente nivel y cuánto falta.
        $claves = array_keys($niveles);
        $idx = array_search($claveNivel, $claves, true);
        $siguiente = $claves[$idx + 1] ?? null;
        $progreso = $siguiente
            ? (int) min(100, round(($historicos - $niveles[$claveNivel]['desde']) * 100 / max(1, $niveles[$siguiente]['desde'] - $niveles[$claveNivel]['desde'])))
            : 100;

        $recompensas = Recompensa::withoutGlobalScopes()->where('tenant_id', $negocio->id)->where('activo', true)
            ->orderBy('puntos_requeridos')->limit(8)->get(['nombre', 'puntos_requeridos', 'descuento_pct']);

        $movimientos = FidelizacionMovimiento::withoutGlobalScopes()->where('tenant_id', $negocio->id)
            ->where('cliente_id', $cliente->id)->latest('id')->limit(6)->get(['puntos', 'descripcion', 'tipo', 'created_at']);

        $url = route('tarjeta.cliente', $token);
        $qr = preg_replace('/^<\?xml[^>]*>\s*/', '', (string) QrCode::format('svg')->size(180)->margin(0)->errorCorrection('M')->generate($url));

        return response()->view('publico.tarjeta_cliente', [
            'negocio' => $negocio,
            'diseno' => $diseno,
            'color' => $diseno['color_primario'] ?? '#2563EB',
            'cliente' => $cliente,
            'saldo' => $saldo,
            'soles' => round($saldo * $programa['valor_punto'], 2),
            'programa' => $programa,
            'nivel' => $niveles[$claveNivel],
            'siguiente' => $siguiente ? $niveles[$siguiente] + ['faltan' => max(0, $niveles[$siguiente]['desde'] - $historicos)] : null,
            'progreso' => $progreso,
            'recompensas' => $recompensas,
            'movimientos' => $movimientos,
            'qr' => $qr,
            'codigo' => strtoupper(substr($token, 0, 4) . '-' . substr($token, 4, 4)),
        ])->header('X-Robots-Tag', 'noindex');
    }
}
