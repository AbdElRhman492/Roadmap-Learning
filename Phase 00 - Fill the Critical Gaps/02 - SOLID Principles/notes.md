# 02 - SOLID Principles

## Overview

**SOLID** is an acronym for five design principles that make software more understandable, flexible, and maintainable. These principles help you write code that is easier to test, refactor, and extend.

---

## S — Single Responsibility Principle (SRP)

### Definition

**A class should have only one reason to change.** A class should have one job or responsibility.

### The Problem

When a class has multiple responsibilities:

- It's harder to test
- It's harder to reuse
- Changes to one responsibility affect others
- Code becomes fragile and tightly coupled

### Example: Bad ❌

```php
class User
{
  public function getName(): string { ... }

  // Multiple responsibilities below:
  public function saveToDatabase(): void { ... }  // Responsibility 1
  public function sendEmail(): void { ... }        // Responsibility 2
  public function generateReport(): void { ... }   // Responsibility 3
  public function validateEmail(): bool { ... }    // Responsibility 4
}
// Reasons to change: Database structure, email service, report format, validation rules
```

### Example: Good ✅

```php
class User
{
  public function getName(): string { ... }
}

class UserRepository
{
  public function save(User $user): void { ... }
}

class EmailService
{
  public function send(User $user): void { ... }
}

class UserReportGenerator
{
  public function generate(User $user): array { ... }
}
// Each class has ONE reason to change
```

### Benefits

- ✓ Easier to test (test each responsibility separately)
- ✓ Easier to reuse (use classes in different contexts)
- ✓ Easier to maintain (changes to one responsibility don't affect others)
- ✓ Clearer code (each class has clear purpose)

---

## O — Open/Closed Principle (OCP)

### Definition

**Software entities should be open for extension but closed for modification.** You should be able to add new functionality without changing existing code.

### The Problem

When you must modify existing code to add features:

- Risk breaking existing functionality
- Code becomes fragile
- Each change requires re-testing everything

### Example: Bad ❌

```php
class PaymentProcessor
{
  public function process(string $type, float $amount): void
  {
    if ($type === 'credit_card') {
      // Process credit card
    } elseif ($type === 'paypal') {
      // Process PayPal
    } elseif ($type === 'bank_transfer') {
      // Process bank transfer
    }
    // Adding new payment method? MUST MODIFY THIS CLASS!
  }
}
```

### Example: Good ✅

```php
interface PaymentMethod
{
  public function process(float $amount): void;
}

class CreditCardPayment implements PaymentMethod
{
  public function process(float $amount): void { ... }
}

class PayPalPayment implements PaymentMethod
{
  public function process(float $amount): void { ... }
}

class PaymentProcessor
{
  public function __construct(private PaymentMethod $method) {}

  public function process(float $amount): void
  {
    $this->method->process($amount);
  }
}

// Add new payment method WITHOUT modifying PaymentProcessor!
class CryptoPayment implements PaymentMethod
{
  public function process(float $amount): void { ... }
}
```

### Benefits

- ✓ Add features without modifying existing code
- ✓ Reduce risk of breaking existing functionality
- ✓ Code is stable and extensible
- ✓ Better for team development (no conflicts)

---

## L — Liskov Substitution Principle (LSP)

### Definition

**Objects of a superclass should be replaceable with objects of its subclasses without breaking the application.** Subtypes must behave like their parent types.

### The Problem

When a subclass doesn't fulfill the contract of its parent:

- Polymorphism doesn't work as expected
- Code breaks in surprising ways
- Developers must use `instanceof` checks

### Example: Bad ❌

```php
class Rectangle
{
  protected int $width;
  protected int $height;

  public function setWidth(int $w): void { $this->width = $w; }
  public function setHeight(int $h): void { $this->height = $h; }
  public function getArea(): int { return $this->width * $this->height; }
}

// This BREAKS Liskov Substitution!
class Square extends Rectangle
{
  public function setWidth(int $w): void
  {
    $this->width = $w;
    $this->height = $w; // Forces height!
  }

  public function setHeight(int $h): void
  {
    $this->height = $h;
    $this->width = $h; // Forces width!
  }
}

// Client code expects Rectangle behavior
$rect = new Rectangle();
$rect->setWidth(5);
$rect->setHeight(10);
// Works: area = 50

// But Square breaks the contract!
$square = new Square();
$square->setWidth(5);
$square->setHeight(10);
// Area = 100 (not 50!) - Surprise!
```

### Example: Good ✅

```php
interface Shape
{
  public function getArea(): float;
}

class Rectangle implements Shape
{
  public function __construct(private float $width, private float $height) {}
  public function getArea(): float { return $this->width * $this->height; }
}

class Square implements Shape
{
  public function __construct(private float $side) {}
  public function getArea(): float { return $this->side * $this->side; }
}

// Both can substitute for Shape interface
$shapes = [new Rectangle(5, 10), new Square(7)];
foreach ($shapes as $shape) {
  echo $shape->getArea(); // Works correctly for all!
}
```

### Benefits

- ✓ Polymorphism works correctly
- ✓ No surprises or special cases
- ✓ Code is predictable
- ✓ No need for type checking

---

## I — Interface Segregation Principle (ISP)

### Definition

**Clients should not be forced to depend on interfaces they don't use.** Use multiple specific interfaces instead of one large general-purpose interface.

### The Problem

When interfaces are too large (fat interfaces):

- Implementing classes must implement methods they don't need
- Empty implementations or exceptions
- Classes depend on more than they use

### Example: Bad ❌

```php
interface Worker
{
  public function work(): void;
  public function eat(): void;
  public function sleep(): void;
  public function recharge(): void;
}

class HumanWorker implements Worker
{
  public function work(): void { /* work */ }
  public function eat(): void { /* eat */ }
  public function sleep(): void { /* sleep */ }
  public function recharge(): void { /* rest */ }
}

// Robot must implement ALL methods, even if not needed!
class Robot implements Worker
{
  public function work(): void { /* work */ }
  public function eat(): void { throw new Exception("Robots don't eat!"); }
  public function sleep(): void { throw new Exception("Robots don't sleep!"); }
  public function recharge(): void { /* charge battery */ }
}
```

### Example: Good ✅

```php
interface Workable { public function work(): void; }
interface Eatable { public function eat(): void; }
interface Sleepable { public function sleep(): void; }
interface Rechargeable { public function recharge(): void; }

class HumanWorker implements Workable, Eatable, Sleepable
{
  public function work(): void { /* work */ }
  public function eat(): void { /* eat */ }
  public function sleep(): void { /* sleep */ }
}

// Robot only implements what it needs
class Robot implements Workable, Rechargeable
{
  public function work(): void { /* work */ }
  public function recharge(): void { /* charge */ }
}
```

### Benefits

- ✓ Classes implement only needed interfaces
- ✓ No forced empty implementations
- ✓ Interfaces are more focused and reusable
- ✓ Easier to understand requirements

---

## D — Dependency Inversion Principle (DIP)

### Definition

**High-level modules should not depend on low-level modules. Both should depend on abstractions.** Always depend on interfaces or abstract classes, not concrete implementations.

### The Problem

When classes depend directly on concrete implementations:

- Tightly coupled code
- Hard to test (can't inject mocks)
- Hard to change implementations
- Code is fragile

### Example: Bad ❌

```php
class MySQLDatabase
{
  public function query(string $sql): array { /* ... */ }
}

class UserService
{
  private MySQLDatabase $database;

  public function __construct()
  {
    $this->database = new MySQLDatabase(); // Tight coupling!
  }

  public function getUser(int $id): void
  {
    $this->database->query("SELECT * FROM users WHERE id = $id");
  }
}
// Can't test with mock database
// Can't use with PostgreSQL
// Hard to change database type
```

### Example: Good ✅

```php
interface Database
{
  public function query(string $sql): array;
}

class MySQLDatabase implements Database
{
  public function query(string $sql): array { /* ... */ }
}

class PostgreSQLDatabase implements Database
{
  public function query(string $sql): array { /* ... */ }
}

class UserService
{
  public function __construct(private Database $database) {} // Inject abstraction!

  public function getUser(int $id): void
  {
    $this->database->query("SELECT * FROM users WHERE id = $id");
  }
}

// Can use with ANY database implementation
$mysqlService = new UserService(new MySQLDatabase());
$postgresService = new UserService(new PostgreSQLDatabase());

// Easy to test with mock
$mockDb = new MockDatabase();
$testService = new UserService($mockDb);
```

### Benefits

- ✓ Loosely coupled code
- ✓ Easy to test (inject mocks)
- ✓ Easy to swap implementations
- ✓ Code is flexible and maintainable

---

## How SOLID Principles Work Together

The OOP project demonstrates all SOLID principles:

| Principle | Implementation in Project                                                              |
| --------- | -------------------------------------------------------------------------------------- |
| **S**     | Each class has one job: `User`, `Exam`, `Student`, `Repository`, `Service`             |
| **O**     | New exam types added without modifying services                                        |
| **L**     | `MultipleChoiceExam` and `PracticalExam` substitute for `Exam`                         |
| **I**     | Specific interfaces: `Gradable`, `Notifiable`, `RepositoryInterface`                   |
| **D**     | Services depend on interfaces: `ExamRepositoryInterface`, `StudentRepositoryInterface` |

---

## Identifying SOLID Violations in Real Code

### Red Flags for SRP Violations

- Class name has "And" in it
- Class has multiple public methods doing different things
- Class needs multiple reasons to change
- Difficult to describe what class does in one sentence

### Red Flags for OCP Violations

- Need to modify existing class to add new feature
- Many `if/else` or `switch` statements
- Hard to test new features without breaking old ones

### Red Flags for LSP Violations

- Subclass throws `NotImplementedException`
- Subclass behaves unexpectedly compared to parent
- Need `instanceof` checks in client code

### Red Flags for ISP Violations

- Implement interface but not all methods are needed
- Methods throw exceptions (not implemented)
- Classes depend on more than they use

### Red Flags for DIP Violations

- Direct instantiation of dependencies with `new`
- Hard-coded class names
- Difficult to test (can't inject mocks)

---

## Real-World Refactoring Example

### Before: Fat Controller (SOLID Violations)

```php
class UserController
{
  public function registerUser(array $data): void
  {
    // SRP: Validation logic
    if (strlen($data['password']) < 8) {
      throw new Exception("Password too short");
    }

    // SRP: Database logic
    $pdo = new PDO('mysql:...');
    $stmt = $pdo->prepare("INSERT INTO users VALUES (?, ?)");
    $stmt->execute([$data['email'], password_hash($data['password'], PASSWORD_BCRYPT)]);

    // SRP: Email logic
    mail($data['email'], 'Welcome', 'Welcome!');

    // DIP: Hard-coded dependencies
    // ISP: Does too much
    // OCP: Can't extend without modification
  }
}
```

### After: SOLID-Compliant Version

```php
class UserController
{
  public function __construct(
    private UserValidator $validator,
    private UserRepository $repository,
    private NotificationService $notificationService
  ) {}

  public function registerUser(array $data): void
  {
    $this->validator->validate($data);
    $user = $this->repository->create($data);
    $this->notificationService->sendWelcome($user);
  }
}

// Each class has one responsibility
// Dependencies injected (not hard-coded)
// Easy to test, extend, modify
```

---

## Summary Checklist

- [ ] **S**: Each class has one reason to change
- [ ] **O**: Open for extension, closed for modification
- [ ] **L**: Subtypes are substitutable for parent types
- [ ] **I**: Don't force implementation of unused methods
- [ ] **D**: Depend on abstractions, not concretions

When all five principles are followed, your code becomes:

- ✓ More testable
- ✓ More flexible
- ✓ More maintainable
- ✓ More understandable
- ✓ More professional

---

## Next Steps

1. Review the `code-examples.php` file for practical demonstrations
2. Identify SOLID violations in your current projects
3. Refactor one piece of code using these principles
4. Apply SOLID principles to future projects from the start
