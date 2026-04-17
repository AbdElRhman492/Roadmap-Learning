<?php

declare(strict_types=1);

namespace PAPP\Contracts;

use PAPP\Entities\Exam;

interface ExamRepositoryInterface
{
  public function save(Exam $exam): void;
  public function findById(int $id): ?Exam;

  /**
   * Summary of all
   * @return Exam[]
   */
  public function all(): array;
}
