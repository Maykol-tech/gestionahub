<?php

namespace App\Http\Controllers;

use App\Support\CrudModulos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** CRUD sencillo de los catalogos configurados en App\Support\CrudModulos (servicios, recompensas). */
class GestionModuloController extends Controller
{
    public function lista(string $modulo): JsonResponse
    {
        $this->validarModulo($modulo);

        return response()->json(['success' => true, 'items' => CrudModulos::cliente($modulo)->items]);
    }

    public function guardar(Request $request, string $modulo): JsonResponse
    {
        $this->validarModulo($modulo);
        $modelo = CrudModulos::definicion($modulo)['modelo'];

        /** @var Model $item */
        $item = new $modelo();
        $item->forceFill($this->datos($request, $modulo) + ['tenant_id' => $request->user()->tenant_id])->save();

        return response()->json(['success' => true, 'message' => CrudModulos::definicion($modulo)['titulo'] . ' creado.', 'item' => $item->fresh()], 201);
    }

    public function actualizar(Request $request, string $modulo, int $id): JsonResponse
    {
        $this->validarModulo($modulo);
        $item = $this->buscar($modulo, $id);
        $item->forceFill($this->datos($request, $modulo))->save();

        return response()->json(['success' => true, 'message' => 'Cambios guardados.', 'item' => $item->fresh()]);
    }

    public function eliminar(string $modulo, int $id): JsonResponse
    {
        $this->validarModulo($modulo);
        $item = $this->buscar($modulo, $id);

        try {
            $item->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            // Tiene registros relacionados (citas, canjes): se desactiva en lugar de borrar
            $item->forceFill(['activo' => false])->save();

            return response()->json(['success' => true, 'message' => 'Tiene registros asociados, así que se desactivó.', 'item' => $item->fresh()]);
        }

        return response()->json(['success' => true, 'message' => 'Eliminado.']);
    }

    private function validarModulo(string $modulo): void
    {
        abort_unless(CrudModulos::existe($modulo), 404);
    }

    private function buscar(string $modulo, int $id): Model
    {
        $modelo = CrudModulos::definicion($modulo)['modelo'];

        return $modelo::query()->where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
    }

    private function datos(Request $request, string $modulo): array
    {
        $datos = $request->validate(CrudModulos::reglas($modulo), [
            'required' => 'Este campo es obligatorio.',
            'numeric' => 'Debe ser un número.',
            'integer' => 'Debe ser un número entero.',
            'min' => 'El valor mínimo es :min.',
            'max' => 'El valor máximo es :max.',
        ]);
        foreach (CrudModulos::definicion($modulo)['campos'] as $c) {
            if ($c[2] === 'si_no') {
                $datos[$c[0]] = $request->boolean($c[0]);
            }
        }

        return $datos;
    }
}
