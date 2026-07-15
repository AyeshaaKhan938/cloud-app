<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KioskAppVersions\Pages;

use App\Filament\Admin\Resources\KioskAppVersions\KioskAppVersionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

final class ManageKioskAppVersions extends ManageRecords
{
    protected static string $resource = KioskAppVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Upload new version')
                ->mutateFormDataUsing(function (array $data): array {
                    // Convert the uploaded APK path into a public URL + size in bytes.
                    if (! empty($data['apk_url']) && is_string($data['apk_url'])) {
                        $relativePath = $data['apk_url'];

                        // Try to read the file size from local disk.
                        $absolutePath = storage_path('app/public/'.$relativePath);
                        if (is_file($absolutePath)) {
                            $data['apk_size_bytes'] = filesize($absolutePath);
                        }

                        // Convert the storage-relative path into a fully-qualified URL.
                        // (storage:link makes /storage/<path> serve from storage/app/public/<path>.)
                        $data['apk_url'] = rtrim(config('app.url'), '/').'/storage/'.ltrim($relativePath, '/');
                    }

                    return $data;
                }),
        ];
    }
}
