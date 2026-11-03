<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Cảm ơn bạn đã đăng ký! Trước khi bắt đầu, vui lòng xác minh địa chỉ email bằng cách bấm vào liên kết chúng tôi vừa gửi tới hộp thư của bạn. Nếu chưa nhận được email, chúng tôi sẵn sàng gửi lại cho bạn.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            Một liên kết xác minh mới đã được gửi tới địa chỉ email bạn dùng khi đăng ký.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    Gửi lại email xác minh
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Đăng xuất
            </button>
        </form>
    </div>
</x-guest-layout>
