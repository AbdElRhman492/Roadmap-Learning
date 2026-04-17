<?php

declare(strict_types=1);

namespace PAPP\Services;

use PAPP\Contracts\ExamRepositoryInterface;
use PAPP\Entities\MultipleChoiceExam;
use PAPP\Entities\PracticalExam;
use PAPP\Entities\ScheduledExam;
use PAPP\Exceptions\InvalidStateTransitionException;
use DateTime;

class ExamService
{
  private ExamRepositoryInterface $examRepo;

  public function __construct(ExamRepositoryInterface $examRepo)
  {
    $this->examRepo = $examRepo;
  }

  /**
   * Create a new Multiple Choice Exam
   *
   * @param int $id Unique exam identifier
   * @param string $title Exam title
   * @param float $maxScore Maximum score for the exam
   * @return MultipleChoiceExam The created exam in draft status
   */
  public function createMultipleChoiceExam(int $id, string $title, float $maxScore): MultipleChoiceExam
  {
    $exam = new MultipleChoiceExam($id, $title, $maxScore);
    $this->examRepo->save($exam);
    return $exam;
  }

  /**
   * Create a new Practical Exam
   *
   * @param int $id Unique exam identifier
   * @param string $title Exam title
   * @param float $maxScore Maximum score for the exam
   * @return PracticalExam The created exam in draft status
   */
  public function createPracticalExam(int $id, string $title, float $maxScore): PracticalExam
  {
    $exam = new PracticalExam($id, $title, $maxScore);
    $this->examRepo->save($exam);
    return $exam;
  }

  /**
   * Publish an exam (transition from draft to published)
   *
   * @param int $examId The ID of the exam to publish
   * @return void
   * @throws InvalidStateTransitionException if exam not found or already published
   */
  public function publishExam(int $examId): void
  {
    $exam = $this->examRepo->findById($examId);

    if ($exam === null) {
      throw new InvalidStateTransitionException("Exam with ID $examId not found.");
    }

    if ($exam->getStatus() === 'published') {
      throw new InvalidStateTransitionException("Exam $examId is already published.");
    }

    $exam->publish();
    $this->examRepo->save($exam);
  }

  /**
   * Schedule an exam (create a ScheduledExam with date, time, and capacity)
   *
   * @param int $examId The exam to schedule
   * @param DateTime $dateTime When the exam will be held
   * @param int $capacity Maximum number of students
   * @return ScheduledExam The scheduled exam instance
   * @throws InvalidStateTransitionException if exam not found
   */
  public function scheduleExam(int $examId, DateTime $dateTime, int $capacity): ScheduledExam
  {
    $exam = $this->examRepo->findById($examId);

    if ($exam === null) {
      throw new InvalidStateTransitionException("Cannot schedule: Exam with ID $examId not found.");
    }

    // Create a ScheduledExam linked to this exam
    $scheduledExam = new ScheduledExam($examId, $dateTime, $capacity);

    return $scheduledExam;
  }

  /**
   * Retrieve an exam by ID
   *
   * @param int $id The exam ID
   * @return mixed The exam or null if not found
   */
  public function getExam(int $id)
  {
    return $this->examRepo->findById($id);
  }

  /**
   * Retrieve all exams
   *
   * @return array All exams
   */
  public function getAllExams(): array
  {
    return $this->examRepo->all();
  }
}
