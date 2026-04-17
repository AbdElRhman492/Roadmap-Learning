<?php

declare(strict_types=1);

namespace PAPP\Notifications;

use PAPP\Contracts\Notifiable;

class LogNotifier implements Notifiable
{
  public function send(string $recipient, string $message): void
  {
    // Log the notification to a file or database
    echo "Logging notification for {$recipient}: {$message}\n";
  }
}
