<?php

declare(strict_types=1);

namespace PAPP\Entities;

class Instructor extends User
{
  private string $specialty;

  public function __construct(
    int $id,
    string $name,
    string $email,
    string $specialty
  ) {
    parent::__construct($id, $name, $email);

    $this->specialty = $specialty;
  }

  public function getSpecialty(): string
  {
    return $this->specialty;
  }
}
