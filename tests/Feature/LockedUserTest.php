<?php

test('an active user can keep browsing', function () {
    $this->actingAs(createCustomer())
        ->get(route('profile.edit'))
        ->assertOk();
});

test('a user locked mid-session is logged out on the next request', function () {
    $user = createCustomer();
    $this->actingAs($user);
    $user->update(['is_active' => false]);

    $this->get(route('profile.edit'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a locked user gets a 403 json response on ajax requests', function () {
    $user = createCustomer();
    $this->actingAs($user);
    $user->update(['is_active' => false]);

    $this->getJson(route('profile.edit'))->assertForbidden();
});
