<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class DailyMachineAnalyticsMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $report
     */
    public function __construct(
        public readonly User $owner,
        public readonly array $report,
    ) {}

    public function envelope(): Envelope
    {
        $date = (string) ($this->report['period']['to'] ?? now()->toDateString());

        return new Envelope(
            subject: 'VMFS USA — Daily machine report for '.$date,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.daily-machine-analytics',
        );
    }
}
