<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AdvertisementGroups\Pages;

use App\Filament\Admin\Pages\ModulePlaceholder;
use App\Filament\Admin\Resources\AdvertisementGroups\AdvertisementGroupResource;
use App\Models\AdvertisementGroup;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

final class CreateAdvertisementGroup extends CreateRecord
{
    protected static string $resource = AdvertisementGroupResource::class;

    protected static bool $canCreateAnother = false;

    protected bool $redirectToBindDevice = false;

    /**
     * @var array<string, list<int>>|null
     */
    protected ?array $pendingSlotSelections = null;

    public function getTitle(): string|Htmlable
    {
        return 'Create advertisement group';
    }

    public function defaultForm(Schema $schema): Schema
    {
        $schema = parent::defaultForm($schema);

        return $schema->columns(1);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $pulled = AdvertisementGroupResource::pullSlotSelectionsFromFormData($data);
        $this->pendingSlotSelections = $pulled['slots'];

        return $pulled['data'];
    }

    protected function afterCreate(): void
    {
        /** @var AdvertisementGroup $record */
        $record = $this->getRecord();
        if ($this->pendingSlotSelections !== null) {
            $record->syncAllAdvertisementSlots($this->pendingSlotSelections);
            $this->pendingSlotSelections = null;
        }
    }

    protected function getRedirectUrl(): string
    {
        if ($this->redirectToBindDevice) {
            $this->redirectToBindDevice = false;

            return ModulePlaceholder::getUrl().'?label='.rawurlencode('Bind device');
        }

        return parent::getRedirectUrl();
    }

    /**
     * @return array<Action|ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Create'),
            Action::make('createAndBindDevice')
                ->label('Create and bind device')
                ->color('success')
                ->action('submitCreateAndBindDevice'),
            $this->getCancelFormAction(),
        ];
    }

    public function submitCreateAndBindDevice(): void
    {
        $this->redirectToBindDevice = true;

        try {
            $this->create(another: false);
        } finally {
            if ($this->redirectToBindDevice) {
                $this->redirectToBindDevice = false;
            }
        }
    }
}
