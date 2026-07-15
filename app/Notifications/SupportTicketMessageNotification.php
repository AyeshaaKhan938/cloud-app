<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\WorkOrder;
use App\Models\WorkOrderMessage;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SupportTicketMessageNotification extends Notification
{
    public function __construct(
        public readonly WorkOrder $ticket,
        public readonly WorkOrderMessage $message,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return ['mail'];
        }

        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('New reply on '.$this->ticket->work_order_number)
            ->body($this->message->author_name.': '.str($this->message->body)->limit(120)->toString())
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->actions([
                Action::make('view')
                    ->label('Open ticket')
                    ->url(url('/admin/work-orders/'.$this->ticket->id))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New reply on ticket '.$this->ticket->work_order_number)
            ->line($this->message->author_name.' replied on support ticket '.$this->ticket->work_order_number.'.')
            ->line(str($this->message->body)->limit(200)->toString());
    }
}
