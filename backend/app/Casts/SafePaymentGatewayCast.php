<?php

namespace App\Casts;

use App\Enums\PaymentGateway;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class SafePaymentGatewayCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?PaymentGateway
    {
        if ($value instanceof PaymentGateway) {
            return $value;
        }

        return PaymentGateway::tryFrom((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value instanceof PaymentGateway) {
            return $value->value;
        }

        return $value;
    }
}
