<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $avatarRules = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'];
        if ($this->has('upload_avatar') || $this->routeIs('profile.avatar.*')) {
            $avatarRules = ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()?->id),
            ],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^(0|\+84)[\s.-]?(3[2-9]|5[2689]|7[06-9]|8[1-9]|9\d)([\s.-]?\d){7}$/'],
            'avatar' => $avatarRules,
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'họ và tên', 'phone' => 'số điện thoại', 'avatar' => 'ảnh đại diện'];
    }
}
