<?php

use App\Models\Category;
use App\Models\Event;

test('admin sees the category list', function () {
    Category::factory()->create(['name' => 'Concert']);

    $this->actingAs(createAdmin())
        ->get(route('admin.categories.index'))
        ->assertOk()
        ->assertSee('Concert');
});

test('admin creates a category with an auto slug', function () {
    $this->actingAs(createAdmin())
        ->post(route('admin.categories.store'), ['name' => 'Hòa Nhạc', 'description' => 'Giao hưởng'])
        ->assertRedirect(route('admin.categories.index'))
        ->assertSessionHas('success');

    expect(Category::where('slug', 'hoa-nhac')->exists())->toBeTrue();
});

test('category name must be unique', function () {
    Category::factory()->create(['name' => 'Concert']);

    $this->actingAs(createAdmin())
        ->post(route('admin.categories.store'), ['name' => 'Concert'])
        ->assertSessionHasErrors('name');
});

test('admin updates a category', function () {
    $category = Category::factory()->create(['name' => 'Cũ']);

    $this->actingAs(createAdmin())
        ->put(route('admin.categories.update', $category), ['name' => 'Mới'])
        ->assertRedirect(route('admin.categories.index'));

    expect($category->fresh()->name)->toBe('Mới');
});

test('admin deletes an empty category', function () {
    $category = Category::factory()->create();

    $this->actingAs(createAdmin())
        ->delete(route('admin.categories.destroy', $category))
        ->assertSessionHas('success');

    expect(Category::find($category->id))->toBeNull();
});

test('a category that still has events cannot be deleted', function () {
    $category = Category::factory()->create();
    Event::factory()->for($category)->create();

    $this->actingAs(createAdmin())
        ->delete(route('admin.categories.destroy', $category))
        ->assertSessionHas('error');

    expect(Category::find($category->id))->not->toBeNull();
});

test('a regular user cannot manage categories', function () {
    $this->actingAs(createCustomer())
        ->get(route('admin.categories.index'))
        ->assertForbidden();
});

test('successful category creation displays success alert and toast on index screen', function () {
    $this->actingAs(createAdmin())
        ->followingRedirects()
        ->post(route('admin.categories.store'), [
            'name' => 'Kịch Truyền Cảm Hứng',
            'description' => 'Mô tả thể loại kịch',
        ])
        ->assertOk()
        ->assertSee('Thao tác thành công')
        ->assertSee('Đã tạo danh mục [Kịch Truyền Cảm Hứng]')
        ->assertSee('Kịch Truyền Cảm Hứng');
});

test('failed category creation due to duplicate name displays error alert and toast on screen', function () {
    Category::factory()->create(['name' => 'Concert Đêm']);

    $this->actingAs(createAdmin())
        ->from(route('admin.categories.index'))
        ->followingRedirects()
        ->post(route('admin.categories.store'), [
            'name' => 'Concert Đêm',
        ])
        ->assertOk()
        ->assertSee('Thao tác thất bại')
        ->assertSee('Tên danh mục đã tồn tại trong hệ thống.')
        ->assertSee('open: true'); // modal giữ mở khi có lỗi
});

test('failed category creation due to empty name displays validation error on screen', function () {
    $this->actingAs(createAdmin())
        ->from(route('admin.categories.index'))
        ->followingRedirects()
        ->post(route('admin.categories.store'), [
            'name' => '',
        ])
        ->assertOk()
        ->assertSee('Thao tác thất bại')
        ->assertSee('Tên danh mục không được để trống.')
        ->assertSee('open: true');
});

test('successful category update displays success alert on index screen', function () {
    $category = Category::factory()->create(['name' => 'Tên Cũ']);

    $this->actingAs(createAdmin())
        ->followingRedirects()
        ->put(route('admin.categories.update', $category), [
            'name' => 'Tên Mới Đã Sửa',
        ])
        ->assertOk()
        ->assertSee('Thao tác thành công')
        ->assertSee('Đã cập nhật danh mục [Tên Mới Đã Sửa]');
});

test('failed category update due to duplicate name displays error alert on screen', function () {
    Category::factory()->create(['name' => 'Danh Mục A']);
    $categoryB = Category::factory()->create(['name' => 'Danh Mục B']);

    $this->actingAs(createAdmin())
        ->from(route('admin.categories.index'))
        ->followingRedirects()
        ->put(route('admin.categories.update', $categoryB), [
            'name' => 'Danh Mục A',
        ])
        ->assertOk()
        ->assertSee('Thao tác thất bại')
        ->assertSee('Tên danh mục đã tồn tại trong hệ thống.');
});

test('successful category deletion displays success alert on index screen', function () {
    $category = Category::factory()->create(['name' => 'Danh Mục Xóa']);

    $this->actingAs(createAdmin())
        ->followingRedirects()
        ->delete(route('admin.categories.destroy', $category))
        ->assertOk()
        ->assertSee('Thao tác thành công')
        ->assertSee('Đã xóa danh mục.');
});

test('failed category deletion with existing events displays error alert on screen', function () {
    $category = Category::factory()->create(['name' => 'Hội Chợ Triển Lãm']);
    Event::factory()->for($category)->create(['title' => 'Triển Lãm Tech 2026']);

    $this->actingAs(createAdmin())
        ->from(route('admin.categories.index'))
        ->followingRedirects()
        ->delete(route('admin.categories.destroy', $category))
        ->assertOk()
        ->assertSee('Thao tác không thành công')
        ->assertSee('Danh mục [Hội Chợ Triển Lãm] vẫn còn sự kiện, không thể xóa.');
});

test('failed bulk deletion with empty selection displays error alert on screen', function () {
    $this->actingAs(createAdmin())
        ->from(route('admin.categories.index'))
        ->followingRedirects()
        ->post(route('admin.categories.bulk-destroy'), [
            'ids' => [],
        ])
        ->assertOk()
        ->assertSee('Thao tác không thành công')
        ->assertSee('Vui lòng chọn ít nhất một danh mục để xóa.');
});

test('bulk deletion with protected categories displays informative notice on screen', function () {
    $catEmpty = Category::factory()->create(['name' => 'Danh Mục Trống']);
    $catHasEvent = Category::factory()->create(['name' => 'Danh Mục Có Event']);
    Event::factory()->for($catHasEvent)->create();

    $this->actingAs(createAdmin())
        ->followingRedirects()
        ->post(route('admin.categories.bulk-destroy'), [
            'ids' => [$catEmpty->id, $catHasEvent->id],
        ])
        ->assertOk()
        ->assertSee('Thao tác thành công')
        ->assertSee('Đã xóa 1 danh mục hợp lệ. Không thể xóa: Danh Mục Có Event (do vẫn còn sự kiện).');
});

