<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Wallet;

use App\Filament\Admin\Pages\CollectionAccountConfig;
use App\Models\PaymentCollectionConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class CollectionAccountConfigPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_collection_account_config_page(): void
    {
        $this->get(route('filament.admin.pages.collection-account-config'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_access_collection_account_config_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.pages.collection-account-config'))
            ->assertOk();
    }

    public function test_collection_account_config_page_shows_search_and_gateway_cards(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.pages.collection-account-config'))
            ->assertOk()
            ->assertSee('Search payment method', false)
            ->assertSee('Not configured', false)
            ->assertSee('Paypal Payment Config', false)
            ->assertSee('Configure now', false);
    }

    /**
     * @return array<string, array{0: string, 1: array<string, string>}>
     */
    public static function gatewaySavePayloadProvider(): array
    {
        return [
            'paypal' => ['paypal', [
                'client_id' => 'test-client-id',
                'secret' => 'test-secret',
            ]],
            'cashu' => ['cashu', [
                'service_name' => 'svc',
                'merchant_id' => 'mid',
                'encryption_key' => 'enc',
                'merchant_display_name' => 'Display',
            ]],
            'paymentwall' => ['paymentwall', [
                'public_key' => 'pk',
                'private_key' => 'sk',
            ]],
            'gccpay' => ['gccpay', [
                'client_id' => 'cid',
                'merchant_id' => 'mid',
                'gcc_key' => 'gk',
                'gcc_key_string' => 'gks',
            ]],
            'barion' => ['barion', [
                'receiving_account' => 'acc',
                'key' => 'key',
                'authorization_amount' => '100',
                'currency' => 'USD',
            ]],
            'click' => ['click', [
                'service_id' => 'sid',
                'merchant_id' => 'mid',
                'key' => 'k',
                'merchant_user_id' => 'uid',
            ]],
            'payme' => ['payme', [
                'login_name' => 'ln',
                'login_password' => 'pw',
                'merchant_number' => 'mn',
                'merchant_key' => 'mk',
            ]],
            'xendit' => ['xendit', [
                'business_id' => 'bid',
                'key' => 'xk',
                'webhook_verification_token' => 'wvt',
            ]],
            'ageaptpay' => ['ageaptpay', [
                'api_key' => 'ak',
                'key_string' => 'ks',
                'ageapt_token' => 'tok',
            ]],
            'deuna' => ['deuna', [
                'point_of_sale' => 'pos',
                'api_key' => 'ak',
                'api_key_string' => 'aks',
            ]],
            'oppa' => ['oppa', [
                'mode' => 'sandbox',
                'shopId' => 'shop-99',
            ]],
            'razorpay' => ['razorpay', [
                'apiKey' => 'rk',
                'apiSecret' => 'rs',
            ]],
        ];
    }

    #[DataProvider('gatewaySavePayloadProvider')]
    public function test_each_payment_gateway_can_be_saved_and_marked_configured(string $slug, array $payload): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CollectionAccountConfig::class)
            ->callAction('configureGateway', $payload, ['slug' => $slug]);

        $this->assertTrue(PaymentCollectionConfig::isConfigured($slug));
        $row = PaymentCollectionConfig::query()->where('gateway_slug', $slug)->first();
        $this->assertNotNull($row);
        $this->assertIsArray($row->payload);
        foreach ($payload as $key => $expected) {
            $this->assertSame($expected, $row->payload[$key] ?? null, "Key {$key} for {$slug}");
        }
    }
}
