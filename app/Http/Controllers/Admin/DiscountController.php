<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscountController extends Controller
{
    /**
     * Display a listing of discount codes and vouchers.
     */
    public function index(Request $request): View
    {
        $query = Discount::query();

        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('applicable_to', 'like', "%{$search}%");
            });
        }

        $status = $request->input('status');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'expired') {
            $query->where(function ($q) {
                $q->where('is_active', false)
                  ->orWhere(function ($sq) {
                      $sq->whereNotNull('end_date')->where('end_date', '<', now()->toDateString());
                  })
                  ->orWhereRaw('used_count >= max_uses');
            });
        }

        $discounts = $query->latest('id')->paginate(10)->withQueryString();

        $stats = [
            'total_active' => Discount::where('is_active', true)->count(),
            'total_used' => Discount::sum('used_count'),
            'total_vouchers' => Discount::count(),
            'expiring_soon' => Discount::where('is_active', true)
                ->whereNotNull('end_date')
                ->whereBetween('end_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
                ->count(),
        ];

        return view('admin.discounts.index', compact('discounts', 'stats', 'search', 'status'));
    }

    /**
     * Store a newly created discount in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:32|unique:discounts,code',
            'title' => 'required|string|max:255',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|integer|min:1',
            'min_order_value' => 'nullable|integer|min:0',
            'max_discount_amount' => 'nullable|integer|min:0',
            'max_uses' => 'required|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'applicable_to' => 'nullable|string|max:255',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['min_order_value'] = $validated['min_order_value'] ?? 0;
        $validated['applicable_to'] = $validated['applicable_to'] ?? 'Tất cả sự kiện & concert';
        $validated['is_active'] = true;

        $discount = Discount::create($validated);

        return redirect()->route('admin.discounts.index')
            ->with('success', "Đã tạo mã khuyến mãi [{$discount->code}] thành công!");
    }

    /**
     * Update the specified discount in storage.
     */
    public function update(Request $request, Discount $discount): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|integer|min:1',
            'min_order_value' => 'nullable|integer|min:0',
            'max_discount_amount' => 'nullable|integer|min:0',
            'max_uses' => 'required|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'applicable_to' => 'nullable|string|max:255',
        ]);

        $discount->update($validated);

        return redirect()->route('admin.discounts.index')
            ->with('success', "Đã cập nhật mã khuyến mãi [{$discount->code}]!");
    }

    /**
     * Remove the specified discount from storage.
     */
    public function destroy(Discount $discount): RedirectResponse
    {
        $code = $discount->code;
        $discount->delete();

        return redirect()->route('admin.discounts.index')
            ->with('success', "Đã xóa mã khuyến mãi [{$code}].");
    }

    /**
     * Toggle active status of a discount.
     */
    public function toggleStatus(Discount $discount): RedirectResponse
    {
        $discount->is_active = ! $discount->is_active;
        $discount->save();

        $statusText = $discount->is_active ? 'kích hoạt' : 'tạm dừng';

        return redirect()->route('admin.discounts.index')
            ->with('success', "Đã {$statusText} mã khuyến mãi [{$discount->code}].");
    }
}
