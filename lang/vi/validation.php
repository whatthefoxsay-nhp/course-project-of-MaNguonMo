<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Thông báo kiểm tra dữ liệu (Validation)
    |--------------------------------------------------------------------------
    |
    | Bản dịch tiếng Việt cho toàn bộ quy tắc kiểm tra mặc định của Laravel 12.
    | ":Attribute" tự viết hoa chữ cái đầu của tên trường khi đứng đầu câu.
    |
    */

    'accepted' => ':Attribute phải được chấp nhận.',
    'accepted_if' => ':Attribute phải được chấp nhận khi :other là :value.',
    'active_url' => ':Attribute phải là một URL hợp lệ.',
    'after' => ':Attribute phải là một ngày sau :date.',
    'after_or_equal' => ':Attribute phải là một ngày sau hoặc bằng :date.',
    'alpha' => ':Attribute chỉ được chứa chữ cái.',
    'alpha_dash' => ':Attribute chỉ được chứa chữ cái, chữ số, dấu gạch ngang và dấu gạch dưới.',
    'alpha_num' => ':Attribute chỉ được chứa chữ cái và chữ số.',
    'array' => ':Attribute phải là một mảng.',
    'ascii' => ':Attribute chỉ được chứa ký tự chữ, số và ký hiệu một byte.',
    'before' => ':Attribute phải là một ngày trước :date.',
    'before_or_equal' => ':Attribute phải là một ngày trước hoặc bằng :date.',
    'between' => [
        'array' => ':Attribute phải có từ :min đến :max phần tử.',
        'file' => ':Attribute phải có dung lượng từ :min đến :max KB.',
        'numeric' => ':Attribute phải nằm trong khoảng từ :min đến :max.',
        'string' => ':Attribute phải có từ :min đến :max ký tự.',
    ],
    'boolean' => ':Attribute phải là đúng hoặc sai.',
    'can' => ':Attribute chứa giá trị không được phép.',
    'confirmed' => 'Xác nhận :attribute không khớp.',
    'contains' => ':Attribute còn thiếu một giá trị bắt buộc.',
    'current_password' => 'Mật khẩu không chính xác.',
    'date' => ':Attribute phải là một ngày hợp lệ.',
    'date_equals' => ':Attribute phải là ngày :date.',
    'date_format' => ':Attribute phải đúng định dạng :format.',
    'decimal' => ':Attribute phải có :decimal chữ số thập phân.',
    'declined' => ':Attribute phải bị từ chối.',
    'declined_if' => ':Attribute phải bị từ chối khi :other là :value.',
    'different' => ':Attribute và :other phải khác nhau.',
    'digits' => ':Attribute phải gồm :digits chữ số.',
    'digits_between' => ':Attribute phải có từ :min đến :max chữ số.',
    'dimensions' => ':Attribute có kích thước ảnh không hợp lệ.',
    'distinct' => ':Attribute có giá trị bị trùng lặp.',
    'doesnt_end_with' => ':Attribute không được kết thúc bằng một trong các giá trị: :values.',
    'doesnt_start_with' => ':Attribute không được bắt đầu bằng một trong các giá trị: :values.',
    'email' => ':Attribute phải là địa chỉ email hợp lệ.',
    'ends_with' => ':Attribute phải kết thúc bằng một trong các giá trị: :values.',
    'enum' => ':Attribute đã chọn không hợp lệ.',
    'exists' => ':Attribute đã chọn không hợp lệ.',
    'extensions' => ':Attribute phải có một trong các phần mở rộng: :values.',
    'file' => ':Attribute phải là một tệp tin.',
    'filled' => ':Attribute không được để trống.',
    'gt' => [
        'array' => ':Attribute phải có nhiều hơn :value phần tử.',
        'file' => ':Attribute phải lớn hơn :value KB.',
        'numeric' => ':Attribute phải lớn hơn :value.',
        'string' => ':Attribute phải dài hơn :value ký tự.',
    ],
    'gte' => [
        'array' => ':Attribute phải có ít nhất :value phần tử.',
        'file' => ':Attribute phải lớn hơn hoặc bằng :value KB.',
        'numeric' => ':Attribute phải lớn hơn hoặc bằng :value.',
        'string' => ':Attribute phải có ít nhất :value ký tự.',
    ],
    'hex_color' => ':Attribute phải là mã màu hex hợp lệ.',
    'image' => ':Attribute phải là hình ảnh.',
    'in' => ':Attribute đã chọn không hợp lệ.',
    'in_array' => ':Attribute phải tồn tại trong :other.',
    'integer' => ':Attribute phải là số nguyên.',
    'ip' => ':Attribute phải là địa chỉ IP hợp lệ.',
    'ipv4' => ':Attribute phải là địa chỉ IPv4 hợp lệ.',
    'ipv6' => ':Attribute phải là địa chỉ IPv6 hợp lệ.',
    'json' => ':Attribute phải là chuỗi JSON hợp lệ.',
    'list' => ':Attribute phải là một danh sách.',
    'lowercase' => ':Attribute phải viết thường.',
    'lt' => [
        'array' => ':Attribute phải có ít hơn :value phần tử.',
        'file' => ':Attribute phải nhỏ hơn :value KB.',
        'numeric' => ':Attribute phải nhỏ hơn :value.',
        'string' => ':Attribute phải ngắn hơn :value ký tự.',
    ],
    'lte' => [
        'array' => ':Attribute không được có nhiều hơn :value phần tử.',
        'file' => ':Attribute phải nhỏ hơn hoặc bằng :value KB.',
        'numeric' => ':Attribute phải nhỏ hơn hoặc bằng :value.',
        'string' => ':Attribute không được dài quá :value ký tự.',
    ],
    'mac_address' => ':Attribute phải là địa chỉ MAC hợp lệ.',
    'max' => [
        'array' => ':Attribute không được có nhiều hơn :max phần tử.',
        'file' => ':Attribute không được lớn hơn :max KB.',
        'numeric' => ':Attribute không được lớn hơn :max.',
        'string' => ':Attribute không được dài quá :max ký tự.',
    ],
    'max_digits' => ':Attribute không được có nhiều hơn :max chữ số.',
    'mimes' => ':Attribute phải là tệp thuộc loại: :values.',
    'mimetypes' => ':Attribute phải là tệp thuộc loại: :values.',
    'min' => [
        'array' => ':Attribute phải có ít nhất :min phần tử.',
        'file' => ':Attribute phải có dung lượng ít nhất :min KB.',
        'numeric' => ':Attribute phải lớn hơn hoặc bằng :min.',
        'string' => ':Attribute phải có ít nhất :min ký tự.',
    ],
    'min_digits' => ':Attribute phải có ít nhất :min chữ số.',
    'missing' => ':Attribute không được xuất hiện.',
    'missing_if' => ':Attribute không được xuất hiện khi :other là :value.',
    'missing_unless' => ':Attribute không được xuất hiện trừ khi :other là :value.',
    'missing_with' => ':Attribute không được xuất hiện khi có :values.',
    'missing_with_all' => ':Attribute không được xuất hiện khi có đủ :values.',
    'multiple_of' => ':Attribute phải là bội số của :value.',
    'not_in' => ':Attribute đã chọn không hợp lệ.',
    'not_regex' => 'Định dạng :attribute không hợp lệ.',
    'numeric' => ':Attribute phải là số.',
    'password' => [
        'letters' => ':Attribute phải chứa ít nhất một chữ cái.',
        'mixed' => ':Attribute phải chứa ít nhất một chữ hoa và một chữ thường.',
        'numbers' => ':Attribute phải chứa ít nhất một chữ số.',
        'symbols' => ':Attribute phải chứa ít nhất một ký tự đặc biệt.',
        'uncompromised' => ':Attribute này đã xuất hiện trong một vụ rò rỉ dữ liệu. Vui lòng chọn :attribute khác.',
    ],
    'present' => ':Attribute phải được cung cấp.',
    'present_if' => ':Attribute phải được cung cấp khi :other là :value.',
    'present_unless' => ':Attribute phải được cung cấp trừ khi :other là :value.',
    'present_with' => ':Attribute phải được cung cấp khi có :values.',
    'present_with_all' => ':Attribute phải được cung cấp khi có đủ :values.',
    'prohibited' => ':Attribute không được phép nhập.',
    'prohibited_if' => ':Attribute không được phép nhập khi :other là :value.',
    'prohibited_if_accepted' => ':Attribute không được phép nhập khi :other được chấp nhận.',
    'prohibited_if_declined' => ':Attribute không được phép nhập khi :other bị từ chối.',
    'prohibited_unless' => ':Attribute không được phép nhập trừ khi :other thuộc :values.',
    'prohibits' => ':Attribute không cho phép :other cùng xuất hiện.',
    'regex' => 'Định dạng :attribute không hợp lệ.',
    'required' => ':Attribute không được để trống.',
    'required_array_keys' => ':Attribute phải chứa các mục: :values.',
    'required_if' => ':Attribute không được để trống khi :other là :value.',
    'required_if_accepted' => ':Attribute không được để trống khi :other được chấp nhận.',
    'required_if_declined' => ':Attribute không được để trống khi :other bị từ chối.',
    'required_unless' => ':Attribute không được để trống trừ khi :other thuộc :values.',
    'required_with' => ':Attribute không được để trống khi có :values.',
    'required_with_all' => ':Attribute không được để trống khi có đủ :values.',
    'required_without' => ':Attribute không được để trống khi không có :values.',
    'required_without_all' => ':Attribute không được để trống khi không có giá trị nào trong :values.',
    'same' => ':Attribute và :other phải giống nhau.',
    'size' => [
        'array' => ':Attribute phải chứa :size phần tử.',
        'file' => ':Attribute phải có dung lượng :size KB.',
        'numeric' => ':Attribute phải bằng :size.',
        'string' => ':Attribute phải có đúng :size ký tự.',
    ],
    'starts_with' => ':Attribute phải bắt đầu bằng một trong các giá trị: :values.',
    'string' => ':Attribute phải là chuỗi ký tự.',
    'timezone' => ':Attribute phải là múi giờ hợp lệ.',
    'unique' => ':Attribute đã tồn tại.',
    'uploaded' => ':Attribute tải lên thất bại.',
    'uppercase' => ':Attribute phải viết hoa.',
    'url' => ':Attribute phải là một URL hợp lệ.',
    'ulid' => ':Attribute phải là ULID hợp lệ.',
    'uuid' => ':Attribute phải là UUID hợp lệ.',

    /*
    |--------------------------------------------------------------------------
    | Thông báo tùy chỉnh theo từng trường
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tên hiển thị của các trường dùng chung
    |--------------------------------------------------------------------------
    |
    | FormRequest nào khai báo attributes() riêng sẽ ghi đè các tên dưới đây.
    |
    */

    'attributes' => [
        'name' => 'tên',
        'email' => 'email',
        'phone' => 'số điện thoại',
        'password' => 'mật khẩu',
        'password_confirmation' => 'xác nhận mật khẩu',
        'current_password' => 'mật khẩu hiện tại',
        'role' => 'vai trò',
        'address' => 'địa chỉ',
        'latitude' => 'vĩ độ',
        'longitude' => 'kinh độ',
        'capacity' => 'sức chứa',
        'layout_preset' => 'mẫu bố cục',
        'base_price' => 'giá vé cơ sở',
        'rows' => 'số hàng ghế',
        'cols' => 'số ghế mỗi hàng',
        'zones' => 'khu vực',
        'title' => 'tiêu đề',
        'description' => 'mô tả',
        'content' => 'nội dung',
        'category_id' => 'danh mục',
        'event_id' => 'sự kiện',
        'room_id' => 'khán phòng',
        'start_time' => 'giờ bắt đầu',
        'end_time' => 'giờ kết thúc',
        'status' => 'trạng thái',
        'code' => 'mã',
        'q' => 'từ khóa tìm kiếm',
        'message' => 'tin nhắn',
    ],

];
