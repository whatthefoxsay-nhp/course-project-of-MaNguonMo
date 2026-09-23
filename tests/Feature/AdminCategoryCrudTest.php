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
