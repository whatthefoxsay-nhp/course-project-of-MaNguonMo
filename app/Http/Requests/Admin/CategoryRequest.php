<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route đã nằm sau middleware role:admin
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($this->route('category'))],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'tên danh mục', 'description' => 'mô tả'];
    }
}
