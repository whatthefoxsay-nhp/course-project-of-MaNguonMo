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
        $this->merge([
            'buyer_name' => is_string($this->buyer_name) ? trim($this->buyer_name) : $this->buyer_name,
            'buyer_phone' => is_string($this->buyer_phone) ? trim($this->buyer_phone) : $this->buyer_phone,
            'buyer_email' => is_string($this->buyer_email) && trim($this->buyer_email) !== '' ? trim($this->buyer_email) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', Rule::in(CheckoutService::PAYMENT_METHODS)],
            'buyer_name' => ['required', 'string', 'max:100'],
            'buyer_phone' => ['required', 'string', 'regex:/^\+?[0-9][0-9 .\-]{7,18}$/'],
            'buyer_email' => ['nullable', 'email', 'max:255'],
            'discount_code' => ['nullable', 'string', 'max:32'],
        ];
    }

    public function attributes(): array
    {
        return [
            'payment_method' => 'phương thức thanh toán',
            'buyer_name' => 'họ tên người nhận vé',
            'buyer_phone' => 'số điện thoại',
            'buyer_email' => 'email',
            'discount_code' => 'mã giảm giá',
        ];
    }

    public function messages(): array
    {
        return [
            'buyer_phone.regex' => 'Số điện thoại không hợp lệ.',
        ];
    }

    /** @return array{name: string, phone: string, email: ?string} */
    public function buyer(): array
    {
        return [
            'name' => $this->validated('buyer_name'),
            'phone' => $this->validated('buyer_phone'),
            'email' => $this->validated('buyer_email'),
        ];
    }
}
