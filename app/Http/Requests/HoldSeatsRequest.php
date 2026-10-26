<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class HoldSeatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'seat_ids' => ['nullable', 'array', 'max:8'],
            'seat_ids.*' => ['integer', 'distinct'],
            'zones' => ['nullable', 'array', 'max:12'],
            'zones.*' => ['integer', 'min:0', 'max:8'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->seatIds() === [] && array_sum($this->zoneQuantities()) === 0) {
                    $validator->errors()->add('seat_ids', 'Vui lòng chọn ít nhất 1 vé.');
                }
            },
        ];
    }

    /** @return list<int> */
    public function seatIds(): array
    {
        return array_map('intval', $this->input('seat_ids', []) ?? []);
    }

    /** @return array<int, int> [id khu => số vé], bỏ các khu có số lượng 0 */
    public function zoneQuantities(): array
    {
        $quantities = [];
        foreach ($this->input('zones', []) ?? [] as $zoneId => $quantity) {
            if ((int) $quantity > 0) {
                $quantities[(int) $zoneId] = (int) $quantity;
            }
        }

        return $quantities;
    }
}
