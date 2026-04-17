<?php

/**
 * ============================================================================
 * SOLID PRINCIPLES - Comprehensive Code Examples
 * ============================================================================
 * 
 * S - Single Responsibility Principle
 * O - Open/Closed Principle
 * L - Liskov Substitution Principle
 * I - Interface Segregation Principle
 * D - Dependency Inversion Principle
 * 
 * Each principle includes:
 * - ❌ Violation example (bad code)
 * - ✅ Correct implementation (good code)
 * - 💡 Explanation of benefits
 * 
 * ============================================================================
 */

declare(strict_types=1);

// ============================================================================
// S - SINGLE RESPONSIBILITY PRINCIPLE
// ============================================================================
// A class should have ONE reason to change

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "S - SINGLE RESPONSIBILITY PRINCIPLE\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// ❌ VIOLATION: User class doing too much
echo "❌ VIOLATION - Multiple Responsibilities:\n";
echo "───────────────────────────────────────────────────────────────\n";

class UserBad
{
  private string $name;
  private string $email;

  public function __construct(string $name, string $email)
  {
    $this->name = $name;
    $this->email = $email;
  }

  // Responsibility 1: User data
  public function getName(): string
  {
    return $this->name;
  }

  // Responsibility 2: Database operations
  public function saveToDatabase(): void
  {
    echo "Saving {$this->email} to database...\n";
  }

  // Responsibility 3: Email sending
  public function sendWelcomeEmail(): void
  {
    echo "Sending welcome email to {$this->email}...\n";
  }

  // Responsibility 4: Validation
  public function validateEmail(): bool
  {
    return strpos($this->email, '@') !== false;
  }

  // Responsibility 5: Logging
  public function logUserCreation(): void
  {
    echo "Logging: User {$this->name} created\n";
  }
}

echo "Problems:\n";
echo "- 5 reasons to change this class!\n";
echo "- Hard to test\n";
echo "- Hard to reuse\n";
echo "- Tight coupling\n\n";

// ✅ CORRECT: Single responsibility for each class
echo "✅ CORRECT - Single Responsibilities:\n";
echo "───────────────────────────────────────────────────────────────\n";

class User
{
  private string $name;
  private string $email;

  public function __construct(string $name, string $email)
  {
    $this->name = $name;
    $this->email = $email;
  }

  public function getName(): string
  {
    return $this->name;
  }

  public function getEmail(): string
  {
    return $this->email;
  }
}

class UserRepository
{
  public function save(User $user): void
  {
    echo "Saving {$user->getEmail()} to database...\n";
  }
}

class EmailService
{
  public function sendWelcomeEmail(User $user): void
  {
    echo "Sending welcome email to {$user->getEmail()}...\n";
  }
}

class EmailValidator
{
  public function validate(string $email): bool
  {
    return strpos($email, '@') !== false;
  }
}

class Logger
{
  public function logUserCreation(User $user): void
  {
    echo "Logging: User {$user->getName()} created\n";
  }
}

echo "Benefits:\n";
echo "✓ Each class has ONE reason to change\n";
echo "✓ Easy to test (test each class independently)\n";
echo "✓ Easy to reuse (use any class in different contexts)\n";
echo "✓ Loose coupling (classes don't depend on each other)\n\n";

// ============================================================================
// O - OPEN/CLOSED PRINCIPLE
// ============================================================================
// Open for extension, closed for modification

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "O - OPEN/CLOSED PRINCIPLE\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// ❌ VIOLATION: Must modify class to add new payment methods
echo "❌ VIOLATION - Closed for Extension:\n";
echo "───────────────────────────────────────────────────────────────\n";

class PaymentProcessorBad
{
  public function process(string $type, float $amount): void
  {
    if ($type === 'credit_card') {
      echo "Processing credit card payment: \$$amount\n";
    } elseif ($type === 'paypal') {
      echo "Processing PayPal payment: \$$amount\n";
    } elseif ($type === 'bank_transfer') {
      echo "Processing bank transfer: \$$amount\n";
    }
    // Adding new payment method? MUST MODIFY THIS CLASS!
  }
}

echo "Problems:\n";
echo "- Adding new payment method = modify existing code\n";
echo "- Risk breaking existing functionality\n";
echo "- Violates Open/Closed Principle\n\n";

// ✅ CORRECT: Use polymorphism for extension
echo "✅ CORRECT - Open for Extension:\n";
echo "───────────────────────────────────────────────────────────────\n";

interface PaymentMethod
{
  public function process(float $amount): void;
}

class CreditCardPayment implements PaymentMethod
{
  public function process(float $amount): void
  {
    echo "Processing credit card payment: \$$amount\n";
  }
}

class PayPalPayment implements PaymentMethod
{
  public function process(float $amount): void
  {
    echo "Processing PayPal payment: \$$amount\n";
  }
}

class BankTransferPayment implements PaymentMethod
{
  public function process(float $amount): void
  {
    echo "Processing bank transfer: \$$amount\n";
  }
}

// New payment method? Just create new class, NO changes needed!
class CryptoPayment implements PaymentMethod
{
  public function process(float $amount): void
  {
    echo "Processing crypto payment: \$$amount\n";
  }
}

class PaymentProcessor
{
  private PaymentMethod $paymentMethod;

  public function __construct(PaymentMethod $paymentMethod)
  {
    $this->paymentMethod = $paymentMethod;
  }

  public function process(float $amount): void
  {
    $this->paymentMethod->process($amount);
  }
}

echo "Benefits:\n";
echo "✓ Add new payment types WITHOUT modifying existing code\n";
echo "✓ Closed for modification (stable)\n";
echo "✓ Open for extension (flexible)\n";
echo "✓ Reduces risk of breaking existing functionality\n\n";

// ============================================================================
// L - LISKOV SUBSTITUTION PRINCIPLE
// ============================================================================
// Subtypes must be substitutable for their base type

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "L - LISKOV SUBSTITUTION PRINCIPLE\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// ❌ VIOLATION: Rectangle and Square breaks contract
echo "❌ VIOLATION - Breaking Substitution:\n";
echo "───────────────────────────────────────────────────────────────\n";

class RectangleBad
{
  protected int $width;
  protected int $height;

  public function setWidth(int $width): void
  {
    $this->width = $width;
  }

  public function setHeight(int $height): void
  {
    $this->height = $height;
  }

  public function getArea(): int
  {
    return $this->width * $this->height;
  }
}

class SquareBad extends RectangleBad
{
  // Square breaks Rectangle contract!
  // Width and height MUST be equal
  public function setWidth(int $width): void
  {
    $this->width = $width;
    $this->height = $width; // Forces height to match
  }

  public function setHeight(int $height): void
  {
    $this->height = $height;
    $this->width = $height; // Forces width to match
  }
}

// This breaks LSP!
$rect = new RectangleBad();
$rect->setWidth(5);
$rect->setHeight(10);
echo "Rectangle area: {$rect->getArea()} (expected 50)\n";

$square = new SquareBad();
$square->setWidth(5);
$square->setHeight(10);
echo "Square area: {$square->getArea()} (expected 50, got " . $square->getArea() . ")\n";
echo "❌ Substitution broke! Square doesn't behave like Rectangle\n\n";

// ✅ CORRECT: Different hierarchies
echo "✅ CORRECT - Proper Substitution:\n";
echo "───────────────────────────────────────────────────────────────\n";

interface Shape
{
  public function getArea(): float;
}

class Rectangle implements Shape
{
  private float $width;
  private float $height;

  public function __construct(float $width, float $height)
  {
    $this->width = $width;
    $this->height = $height;
  }

  public function getArea(): float
  {
    return $this->width * $this->height;
  }
}

class Square implements Shape
{
  private float $side;

  public function __construct(float $side)
  {
    $this->side = $side;
  }

  public function getArea(): float
  {
    return $this->side * $this->side;
  }
}

$shapes = [
  new Rectangle(5, 10),
  new Square(7)
];

foreach ($shapes as $shape) {
  echo "Area: {$shape->getArea()}\n"; // LSP: works correctly for all!
}

echo "Benefits:\n";
echo "✓ Subtypes reliably substitute for parent types\n";
echo "✓ No surprising behavior\n";
echo "✓ Polymorphism works as expected\n\n";

// ============================================================================
// I - INTERFACE SEGREGATION PRINCIPLE
// ============================================================================
// Clients shouldn't depend on interfaces they don't use

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "I - INTERFACE SEGREGATION PRINCIPLE\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// ❌ VIOLATION: Fat interface with unrelated methods
echo "❌ VIOLATION - Fat Interface:\n";
echo "───────────────────────────────────────────────────────────────\n";

interface WorkerBad
{
  public function work(): void;
  public function eat(): void;
  public function sleep(): void;
  public function recharge(): void;
}

class HumanWorkerBad implements WorkerBad
{
  public function work(): void
  {
    echo "Human working...\n";
  }
  public function eat(): void
  {
    echo "Human eating...\n";
  }
  public function sleep(): void
  {
    echo "Human sleeping...\n";
  }
  public function recharge(): void
  {
    echo "Human recharged\n";
  }
}

// Robot needs to work but NOT eat/sleep/recharge (forced to implement!)
class RobotBad implements WorkerBad
{
  public function work(): void
  {
    echo "Robot working...\n";
  }
  public function eat(): void
  {
    throw new Exception("Robots don't eat!");
  }
  public function sleep(): void
  {
    throw new Exception("Robots don't sleep!");
  }
  public function recharge(): void
  {
    echo "Robot recharging battery...\n";
  }
}

echo "Problems:\n";
echo "❌ Robot forced to implement eat() and sleep()\n";
echo "❌ Interface too fat (too many responsibilities)\n";
echo "❌ Not all implementers need all methods\n\n";

// ✅ CORRECT: Segregated interfaces
echo "✅ CORRECT - Segregated Interfaces:\n";
echo "───────────────────────────────────────────────────────────────\n";

interface Workable
{
  public function work(): void;
}

interface Eatable
{
  public function eat(): void;
}

interface Sleepable
{
  public function sleep(): void;
}

interface Rechargeable
{
  public function recharge(): void;
}

class HumanWorker implements Workable, Eatable, Sleepable
{
  public function work(): void
  {
    echo "Human working...\n";
  }
  public function eat(): void
  {
    echo "Human eating...\n";
  }
  public function sleep(): void
  {
    echo "Human sleeping...\n";
  }
}

class Robot implements Workable, Rechargeable
{
  public function work(): void
  {
    echo "Robot working...\n";
  }
  public function recharge(): void
  {
    echo "Robot recharging...\n";
  }
}

echo "Benefits:\n";
echo "✓ Each interface has single purpose\n";
echo "✓ Classes implement only needed interfaces\n";
echo "✓ No forced empty implementations\n";
echo "✓ More flexible and cleaner\n\n";

// ============================================================================
// D - DEPENDENCY INVERSION PRINCIPLE
// ============================================================================
// Depend on abstractions, not concrete implementations

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "D - DEPENDENCY INVERSION PRINCIPLE\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// ❌ VIOLATION: Direct dependency on concrete class
echo "❌ VIOLATION - Direct Dependencies:\n";
echo "───────────────────────────────────────────────────────────────\n";

class MySQLDatabase
{
  public function query(string $sql): array
  {
    echo "Executing MySQL query: $sql\n";
    return [];
  }
}

class UserServiceBad
{
  private MySQLDatabase $database; // Depends on concrete class!

  public function __construct()
  {
    $this->database = new MySQLDatabase(); // Tight coupling!
  }

  public function getUser(int $id): void
  {
    $this->database->query("SELECT * FROM users WHERE id = $id");
  }
}

echo "Problems:\n";
echo "❌ Tightly coupled to MySQLDatabase\n";
echo "❌ Can't use with PostgreSQL or MongoDB\n";
echo "❌ Hard to test (can't inject mock database)\n";
echo "❌ Violates Dependency Inversion\n\n";

// ✅ CORRECT: Depend on abstractions
echo "✅ CORRECT - Abstraction Dependencies:\n";
echo "───────────────────────────────────────────────────────────────\n";

interface Database
{
  public function query(string $sql): array;
}

class MySQLDatabaseGood implements Database
{
  public function query(string $sql): array
  {
    echo "Executing MySQL query: $sql\n";
    return [];
  }
}

class PostgreSQLDatabase implements Database
{
  public function query(string $sql): array
  {
    echo "Executing PostgreSQL query: $sql\n";
    return [];
  }
}

class UserService
{
  private Database $database; // Depends on abstraction!

  public function __construct(Database $database) // Inject dependency
  {
    $this->database = $database;
  }

  public function getUser(int $id): void
  {
    $this->database->query("SELECT * FROM users WHERE id = $id");
  }
}

// Can use with ANY database!
$mysqlService = new UserService(new MySQLDatabaseGood());
$mysqlService->getUser(1);

$postgresService = new UserService(new PostgreSQLDatabase());
$postgresService->getUser(1);

echo "Benefits:\n";
echo "✓ Loosely coupled\n";
echo "✓ Can swap implementations easily\n";
echo "✓ Easy to test (inject mock)\n";
echo "✓ Flexible and maintainable\n\n";

// ============================================================================
// REAL-WORLD EXAMPLE: Refactoring a Fat Controller
// ============================================================================

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "REAL-WORLD: Refactoring Fat Controller\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// ❌ FAT CONTROLLER VIOLATION
echo "❌ BEFORE - Fat Controller (All violations):\n";
echo "───────────────────────────────────────────────────────────────\n";

class UserControllerBad
{
  // This controller violates ALL SOLID principles!

  public function registerUser(array $data): void
  {
    // Validation (SRP violation: validation responsibility)
    if (!isset($data['email']) || strpos($data['email'], '@') === false) {
      echo "Invalid email\n";
      return;
    }

    // Database (SRP violation: data access responsibility)
    $mysqli = new mysqli('localhost', 'user', 'pass', 'db');
    $email = $mysqli->real_escape_string($data['email']);
    $result = $mysqli->query("INSERT INTO users (email) VALUES ('$email')");

    // Email sending (SRP violation: notification responsibility)
    mail($data['email'], 'Welcome', 'Welcome to our site');

    // Logging (SRP violation: logging responsibility)
    echo "Logged: User {$data['email']} registered\n";

    // Hard-coded dependency (DIP violation: depends on concrete classes)
    // No testing possible, no flexibility
  }
}

echo "Problems:\n";
echo "❌ Single class does 5 different things\n";
echo "❌ Hard-coded dependencies (MySQLi, mail)\n";
echo "❌ Impossible to test in isolation\n";
echo "❌ Violates SRP, DIP, OCP\n\n";

// ✅ REFACTORED: SOLID-compliant version
echo "✅ AFTER - Refactored with SOLID:\n";
echo "───────────────────────────────────────────────────────────────\n";

interface EmailValidatorInterface
{
  public function isValid(string $email): bool;
}

class SimpleEmailValidator implements EmailValidatorInterface
{
  public function isValid(string $email): bool
  {
    return strpos($email, '@') !== false;
  }
}

interface UserRepositoryInterface
{
  public function create(string $email): void;
}

class UserRepositoryInMemory implements UserRepositoryInterface
{
  private array $users = [];

  public function create(string $email): void
  {
    $this->users[] = $email;
    echo "User saved to repository: $email\n";
  }
}

interface NotificationServiceInterface
{
  public function sendWelcome(string $email): void;
}

class EmailNotificationService implements NotificationServiceInterface
{
  public function sendWelcome(string $email): void
  {
    echo "Sending welcome email to: $email\n";
  }
}

class UserController
{
  private EmailValidatorInterface $validator;
  private UserRepositoryInterface $repository;
  private NotificationServiceInterface $notificationService;

  // D: Dependency Injection (DIP)
  public function __construct(
    EmailValidatorInterface $validator,
    UserRepositoryInterface $repository,
    NotificationServiceInterface $notificationService
  ) {
    $this->validator = $validator;
    $this->repository = $repository;
    $this->notificationService = $notificationService;
  }

  // S: Single Responsibility - controller only orchestrates
  public function registerUser(array $data): void
  {
    // Delegate to specialized classes
    if (!$this->validator->isValid($data['email'])) {
      echo "Invalid email\n";
      return;
    }

    $this->repository->create($data['email']);
    $this->notificationService->sendWelcome($data['email']);

    echo "Registration complete!\n";
  }
}

// Usage with dependency injection
$validator = new SimpleEmailValidator();
$repository = new UserRepositoryInMemory();
$notificationService = new EmailNotificationService();

$controller = new UserController($validator, $repository, $notificationService);
$controller->registerUser(['email' => 'user@example.com']);

echo "\nBenefits of Refactoring:\n";
echo "✓ Each class has one responsibility (SRP)\n";
echo "✓ Easy to test (inject mocks)\n";
echo "✓ Can swap implementations (DIP)\n";
echo "✓ Can add new validators/notifiers (OCP)\n";
echo "✓ No unnecessary dependencies (ISP)\n\n";

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "SOLID principles complete! Your code is now clean, testable, and maintainable.\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
