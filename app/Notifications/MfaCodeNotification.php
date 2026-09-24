<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Carries the plaintext code to exactly one place: the user's own inbox or
 * handset. It is never persisted and never logged.
 */
class MfaCodeNotification extends Notification
{
    use Queueable;

    public function __construct(private string $code, private string $channel) {}

    public function via(object $notifiable): array
    {
        return $this->channel === 'sms' ? ['sms'] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your SL-DWP sign-in code')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your code is: **'.$this->code.'**')
            ->line('It is valid for five minutes and can be used once.')
            ->line('If you did not try to sign in, change your password and tell an administrator.');
    }

    /** Implement a driver for your SMS gateway; the payload stays this small. */
    public function toSms(object $notifiable): array
    {
        return [
            'to'      => $notifiable->phone,
            'message' => 'SL-DWP code: '.$this->code.'. Valid 5 minutes. Never share it.',
        ];
    }
}
