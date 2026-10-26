<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use LogicException;

class LogSmsChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! app()->environment('testing')) {
            throw new LogicException(
                'LogSmsChannel is available only in testing.'
            );
        }

        if (! method_exists($notification, 'toSms')) {
            throw new LogicException(
                'The notification must implement toSms().'
            );
        }

        $payload = $notification->toSms($notifiable);

        if (! isset($payload['message']) || ! is_string($payload['message'])) {
            throw new LogicException('Invalid SMS payload.');
        }

        Log::info($payload['message'], ['channel' => 'sms-test']);
    }
}
