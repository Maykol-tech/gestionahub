<?php

namespace App\Http\Controllers;

use App\Models\BotFlujo;
use App\Models\WhatsappConexion;
use App\Support\BotFlujos;
use App\Support\CrudModulos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Configuracion de flujos del bot de WhatsApp, con un probador para ver la respuesta antes de publicar. */
class BotFlujosController extends DashboardController
{
    public function pagina(Request $request)
    {
        $this->compartirLayout();

        $flujos = BotFlujo::query()->orderBy('orden')->orderBy('id')->get()
            ->map(fn (BotFlujo $f) => $this->presentar($f))->values();

        $conexion = WhatsappConexion::query()->first();

        return view('admin.modulos.bot_flujos', [
            'titulo' => 'Bot de WhatsApp',
            'modulo' => 'bot',
            'flujos' => $flujos,
            'coincidencias' => BotFlujo::COINCIDENCIAS,
            'conexion' => $conexion ? [
                'phone_id' => $conexion->phone_id,
                'numero_visible' => $conexion->numero_visible ?? '',
                'tiene_token' => filled($conexion->getRawOriginal('token')),
            ] : null,
            'webhook' => url('/api/whatsapp/webhook'),
        ]);
    }

    public function guardar(Request $request): JsonResponse
    {
        $datos = $this->validar($request);
        BotFlujo::query()->create($datos + ['tenant_id' => $request->user()->tenant_id]);

        return response()->json(['success' => true, 'message' => 'Flujo creado.']);
    }

    public function actualizar(Request $request, int $id): JsonResponse
    {
        $flujo = BotFlujo::query()->findOrFail($id);
        $flujo->update($this->validar($request));

        return response()->json(['success' => true, 'message' => 'Flujo actualizado.']);
    }

    public function alternar(int $id): JsonResponse
    {
        $flujo = BotFlujo::query()->findOrFail($id);
        $flujo->update(['activo' => ! $flujo->activo]);

        return response()->json([
            'success' => true,
            'message' => $flujo->activo ? 'Flujo activado.' : 'Flujo pausado.',
        ]);
    }

    public function eliminar(int $id): JsonResponse
    {
        BotFlujo::query()->findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Flujo eliminado.']);
    }

    /** Simula un mensaje entrante y devuelve la respuesta que enviaria el bot. */
    public function probar(Request $request): JsonResponse
    {
        $datos = $request->validate(['mensaje' => ['required', 'string', 'max:500']], [
            'required' => 'Escribe un mensaje para probar.',
        ]);

        $respuesta = BotFlujos::respuesta((int) $request->user()->tenant_id, $datos['mensaje']);

        return response()->json([
            'success' => true,
            'message' => $respuesta ?? 'Ningún flujo coincide. El bot no respondería a este mensaje.',
            'respuesta' => $respuesta,
        ]);
    }

    /** Guarda el numero de WhatsApp Business del negocio. El token se cifra antes de guardarlo. */
    public function guardarConexion(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'phone_id' => ['required', 'digits_between:8,25'],
            'token' => ['nullable', 'string', 'min:20', 'max:600'],
            'numero_visible' => ['nullable', 'string', 'max:30'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'phone_id.digits_between' => 'El ID del número debe tener solo dígitos.',
            'token.min' => 'El token parece incompleto.',
        ]);

        $tenantId = (int) $request->user()->tenant_id;

        $duplicado = WhatsappConexion::withoutGlobalScopes()
            ->where('phone_id', $datos['phone_id'])->where('tenant_id', '!=', $tenantId)->exists();

        if ($duplicado) {
            return response()->json([
                'success' => false,
                'message' => 'Ese número ya está conectado a otro negocio.',
                'errors' => ['phone_id' => ['Ese número ya está conectado a otro negocio.']],
            ], 422);
        }

        $conexion = WhatsappConexion::query()->firstOrNew(['tenant_id' => $tenantId]);
        $conexion->phone_id = $datos['phone_id'];
        $conexion->numero_visible = $datos['numero_visible'] ?? null;
        $conexion->activo = true;

        if (filled($datos['token'] ?? null)) {
            $conexion->guardarToken($datos['token']);
        } elseif (! $conexion->exists) {
            return response()->json([
                'success' => false,
                'message' => 'Pega el token de acceso para conectar el número.',
                'errors' => ['token' => ['Pega el token de acceso para conectar el número.']],
            ], 422);
        }

        $conexion->tenant_id = $tenantId;
        $conexion->save();

        return response()->json(['success' => true, 'message' => 'Número de WhatsApp conectado.']);
    }

    private function validar(Request $request): array
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'disparador' => ['required', 'string', 'max:120'],
            'coincidencia' => ['required', Rule::in(array_keys(BotFlujo::COINCIDENCIAS))],
            'respuesta' => ['required', 'string', 'max:1000'],
            'orden' => ['nullable', 'integer', 'between:0,999'],
            'activo' => ['sometimes', 'boolean'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'coincidencia.in' => 'Elige un tipo de coincidencia válido.',
        ]);

        $datos['orden'] = (int) ($datos['orden'] ?? 0);

        return $datos;
    }

    private function presentar(BotFlujo $f): array
    {
        return [
            'id' => $f->id,
            'nombre' => $f->nombre,
            'disparador' => $f->disparador,
            'coincidencia' => $f->coincidencia,
            'coincidencia_label' => BotFlujo::COINCIDENCIAS[$f->coincidencia] ?? $f->coincidencia,
            'respuesta' => $f->respuesta,
            'orden' => (int) $f->orden,
            'activo' => (bool) $f->activo,
        ];
    }
}
