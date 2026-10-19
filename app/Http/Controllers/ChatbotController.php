<?php

namespace App\Http\Controllers;

use App\Services\GeminiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function sendMessage(Request $request, GeminiChatService $gemini): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array|max:10',
        ]);

        // Chưa có GEMINI_API_KEY: báo client dùng bộ trả lời có sẵn (FAQ) thay vì hiện lỗi cấu hình.
        if (! $gemini->isConfigured()) {
            return response()->json(['reply' => null, 'fallback' => true]);
        }

        $replyHtml = $gemini->reply(
            $validated['message'],
            $validated['history'] ?? []
        );

        return response()->json([
            'reply' => $replyHtml,
        ]);
    }
}
