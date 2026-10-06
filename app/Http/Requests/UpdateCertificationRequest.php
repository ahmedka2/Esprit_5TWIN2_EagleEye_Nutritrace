<?php

namespace App\Http\Requests;

use App\Enums\CertificationType;
use App\Models\Certification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCertificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $certification = $this->route('certification');
        $certificationId = $certification instanceof Certification ? $certification->getKey() : $certification;

        return [
            'nom' => ['required', 'string', 'max:255', Rule::unique('certifications', 'nom')->ignore($certificationId)],
            'type' => ['required', Rule::enum(CertificationType::class)],
            'organisme' => ['required', 'string', 'max:255'],
        ];
    }
}