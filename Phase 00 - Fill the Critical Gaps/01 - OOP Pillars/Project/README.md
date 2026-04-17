# Project: OOP Pillars - Exam Management Core (No UI)

Build a backend-only PHP project that simulates a real exam workflow.

You will create and schedule exams, register students, collect submissions, grade them, and send notifications.

No frontend, no framework, no database required.

---

## 1) Project Brief

You are building the core logic for a training institute.

The institute needs a system to:

1. Create exams
2. Publish and schedule exams
3. Register students
4. Accept submissions
5. Grade submissions
6. Notify students with results

This project is designed to force you to apply:

- Encapsulation
- Abstraction
- Inheritance
- Polymorphism

---

## 2) Learning Objectives

By the end of this project, you should be able to:

- Model domain objects with private state and controlled behavior (encapsulation)
- Design contracts with interfaces and abstract classes (abstraction)
- Reuse common behavior with base classes (inheritance)
- Swap behaviors using interface types and method overriding (polymorphism)
- Apply basic SOLID thinking (SRP + DIP) in a small architecture

---

## 3) Technical Rules

- Use PHP 8+
- Use `declare(strict_types=1);` in all PHP files
- No HTML, no JS, no CSS
- Use CLI output (`echo`) only for simulation
- No global mutable state
- Use constructor dependency injection for services
- Throw exceptions for invalid state transitions

---

## 4) Required Architecture

### Core Entities

- `User` (optional base)
- `Student`
- `Instructor`
- `Exam` (abstract)
- `MultipleChoiceExam`
- `PracticalExam`
- `ScheduledExam`
- `Submission`
- `Result`

### Contracts (Interfaces)

- `Gradable` -> `grade(Submission $submission): Result`
- `Notifiable` -> `send(string $recipient, string $message): void`
- `RepositoryInterface` (or specific repo contracts)

### Services

- `ExamService`
- `RegistrationService`
- `GradingService`
- `NotificationService`

### Repositories

- `StudentRepository`
- `ExamRepository`
- `SubmissionRepository`

Use in-memory repositories first (arrays). File/JSON repositories can be a stretch goal.

---

## 5) Suggested Folder Structure

```text
Project/
  src/
	Contracts/
	  Gradable.php
	  Notifiable.php
	  StudentRepositoryInterface.php
	  ExamRepositoryInterface.php
	  SubmissionRepositoryInterface.php
	Entities/
	  User.php
	  Student.php
	  Instructor.php
	  Exam.php
	  MultipleChoiceExam.php
	  PracticalExam.php
	  ScheduledExam.php
	  Submission.php
	  Result.php
	Services/
	  ExamService.php
	  RegistrationService.php
	  GradingService.php
	  NotificationService.php
	Repositories/
	  InMemoryStudentRepository.php
	  InMemoryExamRepository.php
	  InMemorySubmissionRepository.php
	Notifications/
	  EmailNotifier.php
	  SmsNotifier.php
	  LogNotifier.php
	Exceptions/
	  DomainException.php
	  InvalidStateTransitionException.php
  index.php
```

---

## 6) Stage-by-Stage Implementation Plan (With Hints)

## Stage 1 - Model the Domain Objects

Create your entity classes with private properties and constructor validation.

Minimum fields suggestion:

- `Student`: id, name, email
- `Instructor`: id, name, specialty
- `Exam`: id, title, maxScore, status (`draft|published`)
- `ScheduledExam`: examId, dateTime, registrationOpen, capacity
- `Submission`: studentId, examId, payload, submittedAt
- `Result`: studentId, examId, score, passed, isFinal

**Hints:**

- Keep entities focused on business rules, not storage.
- Add meaningful methods: `publish()`, `openRegistration()`, `closeRegistration()`, `submit()`, `markFinal()`.
- Reject invalid input early (empty title, negative max score, etc.).

## Stage 2 - Abstraction with Contracts

Define interfaces for behavior that can have multiple implementations.

**Hints:**

- `Gradable` should be implemented by exam types.
- `Notifiable` should allow swapping email/SMS/log channels.
- Type-hint services against interfaces, not concrete classes.

## Stage 3 - Inheritance + Polymorphism for Exam Types

Create `Exam` as an abstract class and extend it with:

- `MultipleChoiceExam`
- `PracticalExam`

Each exam type must implement grading differently.

**Hints:**

- `MultipleChoiceExam` can compare answers against answer key.
- `PracticalExam` can use criteria weights or a manual score simulation.
- In `GradingService`, call the same method (`grade`) regardless of exam type.

## Stage 4 - Repositories (In-Memory)

Create repository contracts and in-memory implementations.

**Hints:**

- Start with arrays keyed by ID.
- Add `save()`, `findById()`, and `all()` methods.
- Do not mix domain logic into repositories.

## Stage 5 - Services and Orchestration

Implement use-case services:

- `ExamService`: create, publish, schedule
- `RegistrationService`: register students with capacity/state checks
- `GradingService`: load exam + submission and produce final result
- `NotificationService`: send message through injected notifiers

**Hints:**

- Keep each service responsible for one workflow area.
- Throw domain exceptions for forbidden actions.
- Use composition: services depend on repos + contracts.

## Stage 6 - Runner Script (`index.php`)

Simulate a full workflow end-to-end:

1. Create instructor and students
2. Create exams
3. Publish and schedule an exam
4. Open registration and register students
5. Accept submission
6. Grade submission
7. Notify student
8. Print final report

**Hints:**

- Include both success and failure scenarios (e.g., register when closed).
- Use try/catch to show proper error handling.
- Keep script readable; this is your integration test.

---

## 7) Real-World Constraints to Enforce

Your code must prevent:

- Grading before exam is published
- Registering when registration is closed
- Submitting after deadline (if you add deadline)
- Duplicate registration for same student/exam
- Final result overwrite after `markFinal()`

---

## 8) Definition of Done (Acceptance Checklist)

- [ ] All 4 OOP pillars are visible in code design
- [ ] At least 2 interfaces are used by multiple implementations
- [ ] At least 1 abstract class is used correctly
- [ ] Services depend on contracts where possible
- [ ] Invalid state transitions throw clear exceptions
- [ ] `index.php` demonstrates complete business flow
- [ ] Code is organized by responsibility and easy to navigate

---

## 9) Stretch Goals (Advanced)

1. Add `OnlineExam` without modifying existing grading orchestration.
2. Add `JsonStudentRepository` and swap repo via dependency injection.
3. Add `PassPolicy` strategy (e.g., fixed threshold vs weighted criteria).
4. Add simple tests for `GradingService` and entity state transitions.
5. Add a tiny event system (`ResultPublishedEvent` + listener notifier).

---

## 10) Self-Evaluation Rubric (100 points)

- 25 pts: Correct OOP pillar usage
- 20 pts: Clean architecture and class responsibility
- 20 pts: Polymorphic grading + interface-driven design
- 15 pts: Robust validation and exception handling
- 10 pts: Readable naming and folder structure
- 10 pts: End-to-end workflow quality in `index.php`

Score guide:

- 90-100: Production-ready learning project
- 75-89: Strong, with minor design issues
- 60-74: Works but architecture needs improvement
- <60: Rebuild core object model and responsibilities

---

## 11) Optional Run Commands

If your entry file is `index.php`, run:

```bash
php index.php
```

If you organize with Composer autoload (optional):

```bash
composer dump-autoload
php index.php
```

---

## 12) Submission Checklist (For Your Instructor)

Before sharing your solution, verify:

- [ ] No UI code exists
- [ ] README updated with your final design decisions
- [ ] `index.php` runs without fatal errors
- [ ] At least one invalid scenario is demonstrated and handled
- [ ] Your code shows both inheritance and composition

Good luck. Build it as if a real institute will use it next week.

classDiagram
%% Interfaces/Contracts
class Gradable {
<<interface>>
+grade(Submission): Result
}

    class Notifiable {
        <<interface>>
        +send(string, string): void
    }

    class StudentRepositoryInterface {
        <<interface>>
        +save(Student): void
        +findById(int): Student
        +all(): Student[]
    }

    class ExamRepositoryInterface {
        <<interface>>
        +save(Exam): void
        +findById(int): Exam
        +all(): Exam[]
    }

    class SubmissionRepositoryInterface {
        <<interface>>
        +save(Submission): void
        +findById(int): Submission
        +all(): Submission[]
    }

    %% Domain Entities
    class User {
        -id: int
        -name: string
        -email: string
        +getId(): int
        +getName(): string
        +getEmail(): string
    }

    class Student {
        -registrations: Exam[]
        +registerFor(Exam): void
        +getRegistrations(): Exam[]
    }

    class Instructor {
        -specialty: string
        +getSpecialty(): string
    }

    class Exam {
        <<abstract>>
        -id: int
        -title: string
        -maxScore: float
        -status: string
        +publish(): void
        +getId(): int
        +getTitle(): string
        +getMaxScore(): float
        +getStatus(): string
    }

    class MultipleChoiceExam {
        -answerKey: array
        +setAnswerKey(array): void
        +grade(Submission): Result
    }

    class PracticalExam {
        -criteria: array
        +setCriteria(array): void
        +grade(Submission): Result
    }

    class ScheduledExam {
        -examId: int
        -dateTime: DateTime
        -registrationOpen: bool
        -capacity: int
        -enrolledCount: int
        +openRegistration(): void
        +closeRegistration(): void
        +canRegister(): bool
        +getEnrolledCount(): int
    }

    class Submission {
        -id: int
        -studentId: int
        -examId: int
        -payload: string
        -submittedAt: DateTime
        +getId(): int
        +getStudentId(): int
        +getExamId(): int
        +getPayload(): string
        +getSubmittedAt(): DateTime
    }

    class Result {
        -id: int
        -studentId: int
        -examId: int
        -score: float
        -passed: bool
        -isFinal: bool
        +markFinal(): void
        +getId(): int
        +getScore(): float
        +isPassed(): bool
        +isFinal(): bool
    }

    %% Services
    class ExamService {
        -examRepo: ExamRepositoryInterface
        +createExam(type, title, maxScore): Exam
        +publishExam(id): void
        +scheduleExam(examId, dateTime): ScheduledExam
    }

    class RegistrationService {
        -studentRepo: StudentRepositoryInterface
        -examRepo: ExamRepositoryInterface
        +registerStudent(studentId, examId): void
        +canRegister(studentId, examId): bool
    }

    class GradingService {
        -examRepo: ExamRepositoryInterface
        -submissionRepo: SubmissionRepositoryInterface
        +grade(submissionId): Result
    }

    class NotificationService {
        -notifiers: Notifiable[]
        +addNotifier(Notifiable): void
        +notify(recipient, message): void
    }

    %% Repositories
    class InMemoryStudentRepository {
        -students: array
        +save(Student): void
        +findById(int): Student
        +all(): Student[]
    }

    class InMemoryExamRepository {
        -exams: array
        +save(Exam): void
        +findById(int): Exam
        +all(): Exam[]
    }

    class InMemorySubmissionRepository {
        -submissions: array
        +save(Submission): void
        +findById(int): Submission
        +all(): Submission[]
    }

    %% Notifiers
    class EmailNotifier {
        +send(recipient, message): void
    }

    class SmsNotifier {
        +send(recipient, message): void
    }

    class LogNotifier {
        +send(recipient, message): void
    }

    %% Exceptions
    class DomainException {
        <<exception>>
    }

    class InvalidStateTransitionException {
        <<exception>>
    }

    %% Relationships
    Student --|> User
    Instructor --|> User

    MultipleChoiceExam --|> Exam
    PracticalExam --|> Exam

    Exam ..|> Gradable
    MultipleChoiceExam ..|> Gradable
    PracticalExam ..|> Gradable

    EmailNotifier ..|> Notifiable
    SmsNotifier ..|> Notifiable
    LogNotifier ..|> Notifiable

    InMemoryStudentRepository ..|> StudentRepositoryInterface
    InMemoryExamRepository ..|> ExamRepositoryInterface
    InMemorySubmissionRepository ..|> SubmissionRepositoryInterface

    InvalidStateTransitionException --|> DomainException

    ExamService --> ExamRepositoryInterface
    RegistrationService --> StudentRepositoryInterface
    RegistrationService --> ExamRepositoryInterface
    GradingService --> ExamRepositoryInterface
    GradingService --> SubmissionRepositoryInterface
    NotificationService --> Notifiable

    ScheduledExam --> Exam
    Submission --> Student
    Submission --> Exam
    Result --> Student
    Result --> Exam
