# Phase 0 — Project Setup Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Scaffold the Laravel 11 application into the existing (non-empty) project
directory, wire up auth (Breeze), roles (spatie/laravel-permission), a protected admin
area, Pest, and the agent-memory files (CLAUDE.md/AGENTS.md/docs), producing a
committed, working "empty shell" that later feature plans build on.

**Architecture:** Standard Laravel 11 app (Blade + Vite), MySQL via Laragon, roles via
`spatie/laravel-permission` with a `role:admin` middleware gate on `/admin/*`. Executed
directly by Claude (not handed to Codex) per the spec, to avoid multiple people
bootstrapping the skeleton in parallel and producing merge conflicts.

**Tech Stack:** PHP 8.3, Laravel 11, MySQL 8.4, Breeze (blade stack, Vite), Pest,
spatie/laravel-permission, Node 22 (Laragon-bundled).

**Spec:** `docs/superpowers/specs/2026-09-07-ticket-booking-platform-design.md`

## Global Constraints

- Laravel version: `^11.0` (spec section 2)
- PHP: 8.3 (Laragon-bundled, path below — not on shell PATH by default)
- DB: MySQL, database name `doanmnm_ticket` (new, created in Task 2)
- Roles: exactly two spatie roles, `admin` and `user` (spec section 2)
- Test framework: Pest, not PHPUnit (spec section 2) — must be installed explicitly,
  `composer create-project` does not set this up by default
- No `.env` file is ever committed (only `.env.example`) — verify `.gitignore` covers it
- **Deviation from spec section 2:** frontend stack is Breeze's default **Tailwind CSS +
  Vite**, not "Blade + Bootstrap 5" as originally written in the spec. Reason: Breeze's
  blade stack ships Tailwind by default; mixing it with Bootstrap would fight over
  utility-class conventions for no benefit, and Node 22 is confirmed available via
  Laragon so the Vite build has no missing dependency. Logged in `docs/architecture.md`
  (Task 6) — flag this to the user for a nod before Phase 1 UI work begins.
- Every commit message ends with `Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>`
- **Do not run `git push` until Task 7's explicit confirmation step** — every earlier
  commit stays local only

**Tool paths (Laragon, not on default shell PATH):**
```
PHP:      /c/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe
Composer: /c/laragon/bin/composer/composer.bat
MySQL:    /c/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql.exe
Node:     /c/laragon/bin/nodejs/node-v22/node.exe
npm:      /c/laragon/bin/nodejs/node-v22/npm.cmd
```
Every bash step below is self-contained and prefixes the PATH export — copy-paste safe
regardless of shell state.

---

### Task 1: PATH setup, branch rename, Laravel skeleton scaffold, Pest install

**Files:**
- Create: entire Laravel 11 skeleton (`app/`, `bootstrap/`, `config/`, `database/`,
  `public/`, `resources/`, `routes/`, `storage/`, `tests/`, `artisan`, `composer.json`,
  `.env.example`, `.gitignore`, `vite.config.js`, `package.json`, ...)
- Modify: none (repo currently has only `docs/`, `skill_agent.txt`)

**Interfaces:**
- Consumes: nothing (first task)
- Produces: working `artisan` CLI, `composer.json`, Pest test runner — every later task
  runs commands through these

- [ ] **Step 1: Permanently add Laragon tool paths to the user PATH (safe, append-only)**

Run in PowerShell:
```powershell
$paths = @(
  'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64',
  'C:\laragon\bin\composer',
  'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin',
  'C:\laragon\bin\nodejs\node-v22'
)
$current = [Environment]::GetEnvironmentVariable('Path', 'User')
$missing = $paths | Where-Object { $current -notlike "*$_*" }
if ($missing) {
  $new = ($current.TrimEnd(';') + ';' + ($missing -join ';'))
  [Environment]::SetEnvironmentVariable('Path', $new, 'User')
  Write-Host "Added: $($missing -join ', ')"
} else {
  Write-Host "Already present, no change made."
}
```
Expected: prints either the paths added, or "Already present". This only appends — it
never removes or overwrites existing PATH entries. New terminals (not this session) will
pick it up.

- [ ] **Step 2: Rename default branch `master` to `main`**

```bash
cd "c:/laragon/www/DoAnMNM" && git branch -m master main && git branch
```
Expected: output shows `* main`.

- [ ] **Step 3: Scaffold Laravel into a temp subfolder (target dir is non-empty, so
  `composer create-project .` would fail)**

```bash
export PATH="/c/laragon/bin/php/php-8.3.30-Win32-vs16-x64:/c/laragon/bin/composer:$PATH"
cd "c:/laragon/www/DoAnMNM"
composer create-project laravel/laravel _scaffold "^11.0" --prefer-dist --no-interaction
rm -rf _scaffold/.git
```
Expected: `_scaffold/artisan` exists; `_scaffold/.git` does not exist after the rm.

- [ ] **Step 4: Merge the scaffold into the project root, then remove the temp folder**

```bash
cd "c:/laragon/www/DoAnMNM"
cp -rn _scaffold/. .
rm -rf _scaffold
ls artisan composer.json .gitignore
```
Expected: `ls` lists all three files with no "No such file" error. `-n` (no-clobber)
guarantees `docs/`, `skill_agent.txt` were never touched.

- [ ] **Step 5: Install Pest (spec requires Pest, not the PHPUnit default)**

```bash
export PATH="/c/laragon/bin/php/php-8.3.30-Win32-vs16-x64:/c/laragon/bin/composer:$PATH"
cd "c:/laragon/www/DoAnMNM"
composer remove phpunit/phpunit --dev --no-interaction
composer require pestphp/pest pestphp/pest-plugin-laravel --dev --with-all-dependencies --no-interaction
php artisan pest:install --no-interaction
```
Expected: `tests/Pest.php` is created; `php artisan pest:install` exits 0.

- [ ] **Step 6: Verify the skeleton boots and run the default test suite**

```bash
export PATH="/c/laragon/bin/php/php-8.3.30-Win32-vs16-x64:$PATH"
cd "c:/laragon/www/DoAnMNM"
php artisan --version
php artisan test
```
Expected: `Laravel Framework 11.x.x`; test run shows at least the example tests (DB-backed
ones may fail here — no `.env`/DB yet, that's Task 2. Only confirm Pest itself runs and
reports results, not that every test passes).

- [ ] **Step 7: Commit the skeleton**

```bash
cd "c:/laragon/www/DoAnMNM"
git add -A
git commit -m "$(cat <<'EOF'
feat: scaffold Laravel 11 skeleton with Pest

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```
Expected: commit succeeds; `git log --oneline` shows two commits total.

---

### Task 2: Environment config, MySQL database, default migrations

**Files:**
- Create: `.env` (from `.env.example`, never committed)
- Modify: `.env` (DB_* values)

**Interfaces:**
- Consumes: `artisan` from Task 1
- Produces: a reachable MySQL database (`doanmnm_ticket`) with default Laravel tables —
  every later migration task depends on this connection working

- [ ] **Step 1: Create the `.env` file and app key**

```bash
export PATH="/c/laragon/bin/php/php-8.3.30-Win32-vs16-x64:$PATH"
cd "c:/laragon/www/DoAnMNM"
cp .env.example .env
php artisan key:generate
grep APP_KEY .env
```
Expected: `APP_KEY=base64:...` is non-empty.

- [ ] **Step 2: Create the MySQL database**

```bash
export PATH="/c/laragon/bin/mysql/mysql-8.4.3-winx64/bin:$PATH"
mysql -u root -e "CREATE DATABASE IF NOT EXISTS doanmnm_ticket CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -e "SHOW DATABASES LIKE 'doanmnm_ticket';"
```
Expected: second command prints `doanmnm_ticket`. (Laragon's default MySQL root user has
no password — if this fails with an access error, check Laragon's MySQL root password in
`C:\laragon\etc\mysql\my.ini` and adjust the `-u root` call accordingly, e.g. add
`-proot`.)

- [ ] **Step 3: Point `.env` at the new database**

Edit `.env`, replace the `DB_*` block with:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=doanmnm_ticket
DB_USERNAME=root
DB_PASSWORD=
```

- [ ] **Step 4: Run the default migrations**

```bash
export PATH="/c/laragon/bin/php/php-8.3.30-Win32-vs16-x64:$PATH"
cd "c:/laragon/www/DoAnMNM"
php artisan migrate
```
Expected: output lists `users`, `cache`, `jobs` (and related) migrations as `DONE`, no
errors.

- [ ] **Step 5: Commit `.env.example` if it changed (never commit `.env`)**

```bash
cd "c:/laragon/www/DoAnMNM"
git status --short
```
Expected: `.env` shows as untracked-but-ignored (not listed, or listed only if
`.gitignore` is wrong — if `.env` appears in `git status`, STOP and fix `.gitignore`
before continuing, do not commit it). If `.env.example` is unchanged, skip the commit.

---

### Task 3: Laravel Breeze (blade + Vite)

**Files:**
- Create: `resources/views/auth/*`, `resources/views/layouts/app.blade.php`,
  `resources/views/layouts/navigation.blade.php`, `resources/js/app.js`,
  `resources/css/app.css`, Breeze auth routes in `routes/auth.php`
- Modify: `routes/web.php` (Breeze appends the `require __DIR__.'/auth.php';` line)

**Interfaces:**
- Consumes: working DB connection from Task 2 (Breeze's registration flow writes to
  `users`)
- Produces: `layouts/app.blade.php` and `layouts/navigation.blade.php` — Task 5 extends
  these rather than creating a new layout

- [ ] **Step 1: Install Breeze and scaffold the blade stack**

```bash
export PATH="/c/laragon/bin/php/php-8.3.30-Win32-vs16-x64:/c/laragon/bin/composer:$PATH"
cd "c:/laragon/www/DoAnMNM"
composer require laravel/breeze --dev --no-interaction
php artisan breeze:install blade --no-interaction
```
Expected: command completes; `resources/views/auth/login.blade.php` now exists.

- [ ] **Step 2: Install JS deps and build assets once, to confirm the pipeline works**

```bash
export PATH="/c/laragon/bin/nodejs/node-v22:$PATH"
cd "c:/laragon/www/DoAnMNM"
npm install
npm run build
```
Expected: `npm run build` finishes with a `public/build/manifest.json` written, no
errors.

- [ ] **Step 3: Verify auth routes are registered**

```bash
export PATH="/c/laragon/bin/php/php-8.3.30-Win32-vs16-x64:$PATH"
cd "c:/laragon/www/DoAnMNM"
php artisan route:list | grep -E "login|register"
```
Expected: lines for `GET login`, `POST login`, `GET register`, `POST register`.

- [ ] **Step 4: Commit**

```bash
cd "c:/laragon/www/DoAnMNM"
git add -A
git commit -m "$(cat <<'EOF'
feat: install Laravel Breeze auth (blade stack)

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```
Expected: commit succeeds.

---

### Task 4: spatie/laravel-permission + role seeder

**Files:**
- Modify: `app/Models/User.php` (add `HasRoles` trait)
- Modify: `bootstrap/app.php` (register `role` middleware alias)
- Create: `database/seeders/RoleSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php` (call `RoleSeeder`)
- Test: `tests/Feature/RoleSeederTest.php`

**Interfaces:**
- Consumes: `HasRoles`-ready `User` model is required by Task 5's middleware test
- Produces: two DB rows in `roles` (`admin`, `user`); `role:admin` middleware alias
  usable in any route file from Task 5 onward

- [ ] **Step 1: Install the package and publish its migration**

```bash
export PATH="/c/laragon/bin/php/php-8.3.30-Win32-vs16-x64:/c/laragon/bin/composer:$PATH"
cd "c:/laragon/www/DoAnMNM"
composer require spatie/laravel-permission --no-interaction
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --no-interaction
php artisan migrate
```
Expected: migration output includes a line creating `permission_tables` (creates
`roles`, `permissions`, `model_has_roles`, `model_has_permissions`,
`role_has_permissions`).

- [ ] **Step 2: Add the `HasRoles` trait to `User`**

In `app/Models/User.php`, add the import and trait:
```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;
    // ...existing code...
}
```

- [ ] **Step 3: Register the `role` middleware alias**

In `bootstrap/app.php`, inside the `->withMiddleware(function (Middleware $middleware) {`
block, add:
```php
$middleware->alias([
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
]);
```

- [ ] **Step 4: Write the failing test for the seeder**

Create `tests/Feature/RoleSeederTest.php`:
```php
<?php

use Spatie\Permission\Models\Role;

it('seeds exactly the admin and user roles', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    expect(Role::pluck('name')->sort()->values()->all())->toBe(['admin', 'user']);
});
```

- [ ] **Step 5: Run the test to verify it fails**

```bash
export PATH="/c/laragon/bin/php/php-8.3.30-Win32-vs16-x64:$PATH"
cd "c:/laragon/www/DoAnMNM"
php artisan test --filter=RoleSeederTest
```
Expected: FAIL — `Class "Database\Seeders\RoleSeeder" not found`.

- [ ] **Step 6: Create the seeder**

Create `database/seeders/RoleSeeder.php`:
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    }
}
```

In `database/seeders/DatabaseSeeder.php`, inside `run()`, add:
```php
$this->call(RoleSeeder::class);
```

- [ ] **Step 7: Run the test to verify it passes**

```bash
export PATH="/c/laragon/bin/php/php-8.3.30-Win32-vs16-x64:$PATH"
cd "c:/laragon/www/DoAnMNM"
php artisan test --filter=RoleSeederTest
```
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
cd "c:/laragon/www/DoAnMNM"
git add -A
git commit -m "$(cat <<'EOF'
feat: add spatie/laravel-permission with admin/user role seeder

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```
Expected: commit succeeds.

---

### Task 5: Protected admin area (`role:admin` middleware) + nav update

**Files:**
- Create: `app/Http/Controllers/Admin/DashboardController.php`
- Create: `resources/views/admin/dashboard.blade.php`
- Modify: `routes/web.php` (add `admin` route group)
- Modify: `resources/views/layouts/navigation.blade.php` (show "Admin" link only for
  admins)
- Test: `tests/Feature/AdminAccessTest.php`

**Interfaces:**
- Consumes: `role:admin` middleware alias and `RoleSeeder` from Task 4
- Produces: `/admin` route pattern and `Admin\` controller namespace — every future
  admin CRUD task (Phase 1) adds routes under this same group

- [ ] **Step 1: Write the failing access-control test**

Create `tests/Feature/AdminAccessTest.php`:
```php
<?php

use App\Models\User;

it('blocks a plain user from the admin dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('allows an admin into the admin dashboard', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/admin')->assertOk();
});

it('redirects a guest to login', function () {
    $this->get('/admin')->assertRedirect('/login');
});
```

- [ ] **Step 2: Run the tests to verify they fail**

```bash
export PATH="/c/laragon/bin/php/php-8.3.30-Win32-vs16-x64:$PATH"
cd "c:/laragon/www/DoAnMNM"
php artisan test --filter=AdminAccessTest
```
Expected: FAIL — route `/admin` does not exist (404 instead of the expected statuses).

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/Admin/DashboardController.php`:
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard');
    }
}
```

- [ ] **Step 4: Create the view**

Create `resources/views/admin/dashboard.blade.php`:
```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                Khu vực quản trị — Phase 1 sẽ thêm CRUD phim/sự kiện, suất chiếu tại đây.
            </div>
        </div>
    </div>
</x-app-layout>
```

- [ ] **Step 5: Register the route**

In `routes/web.php`, add:
```php
use App\Http\Controllers\Admin\DashboardController;

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
});
```

- [ ] **Step 6: Run the tests to verify they pass**

```bash
export PATH="/c/laragon/bin/php/php-8.3.30-Win32-vs16-x64:$PATH"
cd "c:/laragon/www/DoAnMNM"
php artisan test --filter=AdminAccessTest
```
Expected: all 3 tests PASS.

- [ ] **Step 7: Show the Admin nav link only to admins**

In `resources/views/layouts/navigation.blade.php`, inside the existing nav-links block
(next to the "Dashboard" link Breeze already generated), add:
```blade
@can('') @endcan
@if(auth()->user()?->hasRole('admin'))
    <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
        {{ __('Admin') }}
    </x-nav-link>
@endif
```
(Remove the empty `@can`/`@endcan` line above — it was left in by mistake; the real
content is just the `@if` block.)

- [ ] **Step 8: Commit**

```bash
cd "c:/laragon/www/DoAnMNM"
git add -A
git commit -m "$(cat <<'EOF'
feat: add role-protected admin dashboard shell

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```
Expected: commit succeeds.

---

### Task 6: Agent memory files (CLAUDE.md, AGENTS.md, docs/architecture.md, docs/report/)

**Files:**
- Create: `CLAUDE.md`, `AGENTS.md`, `docs/architecture.md`, `docs/report/.gitkeep`

**Interfaces:**
- Consumes: nothing code-related — pure documentation
- Produces: the persistent-memory files every future Claude/Codex session and every
  human teammate reads first

- [ ] **Step 1: Create `CLAUDE.md`**

```markdown
# CLAUDE.md

Bạn là **supervisor** của đồ án "Website Quản lý đặt vé" (môn Mã nguồn mở).

## Vai trò
- Viết plan chi tiết (skill `writing-plans`) cho từng feature vào `docs/superpowers/plans/`.
- Review PR do Codex hoặc thành viên mở, bằng skill `code-review` rồi `security-review`
  trước khi merge.
- Tự sửa khi Codex không tự sửa được sau 1-2 vòng feedback.
- KHÔNG tự ý mở rộng phạm vi ngoài spec (`docs/superpowers/specs/`) mà không hỏi user.

## Lệnh hay dùng
- Test: `php artisan test` (Pest)
- Migrate: `php artisan migrate` (fresh: `php artisan migrate:fresh --seed`)
- Route list: `php artisan route:list`
- Build assets: `npm run build` (dev: `npm run dev`)
- PATH Laragon (nếu shell mới chưa có): xem `docs/superpowers/plans/2026-09-07-phase0-project-setup.md`
  mục "Tool paths"

## Quy ước code
- Roles: chỉ `admin` và `user` (spatie/laravel-permission), không thêm role khác khi
  chưa hỏi.
- Mọi route khu quản trị nằm dưới `admin.*` + middleware `role:admin`.
- Test framework: Pest, không dùng PHPUnit thuần.
- Trước khi báo "xong": chạy `php artisan test` và dán kết quả, không suy đoán.

## Tài liệu liên quan
- Spec gốc: `docs/superpowers/specs/2026-09-07-ticket-booking-platform-design.md`
- Kiến trúc sống: `docs/architecture.md`
- Plan đang chạy: `docs/superpowers/plans/`
```

- [ ] **Step 2: Create `AGENTS.md`**

```markdown
# AGENTS.md

Bạn (Codex) là **executor** của đồ án "Website Quản lý đặt vé" (môn Mã nguồn mở).

## Vai trò
- Implement đúng theo plan trong `docs/superpowers/plans/<file>.md` mà Claude đã viết —
  không tự thêm tính năng ngoài plan.
- Mỗi plan = một branch riêng: `feature/<module>-<mô tả-ngắn>`, không commit thẳng vào
  `main`.
- Mở PR khi xong, chờ Claude review. Sửa theo comment, không tranh luận lan man — nếu
  không đồng ý với review, giải thích lý do kỹ thuật cụ thể trong PR.

## Lệnh hay dùng
- Test: `php artisan test` (Pest — không dùng PHPUnit thuần)
- Migrate: `php artisan migrate`
- PATH Laragon nếu shell chưa có, xem file plan Phase 0, mục "Tool paths".

## Quy tắc
- KHÔNG chạy `git push --force` lên `main`.
- KHÔNG sửa migration đã merge vào `main` — tạo migration mới nếu cần đổi schema.
- KHÔNG đổi role/middleware/tech stack đã chốt trong spec mà không hỏi qua Claude/user.
- Mỗi task trong plan kết thúc bằng: chạy test liên quan + commit — không gộp nhiều task
  vào 1 commit.

## Tài liệu liên quan
- Spec gốc: `docs/superpowers/specs/2026-09-07-ticket-booking-platform-design.md`
- Kiến trúc sống: `docs/architecture.md`
```

- [ ] **Step 3: Create `docs/architecture.md`**

```markdown
# Kiến trúc hệ thống (living doc)

Cập nhật file này mỗi khi kiến trúc thay đổi so với spec gốc. Nguồn sự thật ban đầu:
`docs/superpowers/specs/2026-09-07-ticket-booking-platform-design.md`.

## Trạng thái hiện tại
- Phase 0 (setup nền tảng): xem
  `docs/superpowers/plans/2026-09-07-phase0-project-setup.md`

## Thay đổi so với spec gốc

- **2026-09-07 — Frontend CSS**: spec gốc ghi "Blade + Bootstrap 5", nhưng Phase 0 dùng
  **Tailwind CSS + Vite** (mặc định của Laravel Breeze blade stack) thay vì Bootstrap.
  Lý do: Breeze tự dựng Tailwind sẵn, trộn thêm Bootstrap sẽ xung đột quy ước class mà
  không lợi gì; Node 22 đã có sẵn qua Laragon nên Vite build không thiếu phụ thuộc.

## ERD
(Sẽ chèn hình/link ERD ở đây khi Phase 1 hoàn thành schema đầy đủ — xem spec mục 3 cho
bản nháp text hiện tại.)
```

- [ ] **Step 4: Create the report deliverables folder**

```bash
cd "c:/laragon/www/DoAnMNM"
mkdir -p docs/report
touch docs/report/.gitkeep
```

- [ ] **Step 5: Commit**

```bash
cd "c:/laragon/www/DoAnMNM"
git add CLAUDE.md AGENTS.md docs/architecture.md docs/report/.gitkeep
git commit -m "$(cat <<'EOF'
docs: add CLAUDE.md/AGENTS.md agent memory files and architecture doc

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```
Expected: commit succeeds.

---

### Task 7: Final verification and first push (confirmation gate)

**Files:** none — verification and a git operation only.

**Interfaces:**
- Consumes: everything from Tasks 1-6
- Produces: the pushed `main` branch on GitHub that Codex and teammates clone from

- [ ] **Step 1: Full verification pass**

```bash
export PATH="/c/laragon/bin/php/php-8.3.30-Win32-vs16-x64:$PATH"
cd "c:/laragon/www/DoAnMNM"
php artisan test
php artisan route:list
```
Expected: all tests PASS (no failures); route list shows `login`, `register`,
`admin.dashboard`, and Breeze's default routes.

- [ ] **Step 2: STOP — confirm with the user before pushing**

Do not run `git push` automatically. Show the user `git log --oneline` and
`git remote -v`, and explicitly ask for a yes before pushing — this is the first time
anything leaves the local machine, and it's the shared branch the whole team will clone.

- [ ] **Step 3: Push (only after explicit user confirmation)**

```bash
cd "c:/laragon/www/DoAnMNM"
git push -u origin main
```
Expected: push succeeds; branch `main` now exists on
`github.com/whatthefoxsay-nhp/course-project-of-MaNguonMo`.
