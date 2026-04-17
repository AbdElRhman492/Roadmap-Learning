<?php

declare(strict_types=1);

namespace PAPP\Entities;

class Result
{
  private int $id;
  private int $studentId;
  private int $examId;
  private float $score;
  private bool $passed;
  private bool $isFinal;

  public function __construct(
    int $id,
    int $studentId,
    int $examId,
    float $score,
    bool $passed,
    bool $isFinal = false
  ) {
    $this->id = $id;
    $this->studentId = $studentId;
    $this->examId = $examId;
    $this->score = $score;
    $this->passed = $passed;
    $this->isFinal = $isFinal;
  }

  public function getId(): int
  {
    return $this->id;
  }

  public function getStudentId(): int
  {
    return $this->studentId;
  }

  public function getExamId(): int
  {
    return $this->examId;
  }

  public function getScore(): float
  {
    return $this->score;
  }

  public function isPassed(): bool
  {
    return $this->passed;
  }

  public function isFinal(): bool
  {
    return $this->isFinal;
  }

  public function markFinal(): void
  {
    $this->isFinal = true;
  }
}
