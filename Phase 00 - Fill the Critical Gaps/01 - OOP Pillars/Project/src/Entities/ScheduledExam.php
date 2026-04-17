<?php

declare(strict_types=1);

namespace PAPP\Entities;

use DateTime;

class ScheduledExam
{
  private int $examId;
  private DateTime $dateTime;
  private bool $registrationOpen;
  private int $capacity;
  private int $enrolledCount;

  public function __construct(
    int $examId,
    DateTime $dateTime,
    int $capacity,
    bool $registrationOpen = true,
    int $enrolledCount = 0
  ) {
    $this->examId = $examId;
    $this->dateTime = $dateTime;
    $this->registrationOpen = $registrationOpen;
    $this->capacity = $capacity;
    $this->enrolledCount = $enrolledCount;
  }

  public function openRegistration(): void
  {
    $this->registrationOpen = true;
  }

  public function closeRegistration(): void
  {
    $this->registrationOpen = false;
  }

  public function canRegister(): bool
  {
    if (!$this->registrationOpen) {
      return false;
    }

    return $this->enrolledCount < $this->capacity;
  }

  public function getEnrolledCount(): int
  {
    return $this->enrolledCount;
  }

  public function incrementEnrolledCount(): void
  {
    if ($this->enrolledCount < $this->capacity) {
      $this->enrolledCount++;
    }
  }

  public function getCapacity(): int
  {
    return $this->capacity;
  }

  public function getExamId(): int
  {
    return $this->examId;
  }

  public function getDateTime(): DateTime
  {
    return $this->dateTime;
  }

  public function isRegistrationOpen(): bool
  {
    return $this->registrationOpen;
  }
}
