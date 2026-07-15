<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AdvertisementGroups\Pages;

use App\Enums\AdvertisementGroupSlot;
use App\Filament\Admin\Resources\AdvertisementGroups\AdvertisementGroupResource;
use App\Models\AdvertisementGroup;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

final class EditAdvertisementGroup extends EditRecord
{
    protected static string $resource = AdvertisementGroupResource::class;

    /**
     * @var array<string, list<int>>|null
     */
    protected ?array $pendingSlotSelections = null;

    public function getTitle(): string|Htmlable
    {
        return 'Edit advertisement group';
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
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var AdvertisementGroup $record */
        $record = $this->getRecord();

        foreach (AdvertisementGroupSlot::cases() as $slot) {
            $data[$slot->formFieldKey()] = $record->advertisementIdsForSlot($slot);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $pulled = AdvertisementGroupResource::pullSlotSelectionsFromFormData($data);
        $this->pendingSlotSelections = $pulled['slots'];

        return $pulled['data'];
    }

    protected function afterSave(): void
    {
        /** @var AdvertisementGroup $record */
        $record = $this->getRecord();
        if ($this->pendingSlotSelections !== null) {
            $record->syncAllAdvertisementSlots($this->pendingSlotSelections);
            $this->pendingSlotSelections = null;
        }
    }
}
