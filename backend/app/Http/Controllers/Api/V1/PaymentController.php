<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments)
    {
    }

    public function store(Request $request, Order $order)
    {
        $user = $request->user('sanctum');
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if ((int) $order->user_id !== (int) $user->id) {
            abort(404, 'Order not found.');
        }

        $allowedProviders = $this->payments->allowedProviders();
        $data = $request->validate([
            'provider' => [
                'nullable',
                'string',
                Rule::in($allowedProviders),
            ],
        ]);

        $payment = $this->payments->createForOrder(
            $order,
            (int) $user->id,
            $data['provider'] ?? null
        );

        return (new PaymentResource($payment))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
