<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class AuthService
{
    public function throttle(array $data): ?array
    {
        $maxAttempts = 5;
        $ip = $data['ip'] ?? '0.0.0.0';
        $key = $ip . '|' . $data['name'];

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            return [
                'success' => false,
                'message' => "Too many attempts. Try again after {$seconds} seconds.",
            ];
        }

        RateLimiter::hit($key, 120);
        return null;
    }

    public function generateSecurityKey(User $user): array
    {
        $plainKey = Str::uuid();

        $user->update([
            'key' => Hash::make($plainKey),
        ]);

        return [
            'success' => true,
            'message' => 'Security key generated successfully.',
            'key'     => $plainKey,
        ];
    }

    public function register(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'phone'    => $data['phone'],
            'password' => Hash::make($data['password']),
            'key'      => null,
        ]);

        $token = $user->createToken('auth_token', ['*'], now()->addDay(1))->plainTextToken;

        DB::connection('supabase')->table('notifications')->insert([
            'user_id' => $user->id,
            'title'   => 'Welcome to the App!',
            'body' => 'Thank you, ' . $user->name . ', for registering. We are excited to have you on board.',
            'created_at' => now(),
            'updated_at' => now(),
            'read_at'    => null,
        ]);

        return [
            'success' => true,
            'message' => 'Registration successful.',
            'user'    => $this->formatUserResponse($user),
            'key'     => $user->key,
            'token'   => $token,
            'telegram_bot_url' => "https://t.me/FlashDriverKareemBot?start=" . $user->name,
        ];
    }

    public function login(array $credentials): array
    {
        $throttleResponse = $this->throttle($credentials);
        if ($throttleResponse) {
            return $throttleResponse;
        }

        $user = User::where('name', $credentials['name'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return [
                'success' => false,
                'message' => 'Invalid name or password.',
            ];
        }

        $ip = $credentials['ip'] ?? '0.0.0.0';
        RateLimiter::clear($ip . '|' . $credentials['name']);

        if ($user->generated_password !== null) {
            $user->generated_password = null;
            $user->save();
        }

        $days = !empty($credentials['remember_me']) ? 30 : 1;

        $token = $user->createToken('auth_token', ['*'], now()->addDays($days))->plainTextToken;

        DB::connection('supabase')->table('notifications')->insert([
        'user_id' => $user->id,
        'title'   => 'Login Successfully',
        'body' => 'Welcome back, ' . $user->name . '!',
        'created_at' => now(),
        'updated_at' => now(),
        'read_at'    => null,
        ]);

        Http::post("https://api.telegram.org/bot8508486868:AAHKYvqCmtgBuUAlSDsVFqc2kVODt4FJqV0/sendMessage", [
        'chat_id' => $user->telegram_chat_id,
        'text' => "Security Key: " . $this->generateSecurityKey($user)['key'],
        ]);

        return [
            'success' => true,
            'message' => $user->id,
            'user'    => $this->formatUserResponse($user),
            'role'    => $user->role,
            'token'   => $token,
        ];
    }

    public function resetPassword(array $data): array
    {

        $throttleResponse = $this->throttle($data);
        if ($throttleResponse) {
            return $throttleResponse;
        }

        $user = User::where('name', $data['name'])->first();

        if (!$user || !Hash::check($data['key'], $user->key)) {
            return [
                'success' => false,
                'message' => 'If user exists, reset instructions will be processed.',
            ];
        }

        $generatedPassword = Str::random(25);

        $user->update([
            'password'           => Hash::make($generatedPassword),
            'generated_password' => $generatedPassword,
        ]);

        try {
        DB::connection('supabase')->table('notifications')->insert([
        'user_id' => $user->id,
        'title'   => 'Generated Temporary Password',
        'body' => $generatedPassword,
        'created_at' => now(),
        'updated_at' => now(),
        'read_at'    => null,
        ]);
        } catch (\Exception $e) {
        return [
        'success' => true,
        'message' => 'Temporary password generated, but failed to log notification.',
        ];
        }

        Http::post("https://api.telegram.org/bot8508486868:AAHKYvqCmtgBuUAlSDsVFqc2kVODt4FJqV0/sendMessage", [
        'chat_id' => $user->telegram_chat_id,
        'text' => "Temporary password: " . $generatedPassword
        ]);


        $user->tokens()->delete();

        return [
            'success' => true,
            'message' => 'Temporary password generated.',
        ];
    }

    public function changePassword(array $data): array
    {
        $user = Auth::user();

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        DB::connection('supabase')->table('notifications')->insert([
            'user_id' => $user->id,
            'title'   => 'Password Changed',
            'body' =>  $user->name . ' has been changed password successfully.',
            'created_at' => now(),
            'updated_at' => now(),
            'read_at'    => null,
        ]);


        Http::post("https://api.telegram.org/bot8508486868:AAHKYvqCmtgBuUAlSDsVFqc2kVODt4FJqV0/sendMessage", [
        'chat_id' => $user->telegram_chat_id,
        'text' => "Security Key: " . $this->generateSecurityKey($user)['key'],
        ]);

        return [
            'success' => true,
            'message' => 'Password changed successfully.',
        ];
    }

    public function logout(User $user): array
    {
        $user->tokens()->delete();

        DB::connection('supabase')->table('notifications')->insert([
            'user_id' => $user->id,
            'title'   => 'Logged Out',
            'body' =>  $user->name . ' has been logged out successfully.',
            'created_at' => now(),
            'updated_at' => now(),
            'read_at'    => null,
        ]);

        return [
            'success' => true,
            'message' => 'Logged out successfully.',
        ];
    }

    public function updateProfile(array $data): array
    {
        $user = Auth::user();

         $plainKey = Str::uuid();

        $user->update([
            'name'  => $data['name']  ?? $user->name,
            'phone' => $data['phone'] ?? $user->phone,
        ]);

        DB::connection('supabase')->table('notifications')->insert([
            'user_id' => $user->id,
            'title'   => 'Profile Updated',
            'body' =>  $user->name . ' has been updated profile successfully.',
            'created_at' => now(),
            'updated_at' => now(),
            'read_at'    => null,
        ]);

        Http::post("https://api.telegram.org/bot8508486868:AAHKYvqCmtgBuUAlSDsVFqc2kVODt4FJqV0/sendMessage", [
        'chat_id' => $user->telegram_chat_id,
        'text' => "Security Key: " . $this->generateSecurityKey($user)['key'],
        ]);

        return [
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user'    => $this->formatUserResponse($user),
        ];
    }

    public function getUser(User $user): array
    {
        return [
            'success' => true,
            'user'    => $this->formatUserResponse($user),
        ];
    }

    private function formatUserResponse(User $user): array
    {
        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'phone' => $user->phone,
            'role'  => $user->role,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}

