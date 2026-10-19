<?php

namespace App\Http\Requests\Admin;

use App\Models\Showtime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ShowtimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route đã nằm sau middleware role:admin
    }

    public function rules(): array
    {
        // "after:now" chỉ áp dụng khi tạo mới hoặc khi đổi giờ bắt đầu: suất đã diễn ra vẫn sửa được giá/giờ kết thúc.
        $current = $this->route('showtime');
        $startTime = ['required', 'date'];
        if (! $current instanceof Showtime || ! $this->startTimeUnchanged($current)) {
            $startTime[] = 'after:now';
        }

        return [
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'start_time' => $startTime,
            'end_time' => ['required', 'date', 'after:start_time'],
            'base_price' => ['required', 'integer', 'min:10000', 'max:50000000'],
        ];
    }

    private function startTimeUnchanged(Showtime $showtime): bool
    {
        $input = $this->input('start_time');
        $timestamp = is_string($input) ? strtotime($input) : false;

        // Form gửi theo phút (Y-m-d\TH:i) nên so sánh tới phút.
        return $timestamp !== false
            && $showtime->start_time?->format('Y-m-d H:i') === date('Y-m-d H:i', $timestamp);
    }

    public function attributes(): array
    {
        return [
            'event_id' => 'sự kiện',
            'room_id' => 'khán phòng',
            'start_time' => 'giờ bắt đầu',
            'end_time' => 'giờ kết thúc',
            'base_price' => 'giá vé cơ bản',
        ];
    }

    /** Chặn 2 suất diễn chồng giờ trong cùng một khán phòng. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $current = $this->route('showtime');

                $overlaps = Showtime::where('room_id', $this->integer('room_id'))
                    ->when($current, fn ($query) => $query->whereKeyNot($current->id))
                    ->where('start_time', '<', $this->date('end_time'))
                    ->where('end_time', '>', $this->date('start_time'))
                    ->exists();

                if ($overlaps) {
                    $validator->errors()->add('start_time', 'Khán phòng đã có suất diễn khác trùng khung giờ này.');
                }
            },
        ];
    }
}
