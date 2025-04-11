<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'يجب أن يكون بريدًا إلكترونيًا صالحًا',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'يجب أن تكون كلمة المرور على الأقل 6 أحرف',
        ];
    }
}
