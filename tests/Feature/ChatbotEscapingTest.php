<?php

use Illuminate\Support\Facades\Http;

it('escapes raw html returned by the AI model', function () {
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [
                    ['text' => "**Xin chào** <img src=x onerror=alert(1)>\n\n[link](javascript:alert(1))"],
                ]],
            ]],
        ], 200),
    ]);
    config(['services.gemini.api_key' => 'fake-test-key']);

    $reply = $this->postJson(route('chatbot.message'), ['message' => 'Xin chào'])
        ->assertOk()
        ->json('reply');

    expect($reply)
        ->toContain('<strong>Xin chào</strong>')
        ->toContain('&lt;img')
        ->not->toContain('<img')
        ->not->toContain('javascript:');
});

it('login page escapes the old email value and hides demo accounts outside local', function () {
    $this->withSession(['_old_input' => ['email' => "x'});alert(1);//"]])
        ->get(route('login'))
        ->assertOk()
        ->assertDontSee("emailVal: 'x'", false)
        ->assertDontSee('fillAccount(\'admin@ticketbox.vn\'', false);
});
