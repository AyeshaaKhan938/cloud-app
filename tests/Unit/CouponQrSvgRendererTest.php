<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Coupons\CouponQrSvgRenderer;
use PHPUnit\Framework\TestCase;

final class CouponQrSvgRendererTest extends TestCase
{
    public function test_renders_inline_svg_markup_not_data_uri(): void
    {
        $svg = (new CouponQrSvgRenderer)->render('123456');

        $this->assertStringStartsWith('<svg', ltrim($svg));
        $this->assertStringNotContainsString('data:image', $svg);
        $this->assertStringNotContainsString('base64,', $svg);
    }
}
