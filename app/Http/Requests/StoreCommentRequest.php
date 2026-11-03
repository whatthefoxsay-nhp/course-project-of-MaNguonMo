<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        // Mảng/kiểu lạ để nguyên cho rule 'string' trả 422, không ép kiểu gây lỗi 500.
        if (is_string($this->input('content'))) {
            $this->merge(['content' => trim($this->input('content'))]);
        }
    }

    public function rules(): array
    {
        return ['content' => ['required', 'string', 'min:3', 'max:1000']];
    }

    public function attributes(): array
    {
        return ['content' => 'nội dung bình luận'];
    }
}
