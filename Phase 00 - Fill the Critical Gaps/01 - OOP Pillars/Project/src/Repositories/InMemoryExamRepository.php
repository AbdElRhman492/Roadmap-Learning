<?php

declare(strict_types=1);

namespace PAPP\Repositories;

use PAPP\Contracts\ExamRepositoryInterface;
use PAPP\Entities\Exam;
use PAPP\Exceptions\DomainException;

class InMemoryExamRepository implements ExamRepositoryInterface
{
  private array $exams = [];

  public function save(Exam $exam): void
  {
    $this->exams[$exam->getId()] = $exam;
  }

  public function findById(int $id): ?Exam
  {
    return $this->exams[$id] ?? null;
  }

  /**
   * @return Exam[]
   */
  public function all(): array
  {
    return $this->exams;
  }
}
