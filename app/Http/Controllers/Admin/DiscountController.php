<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscountController extends Controller
{
    /**
     * Display a listing of discount codes and vouchers.
     */
    public function index(Request $request): View
    {
        // Sample discount vouchers data
        $discounts = collect([
            [
                'id' => 1,
                'code' => 'TICKETBOX2026',
                'title' => 'Ưu đãi chào mừng thành viên mới 2026',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'min_order_value' => 200000,
                'max_discount_amount' => 100000,
                'used_count' => 142,
                'max_uses' => 500,
                'status' => 'active',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'applicable_to' => 'Tất cả sự kiện & concert',
            ],
            [
                'id' => 2,
                'code' => 'VIPCONCERT30',
                'title' => 'Giảm 30% cho khách hàng VIP đặt vé sớm',
                'discount_type' => 'percentage',
                'discount_value' => 30,
                'min_order_value' => 500000,
                'max_discount_amount' => 300000,
                'used_count' => 88,
                'max_uses' => 100,
                'status' => 'active',
                'start_date' => '2026-03-01',
                'end_date' => '2026-09-30',
                'applicable_to' => 'Vé VIP & Super VIP',
            ],
            [
                'id' => 3,
                'code' => 'EARLYBIRD50K',
                'title' => 'Voucher giảm 50.000đ khi đặt trước 7 ngày',
                'discount_type' => 'fixed',
                'discount_value' => 50000,
                'min_order_value' => 300000,
                'max_discount_amount' => 50000,
                'used_count' => 320,
                'max_uses' => 1000,
                'status' => 'active',
                'start_date' => '2026-02-15',
                'end_date' => '2026-11-30',
                'applicable_to' => 'Live Concert & Liveshow',
            ],
            [
                'id' => 4,
                'code' => 'TECHSALL100K',
                'title' => 'Hội thảo & Diễn đàn công nghệ Tech Summit',
                'discount_type' => 'fixed',
                'discount_value' => 100000,
                'min_order_value' => 800000,
                'max_discount_amount' => 100000,
                'used_count' => 64,
                'max_uses' => 200,
                'status' => 'active',
                'start_date' => '2026-04-01',
                'end_date' => '2026-10-15',
                'applicable_to' => 'Hội Thảo & Masterclass',
            ],
            [
                'id' => 5,
                'code' => 'SUMMERFEST15',
                'title' => 'Flash Sale Lễ Hội Âm Nhạc Mùa Hè',
                'discount_type' => 'percentage',
                'discount_value' => 15,
                'min_order_value' => 250000,
                'max_discount_amount' => 75000,
                'used_count' => 450,
                'max_uses' => 450,
                'status' => 'expired',
                'start_date' => '2026-05-01',
                'end_date' => '2026-08-31',
                'applicable_to' => 'Festival Âm Nhạc Ngoài Trời',
            ],
        ]);

        $search = $request->input('search');
        $status = $request->input('status');

        if ($search) {
            $discounts = $discounts->filter(function ($item) use ($search) {
                return str_contains(strtoupper($item['code']), strtoupper($search)) ||
                       str_contains(mb_strtolower($item['title']), mb_strtolower($search)) ||
                       str_contains(mb_strtolower($item['applicable_to']), mb_strtolower($search));
            });
        }

        if ($status && in_array($status, ['active', 'expired', 'disabled'])) {
            $discounts = $discounts->filter(function ($item) use ($status) {
                return $item['status'] === $status;
            });
        }

        $stats = [
            'total_active' => 4,
            'total_used' => 1064,
            'total_discount_amount' => 128500000, // 128.5 triệu VNĐ
            'expiring_soon' => 2,
        ];

        return view('admin.discounts.index', compact('discounts', 'stats', 'search', 'status'));
    }
}
