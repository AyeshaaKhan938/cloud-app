<?php

declare(strict_types=1);

namespace App\Services;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

/**
 * Form schema for each collection-account payment gateway (admin modals).
 */
final class PaymentGatewayFormBuilder
{
    /**
     * Credential keys that must be non-empty for a gateway to count as configured.
     *
     * @return list<string>
     */
    public static function requiredCredentialKeys(string $gatewaySlug): array
    {
        return match ($gatewaySlug) {
            'paypal' => ['client_id', 'secret'],
            'cashu' => ['service_name', 'merchant_id', 'encryption_key', 'merchant_display_name'],
            'paymentwall' => ['public_key', 'private_key'],
            'gccpay' => ['client_id', 'merchant_id', 'gcc_key', 'gcc_key_string'],
            'barion' => ['receiving_account', 'key', 'authorization_amount', 'currency'],
            'click' => ['service_id', 'merchant_id', 'key', 'merchant_user_id'],
            'payme' => ['login_name', 'login_password', 'merchant_number', 'merchant_key'],
            'xendit' => ['business_id', 'key', 'webhook_verification_token'],
            'ageaptpay' => ['api_key', 'key_string', 'ageapt_token'],
            'deuna' => ['point_of_sale', 'api_key', 'api_key_string'],
            'oppa' => ['mode', 'shopId'],
            'razorpay' => ['apiKey', 'apiSecret'],
            default => throw new InvalidArgumentException("Unknown payment gateway: {$gatewaySlug}"),
        };
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public static function payloadIsComplete(string $gatewaySlug, ?array $payload): bool
    {
        if ($payload === null) {
            return false;
        }

        foreach (self::requiredCredentialKeys($gatewaySlug) as $key) {
            $value = $payload[$key] ?? null;
            if ($value === null) {
                return false;
            }
            if (is_string($value) && trim($value) === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<Field>
     */
    public static function schemaFor(string $gatewaySlug): array
    {
        return match ($gatewaySlug) {
            'paypal' => [
                TextInput::make('client_id')
                    ->label('Client ID')
                    ->required()
                    ->placeholder('Please enter Client ID'),
                TextInput::make('secret')
                    ->label('Secret')
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->required()
                    ->placeholder('Please enter Secret'),
                Placeholder::make('paypal_note')
                    ->label('Note')
                    ->content(new HtmlString(
                        '<div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100">'
                        .'<strong>Note:</strong> How to obtain PayPal developer keys: Register PayPal developer account > Create application > Obtain Client ID and Secret. '
                        .'<a href="https://developer.paypal.com/" target="_blank" rel="noopener noreferrer" class="font-medium text-primary-600 underline dark:text-primary-400">https://developer.paypal.com/</a>'
                        .'</div>'
                    )),
            ],
            'cashu' => [
                TextInput::make('service_name')
                    ->label('Service Name')
                    ->required()
                    ->placeholder('Please enter service name.'),
                TextInput::make('merchant_id')
                    ->label('Merchant ID')
                    ->required()
                    ->placeholder('Please enter merchant ID.'),
                TextInput::make('encryption_key')
                    ->label('Encryption Key')
                    ->required()
                    ->placeholder('Please enter the encryption key.'),
                TextInput::make('merchant_display_name')
                    ->label('Merchant Display Name')
                    ->required()
                    ->placeholder('Please enter merchant display name.'),
            ],
            'paymentwall' => [
                TextInput::make('public_key')
                    ->label('Public Key')
                    ->required()
                    ->placeholder('Please enter public key'),
                TextInput::make('private_key')
                    ->label('Private Key')
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->required()
                    ->placeholder('Please enter private key'),
            ],
            'gccpay' => [
                TextInput::make('client_id')
                    ->label('Client ID')
                    ->required()
                    ->placeholder('Please enter client ID'),
                TextInput::make('merchant_id')
                    ->label('Merchant ID')
                    ->required()
                    ->placeholder('Please enter merchant ID'),
                TextInput::make('gcc_key')
                    ->label('GCC Key')
                    ->required()
                    ->placeholder('Please enter GCC Key'),
                TextInput::make('gcc_key_string')
                    ->label('GCC Key String')
                    ->required()
                    ->placeholder('Please enter GCC Key String'),
            ],
            'barion' => [
                TextInput::make('receiving_account')
                    ->label('Receiving Account')
                    ->required()
                    ->placeholder('Please enter receiving account'),
                TextInput::make('key')
                    ->label('Key')
                    ->required()
                    ->placeholder('Please enter key'),
                TextInput::make('authorization_amount')
                    ->label('Authorization Amount')
                    ->required()
                    ->placeholder('Please enter authorization amount'),
                Select::make('currency')
                    ->label('Currency')
                    ->required()
                    ->placeholder('Please select currency')
                    ->options([
                        'USD' => 'USD',
                        'EUR' => 'EUR',
                        'HUF' => 'HUF',
                        'GBP' => 'GBP',
                    ]),
            ],
            'click' => [
                TextInput::make('service_id')
                    ->label('Service ID')
                    ->required()
                    ->placeholder('Please enter service ID'),
                TextInput::make('merchant_id')
                    ->label('Merchant ID')
                    ->required()
                    ->placeholder('Please enter merchant ID'),
                TextInput::make('key')
                    ->label('Key')
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->required()
                    ->placeholder('Please enter key'),
                TextInput::make('merchant_user_id')
                    ->label('Merchant User ID')
                    ->required()
                    ->placeholder('Please enter merchant user ID'),
            ],
            'payme' => [
                TextInput::make('login_name')
                    ->label('Login Name')
                    ->required()
                    ->placeholder('Please enter login name.'),
                TextInput::make('login_password')
                    ->label('Login Password')
                    ->password()
                    ->revealable()
                    ->autocomplete('current-password')
                    ->required()
                    ->placeholder('Please enter login password.'),
                TextInput::make('merchant_number')
                    ->label('Merchant Number')
                    ->required()
                    ->placeholder('Please enter merchant number.'),
                TextInput::make('merchant_key')
                    ->label('Merchant Key')
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->required()
                    ->placeholder('Please enter merchant key.'),
            ],
            'xendit' => [
                TextInput::make('business_id')
                    ->label('Business ID')
                    ->required()
                    ->placeholder('Please enter business ID'),
                TextInput::make('key')
                    ->label('Key')
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->required()
                    ->placeholder('Please enter key'),
                TextInput::make('webhook_verification_token')
                    ->label('Webhook Verification Token')
                    ->required()
                    ->placeholder('Please enter Webhook verification token'),
            ],
            'ageaptpay' => [
                TextInput::make('api_key')
                    ->label('API Key')
                    ->required()
                    ->placeholder('Please enter API key'),
                TextInput::make('key_string')
                    ->label('Key String')
                    ->required()
                    ->placeholder('Please enter key string'),
                TextInput::make('ageapt_token')
                    ->label('AGEAPT Token')
                    ->required()
                    ->placeholder('Please enter AGEAPT token'),
            ],
            'deuna' => [
                TextInput::make('point_of_sale')
                    ->label('Point of Sale')
                    ->required()
                    ->placeholder('Please enter point of sale'),
                TextInput::make('api_key')
                    ->label('API Key')
                    ->required()
                    ->placeholder('Please enter API key'),
                TextInput::make('api_key_string')
                    ->label('API Key String')
                    ->required()
                    ->placeholder('Please enter API key string'),
            ],
            'oppa' => [
                Radio::make('mode')
                    ->label('Mode')
                    ->options([
                        'sandbox' => 'sandbox',
                        'live' => 'live',
                    ])
                    ->required(),
                TextInput::make('shopId')
                    ->label('shopId')
                    ->required()
                    ->placeholder('Please enter shopId'),
                Placeholder::make('oppa_base_url')
                    ->label('')
                    ->content(new HtmlString(
                        '<div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100">'
                        .'Base URL (read-only): —'
                        .'</div>'
                    )),
            ],
            'razorpay' => [
                TextInput::make('apiKey')
                    ->label('apiKey')
                    ->required()
                    ->placeholder('Please enter apiKey'),
                TextInput::make('apiSecret')
                    ->label('apiSecret')
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->required()
                    ->placeholder('Please enter apiSecret'),
            ],
            default => throw new InvalidArgumentException("Unknown payment gateway: {$gatewaySlug}"),
        };
    }
}
