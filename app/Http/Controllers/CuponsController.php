<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CuponsController extends Controller
{
    public function index(): Response
    {
        $tenantId = auth()->user()->tenant_id;
        $cupons = Coupon::forTenant($tenantId)
            ->with(['product:id,name', 'products:id,name'])
            ->orderBy('code')
            ->get()
            ->map(fn (Coupon $c) => $this->couponToArray($c));

        $produtos = Product::forTenant($tenantId)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Produtos/Cupons', [
            'cupons' => $cupons,
            'produtos' => $produtos->map(fn (Product $p) => ['id' => $p->id, 'name' => $p->name])->values()->all(),
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'type' => ['required', 'string', 'in:'.Coupon::TYPE_PERCENT.','.Coupon::TYPE_FIXED],
            'value' => ['required', 'numeric', 'min:0'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_active' => ['boolean'],
        ]);
        $validated['tenant_id'] = $tenantId;
        $validated['is_active'] = $request->boolean('is_active', true);
        $productIds = $validated['product_ids'] ?? [];
        unset($validated['product_ids']);

        $exists = Coupon::forTenant($tenantId)
            ->whereRaw('LOWER(code) = ?', [strtolower($validated['code'])])
            ->exists();
        if ($exists) {
            return back()->with('error', 'Já existe um cupom com este código.')->withInput();
        }

        foreach ($productIds as $pid) {
            $product = Product::find($pid);
            if ($product && $product->tenant_id !== $tenantId) {
                abort(403);
            }
        }

        $coupon = Coupon::create($validated);
        $coupon->products()->sync($productIds);

        return redirect()->route('cupons.index')->with('success', 'Cupom criado.');
    }

    public function update(Request $request, Coupon $coupon)
    {
        $this->authorizeCoupon($coupon);
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'type' => ['required', 'string', 'in:'.Coupon::TYPE_PERCENT.','.Coupon::TYPE_FIXED],
            'value' => ['required', 'numeric', 'min:0'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_active' => ['boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        $productIds = $validated['product_ids'] ?? [];
        unset($validated['product_ids']);

        $tenantId = auth()->user()->tenant_id;
        $exists = Coupon::forTenant($tenantId)
            ->where('id', '!=', $coupon->id)
            ->whereRaw('LOWER(code) = ?', [strtolower($validated['code'])])
            ->exists();
        if ($exists) {
            return back()->with('error', 'Já existe um cupom com este código.')->withInput();
        }

        foreach ($productIds as $pid) {
            $product = Product::find($pid);
            if ($product && $product->tenant_id !== $tenantId) {
                abort(403);
            }
        }

        $coupon->update($validated);
        $coupon->products()->sync($productIds);

        return redirect()->route('cupons.index')->with('success', 'Cupom atualizado.');
    }

    public function destroy(Coupon $coupon)
    {
        $this->authorizeCoupon($coupon);
        $coupon->delete();

        return redirect()->route('cupons.index')->with('success', 'Cupom removido.');
    }

    private function authorizeCoupon(Coupon $coupon): void
    {
        if ($coupon->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
    }

    private function couponToArray(Coupon $c): array
    {
        $productIds = $c->products->pluck('id')->values()->all();
        if (empty($productIds) && $c->product_id !== null) {
            $productIds = [$c->product_id];
        }
        $productNames = $c->products->pluck('name')->values()->all();
        if (empty($productNames) && $c->product_id !== null && $c->relationLoaded('product') && $c->product) {
            $productNames = [$c->product->name];
        }

        return [
            'id' => $c->id,
            'code' => $c->code,
            'type' => $c->type,
            'value' => (float) $c->value,
            'product_ids' => $productIds,
            'product_names' => $productNames,
            'product_name' => empty($productNames) ? null : implode(', ', $productNames),
            'min_amount' => $c->min_amount !== null ? (float) $c->min_amount : null,
            'max_uses' => $c->max_uses,
            'used_count' => $c->used_count,
            'valid_from' => $c->valid_from?->toIso8601String(),
            'valid_until' => $c->valid_until?->toIso8601String(),
            'is_active' => $c->is_active,
        ];
    }
}
