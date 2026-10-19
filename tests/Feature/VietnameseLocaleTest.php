<?php

use Illuminate\Support\Facades\Validator;

test('the application defaults to vietnamese', function () {
    expect(app()->getLocale())->toBe('vi')
        ->and(config('app.fallback_locale'))->toBe('en');
});

test('validation messages are fully vietnamese', function () {
    $errors = Validator::make(
        ['email' => 'khong-phai-email', 'latitude' => '123'],
        ['name' => 'required', 'email' => 'email', 'latitude' => 'numeric|between:-90,90'],
    )->errors();

    expect($errors->first('name'))->toBe('Tên không được để trống.')
        ->and($errors->first('email'))->toBe('Email phải là địa chỉ email hợp lệ.')
        ->and($errors->first('latitude'))->toBe('Vĩ độ phải nằm trong khoảng từ -90 đến 90.');
});

test('custom attribute names from form requests are not mixed with english', function () {
    $this->actingAs(createAdmin())
        ->post(route('admin.categories.store'), ['name' => ''])
        ->assertSessionHasErrors('name');

    $message = session('errors')->first('name');
    expect($message)->not->toContain('The ')->and($message)->not->toContain('field');
});

test('framework strings and breeze pages are translated', function () {
    expect(__('pagination.next'))->toBe('Sau &raquo;')
        ->and(__('passwords.reset'))->toBe('Mật khẩu của bạn đã được đặt lại.')
        ->and(__('Showing'))->toBe('Hiển thị');

    $this->actingAs(createCustomer())
        ->get(route('password.confirm'))
        ->assertOk()
        ->assertSee('Vui lòng nhập lại mật khẩu để tiếp tục.')
        ->assertDontSee('This is a secure area');
});
