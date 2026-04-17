<?php

declare(strict_types=1);

namespace PAPP\Entities;

class PracticalExam extends Exam
{
  private array $criteria = [];

  public function setCriteria(array $criteria): void
  {
    $this->criteria = $criteria;
  }

  public function grade(Submission $submission): Result
  {
    // Simulate practical exam grading based on criteria weights
    $submissionData = json_decode($submission->getPayload(), true);
    $score = 0;
    $totalWeight = 0;

    foreach ($this->criteria as $criterion => $weight) {
      $totalWeight += $weight;
      // Simulate score for each criterion (0-100)
      $criterionScore = rand(0, 100);
      $score += ($criterionScore / 100) * $weight;
    }

    // Normalize score to exam max score
    $finalScore = ($score / max($totalWeight, 1)) * $this->getMaxScore();
    $passed = $finalScore >= ($this->getMaxScore() * 0.5); // 50% pass threshold

    return new Result(
      1,
      $submission->getStudentId(),
      $submission->getExamId(),
      $finalScore,
      $passed,
      false
    );
  }
}
