<?php

namespace App\Services;

use App\Models\Prospecto;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente del microservicio de prospeccion (FastAPI + Playwright) y guardado de sus resultados.
 * Contrato esperado: POST {url}/prospectar con {rubro, zona, limite}
 * y respuesta {"prospectos": [{"nombre", "telefono", "email", "rubro", "fuente_url"}]}.
 */
class ProspeccionIaService
{
    /** Pide prospectos al microservicio. Lanza RuntimeException con un mensaje claro si no responde. */
    public function buscar(string $rubro, string $zona, int $limite = 20): array
    {
        $url = rtrim((string) config('services.prospeccion.url'), '/');
        if ($url === '') {
            throw new RuntimeException('La prospección con IA no está configurada (falta PROSPECCION_IA_URL).');
        }

        try {
            $respuesta = Http::acceptJson()
                ->withToken((string) config('services.prospeccion.token'))
                ->timeout((int) config('services.prospeccion.timeout', 60))
                ->post($url . '/prospectar', ['rubro' => $rubro, 'zona' => $zona, 'limite' => $limite]);
        } catch (ConnectionException) {
            throw new RuntimeException('No pude conectar con el servicio de prospección. Revisa que esté encendido.');
        }

        if (! $respuesta->successful()) {
            throw new RuntimeException('El servicio de prospección respondió con error (' . $respuesta->status() . ').');
        }

        $items = $respuesta->json('prospectos');

        return is_array($items) ? $items : [];
    }

    /**
     * Guarda los prospectos del tenant. Se descartan los repetidos por telefono o email
     * y los que no traen nombre. Devuelve cuantos se guardaron y cuantos ya existian.
     */
    public function importar(int $tenantId, array $items): array
    {
        $guardados = 0;
        $repetidos = 0;

        foreach ($items as $item) {
            $nombre = trim((string) ($item['nombre'] ?? ''));
            // Solo digitos: asi +51 999 111 222 y 999111222 son el mismo numero
            $telefono = preg_replace('/\D/', '', (string) ($item['telefono'] ?? '')) ?: null;
            $email = filled($item['email'] ?? null) ? mb_strtolower(trim((string) $item['email'])) : null;

            if ($nombre === '') {
                continue;
            }

            $existe = Prospecto::withoutGlobalScopes()->where('tenant_id', $tenantId)
                ->where(function ($q) use ($telefono, $email) {
                    if ($telefono) {
                        // Compara los ultimos 9 digitos (numero peruano sin codigo de pais)
                        $q->orWhereRaw("REPLACE(REPLACE(telefono, ' ', ''), '+', '') LIKE ?", ['%' . substr($telefono, -9)]);
                    }
                    if ($email) {
                        $q->orWhere('email', $email);
                    }
                })
                ->when(! $telefono && ! $email, fn ($q) => $q->whereRaw('1 = 0'))
                ->exists();

            if ($existe) {
                $repetidos++;
                continue;
            }

            Prospecto::withoutGlobalScopes()->create([
                'tenant_id' => $tenantId,
                'nombre' => mb_substr($nombre, 0, 150),
                'telefono' => $telefono ? mb_substr($telefono, 0, 30) : null,
                'email' => $email ? mb_substr($email, 0, 150) : null,
                'rubro' => filled($item['rubro'] ?? null) ? mb_substr((string) $item['rubro'], 0, 80) : null,
                'fuente' => 'ia',
                'estado' => 'nuevo',
                'notas' => filled($item['fuente_url'] ?? null) ? mb_substr('Fuente: ' . $item['fuente_url'], 0, 1000) : null,
            ]);
            $guardados++;
        }

        return ['guardados' => $guardados, 'repetidos' => $repetidos];
    }
}
