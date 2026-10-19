<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    private const MESSAGE = 'Tài khoản của bạn đã bị tạm khóa bởi Quản trị viên. Vui lòng liên hệ ban quản trị để được hỗ trợ.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // So sánh chặt với false: model vừa tạo trong bộ nhớ có thể chưa nạp giá trị mặc định (null).
        if ($user && $user->is_active === false) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => self::MESSAGE], 403);
            }

            return redirect()->route('login')->withErrors(['email' => self::MESSAGE]);
        }

        return $next($request);
    }
}
