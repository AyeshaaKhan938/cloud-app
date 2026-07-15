<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

final class LegalPageController extends Controller
{
    public function privacy(): View
    {
        return view('legal.privacy', [
            'minAge' => (int) config('age_verification.min_age', 18),
            'documentRetentionHours' => (int) config('age_verification.document_retention_hours', 24),
            'provider' => config('age_verification.provider', 'local'),
            'supportEmail' => config('mail.from.address', 'support@vmfsusa.com'),
        ]);
    }

    public function terms(): View
    {
        return view('legal.terms', [
            'minAge' => (int) config('age_verification.min_age', 18),
            'supportEmail' => config('mail.from.address', 'support@vmfsusa.com'),
        ]);
    }
}
