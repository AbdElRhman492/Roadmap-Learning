<?php

declare(strict_types=1);

namespace PAPP\Entities;

use DateTime;

class Submission
{
  private int $id;
  private int $studentId;
  private int $examId;
  private string $payload;
  private DateTime $submittedAt;

  public function __construct(
    int $id,
    int $studentId,
    int $examId,
    string $payload,
    DateTime $submittedAt
  ) {
    $this->id = $id;
    $this->studentId = $studentId;
    $this->examId = $examId;
    $this->payload = $payload;
    $this->submittedAt = $submittedAt;
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

  public function getPayload(): string
  {
    return $this->payload;
  }

  public function getSubmittedAt(): DateTime
  {
    return $this->submittedAt;
  }
}
