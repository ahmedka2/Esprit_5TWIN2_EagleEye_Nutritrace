<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveTransformationPlanRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'intent' => ['required', 'in:draft,validate'],
            'matiere_premiere_id' => ['required', 'exists:matieres_premieres,id'],
            'quantite_utilisee' => ['required', 'numeric', 'min:0.01'],
            'unite' => ['required', 'string', 'max:10'],
            'product_name' => ['required', 'string', 'max:255'],
            'expected_yield_percent' => ['required', 'numeric', 'between:0.01,100'],
            'suggested_shelf_life_days' => ['required', 'integer', 'between:1,3650'],
            'planned_start' => ['required', 'date'],
            'steps' => ['required', 'array', 'min:1', 'max:15'],
            'steps.*.name' => ['required', 'string', 'max:255'],
            'steps.*.description' => ['nullable', 'string', 'max:2000'],
            'steps.*.duration_min' => ['required', 'integer', 'min:1'],
            'steps.*.target_temperature' => ['nullable', 'numeric', 'between:-30,250'],
            'steps.*.is_critical' => ['required', 'boolean'],
        ];
    }
}
