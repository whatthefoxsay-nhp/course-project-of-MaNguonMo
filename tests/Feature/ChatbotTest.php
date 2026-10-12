<?php

use App\Services\GeminiChatService;
use Illuminate\Support\Facades\Http;

it('validates request payload for chatbot message', function () {
    $response = $this->postJson(route('chatbot.message'), [
        'message' => '',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['message']);
});

it('returns a reply from gemini chat service', function () {
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Xin chào! Tôi là TicketBot AI.'],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    config(['services.gemini.api_key' => 'fake-test-key']);

    $response = $this->postJson(route('chatbot.message'), [
        'message' => 'Xin chào',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['reply'])
        ->assertJsonFragment(['reply' => "<p>Xin chào! Tôi là TicketBot AI.</p>\n"]);
});

it('handles gemini api failure gracefully', function () {
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response('Server Error', 500),
    ]);

    config(['services.gemini.api_key' => 'fake-test-key']);

    $response = $this->postJson(route('chatbot.message'), [
        'message' => 'Sự kiện có gì hot?',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['reply']);

    expect($response->json('reply'))->toContain('máy chủ trợ lý AI tạm thời gián đoạn');
});

it('sends strict scope instructions and low temperature in gemini payload', function () {
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Xin lỗi bạn, tôi là trợ lý ảo chỉ chuyên hỗ trợ dịch vụ của TicketBox.'],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    config(['services.gemini.api_key' => 'fake-test-key']);

    $response = $this->postJson(route('chatbot.message'), [
        'message' => 'Viết cho tôi bài thơ về tình yêu',
    ]);

    $response->assertOk();

    Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
        $data = $request->data();
        $sysInstruction = $data['system_instruction']['parts'][0]['text'] ?? '';
        $temperature = $data['generationConfig']['temperature'] ?? null;

        return str_contains($sysInstruction, 'NGUYÊN TẮC GIỚI HẠN PHẠM VI')
            && str_contains($sysInstruction, 'TUYỆT ĐỐI TỪ CHỐI TẤT CẢ CÁC CÂU HỎI NGOÀI PHẠM VI')
            && $temperature === 0.2;
    });
});

