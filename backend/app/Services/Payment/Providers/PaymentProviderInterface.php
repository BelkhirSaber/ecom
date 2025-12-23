<?php

namespace App\Services\Payment\Providers;

use App\Models\Order;
use App\Models\Payment;

interface PaymentProviderInterface
{
    /**
     * @return array{provider_reference?:string,client_secret?:string,checkout_url?:string,status?:string,metadata?:array}
     */
    public function createIntent(Order $order, Payment $payment): array;
}
