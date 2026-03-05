<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OfferController extends Controller
{
    private function seller()
    {
        return auth()->user()->seller;
    }

    /** GET /seller/offers */
    public function index(Request $request): JsonResponse
    {
        $offers = $this->seller()->offers()
            ->with('product.primaryImage')
            ->when($request->is_flash_deal !== null, fn($q) => $q->where('is_flash_deal', $request->boolean('is_flash_deal')))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json(['success' => true, 'data' => $offers]);
    }

    /** POST /seller/offers */
    public function store(Request $request): JsonResponse
    {
        $seller = $this->seller();

        // Check plan offer limit
        $activeSubscription = $seller->activeSubscription()->first();
        $plan = $activeSubscription?->plan;
        if ($plan && $plan->max_offers !== null) {
            $activeOffers = $seller->offers()->where('is_active', true)->count();
            if ($activeOffers >= $plan->max_offers) {
                return response()->json([
                    'success' => false,
                    'message' => "Your plan allows a maximum of {$plan->max_offers} active offers. Upgrade to add more.",
                ], 422);
            }
        }

        $validated = $request->validate([
            'product_id'     => 'required|exists:products,id',
            'discount_type'  => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric|min:0',
            'starts_at'      => 'required|date',
            'ends_at'        => 'required|date|after:starts_at',
            'is_flash_deal'  => 'boolean',
        ]);

        // Ensure product belongs to seller
        $product = $seller->products()->findOrFail($validated['product_id']);

        $offer = $seller->offers()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Offer created.',
            'data'    => $offer->load('product.primaryImage'),
        ], 201);
    }

    /** PUT /seller/offers/{id} */
    public function update(Request $request, Offer $offer): JsonResponse
    {
        $this->authorizeOffer($offer);

        $validated = $request->validate([
            'discount_type'  => 'sometimes|in:percent,fixed',
            'discount_value' => 'sometimes|numeric|min:0',
            'starts_at'      => 'sometimes|date',
            'ends_at'        => 'sometimes|date|after:starts_at',
            'is_flash_deal'  => 'boolean',
            'is_active'      => 'boolean',
        ]);

        $offer->update($validated);

        return response()->json(['success' => true, 'message' => 'Offer updated.', 'data' => $offer]);
    }

    /** DELETE /seller/offers/{id} */
    public function destroy(Offer $offer): JsonResponse
    {
        $this->authorizeOffer($offer);
        $offer->delete();

        return response()->json(['success' => true, 'message' => 'Offer deleted.']);
    }

    private function authorizeOffer(Offer $offer): void
    {
        if ($offer->seller_id !== $this->seller()->id) {
            abort(403, 'Access denied.');
        }
    }
}
