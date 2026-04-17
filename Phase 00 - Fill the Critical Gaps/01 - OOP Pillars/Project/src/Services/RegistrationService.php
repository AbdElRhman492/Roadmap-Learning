<?php

declare(strict_types=1);

namespace PAPP\Services;

use PAPP\Contracts\StudentRepositoryInterface;
use PAPP\Contracts\ExamRepositoryInterface;
use PAPP\Entities\ScheduledExam;
use PAPP\Exceptions\InvalidStateTransitionException;

class RegistrationService
{
  private StudentRepositoryInterface $studentRepo;
  private ExamRepositoryInterface $examRepo;

  public function __construct(
    StudentRepositoryInterface $studentRepo,
    ExamRepositoryInterface $examRepo
  ) {
    $this->studentRepo = $studentRepo;
    $this->examRepo = $examRepo;
  }

  /**
   * Register a student for an exam with capacity and state checks
   *
   * @param int $studentId The student to register
   * @param int $examId The exam to register for
   * @param ScheduledExam $scheduledExam The scheduled instance (contains capacity/deadline)
   * @return void
   * @throws InvalidStateTransitionException if:
   *   - Student not found
   *   - Exam not found
   *   - Registration is closed
   *   - Capacity is full
   *   - Student already registered
   */
  public function registerStudent(
    int $studentId,
    int $examId,
    ScheduledExam $scheduledExam
  ): void {
    // 1. Validate student exists
    $student = $this->studentRepo->findById($studentId);
    if ($student === null) {
      throw new InvalidStateTransitionException("Student with ID $studentId not found.");
    }

    // 2. Validate exam exists
    $exam = $this->examRepo->findById($examId);
    if ($exam === null) {
      throw new InvalidStateTransitionException("Exam with ID $examId not found.");
    }

    // 3. Check if exam is published (can only register for published exams)
    if ($exam->getStatus() !== 'published') {
      throw new InvalidStateTransitionException(
        "Cannot register: Exam $examId is not published yet."
      );
    }

    // 4. Check if registration is open
    if (!$scheduledExam->isRegistrationOpen()) {
      throw new InvalidStateTransitionException(
        "Cannot register: Registration is closed for exam $examId."
      );
    }

    // 5. Check if capacity is not full
    if (!$scheduledExam->canRegister()) {
      throw new InvalidStateTransitionException(
        "Cannot register: Exam $examId is at capacity."
      );
    }

    // 6. Check for duplicate registration (already registered)
    $registrations = $student->getRegistrations();
    foreach ($registrations as $registeredExam) {
      if ($registeredExam->getId() === $examId) {
        throw new InvalidStateTransitionException(
          "Student $studentId is already registered for exam $examId."
        );
      }
    }

    // 7. All validations passed - register student
    $student->registerFor($exam);

    // 8. Increment enrolled count
    $scheduledExam->incrementEnrolledCount();

    // 9. Save updated student
    $this->studentRepo->save($student);
  }

  /**
   * Check if a student can register for an exam without throwing
   *
   * @param int $studentId
   * @param int $examId
   * @param ScheduledExam $scheduledExam
   * @return bool True if all conditions allow registration
   */
  public function canRegister(
    int $studentId,
    int $examId,
    ScheduledExam $scheduledExam
  ): bool {
    $student = $this->studentRepo->findById($studentId);
    if ($student === null) {
      return false;
    }

    $exam = $this->examRepo->findById($examId);
    if ($exam === null) {
      return false;
    }

    if ($exam->getStatus() !== 'published') {
      return false;
    }

    if (!$scheduledExam->isRegistrationOpen()) {
      return false;
    }

    if (!$scheduledExam->canRegister()) {
      return false;
    }

    // Check duplicate
    $registrations = $student->getRegistrations();
    foreach ($registrations as $registeredExam) {
      if ($registeredExam->getId() === $examId) {
        return false;
      }
    }

    return true;
  }

  /**
   * Get a student's registrations
   *
   * @param int $studentId
   * @return array List of registered exams
   * @throws InvalidStateTransitionException if student not found
   */
  public function getStudentRegistrations(int $studentId): array
  {
    $student = $this->studentRepo->findById($studentId);
    if ($student === null) {
      throw new InvalidStateTransitionException("Student with ID $studentId not found.");
    }

    return $student->getRegistrations();
  }
}
