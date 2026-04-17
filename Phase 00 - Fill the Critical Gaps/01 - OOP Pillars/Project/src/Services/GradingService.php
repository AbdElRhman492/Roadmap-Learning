<?php

declare(strict_types=1);

namespace PAPP\Services;

use PAPP\Contracts\ExamRepositoryInterface;
use PAPP\Contracts\SubmissionRepositoryInterface;
use PAPP\Entities\Result;
use PAPP\Exceptions\InvalidStateTransitionException;

class GradingService
{
  private ExamRepositoryInterface $examRepo;
  private SubmissionRepositoryInterface $submissionRepo;

  public function __construct(
    ExamRepositoryInterface $examRepo,
    SubmissionRepositoryInterface $submissionRepo
  ) {
    $this->examRepo = $examRepo;
    $this->submissionRepo = $submissionRepo;
  }

  /**
   * Grade a submission and return the result
   *
   * POLYMORPHISM IN ACTION:
   * This method calls exam->grade() which behaves differently based on exam type:
   * - MultipleChoiceExam: Compares answers against answer key
   * - PracticalExam: Uses weighted criteria
   *
   * The service doesn't need to know WHICH exam type it is!
   *
   * @param int $submissionId The submission to grade
   * @return Result The graded result
   * @throws InvalidStateTransitionException if:
   *   - Submission not found
   *   - Exam not found
   *   - Exam not published (cannot grade draft exams)
   */
  public function gradeSubmission(int $submissionId): Result
  {
    // 1. Find the submission
    $submission = $this->submissionRepo->findById($submissionId);
    if ($submission === null) {
      throw new InvalidStateTransitionException("Submission with ID $submissionId not found.");
    }

    // 2. Find the exam this submission is for
    $exam = $this->examRepo->findById($submission->getExamId());
    if ($exam === null) {
      throw new InvalidStateTransitionException(
        "Exam with ID {$submission->getExamId()} not found."
      );
    }

    // 3. Validate exam is published (enforce business rule)
    if ($exam->getStatus() !== 'published') {
      throw new InvalidStateTransitionException(
        "Cannot grade: Exam {$submission->getExamId()} is not published."
      );
    }

    // 4. POLYMORPHIC CALL - this is the magic!
    // $exam could be MultipleChoiceExam or PracticalExam || any Type that implements Gradable
    // Each implements grade() differently
    // The service doesn't care which one it is!
    $result = $exam->grade($submission);

    // 5. Save the result
    $this->submissionRepo->save($submission);

    // 6. Return the result
    return $result;
  }

  /**
   * Mark a result as final (prevents changes)
   *
   * @param Result $result The result to finalize
   * @return void
   * @throws InvalidStateTransitionException if already final
   */
  public function markResultFinal(Result $result): void
  {
    if ($result->isFinal()) {
      throw new InvalidStateTransitionException(
        "Result is already marked as final and cannot be changed."
      );
    }

    $result->markFinal();
  }

  /**
   * Get all results for a student
   *
   * @param int $studentId
   * @return Result[] All results for this student
   */
  public function getStudentResults(int $studentId): array
  {
    $allResults = $this->submissionRepo->all();
    $studentResults = [];

    // Filter results by student ID
    foreach ($allResults as $submission) {
      if ($submission->getStudentId() === $studentId) {
        $studentResults[] = $submission;
      }
    }

    return $studentResults;
  }

  /**
   * Get all results for an exam
   *
   * @param int $examId
   * @return Result[] All results for this exam
   */
  public function getExamResults(int $examId): array
  {
    $allResults = $this->submissionRepo->all();
    $examResults = [];

    // Filter results by exam ID
    foreach ($allResults as $submission) {
      if ($submission->getExamId() === $examId) {
        $examResults[] = $submission;
      }
    }

    return $examResults;
  }
}
