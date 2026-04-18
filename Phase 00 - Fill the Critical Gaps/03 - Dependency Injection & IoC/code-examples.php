<?php

declare(strict_types=1);

/**
 * 03 - Dependency Injection & IoC (Inversion of Control)
 * 
 * This file covers the 7 core pillars of DI & IoC:
 * 1. What is DI and why it matters
 * 2. Constructor injection
 * 3. Method injection
 * 4. Service Container - resolution and binding
 * 5. Service Providers - boot() vs register()
 * 6. Binding interfaces to implementations
 * 7. Singletons in the container
 */

// ============================================================================
// PILLAR 1: WHAT IS DI AND WHY IT MATTERS
// ============================================================================

echo "=== PILLAR 1: What is DI and why it matters ===\n\n";

/**
 * PROBLEM: Tight Coupling (Without DI)
 * 
 * When a class creates its own dependencies, it's tightly coupled:
 * - Hard to test (can't mock dependencies)
 * - Hard to change implementations
 * - Difficult to maintain
 */

class PaymentProcessorBad
{
  private StripeGateway $stripe; // Tightly coupled!

  public function __construct()
  {
    $this->stripe = new StripeGateway(); // Creates its own dependency
  }

  public function charge(float $amount): bool
  {
    return $this->stripe->processCharge($amount);
  }
}

// Problem: To test this, we'd need real Stripe credentials!
// We can't swap Stripe for a mock implementation.

/**
 * SOLUTION: Loose Coupling (With DI)
 * 
 * When dependencies are injected, the class doesn't know WHO provides them:
 * - Loosely coupled
 * - Easy to test (inject mocks)
 * - Easy to swap implementations
 * - Easy to maintain
 */

interface PaymentGateway
{
  public function processCharge(float $amount): bool;
}

class StripeGateway implements PaymentGateway
{
  public function processCharge(float $amount): bool
  {
    // Real Stripe implementation
    echo "Processing $amount via Stripe\n";
    return true;
  }
}

class PayPalGateway implements PaymentGateway
{
  public function processCharge(float $amount): bool
  {
    // PayPal implementation
    echo "Processing $amount via PayPal\n";
    return true;
  }
}

// Good: Constructor injection with interface
class PaymentProcessorGood
{
  private PaymentGateway $gateway; // Depends on interface, not concrete class

  // Dependency is injected - we don't create it
  public function __construct(PaymentGateway $gateway)
  {
    $this->gateway = $gateway;
  }

  public function charge(float $amount): bool
  {
    return $this->gateway->processCharge($amount);
  }
}

echo "=== DI Benefits Demo ===\n";

// Stripe processor
$stripeProcessor = new PaymentProcessorGood(new StripeGateway());
$stripeProcessor->charge(100); // Processing 100 via Stripe

// PayPal processor - same class, different implementation!
$paypalProcessor = new PaymentProcessorGood(new PayPalGateway());
$paypalProcessor->charge(100); // Processing 100 via PayPal

// Mock processor for testing
class MockPaymentGateway implements PaymentGateway
{
  public function processCharge(float $amount): bool
  {
    echo "Mock: Charge of $amount authorized (test mode)\n";
    return true;
  }
}

$testProcessor = new PaymentProcessorGood(new MockPaymentGateway());
$testProcessor->charge(50); // Mock: Charge of 50 authorized (test mode)

echo "\n";


// ============================================================================
// PILLAR 2: CONSTRUCTOR INJECTION
// ============================================================================

echo "=== PILLAR 2: Constructor Injection ===\n\n";

/**
 * Constructor injection is the most common and recommended pattern.
 * Dependencies are injected via the constructor.
 */

interface Logger
{
  public function log(string $message): void;
}

class ConsoleLogger implements Logger
{
  public function log(string $message): void
  {
    echo "[LOG] $message\n";
  }
}

interface DatabaseRepository
{
  public function save(array $data): bool;
  public function fetch(int $id): array;
}

class UserRepository implements DatabaseRepository
{
  private Logger $logger;
  private array $storage = []; // Simulate database

  // Constructor injection
  public function __construct(Logger $logger)
  {
    $this->logger = $logger;
  }

  public function save(array $data): bool
  {
    $this->logger->log("Saving user: " . json_encode($data));
    $id = count($this->storage) + 1;
    $this->storage[$id] = $data;
    return true;
  }

  public function fetch(int $id): array
  {
    $this->logger->log("Fetching user ID: $id");
    return $this->storage[$id] ?? [];
  }
}

class UserService
{
  private UserRepository $repository;
  private Logger $logger;

  // Multiple constructor injection
  public function __construct(UserRepository $repository, Logger $logger)
  {
    $this->repository = $repository;
    $this->logger = $logger;
  }

  public function registerUser(string $email, string $name): bool
  {
    $this->logger->log("Registering user: $email");

    return $this->repository->save([
      'email' => $email,
      'name' => $name,
      'created_at' => date('Y-m-d H:i:s')
    ]);
  }
}

echo "=== Constructor Injection Demo ===\n";

$logger = new ConsoleLogger();
$userRepository = new UserRepository($logger);
$userService = new UserService($userRepository, $logger);

$userService->registerUser('john@example.com', 'John Doe');

echo "\n";


// ============================================================================
// PILLAR 3: METHOD INJECTION
// ============================================================================

echo "=== PILLAR 3: Method Injection ===\n\n";

/**
 * Method injection passes dependencies via methods instead of constructor.
 * Useful for:
 * - Optional dependencies
 * - Dependencies needed only for specific methods
 * - Changing dependencies after instantiation
 */

interface Notifier
{
  public function notify(string $message): void;
}

class EmailNotifier implements Notifier
{
  public function notify(string $message): void
  {
    echo "[EMAIL] $message\n";
  }
}

class SMSNotifier implements Notifier
{
  public function notify(string $message): void
  {
    echo "[SMS] $message\n";
  }
}

class OrderProcessor
{
  private array $orders = [];

  public function processOrder(array $orderData): bool
  {
    echo "Processing order: " . json_encode($orderData) . "\n";
    $this->orders[] = $orderData;
    return true;
  }

  // Method injection - dependency passed to specific method
  public function sendConfirmation(Notifier $notifier, string $orderId): void
  {
    $notifier->notify("Order #$orderId confirmed!");
  }

  // Another method with different injection
  public function sendReminder(Notifier $notifier, int $daysAfter): void
  {
    $notifier->notify("Your order from $daysAfter days ago - how's it going?");
  }
}

echo "=== Method Injection Demo ===\n";

$processor = new OrderProcessor();
$processor->processOrder(['item' => 'Book', 'price' => 29.99]);

// Send confirmation via email
$processor->sendConfirmation(new EmailNotifier(), 'ORD-12345');

// Send reminder via SMS
$processor->sendReminder(new SMSNotifier(), 7);

echo "\n";


// ============================================================================
// PILLAR 4: SERVICE CONTAINER - THE CORE MECHANISM
// ============================================================================

echo "=== PILLAR 4: Service Container ===\n\n";

/**
 * A Service Container (IoC Container) is a registry that manages:
 * - Creating and storing services
 * - Resolving dependencies automatically
 * - Managing service lifecycle (singleton, transient, etc.)
 * - Dependency injection configuration
 */

class SimpleContainer
{
  private array $bindings = [];
  private array $singletons = [];

  /**
   * Bind a service to the container
   * 
   * @param string $abstract Interface or service name
   * @param callable|string $concrete Factory function or class name
   */
  public function bind(string $abstract, callable|string $concrete): void
  {
    $this->bindings[$abstract] = $concrete;
  }

  /**
   * Bind a singleton - only create once, reuse for all requests
   */
  public function singleton(string $abstract, callable|string $concrete): void
  {
    $this->bind($abstract, $concrete);
    $this->singletons[$abstract] = true;
  }

  /**
   * Resolve a service from the container
   * 
   * Automatically injects dependencies based on type hints
   */
  public function resolve(string $abstract)
  {
    // Return cached singleton if available
    if (isset($this->singletons[$abstract]) && isset($this->bindings[$abstract])) {
      if (!is_callable($this->bindings[$abstract])) {
        // Already instantiated
        if (is_object($this->bindings[$abstract])) {
          return $this->bindings[$abstract];
        }
      }
    }

    if (!isset($this->bindings[$abstract])) {
      throw new Exception("Service '$abstract' not bound in container");
    }

    $concrete = $this->bindings[$abstract];

    // If it's a callable (factory), invoke it
    if (is_callable($concrete)) {
      $instance = $concrete($this);
    } else {
      // If it's a class name, instantiate it
      $instance = new $concrete();
    }

    // Cache singletons
    if (isset($this->singletons[$abstract])) {
      $this->bindings[$abstract] = $instance;
    }

    return $instance;
  }
}

// Example interfaces and classes
interface DatabaseConnection
{
  public function query(string $sql): array;
}

class MySQLConnection implements DatabaseConnection
{
  private static int $instanceCount = 0;
  private int $instanceId;

  public function __construct()
  {
    self::$instanceCount++;
    $this->instanceId = self::$instanceCount;
    echo "MySQLConnection instance #$this->instanceId created\n";
  }

  public function query(string $sql): array
  {
    return ["Result from query: $sql"];
  }
}

class UserRepository2 implements DatabaseRepository
{
  private DatabaseConnection $connection;

  public function __construct(DatabaseConnection $connection)
  {
    $this->connection = $connection;
  }

  public function save(array $data): bool
  {
    echo "Saving: " . json_encode($data) . "\n";
    return true;
  }

  public function fetch(int $id): array
  {
    return $this->connection->query("SELECT * FROM users WHERE id = $id");
  }
}

echo "=== Container Demo: Transient (New instance each time) ===\n";

$container = new SimpleContainer();

// Bind the interface to concrete class
$container->bind(DatabaseConnection::class, MySQLConnection::class);

// Each resolve creates a new instance
$conn1 = $container->resolve(DatabaseConnection::class);
$conn2 = $container->resolve(DatabaseConnection::class);
// Both different instances!

echo "\n=== Container Demo: Singleton (Reuse same instance) ===\n";

$container2 = new SimpleContainer();

// Bind as singleton
$container2->singleton(DatabaseConnection::class, MySQLConnection::class);

// These will reuse the same instance
$singleton1 = $container2->resolve(DatabaseConnection::class);
$singleton2 = $container2->resolve(DatabaseConnection::class);
// Both point to same instance!

echo "\n";


// ============================================================================
// PILLAR 5: SERVICE PROVIDERS - BOOT() VS REGISTER()
// ============================================================================

echo "=== PILLAR 5: Service Providers - boot() vs register() ===\n\n";

/**
 * Service Providers are the place where you bind services to the container.
 * 
 * register() - Bind services (should only register, no other logic)
 * boot()     - Services are booted after all are registered (initialize, configure)
 */

abstract class ServiceProvider
{
  protected SimpleContainer $container;

  public function __construct(SimpleContainer $container)
  {
    $this->container = $container;
  }

  /**
   * Register services into the container
   * 
   * This should ONLY bind services.
   * Don't depend on other services here - they might not be registered yet.
   */
  abstract public function register(): void;

  /**
   * Boot services after all are registered
   * 
   * Safe to depend on other services here.
   * Use for configuration, initialization, etc.
   */
  public function boot(): void
  {
    // Default no-op
  }
}

class LogServiceProvider extends ServiceProvider
{
  public function register(): void
  {
    echo "[LogServiceProvider] Registering...\n";

    // Only bind, don't instantiate
    $this->container->singleton(Logger::class, ConsoleLogger::class);
  }

  public function boot(): void
  {
    echo "[LogServiceProvider] Booting...\n";

    // Safe to use other services now
    $logger = $this->container->resolve(Logger::class);
    $logger->log("Application started");
  }
}

class DatabaseServiceProvider extends ServiceProvider
{
  public function register(): void
  {
    echo "[DatabaseServiceProvider] Registering...\n";

    $this->container->singleton(DatabaseConnection::class, MySQLConnection::class);
  }

  public function boot(): void
  {
    echo "[DatabaseServiceProvider] Booting...\n";

    // Initialize database, run migrations, etc.
    $connection = $this->container->resolve(DatabaseConnection::class);
    echo "[Database] Connected successfully\n";
  }
}

class Application
{
  private SimpleContainer $container;
  private array $providers = [];

  public function __construct()
  {
    $this->container = new SimpleContainer();
  }

  public function registerProvider(ServiceProvider $provider): void
  {
    $this->providers[] = $provider;
  }

  public function getContainer(): SimpleContainer
  {
    return $this->container;
  }

  public function boot(): void
  {
    echo "=== Bootstrap Phase ===\n";

    // Phase 1: Register all services
    foreach ($this->providers as $provider) {
      $provider->register();
    }

    echo "\n=== Boot Phase ===\n";

    // Phase 2: Boot all services
    foreach ($this->providers as $provider) {
      $provider->boot();
    }

    echo "\n";
  }
}

echo "=== Service Providers Demo ===\n";

$app = new Application();
$app->registerProvider(new LogServiceProvider($app->getContainer()));
$app->registerProvider(new DatabaseServiceProvider($app->getContainer()));

// Boot the application
$app->boot();

echo "\n";


// ============================================================================
// PILLAR 6: BINDING INTERFACES TO IMPLEMENTATIONS
// ============================================================================

echo "=== PILLAR 6: Binding Interfaces to Implementations ===\n\n";

/**
 * The power of DI is binding interfaces to different implementations.
 * This allows:
 * - Swapping implementations without changing code
 * - Supporting multiple drivers
 * - Testing with mocks
 */

interface Cache
{
  public function get(string $key): ?string;
  public function put(string $key, string $value): void;
}

class RedisCache implements Cache
{
  public function get(string $key): ?string
  {
    echo "[Redis] Getting: $key\n";
    return "redis_value";
  }

  public function put(string $key, string $value): void
  {
    echo "[Redis] Storing $key => $value\n";
  }
}

class FileCache implements Cache
{
  public function get(string $key): ?string
  {
    echo "[File] Getting: $key\n";
    return "file_value";
  }

  public function put(string $key, string $value): void
  {
    echo "[File] Storing $key => $value\n";
  }
}

class MemoryCache implements Cache
{
  private array $store = [];

  public function get(string $key): ?string
  {
    echo "[Memory] Getting: $key\n";
    return $this->store[$key] ?? null;
  }

  public function put(string $key, string $value): void
  {
    echo "[Memory] Storing $key => $value\n";
    $this->store[$key] = $value;
  }
}

class ConfigService
{
  private Cache $cache;

  public function __construct(Cache $cache)
  {
    $this->cache = $cache;
  }

  public function getConfig(string $key): ?string
  {
    // Same code works with ANY Cache implementation
    return $this->cache->get("config_$key");
  }

  public function setConfig(string $key, string $value): void
  {
    $this->cache->put("config_$key", $value);
  }
}

echo "=== Binding Demo: Different Cache Implementations ===\n\n";

// Scenario 1: Use Redis cache
echo "Using Redis Cache:\n";
$redisConfig = new ConfigService(new RedisCache());
$redisConfig->setConfig('app.name', 'MyApp');

// Scenario 2: Use File cache
echo "\nUsing File Cache:\n";
$fileConfig = new ConfigService(new FileCache());
$fileConfig->setConfig('app.name', 'MyApp');

// Scenario 3: Use Memory cache
echo "\nUsing Memory Cache:\n";
$memoryConfig = new ConfigService(new MemoryCache());
$memoryConfig->setConfig('app.name', 'MyApp');

// Scenario 4: Test with mock
class MockCache implements Cache
{
  public function get(string $key): ?string
  {
    return "mock_value";
  }

  public function put(string $key, string $value): void
  {
    // Mock - do nothing
  }
}

echo "\nUsing Mock Cache (for testing):\n";
$testConfig = new ConfigService(new MockCache());
$testConfig->setConfig('app.name', 'MyApp');

echo "\n";


// ============================================================================
// PILLAR 7: SINGLETONS IN THE CONTAINER
// ============================================================================

echo "=== PILLAR 7: Singletons in the Container ===\n\n";

/**
 * Singletons ensure only ONE instance exists throughout the application.
 * Use for:
 * - Database connections
 * - Configuration services
 * - Logging
 * - Cache repositories
 */

class DatabaseConnection2
{
  private static int $instanceCount = 0;
  private int $instanceNumber;
  private string $connectionId;

  public function __construct()
  {
    self::$instanceCount++;
    $this->instanceNumber = self::$instanceCount;
    $this->connectionId = uniqid('conn_');

    echo "Created database connection #$this->instanceNumber (ID: $this->connectionId)\n";
  }

  public function getId(): string
  {
    return $this->connectionId;
  }
}

class QueryBuilder
{
  private DatabaseConnection2 $connection;

  public function __construct(DatabaseConnection2 $connection)
  {
    $this->connection = $connection;
  }

  public function usingConnection(): string
  {
    return $this->connection->getId();
  }
}

class UserRepository3
{
  private DatabaseConnection2 $connection;

  public function __construct(DatabaseConnection2 $connection)
  {
    $this->connection = $connection;
  }

  public function usingConnection(): string
  {
    return $this->connection->getId();
  }
}

echo "=== Transient vs Singleton Comparison ===\n\n";

// TRANSIENT: New instance every time
echo "TRANSIENT (New instance each time):\n";

$containerTransient = new SimpleContainer();
$containerTransient->bind(DatabaseConnection2::class, DatabaseConnection2::class);

$db1 = $containerTransient->resolve(DatabaseConnection2::class);
$db2 = $containerTransient->resolve(DatabaseConnection2::class);
echo "Same instance? " . ($db1->getId() === $db2->getId() ? "Yes" : "No") . "\n";

// SINGLETON: Same instance every time
echo "\nSINGLETON (Reuse same instance):\n";

$containerSingleton = new SimpleContainer();
$containerSingleton->singleton(DatabaseConnection2::class, DatabaseConnection2::class);

$db3 = $containerSingleton->resolve(DatabaseConnection2::class);
$db4 = $containerSingleton->resolve(DatabaseConnection2::class);
echo "Same instance? " . ($db3->getId() === $db4->getId() ? "Yes" : "No") . "\n";

// REAL USE CASE: All classes share same connection
echo "\n=== Real Use Case: Shared Connection ===\n";

$container = new SimpleContainer();
$container->singleton(DatabaseConnection2::class, DatabaseConnection2::class);

// Bind services that need the connection
$container->bind(QueryBuilder::class, function ($c) {
  return new QueryBuilder($c->resolve(DatabaseConnection2::class));
});

$container->bind(UserRepository3::class, function ($c) {
  return new UserRepository3($c->resolve(DatabaseConnection2::class));
});

$qb = $container->resolve(QueryBuilder::class);
$repo = $container->resolve(UserRepository3::class);

echo "\nQueryBuilder connection: " . substr($qb->usingConnection(), 0, 20) . "...\n";
echo "UserRepository connection: " . substr($repo->usingConnection(), 0, 20) . "...\n";
echo "Same connection? " . ($qb->usingConnection() === $repo->usingConnection() ? "Yes ✓" : "No") . "\n";

echo "\n";


// ============================================================================
// SUMMARY
// ============================================================================

echo "=== SUMMARY ===\n\n";

echo "7 Pillars of Dependency Injection & IoC:\n\n";

echo "1. ✓ What is DI and why it matters\n";
echo "   - Loose coupling vs tight coupling\n";
echo "   - Testability and flexibility\n\n";

echo "2. ✓ Constructor Injection\n";
echo "   - Most common pattern\n";
echo "   - Dependencies in constructor\n\n";

echo "3. ✓ Method Injection\n";
echo "   - Pass dependencies to methods\n";
echo "   - Useful for optional dependencies\n\n";

echo "4. ✓ Service Container\n";
echo "   - Registry for services\n";
echo "   - Automatic dependency resolution\n\n";

echo "5. ✓ Service Providers\n";
echo "   - register() - bind services\n";
echo "   - boot() - initialize services\n\n";

echo "6. ✓ Binding Interfaces to Implementations\n";
echo "   - Swap implementations easily\n";
echo "   - Support multiple drivers\n\n";

echo "7. ✓ Singletons in the Container\n";
echo "   - Only one instance throughout app\n";
echo "   - Perfect for connections, config\n";
