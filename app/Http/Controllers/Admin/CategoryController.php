<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $request->input('sort', 'name_asc');
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 20, 50])) {
            $perPage = 10;
        }

        $query = Category::withCount('events')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            });

        match ($sort) {
            'events_desc' => $query->orderByDesc('events_count')->orderBy('name'),
            'events_asc' => $query->orderBy('events_count')->orderBy('name'),
            'latest' => $query->latest('id'),
            default => $query->orderBy('name'),
        };

        $categories = $query->paginate($perPage)->withQueryString();

        // Calculate summary statistics
        $allCategories = Category::withCount('events')->get();
        $totalEvents = (int) $allCategories->sum('events_count');
        $topCategory = $allCategories->sortByDesc('events_count')->first();
        $emptyCategoriesCount = $allCategories->where('events_count', 0)->count();

        $stats = [
            'total_categories' => $allCategories->count(),
            'total_events' => $totalEvents,
            'top_category' => $topCategory,
            'empty_categories_count' => $emptyCategoriesCount,
        ];

        return view('admin.categories.index', compact('categories', 'stats', 'totalEvents', 'sort', 'perPage'));
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids) || !is_array($ids)) {
            return back()->with('error', 'Vui lòng chọn ít nhất một danh mục để xóa.');
        }

        $categories = Category::withCount('events')->whereIn('id', $ids)->get();
        $deletedCount = 0;
        $blockedNames = [];

        foreach ($categories as $cat) {
            if ($cat->events_count > 0) {
                $blockedNames[] = $cat->name;
            } else {
                $cat->delete();
                $deletedCount++;
            }
        }

        $msg = "Đã xóa {$deletedCount} danh mục hợp lệ.";
        if (!empty($blockedNames)) {
            $msg .= ' Không thể xóa: ' . implode(', ', $blockedNames) . ' (do vẫn còn sự kiện).';
        }

        return redirect()->route('admin.categories.index')->with($deletedCount > 0 ? 'success' : 'error', $msg);
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('success', "Đã tạo danh mục [{$category->name}].");
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('success', "Đã cập nhật danh mục [{$category->name}].");
    }

    public function destroy(Category $category): RedirectResponse
    {
        // FK events.category_id là cascade: xóa danh mục sẽ xóa luôn sự kiện → chặn.
        if ($category->events()->exists()) {
            return back()->with('error', "Danh mục [{$category->name}] vẫn còn sự kiện, không thể xóa.");
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Đã xóa danh mục.');
    }
}
