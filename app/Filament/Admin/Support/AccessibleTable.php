<?php

declare(strict_types=1);

namespace App\Filament\Admin\Support;

use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

final class AccessibleTable
{
    public static function apply(Table $table, string $searchPlaceholder): Table
    {
        $table
            ->searchPlaceholder($searchPlaceholder)
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->searchDebounce('400ms');

        if (count($table->getFilters()) > 0) {
            $table
                ->filtersLayout(FiltersLayout::AboveContentCollapsible)
                ->filtersFormColumns([
                    'default' => 1,
                    'sm' => 2,
                    'lg' => 3,
                ]);
        }

        return $table;
    }
}
