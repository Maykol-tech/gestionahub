<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AjustesNegocio;
use App\Support\PedidosApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

/** GEST-62: tokens de la API de pedidos, aviso (webhook) y ejemplos para conectar otro sistema. */
class ApiNegocioController extends DashboardController
{
    private function tokens()
    {
        $usuarios = User::query()->where('tenant_id', $this->tenantId())->pluck('name', 'id');

        return PersonalAccessToken::query()->where('tokenable_type', User::class)->whereIn('tokenable_id', $usuarios->keys())
            ->where('abilities', json_encode([PedidosApi::PERMISO]))->latest('id')->get()
            ->map(fn ($t) => [
                'id' => $t->id, 'nombre' => $t->name, 'creado' => $t->created_at?->format('d/m/Y'),
                'usado' => $t->last_used_at?->diffForHumans() ?? 'Nunca', 'por' => $usuarios[$t->tokenable_id] ?? '—',
            ])->values();
    }

    public function pagina()
    {
        $this->compartirLayout();
        $a = AjustesNegocio::leer($this->tenantId());

        return view('admin.api', [
            'tokens' => $this->tokens(),
            'webhook' => ['url' => $a['api_webhook_url'], 'secreto' => $a['api_webhook_secreto'], 'ultimo' => PedidosApi::ultimoEnvio($this->tenantId())],
            'base' => url('/api/v1'),
            'estados' => PedidosApi::ESTADOS,
        ]);
    }

    public function crearToken(Request $request): JsonResponse
    {
        $d = $request->validate(['nombre' => ['required', 'string', 'max:60']], ['nombre.required' => 'Ponle un nombre, por ejemplo "Mi web".']);
        abort_if($this->tokens()->count() >= 10, 422, 'Puedes tener hasta 10 tokens. Revoca uno que no uses.');
        $nuevo = $request->user()->createToken($d['nombre'], [PedidosApi::PERMISO]);

        return response()->json([
            'message' => 'Token creado. Cópialo ahora: por seguridad no lo volveremos a mostrar.',
            'token' => $nuevo->plainTextToken, 'tokens' => $this->tokens(),
        ], 201);
    }

    public function revocarToken(int $id): JsonResponse
    {
        $usuarios = User::query()->where('tenant_id', $this->tenantId())->pluck('id');
        $t = PersonalAccessToken::query()->where('tokenable_type', User::class)->whereIn('tokenable_id', $usuarios)->findOrFail($id);
        $t->delete();

        return response()->json(['message' => 'Token revocado. Lo que lo usaba dejará de funcionar.', 'tokens' => $this->tokens()]);
    }

    public function guardarWebhook(Request $request): JsonResponse
    {
        $d = $request->validate(['url' => ['nullable', 'url:https', 'max:255']], ['url.url' => 'Escribe una dirección que empiece con https://']);
        $a = AjustesNegocio::leer($this->tenantId());
        $secreto = $a['api_webhook_secreto'] ?: Str::random(40);
        AjustesNegocio::guardar($this->tenantId(), ['api_webhook_url' => (string) ($d['url'] ?? ''), 'api_webhook_secreto' => $secreto]);

        return response()->json(['message' => ($d['url'] ?? null) ? 'Aviso guardado. Te avisaremos a esa dirección cuando un pedido cambie de estado.' : 'Aviso desactivado.', 'secreto' => $secreto]);
    }

    public function probarWebhook(): JsonResponse
    {
        $codigo = PedidosApi::enviar($this->tenantId(), ['evento' => 'prueba', 'mensaje' => 'Aviso de prueba de GestionaHub']);
        abort_if($codigo === null, 422, 'Primero guarda la dirección del aviso.');
        $ok = $codigo >= 200 && $codigo < 300;

        return response()->json(['message' => $ok ? "Tu sistema respondió bien (código {$codigo})." : ($codigo ? "Tu sistema respondió con el código {$codigo}." : 'No hubo respuesta de esa dirección.'),
            'ok' => $ok, 'ultimo' => PedidosApi::ultimoEnvio($this->tenantId())], $ok ? 200 : 422);
    }
}
