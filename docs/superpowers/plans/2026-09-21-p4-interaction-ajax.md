# Plan 4 — Tương tác AJAX: bình luận, gợi ý tìm kiếm, avatar

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bình luận sự kiện lưu DB và gửi/xóa không tải lại trang; ô tìm kiếm toàn trang gợi ý
sự kiện theo từng ký tự gõ; người dùng cập nhật số điện thoại và ảnh đại diện.

**Architecture:** Endpoint JSON trong `routes/web.php` (dùng session + CSRF như phần còn lại),
Alpine component gọi bằng `fetch()`. Quyền xóa bình luận qua `CommentPolicy` (chủ bình luận
hoặc admin). Ảnh đại diện lưu disk `public` thư mục `avatars/`.

**Tech Stack:** Laravel 11 (Policy, Form Request, Storage), Pest 4, Alpine.js.

**Spec:** `docs/superpowers/specs/2026-09-07-ticket-booking-platform-design.md` (mục 2 "AJAX: … tìm kiếm gợi ý, bình luận không reload", "Upload: … avatar người dùng"; Phase 3–4)
**Lộ trình:** `docs/superpowers/plans/2026-09-21-roadmap-hoan-thien.md`

## Global Constraints

- Nhánh: `feature/interaction-ajax`, rẽ từ `main` **sau khi Plan 2 đã merge** (không cần chờ Plan 3).
  Nếu Plan 3 merge trước, rebase lên `main` trước khi mở PR; xung đột có thể ở `resources/js/app.js`
  (hàm `jsonHeaders`) và `app/Http/Controllers/EventController.php` — giữ thay đổi của cả hai bên.
- Cần từ Plan 1–2: `Event::published()`, `EventController@show` đọc DB, `$event->poster_url`,
  `$event->base_price`, `createAdmin()`/`createCustomer()`, seeder (bình luận mẫu trên sự kiện hòa nhạc).
- Bình luận: 3–1000 ký tự, throttle 10 lần/phút. Gợi ý tìm kiếm: từ 2 ký tự, tối đa 6 kết quả.
- Avatar: jpg/png/webp, ≤ 1MB. Không gọi dịch vụ ảnh bên ngoài để tạo avatar (không gửi tên user ra ngoài).
- Nội dung bình luận luôn render bằng `x-text` (không `x-html`) để tránh XSS.
- Chạy `npm run build` sau task có sửa JS. `php artisan test` xanh trước PR.

## File Structure

| File | Trách nhiệm |
|---|---|
| `app/Models/Comment.php` (sửa) | `toPublicArray(?User $viewer)` |
| `app/Policies/CommentPolicy.php` (mới) | Chủ bình luận hoặc admin được xóa |
| `app/Http/Requests/StoreCommentRequest.php` (mới) | Validation nội dung |
| `app/Http/Controllers/CommentController.php` (mới) | Thêm / xóa bình luận (JSON) |
| `app/Http/Controllers/EventController.php` (sửa) | Truyền danh sách bình luận cho trang chi tiết |
| `app/Http/Controllers/SearchSuggestionController.php` (mới) | Gợi ý tìm kiếm (JSON) |
| `app/Http/Requests/ProfileUpdateRequest.php`, `ProfileController.php` (sửa) | SĐT + avatar |
| `app/Models/User.php` (sửa) | `avatar_url` |
| `resources/js/app.js` (sửa) | `eventComments`, `searchModal.handleSearch` |
| `resources/views/events/show.blade.php` (sửa, khối bình luận dòng ~535–570) | |
| `resources/views/layouts/site.blade.php` (sửa, modal tìm kiếm dòng ~350–385) | |
| `resources/views/profile/partials/update-profile-information-form.blade.php` (sửa) | |

---

### Task 1: API bình luận

**Files:**
- Modify: `app/Models/Comment.php`
- Create: `app/Policies/CommentPolicy.php`
- Create: `app/Http/Requests/StoreCommentRequest.php`
- Create: `app/Http/Controllers/CommentController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/CommentApiTest.php`

**Interfaces:**
- Produces: `Comment::toPublicArray(?User $viewer): array{id:int, user:string, avatar_url:?string, time:string, text:string, can_delete:bool}` (Task 2 dùng)
- Produces routes:
  - `POST /events/{event:slug}/comments` → `comments.store` (auth, throttle 10/phút). Body `{ "content": "..." }`. 201 → `{ "comment": {...toPublicArray} }`; 422 lỗi validation; 404 nếu sự kiện chưa `published`.
  - `DELETE /comments/{comment}` → `comments.destroy` (auth). 200 → `{ "message": "..." }`; 403 nếu không có quyền.

- [ ] **Step 1: Viết test thất bại `tests/Feature/CommentApiTest.php`**

```php
<?php

use App\Models\Comment;
use App\Models\Event;

beforeEach(fn () => $this->seed());

function concert(): Event
{
    return Event::firstWhere('slug', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026');
}

test('guests cannot comment', function () {
    $this->postJson(route('comments.store', concert()), ['content' => 'Hay quá'])->assertUnauthorized();
});

test('a customer posts a comment and gets it back as json', function () {
    $user = createCustomer(['name' => 'Minh Anh']);

    $this->actingAs($user)
        ->postJson(route('comments.store', concert()), ['content' => '  Sân khấu rất đẹp!  '])
        ->assertCreated()
        ->assertJsonPath('comment.user', 'Minh Anh')
        ->assertJsonPath('comment.text', 'Sân khấu rất đẹp!')
        ->assertJsonPath('comment.can_delete', true);

    expect(Comment::where('user_id', $user->id)->where('event_id', concert()->id)->exists())->toBeTrue();
});

test('comment content is validated', function (string $content) {
    $this->actingAs(createCustomer())
        ->postJson(route('comments.store', concert()), ['content' => $content])
        ->assertStatus(422)
        ->assertJsonValidationErrors('content');
})->with(['rỗng' => '', 'quá ngắn' => 'ok', 'quá dài' => str_repeat('a', 1001)]);

test('comments cannot be posted on unpublished events', function () {
    $draft = Event::factory()->create(['status' => 'draft']);

    $this->actingAs(createCustomer())
        ->postJson(route('comments.store', $draft), ['content' => 'Bình luận thử'])
        ->assertNotFound();
});

test('the author can delete their comment', function () {
    $user = createCustomer();
    $comment = Comment::create(['user_id' => $user->id, 'event_id' => concert()->id, 'content' => 'Xóa tôi đi']);

    $this->actingAs($user)->deleteJson(route('comments.destroy', $comment))->assertOk();

    expect(Comment::find($comment->id))->toBeNull();
});

test('another customer cannot delete it but an admin can', function () {
    $comment = Comment::create(['user_id' => createCustomer()->id, 'event_id' => concert()->id, 'content' => 'Bình luận']);

    $this->actingAs(createCustomer())->deleteJson(route('comments.destroy', $comment))->assertForbidden();
    $this->actingAs(createAdmin())->deleteJson(route('comments.destroy', $comment))->assertOk();

    expect(Comment::find($comment->id))->toBeNull();
});
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=CommentApiTest`
Expected: FAIL — route `comments.store` chưa có.

- [ ] **Step 3: `Comment::toPublicArray()`**

Trong `app/Models/Comment.php` thêm:

```php
    public function toPublicArray(?User $viewer): array
    {
        return [
            'id' => $this->id,
            'user' => $this->user?->name ?? 'Người dùng',
            'avatar_url' => $this->user?->avatar_url,
            'time' => $this->created_at?->locale('vi')->diffForHumans() ?? 'Vừa xong',
            'text' => $this->content,
            'can_delete' => $viewer !== null && $viewer->can('delete', $this),
        ];
    }
```

(Accessor `avatar_url` được thêm ở Task 3; trước đó trả `null` vì thuộc tính chưa tồn tại — test Task 1 không kiểm tra trường này.)

- [ ] **Step 4: `app/Policies/CommentPolicy.php`**

```php
<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id || $user->hasRole('admin');
    }
}
```

(Laravel 11 tự tìm policy theo tên `App\Policies\{Model}Policy`, không cần đăng ký.)

- [ ] **Step 5: `app/Http/Requests/StoreCommentRequest.php`**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['content' => trim((string) $this->input('content'))]);
    }

    public function rules(): array
    {
        return ['content' => ['required', 'string', 'min:3', 'max:1000']];
    }

    public function attributes(): array
    {
        return ['content' => 'nội dung bình luận'];
    }
}
```

- [ ] **Step 6: `app/Http/Controllers/CommentController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Event $event): JsonResponse
    {
        abort_unless($event->status === 'published', 404);

        $comment = $event->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->validated('content'),
        ]);

        return response()->json(['comment' => $comment->load('user')->toPublicArray($request->user())], 201);
    }

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->json(['message' => 'Đã xóa bình luận.']);
    }
}
```

- [ ] **Step 7: Routes**

Thêm `use App\Http\Controllers\CommentController;` vào `routes/web.php`, và trong group `Route::middleware('auth')` đầu tiên:

```php
    Route::post('/events/{event:slug}/comments', [CommentController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
```

- [ ] **Step 8: Chạy test**

Run: `php artisan test --filter=CommentApiTest`
Expected: PASS 8 test (kể cả 3 case dataset).

- [ ] **Step 9: Commit**

```bash
git add app/Models/Comment.php app/Policies/CommentPolicy.php app/Http/Requests/StoreCommentRequest.php app/Http/Controllers/CommentController.php routes/web.php tests/Feature/CommentApiTest.php
git commit -m "feat: add comment create/delete JSON endpoints with ownership policy"
```

---

### Task 2: Giao diện bình luận AJAX trên trang chi tiết

**Files:**
- Modify: `app/Http/Controllers/EventController.php` (method `show`)
- Modify: `resources/js/app.js`
- Modify: `resources/views/events/show.blade.php` (khối `<!-- 8. COMMENTS / REVIEWS SECTION -->`, ~dòng 535–570)
- Test: `tests/Feature/EventCommentsPageTest.php`

**Interfaces:**
- Consumes: `Comment::toPublicArray()`, routes `comments.store`, `comments.destroy` (Task 1)
- Produces: view `events.show` nhận thêm `$comments` (array các `toPublicArray`, mới nhất trước, tối đa 30)
- Produces: helper JS `jsonHeaders()` (nếu chưa có) và `Alpine.data('eventComments', config)`; config: `comments`, `storeUrl`, `destroyUrl` (chứa `__ID__`), `loginUrl`, `canComment`

- [ ] **Step 1: Viết test thất bại `tests/Feature/EventCommentsPageTest.php`**

```php
<?php

use App\Models\Event;

beforeEach(fn () => $this->seed());

test('the event page renders stored comments instead of hard coded ones', function () {
    $this->get(route('events.show', 'hoa-nhac-giao-huong-saigon-philharmonic'))
        ->assertOk()
        ->assertSee('Khán phòng cách âm tuyệt vời')
        ->assertDontSee('Dàn âm thanh L-Acoustics và visual pháo hoa đỉnh chóp')
        ->assertViewHas('comments', fn (array $comments) => count($comments) === 1 && $comments[0]['can_delete'] === false);
});

test('the author sees a delete option on their own comment', function () {
    $user = createCustomer();
    Event::firstWhere('slug', 'vietnam-tech-summit-ai-expo-2026')
        ->comments()->create(['user_id' => $user->id, 'content' => 'Rất bổ ích']);

    $this->actingAs($user)
        ->get(route('events.show', 'vietnam-tech-summit-ai-expo-2026'))
        ->assertViewHas('comments', fn (array $comments) => $comments[0]['can_delete'] === true);
});
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=EventCommentsPageTest`
Expected: FAIL — view chưa có biến `comments`.

- [ ] **Step 3: Truyền bình luận trong `EventController@show`**

Trong `app/Http/Controllers/EventController.php`, method `show(string $slug)` đổi chữ ký thành
`show(Request $request, string $slug)` và thêm trước `return view(...)`:

```php
        $comments = $event->comments()
            ->with('user')
            ->latest()
            ->take(30)
            ->get()
            ->map(fn ($comment) => $comment->toPublicArray($request->user()))
            ->all();
```

Thêm `'comments' => $comments,` vào mảng truyền cho view.

- [ ] **Step 4: Alpine component trong `resources/js/app.js`**

Nếu file **chưa có** `const jsonHeaders` (kiểm tra: `grep -n "const jsonHeaders" resources/js/app.js`), thêm ngay sau dòng `window.Alpine = Alpine;`:

```js
// Header chuẩn cho request AJAX tới Laravel (CSRF + nhận JSON)
const jsonHeaders = () => ({
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
});
```

Thêm component mới (đặt ngay trước dòng `Alpine.data('countdownTimer', ...`):

```js
// Bình luận sự kiện: gửi / xóa không tải lại trang
Alpine.data('eventComments', (config = {}) => ({
    comments: config.comments || [],
    newComment: '',
    isPosting: false,
    error: '',

    async submit() {
        const content = this.newComment.trim();
        if (!content || this.isPosting) return;

        if (!config.canComment) {
            window.location.href = config.loginUrl;
            return;
        }

        this.isPosting = true;
        this.error = '';

        try {
            const response = await fetch(config.storeUrl, {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ content }),
            });
            const data = await response.json();

            if (!response.ok) {
                this.error = data.errors?.content?.[0] || data.message || 'Không thể gửi bình luận.';
                return;
            }

            this.comments.unshift(data.comment);
            this.newComment = '';
        } catch (error) {
            this.error = 'Mất kết nối tới máy chủ, vui lòng thử lại.';
        } finally {
            this.isPosting = false;
        }
    },

    async remove(comment) {
        if (!confirm('Xóa bình luận này?')) return;

        const response = await fetch(config.destroyUrl.replace('__ID__', comment.id), {
            method: 'DELETE',
            headers: jsonHeaders(),
        });

        if (response.ok) {
            this.comments = this.comments.filter(item => item.id !== comment.id);
        } else {
            this.error = 'Không thể xóa bình luận này.';
        }
    },
}));
```

- [ ] **Step 5: Thay khối bình luận trong `resources/views/events/show.blade.php`**

Thay toàn bộ từ `<!-- 8. COMMENTS / REVIEWS SECTION -->` tới thẻ `</div>` đóng khối đó
(khối có `x-data="{ newComment: '', comments: [...] }"`, kết thúc ngay sau `</template></div>` của danh sách, ~dòng 570) bằng:

```blade
                        <!-- 8. COMMENTS / REVIEWS SECTION -->
                        <div class="mt-10 pt-8 border-t border-black/10"
                             x-data="eventComments({
                                 comments: @js($comments),
                                 storeUrl: @js(route('comments.store', $movie->slug)),
                                 destroyUrl: @js(route('comments.destroy', '__ID__')),
                                 loginUrl: @js(route('login')),
                                 canComment: @js(auth()->check()),
                             })">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-display font-black text-lg text-black">Đánh Giá &amp; Cảm Nhận Khán Giả</h3>
                                <span class="text-xs text-gray-500" x-text="comments.length + ' bình luận'"></span>
                            </div>

                            <!-- Comment Input Form -->
                            <form @submit.prevent="submit()" class="bg-[#FAF9F6] rounded-2xl p-3 border border-black/10 mb-2 flex gap-2">
                                <input
                                    type="text"
                                    x-model="newComment"
                                    maxlength="1000"
                                    placeholder="{{ auth()->check() ? 'Chia sẻ cảm nhận của bạn về sự kiện này...' : 'Đăng nhập để bình luận...' }}"
                                    class="bg-transparent border-0 flex-1 text-xs text-black placeholder-gray-500 focus:ring-0 font-medium"
                                >
                                <button
                                    type="submit"
                                    :disabled="isPosting"
                                    class="btn-rose px-5 py-2 rounded-xl text-xs font-bold shrink-0 shadow-sm disabled:opacity-50"
                                    x-text="isPosting ? 'Đang gửi...' : 'Gửi'"
                                >Gửi</button>
                            </form>
                            <p x-show="error" x-text="error" class="text-[11px] font-bold text-[#CC0000] mb-4" style="display: none;"></p>

                            <!-- Comment list -->
                            <div class="space-y-3 mt-4">
                                <p x-show="comments.length === 0" class="text-xs text-gray-500">Chưa có bình luận nào. Hãy là người đầu tiên!</p>
                                <template x-for="c in comments" :key="c.id">
                                    <div class="bg-[#FAF9F6] rounded-2xl p-4 border border-black/10 space-y-1 shadow-sm">
                                        <div class="flex items-center justify-between text-xs gap-3">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <template x-if="c.avatar_url">
                                                    <img :src="c.avatar_url" alt="" class="w-6 h-6 rounded-full object-cover">
                                                </template>
                                                <span class="font-bold text-black truncate" x-text="c.user"></span>
                                            </div>
                                            <div class="flex items-center gap-3 shrink-0">
                                                <span class="text-[10px] text-gray-500" x-text="c.time"></span>
                                                <button type="button" x-show="c.can_delete" @click="remove(c)" class="text-[10px] font-bold text-[#CC0000] hover:underline">Xóa</button>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-700 whitespace-pre-line" x-text="c.text"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
```

- [ ] **Step 6: Build + test**

Run: `npm run build && php artisan test`
Expected: PASS toàn bộ.

- [ ] **Step 7: Kiểm tra tay**

Đăng nhập `user@ticketbox.vn` → trang một sự kiện → gửi bình luận (hiện ngay, không tải lại) → F5 vẫn còn → bấm "Xóa" → mất.
Đăng xuất → bấm "Gửi" → chuyển tới trang đăng nhập. Gửi nội dung `<script>alert(1)</script>` → hiển thị dạng chữ, không chạy.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/EventController.php resources/js/app.js resources/views/events/show.blade.php tests/Feature/EventCommentsPageTest.php
git commit -m "feat: post and delete event comments without page reload"
```

---

### Task 3: Gợi ý tìm kiếm AJAX

**Files:**
- Create: `app/Http/Controllers/SearchSuggestionController.php`
- Modify: `routes/web.php`
- Modify: `resources/js/app.js` (`Alpine.data('searchModal', ...)`, dòng ~210–242)
- Modify: `resources/views/layouts/site.blade.php` (modal tìm kiếm, ~dòng 358–385)
- Test: `tests/Feature/SearchSuggestionTest.php`

**Interfaces:**
- Produces: route `GET /search/suggestions?q=` → `search.suggestions` (public, throttle 60/phút). 200 → `{ "results": [{ "title", "url", "category", "poster_url", "price_from" }] }`; `price_from` là chuỗi đã định dạng (`"250.000₫"`) hoặc `null`. `q` < 2 ký tự → `results: []`. `q` > 100 ký tự → 422.

- [ ] **Step 1: Viết test thất bại `tests/Feature/SearchSuggestionTest.php`**

```php
<?php

use App\Models\Event;

beforeEach(fn () => $this->seed());

test('suggestions match published events by title', function () {
    // Viết đúng hoa/thường như tiêu đề: SQLite (DB test) chỉ so LIKE không phân biệt hoa/thường với ký tự ASCII.
    $this->getJson(route('search.suggestions', ['q' => 'Hòa Nhạc']))
        ->assertOk()
        ->assertJsonCount(1, 'results')
        ->assertJsonPath('results.0.url', route('events.show', 'hoa-nhac-giao-huong-saigon-philharmonic'))
        ->assertJsonPath('results.0.price_from', '180.000₫');
});

test('drafts are never suggested', function () {
    Event::factory()->create(['title' => 'Hòa nhạc bí mật', 'status' => 'draft']);

    $this->getJson(route('search.suggestions', ['q' => 'bí mật']))
        ->assertOk()
        ->assertJsonCount(0, 'results');
});

test('short queries return nothing and long ones are rejected', function () {
    $this->getJson(route('search.suggestions', ['q' => 'a']))->assertOk()->assertJsonCount(0, 'results');
    $this->getJson(route('search.suggestions', ['q' => str_repeat('a', 101)]))->assertStatus(422);
});

test('at most six suggestions are returned', function () {
    Event::factory()->count(8)->create(['title' => 'Liveshow đặc biệt']);

    $this->getJson(route('search.suggestions', ['q' => 'Liveshow']))->assertJsonCount(6, 'results');
});
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=SearchSuggestionTest`
Expected: FAIL — route `search.suggestions` chưa có.

- [ ] **Step 3: `app/Http/Controllers/SearchSuggestionController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $query = trim((string) ($validated['q'] ?? ''));

        if (mb_strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results = Event::published()
            ->with('category')
            ->withMin('showtimes', 'base_price')
            ->where('title', 'like', "%{$query}%")
            ->orderBy('title')
            ->take(6)
            ->get()
            ->map(fn (Event $event) => [
                'title' => $event->title,
                'url' => route('events.show', $event->slug),
                'category' => $event->category?->name,
                'poster_url' => $event->poster_url,
                'price_from' => $event->base_price === null ? null : number_format($event->base_price, 0, ',', '.').'₫',
            ]);

        return response()->json(['results' => $results]);
    }
}
```

- [ ] **Step 4: Route**

Thêm `use App\Http\Controllers\SearchSuggestionController;` và (ngoài group auth, cạnh các route `events.*`):

```php
Route::get('/search/suggestions', SearchSuggestionController::class)
    ->middleware('throttle:60,1')
    ->name('search.suggestions');
```

- [ ] **Step 5: Sửa `searchModal` trong `resources/js/app.js`**

Thêm state `searchTimer: null,` sau `isLoading: false,`. Thay method `close()` và `handleSearch()` bằng:

```js
    close() {
        this.isOpen = false;
        this.query = '';
        this.results = [];
        clearTimeout(this.searchTimer);
    },

    // Gợi ý tìm kiếm AJAX, debounce 250ms
    handleSearch() {
        clearTimeout(this.searchTimer);
        const query = this.query.trim();

        if (query.length < 2) {
            this.results = [];
            this.isLoading = false;
            return;
        }

        this.isLoading = true;
        this.searchTimer = setTimeout(async () => {
            const url = document.getElementById('global-search-input')?.dataset.suggestUrl;
            try {
                const response = await fetch(`${url}?q=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await response.json();
                if (query === this.query.trim()) {
                    this.results = response.ok ? data.results : [];
                }
            } catch (error) {
                this.results = [];
            } finally {
                this.isLoading = false;
            }
        }, 250);
    }
```

- [ ] **Step 6: Sửa modal tìm kiếm trong `resources/views/layouts/site.blade.php`**

1. Trên thẻ `<input id="global-search-input" ...>` (~dòng 360) thêm thuộc tính:
   `data-suggest-url="{{ route('search.suggestions') }}"` và `autocomplete="off"`.
2. Ngay **trước** comment `<!-- Quick Suggestions -->` (~dòng 372) chèn:

```blade
            <!-- Live Search Results (AJAX) -->
            <div class="mt-4" x-show="query.trim().length >= 2" style="display: none;">
                <p x-show="isLoading" class="text-xs text-gray-500 py-2">Đang tìm…</p>
                <p x-show="!isLoading && results.length === 0" class="text-xs text-gray-500 py-2">Không tìm thấy sự kiện phù hợp.</p>
                <ul x-show="!isLoading && results.length > 0" class="divide-y divide-black/5">
                    <template x-for="item in results" :key="item.url">
                        <li>
                            <a :href="item.url" class="flex items-center gap-3 py-2.5 px-2 rounded-xl hover:bg-[#FAF9F6]">
                                <img :src="item.poster_url" alt="" class="w-10 h-14 object-cover rounded-lg border border-black/10">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-black truncate" x-text="item.title"></p>
                                    <p class="text-[11px] text-gray-500">
                                        <span x-text="item.category"></span>
                                        <span x-show="item.price_from" x-text="' · Từ ' + item.price_from"></span>
                                    </p>
                                </div>
                            </a>
                        </li>
                    </template>
                </ul>
            </div>
```

- [ ] **Step 7: Build + test + kiểm tra tay**

Run: `npm run build && php artisan test`
Expected: PASS. Trên trình duyệt: mở ô tìm kiếm, gõ "con" → sau ~0,25s hiện danh sách; bấm một dòng → sang trang sự kiện.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/SearchSuggestionController.php routes/web.php resources/js/app.js resources/views/layouts/site.blade.php tests/Feature/SearchSuggestionTest.php
git commit -m "feat: add live search suggestions to the global search modal"
```

---

### Task 4: Số điện thoại + ảnh đại diện trong hồ sơ

**Files:**
- Modify: `app/Http/Requests/ProfileUpdateRequest.php`
- Modify: `app/Http/Controllers/ProfileController.php` (method `update`)
- Modify: `app/Models/User.php`
- Modify: `resources/views/profile/partials/update-profile-information-form.blade.php`
- Test: `tests/Feature/ProfileAvatarTest.php`

**Interfaces:**
- Produces: `$user->avatar_url` (`?string`: URL ảnh trên disk public, URL ngoài nếu `avatar` là URL, hoặc `null`)
- Consumes: `Comment::toPublicArray()` hiển thị `avatar_url` (Task 1)

- [ ] **Step 1: Viết test thất bại `tests/Feature/ProfileAvatarTest.php`**

```php
<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('public'));

function profilePayload($user, array $overrides = []): array
{
    return array_merge(['name' => $user->name, 'email' => $user->email], $overrides);
}

test('a user uploads an avatar and sets a phone number', function () {
    $user = createCustomer();

    $this->actingAs($user)
        ->patch(route('profile.update'), profilePayload($user, [
            'phone' => '0912 345 678',
            'avatar' => UploadedFile::fake()->image('me.png', 200, 200),
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();
    expect($user->phone)->toBe('0912 345 678')
        ->and($user->avatar)->toStartWith('avatars/')
        ->and($user->avatar_url)->toContain('/storage/avatars/');
    Storage::disk('public')->assertExists($user->avatar);
});

test('uploading a new avatar deletes the old file', function () {
    Storage::disk('public')->put('avatars/old.png', 'x');
    $user = createCustomer(['avatar' => 'avatars/old.png']);

    $this->actingAs($user)->patch(route('profile.update'), profilePayload($user, [
        'avatar' => UploadedFile::fake()->image('new.png'),
    ]));

    Storage::disk('public')->assertMissing('avatars/old.png');
});

test('saving the profile without a file keeps the current avatar', function () {
    Storage::disk('public')->put('avatars/keep.png', 'x');
    $user = createCustomer(['avatar' => 'avatars/keep.png']);

    $this->actingAs($user)->patch(route('profile.update'), profilePayload($user, ['name' => 'Tên mới']));

    expect($user->fresh()->avatar)->toBe('avatars/keep.png');
    Storage::disk('public')->assertExists('avatars/keep.png');
});

test('avatar must be a small image and phone must look like a phone number', function () {
    $user = createCustomer();

    $this->actingAs($user)->patch(route('profile.update'), profilePayload($user, [
        'avatar' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf'),
        'phone' => 'gọi tôi nhé',
    ]))->assertSessionHasErrors(['avatar', 'phone']);

    $this->actingAs($user)->patch(route('profile.update'), profilePayload($user, [
        'avatar' => UploadedFile::fake()->image('big.jpg')->size(2048),
    ]))->assertSessionHasErrors('avatar');
});
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=ProfileAvatarTest`
Expected: FAIL (field `phone`/`avatar` chưa được xử lý).

- [ ] **Step 3: `ProfileUpdateRequest::rules()` thêm 2 rule**

```php
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9 +().-]{8,20}$/'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
```

và thêm method:

```php
    public function attributes(): array
    {
        return ['phone' => 'số điện thoại', 'avatar' => 'ảnh đại diện'];
    }
```

- [ ] **Step 4: `ProfileController@update`**

Thêm `use Illuminate\Support\Facades\Storage;` và `use Illuminate\Support\Str;`. Thay dòng
`$request->user()->fill($request->validated());` bằng:

```php
        $user = $request->user();
        $user->fill($request->safe()->except('avatar'));

        if ($request->hasFile('avatar')) {
            if ($user->avatar && ! Str::startsWith($user->avatar, ['http://', 'https://'])) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }
```

và đổi các dòng phía sau đang dùng `$request->user()` trong method này sang `$user` (giữ nguyên logic reset `email_verified_at` và `save()`).

- [ ] **Step 5: Accessor trong `app/Models/User.php`**

Thêm `use Illuminate\Support\Facades\Storage;` và:

```php
    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }

        return Storage::disk('public')->url($this->avatar);
    }
```

- [ ] **Step 6: Form hồ sơ**

Trong `resources/views/profile/partials/update-profile-information-form.blade.php`:
1. Thẻ `<form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">` thêm `enctype="multipart/form-data"`.
2. Ngay trước `<div class="flex items-center gap-4">` (khối nút Save) chèn:

```blade
        <div>
            <x-input-label for="phone" value="Số điện thoại" />
            <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $user->phone)" autocomplete="tel" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <div>
            <x-input-label for="avatar" value="Ảnh đại diện" />
            <div class="mt-2 flex items-center gap-4">
                @if ($user->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="Ảnh đại diện" class="w-16 h-16 rounded-full object-cover border border-black/10">
                @else
                    <div class="w-16 h-16 rounded-full bg-[#F5F5DC] border border-black/10 flex items-center justify-center font-black text-lg">
                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                    </div>
                @endif
                <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" class="text-sm">
            </div>
            <p class="mt-1 text-xs text-gray-500">JPG, PNG hoặc WEBP, tối đa 1MB.</p>
            <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
        </div>
```

- [ ] **Step 7: Chạy test**

Run: `php artisan test`
Expected: PASS toàn bộ (kể cả `ProfileTest` của Breeze).

- [ ] **Step 8: Commit**

```bash
git add app/Http/Requests/ProfileUpdateRequest.php app/Http/Controllers/ProfileController.php app/Models/User.php resources/views/profile/partials/update-profile-information-form.blade.php tests/Feature/ProfileAvatarTest.php
git commit -m "feat: let users update phone number and avatar"
```

---

### Task 5: Tài liệu + PR

- [ ] **Step 1:** Trong `docs/architecture.md` mục "Tiến độ hoàn thành" thêm:
  `- **Tương tác AJAX (Plan 4)**: bình luận thêm/xóa không tải lại (policy chủ bình luận hoặc admin), gợi ý tìm kiếm theo từng ký tự, cập nhật SĐT + ảnh đại diện.`

- [ ] **Step 2: Test + commit + PR**

```bash
php artisan test
git add docs/architecture.md
git commit -m "docs: record plan 4 progress"
git push -u origin feature/interaction-ajax
gh pr create --base main --title "Plan 4: Bình luận AJAX, gợi ý tìm kiếm, avatar" --body "Theo docs/superpowers/plans/2026-09-21-p4-interaction-ajax.md. Output php artisan test: <dán vào đây>"
```
