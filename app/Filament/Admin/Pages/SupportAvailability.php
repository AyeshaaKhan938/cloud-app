<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Navigation\AdminNavigationGroups;
use App\Models\SupportAgentPresence;
use App\Models\User;
use App\Services\Support\SupportQueueService;
use App\Services\Support\WorkOrderService;
use App\Services\Users\UserCloudScope;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class SupportAvailability extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Live chat availability';

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroups::Support;

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'Live chat availability';

    protected static ?string $slug = 'support-availability';

    protected string $view = 'filament.admin.pages.support-availability';

    public bool $available = false;

    public function mount(): void
    {
        abort_unless(self::canAccess(), 403);

        $this->available = SupportAgentPresence::query()
            ->where('user_id', auth()->id())
            ->value('is_available_for_live_chat') ?? false;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && app(UserCloudScope::class)->hasFullCloudAccess($user)
            && app(WorkOrderService::class)->canManageQueue($user);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canAccess();
    }

    public function getSubheading(): ?string
    {
        $count = app(SupportQueueService::class)->liveChatRequestedCount();

        return $count > 0
            ? "{$count} ticket(s) currently waiting for live chat."
            : 'Toggle on when you can accept live chat requests from operators.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveAvailability')
                ->label(fn (): string => $this->available ? 'Go offline' : 'Go online for live chat')
                ->color(fn (): string => $this->available ? 'danger' : 'success')
                ->form([
                    Toggle::make('available')
                        ->label('Available for live chat')
                        ->default($this->available)
                        ->inline(false),
                ])
                ->fillForm(['available' => $this->available])
                ->action(function (array $data): void {
                    /** @var User $user */
                    $user = auth()->user();
                    app(WorkOrderService::class)->setAgentAvailability($user, (bool) $data['available']);
                    $this->available = (bool) $data['available'];

                    Notification::make()
                        ->title($this->available ? 'You are online for live chat' : 'You are offline for live chat')
                        ->success()
                        ->send();
                }),
        ];
    }
}
