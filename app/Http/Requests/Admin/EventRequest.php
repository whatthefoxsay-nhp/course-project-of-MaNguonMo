<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route đã nằm sau middleware role:admin
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_seated' => $this->boolean('is_seated')]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'is_seated' => ['boolean'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:1440'],
            'release_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:5000'],
            'poster' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'details' => ['nullable', 'array'],
            'details.venue_name' => ['nullable', 'string', 'max:255'],
            'details.venue_address' => ['nullable', 'string', 'max:500'],
            'details.host_mc' => ['nullable', 'string', 'max:255'],
            'details.special_guests' => ['nullable', 'string', 'max:500'],
            'details.participants_summary' => ['nullable', 'string', 'max:500'],
            'lineup_text' => ['nullable', 'string', 'max:5000'],
            'timeline_text' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'tên sự kiện',
            'category_id' => 'danh mục',
            'status' => 'trạng thái',
            'duration_minutes' => 'thời lượng',
            'release_date' => 'ngày mở bán',
            'poster' => 'poster',
        ];
    }

    /** Các cột thường của bảng events. */
    public function eventAttributes(): array
    {
        return $this->safe()->only([
            'title', 'category_id', 'status', 'is_seated', 'duration_minutes', 'release_date', 'description',
        ]);
    }

    /**
     * Gộp các ô chi tiết của form vào `details` hiện có.
     * Key form không quản lý (organizers, entry_policy, venue_gates…) được giữ nguyên.
     */
    public function mergedDetails(array $existing): array
    {
        $details = array_merge($existing, $this->validated('details') ?? []);
        $details['lineup'] = $this->parseLines($this->validated('lineup_text'), ['name', 'role', 'tag', 'badge']);
        $details['timeline'] = $this->parseLines($this->validated('timeline_text'), ['time', 'title', 'desc']);

        return array_filter($details, fn ($value) => $value !== null && $value !== []);
    }

    /** Mỗi dòng "a | b | c" -> ['key1' => 'a', 'key2' => 'b', ...], thiếu cột thì để chuỗi rỗng. */
    private function parseLines(?string $text, array $keys): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $text))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->map(function (string $line) use ($keys) {
                $parts = array_map('trim', explode('|', $line));

                return array_combine($keys, array_pad(array_slice($parts, 0, count($keys)), count($keys), ''));
            })
            ->values()
            ->all();
    }
}
