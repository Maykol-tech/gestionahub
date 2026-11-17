<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\ListaEsperaCita;
use App\Models\Tenant;
use App\Support\DisenoNegocio;
use App\Support\ListaEspera;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** GEST-86: página pública donde la persona en espera toma el cupo liberado. */
class PublicEsperaController extends Controller
{
    private function datos(string $token): array
    {
        $oferta = DB::table('lista_espera_ofertas')->where('token', $token)->first();
        abort_if(! $oferta, 404);
        $cita = Cita::withoutGlobalScopes()->with('servicio')->findOrFail($oferta->cita_id);
        $espera = ListaEsperaCita::withoutGlobalScopes()->findOrFail($oferta->espera_id);
        $negocio = Tenant::findOrFail($oferta->tenant_id);

        $vigente = $oferta->estado === 'enviada' && now()->lessThan($oferta->expira_at) && ListaEspera::cupoLibre($cita);

        return compact('oferta', 'cita', 'espera', 'negocio', 'vigente');
    }

    public function mostrar(string $token)
    {
        $d = $this->datos($token);

        return view('publico.espera_cupo', $d + ['d' => DisenoNegocio::de($d['negocio']), 'tomada' => session('tomada')]);
    }

    public function tomar(Request $request, string $token)
    {
        $d = $this->datos($token);
        try {
            ListaEspera::tomar($d['oferta']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('publico.espera.cupo', $token)->with('tomada', true);
    }
}
