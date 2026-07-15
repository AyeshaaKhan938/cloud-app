<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PaymentGatewayFormBuilder;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\HtmlString;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Locks modal field contracts for collection-account gateways to the reference UI (screenshots).
 */
final class PaymentGatewayFormBuilderModalContractTest extends TestCase
{
    #[Test]
    public function paypal_modal_matches_reference_fields_and_note(): void
    {
        $schema = PaymentGatewayFormBuilder::schemaFor('paypal');
        $this->assertCount(3, $schema);

        $this->assertInstanceOf(TextInput::class, $schema[0]);
        $this->assertSame('client_id', $schema[0]->getName());
        $this->assertSame('Client ID', $schema[0]->getLabel());
        $this->assertSame('Please enter Client ID', $schema[0]->getPlaceholder());
        $this->assertTrue($schema[0]->isRequired());
        $this->assertFalse($schema[0]->isPassword());

        $this->assertInstanceOf(TextInput::class, $schema[1]);
        $this->assertSame('secret', $schema[1]->getName());
        $this->assertSame('Secret', $schema[1]->getLabel());
        $this->assertSame('Please enter Secret', $schema[1]->getPlaceholder());
        $this->assertTrue($schema[1]->isRequired());
        $this->assertTrue($schema[1]->isPassword());
        $this->assertSame('new-password', $schema[1]->getAutocomplete());

        $this->assertInstanceOf(Placeholder::class, $schema[2]);
        $this->assertSame('paypal_note', $schema[2]->getName());
        $this->assertSame('Note', $schema[2]->getLabel());
        $content = $schema[2]->getContent();
        $html = $content instanceof HtmlString ? $content->toHtml() : (string) $content;
        $this->assertStringContainsString('<strong>Note:</strong>', $html);
        $this->assertStringContainsString('How to obtain PayPal developer keys', $html);
        $this->assertStringContainsString('https://developer.paypal.com/', $html);
    }

    #[Test]
    public function ageaptpay_modal_matches_reference_fields(): void
    {
        $schema = PaymentGatewayFormBuilder::schemaFor('ageaptpay');
        $this->assertCount(3, $schema);

        $this->assertInstanceOf(TextInput::class, $schema[0]);
        $this->assertSame('api_key', $schema[0]->getName());
        $this->assertSame('API Key', $schema[0]->getLabel());
        $this->assertSame('Please enter API key', $schema[0]->getPlaceholder());
        $this->assertTrue($schema[0]->isRequired());

        $this->assertInstanceOf(TextInput::class, $schema[1]);
        $this->assertSame('key_string', $schema[1]->getName());
        $this->assertSame('Key String', $schema[1]->getLabel());
        $this->assertSame('Please enter key string', $schema[1]->getPlaceholder());
        $this->assertTrue($schema[1]->isRequired());

        $this->assertInstanceOf(TextInput::class, $schema[2]);
        $this->assertSame('ageapt_token', $schema[2]->getName());
        $this->assertSame('AGEAPT Token', $schema[2]->getLabel());
        $this->assertSame('Please enter AGEAPT token', $schema[2]->getPlaceholder());
        $this->assertTrue($schema[2]->isRequired());
    }

    #[Test]
    public function barion_modal_matches_reference_fields(): void
    {
        $schema = PaymentGatewayFormBuilder::schemaFor('barion');
        $this->assertCount(4, $schema);

        $this->assertInstanceOf(TextInput::class, $schema[0]);
        $this->assertSame('receiving_account', $schema[0]->getName());
        $this->assertSame('Receiving Account', $schema[0]->getLabel());
        $this->assertSame('Please enter receiving account', $schema[0]->getPlaceholder());
        $this->assertTrue($schema[0]->isRequired());

        $this->assertInstanceOf(TextInput::class, $schema[1]);
        $this->assertSame('key', $schema[1]->getName());
        $this->assertSame('Key', $schema[1]->getLabel());
        $this->assertSame('Please enter key', $schema[1]->getPlaceholder());
        $this->assertTrue($schema[1]->isRequired());
        $this->assertFalse($schema[1]->isPassword(), 'Barion Key must be a plain text field per reference UI.');

        $this->assertInstanceOf(TextInput::class, $schema[2]);
        $this->assertSame('authorization_amount', $schema[2]->getName());
        $this->assertSame('Authorization Amount', $schema[2]->getLabel());
        $this->assertSame('Please enter authorization amount', $schema[2]->getPlaceholder());
        $this->assertTrue($schema[2]->isRequired());

        $this->assertInstanceOf(Select::class, $schema[3]);
        $this->assertSame('currency', $schema[3]->getName());
        $this->assertSame('Currency', $schema[3]->getLabel());
        $this->assertSame('Please select currency', $schema[3]->getPlaceholder());
        $this->assertTrue($schema[3]->isRequired());
        $this->assertSame(
            ['USD' => 'USD', 'EUR' => 'EUR', 'HUF' => 'HUF', 'GBP' => 'GBP'],
            $schema[3]->getOptions()
        );
    }
}
