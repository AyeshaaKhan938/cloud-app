<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'homepage_logo_path',
    'homepage_icon_path',
    'homepage_promotion_image_path',
    'homepage_background_image_path',
    'device_startup_animation_path',
    'homepage_bottom_logo_path',
    'device_bottom_logo_path',
    'default_webpage_title',
    'homepage_logo_jump_link',
    'device_default_ad_eliminates_logo',
    'homepage_footer_html',
])]
final class BrandSetting extends Model
{
    private static ?self $currentCache = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'device_default_ad_eliminates_logo' => 'boolean',
        ];
    }

    public static function current(): self
    {
        if (self::$currentCache instanceof self) {
            return self::$currentCache;
        }

        $row = self::query()->first();
        if ($row === null) {
            $row = self::query()->create([
                'default_webpage_title' => 'VMFS USA Cloud',
                'homepage_logo_jump_link' => 'vmfsusa.com',
                'device_default_ad_eliminates_logo' => false,
                'homepage_footer_html' => '<p>2020-2026 VMFS USA™ All Rights Reserved</p>',
            ]);
        }

        return self::$currentCache = $row;
    }

    public static function forgetCurrentCache(): void
    {
        self::$currentCache = null;
    }
}
