<?php

declare(strict_types=1);

namespace App\Filament\Admin\Concerns;

use Illuminate\Database\Eloquent\Model;

trait EnrichesGlobalSearch
{
    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return static::globalSearchAttributes();
    }

    /**
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return static::globalSearchDetails($record);
    }

    /**
     * @return list<string>
     */
    abstract protected static function globalSearchAttributes(): array;

    /**
     * @return array<string, string>
     */
    abstract protected static function globalSearchDetails(Model $record): array;
}
