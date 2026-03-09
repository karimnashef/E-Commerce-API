<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TelegramController extends Controller
{
    public function webhook(Request $request)
    {
        $data = $request->all();

        if (isset($data['message'])) {
            $chatId = $data['message']['chat']['id'];

            $text = $data['message']['text'];
            $username = trim(str_replace("/start", "", $text));

            $user = User::where('name', $username)->first();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found.']);
            }
            if ($user) {
                $user->telegram_chat_id = $chatId;
                $user->save();
            }

            DB::connection('supabase')->table('notifications')
                ->insert([
                    'user_id' => $user->id,
                    'title' => 'Telegram Connection',
                    'body' => "User {$username} connected with chat ID: {$chatId}",
                    'created_at' => now(),
                    'updated_at' => now(),
                    'read_at' => null,
                ]);

            return response()->json(['status' => 'success', 'message' => 'Chat ID saved successfully.']);
        }
    }
}
