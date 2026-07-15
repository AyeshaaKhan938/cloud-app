<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\RegistersForPlatformAdmins;
use App\Filament\Admin\Navigation\AdminNavigationGroups;
use App\Models\NotificationSetting;
use App\Services\Analytics\DailyMachineAnalyticsEmailService;
use App\Services\Kiosk\OperatorAlertEmailService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Throwable;
use UnitEnum;

/**
 * @property-read Schema $form
 */
final class NotificationConfiguration extends Page
{
    use CanUseDatabaseTransactions;
    use RegistersForPlatformAdmins;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $navigationLabel = 'Alerts & email settings';

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroups::System;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'notification-configuration';

    public function getSubheading(): ?string
    {
        return 'Configure email alerts, daily analytics delivery, and operator notifications.';
    }

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(self::canAccess(), 403);

        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $this->form->fill(NotificationSetting::current()->attributesToArray());
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();

            NotificationSetting::current()->update($data);
            NotificationSetting::forgetCurrentCache();
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $this->commitDatabaseTransaction();

        Notification::make()
            ->success()
            ->title('Saved')
            ->send();

        $this->fillForm();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->model(NotificationSetting::current())
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Notification channels')
                    ->description('Configure the email address that will receive system alerts.')
                    ->schema([
                        TextInput::make('notification_email')
                            ->label('Account notification email')
                            ->email()
                            ->maxLength(255)
                            ->nullable()
                            ->placeholder('alerts@yourcompany.com')
                            ->helperText('Active alerts are emailed here (every 15 minutes, or use “Send alert email now” above).'),
                    ]),

                Section::make('Alert types')
                    ->description('Enable or disable each type of system notification.')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Toggle::make('account_email_notification')
                                    ->label('Daily machine analytics email')
                                    ->helperText('Each morning (7:00 in the account owner\'s timezone), send a VMFS USA email with yesterday\'s items sold, sales revenue, and gross profit per machine.'),

                                Toggle::make('inventory_shortage_notification')
                                    ->label('Inventory shortage notification')
                                    ->helperText('Alert when machine slot stock falls below the configured minimum threshold.'),

                                Toggle::make('equipment_offline_notification')
                                    ->label('Equipment offline notification')
                                    ->helperText('Alert when a machine goes offline or stops responding.'),

                                Toggle::make('slot_failure_notification')
                                    ->label('Slot failure notification')
                                    ->helperText('Alert when a machine slot is marked as faulty.'),

                                Toggle::make('dispense_failure_notification')
                                    ->label('Dispense failure notification')
                                    ->helperText('Alert when a product delivery or dispense attempt fails on the kiosk.'),

                                Toggle::make('network_anomaly_notification')
                                    ->label('Network anomaly notification')
                                    ->helperText('Alert on network connectivity anomalies detected in the machine.'),
                            ]),
                    ]),
            ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Notification Configuration';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestAlertEmail')
                ->label('Send test alert email')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Sends a sample alert email to verify SMTP and the email template. Does not require active machine alerts.')
                ->action(function (OperatorAlertEmailService $emailService): void {
                    $result = $emailService->sendTestEmail();

                    if ($result['sent']) {
                        Notification::make()
                            ->success()
                            ->title('Test email sent')
                            ->body("Sent {$result['alert_count']} sample alert(s) to ".NotificationSetting::current()->notification_email)
                            ->send();

                        return;
                    }

                    $message = match ($result['reason']) {
                        'notification_email_not_configured' => 'Set a valid notification email address first.',
                        'mail_send_failed' => 'Mail failed — check MAIL_* in .env and storage/logs/laravel.log.',
                        default => "Not sent: {$result['reason']}",
                    };

                    Notification::make()
                        ->warning()
                        ->title('Test email not sent')
                        ->body($message)
                        ->send();
                }),
            Action::make('sendDailyAnalyticsTest')
                ->label('Send daily analytics test')
                ->icon(Heroicon::OutlinedChartBar)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Sends a sample daily machine report using real data from the first account owner with machines. Delivered to the notification email below (or that owner\'s email).')
                ->action(function (DailyMachineAnalyticsEmailService $emailService): void {
                    $recipientEmail = filled($this->data['notification_email'] ?? null)
                        ? (string) $this->data['notification_email']
                        : null;

                    $result = $emailService->sendTestEmail(recipientEmail: $recipientEmail);

                    if ($result['sent']) {
                        Notification::make()
                            ->success()
                            ->title('Daily analytics test sent')
                            ->body("Sample report for {$result['owner_name']} sent to {$result['recipient']}.")
                            ->send();

                        return;
                    }

                    $message = match ($result['reason']) {
                        'notification_email_not_configured' => 'Set a valid notification email address above, then click Save (or use a machine owner with a real email).',
                        'no_account_owners_with_machines' => 'No customer, agency, or operator accounts with machines were found.',
                        'mail_send_failed' => str_ends_with((string) ($result['recipient'] ?? ''), '@test.com')
                            ? "Mail rejected for {$result['recipient']}. Enter your real inbox in Account notification email, click Save, then try again."
                            : "Mail failed sending to {$result['recipient']}. Check MAIL_* in .env and storage/logs/laravel.log.",
                        default => "Not sent: {$result['reason']}",
                    };

                    Notification::make()
                        ->warning()
                        ->title('Daily analytics test not sent')
                        ->body($message)
                        ->send();
                }),
            Action::make('sendAlertEmail')
                ->label('Send alert email now')
                ->icon(Heroicon::OutlinedEnvelope)
                ->requiresConfirmation()
                ->modalDescription('Sends a summary of all active operator alerts to the notification email address below.')
                ->action(function (OperatorAlertEmailService $emailService): void {
                    $result = $emailService->sendDigest(force: true);

                    if ($result['sent']) {
                        Notification::make()
                            ->success()
                            ->title('Email sent')
                            ->body("Sent {$result['alert_count']} alert(s) to ".NotificationSetting::current()->notification_email)
                            ->send();

                        return;
                    }

                    $message = match ($result['reason']) {
                        'no_active_alerts' => 'No active alerts to send. Enable alert types and ensure machines have issues to report.',
                        'notification_email_not_configured' => 'Set a valid notification email address first.',
                        'mail_send_failed' => 'Mail failed — check MAIL_* in .env and storage/logs/laravel.log.',
                        default => "Not sent: {$result['reason']}",
                    };

                    Notification::make()
                        ->warning()
                        ->title('Email not sent')
                        ->body($message)
                        ->send();
                }),
        ];
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->key('form-actions'),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::Start;
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }
}
