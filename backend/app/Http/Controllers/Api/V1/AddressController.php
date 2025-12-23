<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user('sanctum');
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $query = Address::query()->where('user_id', $user->id);

        if ($request->boolean('only_default_shipping')) {
            $query->where('is_default_shipping', true);
        }

        if ($request->boolean('only_default_billing')) {
            $query->where('is_default_billing', true);
        }

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $addresses = $query
            ->orderByDesc('is_default_shipping')
            ->orderByDesc('is_default_billing')
            ->orderByDesc('id')
            ->paginate($perPage);

        return AddressResource::collection($addresses);
    }

    public function store(StoreAddressRequest $request)
    {
        $user = $request->user('sanctum');
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $data = $request->validated();
        $data['user_id'] = $user->id;
        $data['country_code'] = strtoupper($data['country_code']);

        return DB::transaction(function () use ($request, $user, $data) {
            if (! empty($data['is_default_shipping'])) {
                Address::where('user_id', $user->id)->update(['is_default_shipping' => false]);
            }

            if (! empty($data['is_default_billing'])) {
                Address::where('user_id', $user->id)->update(['is_default_billing' => false]);
            }

            $address = Address::create($data);

            Log::channel('catalogue')->info('address.created', [
                'address_id' => $address->id,
                'user_id' => $user->id,
            ]);

            return (new AddressResource($address))
                ->response()
                ->setStatusCode(201);
        });
    }

    public function show(Request $request, Address $address)
    {
        $this->assertOwnership($request, $address);

        return new AddressResource($address);
    }

    public function update(UpdateAddressRequest $request, Address $address)
    {
        $user = $this->assertOwnership($request, $address);

        $data = $request->validated();
        if (array_key_exists('country_code', $data) && $data['country_code'] !== null) {
            $data['country_code'] = strtoupper($data['country_code']);
        }

        return DB::transaction(function () use ($request, $user, $address, $data) {
            if (array_key_exists('is_default_shipping', $data) && $data['is_default_shipping']) {
                Address::where('user_id', $user->id)->where('id', '!=', $address->id)->update(['is_default_shipping' => false]);
            }

            if (array_key_exists('is_default_billing', $data) && $data['is_default_billing']) {
                Address::where('user_id', $user->id)->where('id', '!=', $address->id)->update(['is_default_billing' => false]);
            }

            $address->update($data);

            Log::channel('catalogue')->info('address.updated', [
                'address_id' => $address->id,
                'user_id' => $user->id,
            ]);

            return new AddressResource($address->fresh());
        });
    }

    public function destroy(Request $request, Address $address)
    {
        $user = $this->assertOwnership($request, $address);

        $addressId = $address->id;
        $address->delete();

        Log::channel('catalogue')->info('address.deleted', [
            'address_id' => $addressId,
            'user_id' => $user->id,
        ]);

        return response()->noContent();
    }

    protected function assertOwnership(Request $request, Address $address)
    {
        $user = $request->user('sanctum');
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if ((int) $address->user_id !== (int) $user->id) {
            abort(404, 'Address not found.');
        }

        return $user;
    }
}
