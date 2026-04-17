<?php

declare(strict_types=1);

namespace PAPP\Entities;

use PAPP\Contracts\Gradable;

abstract class Exam implements Gradable
{
  private int $id;
  private string $title;
  private float $maxScore;
  private string $status; // draft|published

  public function __construct(int $id, string $title, float $maxScore)
  {
    $this->id = $id;
    $this->title = $title;
    $this->maxScore = $maxScore;
    $this->status = 'draft';
  }

  public function publish(): void
  {
    $this->status = 'published';
  }

  public function getId(): int
  {
    return $this->id;
  }

  public function getTitle(): string
  {
    return $this->title;
  }

  public function getMaxScore(): float
  {
    return $this->maxScore;
  }

  public function getStatus(): string
  {
    return $this->status;
  }
}
