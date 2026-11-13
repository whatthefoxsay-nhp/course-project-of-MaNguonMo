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
        $this->merge(['content' => trim((string) $this->input('content'))]);
    }

    public function rules(): array
    {
        $ratingRules = ['nullable', 'integer', 'between:1,5'];

        if ($this->has('require_rating') || array_key_exists('rating', $this->all())) {
            $ratingRules = ['required', 'integer', 'between:1,5'];
        }

        return [
            'content' => ['required', 'string', 'min:3', 'max:1000'],
            'rating' => $ratingRules,
        ];
    }

    public function attributes(): array
    {
        return ['content' => 'nội dung bình luận', 'rating' => 'số sao'];
    }
}
