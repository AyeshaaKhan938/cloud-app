<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\WorkOrder;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SupportTicketResolvedNotification extends Notification
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
            ->title('Ticket '.$this->ticket->work_order_number.' resolved')
            ->body('Status: '.$this->ticket->status->getLabel())
            ->icon(Heroicon::OutlinedCheckCircle)
            ->actions([
                Action::make('view')
                    ->label('View ticket')
                    ->url(url('/admin/my-work-orders/'.$this->ticket->id))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Support ticket '.$this->ticket->work_order_number.' resolved')
            ->line('Your support ticket has been updated.')
            ->line('Ticket: '.$this->ticket->work_order_number)
            ->line('Status: '.$this->ticket->status->getLabel())
            ->action('View ticket', url('/admin/my-work-orders/'.$this->ticket->id));
    }
}
