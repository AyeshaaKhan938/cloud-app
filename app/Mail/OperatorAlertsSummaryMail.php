<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class OperatorAlertsSummaryMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  list<array{machine_number: string, machine_name: string, type: string, severity: string, title: string, message: string}>  $alerts
     */
    public function __construct(
        public readonly array $alerts,
    ) {}

    public function envelope(): Envelope
    {
        $count = count($this->alerts);

        return new Envelope(
            subject: "VMFS USA — {$count} operator alert".($count === 1 ? '' : 's'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.operator-alerts-summary',
        );
    }
}
