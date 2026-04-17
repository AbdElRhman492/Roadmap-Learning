<?php

declare(strict_types=1);

namespace PAPP\Repositories;

use PAPP\Contracts\StudentRepositoryInterface;
use PAPP\Entities\Student;
use PAPP\Exceptions\DomainException;

class InMemoryStudentRepository implements StudentRepositoryInterface
{
  private array $students = [];

  public function save(Student $student): void
  {
    $this->students[$student->getId()] = $student;
  }

  public function findById(int $id): ?Student
  {
    return $this->students[$id] ?? null;
  }

  /**
   * @return Student[]
   */
  public function all(): array
  {
    return $this->students;
  }
}
