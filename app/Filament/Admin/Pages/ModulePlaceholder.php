<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

final class ModulePlaceholder extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.admin.pages.module-placeholder';

    public function getTitle(): string|Htmlable
    {
        $label = request()->query('label');

        if (is_string($label) && $label !== '') {
            return $label;
        }

        return 'Coming soon';
    }
}
