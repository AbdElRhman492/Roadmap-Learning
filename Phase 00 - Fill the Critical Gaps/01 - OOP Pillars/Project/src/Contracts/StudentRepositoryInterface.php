<?php

declare(strict_types=1);

namespace PAPP\Contracts;

use PAPP\Entities\Student;

interface StudentRepositoryInterface
{
  public function save(Student $student): void;

  public function findById(int $id): ?Student;

  /**
   * Summary of all
   * @return Student[]
   */
  public function all(): array;
}
