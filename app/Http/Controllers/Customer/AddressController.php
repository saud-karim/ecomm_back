<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    /** GET /customer/addresses */
    public function index(): JsonResponse
    {
        $addresses = Address::where('user_id', auth()->id())->latest()->get();

        return response()->json(['success' => true, 'data' => $addresses]);
    }

    /** POST /customer/addresses */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label'        => 'nullable|string|max:50', // e.g. "Home", "Work"
            'name'         => 'required|string|max:100',
            'phone'        => 'required|string|max:20',
            'country'      => 'required|string|max:50',
            'city'         => 'required|string|max:100',
            'district'     => 'nullable|string|max:100',
            'street'       => 'required|string|max:200',
            'building'     => 'nullable|string|max:50',
            'postal_code'  => 'nullable|string|max:20',
            'notes'        => 'nullable|string|max:500',
            'is_default'   => 'boolean',
        ]);

        // Only one default address per user
        if ($request->boolean('is_default')) {
            Address::where('user_id', auth()->id())->update(['is_default' => false]);
        }

        $address = Address::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Address saved.', 'data' => $address], 201);
    }

    /** PUT /customer/addresses/{id} */
    public function update(Request $request, Address $address): JsonResponse
    {
        $this->authorizeAddress($address);

        $validated = $request->validate([
            'label'       => 'nullable|string|max:50',
            'name'        => 'sometimes|string|max:100',
            'phone'       => 'sometimes|string|max:20',
            'country'     => 'sometimes|string|max:50',
            'city'        => 'sometimes|string|max:100',
            'district'    => 'nullable|string|max:100',
            'street'      => 'sometimes|string|max:200',
            'building'    => 'nullable|string|max:50',
            'postal_code' => 'nullable|string|max:20',
            'notes'       => 'nullable|string|max:500',
            'is_default'  => 'boolean',
        ]);

        if ($request->boolean('is_default')) {
            Address::where('user_id', auth()->id())->update(['is_default' => false]);
        }

        $address->update($validated);

        return response()->json(['success' => true, 'message' => 'Address updated.', 'data' => $address]);
    }

    /** DELETE /customer/addresses/{id} */
    public function destroy(Address $address): JsonResponse
    {
        $this->authorizeAddress($address);
        $address->delete();

        return response()->json(['success' => true, 'message' => 'Address deleted.']);
    }

    private function authorizeAddress(Address $address): void
    {
        if ($address->user_id !== auth()->id()) {
            abort(403, 'Access denied.');
        }
    }
}
