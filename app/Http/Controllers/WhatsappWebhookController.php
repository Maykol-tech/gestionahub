<?php
namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\WhatsappConversation;
use App\Models\Pedido;
use Illuminate\Http\Request;

class WhatsappWebhookController extends Controller {
    public function handle(Request $request) {
        if ($request->isMethod('get')) {
            if ($request->hub_verify_token === env('WHATSAPP_VERIFY_TOKEN')) {
                return response($request->hub_challenge, 200);
            }
            return response('Token inválido', 403);
        }

        $entry = $request->input('entry.0.changes.0.value');
        if (isset($entry['messages'][0])) {
            $message = $entry['messages'][0];
            $from = $message['from'];
            $text = strtolower(trim($message['text']['body'] ?? ''));
            $tenantId = 1; 

            // SOLUCIÓN: Usar withoutGlobalScopes para que no falle el TenantScope al no haber usuario logueado
            $conv = WhatsappConversation::withoutGlobalScopes()->firstOrCreate(
                ['tenant_id' => $tenantId, 'phone_number' => $from],
                ['step' => 'saludo']
            );

            if ($conv->step === 'saludo') {
                $conv->update(['step' => 'esperando_producto']);
                return response()->json(['status' => 'ok', 'reply' => "Hola, soy el asistente. ¿Qué producto deseas pedir?"]);
            }

            if ($conv->step === 'esperando_producto') {
                $producto = Producto::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('nombre', 'like', "%$text%")
                    ->first();
                    
                if (!$producto) {
                    return response()->json(['status' => 'ok', 'reply' => "No encontré ese producto. Intenta con otro."]);
                }
                $conv->update(['step' => 'esperando_cantidad', 'context' => ['producto_id' => $producto->id]]);
                return response()->json(['status' => 'ok', 'reply' => "¿Cuántas unidades de *{$producto->nombre}* deseas?"]);
            }

            if ($conv->step === 'esperando_cantidad') {
                $cantidad = (int) $text;
                if ($cantidad <= 0) {
                    return response()->json(['status' => 'ok', 'reply' => "Ingresa un número válido."]);
                }
                $context = $conv->context;
                $context['cantidad'] = $cantidad;
                $conv->update(['step' => 'confirmando', 'context' => $context]);
                return response()->json(['status' => 'ok', 'reply' => "Perfecto. ¿Confirmas tu pedido? Responde *si* o *no*."]);
            }

            if ($conv->step === 'confirmando') {
                if ($text === 'si') {
                    $context = $conv->context;
                    $producto = Producto::withoutGlobalScopes()->find($context['producto_id']);
                    $total = $producto->precio * $context['cantidad'];

                    $pedido = new Pedido();
                    $pedido->tenant_id = $tenantId;
                    $pedido->canal = 'whatsapp';
                    $pedido->estado = 'nuevo';
                    $pedido->subtotal = $total;
                    $pedido->total = $total;
                    $pedido->notas = "Pedido vía WhatsApp por $from";
                    $pedido->save();

                    $pedido->items()->create([
                        'producto_id' => $producto->id,
                        'cantidad' => $context['cantidad'],
                        'precio_unitario' => $producto->precio,
                    ]);

                    $conv->update(['step' => 'saludo', 'context' => null]);
                    return response()->json(['status' => 'ok', 'reply' => "✅ Pedido registrado. ¡Gracias!"]);
                } else {
                    $conv->update(['step' => 'saludo', 'context' => null]);
                    return response()->json(['status' => 'ok', 'reply' => "Entendido, cancelado. ¿Deseas algo más?"]);
                }
            }
        }
        return response()->json(['status' => 'ok']);
    }
}