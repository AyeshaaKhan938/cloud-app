<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

final class ScratchCardRedeemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32'],
            'machine_no' => ['required', 'string', 'max:64'],
            'age_verification_session_id' => ['nullable', 'uuid'],
        ];
    }
}
