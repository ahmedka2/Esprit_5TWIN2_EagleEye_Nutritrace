<?php

namespace App\Http\Requests;

use App\Enums\CertificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCertificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255', 'unique:certifications,nom'],
            'type' => ['required', Rule::enum(CertificationType::class)],
            'organisme' => ['required', 'string', 'max:255'],
        ];
    }
}