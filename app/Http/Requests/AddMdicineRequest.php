<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddMdicineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scientific_name' => 'required|string|max:255',
            'commercial_name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'manufacturer' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0.01',
            'expiry_date' => [
                'required',
                'date',
                'after:today',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                        $fail('يجب أن يكون تاريخ الانتهاء بالتنسيق YYYY-MM-DD');
                    }
                }
            ],
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'expiry_date' => $this->formatDate($this->expiry_date)
        ]);
    }

    protected function formatDate($date)
    {
        try {
            return \Carbon\Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            return $date; // إذا كان التنسيق صحيحاً بالفعل
        }
    }}
