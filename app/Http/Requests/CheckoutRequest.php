<?php

namespace App\Http\Requests;

use App\Services\CheckoutService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', Rule::in(CheckoutService::PAYMENT_METHODS)],
        ];
    }

    public function attributes(): array
    {
        return ['payment_method' => 'phương thức thanh toán'];
    }
}
