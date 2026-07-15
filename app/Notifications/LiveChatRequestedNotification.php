<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\WorkOrder;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class LiveChatRequestedNotification extends Notification
{
    public function __construct(
        public readonly WorkOrder $ticket,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Live chat requested')
            ->body($this->ticket->work_order_number.' · '.$this->ticket->device_name)
            ->icon(Heroicon::OutlinedVideoCamera)
            ->actions([
                Action::make('view')
                    ->label('Join ticket')
                    ->url(url('/admin/work-orders/'.$this->ticket->id))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Live chat requested — '.$this->ticket->work_order_number)
            ->line('An operator requested live chat on a support ticket.')
            ->line('Ticket: '.$this->ticket->work_order_number)
            ->line('Machine: '.$this->ticket->device_name)
            ->action('Join ticket', url('/admin/work-orders/'.$this->ticket->id));
    }
}
