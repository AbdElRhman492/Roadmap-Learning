<?php

declare(strict_types=1);

namespace PAPP\Notifications;

use PAPP\Contracts\Notifiable;

class SmsNotifier implements Notifiable
{
  public function send(string $recipient, string $message): void
  {
    // Send the notification via SMS
    echo "Sending SMS to {$recipient}: {$message}\n";
  }
}
