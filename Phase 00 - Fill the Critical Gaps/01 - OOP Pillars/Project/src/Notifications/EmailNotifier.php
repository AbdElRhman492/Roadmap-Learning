<?php

declare(strict_types=1);

namespace PAPP\Notifications;

use PAPP\Contracts\Notifiable;

class EmailNotifier implements Notifiable
{
  public function send(string $recipient, string $message): void
  {
    // Send the notification via email
    echo "Sending email to {$recipient}: {$message}\n";
  }
}
