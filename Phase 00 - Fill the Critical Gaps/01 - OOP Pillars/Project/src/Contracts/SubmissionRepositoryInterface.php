<?php

declare(strict_types=1);

namespace PAPP\Contracts;

use PAPP\Entities\Submission;

interface SubmissionRepositoryInterface
{
  public function save(Submission $submission): void;

  public function findById(int $id): ?Submission;

  /**
   * Summary of all
   * @return Submission[]
   */
  public function all(): array;
}
