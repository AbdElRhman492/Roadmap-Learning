<?php

declare(strict_types=1);

namespace PAPP\Entities;

class Student extends User
{
  private array $registrations = [];

  public function __construct(int $id, string $name, string $email)
  {
    parent::__construct($id, $name, $email);
  }

  public function registerFor(Exam $exam): void
  {
    $this->registrations[] = $exam;
  }

  /**
   * @return Exam[]
   */
  public function getRegistrations(): array
  {
    return $this->registrations;
  }
}
