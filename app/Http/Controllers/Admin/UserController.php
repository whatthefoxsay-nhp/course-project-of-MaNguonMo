<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of users with search, filter, and pagination.
     */
    public function index(Request $request): View
    {
        $query = User::with('roles')->latest();

        // 1. Search keyword (name, email, phone)
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // 2. Filter by Role
        if ($role = $request->input('role')) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        // 3. Filter by Status (active / locked)
        if ($request->has('status') && $request->input('status') !== '' && $request->input('status') !== null) {
            $isActive = $request->input('status') === 'active';
            $query->where('is_active', $isActive);
        }

        $users = $query->paginate(10)->withQueryString();

        // Summary KPI statistics for top cards
        $stats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'locked' => User::where('is_active', false)->count(),
            'admins' => User::role('admin')->count(),
        ];

        return view('admin.users.index', [
            'users' => $users,
            'stats' => $stats,
            'filters' => [
                'q' => $request->input('q', ''),
                'role' => $request->input('role', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    /**
     * Toggle lock/unlock status of a user account via AJAX.
     */
    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        // Prevent admin from locking their own account
        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thể tự khóa tài khoản quản trị của chính mình.',
            ], 422);
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $actionText = $user->is_active ? 'mở khóa' : 'khóa';

        return response()->json([
            'success' => true,
            'message' => "Đã {$actionText} tài khoản {$user->name} thành công.",
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'is_active' => $user->is_active,
                'status_label' => $user->is_active ? 'Đang hoạt động' : 'Đã bị khóa',
            ],
        ]);
    }

    /**
     * Toggle admin / user role for an account.
     */
    public function toggleRole(Request $request, User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Bạn không thể tự thay đổi vai trò của chính mình.');
        }

        if ($user->hasRole('admin')) {
            $user->removeRole('admin');
            $user->assignRole('user');
            $roleName = 'Khách hàng (User)';
        } else {
            $user->removeRole('user');
            $user->assignRole('admin');
            $roleName = 'Quản trị viên (Admin)';
        }

        return back()->with('success', "Đã cập nhật vai trò của tài khoản [{$user->name}] thành {$roleName}.");
    }

    /**
     * Reset password to default temporary password.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $tempPassword = 'TicketBox@' . date('Y');
        $user->password = $tempPassword;
        $user->save();

        return back()->with('success', "Đã đặt lại mật khẩu cho tài khoản [{$user->name}] thành: {$tempPassword}");
    }
}
