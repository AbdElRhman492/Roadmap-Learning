<?php

declare(strict_types=1);

namespace PAPP\Repositories;

use PAPP\Contracts\SubmissionRepositoryInterface;
use PAPP\Entities\Submission;
use PAPP\Exceptions\DomainException;

class InMemorySubmissionRepository implements SubmissionRepositoryInterface
{
  private array $submissions = [];

  public function save(Submission $submission): void
  {
    $this->submissions[$submission->getId()] = $submission;
  }

  public function findById(int $id): ?Submission
  {
    return $this->submissions[$id] ?? null;
  }

  /**
   * @return Submission[]
   */
  public function all(): array
  {
    return $this->submissions;
  }
}
