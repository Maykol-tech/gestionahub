<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListaEsperaController extends Controller
{
    public function pagina(Request $request)
    {
        // Datos mock mientras configuras la base de datos
        $espera = collect([
            (object)['cliente_nombre' => 'Ana Torres', 'telefono' => '999888777', 'servicio' => 'Corte de Cabello', 'fecha' => now()->format('d/m/Y'), 'estado' => 'En espera'],
            (object)['cliente_nombre' => 'Carlos Ruiz', 'telefono' => '911222333', 'servicio' => 'Manicure', 'fecha' => now()->format('d/m/Y'), 'estado' => 'Notificado'],
        ]);

        $servicios = collect([
            (object)['id' => 1, 'nombre' => 'Corte de Cabello'],
            (object)['id' => 2, 'nombre' => 'Manicure'],
        ]);

        return view('admin.modulos.lista_espera', [
            'titulo' => 'Lista de Espera',
            'espera' => $espera,
            'servicios' => $servicios,
            'boletinActivo' => false,
        ]);
    }

    public function index(Request $request) {
        return $this->pagina($request);
    }
}