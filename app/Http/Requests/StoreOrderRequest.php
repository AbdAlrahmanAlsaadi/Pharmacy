<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
public function authorize()
{
return true;
}

public function rules()
{
return [
'items' => 'required|array|min:1',
'items.*.medicine_id' => 'required',
'items.*.quantity' => 'required|integer|min:1',
'notes' => 'nullable|string|max:500'
];
}
}
