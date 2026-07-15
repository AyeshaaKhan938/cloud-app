<?php

declare(strict_types=1);

namespace Tests\Feature\Storage;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class PublicStorageLinkTest extends TestCase
{
    public function test_public_storage_entry_exists_for_filament_public_disk_urls(): void
    {
        $publicStorage = public_path('storage');

        $this->assertTrue(
            is_link($publicStorage) || File::exists($publicStorage),
            'Missing public/storage. Product images use Storage disk "public" (files live in storage/app/public). '
            .'Run: php artisan storage:link'
        );
    }
}
