<?php

declare(strict_types=1);

namespace PAPP\Contracts;

interface Notifiable
{
  public function send(string $recipient, string $message): void;
}
