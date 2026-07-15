<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UploadAgeVerificationDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'document' => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'document_type' => ['required', 'string', Rule::in(['drivers_license', 'id_card', 'passport'])],
        ];
    }
}
