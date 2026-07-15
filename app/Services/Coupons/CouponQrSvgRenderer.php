<?php

declare(strict_types=1);

namespace App\Services\Coupons;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\QROptionsTrait;

final class CouponQrSvgRenderer
{
    public function render(string $payload): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            /** @see QROptionsTrait::$outputBase64 Defaults to true; must be false for inline HTML (otherwise a data: URI string is shown as text). */
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
            'scale' => 5,
            'imageTransparent' => true,
        ]);

        return (new QRCode($options))->render($payload);
    }
}
