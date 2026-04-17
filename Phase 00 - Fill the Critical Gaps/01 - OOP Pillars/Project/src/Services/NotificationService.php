<?php

declare(strict_types=1);

namespace PAPP\Services;

use PAPP\Contracts\Notifiable;

class NotificationService
{
  private array $notifiers = [];

  public function addNotifier(Notifiable $notifier): void
  {
    $this->notifiers[] = $notifier;
  }

  public function notify(string $recipient, string $message): void
  {
    foreach ($this->notifiers as $notifier) {
      $notifier->send($recipient, $message);
    }
  }
}
