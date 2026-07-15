<?php

declare(strict_types=1);

namespace App\Filament\Admin\GlobalSearch;

use App\Filament\Admin\Navigation\AdminNavigationSearch;
use Filament\GlobalSearch\GlobalSearchResults;
use Filament\GlobalSearch\Providers\Contracts\GlobalSearchProvider;
use Filament\GlobalSearch\Providers\DefaultGlobalSearchProvider;

final class VmfsGlobalSearchProvider implements GlobalSearchProvider
{
    public function getResults(string $query): ?GlobalSearchResults
    {
        $query = trim($query);

        if ($query === '') {
            return null;
        }

        $builder = GlobalSearchResults::make();

        $navigationResults = AdminNavigationSearch::resultsFor($query);

        if ($navigationResults->isNotEmpty()) {
            $builder->category('Pages & menu', $navigationResults);
        }

        $resourceResults = app(DefaultGlobalSearchProvider::class)->getResults($query);

        if ($resourceResults !== null) {
            foreach ($resourceResults->getCategories() as $category => $results) {
                $builder->category($category, $results);
            }
        }

        return $builder->getCategories()->isEmpty() ? null : $builder;
    }
}
