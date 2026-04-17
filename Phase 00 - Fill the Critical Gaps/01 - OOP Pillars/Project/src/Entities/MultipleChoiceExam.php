<?php

declare(strict_types=1);

namespace PAPP\Entities;

class MultipleChoiceExam extends Exam
{
  private array $answerKey = [];

  public function setAnswerKey(array $answerKey): void
  {
    $this->answerKey = $answerKey;
  }

  public function grade(Submission $submission): Result
  {
    $studentAnswers = json_decode($submission->getPayload(), true);
    $correctAnswers = 0;
    $totalQuestions = count($this->answerKey);

    foreach ($this->answerKey as $questionId => $correctAnswer) {
      if (isset($studentAnswers[$questionId]) && $studentAnswers[$questionId] === $correctAnswer) {
        $correctAnswers++;
      }
    }

    $score = ($correctAnswers / max($totalQuestions, 1)) * $this->getMaxScore();
    $passed = $score >= ($this->getMaxScore() * 0.5); // 50% pass threshold

    return new Result(
      1,
      $submission->getStudentId(),
      $submission->getExamId(),
      $score,
      $passed,
      false
    );
  }
}
