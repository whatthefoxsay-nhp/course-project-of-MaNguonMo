<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $currentTab = (string) $request->query('category', 'all');

        // Sự kiện Spotlight nổi bật nhất (hiển thị ở Hero Section)
        $spotlightEvent = Event::published()
            ->with('category')
            ->withMin('showtimes', 'base_price')
            ->latest('release_date')
            ->first();

        // Lọc danh sách sự kiện theo danh mục
        $query = Event::published()
            ->with('category')
            ->withMin('showtimes', 'base_price')
            ->latest('release_date');

        if ($currentTab === 'concert') {
            $query->whereHas('category', fn ($q) => $q->whereIn('slug', ['concert', 'hoa-nhac']));
        } elseif ($currentTab === 'expo') {
            $query->whereHas('category', fn ($q) => $q->whereIn('slug', ['hoi-thao', 'trien-lam', 'workshop']));
        } elseif ($currentTab !== 'all') {
            $query->whereHas('category', fn ($q) => $q->where('slug', $currentTab)->orWhere('id', $currentTab));
        }

        $events = $query->take(9)->get();

        // Phản hồi AJAX khi người dùng chuyển đổi tab
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('partials.home-event-grid', ['movies' => $events])->render(),
                'count' => $events->count(),
                'category' => $currentTab,
            ]);
        }

        return view('home', [
            'movies' => $events->all(),
            'spotlightEvent' => $spotlightEvent,
            'currentTab' => $currentTab,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }
}
