<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateTransformationPlanRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'original_request' => ['required', 'string', 'min:10', 'max:5000'],
            'planned_start' => ['required', 'date', 'after_or_equal:now'],
        ];
    }
}
