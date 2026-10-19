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

        $replyHtml = $gemini->reply(
            $validated['message'],
            $validated['history'] ?? []
        );

        return response()->json([
            'reply' => $replyHtml,
        ]);
    }
}
