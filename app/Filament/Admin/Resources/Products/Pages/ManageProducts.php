<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Models\User;
use App\Services\Users\UserCloudScope;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Schema;

final class ManageProducts extends ManageRecords
{
    protected static string $resource = ProductResource::class;

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        return match (true) {
            $action instanceof CreateAction, $action instanceof EditAction => fn (Schema $schema): Schema => $this->form($schema->hasCustomColumns() ? $schema : $schema->columns(1)),
            $action instanceof ViewAction => fn (Schema $schema): Schema => $this->infolist($this->form($schema->hasCustomColumns() ? $schema : $schema->columns(1))),
            default => parent::getDefaultActionSchemaResolver($action),
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add')
                ->modalHeading('Add product')
                ->mutateFormDataUsing(function (array $data): array {
                    if (blank($data['sku'] ?? null)) {
                        $data['sku'] = 'PRD-'.now()->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
                    }

                    $user = auth()->user();

                    if ($user instanceof User && app(UserCloudScope::class)->requiresScoping($user)) {
                        $data['user_id'] = app(UserCloudScope::class)->accountOwner($user)->id;
                    }

                    return $data;
                }),
        ];
    }
}
