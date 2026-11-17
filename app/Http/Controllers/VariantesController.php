<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\PesoDespacho;
use App\Models\Producto;
use App\Models\ProductoKilo;
use App\Models\ProductoVariante;
use App\Support\Variantes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Variantes, venta por kilo y ajuste de peso al despachar (GEST-105 y GEST-111). */
class VariantesController extends DashboardController
{
    public function pagina()
    {
        $this->compartirLayout();

        return view('admin.modulos.variantes', [
            'titulo' => 'Variantes y venta por kilo',
            'modulo' => 'variantes',
            'productos' => Producto::query()->where('activo', true)->orderBy('nombre')->limit(200)
                ->get(['id', 'nombre', 'precio'])
                ->map(fn (Producto $p) => [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'precio' => (float) $p->precio,
                    'kilo' => ProductoKilo::query()->where('producto_id', $p->id)->first(),
                    'variantes' => ProductoVariante::query()->where('producto_id', $p->id)->orderBy('precio')->get(),
                ]),
            'pedidosKilo' => Pedido::query()->whereHas('items')->orderByDesc('id')->limit(20)->get(['id', 'numero', 'total']),
        ]);
    }

    public function guardarVariante(Request $request, Producto $producto): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:60'],
            'precio' => ['required', 'numeric', 'min:0', 'max:99999'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);

        ProductoVariante::create($datos + [
            'tenant_id' => auth()->user()->tenant_id,
            'producto_id' => $producto->id,
            'stock' => $datos['stock'] ?? 0,
            'activo' => true,
        ]);

        return back()->with('ok', 'Variante agregada a ' . $producto->nombre . '.');
    }

    public function eliminarVariante(ProductoVariante $variante): RedirectResponse
    {
        $variante->delete();

        return back()->with('ok', 'Variante eliminada.');
    }

    public function guardarKilo(Request $request, Producto $producto): RedirectResponse
    {
        $datos = $request->validate([
            'precio_kg' => ['required', 'numeric', 'min:0.01', 'max:99999'],
            'activo' => ['sometimes', 'boolean'],
            'presentaciones' => ['nullable', 'string', 'max:120'],
            'peso_libre' => ['sometimes', 'boolean'],
            'peso_minimo' => ['nullable', 'numeric', 'min:0.01', 'max:50'],
        ]);

        // GEST-105: presentaciones en gramos separadas por coma (250, 500, 1000) → kilos
        $presentaciones = collect(preg_split('/[\s,;]+/', (string) ($datos['presentaciones'] ?? ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($g) => round((float) str_replace(',', '.', $g) / 1000, 3))->filter(fn ($kg) => $kg > 0 && $kg <= 50)
            ->unique()->sort()->values()->take(8)->all();

        ProductoKilo::query()->updateOrCreate(
            ['producto_id' => $producto->id],
            [
                'tenant_id' => auth()->user()->tenant_id,
                'precio_kg' => $datos['precio_kg'],
                'activo' => (bool) ($datos['activo'] ?? true),
                'presentaciones' => $presentaciones ?: null,
                'peso_libre' => $request->boolean('peso_libre'),
                'peso_minimo' => $datos['peso_minimo'] ?? 0.1,
            ]
        );

        return back()->with('ok', 'Venta por kilo configurada.');
    }

    /** Registra el peso real de un producto por kilo del pedido y ajusta el total. */
    public function ajustarPeso(Request $request, Pedido $pedido): RedirectResponse
    {
        $datos = $request->validate([
            'producto_id' => ['required', 'integer'],
            'peso_estimado_kg' => ['required', 'numeric', 'min:0.001', 'max:999'],
            'peso_real_kg' => ['required', 'numeric', 'min:0.001', 'max:999'],
        ]);

        $ajuste = Variantes::ajustarPeso($pedido, (int) $datos['producto_id'],
            (float) $datos['peso_real_kg'], (float) $datos['peso_estimado_kg']);

        $signo = $ajuste->diferencia >= 0 ? '+' : '';

        return back()->with('ok', "Peso ajustado. Diferencia en el pedido: {$signo}S/ " . number_format((float) $ajuste->diferencia, 2) . '.');
    }
}
