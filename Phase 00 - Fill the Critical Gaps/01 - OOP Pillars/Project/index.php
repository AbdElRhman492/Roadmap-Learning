<?php

declare(strict_types=1);

/**
 * ============================================================================
 * EXAM MANAGEMENT SYSTEM - Complete OOP Demonstration
 * ============================================================================
 * 
 * This script demonstrates:
 * - All 4 OOP Pillars (Encapsulation, Abstraction, Inheritance, Polymorphism)
 * - SOLID Principles in real application
 * - Complete exam workflow from creation to grading
 * - Error handling with custom exceptions
 * - Dependency Injection pattern
 * 
 * ============================================================================
 */

use PAPP\Entities\{
  Student,
  Instructor,
  MultipleChoiceExam,
  PracticalExam,
  Submission
};
use PAPP\Repositories\{
  InMemoryStudentRepository,
  InMemoryExamRepository,
  InMemorySubmissionRepository
};
use PAPP\Services\{
  ExamService,
  RegistrationService,
  GradingService,
  NotificationService
};
use PAPP\Notifications\{
  EmailNotifier,
  LogNotifier
};
use PAPP\Exceptions\InvalidStateTransitionException;

// Simple PSR-4 Autoloader
spl_autoload_register(function ($class) {
  if (strpos($class, 'PAPP\\') === 0) {
    $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, 5)) . '.php';
    if (file_exists($file)) {
      require_once $file;
    }
  }
});

// ============================================================================
// INITIALIZATION - Setup repositories and services
// ============================================================================

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "EXAM MANAGEMENT SYSTEM - Complete Workflow Demo\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// Create repositories (in-memory storage)
$studentRepo = new InMemoryStudentRepository();
$examRepo = new InMemoryExamRepository();
$submissionRepo = new InMemorySubmissionRepository();

// Create services with dependency injection
$examService = new ExamService($examRepo);
$registrationService = new RegistrationService($studentRepo, $examRepo);
$gradingService = new GradingService($examRepo, $submissionRepo);
$notificationService = new NotificationService();

// Setup notifiers (can add multiple)
$notificationService->addNotifier(new EmailNotifier());
$notificationService->addNotifier(new LogNotifier());

// ============================================================================
// SECTION 1: Create Entities
// ============================================================================

echo "┌─ SECTION 1: Creating Entities ─────────────────────────────────────────────┐\n";

// Create instructor
$instructor = new Instructor(100, 'Dr. John Smith', 'john.smith@institute.com', 'PHP & Web Development');
echo "✓ Created Instructor: {$instructor->getName()} (Specialty: {$instructor->getSpecialty()})\n";

// Create students
$student1 = new Student(1, 'Alice Johnson', 'alice@student.com');
$student2 = new Student(2, 'Bob Williams', 'bob@student.com');
$student3 = new Student(3, 'Charlie Brown', 'charlie@student.com');

$studentRepo->save($student1);
$studentRepo->save($student2);
$studentRepo->save($student3);

echo "✓ Created 3 Students:\n";
echo "  - {$student1->getName()} ({$student1->getEmail()})\n";
echo "  - {$student2->getName()} ({$student2->getEmail()})\n";
echo "  - {$student3->getName()} ({$student3->getEmail()})\n";

echo "└────────────────────────────────────────────────────────────────────────────┘\n\n";

// ============================================================================
// SECTION 2: Create Exams
// ============================================================================

echo "┌─ SECTION 2: Creating and Publishing Exams ─────────────────────────────────┐\n";

// POLYMORPHISM: Creating two different exam types
$exam1 = $examService->createMultipleChoiceExam(
  1,
  'PHP OOP Fundamentals',
  100
);
echo "✓ Created MultipleChoiceExam: {$exam1->getTitle()}\n";
echo "  Status: {$exam1->getStatus()} | Max Score: {$exam1->getMaxScore()}\n";

// Set answer key for multiple choice exam
$exam1->setAnswerKey([
  'q1' => 'A',
  'q2' => 'B',
  'q3' => 'C',
  'q4' => 'B',
  'q5' => 'A'
]);
echo "  Answer key set for 5 questions\n";

// INHERITANCE: Create practical exam
$exam2 = $examService->createPracticalExam(
  2,
  'PHP Project Implementation',
  100
);
echo "✓ Created PracticalExam: {$exam2->getTitle()}\n";
echo "  Status: {$exam2->getStatus()} | Max Score: {$exam2->getMaxScore()}\n";

// Set criteria for practical exam
$exam2->setCriteria([
  'code_quality' => 40,
  'functionality' => 40,
  'documentation' => 20
]);
echo "  Criteria set: Code Quality (40%), Functionality (40%), Documentation (20%)\n";

// Try to grade before publishing (should fail)
echo "\n✗ Attempting to publish exam BEFORE it exists in draft:\n";
try {
  $examService->publishExam(999); // Non-existent exam
} catch (InvalidStateTransitionException $e) {
  echo "  Error (Expected): {$e->getMessage()}\n";
}

// Publish exams
$examService->publishExam(1);
echo "✓ Published Exam 1: {$exam1->getTitle()}\n";

$examService->publishExam(2);
echo "✓ Published Exam 2: {$exam2->getTitle()}\n";

echo "└────────────────────────────────────────────────────────────────────────────┘\n\n";

// ============================================================================
// SECTION 3: Schedule Exams
// ============================================================================

echo "┌─ SECTION 3: Scheduling Exams ──────────────────────────────────────────────┐\n";

// Schedule exam 1
$dateTime1 = new DateTime('2026-05-20 10:00');
$scheduled1 = $examService->scheduleExam(1, $dateTime1, 50);
echo "✓ Scheduled Exam 1\n";
echo "  Date/Time: {$scheduled1->getDateTime()->format('Y-m-d H:i')}\n";
echo "  Capacity: {$scheduled1->getCapacity()}\n";
echo "  Registration: " . ($scheduled1->isRegistrationOpen() ? 'OPEN' : 'CLOSED') . "\n";

// Schedule exam 2
$dateTime2 = new DateTime('2026-05-22 14:00');
$scheduled2 = $examService->scheduleExam(2, $dateTime2, 30);
echo "✓ Scheduled Exam 2\n";
echo "  Date/Time: {$scheduled2->getDateTime()->format('Y-m-d H:i')}\n";
echo "  Capacity: {$scheduled2->getCapacity()}\n";

echo "└────────────────────────────────────────────────────────────────────────────┘\n\n";

// ============================================================================
// SECTION 4: Student Registrations
// ============================================================================

echo "┌─ SECTION 4: Student Registration ──────────────────────────────────────────┐\n";

// Register students
echo "Registering students for Exam 1:\n";
try {
  $registrationService->registerStudent(1, 1, $scheduled1);
  echo "✓ Alice registered for: PHP OOP Fundamentals\n";

  $registrationService->registerStudent(2, 1, $scheduled1);
  echo "✓ Bob registered for: PHP OOP Fundamentals\n";

  $registrationService->registerStudent(3, 1, $scheduled1);
  echo "✓ Charlie registered for: PHP OOP Fundamentals\n";
} catch (InvalidStateTransitionException $e) {
  echo "✗ Registration Error: {$e->getMessage()}\n";
}

// Attempt duplicate registration
echo "\nAttempting duplicate registration:\n";
try {
  $registrationService->registerStudent(1, 1, $scheduled1);
} catch (InvalidStateTransitionException $e) {
  echo "✗ Error (Expected): {$e->getMessage()}\n";
}

// Register for exam 2
echo "\nRegistering students for Exam 2:\n";
try {
  $registrationService->registerStudent(1, 2, $scheduled2);
  echo "✓ Alice registered for: PHP Project Implementation\n";

  $registrationService->registerStudent(2, 2, $scheduled2);
  echo "✓ Bob registered for: PHP Project Implementation\n";
} catch (InvalidStateTransitionException $e) {
  echo "✗ Registration Error: {$e->getMessage()}\n";
}

// Display student registrations
echo "\nStudent Registrations:\n";
try {
  $aliceExams = $registrationService->getStudentRegistrations(1);
  echo "Alice is registered for " . count($aliceExams) . " exam(s):\n";
  foreach ($aliceExams as $exam) {
    echo "  - {$exam->getTitle()}\n";
  }
} catch (InvalidStateTransitionException $e) {
  echo "Error: {$e->getMessage()}\n";
}

echo "└────────────────────────────────────────────────────────────────────────────┘\n\n";

// ============================================================================
// SECTION 5: Submissions and Grading (POLYMORPHISM IN ACTION!)
// ============================================================================

echo "┌─ SECTION 5: Submissions and Grading ───────────────────────────────────────┐\n";

// Array to store results for retrieval later
$resultsStore = [];

// Create submission for Alice - Multiple Choice Exam
echo "Creating submission for Alice (Exam 1 - Multiple Choice):\n";
$submission1 = new Submission(
  1,
  1, // Student 1 (Alice)
  1, // Exam 1 (Multiple Choice)
  json_encode(['q1' => 'A', 'q2' => 'B', 'q3' => 'B', 'q4' => 'B', 'q5' => 'A']),
  new DateTime()
);
$submissionRepo->save($submission1);
echo "✓ Submission created\n";

// POLYMORPHIC GRADING: Works differently for each exam type!
echo "\nGrading submission (POLYMORPHISM - different logic for each exam type):\n";
try {
  $result1 = $gradingService->gradeSubmission(1);
  $resultsStore[] = $result1; // Store for later retrieval
  echo "✓ Graded!\n";
  echo "  Score: {$result1->getScore()}/100\n";
  echo "  Passed: " . ($result1->isPassed() ? 'YES ✓' : 'NO ✗') . "\n";

  // Mark as final
  $gradingService->markResultFinal($result1);
  echo "  Result marked as FINAL\n";
} catch (InvalidStateTransitionException $e) {
  echo "✗ Grading Error: {$e->getMessage()}\n";
}

// Create submission for Bob - Practical Exam
echo "\nCreating submission for Bob (Exam 2 - Practical):\n";
$submission2 = new Submission(
  2,
  2, // Student 2 (Bob)
  2, // Exam 2 (Practical)
  json_encode(['approach' => 'good', 'implementation' => 'excellent']),
  new DateTime()
);
$submissionRepo->save($submission2);
echo "✓ Submission created\n";

// Grade with different logic (Practical Exam)
echo "\nGrading submission (different logic for Practical Exam):\n";
try {
  $result2 = $gradingService->gradeSubmission(2);
  $resultsStore[] = $result2; // Store for later retrieval
  echo "✓ Graded!\n";
  echo "  Score: {$result2->getScore()}/100\n";
  echo "  Passed: " . ($result2->isPassed() ? 'YES ✓' : 'NO ✗') . "\n";
} catch (InvalidStateTransitionException $e) {
  echo "✗ Grading Error: {$e->getMessage()}\n";
}

echo "└────────────────────────────────────────────────────────────────────────────┘\n\n";

// ============================================================================
// SECTION 6: Notifications (POLYMORPHIC NOTIFIERS)
// ============================================================================

echo "┌─ SECTION 6: Notifications ─────────────────────────────────────────────────┐\n";

echo "Sending notifications to students (via multiple notifier types):\n\n";

// Notify Alice
$notificationService->notify(
  'alice@student.com',
  'Your exam result: Score 80/100 - PASSED ✓'
);

// Notify Bob
$notificationService->notify(
  'bob@student.com',
  'Your exam result: Score 75/100 - PASSED ✓'
);

echo "└────────────────────────────────────────────────────────────────────────────┘\n\n";

// ============================================================================
// SECTION 7: Retrieve Results
// ============================================================================

echo "┌─ SECTION 7: Exam Reports ──────────────────────────────────────────────────┐\n";

// Note: Using resultsStore array created during grading
// In a real system, you'd have a ResultRepository
echo "Results for Alice (Student 1):\n";
foreach ($resultsStore as $result) {
  if ($result->getStudentId() === 1) {
    echo "  - Exam {$result->getExamId()}: {$result->getScore()}/100 ";
    echo ($result->isPassed() ? '✓ PASSED' : '✗ FAILED') . "\n";
  }
}

// Get results for exam 1
echo "\nResults for Exam 1 (Multiple Choice):\n";
foreach ($resultsStore as $result) {
  if ($result->getExamId() === 1) {
    echo "  - Student {$result->getStudentId()}: {$result->getScore()}/100 ";
    echo ($result->isPassed() ? '✓ PASSED' : '✗ FAILED') . "\n";
  }
}

echo "└────────────────────────────────────────────────────────────────────────────┘\n\n";

// ============================================================================
// SECTION 8: Error Handling Examples
// ============================================================================

echo "┌─ SECTION 8: Error Handling & Constraints ──────────────────────────────────┐\n";

echo "Demonstrating business rule enforcement:\n\n";

// Try to register non-existent student
echo "1. Register non-existent student:\n";
try {
  $registrationService->registerStudent(999, 1, $scheduled1);
} catch (InvalidStateTransitionException $e) {
  echo "   ✓ Caught: {$e->getMessage()}\n";
}

// Try to register for non-existent exam
echo "\n2. Register for non-existent exam:\n";
try {
  $registrationService->registerStudent(1, 999, $scheduled1);
} catch (InvalidStateTransitionException $e) {
  echo "   ✓ Caught: {$e->getMessage()}\n";
}

// Close registration and try to register
echo "\n3. Close registration and attempt to register:\n";
$scheduled1->closeRegistration();
echo "   Registration closed for Exam 1\n";
try {
  // Need to create a new student to test this
  $student4 = new Student(4, 'Diana Prince', 'diana@student.com');
  $studentRepo->save($student4);
  $registrationService->registerStudent(4, 1, $scheduled1);
} catch (InvalidStateTransitionException $e) {
  echo "   ✓ Caught: {$e->getMessage()}\n";
}

// Try to grade for non-existent submission
echo "\n4. Grade non-existent submission:\n";
try {
  $gradingService->gradeSubmission(999);
} catch (InvalidStateTransitionException $e) {
  echo "   ✓ Caught: {$e->getMessage()}\n";
}

echo "└────────────────────────────────────────────────────────────────────────────┘\n\n";

// ============================================================================
// SUMMARY
// ============================================================================

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "SUMMARY - OOP Pillars & SOLID Principles Demonstrated\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

echo "✓ ENCAPSULATION:\n";
echo "  - Private properties in all classes (id, name, email, status, etc.)\n";
echo "  - Controlled access via getter methods\n";
echo "  - Validation in constructors\n\n";

echo "✓ ABSTRACTION:\n";
echo "  - Abstract Exam class defines contract\n";
echo "  - Interfaces (Gradable, Notifiable, RepositoryInterface)\n";
echo "  - Services depend on interfaces, not concrete classes\n\n";

echo "✓ INHERITANCE:\n";
echo "  - Student extends User (inherits id, name, email)\n";
echo "  - Instructor extends User (inherits + adds specialty)\n";
echo "  - MultipleChoiceExam extends Exam\n";
echo "  - PracticalExam extends Exam\n";
echo "  - InvalidStateTransitionException extends DomainException\n\n";

echo "✓ POLYMORPHISM:\n";
echo "  - exam->grade() works differently for each exam type\n";
echo "  - notifier->send() works for Email, SMS, Log differently\n";
echo "  - Same registerStudent() for any exam type\n";
echo "  - No type checking (instanceof) needed!\n\n";

echo "✓ SOLID PRINCIPLES:\n";
echo "  - S: Each class has one responsibility\n";
echo "  - O: Open for extension (new exam types), closed for modification\n";
echo "  - L: Any Exam subtype can be used where Exam is expected\n";
echo "  - I: Depend on specific interfaces\n";
echo "  - D: Services depend on interfaces, not concrete classes\n\n";

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "Demo completed successfully! ✓\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
