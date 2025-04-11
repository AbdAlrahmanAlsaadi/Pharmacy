<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FavoriteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'medicine_id' => 'required'
        ];
    }
}
