<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\WorkOrder;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NewSupportTicketNotification extends Notification
{
    public function __construct(
        public readonly WorkOrder $ticket,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable
            ? ['mail']
            : ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('New support ticket '.$this->ticket->work_order_number)
            ->body($this->ticket->priority->getLabel().' priority · '.$this->ticket->device_name.' · '.$this->ticket->submitted_by)
            ->icon(Heroicon::OutlinedLifebuoy)
            ->actions([
                Action::make('view')
                    ->label('Open queue')
                    ->url(url('/admin/work-orders/'.$this->ticket->id))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New support ticket '.$this->ticket->work_order_number)
            ->line('A new support ticket was submitted.')
            ->line('Ticket: '.$this->ticket->work_order_number)
            ->line('Machine: '.$this->ticket->device_name.' ('.$this->ticket->device_number.')')
            ->line('Priority: '.$this->ticket->priority->getLabel())
            ->line('Submitted by: '.$this->ticket->submitted_by)
            ->action('Open support queue', url('/admin/work-orders/'.$this->ticket->id));
    }
}
