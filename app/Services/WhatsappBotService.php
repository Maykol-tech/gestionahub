<?php
namespace App\Services;
use App\Models\Producto;
use App\Models\Pedido;
use App\Models\WhatsappConversation;
use Illuminate\Support\Facades\Http;

class WhatsappBotService {
    public function handle($from, $text, $tenantId) {
        $conv = WhatsappConversation::firstOrCreate(
            ['tenant_id' => $tenantId, 'phone_number' => $from],
            ['step' => 'saludo']
        );

        $text = strtolower(trim($text));

        if ($conv->step === 'saludo') {
            $conv->update(['step' => 'esperando_producto']);
            return $this->reply("Hola 👋, soy el asistente de GestionaHub. ¿Qué producto deseas pedir?");
        }

        if ($conv->step === 'esperando_producto') {
            $producto = Producto::where('nombre', 'like', "%$text%")->first();
            if (!$producto) {
                return $this->reply("No encontré ese producto. Intenta con otro nombre.");
            }
            $conv->update(['step' => 'esperando_cantidad', 'context' => ['producto_id' => $producto->id]]);
            return $this->reply("¡Excelente! ¿Cuántas unidades de *{$producto->nombre}* deseas?");
        }

        if ($conv->step === 'esperando_cantidad') {
            $cantidad = (int) $text;
            if ($cantidad <= 0) {
                return $this->reply("Por favor, ingresa un número válido de unidades.");
            }
            $context = $conv->context;
            $context['cantidad'] = $cantidad;
            $conv->update(['context' => $context, 'step' => 'confirmando']);
            return $this->reply("Perfecto. ¿Confirmas tu pedido? Responde *si* o *no*.");
        }

        if ($conv->step === 'confirmando') {
            if ($text === 'si') {
                $context = $conv->context;
                $producto = Producto::find($context['producto_id']);
                $total = $producto->precio * $context['cantidad'];

                // Crear el pedido real en el sistema
                $pedido = Pedido::create([
                    'tenant_id' => $tenantId,
                    'cliente_id' => null, // Se puede vincular después
                    'canal' => 'whatsapp',
                    'estado' => 'nuevo',
                    'subtotal' => $total,
                    'total' => $total,
                    'notas' => "Pedido vía WhatsApp por $from",
                ]);

                $pedido->items()->create([
                    'producto_id' => $producto->id,
                    'cantidad' => $context['cantidad'],
                    'precio_unitario' => $producto->precio,
                ]);

                $conv->update(['step' => 'saludo', 'context' => null]);
                return $this->reply("✅ Pedido #{$pedido->numero} registrado. ¡Gracias por tu compra!");
            } else {
                $conv->update(['step' => 'saludo', 'context' => null]);
                return $this->reply("Entendido, he cancelado el pedido. ¿Deseas algo más?");
            }
        }
    }

    private function reply($message) {
        // Aquí iría la llamada real a la API de Meta para enviar el mensaje
        // Ejemplo: Http::withToken(env('WHATSAPP_TOKEN'))->post(...)
        return response()->json(['status' => 'ok', 'reply' => $message]);
    }
}