<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user('sanctum');
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $perPage = (int) $request->integer('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate($perPage);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order)
    {
        $user = $request->user('sanctum');
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if ((int) $order->user_id !== (int) $user->id) {
            abort(404, 'Order not found.');
        }

        return new OrderResource($order->load('items'));
    }

    public function store(Request $request)
    {
        $user = $request->user('sanctum');
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $data = $request->validate([
            'shipping_address_id' => ['nullable', 'integer'],
            'billing_address_id' => ['nullable', 'integer'],
        ]);

        $cart = Cart::query()->where('user_id', $user->id)->where('status', 'active')->first();
        if (! $cart) {
            throw ValidationException::withMessages([
                'cart' => ['No active cart found.'],
            ]);
        }

        $shippingAddress = $this->resolveAddress($user->id, $data['shipping_address_id'] ?? null, 'shipping');
        $billingAddress = $this->resolveAddress($user->id, $data['billing_address_id'] ?? null, 'billing');

        $order = $this->orders->createFromCart($cart, $shippingAddress, $billingAddress, $user->id);

        return (new OrderResource($order->load('items')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    protected function resolveAddress(int $userId, ?int $addressId, string $kind): Address
    {
        if ($addressId) {
            $address = Address::query()->where('user_id', $userId)->whereKey($addressId)->first();
            if ($address) {
                return $address;
            }
        }

        $query = Address::query()->where('user_id', $userId);
        if ($kind === 'shipping') {
            $query->where('is_default_shipping', true);
        } else {
            $query->where('is_default_billing', true);
        }

        $default = $query->first();
        if ($default) {
            return $default;
        }

        throw ValidationException::withMessages([
            $kind . '_address_id' => ['A valid ' . $kind . ' address is required.'],
        ]);
    }
}
