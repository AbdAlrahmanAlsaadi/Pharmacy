<?php
namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role; // أضف هذا الاستيراد إذا كنت تحتاجه


class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        $user = $this->authService->registerUser($request->validated());

        return response()->json([
            'message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني',
            'user_id' => $user->id
        ], 201);
    }

public function verifyOtp(VerifyOtpRequest $request)
{
    try {
        $result = $this->authService->verifyOtp(
            $request->validated()['user_id'],
            $request->validated()['otp_code']
        );

        return response()->json([
            'message' => 'تم التحقق بنجاح وتم تفعيل حسابك',
            'user' => $result['user'],
            'token' => $result['token']
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'message' => $e->getMessage()
        ], 400);
    }
}

public function login(LoginRequest $request)
    {
        try {
            $result = $this->authService->login(
                $request->email,
                $request->password
            );

            return response()->json([
                'message' => 'تم تسجيل الدخول بنجاح',
                'user' => $result['user'],
                'token' => $result['token']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 401);
        }


}
public function logout(Request $request)
{
    try {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'حدث خطأ أثناء محاولة تسجيل الخروج'
        ], 500);
    }
}

public function add()
{
    /** @var \App\Models\User $user */
    $user = Auth::user();

    if ($user && $user->hasRole("admin")) {
        return response()->json(['message' => 'مرحباً أيها المسؤول']);
    }

    return response()->json(['message' => 'غير مصرح لك بالوصول'], 403);
}


}

