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

    protected function prepareForValidation(): void
    {
        if (! $this->has('delivery_email') && $this->user()) {
            $this->merge(['delivery_email' => $this->user()->email]);
        }
        if (! $this->has('delivery_name') && $this->user()) {
            $this->merge(['delivery_name' => $this->user()->name]);
        }
        if (! $this->has('delivery_phone') && $this->user()) {
            $this->merge(['delivery_phone' => $this->user()->phone ?: '0912345678']);
        }
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', Rule::in(CheckoutService::PAYMENT_METHODS)],
            'delivery_email' => ['required', 'email', 'max:255'],
            'delivery_name' => ['required', 'string', 'max:255'],
            'delivery_phone' => ['required', 'string', 'regex:/^(0|\+84)(3[2-9]|5[2689]|7[06-9]|8[1-9]|9\d)\d{7}$/'],
            'discount_code' => ['nullable', 'string', 'max:32'],
            'seat_ids' => ['nullable', 'array'],
            'seat_ids.*' => ['integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'payment_method' => 'phương thức thanh toán',
            'delivery_email' => 'email nhận vé',
            'delivery_name' => 'họ tên người nhận vé',
            'delivery_phone' => 'số điện thoại người nhận vé',
        ];
    }
}
