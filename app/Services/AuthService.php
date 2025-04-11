<?php

namespace App\Services;

use App\Mail\SendOtp;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AuthService
{
    public function registerUser(array $data)
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password'])
            ]);

            $role = Role::findByName($data['role']);
            $user->assignRole($role);

        $otp = $this->generateOtp($user);
        $this->sendOtpEmail($user, $otp->code);

        return $user;
    });
}

private function generateOtp(User $user)
{
    Otp::where('user_id', $user->id)->delete();

    return Otp::create([
        'user_id' => $user->id,
        'code' => rand(100000, 999999),
        'expires_at' => now()->addMinutes(10),
    ]);
}

private function sendOtpEmail(User $user, $code)
{
    Mail::to($user->email)->send(new SendOtp($code));
}
public function verifyOtp($userId, $otpCode)
{
    return DB::transaction(function () use ($userId, $otpCode) {
        $otp = Otp::where('user_id', $userId)
                  ->where('code', $otpCode)
                  ->where('expires_at', '>', now())
                  ->first();

        if (!$otp) {
            throw new \Exception('رمز التحقق غير صحيح أو منتهي الصلاحية');
        }

        $user = User::findOrFail($userId);
        $user->email_verified_at = now();
        $user->save();

        $otp->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    });
}
public function login(string $email, string $password)
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['بيانات الاعتماد غير صحيحة.'],
            ]);
        }

        // $user->tokens()->delete();

        return [
            'user' => $user,
            'token' => $user->createToken('auth_token')->plainTextToken
        ];
    }

    /**
     * تسجيل الخروج (إلغاء التوكن)
     */
    public function logout(User $user)
    {
        //$user->currentAccessToken()->delete();

        // $user->tokens()->delete();
    }
}



